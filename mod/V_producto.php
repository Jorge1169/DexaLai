<?php
$id_producto = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona actual
if ($id_producto) {
    // datos del producto 
    $sqlproducto = "SELECT * FROM productos WHERE id_prod = ?";
    $stmtproducto = $conn_mysql->prepare($sqlproducto);
    $stmtproducto->bind_param('i', $id_producto);
    $stmtproducto->execute();
    $resultProducto = $stmtproducto->get_result();
    $producto = $resultProducto->fetch_assoc();
}

// Procesar eliminación de precio (cambiar status a 0)
if (isset($_POST['eliminar_precio'])) {
    $id_precio = $_POST['id_precio']; 
    $sqlEliminar = "UPDATE precios SET status = 0 WHERE id_precio = ?";
    $stmtEliminar = $conn_mysql->prepare($sqlEliminar);
    $stmtEliminar->bind_param('i', $id_precio);
    $stmtEliminar->execute();

    if ($stmtEliminar->affected_rows > 0) {
        alert("Precio eliminado con éxito", 1, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Elimino el precio ' . $id_producto);
    } else {
        alert("Error al eliminar el precio", 2, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Intento elimino el precio ' . $id_producto);
    }
}

// Procesar reactivación de precio
if (isset($_POST['reactivar_precio'])) {
    $id_precio = $_POST['id_precio']; 
    $sqlReactivar = "UPDATE precios SET status = 1 WHERE id_precio = ?";
    $stmtReactivar = $conn_mysql->prepare($sqlReactivar);
    $stmtReactivar->bind_param('i', $id_precio);
    $stmtReactivar->execute();

    if ($stmtReactivar->affected_rows > 0) {
        alert("Precio reactivado con éxito", 1, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Reactivó el precio ' . $id_precio);
    } else {
        alert("Error al reactivar el precio", 2, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Intento reactivar el precio ' . $id_precio);
    }
}

// Procesar actualización rápida de vigencia
if (isset($_POST['actualizar_vigencia'])) {
    $id_precio = $_POST['id_precio']; 
    $nueva_fecha_fin = $_POST['nueva_fecha_fin'];
    
    if (!DateTime::createFromFormat('Y-m-d', $nueva_fecha_fin)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "V_producto&id=".$id_producto."");
        exit;
    }
    
    $sqlActualizar = "UPDATE precios SET fecha_fin = ?, usuario = ? WHERE id_precio = ?";
    $stmtActualizar = $conn_mysql->prepare($sqlActualizar);
    $stmtActualizar->bind_param('sii', $nueva_fecha_fin, $idUser, $id_precio);
    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows > 0) {
        alert("Vigencia actualizada con éxito", 1, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Actualizó vigencia del precio ' . $id_precio . ' a ' . $nueva_fecha_fin);
    } else {
        alert("Error al actualizar la vigencia", 2, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Intento actualizar vigencia del precio ' . $id_precio);
    }
}

// Procesar extensión rápida de vigencia
if (isset($_POST['extender_vigencia'])) {
    $id_precio = $_POST['id_precio']; 
    $dias_extension = $_POST['dias_extension'];
    
    // Obtener la fecha actual del precio
    $sqlFecha = "SELECT fecha_fin FROM precios WHERE id_precio = ?";
    $stmtFecha = $conn_mysql->prepare($sqlFecha);
    $stmtFecha->bind_param('i', $id_precio);
    $stmtFecha->execute();
    $resultFecha = $stmtFecha->get_result();
    $precioData = $resultFecha->fetch_assoc();
    
    $nueva_fecha_fin = date('Y-m-d', strtotime($precioData['fecha_fin'] . ' + ' . $dias_extension . ' days'));
    
    $sqlExtender = "UPDATE precios SET fecha_fin = ?, usuario = ? WHERE id_precio = ?";
    $stmtExtender = $conn_mysql->prepare($sqlExtender);
    $stmtExtender->bind_param('sii', $nueva_fecha_fin, $idUser, $id_precio);
    $stmtExtender->execute();

    if ($stmtExtender->affected_rows > 0) {
        alert("Vigencia extendida " . $dias_extension . " días con éxito", 1, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Extendió vigencia del precio ' . $id_precio . ' por ' . $dias_extension . ' días');
    } else {
        alert("Error al extender la vigencia", 2, "V_producto&id=".$id_producto."");
        logActivity('PRECIO', 'Intento extender vigencia del precio ' . $id_precio);
    }
}

if (isset($_POST['guardar01'])) {
    // Si es compra, forzar destino = 0
    if ($_POST['tipo'] == 'c') {
        $PlantaV = 0;
    } else {
        $PlantaV = $_POST['planta'] ?? 0;
    }

    $fecha_Ini = $_POST['fechaini'] ?? date('Y-m-d');
    if (!DateTime::createFromFormat('Y-m-d', $fecha_Ini)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "N_compra");
        exit;
    }

    $fecha_Fin = $_POST['fechafin'] ?? date('Y-m-d');
    if (!DateTime::createFromFormat('Y-m-d', $fecha_Fin)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "N_compra");
        exit;
    }
    
    // VERIFICAR si ya existe un precio activo con el mismo tipo, producto, precio Y DESTINO
    $sql_verificar = "SELECT id_precio FROM precios 
    WHERE id_prod = ? 
    AND tipo = ? 
    AND precio = ?
    AND destino = ?
    AND status = '1'";
    $stmt_verificar = $conn_mysql->prepare($sql_verificar);
    $stmt_verificar->bind_param('isdi', $id_producto, $_POST['tipo'], $_POST['precio'], $PlantaV);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows > 0) {
        // Ya existe un precio con los mismos datos → ACTUALIZAR FECHAS
        $precio_existente = $result_verificar->fetch_assoc();
        $sql_actualizar = "UPDATE precios SET fecha_ini = ?, fecha_fin = ?, usuario = ? 
        WHERE id_precio = ?";
        $stmt_actualizar = $conn_mysql->prepare($sql_actualizar);
        $stmt_actualizar->bind_param('ssii', $fecha_Ini, $fecha_Fin, $idUser, $precio_existente['id_precio']);
        $stmt_actualizar->execute();
        
        $mensaje = "Precio actualizado con éxito (fechas modificadas)";
    } else {
        // No existe un precio con estos datos → INSERTAR NUEVO PRECIO
        $PrecioData = [
            'id_prod' => $id_producto,
            'precio' => $_POST['precio'],
            'tipo' => $_POST['tipo'],
            'fecha_ini' => $fecha_Ini,
            'destino' => $PlantaV,
            'fecha_fin' => $fecha_Fin,
            'usuario' => $idUser,
            'status' => 1
        ];
        
        $columns = implode(', ', array_keys($PrecioData));
        $placeholders = str_repeat('?,', count($PrecioData) - 1) . '?';
        $sql = "INSERT INTO precios ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        $types = str_repeat('s', count($PrecioData));
        $stmt->bind_param($types, ...array_values($PrecioData));
        $stmt->execute();
        
        $mensaje = "Precio registrado con éxito";
    }

    alert($mensaje, 1, "V_producto&id=".$id_producto."");
    logActivity('PRECIO', $mensaje ." Para el producto ". $id_producto);
}

// Función para verificar si un precio está vigente
function estaVigente($fecha_ini, $fecha_fin) {
    $hoy = date('Y-m-d');
    return ($hoy >= $fecha_ini && $hoy <= $fecha_fin);
}

// Función para obtener la clase Bootstrap según el estado
function getClaseEstado($fecha_ini, $fecha_fin, $status) {
    if ($status == 0) {
        return 'table-danger'; // Eliminado
    }
    
    if (estaVigente($fecha_ini, $fecha_fin)) {
        return 'table-success'; // Vigente
    } else {
        return 'table-warning'; // No vigente
    }
}

// Función para obtener el badge del estado
function getBadgeEstado($fecha_ini, $fecha_fin, $status) {
    if ($status == 0) {
        return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Eliminado</span>';
    }
    
    if (estaVigente($fecha_ini, $fecha_fin)) {
        return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Vigente</span>';
    } else {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>No Vigente</span>';
    }
}

// Función para calcular días restantes
function diasRestantes($fecha_fin) {
    $hoy = new DateTime();
    $fin = new DateTime($fecha_fin);
    $diferencia = $hoy->diff($fin);
    
    if ($hoy > $fin) {
        return '<span class="text-danger">-' . $diferencia->days . ' días</span>';
    } else {
        return '<span class="text-success">' . $diferencia->days . ' días</span>';
    }
}
?>
<div class="container mt-2">
    <div class="card mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Datos del producto</h5>
                <span class="small">Código: <?=$producto['cod']?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="?p=productos" class="btn btn-sm rounded-3 btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
                <a href="?p=E_producto&id=<?=$id_producto?>" class="btn btn-sm rounded-3 btn-light" <?= $perm['Clien_Editar'];?>>
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            Información del producto <i class="bi bi-box-seam text-primary"></i>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Producto
                                    <span class="badge text-body"><?=$producto['nom_pro']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Codigo
                                    <span class="badge text-body"><?=$producto['cod']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Linea
                                    <span class="badge text-body"><?=$producto['lin']?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Precios <i class="bi bi-cash-stack text-teal"></i></span>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="d-flex gap-1 small">
                                    <span class="badge bg-success">Vigente</span>
                                    <span class="badge bg-warning text-dark">No Vigente</span>
                                    <span class="badge bg-danger">Eliminado</span>
                                </div>
                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-success btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#AgregarPrec">
                                    <i class="bi bi-plus-circle me-1"></i> Agregar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Pestañas para precios activos e históricos -->
                            <ul class="nav nav-tabs mb-3" id="preciosTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="activos-tab" data-bs-toggle="tab" data-bs-target="#activos" type="button" role="tab">
                                        <i class="bi bi-check-circle me-1"></i>Precios Activos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="historicos-tab" data-bs-toggle="tab" data-bs-target="#historicos" type="button" role="tab">
                                        <i class="bi bi-clock-history me-1"></i>Histórico
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="preciosTabContent">
                                <!-- Tab de precios activos -->
                                <div class="tab-pane fade show active" id="activos" role="tabpanel">
                                    <!-- Acciones rápidas -->
                                    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <i class="bi bi-lightning me-2"></i>
                                            <strong>Acciones rápidas:</strong> 
                                            <span class="ms-2">Extender vigencias fácilmente</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalExtenderTodos">
                                                <i class="bi bi-calendar-plus me-1"></i>Extender Todos
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Precios de compra</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Estado</th>
                                                    <th>Vigencia</th>
                                                    <th>Días Rest.</th>
                                                    <th width="150">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $PreciCom00 = $conn_mysql->query("SELECT * FROM precios where tipo = 'c' AND status = '1' AND id_prod = '$id_producto' ORDER BY fecha_ini DESC");
                                                while ($PreciCom01 = mysqli_fetch_array($PreciCom00)) {
                                                    $claseFila = getClaseEstado($PreciCom01['fecha_ini'], $PreciCom01['fecha_fin'], $PreciCom01['status']);
                                                    $badgeEstado = getBadgeEstado($PreciCom01['fecha_ini'], $PreciCom01['fecha_fin'], $PreciCom01['status']);
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td class="fw-semibold">$<?=number_format($PreciCom01['precio'], 2)?></td>
                                                        <td><?= $badgeEstado ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                De <?=date('Y-m-d', strtotime($PreciCom01['fecha_ini']))?> a <?=date('Y-m-d', strtotime($PreciCom01['fecha_fin']))?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?= diasRestantes($PreciCom01['fecha_fin']) ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtenderVigencia" 
                                                                    data-precio-id="<?=$PreciCom01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciCom01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciCom01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-plus" title="Extender vigencia"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalActualizarVigencia"
                                                                    data-precio-id="<?=$PreciCom01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciCom01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciCom01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-check" title="Actualizar fecha"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrecio" 
                                                                    data-precio-id="<?=$PreciCom01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciCom01['precio'], 2)?>">
                                                                    <i class="bi bi-trash" title="Eliminar"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(mysqli_num_rows($PreciCom00) == 0): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">No hay precios de compra registrados</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <hr class="my-3">

                                    <h6 class="card-subtitle mb-2 text-muted">Precios de venta</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Cliente</th>
                                                    <th>Estado</th>
                                                    <th>Vigencia</th>
                                                    <th>Días Rest.</th>
                                                    <th width="150">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $PreciVen00 = $conn_mysql->query("
                                                    SELECT p.*, d.cod_al, d.noma 
                                                    FROM precios p 
                                                    LEFT JOIN direcciones d ON p.destino = d.id_direc 
                                                    WHERE p.tipo = 'v' AND p.status = '1' AND p.id_prod = '$id_producto'
                                                    ORDER BY p.fecha_ini DESC
                                                    ");
                                                while ($PreciVen01 = mysqli_fetch_array($PreciVen00)) {
                                                    $cliente = "General";
                                                    if ($PreciVen01['destino'] != '0' && $PreciVen01['destino'] != '') {
                                                        $cliente = $PreciVen01['cod_al'] . " - " . $PreciVen01['noma'];
                                                    }
                                                    
                                                    $claseFila = getClaseEstado($PreciVen01['fecha_ini'], $PreciVen01['fecha_fin'], $PreciVen01['status']);
                                                    $badgeEstado = getBadgeEstado($PreciVen01['fecha_ini'], $PreciVen01['fecha_fin'], $PreciVen01['status']);
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td class="fw-semibold">$<?=number_format($PreciVen01['precio'], 2)?></td>
                                                        <td>
                                                            <small class="<?= $PreciVen01['destino'] != '0' ? 'text-primary fw-semibold' : 'text-muted' ?>">
                                                                <?= $cliente ?>
                                                            </small>
                                                        </td>
                                                        <td><?= $badgeEstado ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                De <?=date('Y-m-d', strtotime($PreciVen01['fecha_ini']))?> a <?=date('Y-m-d', strtotime($PreciVen01['fecha_fin']))?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?= diasRestantes($PreciVen01['fecha_fin']) ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtenderVigencia" 
                                                                    data-precio-id="<?=$PreciVen01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciVen01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciVen01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-plus" title="Extender vigencia"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalActualizarVigencia"
                                                                    data-precio-id="<?=$PreciVen01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciVen01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciVen01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-check" title="Actualizar fecha"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrecio" 
                                                                    data-precio-id="<?=$PreciVen01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciVen01['precio'], 2)?>">
                                                                    <i class="bi bi-trash" title="Eliminar"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(mysqli_num_rows($PreciVen00) == 0): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">No hay precios de venta registrados</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab de precios históricos -->
                                <div class="tab-pane fade" id="historicos" role="tabpanel">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Precios eliminados o no vigentes
                                    </div>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Precios de compra históricos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Estado</th>
                                                    <th>Vigencia</th>
                                                    <th width="100">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $PreciComHistoricos = $conn_mysql->query("
                                                    SELECT * FROM precios 
                                                    WHERE tipo = 'c' AND id_prod = '$id_producto' 
                                                    AND (status = '0' OR fecha_fin < CURDATE())
                                                    ORDER BY fecha_ini DESC
                                                ");
                                                while ($precioHist = mysqli_fetch_array($PreciComHistoricos)) {
                                                    $claseFila = getClaseEstado($precioHist['fecha_ini'], $precioHist['fecha_fin'], $precioHist['status']);
                                                    $badgeEstado = getBadgeEstado($precioHist['fecha_ini'], $precioHist['fecha_fin'], $precioHist['status']);
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td class="fw-semibold">$<?=number_format($precioHist['precio'], 2)?></td>
                                                        <td><?= $badgeEstado ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                De <?=date('Y-m-d', strtotime($precioHist['fecha_ini']))?> a <?=date('Y-m-d', strtotime($precioHist['fecha_fin']))?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if($precioHist['status'] == 0): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="id_precio" value="<?=$precioHist['id_precio']?>">
                                                                    <button <?= $perm['sub_precios'];?> type="submit" name="reactivar_precio" class="btn btn-sm btn-success" title="Reactivar precio">
                                                                        <i class="bi bi-arrow-clockwise"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrecio" data-precio-id="<?=$precioHist['id_precio']?>" data-precio-valor="$<?=number_format($precioHist['precio'], 2)?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(mysqli_num_rows($PreciComHistoricos) == 0): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">No hay precios históricos de compra</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <hr class="my-3">

                                    <h6 class="card-subtitle mb-2 text-muted">Precios de venta históricos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Cliente</th>
                                                    <th>Estado</th>
                                                    <th>Vigencia</th>
                                                    <th width="100">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $PreciVenHistoricos = $conn_mysql->query("
                                                    SELECT p.*, d.cod_al, d.noma 
                                                    FROM precios p 
                                                    LEFT JOIN direcciones d ON p.destino = d.id_direc 
                                                    WHERE p.tipo = 'v' AND p.id_prod = '$id_producto' 
                                                    AND (p.status = '0' OR p.fecha_fin < CURDATE())
                                                    ORDER BY p.fecha_ini DESC
                                                ");
                                                while ($precioVenHist = mysqli_fetch_array($PreciVenHistoricos)) {
                                                    $cliente = "General";
                                                    if ($precioVenHist['destino'] != '0' && $precioVenHist['destino'] != '') {
                                                        $cliente = $precioVenHist['cod_al'] . " - " . $precioVenHist['noma'];
                                                    }
                                                    
                                                    $claseFila = getClaseEstado($precioVenHist['fecha_ini'], $precioVenHist['fecha_fin'], $precioVenHist['status']);
                                                    $badgeEstado = getBadgeEstado($precioVenHist['fecha_ini'], $precioVenHist['fecha_fin'], $precioVenHist['status']);
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td class="fw-semibold">$<?=number_format($precioVenHist['precio'], 2)?></td>
                                                        <td>
                                                            <small class="<?= $precioVenHist['destino'] != '0' ? 'text-primary fw-semibold' : 'text-muted' ?>">
                                                                <?= $cliente ?>
                                                            </small>
                                                        </td>
                                                        <td><?= $badgeEstado ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                De <?=date('Y-m-d', strtotime($precioVenHist['fecha_ini']))?> a <?=date('Y-m-d', strtotime($precioVenHist['fecha_fin']))?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if($precioVenHist['status'] == 0): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="id_precio" value="<?=$precioVenHist['id_precio']?>">
                                                                    <button <?= $perm['sub_precios'];?> type="submit" name="reactivar_precio" class="btn btn-sm btn-success" title="Reactivar precio">
                                                                        <i class="bi bi-arrow-clockwise"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrecio" data-precio-id="<?=$precioVenHist['id_precio']?>" data-precio-valor="$<?=number_format($precioVenHist['precio'], 2)?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(mysqli_num_rows($PreciVenHistoricos) == 0): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">No hay precios históricos de venta</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo precio -->
<div class="modal fade" id="AgregarPrec" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="" id="formPrecio">
                <div class="modal-header text-bg-success">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <i class="bi bi-tag me-2"></i> Nuevo precio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="c">Compra</option>
                                <option value="v">Venta</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="precio" class="form-label">Precio $</label>
                            <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="fechaini" class="form-label">Fecha de Inicio</label>
                            <input type="date" value="<?= date('Y-m-d') ?>" name="fechaini" id="fechaini" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="fechafin" class="form-label">Fecha Final</label>
                            <input type="date" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" name="fechafin" id="fechafin" class="form-control" required>
                        </div>
                        
                        <!-- Campo OBLIGATORIO para Ligar Cliente en ventas -->
                        <div class="col-12" id="contenedor-cliente" style="display: none;">
                            <label for="planta" class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select class="form-select" name="planta" id="planta" required>
                                <option value="">Selecciona un cliente</option>
                                <?php
                                $zona_actual = $_SESSION['selected_zone'] ?? '0';

                                $sqlClientes = "SELECT id_cli FROM clientes WHERE status = '1'";

                                if ($zona_actual != '0') {
                                    $sqlClientes .= " AND zona = " . intval($zona_actual);
                                }

                                $sqlDirecciones = "
                                SELECT d.id_direc, d.cod_al, d.noma
                                FROM direcciones d
                                INNER JOIN clientes c ON d.id_us = c.id_cli
                                WHERE d.status = '1' AND c.status = '1'
                                ";

                                if ($zona_actual != '0') {
                                    $sqlDirecciones .= " AND c.zona = " . intval($zona_actual);
                                }

                                $sqlDirecciones .= " ORDER BY d.cod_al";

                                $result = $conn_mysql->query($sqlDirecciones);

                                while ($dir = mysqli_fetch_assoc($result)) {
                                    echo '<option value="'.$dir['id_direc'].'">'.$dir['cod_al'].' ('.$dir['noma'].')</option>';
                                }
                                ?>

                            </select>
                            <div class="form-text text-danger">Para precios de venta es obligatorio seleccionar un cliente</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-3 btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" name="guardar01" class="btn btn-success rounded-3 btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para extender vigencia de un precio -->
<div class="modal fade" id="modalExtenderVigencia" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="">
                <div class="modal-header text-bg-primary">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Extender vigencia</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Extender vigencia del precio: <span id="precio-valor-extender" class="fw-bold"></span></p>
                    <p class="small text-muted">Fecha actual de fin: <span id="fecha-actual-extender" class="fw-semibold"></span></p>
                    
                    <div class="mb-3">
                        <label for="dias_extension" class="form-label">Días a extender:</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtension(7)">7 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtension(15)">15 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtension(30)">30 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtension(60)">60 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtension(90)">90 días</button>
                        </div>
                        <input type="number" class="form-control mt-2" id="dias_extension" name="dias_extension" min="1" max="365" value="30" required>
                        <div class="form-text">Nueva fecha de fin: <span id="nueva-fecha-calculada" class="fw-semibold text-success"></span></div>
                    </div>
                    
                    <input type="hidden" name="id_precio" id="id-precio-extender">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="extender_vigencia" class="btn btn-primary btn-sm rounded-3">Extender Vigencia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para actualizar vigencia específica -->
<div class="modal fade" id="modalActualizarVigencia" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="">
                <div class="modal-header text-bg-warning">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Actualizar fecha de fin</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Actualizar fecha de fin para el precio: <span id="precio-valor-actualizar" class="fw-bold"></span></p>
                    <p class="small text-muted">Fecha actual de fin: <span id="fecha-actual-actualizar" class="fw-semibold"></span></p>
                    
                    <div class="mb-3">
                        <label for="nueva_fecha_fin" class="form-label">Nueva fecha de fin:</label>
                        <input type="date" class="form-control" id="nueva_fecha_fin" name="nueva_fecha_fin" required>
                    </div>
                    
                    <input type="hidden" name="id_precio" id="id-precio-actualizar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="actualizar_vigencia" class="btn btn-warning btn-sm rounded-3">Actualizar Fecha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para extender todos los precios activos -->
<div class="modal fade" id="modalExtenderTodos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="">
                <div class="modal-header text-bg-info">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Extender todos los precios activos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Esta acción extenderá la vigencia de <strong>TODOS</strong> los precios activos de este producto.
                    </div>
                    
                    <div class="mb-3">
                        <label for="dias_extension_todos" class="form-label">Días a extender para todos los precios activos:</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-info" onclick="setDiasExtensionTodos(7)">7 días</button>
                            <button type="button" class="btn btn-outline-info" onclick="setDiasExtensionTodos(15)">15 días</button>
                            <button type="button" class="btn btn-outline-info" onclick="setDiasExtensionTodos(30)">30 días</button>
                            <button type="button" class="btn btn-outline-info" onclick="setDiasExtensionTodos(60)">60 días</button>
                        </div>
                        <input type="number" class="form-control mt-2" id="dias_extension_todos" name="dias_extension_todos" min="1" max="365" value="30" required>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmar_extension_todos" required>
                        <label class="form-check-label" for="confirmar_extension_todos">
                            Confirmo que quiero extender todos los precios activos
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="extender_todos_precios" class="btn btn-info btn-sm rounded-3">Extender Todos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal único para eliminar precio -->
<div class="modal fade" id="modalEliminarPrecio" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="">
                <div class="modal-header text-bg-danger">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Eliminar precio</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar el precio <span id="precio-valor" class="fw-bold"></span>?</p>
                    <input type="hidden" name="id_precio" id="id-precio-eliminar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="eliminar_precio" class="btn btn-danger btn-sm rounded-3">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de eliminar precio
        const modalEliminar = document.getElementById('modalEliminarPrecio');
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const precioId = button.getAttribute('data-precio-id');
            const precioValor = button.getAttribute('data-precio-valor');

            document.getElementById('id-precio-eliminar').value = precioId;
            document.getElementById('precio-valor').textContent = precioValor;
        });

        // Modal de extender vigencia
        const modalExtender = document.getElementById('modalExtenderVigencia');
        modalExtender.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const precioId = button.getAttribute('data-precio-id');
            const precioValor = button.getAttribute('data-precio-valor');
            const fechaActual = button.getAttribute('data-fecha-actual');

            document.getElementById('id-precio-extender').value = precioId;
            document.getElementById('precio-valor-extender').textContent = precioValor;
            document.getElementById('fecha-actual-extender').textContent = fechaActual;
            
            // Calcular nueva fecha por defecto
            calcularNuevaFecha();
        });

        // Modal de actualizar vigencia
        const modalActualizar = document.getElementById('modalActualizarVigencia');
        modalActualizar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const precioId = button.getAttribute('data-precio-id');
            const precioValor = button.getAttribute('data-precio-valor');
            const fechaActual = button.getAttribute('data-fecha-actual');

            document.getElementById('id-precio-actualizar').value = precioId;
            document.getElementById('precio-valor-actualizar').textContent = precioValor;
            document.getElementById('fecha-actual-actualizar').textContent = fechaActual;
            document.getElementById('nueva_fecha_fin').value = fechaActual;
        });

        // Calcular nueva fecha cuando cambian los días de extensión
        document.getElementById('dias_extension').addEventListener('input', calcularNuevaFecha);
    });

    function setDiasExtension(dias) {
        document.getElementById('dias_extension').value = dias;
        calcularNuevaFecha();
    }

    function setDiasExtensionTodos(dias) {
        document.getElementById('dias_extension_todos').value = dias;
    }

    function calcularNuevaFecha() {
        const dias = parseInt(document.getElementById('dias_extension').value) || 0;
        const fechaActual = document.getElementById('fecha-actual-extender').textContent;
        
        if (fechaActual && dias > 0) {
            const fecha = new Date(fechaActual);
            fecha.setDate(fecha.getDate() + dias);
            const nuevaFecha = fecha.toISOString().split('T')[0];
            document.getElementById('nueva-fecha-calculada').textContent = nuevaFecha;
        }
    }

    // Toggle cliente field
    const tipoSelect = document.getElementById('tipo');
    const contenedorCliente = document.getElementById('contenedor-cliente');
    const plantaSelect = document.getElementById('planta');
    const formPrecio = document.getElementById('formPrecio');

    function toggleClienteField() {
        if (tipoSelect.value === 'v') {
            contenedorCliente.style.display = 'block';
            plantaSelect.required = true;
            plantaSelect.disabled = false;
        } else {
            contenedorCliente.style.display = 'none';
            plantaSelect.required = false;
            plantaSelect.disabled = true;
            plantaSelect.value = '';
        }
    }

    tipoSelect.addEventListener('change', toggleClienteField);
    toggleClienteField();

    formPrecio.addEventListener('submit', function(e) {
        if (tipoSelect.value === 'v' && (plantaSelect.value === '' || plantaSelect.value === '0')) {
            e.preventDefault();
            alert('Para precios de venta es obligatorio seleccionar un cliente.');
            plantaSelect.focus();
            return false;
        }
    });

    // Inicializar Select2
    $(document).ready(function() {
        $('#planta').select2({
            allowClear: false,
            language: "es",
            dropdownParent: $('#AgregarPrec'),
            width: '100%',
            placeholder: "Selecciona un cliente"
        });
    });
</script>