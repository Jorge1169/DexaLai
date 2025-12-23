<?php
// Título de la página
$titulo_pagina = "Dashboard";

// Obtener estadísticas generales (código igual)
$stats_query = "
SELECT 
(SELECT COUNT(*) FROM proveedores WHERE status = 1" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS proveedores_activos,
(SELECT COUNT(*) FROM clientes WHERE status = 1" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS clientes_activos,
(SELECT COUNT(*) FROM transportes WHERE status = 1" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS transportes_activos,
(SELECT COUNT(*) FROM productos WHERE status = 1" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS productos_activos,

(SELECT COUNT(*) FROM recoleccion WHERE status = 1 
 AND MONTH(fecha_r) = MONTH(CURRENT_DATE()) 
 AND YEAR(fecha_r) = YEAR(CURRENT_DATE())
" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS recolecciones_mes,

(SELECT COUNT(*) FROM recoleccion WHERE status = 1 
 AND remision IS NOT NULL 
 AND factura_fle IS NOT NULL 
 AND MONTH(fecha_r) = MONTH(CURRENT_DATE()) 
 AND YEAR(fecha_r) = YEAR(CURRENT_DATE())
" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS recolecciones_completas_mes,

(SELECT COUNT(*) FROM recoleccion WHERE status = 1 
 AND (remision IS NULL OR factura_fle IS NULL)
" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : "") . ") AS recolecciones_pendientes
";

$stats = $conn_mysql->query($stats_query)->fetch_assoc();

// AVISOS IMPORTANTES (código igual)
$precios_caducando = $conn_mysql->query("SELECT COUNT(*) as total FROM precios WHERE status = 1 AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)" . ($zona_seleccionada != '0' ? " AND id_prod IN (SELECT id_prod FROM productos WHERE zona = '$zona_seleccionada')" : ""));
$aviso_precios = $precios_caducando->fetch_assoc()['total'] ?? 0;

$productos_sin_precio = $conn_mysql->query("SELECT COUNT(DISTINCT p.id_prod) as total FROM productos p LEFT JOIN precios pr ON p.id_prod = pr.id_prod AND pr.status = 1 AND pr.fecha_ini <= CURDATE() AND (pr.fecha_fin >= CURDATE() OR pr.fecha_fin IS NULL) WHERE p.status = 1 AND pr.id_precio IS NULL" . ($zona_seleccionada != '0' ? " AND p.zona = '$zona_seleccionada'" : ""));
$aviso_sin_precio = $productos_sin_precio->fetch_assoc()['total'] ?? 0;

$fleteros_sin_correo = $conn_mysql->query("SELECT COUNT(*) as total FROM transportes WHERE status = 1 AND (correo IS NULL OR correo = '' OR correo = '0')" . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : ""));
$aviso_fleteros = $fleteros_sin_correo->fetch_assoc()['total'] ?? 0;

// Obtener nombre de zona si está filtrada
$nombre_zona = '';
if($zona_seleccionada != '0') {
    $zona_query = $conn_mysql->query("SELECT nom FROM zonas WHERE id_zone = '$zona_seleccionada'");
    $zona_data = mysqli_fetch_array($zona_query);
    $nombre_zona = $zona_data['nom'] ?? '';
}

// Obtener últimas recolecciones (código igual) 
$ultimasRecolecciones = $conn_mysql->query("
    SELECT r.id_recol, r.folio, r.fecha_r, r.factura_v, 
    p.rs AS proveedor, c.nombre AS cliente, t.razon_so AS fletero,
    pr.nom_pro AS producto, z.cod AS cod_zona,
    r.remision, r.factura_fle, r.status
    FROM recoleccion r
    LEFT JOIN proveedores p ON r.id_prov = p.id_prov
    LEFT JOIN clientes c ON r.id_cli = c.id_cli
    LEFT JOIN transportes t ON r.id_transp = t.id_transp
    LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
    LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
    LEFT JOIN zonas z ON r.zona = z.id_zone
    WHERE r.status = '1'" . ($zona_seleccionada != '0' ? " AND r.zona = '$zona_seleccionada'" : "") . "
    ORDER BY r.fecha_r DESC, r.id_recol DESC LIMIT 5");


// CONSULTA PARA RECOLECCIONES CON FACTURA FLETE RECHAZADA
$recolecciones_rechazadas = $conn_mysql->query("
    SELECT 
        r.id_recol,
        r.folio,
        r.fecha_r,
        z.cod AS cod_zona,
        p.rs AS proveedor,
        t.razon_so AS fletero,
        r.FacFexis AS contador_rechazos
    FROM recoleccion r
    LEFT JOIN zonas z ON r.zona = z.id_zone
    LEFT JOIN proveedores p ON r.id_prov = p.id_prov
    LEFT JOIN transportes t ON r.id_transp = t.id_transp
    WHERE r.status = '1' 
    AND r.factura_fle IS NULL 
    AND r.FacFexis > 0
    " . ($zona_seleccionada != '0' ? " AND r.zona = '$zona_seleccionada'" : "") . "
    ORDER BY r.FacFexis DESC, r.fecha_r DESC
    LIMIT 10
");

$total_rechazadas = $recolecciones_rechazadas->num_rows;
?>
<div class="container py-3">
    
    <!-- HEADER CON TÍTULO Y ZONA -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Panel de Control</h1>
                    <?php if($nombre_zona): ?>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle me-2">
                                <i class="bi bi-geo-alt me-1"></i> Zona: <?= htmlspecialchars($nombre_zona) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small"><?= date('d/m/Y') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS PRINCIPALES -->
    <div class="row mb-4 g-3">
        <?php 
        $metricas_principales = [
            [
                'link' => 'proveedores', 
                'titulo' => 'Proveedores', 
                'valor' => $stats['proveedores_activos'], 
                'icono' => 'building', 
                'color' => 'primary',
                'desc' => 'Activos en sistema'
            ],
            [
                'link' => 'clientes', 
                'titulo' => 'Clientes', 
                'valor' => $stats['clientes_activos'], 
                'icono' => 'people-fill', 
                'color' => 'success',
                'desc' => 'Activos en sistema'
            ],
            [
                'link' => 'recoleccion', 
                'titulo' => 'Recolecciones', 
                'valor' => $stats['recolecciones_mes'], 
                'icono' => 'truck', 
                'color' => 'warning',
                'desc' => 'Este mes'
            ],
            [
                'link' => 'productos', 
                'titulo' => 'Productos', 
                'valor' => $stats['productos_activos'], 
                'icono' => 'box-seam', 
                'color' => 'info',
                'desc' => 'Activos en sistema'
            ],
        ];
        ?>

        <?php foreach ($metricas_principales as $metrica): ?>
        <div class="col-xl-3 col-md-6">
            <a href="?p=<?= $metrica['link'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2"><?= $metrica['titulo'] ?></h6>
                                <h2 class="mb-1 text-<?= $metrica['color'] ?>"><?= $metrica['valor'] ?></h2>
                                <p class="text-muted small mb-0"><?= $metrica['desc'] ?></p>
                            </div>
                            <div class="bg-<?= $metrica['color'] ?>-subtle p-3 rounded-circle">
                                <i class="bi bi-<?= $metrica['icono'] ?> text-<?= $metrica['color'] ?> fs-4"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="small text-muted">
                                <i class="bi bi-arrow-right-circle me-1"></i> Ver detalles
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<!-- ALERTAS Y NOTIFICACIONES -->
    <div class="row mb-4">
        <div class="col-12">
            <?php if ($aviso_precios > 0 || $aviso_sin_precio > 0 || $aviso_fleteros > 0): ?>
            <div class="card border-warning-subtle shadow-sm">
                <div class="card-header bg-warning-subtle border-0 py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                        <h5 class="mb-0">Alertas del Sistema</h5>
                        <span class="badge bg-warning ms-2">
                            <?= ($aviso_precios + $aviso_sin_precio + $aviso_fleteros) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if ($aviso_precios > 0): ?>
                        <div class="col-md-4">
                            <a href="?p=productos&filtro=caducando" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border border-warning-subtle hover-lift">
                                    <div class="bg-warning-subtle p-2 rounded me-3">
                                        <i class="bi bi-calendar-x text-warning fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-warning mb-1">Precios por Caducar</h6>
                                        <p class="text-muted small mb-0"><?= $aviso_precios ?> productos</p>
                                    </div>
                                    <i class="bi bi-chevron-right text-warning"></i>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($aviso_sin_precio > 0): ?>
                        <div class="col-md-4">
                            <a href="?p=productos&filtro=sinprecio" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border border-danger-subtle hover-lift">
                                    <div class="bg-danger-subtle p-2 rounded me-3">
                                        <i class="bi bi-tag text-danger fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-danger mb-1">Sin Precio Actual</h6>
                                        <p class="text-muted small mb-0"><?= $aviso_sin_precio ?> productos</p>
                                    </div>
                                    <i class="bi bi-chevron-right text-danger"></i>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($aviso_fleteros > 0): ?>
                        <div class="col-md-4">
                            <a href="?p=transportes&filtro=sincorreo" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border border-info-subtle hover-lift">
                                    <div class="bg-info-subtle p-2 rounded me-3">
                                        <i class="bi bi-envelope text-info fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-info mb-1">Fleteros sin Correo</h6>
                                        <p class="text-muted small mb-0"><?= $aviso_fleteros ?> transportes</p>
                                    </div>
                                    <i class="bi bi-chevron-right text-info"></i>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FACTURAS RECHAZADAS -->
    <?php if ($total_rechazadas > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger-subtle shadow-sm">
                <div class="card-header bg-danger-subtle border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-octagon-fill text-danger me-2 fs-5"></i>
                            <h5 class="mb-0 text-danger-emphasis">Facturas Rechazadas</h5>
                            <span class="badge bg-danger ms-2"><?= $total_rechazadas ?></span>
                        </div>
                        <small class="text-danger-emphasis">Requieren atención inmediata</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-danger-subtle">
                                <tr>
                                    <th class="ps-4" width="120">Folio</th>
                                    <th width="120">Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Fletero</th>
                                    <th width="120" class="text-center">Rechazos</th>
                                    <th width="100" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($rechazada = $recolecciones_rechazadas->fetch_assoc()): 
                                    $folio_completo = $rechazada['cod_zona'] . "-" . date('ym', strtotime($rechazada['fecha_r'])) . str_pad($rechazada['folio'], 4, '0', STR_PAD_LEFT);
                                    $fecha_formateada = date('d/m/Y', strtotime($rechazada['fecha_r']));
                                ?>
                                <tr class="hover-lift">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger-subtle p-2 rounded me-3">
                                                <i class="bi bi-file-earmark-x text-danger"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($folio_completo) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= $fecha_formateada ?></span>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($rechazada['proveedor']) ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($rechazada['fletero']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger rounded-pill px-3 py-2">
                                            <i class="bi bi-x-circle me-1"></i>
                                            <?= $rechazada['contador_rechazos'] ?> 
                                            <?= $rechazada['contador_rechazos'] == 1 ? 'vez' : 'veces' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="?p=V_recoleccion&id=<?= $rechazada['id_recol'] ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-danger"
                                           title="Corregir recolección">
                                           <i class="bi bi-pencil-square me-1"></i> Corregir
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-danger-subtle border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Estas facturas fueron rechazadas en sistema INVOICE
                        </small>
                        <a href="?p=recoleccion&estado=rechazadas" class="btn btn-outline-danger btn-sm">
                            Ver todas <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- SECCIÓN DE ESTADO DE RECOLECCIONES -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clipboard-data me-2 text-primary"></i>Estado de Recolecciones</h5>
                        <a href="?p=N_recoleccion" class="btn btn-primary" target="_blank" <?= $perm['Recole_Crear'];?>>
                            <i class="bi bi-plus-circle me-2"></i>Nueva Recolección
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="?p=recoleccion&estado=completas" class="text-decoration-none">
                                <div class="card border border-success-subtle bg-success-subtle hover-lift">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                        </div>
                                        <h3 class="text-success mb-2"><?= $stats['recolecciones_completas_mes'] ?></h3>
                                        <p class="text-success-emphasis mb-0">Completadas (Mes)</p>
                                        <small class="text-muted">Remisión y factura completas</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="?p=recoleccion&estado=pendientes" class="text-decoration-none">
                                <div class="card border border-warning-subtle bg-warning-subtle hover-lift">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="bi bi-clock-fill text-warning fs-1"></i>
                                        </div>
                                        <h3 class="text-warning mb-2"><?= $stats['recolecciones_pendientes'] ?></h3>
                                        <p class="text-warning-emphasis mb-0">Pendientes</p>
                                        <small class="text-muted">Faltan documentos por subir</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="?p=transportes" class="text-decoration-none">
                                <div class="card border border-info-subtle bg-info-subtle hover-lift">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="bi bi-truck text-info fs-1"></i>
                                        </div>
                                        <h3 class="text-info mb-2"><?= $stats['transportes_activos'] ?></h3>
                                        <p class="text-info-emphasis mb-0">Fleteros Activos</p>
                                        <small class="text-muted">Transportes en sistema</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORTE FINANCIERO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Reporte Financiero <?= $nombre_zona ? " - $nombre_zona" : "" ?></h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text border-end-0">
                                        <i class="bi bi-calendar text-muted"></i>
                                    </span>
                                    <input id="dt_mvI" type="date" class="form-control border-start-0" 
                                           name="dt_mvI" value="<?= date('Y-m-01') ?>" 
                                           max="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text border-end-0">
                                        <i class="bi bi-calendar text-muted"></i>
                                    </span>
                                    <input id="dt_mvF" type="date" class="form-control border-start-0" 
                                           name="dt_mvF" value="<?= date('Y-m-d') ?>" 
                                           max="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <input type="hidden" name="zona" id="zona" value="<?= $zona_seleccionada ?>">
                                
                                <button class="btn btn-primary px-4" onclick="pre1()">
                                    <i class="bi bi-search me-2"></i> Generar Reporte
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona el período a consultar
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESULTADO DEL REPORTE -->
    <div class="row mb-4" id="res1">
        <!-- Contenido cargado via AJAX -->
    </div>

    <!-- ÚLTIMAS RECOLECCIONES -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas Recolecciones</h5>
                        <a href="?p=recoleccion" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-list me-1"></i> Ver todas
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Folio</th>
                                    <th>Proveedor</th>
                                    <th>Cliente</th>
                                    <th>Fletero</th>
                                    <th>Producto</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($recoleccion = $ultimasRecolecciones->fetch_assoc()): 
                                    $folio_completo = $recoleccion['cod_zona'] . "-" . date('ym', strtotime($recoleccion['fecha_r'])) . str_pad($recoleccion['folio'], 4, '0', STR_PAD_LEFT);
                                    $estado_completo = ($recoleccion['remision'] && $recoleccion['factura_fle']) ? 'Completa' : 'Pendiente';
                                    $color_estado = ($recoleccion['remision'] && $recoleccion['factura_fle']) ? 'success' : 'warning';
                                ?>
                                <tr class="hover-lift">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary-subtle p-2 rounded me-3">
                                                <i class="bi bi-clipboard-data text-primary"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($folio_completo) ?></strong><br>
                                                <small class="text-muted"><?= date('d/m/Y', strtotime($recoleccion['fecha_r'])) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span><?= htmlspecialchars($recoleccion['proveedor']) ?></span>
                                    </td>
                                    <td>
                                        <span><?= htmlspecialchars($recoleccion['cliente']) ?></span>
                                    </td>
                                    <td>
                                        <span><?= htmlspecialchars($recoleccion['fletero']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis">
                                            <?= htmlspecialchars($recoleccion['producto']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $color_estado ?> bg-opacity-10 text-<?= $color_estado ?>-emphasis border border-<?= $color_estado ?>-subtle">
                                            <?= $estado_completo ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="?p=V_recoleccion&id=<?= $recoleccion['id_recol'] ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver detalles">
                                           <i class="bi bi-eye me-1"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-0 py-3">
                    <div class="text-center">
                        <small class="text-muted">
                            Mostrando las 5 recolecciones más recientes
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Utilidades de Bootstrap 5 -->
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    
    .bg-purple-subtle {
        background-color: #e0d4f7 !important;
    }
    .bg-orange-subtle {
        background-color: #ffe5d0 !important;
    }
    .bg-teal-subtle {
        background-color: #d2f4ea !important;
    }
    .bg-indigo-subtle {
        background-color: #e0d4f7 !important;
    }
    
    .text-purple {
        color: #6f42c1 !important;
    }
    .text-orange {
        color: #fd7e14 !important;
    }
    .text-teal {
        color: #20c997 !important;
    }
    .text-indigo {
        color: #6610f2 !important;
    }
    
    .card {
        border-radius: 0.75rem;
    }
    
    .table td, .table th {
        vertical-align: middle;
    }
</style>

<script>
    function pre1(){
        dt_mvI = document.getElementById('dt_mvI').value;
        dt_mvF = document.getElementById('dt_mvF').value;
        zona = document.getElementById('zona').value;

        var parametros = {
            "dt_mvI" : dt_mvI,
            "dt_mvF" : dt_mvF,
            "zona" : zona
        };

        $.ajax({
            data: parametros,
            url: 'reporte_ini.php',
            type: 'POST',
            beforeSend: function() {
                $('#res1').html(`
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <div class="spinner-border text-primary mb-3" role="status"></div>
                                <h5 class="text-muted">Generando reporte...</h5>
                            </div>
                        </div>
                    </div>
                `);
            },
            success: function(mensaje) {
                $('#res1').html(mensaje);
            },
            error: function() {
                $('#res1').html(`
                    <div class="col-12">
                        <div class="card border-danger-subtle shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-exclamation-triangle text-danger fs-1 mb-3"></i>
                                <h5 class="text-danger">Error al cargar el reporte</h5>
                                <p class="text-muted">Intenta nuevamente</p>
                                <button onclick="pre1()" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                `);
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        pre1();
    });
</script>