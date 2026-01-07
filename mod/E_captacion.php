<?php
// Obtener ID de captación
$id_captacion = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_captacion <= 0) {
    alert("ID de captación no válido", 0, "captacion");
    exit;
}

// Obtener captación principal
$sql = "SELECT c.* 
        FROM captacion c 
        WHERE c.id_captacion = ? LIMIT 1";
$stmt = $conn_mysql->prepare($sql);
$stmt->bind_param('i', $id_captacion);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows == 0) {
    alert("Captación no encontrada", 0, "captacion");
    exit;
}
$capt = $res->fetch_assoc();

// Obtener tipo de flete y precio desde captacion_flete y precios
$tipo_flete_actual = '';
$precio_flete_actual = 0;
$id_pre_flete_actual = 0;

$sql_flete = "SELECT cf.id_pre_flete, p.tipo, p.precio 
              FROM captacion_flete cf 
              LEFT JOIN precios p ON cf.id_pre_flete = p.id_precio 
              WHERE cf.id_captacion = ?";
$stmt_flete = $conn_mysql->prepare($sql_flete);
$stmt_flete->bind_param('i', $id_captacion);
$stmt_flete->execute();
$res_flete = $stmt_flete->get_result();
if ($res_flete && $res_flete->num_rows > 0) {
    $flete = $res_flete->fetch_assoc();
    $tipo_flete_actual = $flete['tipo'] ?? '';
    $precio_flete_actual = $flete['precio'] ?? 0;
    $id_pre_flete_actual = $flete['id_pre_flete'] ?? 0;
}

// Inicializar sesión de productos si no existe
if (!isset($_SESSION['productos_agregados'])) {
    $_SESSION['productos_agregados'] = [];
}

// Si es una solicitud GET, cargar productos existentes
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_SESSION['productos_agregados'] = []; // Limpiar primero
    
    // Cargar detalles existentes
    $detSql = "SELECT cd.*, p.cod as cod_producto, p.nom_pro as nombre_producto, 
                      pc.precio as precio_valor, pc.id_precio as id_precio,
                      p.zona as zona_producto
               FROM captacion_detalle cd
               LEFT JOIN productos p ON cd.id_prod = p.id_prod
               LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
               WHERE cd.id_captacion = ? AND cd.status = 1";
    $stmtDet = $conn_mysql->prepare($detSql);
    $stmtDet->bind_param('i', $id_captacion);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    
    while ($r = $resDet->fetch_assoc()) {
        // Determinar tipo de almacenamiento
        $tipo_almacen = 'granel';
        if ($r['granel_kilos'] == 0 && $r['pacas_cantidad'] > 0) {
            $tipo_almacen = 'pacas';
        } elseif ($r['granel_kilos'] > 0 && $r['pacas_cantidad'] > 0) {
            $tipo_almacen = 'mixto';
        }
        
        // Calcular peso promedio
        $peso_promedio = 0;
        if ($r['pacas_cantidad'] > 0 && $r['pacas_kilos'] > 0) {
            $peso_promedio = $r['pacas_kilos'] / $r['pacas_cantidad'];
        }
        
        $_SESSION['productos_agregados'][] = [
            'id_detalle' => (int)$r['id_detalle'],
            'id_producto' => (int)$r['id_prod'],
            'cod_producto' => $r['cod_producto'],
            'nombre_producto' => $r['nombre_producto'],
            'id_precio_compra' => (int)$r['id_pre_compra'],
            'precio_valor' => (float)($r['precio_valor'] ?? 0),
            'tipo_almacen' => $tipo_almacen,
            'granel_kilos' => (float)$r['granel_kilos'],
            'pacas_cantidad' => (int)$r['pacas_cantidad'],
            'pacas_kilos' => (float)$r['pacas_kilos'],
            'peso_promedio' => $peso_promedio,
            'total_kilos' => (float)$r['total_kilos'],
            'observaciones' => $r['observaciones'] ?? '',
            'zona_producto' => (int)$r['zona_producto']
        ];
    }
}

