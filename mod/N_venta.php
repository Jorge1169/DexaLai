<?php
// N_venta.php - Módulo de Nueva Venta para zonas MEO
// Verificación de permisos - Backend
requirePermiso('VENTAS_CREAR', 'ventas');

// Obtener zona seleccionada (igual que en N_captacion.php)
$zona_seleccionada = $_SESSION['selected_zone'] ?? 0;

// Inicializar variables
$folio = '';
$folioM = '';
$fecha_seleccionada = $_POST['fecha_venta'] ?? date('Y-m-d');
$fecha = date('ym', strtotime($fecha_seleccionada));
$mes_actual = date('m', strtotime($fecha_seleccionada));
$anio_actual = date('Y', strtotime($fecha_seleccionada));
$fecha_actual = $fecha_seleccionada . ' ' . date('H:i:s');

// Obtener información de la zona (igual que en N_captacion.php)
if ($zona_seleccionada == 0) {
    $zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1'");
} else {
    $zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
} 
$zona_s1 = mysqli_fetch_array($zona_s0);

// Consulta para obtener el último folio del mes de la fecha seleccionada
$query = "SELECT folio FROM ventas WHERE status = '1' 
AND YEAR(fecha_venta) = '$anio_actual'  
AND MONTH(fecha_venta) = '$mes_actual' 
AND zona = '".$zona_s1['id_zone']."'
ORDER BY folio DESC 
LIMIT 1";

$Venta00 = $conn_mysql->query($query);

if ($Venta00 && $Venta00->num_rows > 0) {
    $Venta01 = $Venta00->fetch_assoc();
    $ultimo_folio = intval($Venta01['folio']);
    $nuevo_numero = $ultimo_folio + 1;
    
    if ($nuevo_numero > 9999) {
        $folio = 'ERROR: Límite alcanzado';
    } else {
        $folio = str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
    }
} else {
    $folio = '0001';
}

// Formato del folio: V-ZONA-AAMM0001
$folioM = "V-".$zona_s1['cod']."-".$fecha.$folio;

