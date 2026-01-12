<?php
// actualizar_status_captacion.php
require_once 'config/conexiones.php';

// Validar datos
if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$accion = $_POST['accion'];
$motivo_cancelacion = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

$usuario_id = $_POST['usuarioId'] ?? 0;
$usuario_nombre = $_POST['usuarioNombre'] ?? 'Usuario desconocido';

// Validaciones
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

if (!in_array($accion, ['activar', 'desactivar'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// Para desactivar, requerir motivo
if ($accion == 'desactivar' && empty($motivo_cancelacion)) {
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para cancelar la captación']);
    exit;
}

try {
    $conn_mysql->begin_transaction();

    // 1. OBTENER DATOS COMPLETOS DE LA CAPTACIÓN
    $sql_captacion = "SELECT 
        c.*,
        z.cod as cod_zona,
        p.rs as proveedor_nombre,
        a.nombre as almacen_nombre,
        d.id_direc as id_bodega,
        d.noma as bodega_nombre
    FROM captacion c
    LEFT JOIN zonas z ON c.zona = z.id_zone
    LEFT JOIN proveedores p ON c.id_prov = p.id_prov
    LEFT JOIN almacenes a ON c.id_alma = a.id_alma
    LEFT JOIN direcciones d ON c.id_direc_alma = d.id_direc
    WHERE c.id_captacion = ?";
    
    $stmt_captacion = $conn_mysql->prepare($sql_captacion);
    $stmt_captacion->bind_param('i', $id);
    $stmt_captacion->execute();
    $captacion = $stmt_captacion->get_result()->fetch_assoc();
    
    if (!$captacion) {
        throw new Exception("Captación no encontrada");
    }
    
    // 2. OBTENER DETALLE DE PRODUCTOS
    $sql_detalle = "SELECT 
        cd.*,
        pr.cod as cod_producto,
        pr.nom_pro as nombre_producto,
        pc.precio as precio_compra
    FROM captacion_detalle cd
    LEFT JOIN productos pr ON cd.id_prod = pr.id_prod
    LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
    WHERE cd.id_captacion = ? AND cd.status = 1";
    
    $stmt_detalle = $conn_mysql->prepare($sql_detalle);
    $stmt_detalle->bind_param('i', $id);
    $stmt_detalle->execute();
    $productos = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($productos)) {
        throw new Exception("No se encontraron productos en esta captación");
    }
    
    // 3. EJECUTAR ACCIÓN ESPECÍFICA
    if ($accion == 'desactivar') {
        // ==========================================
        // DESACTIVAR CAPTACIÓN (Cancelar entrada)
        // ==========================================
        
        foreach ($productos as $producto) {
            // 3.1 Verificar inventario disponible
            $sql_inventario = "SELECT 
                id_inventario,
                granel_kilos_disponible,
                pacas_cantidad_disponible,
                pacas_kilos_disponible,
                total_kilos_disponible
            FROM inventario_bodega 
            WHERE id_bodega = ? AND id_prod = ?";
            
            $stmt_inv = $conn_mysql->prepare($sql_inventario);
            $stmt_inv->bind_param('ii', $captacion['id_bodega'], $producto['id_prod']);
            $stmt_inv->execute();
            $inventario = $stmt_inv->get_result()->fetch_assoc();
            
            if (!$inventario) {
                throw new Exception("No se encontró inventario para el producto: " . 
                    $producto['cod_producto'] . " - " . $producto['nombre_producto']);
            }
            
            // 3.2 Validar stock suficiente
            $granel_faltante = max(0, $producto['granel_kilos'] - $inventario['granel_kilos_disponible']);
            $pacas_faltantes = max(0, $producto['pacas_cantidad'] - $inventario['pacas_cantidad_disponible']);
            $kilos_pacas_faltantes = max(0, $producto['pacas_kilos'] - $inventario['pacas_kilos_disponible']);
            
            if ($granel_faltante > 0 || $pacas_faltantes > 0 || $kilos_pacas_faltantes > 0) {
                throw new Exception("No hay suficiente inventario para cancelar la captación.<br>" .
                    "Producto: " . $producto['cod_producto'] . " - " . $producto['nombre_producto'] . "<br>" .
                    ($granel_faltante > 0 ? "Faltan " . $granel_faltante . " kg en granel<br>" : "") .
                    ($pacas_faltantes > 0 ? "Faltan " . $pacas_faltantes . " pacas<br>" : "") .
                    ($kilos_pacas_faltantes > 0 ? "Faltan " . $kilos_pacas_faltantes . " kg en pacas<br>" : "") .
                    "Inventario actual: " . 
                    $inventario['granel_kilos_disponible'] . " kg granel, " .
                    $inventario['pacas_cantidad_disponible'] . " pacas, " .
                    $inventario['pacas_kilos_disponible'] . " kg en pacas");
            }
            
            // 3.3 Registrar movimiento de salida por cancelación - VERSIÓN COMPLETA CORREGIDA
            $observacion = "Cancelación captación #" . $id . " - " . $motivo_cancelacion;

            $granel_nuevo = $inventario['granel_kilos_disponible'] - $producto['granel_kilos'];
            $pacas_cant_nuevo = $inventario['pacas_cantidad_disponible'] - $producto['pacas_cantidad'];
            $pacas_kilos_nuevo = $inventario['pacas_kilos_disponible'] - $producto['pacas_kilos'];

            $conn_mysql->query("INSERT INTO movimiento_inventario 
                (id_inventario, id_captacion, tipo_movimiento,
                 granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                 granel_kilos_anterior, granel_kilos_nuevo,
                 pacas_cantidad_anterior, pacas_cantidad_nuevo,
                 pacas_kilos_anterior, pacas_kilos_nuevo,
                 observaciones, id_user)
                VALUES (
                    " . $inventario['id_inventario'] . ",
                    " . $id . ",
                    'salida',
                    " . $producto['granel_kilos'] . ",
                    " . $producto['pacas_cantidad'] . ",
                    " . $producto['pacas_kilos'] . ",
                    " . $inventario['granel_kilos_disponible'] . ",
                    " . $granel_nuevo . ",
                    " . $inventario['pacas_cantidad_disponible'] . ",
                    " . $pacas_cant_nuevo . ",
                    " . $inventario['pacas_kilos_disponible'] . ",
                    " . $pacas_kilos_nuevo . ",
                    '" . $conn_mysql->real_escape_string($observacion) . "',
                    " . $usuario_id . "
                )");
            
            // 3.4 Actualizar inventario
            $sql_update_inv = "UPDATE inventario_bodega SET
                granel_kilos_disponible = ?,
                pacas_cantidad_disponible = ?,
                pacas_kilos_disponible = ?,
                total_kilos_disponible = total_kilos_disponible - ?,
                updated_at = NOW()
                WHERE id_inventario = ?";
            
            $total_kilos_salida = $producto['granel_kilos'] + $producto['pacas_kilos'];
            
            $stmt_upd = $conn_mysql->prepare($sql_update_inv);
            $stmt_upd->bind_param('ddddi',
                $granel_nuevo,
                $pacas_cant_nuevo,
                $pacas_kilos_nuevo,
                $total_kilos_salida,
                $inventario['id_inventario']
            );
            
            if (!$stmt_upd->execute()) {
                throw new Exception("Error al actualizar inventario: " . $stmt_upd->error);
            }
        }
        
        // 3.5 Actualizar status de la captación a inactivo (0)
        $sql_update_captacion = "UPDATE captacion SET status = 0 WHERE id_captacion = ?";
        $stmt_update = $conn_mysql->prepare($sql_update_captacion);
        $stmt_update->bind_param('i', $id);
        $stmt_update->execute();

        
        // 3.7 Guardar motivo en tabla específica si existe
        $sql_check_table = "SHOW TABLES LIKE 'captacion_cancelaciones'";
        $result = $conn_mysql->query($sql_check_table);
        
        if ($result && $result->num_rows > 0) {
            $sql_insert_motivo = "INSERT INTO captacion_cancelaciones 
                (id_captacion, motivo, id_usuario, usuario_nombre, fecha_cancelacion)
                VALUES (?, ?, ?, ?, NOW())";
            
            $stmt_motivo = $conn_mysql->prepare($sql_insert_motivo);
            $stmt_motivo->bind_param('isis', $id, $motivo_cancelacion, $usuario_id, $usuario_nombre);
            $stmt_motivo->execute();
        }
        
        $mensaje = "Captación cancelada exitosamente. Se descontó el material del inventario.";
        
    } else {
        // ==========================================
        // REACTIVAR CAPTACIÓN (Reingresar material)
        // ==========================================
        
        // 4.1 Verificar que esté desactivada
        if ($captacion['status'] != 0) {
            throw new Exception("Esta captación ya está activa");
        }
        
        foreach ($productos as $producto) {
            // 4.2 Obtener inventario actual
            $sql_inventario = "SELECT 
                id_inventario,
                granel_kilos_disponible,
                pacas_cantidad_disponible,
                pacas_kilos_disponible,
                total_kilos_disponible
            FROM inventario_bodega 
            WHERE id_bodega = ? AND id_prod = ?";
            
            $stmt_inv = $conn_mysql->prepare($sql_inventario);
            $stmt_inv->bind_param('ii', $captacion['id_bodega'], $producto['id_prod']);
            $stmt_inv->execute();
            $inventario = $stmt_inv->get_result()->fetch_assoc();
            
            if (!$inventario) {
                // Si no existe inventario, crearlo
                $sql_crear_inv = "INSERT INTO inventario_bodega 
                    (id_bodega, id_prod, id_alma,
                     granel_kilos_disponible, pacas_cantidad_disponible, pacas_kilos_disponible,
                     total_kilos_disponible, ultima_entrada, status)
                    VALUES (?, ?, ?, 0, 0, 0, 0, NOW(), 1)";
                
                $stmt_crear = $conn_mysql->prepare($sql_crear_inv);
                $stmt_crear->bind_param('iii', 
                    $captacion['id_bodega'], 
                    $producto['id_prod'],
                    $captacion['id_alma']
                );
                $stmt_crear->execute();
                $id_inventario = $conn_mysql->insert_id;
                
                // Volver a obtener el inventario recién creado
                $inventario = [
                    'id_inventario' => $id_inventario,
                    'granel_kilos_disponible' => 0,
                    'pacas_cantidad_disponible' => 0,
                    'pacas_kilos_disponible' => 0,
                    'total_kilos_disponible' => 0
                ];
            }
            
            // 4.3 Registrar movimiento de entrada por reactivación
            $observacion = "Reactivación captación #" . $id;
            
             $granel_nuevo = $inventario['granel_kilos_disponible'] + $producto['granel_kilos'];
            $pacas_cant_nuevo = $inventario['pacas_cantidad_disponible'] + $producto['pacas_cantidad'];
            $pacas_kilos_nuevo = $inventario['pacas_kilos_disponible'] + $producto['pacas_kilos'];

            $conn_mysql->query("INSERT INTO movimiento_inventario 
                (id_inventario, id_captacion, tipo_movimiento,
                 granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                 granel_kilos_anterior, granel_kilos_nuevo,
                 pacas_cantidad_anterior, pacas_cantidad_nuevo,
                 pacas_kilos_anterior, pacas_kilos_nuevo,
                 observaciones, id_user)
                VALUES (
                    " . $inventario['id_inventario'] . ",
                    " . $id . ",
                    'entrada',
                    " . $producto['granel_kilos'] . ",
                    " . $producto['pacas_cantidad'] . ",
                    " . $producto['pacas_kilos'] . ",
                    " . $inventario['granel_kilos_disponible'] . ",
                    " . $granel_nuevo . ",
                    " . $inventario['pacas_cantidad_disponible'] . ",
                    " . $pacas_cant_nuevo . ",
                    " . $inventario['pacas_kilos_disponible'] . ",
                    " . $pacas_kilos_nuevo . ",
                    '" . $conn_mysql->real_escape_string($observacion) . "',
                    " . $usuario_id . "
                )");
            
            // 4.4 Actualizar inventario
            $sql_update_inv = "UPDATE inventario_bodega SET
                granel_kilos_disponible = ?,
                pacas_cantidad_disponible = ?,
                pacas_kilos_disponible = ?,
                total_kilos_disponible = total_kilos_disponible + ?,
                updated_at = NOW()
                WHERE id_inventario = ?";
            
            $total_kilos_entrada = $producto['granel_kilos'] + $producto['pacas_kilos'];
            
            $stmt_upd = $conn_mysql->prepare($sql_update_inv);
            $stmt_upd->bind_param('ddddi',
                $granel_nuevo,
                $pacas_cant_nuevo,
                $pacas_kilos_nuevo,
                $total_kilos_entrada,
                $inventario['id_inventario']
            );
            
            if (!$stmt_upd->execute()) {
                throw new Exception("Error al actualizar inventario: " . $stmt_upd->error);
            }
        }
        
        // 4.5 Actualizar status de la captación a activo (1)
        $sql_update_captacion = "UPDATE captacion SET status = 1 WHERE id_captacion = ?";
        $stmt_update = $conn_mysql->prepare($sql_update_captacion);
        $stmt_update->bind_param('i', $id);
        $stmt_update->execute();
        
        // 4.6 Actualizar status del detalle
        $sql_update_detalle = "UPDATE captacion_detalle SET status = 1 WHERE id_captacion = ?";
        $stmt_det = $conn_mysql->prepare($sql_update_detalle);
        $stmt_det->bind_param('i', $id);
        $stmt_det->execute();
        
        $mensaje = "Captación reactivada exitosamente. Se reingresó el material al inventario.";
    }
    
    $conn_mysql->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $mensaje
    ]);
    
} catch (Exception $e) {
    $conn_mysql->rollback();
    
    // Log del error
    error_log("Error en actualizar_status_captacion: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>