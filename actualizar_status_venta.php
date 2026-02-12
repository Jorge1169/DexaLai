<?php
// actualizar_status_venta_completo.php - VERSIÓN COMPLETA COMO CAPTACIONES
session_start();
require_once 'config/conexiones.php';

// Configurar para JSON
header('Content-Type: application/json');

// Validar datos
if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$accion = $_POST['accion'];
$motivo_cancelacion = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$usuario_id = $_SESSION['id_cliente'] ?? 0;
$usuario_nombre = $_SESSION['username'] ?? 'Usuario desconocido';

// Validaciones
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

if (!in_array($accion, ['activar', 'desactivar'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// Para desactivar, requerir motivo (igual que captaciones)
if ($accion == 'desactivar' && empty($motivo_cancelacion)) {
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para cancelar la venta']);
    exit;
}

try {
    $conn_mysql->begin_transaction();

    // 1. OBTENER DATOS COMPLETOS DE LA VENTA
    $sql_venta = "SELECT 
        v.*,
        z.cod as cod_zona,
        c.cod as cod_cliente,
        c.nombre as nombre_cliente,
        a.cod as cod_almacen,
        a.nombre as nombre_almacen,
        d.id_direc as id_bodega_almacen,
        d.noma as bodega_nombre
    FROM ventas v
    LEFT JOIN zonas z ON v.zona = z.id_zone
    LEFT JOIN clientes c ON v.id_cliente = c.id_cli
    LEFT JOIN almacenes a ON v.id_alma = a.id_alma
    LEFT JOIN direcciones d ON v.id_direc_alma = d.id_direc
    WHERE v.id_venta = ?";
    
    $stmt_venta = $conn_mysql->prepare($sql_venta);
    $stmt_venta->bind_param('i', $id);
    $stmt_venta->execute();
    $venta = $stmt_venta->get_result()->fetch_assoc();
    
    if (!$venta) {
        throw new Exception("Venta no encontrada");
    }
    
    // 2. OBTENER DETALLE DE PRODUCTOS (igual que captaciones, status=1 siempre)
    $sql_detalle = "SELECT 
        vd.*,
        pr.cod as cod_producto,
        pr.nom_pro as nombre_producto,
        pc.precio as precio_venta
    FROM venta_detalle vd
    LEFT JOIN productos pr ON vd.id_prod = pr.id_prod
    LEFT JOIN precios pc ON vd.id_pre_venta = pc.id_precio
    WHERE vd.id_venta = ? AND vd.status = 1";
    
    $stmt_detalle = $conn_mysql->prepare($sql_detalle);
    $stmt_detalle->bind_param('i', $id);
    $stmt_detalle->execute();
    $productos = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($productos)) {
        throw new Exception("No se encontraron productos en esta venta");
    }
    
    // 3. EJECUTAR ACCIÓN ESPECÍFICA
    if ($accion == 'desactivar') {
        // ==========================================
        // DESACTIVAR VENTA (Cancelar venta = devolver producto al almacén)
        // ==========================================
        
        // Verificar que esté activa
        if ($venta['status'] != 1) {
            throw new Exception("Esta venta ya está cancelada");
        }
        
        foreach ($productos as $producto) {
            // 3.1 Obtener inventario actual
            $sql_inventario = "SELECT 
                id_inventario,
                pacas_cantidad_disponible,
                pacas_kilos_disponible,
                total_kilos_disponible
            FROM inventario_bodega 
            WHERE id_bodega = ? AND id_prod = ?";
            
            $stmt_inv = $conn_mysql->prepare($sql_inventario);
            $stmt_inv->bind_param('ii', $venta['id_bodega_almacen'], $producto['id_prod']);
            $stmt_inv->execute();
            $inventario = $stmt_inv->get_result()->fetch_assoc();
            
            if (!$inventario) {
                // Si no existe inventario, crearlo
                $sql_crear_inv = "INSERT INTO inventario_bodega 
                    (id_bodega, id_prod, id_alma,
                     pacas_cantidad_disponible, pacas_kilos_disponible, pacas_kilos_disponible,
                     total_kilos_disponible, ultima_entrada, status)
                    VALUES (?, ?, ?, 0, 0, 0, 0, NOW(), 1)";
                
                $stmt_crear = $conn_mysql->prepare($sql_crear_inv);
                $stmt_crear->bind_param('iii', 
                    $venta['id_bodega_almacen'], 
                    $producto['id_prod'],
                    $venta['id_alma']
                );
                $stmt_crear->execute();
                $id_inventario = $conn_mysql->insert_id;
                
                $inventario = [
                    'id_inventario' => $id_inventario,
                    'pacas_cantidad_disponible' => 0,
                    'pacas_kilos_disponible' => 0,
                    'total_kilos_disponible' => 0
                ];
            }
            
            // 3.2 Registrar movimiento de ENTRADA por cancelación de venta
            $observacion = "Cancelación venta #" . $id . " - " . $motivo_cancelacion;

            $pacas_nuevo = $inventario['pacas_cantidad_disponible'] + $producto['pacas_cantidad'];
            $kilos_pacas_nuevo = $inventario['pacas_kilos_disponible'] + $producto['total_kilos'];
            
            // Registrar movimiento
            $conn_mysql->query("INSERT INTO movimiento_inventario 
                (id_inventario, id_captacion, id_venta, tipo_movimiento,
                 granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                 granel_kilos_anterior, granel_kilos_nuevo,
                 pacas_cantidad_anterior, pacas_cantidad_nuevo,
                 pacas_kilos_anterior, pacas_kilos_nuevo,
                 observaciones, id_user)
                VALUES ({$inventario['id_inventario']}, NULL, $id, 'entrada', 
                        0, {$producto['pacas_cantidad']}, {$producto['total_kilos']},
                        0, 0,
                        {$inventario['pacas_cantidad_disponible']}, $pacas_nuevo,
                        {$inventario['pacas_kilos_disponible']}, $kilos_pacas_nuevo,
                        '" . $conn_mysql->real_escape_string($observacion) . "', $usuario_id)");
            
            // 3.3 Actualizar inventario (SUMAR porque estamos devolviendo producto)
            $sql_update_inv = "UPDATE inventario_bodega SET
                pacas_cantidad_disponible = ?,
                pacas_kilos_disponible = ?,
                total_kilos_disponible = total_kilos_disponible + ?,
                ultima_entrada = NOW(),
                updated_at = NOW()
                WHERE id_inventario = ?";
            
            $stmt_upd = $conn_mysql->prepare($sql_update_inv);
            $stmt_upd->bind_param('dddi',
                $pacas_nuevo,
                $kilos_pacas_nuevo,
                $producto['total_kilos'],
                $inventario['id_inventario']
            );
            
            if (!$stmt_upd->execute()) {
                throw new Exception("Error al actualizar inventario: " . $stmt_upd->error);
            }
        }
        
        // 3.4 Actualizar status de la venta a inactivo (0)
        $sql_update_venta = "UPDATE ventas SET status = 0 WHERE id_venta = ?";
        $stmt_update = $conn_mysql->prepare($sql_update_venta);
        $stmt_update->bind_param('i', $id);
        $stmt_update->execute();
        
        // 3.5 NO actualizar status del detalle (igual que en captaciones)
        
        // 3.6 Guardar motivo en tabla específica si existe
        $sql_check_table = "SHOW TABLES LIKE 'venta_cancelaciones'";
        $result = $conn_mysql->query($sql_check_table);
        
        if ($result && $result->num_rows > 0) {
            $sql_insert_motivo = "INSERT INTO venta_cancelaciones 
                (id_venta, motivo, id_usuario, usuario_nombre, fecha_cancelacion)
                VALUES (?, ?, ?, ?, NOW())";
            
            $stmt_motivo = $conn_mysql->prepare($sql_insert_motivo);
            $stmt_motivo->bind_param('isis', $id, $motivo_cancelacion, $usuario_id, $usuario_nombre);
            $stmt_motivo->execute();
        }
        
        $mensaje = "Venta cancelada exitosamente. Se devolvió el producto al inventario.";
        
    } else {
        // ==========================================
        // REACTIVAR VENTA (Reactivar venta = descontar producto del almacén)
        // ==========================================
        
        // 4.1 Verificar que esté desactivada
        if ($venta['status'] != 0) {
            throw new Exception("Esta venta ya está activa");
        }
        
        foreach ($productos as $producto) {
            // 4.2 Verificar inventario disponible
            $sql_inventario = "SELECT 
                id_inventario,
                pacas_cantidad_disponible,
                pacas_kilos_disponible,
                total_kilos_disponible
            FROM inventario_bodega 
            WHERE id_bodega = ? AND id_prod = ?";
            
            $stmt_inv = $conn_mysql->prepare($sql_inventario);
            $stmt_inv->bind_param('ii', $venta['id_bodega_almacen'], $producto['id_prod']);
            $stmt_inv->execute();
            $inventario = $stmt_inv->get_result()->fetch_assoc();
            
            if (!$inventario) {
                throw new Exception("No se encontró inventario para el producto: " . 
                    $producto['cod_producto'] . " - " . $producto['nombre_producto']);
            }
            
            // 4.3 Validar stock suficiente (similar a captaciones)
            $pacas_faltantes = max(0, $producto['pacas_cantidad'] - $inventario['pacas_cantidad_disponible']);
            $kilos_pacas_faltantes = max(0, $producto['total_kilos'] - $inventario['pacas_kilos_disponible']);
            
            if ($pacas_faltantes > 0 || $kilos_pacas_faltantes > 0) {
                throw new Exception("No hay suficiente inventario para reactivar la venta.<br>" .
                    "Producto: " . $producto['cod_producto'] . " - " . $producto['nombre_producto'] . "<br>" .
                    ($pacas_faltantes > 0 ? "Faltan " . $pacas_faltantes . " pacas<br>" : "") .
                    ($kilos_pacas_faltantes > 0 ? "Faltan " . $kilos_pacas_faltantes . " kg en pacas<br>" : "") .
                    "Inventario actual: " . 
                    $inventario['pacas_cantidad_disponible'] . " pacas, " .
                    $inventario['pacas_kilos_disponible'] . " kg en pacas");
            }
            
            // 4.4 Registrar movimiento de SALIDA por reactivación de venta
            $observacion = "Reactivación venta #" . $id;
            
            $pacas_nuevo = $inventario['pacas_cantidad_disponible'] - $producto['pacas_cantidad'];
            $kilos_pacas_nuevo = $inventario['pacas_kilos_disponible'] - $producto['total_kilos'];
            
            // Registrar movimiento
            $conn_mysql->query("INSERT INTO movimiento_inventario 
                (id_inventario, id_captacion, id_venta, tipo_movimiento,
                 granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                 granel_kilos_anterior, granel_kilos_nuevo,
                 pacas_cantidad_anterior, pacas_cantidad_nuevo,
                 pacas_kilos_anterior, pacas_kilos_nuevo,
                 observaciones, id_user)
                VALUES ({$inventario['id_inventario']}, NULL, $id, 'salida', 
                        0, {$producto['pacas_cantidad']}, {$producto['total_kilos']},
                        0, 0,
                        {$inventario['pacas_cantidad_disponible']}, $pacas_nuevo,
                        {$inventario['pacas_kilos_disponible']}, $kilos_pacas_nuevo,
                        '" . $conn_mysql->real_escape_string($observacion) . "', $usuario_id)");
            
            // 4.5 Actualizar inventario (RESTAR porque estamos reactivando venta)
            $sql_update_inv = "UPDATE inventario_bodega SET
                pacas_cantidad_disponible = ?,
                pacas_kilos_disponible = ?,
                total_kilos_disponible = total_kilos_disponible - ?,
                ultima_salida = NOW(),
                updated_at = NOW()
                WHERE id_inventario = ?";
            
            $stmt_upd = $conn_mysql->prepare($sql_update_inv);
            $stmt_upd->bind_param('dddi',
                $pacas_nuevo,
                $kilos_pacas_nuevo,
                $producto['total_kilos'],
                $inventario['id_inventario']
            );
            
            if (!$stmt_upd->execute()) {
                throw new Exception("Error al actualizar inventario: " . $stmt_upd->error);
            }
        }
        
        // 4.6 Actualizar status de la venta a activo (1)
        $sql_update_venta = "UPDATE ventas SET status = 1 WHERE id_venta = ?";
        $stmt_update = $conn_mysql->prepare($sql_update_venta);
        $stmt_update->bind_param('i', $id);
        $stmt_update->execute();
        
        // 4.7 NO actualizar status del detalle (igual que en captaciones)
        
        // 4.8 Si existe tabla de cancelaciones, marcar como reactivada
        $sql_check_table = "SHOW TABLES LIKE 'venta_cancelaciones'";
        $result = $conn_mysql->query($sql_check_table);
        
        if ($result && $result->num_rows > 0) {
            $sql_update_cancelacion = "UPDATE venta_cancelaciones 
                SET reactivada = 1, fecha_reactivacion = NOW()
                WHERE id_venta = ?";
            
            $stmt_react = $conn_mysql->prepare($sql_update_cancelacion);
            $stmt_react->bind_param('i', $id);
            $stmt_react->execute();
        }
        
        $mensaje = "Venta reactivada exitosamente. Se descontó el producto del inventario.";
    }
    
    $conn_mysql->commit();

    $accionTexto = ($accion === 'desactivar') ? 'canceló' : 'reactivó';
    logActivity('VENTA_STATUS', "Usuario {$usuario_nombre} {$accionTexto} la venta #{$id}");
    
    echo json_encode([
        'success' => true, 
        'message' => $mensaje,
        'accion' => $accion,
        'id' => $id
    ]);
    
} catch (Exception $e) {
    $conn_mysql->rollback();
    
    // Log del error
    logActivity('VENTA_STATUS_ERROR', "Error al {$accion} venta #{$id}: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>