// Procesar guardar edición
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_edicion'])) {
    // Validar que existan productos
    if (empty($_SESSION['productos_agregados'])) {
        alert("Debe haber al menos un producto", 0, "E_captacion&id=".$id_captacion);
        exit;
    }
    
    // Validar campos obligatorios
    $required = ['fecha_captacion', 'zona', 'idProveedor', 'bodgeProv', 'idAlmacen', 'bodgeAlm', 'idFletero', 'tipo_flete'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            alert("El campo " . $field . " es requerido", 0, "E_captacion&id=".$id_captacion);
            exit;
        }
    }
    
    // Asignar valores
    $fecha_captacion = $_POST['fecha_captacion'];
    $zona = $_POST['zona'];
    $idProveedor = (int)$_POST['idProveedor'];
    $bodgeProv = (int)$_POST['bodgeProv'];
    $idAlmacen = (int)$_POST['idAlmacen'];
    $bodgeAlm = (int)$_POST['bodgeAlm'];
    $idFletero = (int)$_POST['idFletero'];
    $tipo_flete = $_POST['tipo_flete'];
    $id_preFle = isset($_POST['id_preFle']) ? (int)$_POST['id_preFle'] : 0;
    $idUser = $_SESSION['id_user'] ?? $capt['id_user'];
    
    // Validar que tipo_flete sea válido
    if (!in_array($tipo_flete, ['MFT', 'MFV'])) {
        alert("Tipo de flete no válido. Use MFT o MFV", 0, "E_captacion&id=".$id_captacion);
        exit;
    }
    
    try {
        $conn_mysql->begin_transaction();
        
        // 1) Actualizar tabla captacion
        $uSql = "UPDATE captacion SET 
                fecha_captacion = ?, 
                zona = ?, 
                id_prov = ?, 
                id_direc_prov = ?, 
                id_alma = ?, 
                id_direc_alma = ?, 
                id_transp = ?, 
                id_user = ?
                WHERE id_captacion = ?";
        
        $uStmt = $conn_mysql->prepare($uSql);
        $uStmt->bind_param('siiiiiiii', 
            $fecha_captacion, 
            $zona, 
            $idProveedor, 
            $bodgeProv, 
            $idAlmacen, 
            $bodgeAlm, 
            $idFletero, 
            $idUser,
            $id_captacion
        );
        
        if (!$uStmt->execute()) {
            throw new Exception("Error al actualizar captación: " . $uStmt->error);
        }
        
        // 2) Obtener productos ORIGINALES antes de modificar (para inventario)
        $sql_originales = "SELECT * FROM captacion_detalle WHERE id_captacion = ?";
        $stmt_originales = $conn_mysql->prepare($sql_originales);
        $stmt_originales->bind_param('i', $id_captacion);
        $stmt_originales->execute();
        $productos_originales = $stmt_originales->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Guardar los originales en variable para el inventario
        $productos_originales_array = [];
        foreach ($productos_originales as $prod) {
            $productos_originales_array[$prod['id_prod']] = [
                'granel_kilos' => (float)$prod['granel_kilos'],
                'pacas_cantidad' => (int)$prod['pacas_cantidad'],
                'pacas_kilos' => (float)$prod['pacas_kilos'],
                'total_kilos' => (float)$prod['total_kilos'],
                'status' => (int)$prod['status']
            ];
        }
        
        // 3) Marcar TODOS los detalles como inactivos primero
        $delSql = "UPDATE captacion_detalle SET status = 0 WHERE id_captacion = ?";
        $delStmt = $conn_mysql->prepare($delSql);
        $delStmt->bind_param('i', $id_captacion);
        $delStmt->execute();
        
        // 4) Para cada producto en sesión, verificar si ya existe y actualizar/insertar
        $productos_editados = [];
        foreach ($_SESSION['productos_agregados'] as $prod) {
            // Convertir tipo_almacen para BD (granel/pacas)
            $tipo_almacen_bd = $prod['tipo_almacen'];
            if ($tipo_almacen_bd == 'pacas' && $prod['granel_kilos'] > 0) {
                $tipo_almacen_bd = 'pacas';
            }
            
            // Verificar si este producto ya existe en la captación (aunque esté inactivo)
            $checkSql = "SELECT id_detalle FROM captacion_detalle 
                        WHERE id_captacion = ? AND id_prod = ?";
            $checkStmt = $conn_mysql->prepare($checkSql);
            $checkStmt->bind_param('ii', $id_captacion, $prod['id_producto']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult && $checkResult->num_rows > 0) {
                // Producto existe, actualizarlo (reactivar con status = 1)
                $existing = $checkResult->fetch_assoc();
                $updateSql = "UPDATE captacion_detalle SET 
                             id_pre_compra = ?, 
                             tipo_almacen = ?, 
                             granel_kilos = ?, 
                             pacas_cantidad = ?, 
                             pacas_kilos = ?, 
                             pacas_peso_promedio = ?, 
                             total_kilos = ?, 
                             observaciones = ?, 
                             status = 1,
                             id_user = ?
                             WHERE id_detalle = ?";
                
                $updateStmt = $conn_mysql->prepare($updateSql);
                $updateStmt->bind_param('isdidddsii',
                    $prod['id_precio_compra'],
                    $tipo_almacen_bd,
                    $prod['granel_kilos'],
                    $prod['pacas_cantidad'],
                    $prod['pacas_kilos'],
                    $prod['peso_promedio'],
                    $prod['total_kilos'],
                    $prod['observaciones'],
                    $idUser,
                    $existing['id_detalle']
                );
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Error al actualizar detalle: " . $updateStmt->error);
                }
            } else {
                // Producto no existe, insertarlo nuevo
                $insSql = "INSERT INTO captacion_detalle 
                          (id_captacion, id_prod, id_pre_compra, tipo_almacen, 
                           granel_kilos, pacas_cantidad, pacas_kilos, pacas_peso_promedio, 
                           total_kilos, observaciones, id_user, status) 
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,1)";
                
                $insStmt = $conn_mysql->prepare($insSql);
                $insStmt->bind_param('iiisdddddsi',
                    $id_captacion,
                    $prod['id_producto'],
                    $prod['id_precio_compra'],
                    $tipo_almacen_bd,
                    $prod['granel_kilos'],
                    $prod['pacas_cantidad'],
                    $prod['pacas_kilos'],
                    $prod['peso_promedio'],
                    $prod['total_kilos'],
                    $prod['observaciones'],
                    $idUser
                );
                
                if (!$insStmt->execute()) {
                    throw new Exception("Error al insertar detalle: " . $insStmt->error);
                }
            }
            
            // Guardar producto editado para inventario
            $productos_editados[$prod['id_producto']] = [
                'granel_kilos' => $prod['granel_kilos'],
                'pacas_cantidad' => $prod['pacas_cantidad'],
                'pacas_kilos' => $prod['pacas_kilos'],
                'total_kilos' => $prod['total_kilos']
            ];
        }
        
        // 5) Actualizar/Insertar relación de flete
        $checkF = $conn_mysql->prepare("SELECT id_capt_flete FROM captacion_flete WHERE id_captacion = ? LIMIT 1");
        $checkF->bind_param('i', $id_captacion);
        $checkF->execute();
        $rf = $checkF->get_result();
        
        if ($rf && $rf->num_rows > 0) {
            $uf = $conn_mysql->prepare("UPDATE captacion_flete SET id_fletero = ?, id_pre_flete = ? WHERE id_captacion = ?");
            $uf->bind_param('iii', $idFletero, $id_preFle, $id_captacion);
            if (!$uf->execute()) {
                throw new Exception("Error al actualizar flete: " . $uf->error);
            }
        } else {
            $if = $conn_mysql->prepare("INSERT INTO captacion_flete (id_captacion, id_fletero, id_pre_flete) VALUES (?,?,?)");
            $if->bind_param('iii', $id_captacion, $idFletero, $id_preFle);
            if (!$if->execute()) {
                throw new Exception("Error al insertar flete: " . $if->error);
            }
        }
        
        // 6) Actualizar inventario con lógica de edición
        actualizarInventarioCaptacionEdicion($id_captacion, $conn_mysql, $idUser, $productos_originales_array, $productos_editados);
        
        $conn_mysql->commit();
        
        // Limpiar sesión de productos
        unset($_SESSION['productos_agregados']);
        
        alert("Captación actualizada correctamente", 1, "V_captacion&id=".$id_captacion);
        logActivity('EDITAR', 'Editó captación ' . $id_captacion);
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "E_captacion&id=".$id_captacion);
    }
    exit;
}