// Procesar guardar venta completa
if (isset($_POST['guardar_venta'])) {
    // Validar que haya un producto seleccionado directamente
    $id_producto = $_POST['id_producto'] ?? 0;
    $id_precio_venta = $_POST['id_precio_venta'] ?? 0;
    $cantidad_pacas = $_POST['cantidad_pacas'] ?? 0;
    $kilos_venta = $_POST['kilos_venta'] ?? 0;
    $observaciones = $_POST['observaciones_venta'] ?? '';
    $bodega_almacen = $_POST['bodgeAlm'] ?? 0;
    $bodega_cliente = $_POST['bodgeCli'] ?? 0;
    $precio_flete = $_POST['id_preFle'] ?? 0;
    $idFletero = $_POST['idFletero'] ?? 0;
    $idCliente = $_POST['idCliente'] ?? 0;
    $idAlmacen = $_POST['idAlmacen'] ?? 0;
    
    // Validar campos obligatorios
    $errores = [];
    
    if ($id_producto <= 0) {
        $errores[] = "Seleccione un producto válido";
    }
    
    if ($id_precio_venta <= 0) {
        $errores[] = "Seleccione un precio de venta válido";
    }
    
    if ($cantidad_pacas <= 0 && $kilos_venta <= 0) {
        $errores[] = "Debe especificar cantidad de pacas o kilos";
    }
    
    if ($bodega_almacen <= 0) {
        $errores[] = "Debe seleccionar una bodega de almacén válida";
    }
    
    if ($bodega_cliente <= 0) {
        $errores[] = "Debe seleccionar una bodega de cliente válida";
    }
    
    if ($idCliente <= 0) {
        $errores[] = "Debe seleccionar un cliente válido";
    }
    
    if ($idAlmacen <= 0) {
        $errores[] = "Debe seleccionar un almacén válido";
    }
    
    if (!empty($errores)) {
        alert(implode("<br>", $errores), 2, "N_venta");
        exit;
    }
    
    // Obtener información del producto y stock
    $sql_producto = "SELECT p.*, 
                     (SELECT IFNULL(SUM(pacas_cantidad_disponible), 0) 
                      FROM inventario_bodega ib 
                      WHERE ib.id_prod = p.id_prod AND ib.id_bodega = ?) as pacas_disponibles,
                     (SELECT IFNULL(SUM(pacas_kilos_disponible), 0) 
                      FROM inventario_bodega ib 
                      WHERE ib.id_prod = p.id_prod AND ib.id_bodega = ?) as kilos_en_pacas_disponibles
                     FROM productos p WHERE p.id_prod = ?";
    
    $stmt_prod = $conn_mysql->prepare($sql_producto);
    $stmt_prod->bind_param('iii', $bodega_almacen, $bodega_almacen, $id_producto);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    
    if (!$result_prod || $result_prod->num_rows == 0) {
        alert("Producto no encontrado", 2, "N_venta");
        exit;
    }
    
    $prod_data = $result_prod->fetch_assoc();
    
    // Validar stock disponible
    if ($cantidad_pacas > 0 && $cantidad_pacas > $prod_data['pacas_disponibles']) {
        alert("No hay suficientes pacas disponibles. Disponibles: " . $prod_data['pacas_disponibles'], 2, "N_venta");
        exit;
    }
    
    if ($kilos_venta > 0 && $kilos_venta > $prod_data['kilos_en_pacas_disponibles']) {
        alert("No hay suficientes kilos disponibles. Disponibles: " . $prod_data['kilos_en_pacas_disponibles'] . " kg", 2, "N_venta");
        exit;
    }
    
    // Obtener precio de venta
    $sql_precio = "SELECT precio FROM precios WHERE id_precio = ?";
    $stmt_precio = $conn_mysql->prepare($sql_precio);
    $stmt_precio->bind_param('i', $id_precio_venta);
    $stmt_precio->execute();
    $result_precio = $stmt_precio->get_result();
    $precio_data = $result_precio->fetch_assoc();
    
    if (!$precio_data) {
        alert("Precio de venta no encontrado", 2, "N_venta");
        exit;
    }
    
    // Insertar transacción
    try {
        $conn_mysql->begin_transaction();
        
        // 1. Insertar en tabla ventas
        $VentaData = [
            'folio' => $folio,
            'fecha_venta' => $_POST['fecha_venta'],
            'zona' => $zona_seleccionada,
            'id_cliente' => $idCliente,
            'id_direc_cliente' => $bodega_cliente,
            'id_alma' => $idAlmacen,
            'id_direc_alma' => $bodega_almacen,
            'id_transp' => $idFletero,
            'id_user' => $idUser,
            'status' => 1
        ];
        
        $columns = implode(', ', array_keys($VentaData));
        $placeholders = implode(', ', array_fill(0, count($VentaData), '?'));
        $sql = "INSERT INTO ventas ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        $types = str_repeat('s', count($VentaData));
        $stmt->bind_param($types, ...array_values($VentaData));
        $stmt->execute();
        
        $id_venta = $conn_mysql->insert_id;
        
        // 2. Insertar producto en venta_detalle
        $DetalleData = [
            'id_venta' => $id_venta,
            'id_prod' => $id_producto,
            'id_pre_venta' => $id_precio_venta,
            'pacas_cantidad' => $cantidad_pacas,
            'total_kilos' => $kilos_venta,
            'observaciones' => $observaciones,
            'id_user' => $idUser,
            'status' => 1
        ];
        
        $columnsDet = implode(', ', array_keys($DetalleData));
        $placeholdersDet = implode(', ', array_fill(0, count($DetalleData), '?'));
        $sqlDet = "INSERT INTO venta_detalle ($columnsDet) VALUES ($placeholdersDet)";
        $stmtDet = $conn_mysql->prepare($sqlDet);
        
        $typesDet = str_repeat('s', count($DetalleData));
        $stmtDet->bind_param($typesDet, ...array_values($DetalleData));
        $stmtDet->execute();
        
        // 3. Insertar relación de flete (si hay fletero)
        if ($idFletero > 0 && $precio_flete > 0) {
            $FleteData = [
                'id_venta' => $id_venta,
                'id_fletero' => $idFletero,
                'id_pre_flete' => $precio_flete,
                'tipo_camion'=> $_POST['tipo_camion'] ?? null,
                'nombre_chofer'=> $_POST['nombre_chofer'] ?? null,
                'placas_unidad'=> $_POST['placas_unidad'] ?? null
            ];
            
            $columnsFlete = implode(', ', array_keys($FleteData));
            $placeholdersFlete = implode(', ', array_fill(0, count($FleteData), '?'));
            $sqlFlete = "INSERT INTO venta_flete ($columnsFlete) VALUES ($placeholdersFlete)";
            $stmtFlete = $conn_mysql->prepare($sqlFlete);
            
            $typesFlete = str_repeat('s', count($FleteData));
            $stmtFlete->bind_param($typesFlete, ...array_values($FleteData));
            $stmtFlete->execute();
        }
        
        // 4. Actualizar inventario (descontar stock)
        actualizarInventarioVenta($id_venta, $conn_mysql, $idUser);
        
        $conn_mysql->commit();
        
        alert("Venta registrada exitosamente con folio: " . $folioM, 1, "V_venta&id=" . $id_venta);
        logActivity('CREAR', 'Dio de alta una nueva venta '. $id_venta);
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error al registrar venta: " . $e->getMessage(), 2, "N_venta");
    }
}

