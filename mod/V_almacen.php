<?php
// Obtener ID del Almacén
$id_almacen = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona actual
// Obtener datos del almacén
$almacen = [];
$direcciones = [];
$bodegas_cliente = [];
$precios_servicio = [];

if ($id_almacen) {
    // Datos básicos del almacén
    $sqlAlmacen = "SELECT * FROM almacenes WHERE id_alma = ?";
    $stmtAlmacen = $conn_mysql->prepare($sqlAlmacen);
    $stmtAlmacen->bind_param('i', $id_almacen);
    $stmtAlmacen->execute();
    $resultAlmacen = $stmtAlmacen->get_result();
    $almacen = $resultAlmacen->fetch_assoc();
    
    // Direcciones del almacén
    $sqlDirecciones = "SELECT * FROM direcciones WHERE status = '1' AND id_alma = ? ORDER BY noma ASC";
    $stmtDirecciones = $conn_mysql->prepare($sqlDirecciones);
    $stmtDirecciones->bind_param('i', $id_almacen);
    $stmtDirecciones->execute();
    $resultDirecciones = $stmtDirecciones->get_result();
    $direcciones = $resultDirecciones->fetch_all(MYSQLI_ASSOC);

    // Obtener zona
    $zon0 = $conn_mysql->query("SELECT * FROM zonas where id_zone = '".$almacen['zona']."'");
    $zon1 = mysqli_fetch_array($zon0);

    // Bodegas de clientes disponibles para precios de servicio (solo SUR)
    if ($tipoZonaActual === 'SUR') {
        $zona_almacen = intval($almacen['zona'] ?? 0);
        $sqlBodegasCliente = "SELECT d.id_direc, d.cod_al, d.noma, c.cod as cod_cliente, c.nombre as nombre_cliente
                             FROM direcciones d
                             INNER JOIN clientes c ON d.id_us = c.id_cli
                             WHERE d.status = '1' AND c.status = '1'";

        if ($zona_almacen > 0) {
            $sqlBodegasCliente .= " AND c.zona = " . $zona_almacen;
        }

        $sqlBodegasCliente .= " ORDER BY c.nombre, d.cod_al";
        $resultBodegasCliente = $conn_mysql->query($sqlBodegasCliente);
        if ($resultBodegasCliente) {
            $bodegas_cliente = $resultBodegasCliente->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Guardar/actualizar precio de servicio (solo zona SUR)
if (isset($_POST['guardar_precio_servicio']) && $tipoZonaActual === 'SUR' && $id_almacen) {
    $tipo_servicio = $_POST['tipo_servicio'] ?? '';
    $origen_servicio = intval($_POST['origen_servicio'] ?? 0);
    $destino_servicio = intval($_POST['destino_servicio'] ?? 0);
    $precio_servicio = floatval($_POST['precio_servicio'] ?? 0);
    $peso_minimo_servicio = floatval($_POST['peso_minimo_servicio'] ?? 0);
    $fecha_ini_servicio = $_POST['fecha_ini_servicio'] ?? date('Y-m-d');
    $fecha_fin_servicio = $_POST['fecha_fin_servicio'] ?? date('Y-m-d', strtotime('+1 month'));

    $tipos_validos = ['SVT', 'SVV'];
    $origen_valido = false;
    foreach ($direcciones as $dir) {
        if (intval($dir['id_direc']) === $origen_servicio) {
            $origen_valido = true;
            break;
        }
    }

    if (!in_array($tipo_servicio, $tipos_validos, true)) {
        alert('Tipo de servicio no válido', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    if (!$origen_valido) {
        alert('La bodega origen no pertenece a este almacén', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    if ($destino_servicio <= 0) {
        alert('Debe seleccionar una bodega destino válida', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    if ($precio_servicio <= 0) {
        alert('El precio del servicio debe ser mayor a 0', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    if (!DateTime::createFromFormat('Y-m-d', $fecha_ini_servicio) || !DateTime::createFromFormat('Y-m-d', $fecha_fin_servicio)) {
        alert('Formato de fecha inválido', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    // Si existe precio activo con misma combinación, actualizarlo; si no, insertar nuevo
    $sqlExiste = "SELECT id_precio FROM precios
                 WHERE id_prod = ? AND tipo = ? AND origen = ? AND destino = ? AND status = '1'
                 LIMIT 1";
    $stmtExiste = $conn_mysql->prepare($sqlExiste);
    $id_almacen_int = intval($id_almacen);
    $stmtExiste->bind_param('isii', $id_almacen_int, $tipo_servicio, $origen_servicio, $destino_servicio);
    $stmtExiste->execute();
    $resultExiste = $stmtExiste->get_result();

    if ($resultExiste && $resultExiste->num_rows > 0) {
        $precioExistente = $resultExiste->fetch_assoc();
        $sqlActualizar = "UPDATE precios
                         SET precio = ?, conmin = ?, fecha_ini = ?, fecha_fin = ?, cap_ven = 'VEN', usuario = ?, status = '1'
                         WHERE id_precio = ?";
        $stmtActualizar = $conn_mysql->prepare($sqlActualizar);
        $precio_str = (string)$precio_servicio;
        $conmin_str = (string)$peso_minimo_servicio;
        $stmtActualizar->bind_param('ssssii', $precio_str, $conmin_str, $fecha_ini_servicio, $fecha_fin_servicio, $idUser, $precioExistente['id_precio']);
        $stmtActualizar->execute();

        alert('Precio de servicio actualizado con éxito', 1, 'V_almacen&id=' . $id_almacen);
        logActivity('PRECIO_SERVICIO', 'Actualizó precio de servicio en almacén ' . $id_almacen);
        exit;
    }

    $sqlInsertar = "INSERT INTO precios (id_prod, precio, tipo, origen, destino, conmin, fecha_ini, fecha_fin, usuario, status, cap_ven)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '1', 'VEN')";
    $stmtInsertar = $conn_mysql->prepare($sqlInsertar);
    $precio_str = (string)$precio_servicio;
    $conmin_str = (string)$peso_minimo_servicio;
    $stmtInsertar->bind_param('issiisssi', $id_almacen_int, $precio_str, $tipo_servicio, $origen_servicio, $destino_servicio, $conmin_str, $fecha_ini_servicio, $fecha_fin_servicio, $idUser);
    $stmtInsertar->execute();

    alert('Precio de servicio registrado con éxito', 1, 'V_almacen&id=' . $id_almacen);
    logActivity('PRECIO_SERVICIO', 'Registró precio de servicio en almacén ' . $id_almacen);
    exit;
}

// Cambiar estado de precio de servicio (solo zona SUR)
if (isset($_POST['cambiar_estado_precio_servicio']) && $tipoZonaActual === 'SUR' && $id_almacen) {
    $id_precio_servicio = intval($_POST['id_precio_servicio'] ?? 0);
    $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0) === 1 ? 1 : 0;

    if ($id_precio_servicio > 0) {
        $sqlEstado = "UPDATE precios
                     SET status = ?, usuario = ?
                     WHERE id_precio = ? AND id_prod = ? AND tipo IN ('SVT', 'SVV')";
        $stmtEstado = $conn_mysql->prepare($sqlEstado);
        $id_almacen_int = intval($id_almacen);
        $stmtEstado->bind_param('iiii', $nuevo_estado, $idUser, $id_precio_servicio, $id_almacen_int);
        $stmtEstado->execute();

        $mensaje = $nuevo_estado === 1 ? 'Precio de servicio reactivado' : 'Precio de servicio desactivado';
        alert($mensaje, 1, 'V_almacen&id=' . $id_almacen);
        logActivity('PRECIO_SERVICIO', $mensaje . ' en almacén ' . $id_almacen);
        exit;
    }
}

// Extender vigencia de precio de servicio (solo zona SUR)
if (isset($_POST['extender_vigencia_servicio']) && $tipoZonaActual === 'SUR' && $id_almacen) {
    $id_precio_servicio = intval($_POST['id_precio_servicio'] ?? 0);
    $dias_extension_servicio = intval($_POST['dias_extension_servicio'] ?? 0);

    if ($id_precio_servicio <= 0 || $dias_extension_servicio <= 0) {
        alert('Datos inválidos para extender vigencia', 2, 'V_almacen&id=' . $id_almacen);
        exit;
    }

    $sqlExtender = "UPDATE precios
                   SET fecha_fin = DATE_ADD(fecha_fin, INTERVAL ? DAY), usuario = ?
                   WHERE id_precio = ? AND id_prod = ? AND tipo IN ('SVT', 'SVV')";
    $stmtExtender = $conn_mysql->prepare($sqlExtender);
    $id_almacen_int = intval($id_almacen);
    $stmtExtender->bind_param('iiii', $dias_extension_servicio, $idUser, $id_precio_servicio, $id_almacen_int);
    $stmtExtender->execute();

    if ($stmtExtender->affected_rows > 0) {
        alert('Vigencia extendida con éxito', 1, 'V_almacen&id=' . $id_almacen);
        logActivity('PRECIO_SERVICIO', 'Extendió vigencia de precio de servicio en almacén ' . $id_almacen . ' por ' . $dias_extension_servicio . ' días');
    } else {
        alert('No fue posible extender la vigencia', 2, 'V_almacen&id=' . $id_almacen);
    }
    exit;
}

// Consultar precios de servicio del almacén (solo zona SUR)
if ($tipoZonaActual === 'SUR' && $id_almacen) {
    $sqlPreciosServicio = "SELECT p.id_precio, p.precio, p.tipo, p.origen, p.destino, p.conmin,
                                  p.fecha_ini, p.fecha_fin, p.status,
                                  o.cod_al as cod_origen, o.noma as nom_origen,
                                  d.cod_al as cod_destino, d.noma as nom_destino,
                                  c.cod as cod_cliente, c.nombre as nombre_cliente
                           FROM precios p
                           INNER JOIN direcciones o ON p.origen = o.id_direc
                           INNER JOIN direcciones d ON p.destino = d.id_direc
                           LEFT JOIN clientes c ON d.id_us = c.id_cli
                           WHERE p.id_prod = ?
                             AND p.tipo IN ('SVT', 'SVV')
                             AND o.id_alma = ?
                           ORDER BY p.status DESC, p.fecha_ini DESC";

    $stmtPreciosServicio = $conn_mysql->prepare($sqlPreciosServicio);
    $id_almacen_int = intval($id_almacen);
    $stmtPreciosServicio->bind_param('ii', $id_almacen_int, $id_almacen_int);
    $stmtPreciosServicio->execute();
    $resultPreciosServicio = $stmtPreciosServicio->get_result();
    if ($resultPreciosServicio) {
        $precios_servicio = $resultPreciosServicio->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<div class="container mt-2">
    <!-- Tarjeta principal del almacén -->
    <div class="card mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Datos del almacén</h5>
                <span class="small">Código: <?= htmlspecialchars($almacen['cod'] ?? 'N/A') ?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="?p=almacenes" class="btn btn-sm rounded-3 btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
                <a href="?p=E_almacen&id=<?= $id_almacen ?>" class="btn btn-sm rounded-3 btn-light" <?= $perm['Alma_Editar'] ?? '';?>>
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Columna izquierda - Información Básica -->
                <div class="col-md-6">
                    <div class="card border-1 mb-3 h-100" style="background-color: var(--color-acento);">
                        <div class="card-body">
                            <h6 class="text-uppercase text-primary small fw-bold mb-3">
                                <i class="bi bi-info-circle me-1"></i> Información Básica
                            </h6>
                            <div class="mb-3">
                                <p class="text-muted small mb-1">Almacén</p>
                                <h5 class="fw-bold"><?= htmlspecialchars($almacen['nombre'] ?? 'N/A') ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted small mb-1">RFC</p>
                                <h5 class="mb-0"><?= htmlspecialchars($almacen['rfc'] ?? 'N/A') ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted small mb-1">Razón Social</p>
                                <p class="mb-0"><?= htmlspecialchars($almacen['rs'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted small mb-1">Observaciones</p>
                                <p class="mb-0"><?= htmlspecialchars($almacen['obs'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha - Detalles Adicionales -->
                <div class="col-md-6">
                    <div class="card border-1 mb-3 h-100" style="background-color: var(--color-acento);">
                        <div class="card-body">
                            <h6 class="text-uppercase text-primary small fw-bold mb-3">
                                <i class="bi bi-card-checklist me-1"></i> Detalles Adicionales
                            </h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                        <p class="text-muted small mb-1">Tipo de Persona</p>
                                        <span class="d-block fw-bold">
                                            <?= ($almacen['tpersona'] ?? '') == 'fisica' ? 'Persona Física' : 'Persona Moral' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded <?= ($almacen['status'] ?? 0) == 1 ? 'bg-success' : 'bg-secondary' ?> text-white h-100">
                                        <p class="small mb-1">Estado</p>
                                        <span class="fw-bold">
                                            <?= ($almacen['status'] ?? 0) == 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                        <p class="text-muted small mb-1">Tipo de evidencia</p>
                                        <p class="mb-0">
                                            <?= ($almacen['fac_rem'] ?? '') == 'FAC' ? 'Factura' : 'Remisión' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                        <p class="text-muted small mb-1">Zona</p>
                                        <span class="d-block fw-bold"><?= $zon1['nom'] ?? 'N/A' ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de direcciones del almacén -->
<!-- Sección de direcciones del almacén -->
<div class="card-header border-bottom-0 mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Direcciones Registradas</h5>
        <a href="?p=N_direccion_almacen&id=<?= $id_almacen ?>" class="btn btn-sm rounded-3 btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nueva Dirección
        </a>
    </div>
</div>
<div class="card-body">
    <?php if (empty($direcciones)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> Este almacén no tiene direcciones registradas.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm" id="miTabla" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <?php if (esZonaMEOCompatible($tipoZonaActual)): ?>
                        <th>Dirección</th>
                        <?php endif; ?>
                        <th>Observación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($direcciones as $index => $direccion): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= $direccion['cod_al'] ?></strong>
                                <?php if (esZonaMEOCompatible($tipoZonaActual) && !empty($direccion['c_postal'])): ?>
                                <div class="small text-muted">
                                    CP: <?= $direccion['c_postal'] ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $direccion['noma'] ?></td>
                            <td>
                                <div class="small">
                                    <strong>Atención:</strong> <?= $direccion['atencion'] ?><br>
                                    <strong>Tel:</strong> <?= $direccion['tel'] ?><br>
                                    <strong>Email:</strong> <?= $direccion['email'] ?>
                                </div>
                            </td>
                            <?php if (esZonaMEOCompatible($tipoZonaActual)): ?>
                            <td>
                                <?php if (!empty($direccion['calle']) || !empty($direccion['colonia'])): ?>
                                <div class="small">
                                    <?php if (!empty($direccion['calle'])): ?>
                                        <strong>Calle:</strong> <?= $direccion['calle'] ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($direccion['numext'])): ?>
                                        <strong>No. Ext:</strong> <?= $direccion['numext'] ?>
                                        <?php if (!empty($direccion['numint'])): ?>
                                            <strong>Int:</strong> <?= $direccion['numint'] ?><br>
                                        <?php else: ?><br><?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($direccion['colonia'])): ?>
                                        <strong>Colonia:</strong> <?= $direccion['colonia'] ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($direccion['estado'])): ?>
                                        <strong>Estado:</strong> <?= $direccion['estado'] ?><br>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sin dirección registrada</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($direccion['obs']) ?></td>
                            <td class="text-center">
                                <div class="btn-group justify-content-center"> 
                                    <a href="?p=E_direccion_almacen&id=<?= $direccion['id_direc'] ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button <?= $perm['ACT_DES'] ?? '';?> class="btn btn-sm btn-outline-danger eliminar-direccion" 
                                            data-id="<?= $direccion['id_direc'] ?>" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($tipoZonaActual === 'SUR'): ?>
<div class="card mb-4">
    <div class="card-header border-bottom-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Precios de Servicio de Almacenaje (SUR)</h5>
            <button <?= $perm['sub_precios'];?> type="button" class="btn btn-sm rounded-3 btn-info" data-bs-toggle="modal" data-bs-target="#modalPrecioServicio">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Precio Servicio
            </button>
        </div>
        <small class="text-muted">Tipos: SVT (por tonelada) y SVV (por viaje). Origen: bodega del almacén, destino: bodega del cliente.</small>
    </div>
    <div class="card-body">
        <?php if (empty($precios_servicio)): ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i> No hay precios de servicio registrados para este almacén.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm" id="miTablaServicios" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Precio</th>
                            <th>Ruta</th>
                            <th>Peso mín.</th>
                            <th>Vigencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($precios_servicio as $precioSrv): ?>
                            <?php
                                $tipoTexto = $precioSrv['tipo'] === 'SVT' ? 'Por tonelada' : 'Por viaje';
                                $estadoVigente = (strtotime(date('Y-m-d')) >= strtotime(date('Y-m-d', strtotime($precioSrv['fecha_ini']))))
                                    && (strtotime(date('Y-m-d')) <= strtotime(date('Y-m-d', strtotime($precioSrv['fecha_fin']))));
                                $estadoActivo = intval($precioSrv['status']) === 1;
                            ?>
                            <tr class="<?= !$estadoActivo ? 'table-danger' : (!$estadoVigente ? 'table-warning' : '') ?>">
                                <td>
                                    <span class="badge <?= $precioSrv['tipo'] === 'SVT' ? 'bg-primary' : 'bg-success' ?>"><?= htmlspecialchars($precioSrv['tipo']) ?></span>
                                    <div class="small text-muted"><?= $tipoTexto ?></div>
                                </td>
                                <td><strong>$<?= number_format((float)$precioSrv['precio'], 2) ?></strong></td>
                                <td>
                                    <div class="small"><strong>Origen:</strong> <?= htmlspecialchars($precioSrv['cod_origen'] . ' - ' . $precioSrv['nom_origen']) ?></div>
                                    <div class="small"><strong>Destino:</strong> <?= htmlspecialchars($precioSrv['cod_destino'] . ' - ' . $precioSrv['nom_destino']) ?></div>
                                    <?php if (!empty($precioSrv['cod_cliente'])): ?>
                                        <div class="small text-muted">Cliente: <?= htmlspecialchars($precioSrv['cod_cliente'] . ' - ' . $precioSrv['nombre_cliente']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= (float)$precioSrv['conmin'] > 0 ? number_format((float)$precioSrv['conmin'], 2) . ' ton' : 'N/A' ?></td>
                                <td>
                                    <div class="small">
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($precioSrv['fecha_ini']))) ?> -
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($precioSrv['fecha_fin']))) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!$estadoActivo): ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php elseif (!$estadoVigente): ?>
                                        <span class="badge bg-warning text-dark">No vigente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Vigente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button <?= $perm['sub_precios'];?> type="button"
                                                class="btn btn-outline-primary btn-extender-servicio"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalExtenderVigenciaServicio"
                                                data-precio-id="<?= (int)$precioSrv['id_precio'] ?>"
                                                data-precio-valor="$<?= number_format((float)$precioSrv['precio'], 2) ?>"
                                                data-fecha-actual="<?= htmlspecialchars(date('Y-m-d', strtotime($precioSrv['fecha_fin']))) ?>"
                                                title="Extender vigencia">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="id_precio_servicio" value="<?= (int)$precioSrv['id_precio'] ?>">
                                            <?php if ($estadoActivo): ?>
                                                <input type="hidden" name="nuevo_estado" value="0">
                                                <button <?= $perm['sub_precios'];?> type="submit" name="cambiar_estado_precio_servicio" class="btn btn-outline-danger" title="Desactivar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="nuevo_estado" value="1">
                                                <button type="submit" name="cambiar_estado_precio_servicio" class="btn btn-outline-success" title="Reactivar">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalPrecioServicio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header text-bg-info">
                    <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Nuevo Precio de Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de servicio</label>
                            <select class="form-select" name="tipo_servicio" required>
                                <option value="SVT">SVT - Por tonelada</option>
                                <option value="SVV">SVV - Por viaje</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio $</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="precio_servicio" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Peso mínimo (ton)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="peso_minimo_servicio" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Origen (bodega del almacén)</label>
                            <select class="form-select" name="origen_servicio" required>
                                <option value="">Seleccione origen...</option>
                                <?php foreach ($direcciones as $dirAlm): ?>
                                    <option value="<?= (int)$dirAlm['id_direc'] ?>">
                                        <?= htmlspecialchars(($dirAlm['cod_al'] ?? '') . ' - ' . ($dirAlm['noma'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destino (bodega del cliente)</label>
                            <select class="form-select" name="destino_servicio" required>
                                <option value="">Seleccione destino...</option>
                                <?php foreach ($bodegas_cliente as $dirCli): ?>
                                    <option value="<?= (int)$dirCli['id_direc'] ?>">
                                        <?= htmlspecialchars(($dirCli['cod_cliente'] ?? '') . ' - ' . ($dirCli['nombre_cliente'] ?? '') . ' / ' . ($dirCli['cod_al'] ?? '') . ' - ' . ($dirCli['noma'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha inicio</label>
                            <input type="date" class="form-control" name="fecha_ini_servicio" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha fin</label>
                            <input type="date" class="form-control" name="fecha_fin_servicio" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_precio_servicio" class="btn btn-info">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExtenderVigenciaServicio" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Extender vigencia del precio de servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Precio: <span id="precio-servicio-valor" class="fw-bold"></span></p>
                    <p class="small text-muted">Fecha actual de fin: <span id="fecha-servicio-actual" class="fw-semibold"></span></p>

                    <div class="mb-3">
                        <label for="dias_extension_servicio" class="form-label">Días a extender:</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtensionServicio(7)">7 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtensionServicio(15)">15 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtensionServicio(30)">30 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtensionServicio(60)">60 días</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDiasExtensionServicio(90)">90 días</button>
                        </div>
                        <input type="number" class="form-control mt-2" id="dias_extension_servicio" name="dias_extension_servicio" min="1" max="365" value="30" required>
                        <div class="form-text">Nueva fecha de fin: <span id="nueva-fecha-servicio" class="fw-semibold text-success"></span></div>
                    </div>

                    <input type="hidden" name="id_precio_servicio" id="id-precio-servicio-extender">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="extender_vigencia_servicio" class="btn btn-primary">Extender vigencia</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar dirección -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirmar eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta dirección? Esta acción no se puede deshacer.</p>
                    <input type="hidden" id="direccionId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#miTabla').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
                },
                "responsive": true
            });

            if ($('#miTablaServicios').length) {
                $('#miTablaServicios').DataTable({
                    "language": {
                        "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
                    },
                    "responsive": true,
                    "order": [[4, 'desc']]
                });
            }

            const modalExtenderServicio = document.getElementById('modalExtenderVigenciaServicio');
            if (modalExtenderServicio) {
                modalExtenderServicio.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const precioId = button.getAttribute('data-precio-id');
                    const precioValor = button.getAttribute('data-precio-valor');
                    const fechaActual = button.getAttribute('data-fecha-actual');

                    document.getElementById('id-precio-servicio-extender').value = precioId;
                    document.getElementById('precio-servicio-valor').textContent = precioValor;
                    document.getElementById('fecha-servicio-actual').textContent = fechaActual;
                    calcularNuevaFechaServicio();
                });
            }

            const inputDiasServicio = document.getElementById('dias_extension_servicio');
            if (inputDiasServicio) {
                inputDiasServicio.addEventListener('input', calcularNuevaFechaServicio);
            }
        });

        function setDiasExtensionServicio(dias) {
            const input = document.getElementById('dias_extension_servicio');
            if (!input) return;
            input.value = dias;
            calcularNuevaFechaServicio();
        }

        function calcularNuevaFechaServicio() {
            const diasInput = document.getElementById('dias_extension_servicio');
            const fechaActualElem = document.getElementById('fecha-servicio-actual');
            const nuevaFechaElem = document.getElementById('nueva-fecha-servicio');

            if (!diasInput || !fechaActualElem || !nuevaFechaElem) return;

            const dias = parseInt(diasInput.value, 10) || 0;
            const fechaActual = fechaActualElem.textContent;

            if (!fechaActual || dias <= 0) {
                nuevaFechaElem.textContent = '';
                return;
            }

            const fecha = new Date(fechaActual + 'T00:00:00');
            if (Number.isNaN(fecha.getTime())) {
                nuevaFechaElem.textContent = '';
                return;
            }

            fecha.setDate(fecha.getDate() + dias);
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            nuevaFechaElem.textContent = `${year}-${month}-${day}`;
        }
    </script>
    
    <script>
        $(document).ready(function() {
            // Configurar modal para eliminar dirección
            $(document).on('click', '.eliminar-direccion', function() {
                const id = $(this).data('id');
                $('#direccionId').val(id);
                $('#confirmDeleteModal').modal('show');
            });

            // Confirmar eliminación
            $('#confirmDeleteBtn').click(function() {
                const id = $('#direccionId').val();

                $.post('mod/eliminar_direccion_almacen.php', {
                    id: id
                }, function(response) {
                    if (response.success) {
                        // Mostrar notificación de éxito
                        const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                        $('#toastMessage').html('<i class="bi bi-check-circle-fill text-success me-2"></i> Dirección eliminada correctamente');
                        toast.show();
                        
                        // Recargar después de 1.5 segundos
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Mostrar notificación de error
                        const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                        $('#toastMessage').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error: ' + response.message);
                        toast.show();
                    }
                }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error en la solicitud: ' + textStatus);
                    toast.show();
                });

                $('#confirmDeleteModal').modal('hide');
            });
        });
    </script>

    <!-- Toast para notificaciones -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-white">
                <strong class="me-auto">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>
</div>