// Función para actualizar inventario en ediciones
function actualizarInventarioCaptacionEdicion($id_captacion, $conn_mysql, $idUser, $productos_originales, $productos_editados) {
    // 1. Obtener datos de la captación (bodega)
    $sql_capt = "SELECT c.*, d.id_alma, d.id_direc as id_bodega 
                 FROM captacion c 
                 LEFT JOIN direcciones d ON c.id_direc_alma = d.id_direc 
                 WHERE c.id_captacion = ?";
    $stmt_capt = $conn_mysql->prepare($sql_capt);
    $stmt_capt->bind_param('i', $id_captacion);
    $stmt_capt->execute();
    $captacion = $stmt_capt->get_result()->fetch_assoc();
    
    if (!$captacion) return false;
    
    $id_bodega = $captacion['id_bodega'];
    $id_alma = $captacion['id_alma'];
    
    // 2. Para cada producto original, restar del inventario si fue eliminado o modificado
    foreach ($productos_originales as $id_prod => $producto_original) {
        // Solo restar si el producto tenía status = 1 (activo)
        if ($producto_original['status'] == 1) {
            // Verificar si el producto sigue en la edición
            $sigue_activo = isset($productos_editados[$id_prod]);
            
            if (!$sigue_activo) {
                // Producto fue ELIMINADO - Restar todo del inventario
                ajustarInventario($id_bodega, $id_prod, $id_alma, 
                    -$producto_original['granel_kilos'],
                    -$producto_original['pacas_cantidad'],
                    -$producto_original['pacas_kilos'],
                    $idUser, $id_captacion, 'salida_edicion', $conn_mysql);
            } else {
                // Producto fue MODIFICADO - Calcular diferencia
                $producto_editado = $productos_editados[$id_prod];
                
                $diff_granel = $producto_editado['granel_kilos'] - $producto_original['granel_kilos'];
                $diff_pacas_cant = $producto_editado['pacas_cantidad'] - $producto_original['pacas_cantidad'];
                $diff_pacas_kilos = $producto_editado['pacas_kilos'] - $producto_original['pacas_kilos'];
                
                // Si hay diferencia, ajustar inventario
                if ($diff_granel != 0 || $diff_pacas_cant != 0 || $diff_pacas_kilos != 0) {
                    $tipo_movimiento = ($diff_granel > 0 || $diff_pacas_cant > 0 || $diff_pacas_kilos > 0) 
                        ? 'ajuste_entrada' 
                        : 'ajuste_salida';
                    
                    ajustarInventario($id_bodega, $id_prod, $id_alma, 
                        $diff_granel,
                        $diff_pacas_cant,
                        $diff_pacas_kilos,
                        $idUser, $id_captacion, $tipo_movimiento, $conn_mysql);
                }
            }
        }
    }
    
    // 3. Para cada producto editado que es NUEVO (no estaba en originales)
    foreach ($productos_editados as $id_prod => $producto_editado) {
        if (!isset($productos_originales[$id_prod]) || $productos_originales[$id_prod]['status'] == 0) {
            // Producto es NUEVO o estaba inactivo - Sumar al inventario
            ajustarInventario($id_bodega, $id_prod, $id_alma, 
                $producto_editado['granel_kilos'],
                $producto_editado['pacas_cantidad'],
                $producto_editado['pacas_kilos'],
                $idUser, $id_captacion, 'entrada_nueva', $conn_mysql);
        }
    }
    
    return true;
}