// Función para actualizar inventario después de venta - VERSIÓN DEFINITIVA
function actualizarInventarioVenta($id_venta, $conn_mysql, $idUser) {
    // 1. Obtener datos de la venta
    $sql_venta = "SELECT v.*, vd.id_prod, vd.pacas_cantidad, vd.total_kilos,
                         v.id_direc_alma as id_bodega,
                         v.id_alma
                  FROM ventas v 
                  LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta
                  WHERE v.id_venta = ?";
    $stmt_venta = $conn_mysql->prepare($sql_venta);
    $stmt_venta->bind_param('i', $id_venta);
    $stmt_venta->execute();
    $venta_data = $stmt_venta->get_result()->fetch_assoc();
    
    if (!$venta_data) {
        throw new Exception("No se encontraron datos de la venta ID: " . $id_venta);
    }
    
    // 2. Buscar inventario existente
    $sql_inv = "SELECT id_inventario, 
                       pacas_cantidad_disponible, 
                       pacas_kilos_disponible,
                       total_kilos_disponible
                FROM inventario_bodega 
                WHERE id_bodega = ? AND id_prod = ?";
    $stmt_inv = $conn_mysql->prepare($sql_inv);
    $stmt_inv->bind_param('ii', $venta_data['id_bodega'], $venta_data['id_prod']);
    $stmt_inv->execute();
    $result_inv = $stmt_inv->get_result();
    $inventario = $result_inv->fetch_assoc();
    
    if (!$inventario) {
        throw new Exception("No se encontró inventario para la bodega ID: " . $venta_data['id_bodega'] . " y producto ID: " . $venta_data['id_prod']);
    }
    
    // 3. Calcular nuevos valores
    $nuevas_pacas = $inventario['pacas_cantidad_disponible'] - $venta_data['pacas_cantidad'];
    $nuevos_kilos_pacas = $inventario['pacas_kilos_disponible'] - $venta_data['total_kilos'];
    $nuevo_total_kilos = $inventario['total_kilos_disponible'] - $venta_data['total_kilos'];
    
    // Asegurar que no queden valores negativos
    $nuevas_pacas = max(0, $nuevas_pacas);
    $nuevos_kilos_pacas = max(0, $nuevos_kilos_pacas);
    $nuevo_total_kilos = max(0, $nuevo_total_kilos);
    
    // 4. Actualizar inventario existente
    $sql_update = "UPDATE inventario_bodega SET 
                  pacas_cantidad_disponible = ?,
                  pacas_kilos_disponible = ?,
                  total_kilos_disponible = ?,
                  ultima_salida = NOW(),
                  updated_at = NOW(),
                  id_user = ?
                  WHERE id_inventario = ?";
    
    $stmt_update = $conn_mysql->prepare($sql_update);
    $stmt_update->bind_param('iddii', 
        $nuevas_pacas,
        $nuevos_kilos_pacas,
        $nuevo_total_kilos,
        $idUser,
        $inventario['id_inventario']
    );
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar inventario: " . $stmt_update->error);
    }
    
    // 5. Registrar movimiento en auditoría - VERSIÓN SIMPLIFICADA (como tu ejemplo)
    $sql_movimiento = "INSERT INTO movimiento_inventario 
                      (id_inventario, id_captacion, id_venta, tipo_movimiento,
                       granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                       granel_kilos_anterior, granel_kilos_nuevo,
                       pacas_cantidad_anterior, pacas_cantidad_nuevo,
                       pacas_kilos_anterior, pacas_kilos_nuevo,
                       conversion_kilos, conversion_pacas_generadas, conversion_peso_promedio,
                       observaciones, id_user)
                      VALUES (?, NULL, ?, 'salida', 
                              0.00, ?, ?, 
                              0.00, 0.00,
                              ?, ?,
                              ?, ?,
                              0.00, 0, 0.00,
                              ?, ?)";
    
    $stmt_mov = $conn_mysql->prepare($sql_movimiento);
    if (!$stmt_mov) {
        throw new Exception("Error preparando query de movimiento: " . $conn_mysql->error);
    }
    
    $observacion = 'Venta #' . $id_venta;
    
    // CORRECCIÓN: 10 parámetros = 10 caracteres en string de tipos
    // i = id_inventario (entero)
    // i = id_venta (entero)
    // i = pacas_cantidad_movimiento (entero)
    // d = pacas_kilos_movimiento (decimal)
    // i = pacas_cantidad_anterior (entero)
    // i = pacas_cantidad_nuevo (entero)
    // d = pacas_kilos_anterior (decimal)
    // d = pacas_kilos_nuevo (decimal)
    // s = observaciones (string)
    // i = id_user (entero)
    
    $stmt_mov->bind_param('iiidiiddsi',
        $inventario['id_inventario'],      // i = integer
        $id_venta,                         // i = integer  
        $venta_data['pacas_cantidad'],     // i = integer
        $venta_data['total_kilos'],        // d = double/decimal
        $inventario['pacas_cantidad_disponible'], // i = integer
        $nuevas_pacas,                     // i = integer
        $inventario['pacas_kilos_disponible'], // d = double/decimal
        $nuevos_kilos_pacas,               // d = double/decimal
        $observacion,                      // s = string
        $idUser                            // i = integer
    );
    
    if (!$stmt_mov->execute()) {
        throw new Exception("Error al registrar movimiento: " . $stmt_mov->error . ". SQL: " . $sql_movimiento);
    }
    
    return true;
}
// Procesar eliminar producto de venta
if (isset($_POST['eliminar_producto_venta'])) {
    unset($_SESSION['producto_venta']);
    alert("Producto eliminado de la venta", 1, "N_venta");
    exit;
}

