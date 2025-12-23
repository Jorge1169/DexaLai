<?php
$id_transporte = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // <-- Esto es clave
if ($id_transporte) {
    $sqltransp = "SELECT * FROM transportes WHERE id_transp = ?";
    $stmttransporte = $conn_mysql->prepare($sqltransp);
    $stmttransporte->bind_param('i', $id_transporte);
    $stmttransporte->execute();
    $resulttransporte = $stmttransporte->get_result();
    $transp = $resulttransporte->fetch_assoc();
} 

// Procesar eliminación de precio (cambiar status a 0)
if (isset($_POST['eliminar_precio'])) {
    $id_precio = $_POST['id_precio']; 
    $sqlEliminar = "UPDATE precios SET status = 0 WHERE id_precio = ?";
    $stmtEliminar = $conn_mysql->prepare($sqlEliminar);
    $stmtEliminar->bind_param('i', $id_precio);
    $stmtEliminar->execute();

    if ($stmtEliminar->affected_rows > 0) {
        alert("Precio eliminado con éxito", 1, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', "Elimino el precio del fletero ". $id_transporte);
    } else {
        alert("Error al eliminar el precio", 2, "V_transporte&id=".$id_transporte."");
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
        alert("Precio reactivado con éxito", 1, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Reactivó el precio ' . $id_precio);
    } else {
        alert("Error al reactivar el precio", 2, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Intento reactivar el precio ' . $id_precio);
    }
}

// Procesar actualización rápida de vigencia
if (isset($_POST['actualizar_vigencia'])) {
    $id_precio = $_POST['id_precio']; 
    $nueva_fecha_fin = $_POST['nueva_fecha_fin'];
    
    if (!DateTime::createFromFormat('Y-m-d', $nueva_fecha_fin)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "V_transporte&id=".$id_transporte."");
        exit;
    }
    
    $sqlActualizar = "UPDATE precios SET fecha_fin = ?, usuario = ? WHERE id_precio = ?";
    $stmtActualizar = $conn_mysql->prepare($sqlActualizar);
    $stmtActualizar->bind_param('sii', $nueva_fecha_fin, $idUser, $id_precio);
    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows > 0) {
        alert("Vigencia actualizada con éxito", 1, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Actualizó vigencia del precio ' . $id_precio . ' a ' . $nueva_fecha_fin);
    } else {
        alert("Error al actualizar la vigencia", 2, "V_transporte&id=".$id_transporte."");
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
        alert("Vigencia extendida " . $dias_extension . " días con éxito", 1, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Extendió vigencia del precio ' . $id_precio . ' por ' . $dias_extension . ' días');
    } else {
        alert("Error al extender la vigencia", 2, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Intento extender vigencia del precio ' . $id_precio);
    }
}

// Procesar extensión masiva de todos los precios activos
if (isset($_POST['extender_todos_precios'])) {
    $dias_extension = $_POST['dias_extension_todos'];
    
    $sqlExtenderTodos = "UPDATE precios SET fecha_fin = DATE_ADD(fecha_fin, INTERVAL ? DAY), usuario = ? 
    WHERE id_prod = ? AND status = '1' AND (tipo = 'FT' OR tipo = 'FV')";
    $stmtExtenderTodos = $conn_mysql->prepare($sqlExtenderTodos);
    $stmtExtenderTodos->bind_param('iii', $dias_extension, $idUser, $id_transporte);
    $stmtExtenderTodos->execute();

    if ($stmtExtenderTodos->affected_rows > 0) {
        alert("Todos los precios activos extendidos " . $dias_extension . " días con éxito", 1, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Extendió todos los precios activos del transportista ' . $id_transporte . ' por ' . $dias_extension . ' días');
    } else {
        alert("Error al extender los precios", 2, "V_transporte&id=".$id_transporte."");
        logActivity('PRECIO', 'Intento extender todos los precios del transportista ' . $id_transporte);
    }
}

if (isset($_POST['guardar01'])) {

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
    $PrecioData = [
        'id_prod' => $id_transporte,
        'precio' => $_POST['precio'],
        'tipo' => $_POST['tipo'],
        'origen' => $_POST['origen'],
        'destino' => $_POST['destino'],
        'conmin' => $_POST['conmin'],
        'fecha_ini' => $fecha_Ini,
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


    if ($stmt->affected_rows <= 0) {
      throw new Exception("Error al registrar la compra");
  }

  alert("Precio Registrado con exito",1,"V_transporte&id=".$id_transporte."");
  logActivity('PRECIO', "Registro precio de flete para el fletero ". $id_transporte);
}
// Procesar precio MEO (nuevo)
if (isset($_POST['guardar_precio_meo'])) {
    $fecha_Ini = $_POST['fechaini'] ?? date('Y-m-d');
    $fecha_Fin = $_POST['fechafin'] ?? date('Y-m-d');
    
    $PrecioData = [
        'id_prod' => $id_transporte,
        'precio' => $_POST['precio'],
        'tipo' => $_POST['tipo'], // MFV o MFT
        'origen' => $_POST['origen'],
        'destino' => $_POST['destino'],
        'conmin' => $_POST['conmin'],
        'fecha_ini' => $fecha_Ini,
        'fecha_fin' => $fecha_Fin,
        'usuario' => $idUser,
        'status' => 1,
        'cap_ven' => $_POST['cap_ven'] // CAP o VEN
    ];
    
    // Insertar en BD (mismo código que el actual)
    $columns = implode(', ', array_keys($PrecioData));
    $placeholders = str_repeat('?,', count($PrecioData) - 1) . '?';
    $sql = "INSERT INTO precios ($columns) VALUES ($placeholders)";
    $stmt = $conn_mysql->prepare($sql);
    $types = str_repeat('s', count($PrecioData));
    $stmt->bind_param($types, ...array_values($PrecioData));
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        alert("Precio MEO Registrado con éxito", 1, "V_transporte&id=".$id_transporte);
        logActivity('PRECIO', "Registro precio MEO para fletero ". $id_transporte);
    }
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

// Función para determinar el estado de vigencia
function obtenerEstadoVigencia($fecha_fin) {
    $hoy = date('Y-m-d');
    $tres_dias = date('Y-m-d', strtotime('+3 days'));
    
    if ($fecha_fin < $hoy) {
        return [
            'estado' => 'caducado',
            'texto' => 'Caducado',
            'clase' => 'bg-danger',
            'icono' => 'bi-exclamation-triangle',
            'tooltip' => 'Este precio ha caducado'
        ];
    } elseif ($fecha_fin <= $tres_dias) {
        return [
            'estado' => 'por_caducar',
            'texto' => 'Por caducar',
            'clase' => 'bg-warning',
            'icono' => 'bi-clock',
            'tooltip' => 'Caduca en menos de 3 días'
        ];
    } else {
        return [
            'estado' => 'vigente',
            'texto' => 'Vigente',
            'clase' => 'bg-success',
            'icono' => 'bi-check-circle',
            'tooltip' => 'Precio vigente'
        ];
    }
}

$zona_actual = $_SESSION['selected_zone'] ?? '0';

?>

<div class="container mt-2">
    <div class="card mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Datos del transportista</h5>
                <span class="small">Código: <?=$transp['placas']?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="?p=transportes" class="btn btn-sm rounded-3 btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
                <a href="?p=E_transportista&id=<?=$id_transporte?>" class="btn btn-sm rounded-3 btn-light" <?= $perm['Clien_Editar'];?>>
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            Información del transportista <i class="bi bi-box-seam text-primary"></i>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    R. social
                                    <span class="badge text-body"><?=$transp['razon_so']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Codigo
                                    <span class="badge text-body"><?=$transp['placas']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Linea
                                    <span class="badge text-body"><?=$transp['linea']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Tipo
                                    <span class="badge text-body"><?=$transp['tipo']?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Correo
                                    <span class="badge text-body"><?=$transp['correo']?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Precios <i class="bi bi-cash-stack text-teal"></i></span>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="d-flex gap-1 small">
                                    <span class="badge bg-success">Vigente</span>
                                    <span class="badge bg-warning text-dark">Por Caducar</span>
                                    <span class="badge bg-danger">Caducado</span>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <?php if ($tipoZonaActual == 'NOR'): ?>
                                        <!-- BOTÓN solo para zonas NOR -->
                                        <button <?= $perm['sub_precios'];?> type="button" class="btn btn-success btn-sm rounded-3" 
                                            data-bs-toggle="modal" data-bs-target="#AgregarPrec">
                                            <i class="bi bi-plus-circle me-1"></i> Nuevo Precio
                                        </button>
                                    <?php elseif ($tipoZonaActual == 'MEO'): ?>
                                        <!-- BOTÓN solo para zonas MEO -->
                                        <button <?= $perm['sub_precios'];?> type="button" class="btn btn-info btn-sm rounded-3" 
                                            data-bs-toggle="modal" data-bs-target="#AgregarPrecMEO">
                                            <i class="bi bi-building me-1"></i> Nuevo Precio
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">

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

                                    <h6 class="card-subtitle mb-2 text-muted">Precios de flete</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Ruta</th>
                                                    <th>Peso Mín.</th>
                                                    <th>Vigencia</th>
                                                    <th>Días Rest.</th>
                                                    <th>Estado</th>
                                                    <th width="150">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($tipoZonaActual == 'MEO') {
                                                    $PreciFle00 = $conn_mysql->query("
                                                        SELECT p.*, o.cod_al as cod_origen, o.noma as nom_origen,
                                                        d.cod_al as cod_destino, d.noma as nom_destino
                                                        FROM precios p
                                                        LEFT JOIN direcciones o ON p.origen = o.id_direc
                                                        LEFT JOIN direcciones d ON p.destino = d.id_direc
                                                        WHERE p.tipo IN ('MFV', 'MFT') 
                                                        AND p.id_prod = '$id_transporte'
                                                        AND p.status = '1'
                                                        ORDER BY p.fecha_ini DESC
                                                        ");
                                                } else {
                                                    $PreciFle00 = $conn_mysql->query("
                                                        SELECT p.*, 
                                                        o.cod_al as cod_origen, o.noma as nom_origen,
                                                        d.cod_al as cod_destino, d.noma as nom_destino
                                                        FROM precios p
                                                        LEFT JOIN direcciones o ON p.origen = o.id_direc
                                                        LEFT JOIN direcciones d ON p.destino = d.id_direc
                                                        WHERE p.tipo IN ('FT', 'FV') 
                                                        AND p.status = '1' 
                                                        AND p.id_prod = '$id_transporte'
                                                        ORDER BY p.fecha_ini DESC, p.fecha_fin DESC
                                                        ");
                                                }

                                                while ($PreciFle01 = mysqli_fetch_array($PreciFle00)) {
                                    // Determinar texto y badge según el tipo
                                                    if ($tipoZonaActual == 'MEO') {
                                                        $tipo_texto = ($PreciFle01['tipo'] == 'MFT') ? 'Por tonelada (MEO)' : 'Por viaje (MEO)';
                                                        $tipo_badge = ($PreciFle01['tipo'] == 'MFT') ? 'bg-primary' : 'bg-success';
                                                        
                                        // Mostrar tipo de movimiento para MEO
                                                        $movimiento = isset($PreciFle01['cap_ven']) ? 
                                                        ($PreciFle01['cap_ven'] == 'CAP' ? 
                                                            '<span class="badge bg-info me-1">Captación</span>' : 
                                                            '<span class="badge bg-secondary me-1">Venta</span>') : '';
                                                    } else {
                                                        $tipo_texto = ($PreciFle01['tipo'] == 'FT') ? 'Por tonelada' : 'Por viaje';
                                                        $tipo_badge = ($PreciFle01['tipo'] == 'FT') ? 'bg-primary' : 'bg-success';
                                                        $movimiento = '';
                                                    }
                                                    
                                                    $peso_minimo = $PreciFle01['conmin'] > 0 ? $PreciFle01['conmin'] . ' ton' : '';

                                    // Obtener estado de vigencia
                                                    $estadoVigencia = obtenerEstadoVigencia($PreciFle01['fecha_fin']);

                                    // Calcular días restantes
                                                    $hoy = new DateTime();
                                                    $fechaFin = new DateTime($PreciFle01['fecha_fin']);
                                                    $diasRestantes = $hoy->diff($fechaFin)->days;

                                    // Determinar clase para la fila según el estado
                                                    $claseFila = '';
                                                    if ($estadoVigencia['estado'] == 'caducado') {
                                                        $claseFila = 'table-danger';
                                                    } elseif ($estadoVigencia['estado'] == 'por_caducar') {
                                                        $claseFila = 'table-warning';
                                                    }
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td>
                                                            <span class="fw-semibold">$<?= number_format($PreciFle01['precio'], 2) ?></span>
                                                            <br>
                                                            <?= $movimiento ?>
                                                            <span class="badge <?= $tipo_badge ?>"><?= $tipo_texto ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <small class="text-primary me-1">
                                                                    <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($PreciFle01['cod_origen']) ?>
                                                                </small>
                                                                <i class="bi bi-arrow-right text-muted mx-1"></i>
                                                                <small class="text-success">
                                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($PreciFle01['cod_destino']) ?>
                                                                </small>
                                                            </div>
                                                            <div class="small text-muted">
                                                                <?= htmlspecialchars($PreciFle01['nom_origen']) ?> → <?= htmlspecialchars($PreciFle01['nom_destino']) ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border"><?= $peso_minimo ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="small">
                                                                <div class="fw-semibold">
                                                                    <?= date('d/m/Y', strtotime($PreciFle01['fecha_ini'])) ?> - 
                                                                    <?= date('d/m/Y', strtotime($PreciFle01['fecha_fin'])) ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?= diasRestantes($PreciFle01['fecha_fin']) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-estado-precio <?= $estadoVigencia['clase'] ?>" 
                                                                title="<?= $estadoVigencia['tooltip'] ?>"
                                                                data-bs-toggle="tooltip" data-bs-placement="top">
                                                                <i class="bi <?= $estadoVigencia['icono'] ?> me-1"></i>
                                                                <?= $estadoVigencia['texto'] ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtenderVigencia" 
                                                                    data-precio-id="<?=$PreciFle01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciFle01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciFle01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-plus" title="Extender vigencia"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalActualizarVigencia"
                                                                    data-precio-id="<?=$PreciFle01['id_precio']?>" 
                                                                    data-precio-valor="$<?=number_format($PreciFle01['precio'], 2)?>"
                                                                    data-fecha-actual="<?=$PreciFle01['fecha_fin']?>">
                                                                    <i class="bi bi-calendar-check" title="Actualizar fecha"></i>
                                                                </button>
                                                                <button <?= $perm['sub_precios'];?> type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrecio" 
                                                                    data-precio-id="<?= $PreciFle01['id_precio'] ?>" 
                                                                    data-precio-valor="$<?= number_format($PreciFle01['precio'], 2) ?>"
                                                                    data-precio-estado="<?= $estadoVigencia['estado'] ?>">
                                                                    <i class="bi bi-trash" title="Eliminar"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(mysqli_num_rows($PreciFle00) == 0): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted py-3">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            No hay precios de flete registrados
                                                        </td>
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

                                    <h6 class="card-subtitle mb-2 text-muted">Precios de flete históricos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Precio</th>
                                                    <th>Ruta</th>
                                                    <th>Peso Mín.</th>
                                                    <th>Vigencia</th>
                                                    <th>Estado</th>
                                                    <th width="100">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                // MODIFICAR ESTA CONSULTA también
                                                if ($tipoZonaActual == 'MEO') {
                                                    $PreciFleHistoricos = $conn_mysql->query("
                                                        SELECT p.*, 
                                                        o.cod_al as cod_origen, o.noma as nom_origen,
                                                        d.cod_al as cod_destino, d.noma as nom_destino
                                                        FROM precios p
                                                        LEFT JOIN direcciones o ON p.origen = o.id_direc
                                                        LEFT JOIN direcciones d ON p.destino = d.id_direc
                                                        WHERE p.tipo IN ('MFV', 'MFT') 
                                                        AND p.id_prod = '$id_transporte' 
                                                        AND (p.status = '0' OR p.fecha_fin < CURDATE())
                                                        ORDER BY p.fecha_ini DESC
                                                        ");
                                                } else {
                                                    $PreciFleHistoricos = $conn_mysql->query("
                                                        SELECT p.*, 
                                                        o.cod_al as cod_origen, o.noma as nom_origen,
                                                        d.cod_al as cod_destino, d.noma as nom_destino
                                                        FROM precios p
                                                        LEFT JOIN direcciones o ON p.origen = o.id_direc
                                                        LEFT JOIN direcciones d ON p.destino = d.id_direc
                                                        WHERE p.tipo IN ('FT', 'FV') 
                                                        AND p.id_prod = '$id_transporte' 
                                                        AND (p.status = '0' OR p.fecha_fin < CURDATE())
                                                        ORDER BY p.fecha_ini DESC
                                                        ");
                                                }

                                                while ($precioHist = mysqli_fetch_array($PreciFleHistoricos)) {
                                    // Determinar texto y badge según el tipo
                                                    if ($tipoZonaActual == 'MEO') {
                                                        $tipo_texto = ($precioHist['tipo'] == 'MFT') ? 'Por tonelada (MEO)' : 'Por viaje (MEO)';
                                                        $tipo_badge = ($precioHist['tipo'] == 'MFT') ? 'bg-primary' : 'bg-success';
                                                        
                                        // Mostrar tipo de movimiento para MEO
                                                        $movimiento = isset($precioHist['cap_ven']) ? 
                                                        ($precioHist['cap_ven'] == 'CAP' ? 
                                                            '<span class="badge bg-info me-1">Captación</span>' : 
                                                            '<span class="badge bg-secondary me-1">Venta</span>') : '';
                                                    } else {
                                                        $tipo_texto = ($precioHist['tipo'] == 'FT') ? 'Por tonelada' : 'Por viaje';
                                                        $tipo_badge = ($precioHist['tipo'] == 'FT') ? 'bg-primary' : 'bg-success';
                                                        $movimiento = '';
                                                    }
                                                    
                                                    $peso_minimo = $precioHist['conmin'] > 0 ? $precioHist['conmin'] . ' ton' : '';

                                                    $claseFila = getClaseEstado($precioHist['fecha_ini'], $precioHist['fecha_fin'], $precioHist['status']);
                                                    $badgeEstado = getBadgeEstado($precioHist['fecha_ini'], $precioHist['fecha_fin'], $precioHist['status']);
                                                    ?>
                                                    <tr class="<?= $claseFila ?>">
                                                        <td>
                                                            <span class="fw-semibold">$<?= number_format($precioHist['precio'], 2) ?></span>
                                                            <br>
                                                            <?= $movimiento ?>
                                                            <span class="badge <?= $tipo_badge ?>"><?= $tipo_texto ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <small class="text-primary me-1">
                                                                    <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($precioHist['cod_origen']) ?>
                                                                </small>
                                                                <i class="bi bi-arrow-right text-muted mx-1"></i>
                                                                <small class="text-success">
                                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($precioHist['cod_destino']) ?>
                                                                </small>
                                                            </div>
                                                            <div class="small text-muted">
                                                                <?= htmlspecialchars($precioHist['nom_origen']) ?> → <?= htmlspecialchars($precioHist['nom_destino']) ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border"><?= $peso_minimo ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="small">
                                                                <div class="fw-semibold">
                                                                    <?= date('d/m/Y', strtotime($precioHist['fecha_ini'])) ?> - 
                                                                    <?= date('d/m/Y', strtotime($precioHist['fecha_fin'])) ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= $badgeEstado ?></td>
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
                                                if(mysqli_num_rows($PreciFleHistoricos) == 0): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">No hay precios históricos de flete</td>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form class="forms-sample" method="post" action="">
                <div class="modal-header text-bg-success">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <i class="bi bi-tag me-2"></i> Nuevo precio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="FT">Por tonelada</option>
                                <option value="FV">Por viaje</option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label for="precio" class="form-label">Precio $</label>
                            <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="origen" class="form-label">Origen</label>
                            <select class="form-select" name="origen" id="origen">
                                <option selected disabled value="">Selecciona un Origen...</option>
                                <?php
                                $sqlOrigen = "
                                SELECT d.id_direc, d.cod_al, d.noma
                                FROM direcciones d
                                INNER JOIN proveedores p ON d.id_prov = p.id_prov
                                WHERE d.status = '1' AND p.status = '1'
                                ";

                                if ($zona_actual != '0') {
                                    $sqlOrigen .= " AND p.zona = " . intval($zona_actual);
                                }

                                $sqlOrigen .= " ORDER BY d.cod_al";
                                $resOrigen = $conn_mysql->query($sqlOrigen);

                                while ($ori = mysqli_fetch_assoc($resOrigen)) {
                                    echo '<option value="'.$ori['id_direc'].'">'.$ori['cod_al'].' ('.$ori['noma'].')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label for="destino" class="form-label">Destino</label>
                            <select class="form-select" name="destino" id="destino">
                                <option selected disabled value="">Selecciona un Destino...</option>
                                <?php
                                $sqlDestino = "
                                SELECT d.id_direc, d.cod_al, d.noma
                                FROM direcciones d
                                INNER JOIN clientes c ON d.id_us = c.id_cli
                                WHERE d.status = '1' AND c.status = '1'
                                ";

                                if ($zona_actual != '0') {
                                    $sqlDestino .= " AND c.zona = " . intval($zona_actual);
                                }

                                $sqlDestino .= " ORDER BY d.cod_al";
                                $resDestino = $conn_mysql->query($sqlDestino);

                                while ($des = mysqli_fetch_assoc($resDestino)) {
                                    echo '<option value="'.$des['id_direc'].'">'.$des['cod_al'].' ('.$des['noma'].')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="conmin" class="form-label">* Peso minimo (En toneladas)</label>
                            <input type="number" step="0.01" min="0" name="conmin" value="0" id="conmin" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="fechaini" class="form-label">Fecha de Inicio</label>
                            <input type="date" value="<?= date('Y-m-d') ?>" name="fechaini" id="fechaini" class="form-control" required>
                        </div>
                        <div class="col-lg-4">
                            <label for="fechafin" class="form-label">Fecha Final</label>
                            <input type="date" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" name="fechafin" id="fechafin" class="form-control" required>
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

<!-- Los mismos modales de extensión que en el código anterior -->
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
                        Esta acción extenderá la vigencia de <strong>TODOS</strong> los precios activos de este transportista.
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
<!-- Modal para precio MEO (solo se muestra si es zona MEO) -->
<?php if ($tipoZonaActual == 'MEO'): ?>
    <div class="modal fade" id="AgregarPrecMEO" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form class="forms-sample" method="post" action="">
                    <input type="hidden" name="tipo_precio" value="MEO">
                    <div class="modal-header text-bg-info">
                        <h5 class="modal-title">
                            <i class="bi bi-building me-2"></i> Nuevo Precio MEO
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Seleccionar tipo de movimiento -->
                            <div class="col-lg-6">
                                <label for="cap_ven" class="form-label">Tipo de Movimiento</label>
                                <select class="form-select" name="cap_ven" id="cap_ven" required 
                                onchange="cambiarOrigenDestinoMEO(this.value)">
                                <option value="">Seleccione...</option>
                                <option value="CAP">Captación/Compra</option>
                                <option value="VEN">Venta/Salida</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="tipo" class="form-label">Tipo de Precio</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="MFV">Por viaje (MEO)</option>
                                <option value="MFT">Por tonelada (MEO)</option>
                            </select>
                        </div>
                        
                        <!-- Origen y Destino se llenan con JavaScript -->
                        <div class="col-lg-6">
                            <label for="origen" class="form-label">Origen</label>
                            <select class="form-select" name="origen" id="origen_meo" required>
                                <option value="">Seleccione tipo de movimiento primero</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="destino" class="form-label">Destino</label>
                            <select class="form-select" name="destino" id="destino_meo" required>
                                <option value="">Seleccione tipo de movimiento primero</option>
                            </select>
                        </div>
                        
                        <!-- Resto de campos igual -->
                        <div class="col-lg-4">
                            <label for="precio" class="form-label">Precio $</label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" required>
                        </div>
                        
                        <div class="col-lg-4">
                            <label for="conmin" class="form-label">Peso Mínimo (ton)</label>
                            <input type="number" step="0.01" min="0" name="conmin" value="0" class="form-control">
                        </div>
                        
                        <div class="col-lg-4">
                            <label for="fechaini" class="form-label">Fecha Inicio</label>
                            <input type="date" value="<?= date('Y-m-d') ?>" name="fechaini" class="form-control" required>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="fechafin" class="form-label">Fecha Fin</label>
                            <input type="date" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" name="fechafin" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="guardar_precio_meo" class="btn btn-info">
                        <i class="bi bi-check-circle me-1"></i> Guardar Precio MEO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
    $(document).ready(function() {
        $('#origen').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es",
            dropdownParent: $('#AgregarPrec'),
            width: '100%'
        });
        
        $('#destino').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es",
            dropdownParent: $('#AgregarPrec'),
            width: '100%'
        });
    });

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
</script>
<script>
// Función para cambiar origen/destino en MEO
    function cambiarOrigenDestinoMEO(tipo) {
        const origenSelect = document.getElementById('origen_meo');
        const destinoSelect = document.getElementById('destino_meo');

    // Limpiar opciones
        origenSelect.innerHTML = '<option value="">Cargando...</option>';
        destinoSelect.innerHTML = '<option value="">Cargando...</option>';

    // Determinar qué cargar según el tipo
        if (tipo === 'CAP') {
        // CAP: Proveedores → Almacenes
            cargarProveedores(origenSelect);
            cargarAlmacenes(destinoSelect);
        } else if (tipo === 'VEN') {
        // VEN: Almacenes → Clientes
            cargarAlmacenes(origenSelect);
            cargarClientes(destinoSelect);
        }
    }

// Funciones para cargar opciones (pueden ser AJAX o PHP embebido)
    function cargarProveedores(selectElement) {
        selectElement.innerHTML = `
        <option value="">Seleccione proveedor...</option>
        <?php
        $prov = $conn_mysql->query("
            SELECT d.id_direc, d.cod_al, d.noma 
            FROM direcciones d
            INNER JOIN proveedores p ON d.id_prov = p.id_prov
            WHERE d.status = '1' AND p.status = '1'
            AND p.zona = '$zona_actual'
            ORDER BY d.cod_al
            ");
        while ($p = mysqli_fetch_assoc($prov)) {
            echo "<option value='{$p['id_direc']}'>{$p['cod_al']} ({$p['noma']})</option>";
        }
        ?>
    `;
}

function cargarClientes(selectElement) {
    selectElement.innerHTML = `
        <option value="">Seleccione cliente...</option>
        <?php
        $cli = $conn_mysql->query("
            SELECT d.id_direc, d.cod_al, d.noma 
            FROM direcciones d
            INNER JOIN clientes c ON d.id_us = c.id_cli
            WHERE d.status = '1' AND c.status = '1'
            AND c.zona = '$zona_actual'
            ORDER BY d.cod_al
            ");
        while ($c = mysqli_fetch_assoc($cli)) {
            echo "<option value='{$c['id_direc']}'>{$c['cod_al']} ({$c['noma']})</option>";
        }
        ?>
    `;
}

function cargarAlmacenes(selectElement) {
    selectElement.innerHTML = `
        <option value="">Seleccione almacén...</option>
        <?php
        $alm = $conn_mysql->query("
            SELECT d.id_direc, d.cod_al, d.noma 
            FROM direcciones d
            INNER JOIN almacenes a ON d.id_alma = a.id_alma
            WHERE d.status = '1' AND a.status = '1'
            AND a.zona = '$zona_actual'
            ORDER BY d.cod_al
            ");
        while ($a = mysqli_fetch_assoc($alm)) {
            echo "<option value='{$a['id_direc']}'>{$a['cod_al']} ({$a['noma']})</option>";
        }
        ?>
    `;
}
</script>