// Función auxiliar para ajustar inventario (CORREGIDA)
function ajustarInventario($id_bodega, $id_prod, $id_alma, $granel_kilos, $pacas_cantidad, $pacas_kilos, 
                          $idUser, $id_captacion, $tipo_movimiento, $conn_mysql) {
    
    // Si no hay cambios, no hacer nada
    if ($granel_kilos == 0 && $pacas_cantidad == 0 && $pacas_kilos == 0) {
        return;
    }
    
    // 1. Verificar si ya existe registro en inventario y obtener valores actuales
    $sql_check = "SELECT id_inventario, 
                         granel_kilos_disponible, 
                         pacas_cantidad_disponible, 
                         pacas_kilos_disponible 
                  FROM inventario_bodega 
                  WHERE id_bodega = ? AND id_prod = ?";
    $stmt_check = $conn_mysql->prepare($sql_check);
    $stmt_check->bind_param('ii', $id_bodega, $id_prod);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // Valores anteriores (antes del ajuste)
    $granel_anterior = 0;
    $pacas_cant_anterior = 0;
    $pacas_kilos_anterior = 0;
    
    if ($result_check && $result_check->num_rows > 0) {
        // Existe - Obtener valores actuales
        $existente = $result_check->fetch_assoc();
        $id_inventario = $existente['id_inventario'];
        $granel_anterior = $existente['granel_kilos_disponible'];
        $pacas_cant_anterior = $existente['pacas_cantidad_disponible'];
        $pacas_kilos_anterior = $existente['pacas_kilos_disponible'];
        
        // Calcular nuevos valores
        $nuevo_granel = $granel_anterior + $granel_kilos;
        $nuevo_pacas_cant = $pacas_cant_anterior + $pacas_cantidad;
        $nuevo_pacas_kilos = $pacas_kilos_anterior + $pacas_kilos;
        $nuevo_total = $nuevo_granel + $nuevo_pacas_kilos;
        
        // Asegurar que no queden valores negativos
        if ($nuevo_granel < 0) $nuevo_granel = 0;
        if ($nuevo_pacas_cant < 0) $nuevo_pacas_cant = 0;
        if ($nuevo_pacas_kilos < 0) $nuevo_pacas_kilos = 0;
        if ($nuevo_total < 0) $nuevo_total = 0;
        
        $sql_update = "UPDATE inventario_bodega SET 
                      granel_kilos_disponible = ?,
                      pacas_cantidad_disponible = ?,
                      pacas_kilos_disponible = ?,
                      total_kilos_disponible = ?,
                      updated_at = NOW(),
                      id_user = ?
                      WHERE id_inventario = ?";
        
        $stmt_update = $conn_mysql->prepare($sql_update);
        $stmt_update->bind_param('diddii',
            $nuevo_granel,
            $nuevo_pacas_cant,
            $nuevo_pacas_kilos,
            $nuevo_total,
            $idUser,
            $id_inventario
        );
        $stmt_update->execute();
        
    } else {
        // No existe - Crear nuevo (solo si estamos agregando, no restando)
        if ($granel_kilos > 0 || $pacas_cantidad > 0 || $pacas_kilos > 0) {
            $total_kilos = $granel_kilos + $pacas_kilos;
            $peso_promedio = 0;
            if ($pacas_cantidad > 0 && $pacas_kilos > 0) {
                $peso_promedio = $pacas_kilos / $pacas_cantidad;
            }
            
            $sql_insert = "INSERT INTO inventario_bodega 
                          (id_bodega, id_prod, id_alma, 
                           granel_kilos_disponible, pacas_cantidad_disponible, 
                           pacas_kilos_disponible, pacas_peso_promedio,
                           total_kilos_disponible, ultima_entrada, id_user) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            $stmt_insert = $conn_mysql->prepare($sql_insert);
            $stmt_insert->bind_param('iiididddi',
                $id_bodega,
                $id_prod,
                $id_alma,
                $granel_kilos,
                $pacas_cantidad,
                $pacas_kilos,
                $peso_promedio,
                $total_kilos,
                $idUser
            );
            $stmt_insert->execute();
            
            $id_inventario = $conn_mysql->insert_id;
            // Para nuevo registro, los valores anteriores son 0
            $granel_anterior = 0;
            $pacas_cant_anterior = 0;
            $pacas_kilos_anterior = 0;
        } else {
            // No podemos crear inventario con valores negativos
            return;
        }
    }
    
    // 2. Registrar movimiento en la tabla (con estructura correcta)
    if (isset($id_inventario)) {
        // Calcular valores nuevos después del ajuste
        $granel_nuevo = $granel_anterior + $granel_kilos;
        $pacas_cant_nuevo = $pacas_cant_anterior + $pacas_cantidad;
        $pacas_kilos_nuevo = $pacas_kilos_anterior + $pacas_kilos;
        
        // Asegurar que no queden valores negativos
        if ($granel_nuevo < 0) $granel_nuevo = 0;
        if ($pacas_cant_nuevo < 0) $pacas_cant_nuevo = 0;
        if ($pacas_kilos_nuevo < 0) $pacas_kilos_nuevo = 0;
        
        // Mapear tipos de movimiento a los permitidos por la tabla
        $tipos_permitidos = ['entrada', 'salida', 'ajuste', 'conversion'];
        if (!in_array($tipo_movimiento, $tipos_permitidos)) {
            // Mapear tipos personalizados a los permitidos
            $mapeo_tipos = [
                'salida_edicion' => 'salida',
                'ajuste_entrada' => 'ajuste',
                'ajuste_salida' => 'ajuste',
                'entrada_nueva' => 'entrada'
            ];
            $tipo_movimiento = isset($mapeo_tipos[$tipo_movimiento]) ? $mapeo_tipos[$tipo_movimiento] : 'ajuste';
        }
        
        $observaciones = "Edición de captación #" . $id_captacion;
        
        // Insertar en movimiento_inventario con todas las columnas necesarias
        $sql_movimiento = "INSERT INTO movimiento_inventario 
                          (id_inventario, id_captacion, tipo_movimiento,
                           granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                           granel_kilos_anterior, granel_kilos_nuevo,
                           pacas_cantidad_anterior, pacas_cantidad_nuevo,
                           pacas_kilos_anterior, pacas_kilos_nuevo,
                           observaciones, id_user) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_mov = $conn_mysql->prepare($sql_movimiento);
        $stmt_mov->bind_param('iisdidddiiddsi',
            $id_inventario,
            $id_captacion,
            $tipo_movimiento,
            $granel_kilos,
            $pacas_cantidad,
            $pacas_kilos,
            $granel_anterior,    // granel_kilos_anterior
            $granel_nuevo,       // granel_kilos_nuevo
            $pacas_cant_anterior, // pacas_cantidad_anterior
            $pacas_cant_nuevo,   // pacas_cantidad_nuevo
            $pacas_kilos_anterior, // pacas_kilos_anterior
            $pacas_kilos_nuevo,  // pacas_kilos_nuevo
            $observaciones,
            $idUser
        );
        
        if (!$stmt_mov->execute()) {
            error_log("Error al registrar movimiento: " . $stmt_mov->error);
            // No lanzar excepción para no romper el flujo principal
        }
    }
}