// Obtener primera zona para selects iniciales (igual que en N_captacion.php)
$Primera_zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
$Primera_zona1 = mysqli_fetch_array($Primera_zona0);
$Primer_zona_select = $Primera_zona1['id_zone'];
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Venta</h5>
            <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="" id="formVenta">

                <!-- SECCIÓN 1: Información Básica -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" onchange="cambiarZonaVenta()">
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
                                } else {
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
                                }
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>"><?=$zona1['PLANTA']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="resulFolio">
                            <label for="folio" class="form-label">Folio</label>
                            <input type="text" id="folio01" class="form-control" value="<?=$folioM?>" disabled>
                            <input type="hidden" name="folio" value="<?=$folio?>">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_venta" class="form-label">Fecha de Venta</label>
                            <input type="date" name="fecha_venta" id="fecha_venta" class="form-control" 
                            value="<?=$fecha_seleccionada?>" max="<?=date('Y-m-d')?>" 
                            onchange="actualizarFolioYPreciosVenta()" required>
                            <small class="text-muted">Puede seleccionar fechas anteriores</small>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: Almacén y Bodega -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Almacén y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulAlm">
                            <label for="idAlmacen" class="form-label">Almacén</label>
                            <select class="form-select" name="idAlmacen" id="idAlmacen" onchange="cargarBodegasAlmacenVenta()" required>
                                <option selected disabled value="">Selecciona un almacén...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Alm_id1 = mysqli_fetch_array($Alm_id0)) {
                                    ?>
                                    <option value="<?=$Alm_id1['id_alma']?>"><?=$Alm_id1['cod']." - ".$Alm_id1['nombre']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="BodeAlm">
                            <label for="bodgeAlm" class="form-label">Bodega del Almacén</label>
                            <select class="form-select" disabled>
                                <option>Selecciona un almacén</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 3: Cliente y Bodega -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Cliente y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulCli">
                            <label for="idCliente" class="form-label">Cliente</label>
                            <select class="form-select" name="idCliente" id="idCliente" onchange="cargarBodegasCliente()" required>
                                <option selected disabled value="">Selecciona un cliente...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Cli_id1 = mysqli_fetch_array($Cli_id0)) {
                                    ?>
                                    <option value="<?=$Cli_id1['id_cli']?>"><?=$Cli_id1['cod']." / ".$Cli_id1['nombre']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="BodeCli">
                            <label for="bodgeCli" class="form-label">Bodega del Cliente</label>
                            <select class="form-select" disabled>
                                <option>Selecciona un cliente</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 4: Fletero y Tipo de Flete (con campos opcionales para unidad/chofer/placas) -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Fletero y Tipo de Flete</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulfLE">
                            <label for="idFletero" class="form-label">Fletero</label>
                            <select class="form-select" name="idFletero" id="idFletero" onchange="cargarPrecioFleteVenta()">
                                <option selected disabled value="">Selecciona un transportista...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
                                    $verCorF = (empty($Fle_id1['correo'])) ? ' ' : '📧' ;
                                    ?>
                                    <option value="<?=$Fle_id1['id_transp']?>"><?=$Fle_id1['placas']." - ".$Fle_id1['razon_so']." ".$verCorF?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="TipoFlete">
                            <label for="tipo_flete" class="form-label">Tipo de Flete</label>
                            <select class="form-select" name="tipo_flete" id="tipo_flete" onchange="cargarPrecioFleteVenta()" required>
                                <option selected disabled value="">Selecciona tipo...</option>
                                <option value="MFT">Por tonelada (MEO)</option>
                                <option value="MFV">Por viaje (MEO)</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="PreFle">
                            <label for="preFl" class="form-label">Precio del flete</label>
                            <select class="form-select" disabled>
                                <option>Selecciona fletero y tipo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos opcionales: Tipo de Unidad, Nombre del Chofer, Placas de la Unidad -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="tipo_camion" class="form-label">Tipo de Unidad <small class="text-muted">(Opcional)</small></label>
                            <select class="form-select" name="tipo_camion" id="tipo_camion">
                                <option value="">-- No especificar --</option>
                                <option value="TRACTO">Tractocamión</option>
                                <option value="CAJA">Caja Seca</option>
                                <option value="SENCILLA">Sencilla</option>
                                <option value="3_5T">Camión 3.5T</option>
                                <option value="TORTON">Torton</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre_chofer" class="form-label">Nombre del Chofer <small class="text-muted">(Opcional)</small></label>
                            <input type="text" name="nombre_chofer" id="nombre_chofer" class="form-control" placeholder="Nombre del chofer">
                        </div>
                        <div class="col-md-4">
                            <label for="placas_unidad" class="form-label">Placas de la Unidad <small class="text-muted">(Opcional)</small></label>
                            <input type="text" name="placas_unidad" id="placas_unidad" class="form-control" placeholder="XXX-0000">
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 5: Producto y Cantidades -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Producto a Vender</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-4" id="resulProd">
                            <label for="id_producto" class="form-label">Producto</label>
                            <select class="form-select" name="id_producto" id="id_producto" onchange="cargarPrecioVentaYStock()" required>
                                <option selected disabled value="">Selecciona un producto...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
                                    ?>
                                    <option value="<?=$Prod_id1['id_prod']?>"><?=$Prod_id1['cod']." - ".$Prod_id1['nom_pro']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="PrePro">
                            <label for="id_precio_venta" class="form-label">Precio de venta</label>
                            <select class="form-select" name="id_precio_venta" id="id_precio_venta" required disabled>
                                <option>Selecciona un producto</option>
                            </select>
                        </div>
                    </div>

                    <!-- Información de Stock Disponible -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-12" id="infoStock">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Seleccione un producto para ver el stock disponible
                            </div>
                        </div>
                    </div>

                    <!-- Cantidades a Vender -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="cantidad_pacas" class="form-label">Cantidad de Pacas</label>
                            <div class="input-group">
                                <input type="number" name="cantidad_pacas" id="cantidad_pacas" class="form-control" 
                                    min="0" step="1" value="0" onchange="validarCantidades()" required>
                                <span class="input-group-text">pacas</span>
                            </div>
                            <small class="text-muted">Disponibles: <span id="pacas_disponibles">0</span></small>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="kilos_venta" class="form-label">Peso Total (kilos)</label>
                            <div class="input-group">
                                <input type="number" name="kilos_venta" id="kilos_venta" class="form-control" 
                                    step="0.01" min="0" value="0.00" onchange="validarCantidades()" required>
                                <span class="input-group-text">kg</span>
                            </div>
                            <small class="text-muted">Disponibles: <span id="kilos_disponibles">0.00</span> kg</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="observaciones_venta" class="form-label">Observaciones (opcional)</label>
                            <input type="text" name="observaciones_venta" id="observaciones_venta" class="form-control" 
                                placeholder="Ej: Producto especial, requerimientos del cliente, etc.">
                        </div>
                    </div>
                </div>
                
                <!-- Botón para guardar toda la venta -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar_venta" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Guardar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts JavaScript para ventas -->
<script>
    // Deshabilitar selección de producto hasta que se seleccione cliente
    $(document).ready(function() {
        // Inicialmente deshabilitar producto
        $('#id_producto').prop('disabled', true);
        
        // Habilitar producto cuando se seleccione cliente
        $('#idCliente').on('change', function() {
            if ($(this).val()) {
                $('#id_producto').prop('disabled', false);
            } else {
                $('#id_producto').prop('disabled', true).val('');
                $('#PrePro').html('<label class="form-label">Precio de venta</label><select class="form-select" disabled><option>Seleccione cliente primero</option></select>');
            }
        });
        
        // Inicializar Select2
        $('#zona, #idAlmacen, #idCliente, #idFletero, #id_producto').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: false,
            language: "es",
            width: '100%'
        });

        $('#tipo_flete').select2({
            placeholder: "Selecciona tipo de flete",
            allowClear: false,
            language: "es",
            width: '100%'
        });
        
        // Establecer la zona seleccionada
        $('#zona').val('<?= $zona_seleccionada ?>').trigger('change');
    });

    // Función para cambiar zona en ventas
    function cambiarZonaVenta() {
        var zonaId = $('#zona').val();
        var fecha = $('#fecha_venta').val();

        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: {
                zona: zonaId,
                fecha_venta: fecha,
                accion: 'folio_venta'
            },
            success: function(response) {
                $('#resulFolio').html(response);
                // Recargar selects basados en zona
                cargarAlmacenesVenta(zonaId);
                cargarClientesVenta(zonaId);
                cargarFleterosVenta(zonaId);
                cargarProductosVenta(zonaId);
            }
        });
    }

    // Función para cargar almacenes por zona
    function cargarAlmacenesVenta(zonaId) {
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                zonaAlmacen: zonaId,
                accion: 'almacenes_venta'
            },
            success: function(response) {
                $('#resulAlm').html(response);
                $('#idAlmacen').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar clientes por zona
    function cargarClientesVenta(zonaId) {
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                zonaCliente: zonaId,
                accion: 'clientes_venta'
            },
            success: function(response) {
                $('#resulCli').html(response);
                $('#idCliente').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar fleteros por zona
    function cargarFleterosVenta(zonaId) {
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                zonaFletero: zonaId,
                accion: 'fleteros_venta'
            },
            success: function(response) {
                $('#resulfLE').html(response);
                $('#idFletero').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar productos por zona
    function cargarProductosVenta(zonaId) {
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                zonaProducto: zonaId,
                accion: 'productos_venta'
            },
            success: function(response) {
                $('#resulProd').html(response);
                $('#id_producto').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para actualizar folio y precios cuando cambia la fecha
    function actualizarFolioYPreciosVenta() {
        var fechaSeleccionada = $('#fecha_venta').val();
        var zonaId = $('#zona').val();

        if (!fechaSeleccionada || !zonaId) return;

        // Actualizar folio
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: {
                zona: zonaId,
                fecha_venta: fechaSeleccionada,
                accion: 'folio_venta'
            },
            beforeSend: function() {
                $('#resulFolio').html('<label class="form-label">Folio</label><div class="form-control">Actualizando...</div>');
            },
            success: function(response) {
                $('#resulFolio').html(response);

                // Actualizar precios si ya están seleccionados
                if ($('#id_producto').val()) cargarPrecioVentaYStock();
                if ($('#idFletero').val() && $('#tipo_flete').val()) cargarPrecioFleteVenta();
            },
            error: function(xhr, status, error) {
                console.error('Error al actualizar folio:', error);
            }
        });
    }

    // Función para cargar bodegas del almacén
    function cargarBodegasAlmacenVenta() {
        var idAlmacen = $('#idAlmacen').val();

        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                idAlmacen: idAlmacen,
                accion: 'bodegas_almacen_venta'
            },
            beforeSend: function() {
                $('#BodeAlm').html('<label class="form-label">Bodega del Almacén</label><div class="form-control">Cargando...</div>');
            },
            success: function(response) {
                $('#BodeAlm').html(response);
                $('#bodgeAlm').select2({
                    placeholder: "Selecciona bodega",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                }).on('change', function() {
                    if ($('#id_producto').val()) cargarPrecioVentaYStock();
                    if ($('#idFletero').val() && $('#tipo_flete').val()) cargarPrecioFleteVenta();
                });
            }
        });
    }

    // Función para cargar bodegas del cliente - ACTUALIZADA para también cargar precios
    function cargarBodegasCliente() {
        var idCliente = $('#idCliente').val();

        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: { 
                idCliente: idCliente,
                accion: 'bodegas_cliente'
            },
            beforeSend: function() {
                $('#BodeCli').html('<label class="form-label">Bodega del Cliente</label><div class="form-control">Cargando...</div>');
            },
            success: function(response) {
                $('#BodeCli').html(response);
                $('#bodgeCli').select2({
                    placeholder: "Selecciona bodega",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                }).on('change', function() {
                    // Cuando se selecciona bodega, cargar precio de flete
                    cargarPrecioFleteVenta();
                    
                    // Y si ya hay producto seleccionado, actualizar precios también
                    if ($('#id_producto').val()) {
                        cargarPrecioVentaYStock();
                    }
                });
                
                // Después de cargar bodegas, si hay producto seleccionado, cargar precios
                if ($('#id_producto').val()) {
                    cargarPrecioVentaYStock();
                }
            }
        });
    }

    // Función para cargar precio de flete para venta
    function cargarPrecioFleteVenta() {
        var idFletero = $('#idFletero').val();
        var tipoFlete = $('#tipo_flete').val();
        var bodgeAlm = $('#bodgeAlm').val();
        var bodgeCli = $('#bodgeCli').val();
        var fechaVenta = $('#fecha_venta').val();

        if (!idFletero || !tipoFlete || !bodgeAlm || !bodgeCli || !fechaVenta) {
            $('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Complete todos los campos</option></select>');
            return;
        }

        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: {
                idFletero: idFletero,
                tipoFlete: tipoFlete,
                origen: bodgeAlm,
                destino: bodgeCli,
                fechaVenta: fechaVenta,
                cap_ven: 'VEN', // Siempre VEN para ventas
                accion: 'precio_flete_venta'
            },
            beforeSend: function() {
                $('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control">Buscando precios...</div>');
            },
            success: function(response) {
                $('#PreFle').html(response);
            }
        });
    }

    // Función para cargar precio de venta y stock del producto - VERSIÓN CORREGIDA
    function cargarPrecioVentaYStock() {
        var idProd = $('#id_producto').val();
        var fechaVenta = $('#fecha_venta').val();
        var bodgeAlm = $('#bodgeAlm').val();
        var idCliente = $('#idCliente').val();
        var bodgeCli = $('#bodgeCli').val();

        if (!idProd || !fechaVenta) {
            $('#PrePro').html('<label class="form-label">Precio de venta</label><select class="form-select" disabled><option>Seleccione producto y fecha</option></select>');
            $('#infoStock').html('<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un producto para ver el stock</div>');
            
            // Resetear valores de stock
            $('#pacas_disponibles').text('0');
            $('#kilos_disponibles').text('0.00');
            $('#cantidad_pacas').val('0').attr('max', '0');
            $('#kilos_venta').val('0.00').attr('max', '0');
            
            return;
        }

        // Validar que el cliente esté seleccionado
        if (!idCliente || idCliente === '') {
            $('#PrePro').html('<label class="form-label">Precio de venta</label><div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Primero seleccione un cliente</div>');
        } else {
            // Cargar precio de venta
            $.ajax({
                url: 'get_venta.php',
                type: 'POST',
                data: {
                    idProd: idProd,
                    fechaVenta: fechaVenta,
                    idCliente: idCliente,
                    idBodegaCliente: bodgeCli,
                    accion: 'precio_venta'
                },
                beforeSend: function() {
                    $('#PrePro').html('<label class="form-label">Precio de venta</label><div class="form-control">Buscando precios...</div>');
                },
                success: function(response) {
                    $('#PrePro').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar precios:', error);
                    $('#PrePro').html('<label class="form-label">Precio de venta</label><div class="form-control is-invalid">Error al cargar precios</div>');
                }
            });
        }

        // Cargar stock disponible (solo si hay bodega seleccionada)
        if (bodgeAlm) {
            cargarStockProducto(idProd, bodgeAlm);
        } else {
            $('#infoStock').html('<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Seleccione una bodega de almacén para ver el stock</div>');
            
            // Resetear valores de stock
            $('#pacas_disponibles').text('0');
            $('#kilos_disponibles').text('0.00');
            $('#cantidad_pacas').val('0').attr('max', '0');
            $('#kilos_venta').val('0.00').attr('max', '0');
        }
    }
    // Función para resetear valores de stock
    function resetStockValues() {
        $('#pacas_disponibles').text('0');
        $('#kilos_disponibles').text('0.00');
        $('#cantidad_pacas').val('0').attr('max', '0');
        $('#kilos_venta').val('0.00').attr('max', '0');
    }

    // Llamar a resetStockValues cuando cambie la bodega de almacén
    $('#bodgeAlm').on('change', function() {
        if (!$(this).val() || $(this).val() === '') {
            resetStockValues();
        }
    });

    $(document).ready(function() {
        // Inicializar inputs
        $('#kilos_venta').prop('disabled', true);
        $('#cantidad_pacas').prop('disabled', true);
        
        // Habilitar cuando haya stock
        $(document).on('stockUpdated', function(e, pacas, kilos) {
            if (pacas > 0) {
                $('#cantidad_pacas').prop('disabled', false);
            }
            if (kilos > 0) {
                $('#kilos_venta').prop('disabled', false);
            }
        });
    });
    // Función para cargar stock del producto - VERSIÓN CORREGIDA
    function cargarStockProducto(idProd, bodegaId) {
        $.ajax({
            url: 'get_venta.php',
            type: 'POST',
            data: {
                idProd: idProd,
                bodegaId: bodegaId,
                accion: 'stock_producto'
            },
            beforeSend: function() {
                $('#infoStock').html('<div class="alert alert-info"><i class="bi bi-arrow-clockwise bi-spin me-2"></i>Consultando stock...</div>');
            },
            success: function(response) {
                $('#infoStock').html(response);
                
                // Buscar los valores en la respuesta
                var pacasElement = document.getElementById('stock_pacas');
                var kilosElement = document.getElementById('stock_kilos');
                
                if (pacasElement && kilosElement) {
                    var pacas_disponibles = parseInt(pacasElement.getAttribute('data-value')) || 0;
                    var kilos_disponibles = parseFloat(kilosElement.getAttribute('data-value')) || 0;
                    
                    console.log('Pacas disponibles:', pacas_disponibles);
                    console.log('Kilos disponibles:', kilos_disponibles);
                    
                    // Actualizar los campos de disponibilidad
                    $('#pacas_disponibles').text(pacas_disponibles);
                    $('#kilos_disponibles').text(kilos_disponibles.toFixed(2));
                    
                    // Actualizar los atributos max de los inputs
                    $('#cantidad_pacas').attr('max', pacas_disponibles).prop('disabled', pacas_disponibles <= 0);
                    $('#kilos_venta').attr('max', kilos_disponibles).prop('disabled', kilos_disponibles <= 0);
                    
                    // Resetear valores si no hay stock
                    if (pacas_disponibles <= 0) {
                        $('#cantidad_pacas').val('0');
                    }
                    if (kilos_disponibles <= 0) {
                        $('#kilos_venta').val('0.00');
                    }
                } else {
                    console.warn('No se encontraron elementos de stock en la respuesta');
                    $('#pacas_disponibles').text('0');
                    $('#kilos_disponibles').text('0.00');
                    $('#cantidad_pacas').attr('max', '0').prop('disabled', true).val('0');
                    $('#kilos_venta').attr('max', '0').prop('disabled', true).val('0.00');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar stock:', error);
                $('#infoStock').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error al cargar stock: ' + error + '</div>');
                // Resetear valores en caso de error
                $('#pacas_disponibles').text('0');
                $('#kilos_disponibles').text('0.00');
                $('#cantidad_pacas').attr('max', '0').prop('disabled', true).val('0');
                $('#kilos_venta').attr('max', '0').prop('disabled', true).val('0.00');
            }
        });
    }
    // Función para validar cantidades ingresadas
    function validarCantidades() {
        var pacas = parseInt($('#cantidad_pacas').val()) || 0;
        var kilos = parseFloat($('#kilos_venta').val()) || 0;
        var pacasDisponibles = parseInt($('#pacas_disponibles').text().replace(/,/g, '')) || 0;
        var kilosDisponibles = parseFloat($('#kilos_disponibles').text().replace(/,/g, '')) || 0;
        
        // Validar que no exceda el stock
        if (pacas > pacasDisponibles) {
            alert('Error: No hay suficientes pacas disponibles. Disponibles: ' + pacasDisponibles);
            $('#cantidad_pacas').val(Math.min(pacas, pacasDisponibles));
            pacas = Math.min(pacas, pacasDisponibles);
        }
        
        if (kilos > kilosDisponibles) {
            alert('Error: No hay suficientes kilos disponibles. Disponibles: ' + kilosDisponibles.toFixed(2) + ' kg');
            $('#kilos_venta').val(Math.min(kilos, kilosDisponibles).toFixed(2));
            kilos = Math.min(kilos, kilosDisponibles);
        }
        
        // Validar que al menos una cantidad sea mayor a 0
        if (pacas == 0 && kilos == 0) {
            $('#error-producto-venta').html('Debe especificar cantidad de pacas o kilos').show();
        } else {
            $('#error-producto-venta').hide();
        }
    }

    // Cerrar ventana
    $('#btnCerrar').click(function() {
        window.close();
    });

// Validar formulario antes de enviar
$('form').on('submit', function(e) {
    if ($(this).find('button[name="guardar_venta"]').length > 0) {
        var pacas = parseInt($('#cantidad_pacas').val()) || 0;
        var kilos = parseFloat($('#kilos_venta').val()) || 0;
        
        if (pacas == 0 && kilos == 0) {
            e.preventDefault();
            alert('Debe especificar cantidad de pacas o kilos');
            return false;
        }
        
        // Validar que todos los campos requeridos estén completos
        var camposRequeridos = ['#id_producto', '#id_precio_venta', '#bodgeAlm', '#bodgeCli', '#idCliente', '#idAlmacen'];
        var errores = [];
        
        camposRequeridos.forEach(function(campo) {
            if (!$(campo).val() || $(campo).val() === '') {
                var nombreCampo = $(campo).closest('.form-group').find('label').text() || $(campo).attr('name');
                errores.push('El campo ' + nombreCampo + ' es requerido');
            }
        });
        
        if (errores.length > 0) {
            e.preventDefault();
            alert('Por favor complete los siguientes campos:\n\n' + errores.join('\n'));
            return false;
        }
    }
});
    // Función de debug para verificar valores
function debugStock() {
    console.log('=== DEBUG STOCK ===');
    console.log('Pacas disponibles texto:', $('#pacas_disponibles').text());
    console.log('Pacas disponibles num:', parseInt($('#pacas_disponibles').text()));
    console.log('Kilos disponibles texto:', $('#kilos_disponibles').text());
    console.log('Kilos disponibles num:', parseFloat($('#kilos_disponibles').text()));
    console.log('Input kilos max attr:', $('#kilos_venta').attr('max'));
    console.log('Input kilos disabled:', $('#kilos_venta').prop('disabled'));
    console.log('=== FIN DEBUG ===');
}

// Llamar después de cargar stock
$(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.includes('get_venta.php') && settings.data.includes('stock_producto')) {
        setTimeout(debugStock, 100);
    }
});