// Preparar valores para el formulario
$fecha_seleccionada = $capt['fecha_captacion'];
$zona_actual = $capt['zona'];
$folio = $capt['folio'];

// Obtener datos relacionados
$proveedor_actual = $capt['id_prov'];
$almacen_actual = $capt['id_alma'];
$fletero_actual = $capt['id_transp'];
$bodega_prov_actual = $capt['id_direc_prov'];
$bodega_alm_actual = $capt['id_direc_alma'];
?>
<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Captación - <?= htmlspecialchars($capt['folio']) ?></h5>
            <div>
                <a class="btn btn-sm btn-info me-2" href="?p=V_captacion&id=<?= $id_captacion ?>"><i class="bi bi-eye me-1"></i>Ver</a>
                <a class="btn btn-sm btn-secondary" href="?p=captacion"><i class="bi bi-arrow-left me-1"></i>Regresar</a>
            </div>
        </div>
        <div class="card-body">
            <form id="formEditar" method="post" action="">
                <input type="hidden" name="id_captacion" value="<?= $id_captacion ?>">
                
                <!-- SECCIÓN 1: Información Básica -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-info-circle me-2"></i>Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label required">Fecha</label>
                            <input type="date" name="fecha_captacion" id="fecha_captacion" 
                                   class="form-control" value="<?= htmlspecialchars(substr($fecha_seleccionada,0,10)) ?>"
                                   onchange="actualizarFolioYPrecios()" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Zona</label>
                            <select name="zona" id="zona" class="form-select" onchange="cambiarZona()" required>
                                <option value="">Selecciona zona...</option>
                                <?php
                                $z0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' and id_zone = '$zona_actual' ORDER BY id_zone");
                                while ($z1 = mysqli_fetch_array($z0)) {
                                    $sel = $z1['id_zone'] == $zona_actual ? 'selected' : '';
                                    echo "<option value=\"{$z1['id_zone']}\" $sel>{$z1['PLANTA']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="resulFolio">
                            <label class="form-label">Folio</label>
                            <input type="text" class="form-control" 
                                   value="<?= "C-".htmlspecialchars($capt['zona'])."-".$folio ?>" 
                                   disabled readonly>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: Proveedor y Bodega -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-truck me-2"></i>Proveedor y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-6" id="resulProv">
                            <label class="form-label required">Proveedor</label>
                            <select name="idProveedor" id="idProveedor" class="form-select" onchange="cargarBodegasProveedor()" required>
                                <option value="">Selecciona proveedor...</option>
                                <?php
                                // Cargar proveedores de la zona actual
                                $Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$zona_actual'");
                                while ($Prov_id1 = mysqli_fetch_array($Prov_id0)) {
                                    $sel = $Prov_id1['id_prov'] == $proveedor_actual ? 'selected' : '';
                                    echo "<option value=\"{$Prov_id1['id_prov']}\" $sel>{$Prov_id1['cod']} / {$Prov_id1['rs']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="BodePro">
                            <label class="form-label required">Bodega Proveedor</label>
                            <select name="bodgeProv" id="bodgeProv" class="form-select" required>
                                <option value="">Selecciona bodega...</option>
                                <?php
                                // Cargar bodegas del proveedor actual
                                $bpAll = $conn_mysql->query("SELECT * FROM direcciones WHERE id_prov = '$proveedor_actual' AND status = 1");
                                while ($bp = mysqli_fetch_array($bpAll)) {
                                    $sel = $bp['id_direc'] == $bodega_prov_actual ? 'selected' : '';
                                    $verCor = ($bp['email'] == '') ? '' : '📧' ;
                                    echo "<option value=\"{$bp['id_direc']}\" $sel>{$bp['cod_al']} - {$bp['noma']} $verCor</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 3: Almacén y Bodega -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-building me-2"></i>Almacén y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-6" id="resulAlm">
                            <label class="form-label required">Almacén</label>
                            <select name="idAlmacen" id="idAlmacen" class="form-select" onchange="cargarBodegasAlmacen()" required>
                                <option value="">Selecciona almacén...</option>
                                <?php
                                // Cargar almacenes de la zona actual
                                $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$zona_actual'");
                                while ($Alm_id1 = mysqli_fetch_array($Alm_id0)) {
                                    $sel = $Alm_id1['id_alma'] == $almacen_actual ? 'selected' : '';
                                    echo "<option value=\"{$Alm_id1['id_alma']}\" $sel>{$Alm_id1['cod']} - {$Alm_id1['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="BodeAlm">
                            <label class="form-label required">Bodega Almacén</label>
                            <select name="bodgeAlm" id="bodgeAlm" class="form-select" required>
                                <option value="">Selecciona bodega...</option>
                                <?php
                                // Cargar bodegas del almacén actual
                                $baAll = $conn_mysql->query("SELECT * FROM direcciones WHERE id_alma = '$almacen_actual' AND status = 1");
                                while ($ba = mysqli_fetch_array($baAll)) {
                                    $sel = $ba['id_direc'] == $bodega_alm_actual ? 'selected' : '';
                                    echo "<option value=\"{$ba['id_direc']}\" $sel>{$ba['cod_al']} - {$ba['noma']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 4: Fletero y Flete -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-truck-flatbed me-2"></i>Fletero y Flete</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulfLE">
                            <label class="form-label required">Fletero</label>
                            <select name="idFletero" id="idFletero" class="form-select" onchange="cargarPrecioFlete()" required>
                                <option value="">Selecciona fletero...</option>
                                <?php
                                // Cargar fleteros de la zona actual
                                $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$zona_actual'");
                                while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
                                    $sel = $Fle_id1['id_transp'] == $fletero_actual ? 'selected' : '';
                                    $verCorF = (empty($Fle_id1['correo'])) ? '' : '📧' ;
                                    echo "<option value=\"{$Fle_id1['id_transp']}\" $sel>{$Fle_id1['placas']} - {$Fle_id1['razon_so']} $verCorF</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="TipoFlete">
                            <label class="form-label required">Tipo de Flete</label>
                            <select name="tipo_flete" id="tipo_flete" class="form-select" onchange="cargarPrecioFlete()" required>
                                <option value="">Selecciona tipo...</option>
                                <option value="MFT" <?= $tipo_flete_actual == 'MFT' ? 'selected' : '' ?>>MFT (Por tonelada)</option>
                                <option value="MFV" <?= $tipo_flete_actual == 'MFV' ? 'selected' : '' ?>>MFV (Por viaje)</option>
                            </select>
                        </div>
                        <div class="col-md-5" id="PreFle">
                            <label class="form-label required">Precio Flete</label>
                            <select name="id_preFle" id="id_preFle" class="form-select" required>
                                <option value="">Cargando precios...</option>
                                <?php
                                // Cargar precio actual del flete
                                if ($id_pre_flete_actual > 0) {
                                    $pfQuery = $conn_mysql->query("SELECT * FROM precios WHERE id_precio = '$id_pre_flete_actual'");
                                    if ($pfRow = mysqli_fetch_array($pfQuery)) {
                                        echo "<option value=\"{$pfRow['id_precio']}\" selected>
                                                \${$pfRow['precio']} - {$pfRow['tipo']}
                                              </option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 5: Agregar Producto -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-plus-circle me-2"></i>Agregar Producto</h5>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4" id="resulProd">
                            <label class="form-label required">Producto</label>
                            <select id="idProd" class="form-select" onchange="cargarPrecioCompra()">
                                <option value="">Selecciona producto...</option>
                                <?php
                                // Cargar productos de la zona actual
                                $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$zona_actual'");
                                while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
                                    echo "<option value=\"{$Prod_id1['id_prod']}\">{$Prod_id1['cod']} - {$Prod_id1['nom_pro']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="PrePro">
                            <label class="form-label required">Precio compra</label>
                            <select id="id_prePD" class="form-select">
                                <option value="">Selecciona producto primero</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label required">Tipo</label>
                            <select id="tipo_almacen" class="form-select" onchange="cambiarTipoAlmacen(this.value)">
                                <option value="granel">Granel</option>
                                <option value="pacas">Pacas</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="campos_granel">
                            <label class="form-label required">Kilos granel</label>
                            <input type="number" step="0.01" min="0.01" id="granel_kilos" class="form-control" value="0">
                        </div>
                        <div class="col-md-2" id="campos_pacas" style="display:none;">
                            <label class="form-label required">Cant. Pacas</label>
                            <input type="number" min="1" id="pacas_cantidad" class="form-control" value="0">
                        </div>
                        <div class="col-md-2" id="campos_pacas_k" style="display:none;">
                            <label class="form-label required">Kilos Pacas</label>
                            <input type="number" step="0.01" min="0.01" id="pacas_kilos" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-10">
                            <label class="form-label">Observaciones (opcional)</label>
                            <input type="text" id="observaciones_prod" class="form-control" 
                                   placeholder="Ej: Producto húmedo, pacas irregulares, etc.">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="btnAgregarProd" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i> Agregar
                            </button>
                        </div>
                    </div>
                    <div id="error-producto" class="alert alert-danger mt-2" style="display: none;"></div>
                </div>
                
                <!-- SECCIÓN 6: Productos Agregados -->
                <div class="form-section">
                    <h5 class="section-header"><i class="bi bi-list-check me-2"></i>Productos Agregados</h5>
                    <div id="tabla-productos-container">
                        <?php include 'generar_tabla_productos.php'; ?>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <button type="button" class="btn btn-warning" onclick="confirmarLimpiar()">
                            <i class="bi bi-eraser me-1"></i> Limpiar Todo
                        </button>
                    </div>
                    <div>
                        <a href="?p=captacion" class="btn btn-secondary me-2">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                        <button type="submit" name="guardar_edicion" class="btn btn-success" 
                                id="btnGuardar" <?= empty($_SESSION['productos_agregados']) ? 'disabled' : '' ?>>
                            <i class="bi bi-check-circle me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#zona, #idProveedor, #idAlmacen, #idFletero, #tipo_flete, #id_preFle, #idProd').select2({
        placeholder: "Selecciona una opción",
        allowClear: false,
        language: "es",
        width: '100%'
    });
    
    // Inicializar tipo almacen
    cambiarTipoAlmacen('granel');
    
    // Cargar precio de flete inicial
    setTimeout(function() {
        if ($('#idFletero').val() && $('#tipo_flete').val()) {
            cargarPrecioFlete();
        }
    }, 500);
    
    // Evento para agregar producto
    $('#btnAgregarProd').click(function() {
        agregarProductoConAjax();
    });
});

function cambiarZona() {
    var zonaId = $('#zona').val();
    var fecha = $('#fecha_captacion').val();
    
    if (!zonaId || !fecha) return;
    
    // Actualizar folio
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: {
            zona: zonaId,
            fecha_captacion: fecha,
            accion: 'folio'
        },
        success: function(response) {
            $('#resulFolio').html(response);
            
            // Cargar proveedores de la nueva zona
            cargarProveedores(zonaId);
            cargarAlmacenes(zonaId);
            cargarFleteros(zonaId);
            cargarProductos(zonaId);
        }
    });
}

function cargarProveedores(zonaId) {
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            zonaProveedor: zonaId,
            accion: 'proveedores'
        },
        success: function(response) {
            $('#resulProv').html(response);
            $('#idProveedor').select2({
                placeholder: "Selecciona proveedor...",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarBodegasProveedor);
        }
    });
}

function cargarAlmacenes(zonaId) {
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            zonaAlmacen: zonaId,
            accion: 'almacenes'
        },
        success: function(response) {
            $('#resulAlm').html(response);
            $('#idAlmacen').select2({
                placeholder: "Selecciona almacén...",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarBodegasAlmacen);
        }
    });
}

function cargarFleteros(zonaId) {
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            zonaFletero: zonaId,
            accion: 'fleteros'
        },
        success: function(response) {
            $('#resulfLE').html(response);
            $('#idFletero').select2({
                placeholder: "Selecciona fletero...",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarPrecioFlete);
        }
    });
}

function cargarProductos(zonaId) {
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            zonaProducto: zonaId,
            accion: 'productos'
        },
        success: function(response) {
            $('#resulProd').html(response);
            $('#idProd').select2({
                placeholder: "Selecciona producto...",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarPrecioCompra);
        }
    });
}

function cargarBodegasProveedor() {
    var idProveedor = $('#idProveedor').val();
    if (!idProveedor) return;
    
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            idProveedor: idProveedor,
            accion: 'bodegas_proveedor'
        },
        success: function(response) {
            $('#BodePro').html(response);
            $('#bodgeProv').select2({
                placeholder: "Selecciona bodega",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarPrecioFlete);
        }
    });
}