// Función de debug para verificar valores de stock
function debugStockInfo() {
    console.log('=== DEBUG STOCK INFO ===');
    console.log('Pacas disponibles:', $('#pacas_disponibles').text());
    console.log('Kilos disponibles:', $('#kilos_disponibles').text());
    console.log('Input kilos valor:', $('#kilos_venta').val());
    console.log('Input kilos max:', $('#kilos_venta').attr('max'));
    console.log('Input kilos disabled:', $('#kilos_venta').prop('disabled'));
    
    // Verificar elementos en el DOM
    var stockPacasElem = document.getElementById('stock_pacas');
    var stockKilosElem = document.getElementById('stock_kilos');
    console.log('Elemento stock_pacas existe:', stockPacasElem !== null);
    console.log('Elemento stock_kilos existe:', stockKilosElem !== null);
    
    if (stockPacasElem) {
        console.log('Data-value pacas:', stockPacasElem.getAttribute('data-value'));
    }
    if (stockKilosElem) {
        console.log('Data-value kilos:', stockKilosElem.getAttribute('data-value'));
    }
    console.log('=== FIN DEBUG ===');
}

// Llamar después de cargar stock
$(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.includes('get_venta.php') && settings.data.includes('stock_producto')) {
        setTimeout(debugStockInfo, 500);
    }
});
</script>