function cargarBodegasAlmacen() {
    var idAlmacen = $('#idAlmacen').val();
    if (!idAlmacen) return;
    
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: { 
            idAlmacen: idAlmacen,
            accion: 'bodegas_almacen'
        },
        success: function(response) {
            $('#BodeAlm').html(response);
            $('#bodgeAlm').select2({
                placeholder: "Selecciona bodega",
                allowClear: false,
                language: "es",
                width: '100%'
            }).on('change', cargarPrecioFlete);
        }
    });
}

function cargarPrecioFlete() {
    var idFletero = $('#idFletero').val();
    var tipoFlete = $('#tipo_flete').val();
    var bodgeProv = $('#bodgeProv').val();
    var bodgeAlm = $('#bodgeAlm').val();
    var fecha = $('#fecha_captacion').val();
    
    if (!idFletero || !tipoFlete || !bodgeProv || !bodgeAlm || !fecha) {
        $('#PreFle').html('<label class="form-label">Precio Flete</label><select class="form-select" disabled><option>Complete todos los campos</option></select>');
        return;
    }
    
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: {
            accion: 'precio_flete',
            idFletero: idFletero,
            tipoFlete: tipoFlete,
            origen: bodgeProv,
            destino: bodgeAlm,
            fechaCaptacion: fecha
        },
        success: function(resp) {
            $('#PreFle').html(resp);
            $('#id_preFle').select2({
                placeholder: "Selecciona precio",
                allowClear: false,
                language: "es",
                width: '100%'
            });
        }
    });
}

function cargarPrecioCompra() {
    var idProd = $('#idProd').val();
    var fecha = $('#fecha_captacion').val();
    
    if (!idProd || !fecha) return;
    
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: {
            accion: 'precio_compra',
            idProd: idProd,
            fechaCaptacion: fecha
        },
        success: function(resp) {
            $('#PrePro').html(resp);
            $('#id_prePD').select2({
                placeholder: "Selecciona precio",
                allowClear: false,
                language: "es",
                width: '100%'
            });
        }
    });
}

function cambiarTipoAlmacen(tipo) {
    if (tipo === 'granel') {
        $('#campos_granel').show();
        $('#campos_pacas, #campos_pacas_k').hide();
        $('#granel_kilos').prop('required', true);
        $('#pacas_cantidad, #pacas_kilos').prop('required', false);
    } else {
        $('#campos_granel').hide();
        $('#campos_pacas, #campos_pacas_k').show();
        $('#granel_kilos').prop('required', false);
        $('#pacas_cantidad, #pacas_kilos').prop('required', true);
    }
}

function agregarProductoConAjax() {
    // Ocultar error anterior
    $('#error-producto').hide().empty();
    
    // Validar datos
    var idProd = $('#idProd').val();
    var id_prePD = $('#id_prePD').val();
    var tipo_almacen = $('#tipo_almacen').val();
    var granel_kilos = $('#granel_kilos').val() || 0;
    var pacas_cantidad = $('#pacas_cantidad').val() || 0;
    var pacas_kilos = $('#pacas_kilos').val() || 0;
    var observaciones = $('#observaciones_prod').val();
    
    // Validaciones más estrictas
    if (!idProd) {
        mostrarError('Seleccione un producto');
        return;
    }
    if (!id_prePD) {
        mostrarError('Seleccione un precio de compra');
        return;
    }
    
    // Validaciones específicas por tipo
    if (tipo_almacen == 'granel') {
        if (!granel_kilos || parseFloat(granel_kilos) <= 0) {
            mostrarError('Ingrese un peso en granel mayor a 0');
            return;
        }
        // Si es granel, forzar pacas en 0
        pacas_cantidad = 0;
        pacas_kilos = 0;
    } else if (tipo_almacen == 'pacas') {
        if (!pacas_cantidad || parseInt(pacas_cantidad) <= 0) {
            mostrarError('Ingrese una cantidad de pacas mayor a 0');
            return;
        }
        if (!pacas_kilos || parseFloat(pacas_kilos) <= 0) {
            mostrarError('Ingrese un peso total de pacas mayor a 0');
            return;
        }
        // Si es pacas, forzar granel en 0
        granel_kilos = 0;
    }
    
    // Verificar si el producto ya está en la lista
    var productosEnTabla = [];
    $('#tabla-productos-container table tbody tr').each(function() {
        var codProducto = $(this).find('td:nth-child(2) strong').text().trim();
        if (codProducto) {
            productosEnTabla.push(codProducto);
        }
    });
    
    // Obtener código del producto seleccionado
    var selectedProductCode = $('#idProd option:selected').text().split(' - ')[0];
    if (productosEnTabla.includes(selectedProductCode)) {
        mostrarError('Este producto ya está agregado a la lista');
        return;
    }
    
    // Preparar datos
    var datos = {
        accion: 'agregar_producto',
        idProd: idProd,
        id_prePD: id_prePD,
        tipo_almacen: tipo_almacen,
        granel_kilos: granel_kilos,
        pacas_cantidad: pacas_cantidad,
        pacas_kilos: pacas_kilos,
        observaciones_prod: observaciones
    };
    
    // Enviar por AJAX
    $.ajax({
        url: 'ajax_captacion_e.php',
        type: 'POST',
        data: datos,
        beforeSend: function() {
            $('#tabla-productos-container').html('<div class="text-center py-3"><i class="bi bi-arrow-clockwise bi-spin me-2"></i>Agregando producto...</div>');
        },
        success: function(respuesta) {
            $('#tabla-productos-container').html(respuesta);
            
            // Limpiar campos
            $('#granel_kilos, #pacas_cantidad, #pacas_kilos, #observaciones_prod').val('');
            if (tipo_almacen == 'granel') $('#granel_kilos').val('0');
            
            // Habilitar botón guardar si hay productos
            var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
            $('#btnGuardar').prop('disabled', !tieneProductos);
        },
        error: function(xhr, status, error) {
            mostrarError('Error al agregar producto: ' + error);
            console.error(xhr.responseText);
            cargarTablaProductos();
        }
    });
}

function cargarTablaProductos() {
    $.ajax({
        url: 'generar_tabla_productos.php',
        type: 'GET',
        success: function(respuesta) {
            $('#tabla-productos-container').html(respuesta);
            var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
            $('#btnGuardar').prop('disabled', !tieneProductos);
        }
    });
}

function eliminarProducto(index) {
    if (!confirm('¿Está seguro de eliminar este producto?')) return;
    
    $.ajax({
        url: 'ajax_captacion_e.php',
        type: 'POST',
        data: {
            accion: 'eliminar_producto',
            indice_producto: index
        },
        beforeSend: function() {
            $('#tabla-productos-container').html('<div class="text-center py-3"><i class="bi bi-arrow-clockwise bi-spin me-2"></i>Eliminando producto...</div>');
        },
        success: function(respuesta) {
            $('#tabla-productos-container').html(respuesta);
            var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
            $('#btnGuardar').prop('disabled', !tieneProductos);
        }
    });
}

function mostrarError(mensaje) {
    $('#error-producto').html('<i class="bi bi-exclamation-triangle me-2"></i>' + mensaje).show();
}

function confirmarLimpiar() {
    if (confirm('¿Está seguro de limpiar todos los productos? Esta acción no se puede deshacer.')) {
        $.ajax({
            url: 'ajax_captacion_e.php',
            type: 'POST',
            data: { accion: 'limpiar_productos' },
            success: function() {
                cargarTablaProductos();
                alert('Productos limpiados correctamente');
                $('#btnGuardar').prop('disabled', true);
            }
        });
    }
}

function actualizarFolioYPrecios() {
    var fecha = $('#fecha_captacion').val();
    var zona = $('#zona').val();
    
    if (!fecha || !zona) return;
    
    // Actualizar folio
    $.ajax({
        url: 'get_captacion.php',
        type: 'POST',
        data: {
            zona: zona,
            fecha_captacion: fecha,
            accion: 'folio'
        },
        success: function(response) {
            $('#resulFolio').html(response);
        }
    });
    
    // Actualizar precios si hay selecciones
    if ($('#idProd').val()) cargarPrecioCompra();
    if ($('#idFletero').val() && $('#tipo_flete').val()) cargarPrecioFlete();
}
</script>