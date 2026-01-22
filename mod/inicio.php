<?php
// Obtener tipo de zona actual
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql);

// ============================================================================
// ESCENARIO 1: SIN ZONA SELECCIONADA - Solo mostrar mensaje
// ============================================================================
if ($zona_seleccionada == '0' || empty($zona_seleccionada)) {
    // NO usar exit() - solo mostrar contenido específico
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-geo-alt display-1 text-primary"></i>
                        </div>
                        
                        <h2 class="fw-bold text-primary mb-3">Selecciona una Zona</h2>
                        
                        <p class="text-muted fs-5 mb-4">
                            Para acceder al dashboard, primero debes seleccionar la zona con la que trabajarás.
                        </p>
                        
                        <div class="alert alert-info border-0 bg-info-subtle mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                                <div>
                                    <p class="mb-1 fw-semibold">¿Cómo seleccionar una zona?</p>
                                    <p class="mb-0">Usa el selector de zona ubicado en la barra superior junto a la fecha.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="card border border-primary-subtle bg-primary-subtle h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="bi bi-truck text-primary fs-1 mb-3"></i>
                                        <h5 class="text-primary-emphasis">Zonas NOR</h5>
                                        <p class="text-muted small">Dashboard completo con métricas de recolección tradicional</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card border border-success-subtle bg-success-subtle h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="bi bi-box-seam text-success fs-1 mb-3"></i>
                                        <h5 class="text-success-emphasis">Zonas MEO</h5>
                                        <p class="text-muted small">Gestión de materiales especiales y operaciones</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5">
                            <button class="btn btn-primary btn-lg px-5" onclick="mostrarSelectorZona()">
                                <i class="bi bi-geo-alt me-2"></i> Seleccionar Zona Ahora
                            </button>
                        </div>
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                No podrás acceder al dashboard hasta que selecciones una zona
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function mostrarSelectorZona() {
        // Enfocar el selector de zona en la barra superior
        const zoneSelect = document.getElementById('zoneSelect');
        if (zoneSelect) {
            zoneSelect.focus();
            
            // Mostrar un tooltip o mensaje
            Swal.fire({
                icon: 'info',
                title: 'Selector de Zona',
                html: 'Por favor, selecciona una zona del menú desplegable ubicado junto a la fecha en la barra superior.',
                confirmButtonText: 'Entendido'
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Selector no disponible',
                text: 'El selector de zona no está disponible en este momento.',
                confirmButtonText: 'Entendido'
            });
        }
    }
    </script>
    <?php
    // NO usar exit() - dejar que se siga cargando el resto de la página
    // Solo terminar la ejecución de este archivo
    return;
}

// ============================================================================
// ESCENARIO 2: ZONA MEO SELECCIONADA - Mostrar dashboard MEO
// ============================================================================
if ($tipoZonaActual === 'MEO') {
    // Obtener nombre de la zona MEO seleccionada
    $zona_query = $conn_mysql->query("SELECT nom FROM zonas WHERE id_zone = '$zona_seleccionada'");
    $zona_data = mysqli_fetch_array($zona_query);
    $nombre_zona = $zona_data['nom'] ?? '';
    
    // Obtener estadísticas MEO para el dashboard
    $stats = obtenerEstadisticasMEO($conn_mysql, $zona_seleccionada);
    
    // Obtener últimas captaciones y ventas
    $ultimasCaptaciones = obtenerUltimasCaptaciones($conn_mysql, $zona_seleccionada, 5);
    $ultimasVentas = obtenerUltimasVentas($conn_mysql, $zona_seleccionada, 5);
    
    // Obtener resumen mensual
    $resumenMes = obtenerResumenMensualMEO($conn_mysql, $zona_seleccionada);
    
    ?>
    <div class="container py-4">
        <!-- HEADER -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow rounded-4">
                    <div class="card-header encabezado-col border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="fw-bold text-light mb-1">
                                    <i class="bi bi-box-seam me-2"></i>Dashboard - <?= htmlspecialchars($nombre_zona) ?>
                                </h2>
                                <p class="text-muted mb-0">
                                    <span class="badge bg-success me-2">
                                        <i class="bi bi-shield-check me-1"></i> Materiales Especiales y Operaciones
                                    </span>
                                    <!--<span class="badge bg-primary">
                                        <?php
                                        //$fmt = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, 'LLLL yyyy');
                                        //$fecha_es = $fmt->format(new DateTime());
                                        ?>
                                        <i class="bi bi-calendar me-1"></i> <?= htmlspecialchars(ucfirst($fecha_es)) ?>
                                    </span>-->
                                </p>
                            </div>
                            <div>
                                <small class="text- Actualizado">
                                    <i class="bi bi-clock me-1"></i> Actualizado: <?= date('H:i') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MÉTRICAS PRINCIPALES -->
        <div class="row mb-4 g-3">
            <?php 
            $metricas_principales = [
                [
                    'titulo' => 'Total Captaciones', 
                    'valor' => $stats['total_captaciones'], 
                    'icono' => 'inbox-fill', 
                    'color' => 'primary',
                    'desc' => 'Mes actual',
                    'link' => 'captacion'
                ],
                [
                    'titulo' => 'Total Ventas', 
                    'valor' => $stats['total_ventas'], 
                    'icono' => 'cart-check', 
                    'color' => 'success',
                    'desc' => 'Mes actual',
                    'link' => 'ventas'
                ],
                [
                    'titulo' => 'Material Captado', 
                    'valor' => number_format($stats['total_kilos_captados'], 2), 
                    'icono' => 'boxes', 
                    'color' => 'warning',
                    'desc' => 'Kilos totales',
                    'link' => 'captacion'
                ],
                [
                    'titulo' => 'Material Vendido', 
                    'valor' => number_format($stats['total_kilos_vendidos'], 2), 
                    'icono' => 'truck', 
                    'color' => 'info',
                    'desc' => 'Kilos totales',
                    'link' => 'ventas'
                ],
            ];
            ?>

            <?php foreach ($metricas_principales as $metrica): ?>
            <div class="col-xl-3 col-md-6">
                <a href="?p=<?= $metrica['link'] ?>" class="text-decoration-none">
                    <div class="card border-0 shadow h-100 hover-lift">
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

        <!-- RESUMEN FINANCIERO -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 py-3">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Resumen Financiero MEO</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="?p=captacion" class="text-decoration-none">
                                    <div class="card border border-primary-subtle bg-primary-subtle hover-lift">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="bi bi-cash-coin text-primary fs-1"></i>
                                            </div>
                                            <h3 class="text-primary mb-2">$<?= number_format($stats['costo_total_captaciones'], 2) ?></h3>
                                            <p class="text-primary-emphasis mb-0">Costo Captaciones</p>
                                            <small class="text-muted">Inversión en materiales</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <a href="?p=ventas" class="text-decoration-none">
                                    <div class="card border border-success-subtle bg-success-subtle hover-lift">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="bi bi-currency-dollar text-success fs-1"></i>
                                            </div>
                                            <h3 class="text-success mb-2">$<?= number_format($stats['ingreso_total_ventas'], 2) ?></h3>
                                            <p class="text-success-emphasis mb-0">Ingreso Ventas</p>
                                            <small class="text-muted">Ventas totales</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card border border-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-subtle bg-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-subtle hover-lift">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="bi bi-graph-up-arrow text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?> fs-1"></i>
                                        </div>
                                        <h3 class="text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?> mb-2">
                                            $<?= number_format($stats['utilidad_neta'], 2) ?>
                                        </h3>
                                        <p class="text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-emphasis mb-0">Utilidad Neta</p>
                                        <small class="text-muted">
                                            <?= $stats['utilidad_neta'] >= 0 ? 'Ganancia' : 'Pérdida' ?> del mes
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Costo por Kilo</small>
                                        <h6 class="mb-0">$<?= number_format($stats['costo_por_kilo'], 4) ?></h6>
                                    </div>
                                    <i class="bi bi-coin text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Precio Venta por Kilo</small>
                                        <h6 class="mb-0">$<?= number_format($stats['precio_venta_por_kilo'], 4) ?></h6>
                                    </div>
                                    <i class="bi bi-tag text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Margen por Kilo</small>
                                        <h6 class="mb-0 text-<?= $stats['margen_por_kilo'] >= 0 ? 'success' : 'danger' ?>">
                                            $<?= number_format($stats['margen_por_kilo'], 4) ?>
                                        </h6>
                                    </div>
                                    <i class="bi bi-percent text-<?= $stats['margen_por_kilo'] >= 0 ? 'success' : 'danger' ?> fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE ACTIVIDAD RECIENTE -->
        <div class="row mb-4">
            <!-- ÚLTIMAS CAPTACIONES -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-inbox me-2 text-primary"></i>Últimas Captaciones</h5>
                            <a href="?p=captacion" class="btn btn-outline-primary btn-sm">
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
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Kilos</th>
                                        <th>Costo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ultimasCaptaciones)): ?>
                                        <?php foreach ($ultimasCaptaciones as $captacion): ?>
                                        <tr class="hover-lift">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary-subtle p-2 rounded me-3">
                                                        <i class="bi bi-inbox text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($captacion['folio']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= $captacion['fecha'] ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($captacion['proveedor']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis">
                                                    <?= number_format($captacion['kilos'], 2) ?> kg
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-semibold">
                                                    $<?= number_format($captacion['costo'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?p=V_captacion&id=<?= $captacion['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Ver detalles" target="_blank">
                                                   <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-6 opacity-25"></i>
                                                    <p class="mt-2">No hay captaciones recientes</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÚLTIMAS VENTAS -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-cart me-2 text-success"></i>Últimas Ventas</h5>
                            <a href="?p=ventas" class="btn btn-outline-success btn-sm">
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
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Kilos</th>
                                        <th>Total</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ultimasVentas)): ?>
                                        <?php foreach ($ultimasVentas as $venta): ?>
                                        <tr class="hover-lift">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success-subtle p-2 rounded me-3">
                                                        <i class="bi bi-cart-check text-success"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($venta['folio']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= $venta['fecha'] ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($venta['cliente']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info-emphasis">
                                                    <?= number_format($venta['kilos'], 2) ?> kg
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-semibold">
                                                    $<?= number_format($venta['total'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?p=V_venta&id=<?= $venta['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Ver detalles" target="_blank">
                                                   <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-cart display-6 opacity-25"></i>
                                                    <p class="mt-2">No hay ventas recientes</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN MENSUAL -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 py-3">
                        <h5 class="mb-0"><i class="bi bi-calendar2-month me-2 text-primary"></i>Resumen del Mes</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-primary-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-down-circle text-primary fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Entradas</h5>
                                        <p class="text-muted mb-0">
                                            <strong><?= $resumenMes['entradas_captaciones'] ?></strong> captaciones
                                            <br>
                                            <span class="text-success">$<?= number_format($resumenMes['costo_entradas'], 2) ?></span> invertido
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-success-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-up-circle text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Salidas</h5>
                                        <p class="text-muted mb-0">
                                            <strong><?= $resumenMes['salidas_ventas'] ?></strong> ventas
                                            <br>
                                            <span class="text-success">$<?= number_format($resumenMes['ingreso_salidas'], 2) ?></span> generado
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?>-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-left-right text-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?> fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Balance</h5>
                                        <p class="text-muted mb-0">
                                            <strong class="text-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?>">
                                                $<?= number_format($resumenMes['balance'], 2) ?>
                                            </strong> neto
                                            <br>
                                            <small>Ingresos - Costos</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-primary"><?= $resumenMes['promedio_captaciones_dia'] ?></div>
                                    <small class="text-muted">Captaciones/día</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-success"><?= $resumenMes['promedio_ventas_dia'] ?></div>
                                    <small class="text-muted">Ventas/día</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-warning"><?= number_format($resumenMes['kilos_promedio_captacion'], 2) ?></div>
                                    <small class="text-muted">Kilos/captación</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-info"><?= number_format($resumenMes['valor_promedio_venta'], 2) ?></div>
                                    <small class="text-muted">Valor/venta</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    // NO usar exit() - dejar que se siga cargando el resto de la página
    // Solo terminar la ejecución de este archivo
    return;
}
// ============================================================================
// ESCENARIO 2: ZONA MEO SELECCIONADA - Mostrar dashboard MEO
// ============================================================================
if ($tipoZonaActual === 'MEO') {
    // Obtener nombre de la zona MEO seleccionada
    $zona_query = $conn_mysql->query("SELECT nom FROM zonas WHERE id_zone = '$zona_seleccionada'");
    $zona_data = mysqli_fetch_array($zona_query);
    $nombre_zona = $zona_data['nom'] ?? '';
    
    // Obtener parámetro del mes (si existe)
    $mes_param = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
    
    // Validar formato del mes (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $mes_param)) {
        $mes_param = date('Y-m');
    }
    
    // Crear objeto DateTime para el mes seleccionado
    $mes_actual = DateTime::createFromFormat('Y-m', $mes_param);
    if (!$mes_actual) {
        $mes_actual = new DateTime();
        $mes_param = $mes_actual->format('Y-m');
    }
    
    // Calcular mes anterior y siguiente
    $mes_anterior = clone $mes_actual;
    $mes_anterior->modify('-1 month');
    
    $mes_siguiente = clone $mes_actual;
    $mes_siguiente->modify('+1 month');
    
    // Verificar si el mes siguiente es futuro
    $mes_actual_num = intval($mes_actual->format('Ym'));
    $mes_siguiente_num = intval($mes_siguiente->format('Ym'));
    $mes_actual_actual = intval(date('Ym'));
    
    $es_mes_futuro = $mes_siguiente_num > $mes_actual_actual;
    
    // Obtener estadísticas MEO para el dashboard del mes seleccionado
    $stats = obtenerEstadisticasMEO($conn_mysql, $zona_seleccionada, $mes_param);
    
    // Obtener últimas captaciones y ventas del mes seleccionado
    $ultimasCaptaciones = obtenerUltimasCaptaciones($conn_mysql, $zona_seleccionada, 5, $mes_param);
    $ultimasVentas = obtenerUltimasVentas($conn_mysql, $zona_seleccionada, 5, $mes_param);
    
    // Obtener resumen mensual
    $resumenMes = obtenerResumenMensualMEO($conn_mysql, $zona_seleccionada, $mes_param);
    
    // Formatear nombre del mes en español
    $fmt = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, 'LLLL yyyy');
    $nombre_mes = ucfirst($fmt->format($mes_actual));
    
    ?>
    <div class="container py-4">
        <!-- HEADER CON NAVEGACIÓN DE MESES -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow rounded-4">
                    <div class="card-header encabezado-col border-0 py-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="fw-bold text-light mb-1">
                                    <i class="bi bi-box-seam me-2"></i>Dashboard - <?= htmlspecialchars($nombre_zona) ?>
                                </h2>
                                <p class="text-light mb-0">
                                    <span class="badge bg-success me-2">
                                        <i class="bi bi-shield-check me-1"></i> Materiales Especiales y Operaciones
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-end">
                                    <!-- Selector de mes rápido -->
                                    <div class="me-3">
                                        <div class="dropdown">
                                            <button class="btn btn-light dropdown-toggle" type="button" id="mesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-calendar-month me-2"></i><?= htmlspecialchars($nombre_mes) ?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="mesDropdown">
                                                <?php
                                                // Generar opciones para los últimos 12 meses
                                                $hoy = new DateTime();
                                                for ($i = 0; $i < 12; $i++) {
                                                    $mes_opcion = clone $hoy;
                                                    $mes_opcion->modify("-$i months");
                                                    $mes_valor = $mes_opcion->format('Y-m');
                                                    $mes_nombre = ucfirst($fmt->format($mes_opcion));
                                                    $activo = ($mes_valor === $mes_param) ? 'active' : '';
                                                    ?>
                                                    <li>
                                                        <a class="dropdown-item <?= $activo ?>" href="?p=<?= $_GET['p'] ?? 'index' ?>&zona=<?= $zona_seleccionada ?>&mes=<?= $mes_valor ?>">
                                                            <?= $mes_nombre ?>
                                                        </a>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="?p=<?= $_GET['p'] ?? 'index' ?>&zona=<?= $zona_seleccionada ?>&mes=<?= date('Y-m') ?>">
                                                        <i class="bi bi-arrow-clockwise me-2"></i>Mes actual
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Navegación por flechas -->
                                    <div class="btn-group" role="group">
                                        <a href="?p=<?= $_GET['p'] ?? 'index' ?>&zona=<?= $zona_seleccionada ?>&mes=<?= $mes_anterior->format('Y-m') ?>" 
                                           class="btn btn-outline-light">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                        
                                        <button type="button" class="btn btn-light" style="min-width: 120px;">
                                            <i class="bi bi-calendar3 me-2"></i><?= $mes_actual->format('M Y') ?>
                                        </button>
                                        
                                        <a href="?p=<?= $_GET['p'] ?? 'index' ?>&zona=<?= $zona_seleccionada ?>&mes=<?= $mes_siguiente->format('Y-m') ?>" 
                                           class="btn btn-outline-light <?= $es_mes_futuro ? 'disabled' : '' ?>"
                                           <?= $es_mes_futuro ? 'aria-disabled="true"' : '' ?>>
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-light">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Mostrando datos del período: <?= htmlspecialchars($nombre_mes) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <small class="text-light">
                                            <i class="bi bi-clock me-1"></i> Actualizado: <?= date('H:i') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN DEL MES ACTUAL -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 py-3 bg-primary-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary-emphasis">
                                <i class="bi bi-calendar2-check me-2"></i>Resumen del Mes - <?= htmlspecialchars($nombre_mes) ?>
                            </h5>
                            <span class="badge bg-primary">
                                <?= $mes_actual->format('d/m/Y') === date('d/m/Y') ? 'Mes en curso' : 'Mes histórico' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded mb-3">
                                    <div class="text-primary fs-1"><?= $stats['total_captaciones'] ?></div>
                                    <small class="text-muted">Captaciones</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded mb-3">
                                    <div class="text-success fs-1"><?= $stats['total_ventas'] ?></div>
                                    <small class="text-muted">Ventas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded mb-3">
                                    <div class="text-warning fs-1"><?= number_format($stats['total_kilos_captados'], 0) ?></div>
                                    <small class="text-muted">Kilos captados</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded mb-3">
                                    <div class="text-info fs-1"><?= number_format($stats['total_kilos_vendidos'], 0) ?></div>
                                    <small class="text-muted">Kilos vendidos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MÉTRICAS PRINCIPALES -->
        <div class="row mb-4 g-3">
            <?php 
            $metricas_principales = [
                [
                    'titulo' => 'Total Captaciones', 
                    'valor' => $stats['total_captaciones'], 
                    'icono' => 'inbox-fill', 
                    'color' => 'primary',
                    'desc' => 'Mes seleccionado',
                    'link' => 'captacion'
                ],
                [
                    'titulo' => 'Total Ventas', 
                    'valor' => $stats['total_ventas'], 
                    'icono' => 'cart-check', 
                    'color' => 'success',
                    'desc' => 'Mes seleccionado',
                    'link' => 'ventas'
                ],
                [
                    'titulo' => 'Material Captado', 
                    'valor' => number_format($stats['total_kilos_captados'], 2), 
                    'icono' => 'boxes', 
                    'color' => 'warning',
                    'desc' => 'Kilos totales',
                    'link' => 'captacion'
                ],
                [
                    'titulo' => 'Material Vendido', 
                    'valor' => number_format($stats['total_kilos_vendidos'], 2), 
                    'icono' => 'truck', 
                    'color' => 'info',
                    'desc' => 'Kilos totales',
                    'link' => 'ventas'
                ],
            ];
            ?>

            <?php foreach ($metricas_principales as $metrica): ?>
            <div class="col-xl-3 col-md-6">
                <a href="?p=<?= $metrica['link'] ?>" class="text-decoration-none">
                    <div class="card border-0 shadow h-100 hover-lift">
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

        <!-- RESUMEN FINANCIERO -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Resumen Financiero</h5>
                            <small class="text-muted"><?= htmlspecialchars($nombre_mes) ?></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="?p=captacion" class="text-decoration-none">
                                    <div class="card border border-primary-subtle bg-primary-subtle hover-lift">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="bi bi-cash-coin text-primary fs-1"></i>
                                            </div>
                                            <h3 class="text-primary mb-2">$<?= number_format($stats['costo_total_captaciones'], 2) ?></h3>
                                            <p class="text-primary-emphasis mb-0">Costo Captaciones</p>
                                            <small class="text-muted">Inversión en materiales</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <a href="?p=ventas" class="text-decoration-none">
                                    <div class="card border border-success-subtle bg-success-subtle hover-lift">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="bi bi-currency-dollar text-success fs-1"></i>
                                            </div>
                                            <h3 class="text-success mb-2">$<?= number_format($stats['ingreso_total_ventas'], 2) ?></h3>
                                            <p class="text-success-emphasis mb-0">Ingreso Ventas</p>
                                            <small class="text-muted">Ventas totales</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card border border-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-subtle bg-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-subtle hover-lift">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="bi bi-graph-up-arrow text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?> fs-1"></i>
                                        </div>
                                        <h3 class="text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?> mb-2">
                                            $<?= number_format($stats['utilidad_neta'], 2) ?>
                                        </h3>
                                        <p class="text-<?= $stats['utilidad_neta'] >= 0 ? 'success' : 'danger' ?>-emphasis mb-0">Utilidad Neta</p>
                                        <small class="text-muted">
                                            <?= $stats['utilidad_neta'] >= 0 ? 'Ganancia' : 'Pérdida' ?> del mes
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Costo por Kilo</small>
                                        <h6 class="mb-0">$<?= number_format($stats['costo_por_kilo'], 4) ?></h6>
                                    </div>
                                    <i class="bi bi-coin text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Precio Venta por Kilo</small>
                                        <h6 class="mb-0">$<?= number_format($stats['precio_venta_por_kilo'], 4) ?></h6>
                                    </div>
                                    <i class="bi bi-tag text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                    <div>
                                        <small class="text-muted">Margen por Kilo</small>
                                        <h6 class="mb-0 text-<?= $stats['margen_por_kilo'] >= 0 ? 'success' : 'danger' ?>">
                                            $<?= number_format($stats['margen_por_kilo'], 4) ?>
                                        </h6>
                                    </div>
                                    <i class="bi bi-percent text-<?= $stats['margen_por_kilo'] >= 0 ? 'success' : 'danger' ?> fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE ACTIVIDAD RECIENTE -->
        <div class="row mb-4">
            <!-- ÚLTIMAS CAPTACIONES -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-inbox me-2 text-primary"></i>Últimas Captaciones</h5>
                            <a href="?p=captacion" class="btn btn-outline-primary btn-sm">
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
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Kilos</th>
                                        <th>Costo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ultimasCaptaciones)): ?>
                                        <?php foreach ($ultimasCaptaciones as $captacion): ?>
                                        <tr class="hover-lift">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary-subtle p-2 rounded me-3">
                                                        <i class="bi bi-inbox text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($captacion['folio']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= $captacion['fecha'] ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($captacion['proveedor']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis">
                                                    <?= number_format($captacion['kilos'], 2) ?> kg
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-semibold">
                                                    $<?= number_format($captacion['costo'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?p=V_captacion&id=<?= $captacion['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Ver detalles" target="_blank">
                                                   <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-6 opacity-25"></i>
                                                    <p class="mt-2">No hay captaciones en <?= htmlspecialchars($nombre_mes) ?></p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÚLTIMAS VENTAS -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-cart me-2 text-success"></i>Últimas Ventas</h5>
                            <a href="?p=ventas" class="btn btn-outline-success btn-sm">
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
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Kilos</th>
                                        <th>Total</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ultimasVentas)): ?>
                                        <?php foreach ($ultimasVentas as $venta): ?>
                                        <tr class="hover-lift">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success-subtle p-2 rounded me-3">
                                                        <i class="bi bi-cart-check text-success"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($venta['folio']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= $venta['fecha'] ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($venta['cliente']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info-emphasis">
                                                    <?= number_format($venta['kilos'], 2) ?> kg
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-semibold">
                                                    $<?= number_format($venta['total'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?p=V_venta&id=<?= $venta['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Ver detalles" target="_blank">
                                                   <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-cart display-6 opacity-25"></i>
                                                    <p class="mt-2">No hay ventas en <?= htmlspecialchars($nombre_mes) ?></p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN DETALLADO -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 py-3">
                        <h5 class="mb-0"><i class="bi bi-calendar2-month me-2 text-primary"></i>Resumen Detallado - <?= htmlspecialchars($nombre_mes) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-primary-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-down-circle text-primary fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Entradas</h5>
                                        <p class="text-muted mb-0">
                                            <strong><?= $resumenMes['entradas_captaciones'] ?></strong> captaciones
                                            <br>
                                            <span class="text-success">$<?= number_format($resumenMes['costo_entradas'], 2) ?></span> invertido
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-success-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-up-circle text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Salidas</h5>
                                        <p class="text-muted mb-0">
                                            <strong><?= $resumenMes['salidas_ventas'] ?></strong> ventas
                                            <br>
                                            <span class="text-success">$<?= number_format($resumenMes['ingreso_salidas'], 2) ?></span> generado
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <div class="bg-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?>-subtle p-3 rounded-circle me-3">
                                        <i class="bi bi-arrow-left-right text-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?> fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Balance</h5>
                                        <p class="text-muted mb-0">
                                            <strong class="text-<?= $resumenMes['balance'] >= 0 ? 'success' : 'danger' ?>">
                                                $<?= number_format($resumenMes['balance'], 2) ?>
                                            </strong> neto
                                            <br>
                                            <small>Ingresos - Costos</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-primary"><?= $resumenMes['promedio_captaciones_dia'] ?></div>
                                    <small class="text-muted">Captaciones/día</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-success"><?= $resumenMes['promedio_ventas_dia'] ?></div>
                                    <small class="text-muted">Ventas/día</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-warning"><?= number_format($resumenMes['kilos_promedio_captacion'], 2) ?></div>
                                    <small class="text-muted">Kilos/captación</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-2">
                                    <div class="fs-4 text-info"><?= number_format($resumenMes['valor_promedio_venta'], 2) ?></div>
                                    <small class="text-muted">Valor/venta</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script para manejar la navegación por teclado -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Navegación con teclado
        document.addEventListener('keydown', function(e) {
            // Ignorar si estamos en un input o textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            // Flecha izquierda: mes anterior
            if (e.key === 'ArrowLeft') {
                const prevLink = document.querySelector('a[href*="mes="]:has(.bi-chevron-left)');
                if (prevLink && !prevLink.classList.contains('disabled')) {
                    window.location.href = prevLink.href;
                }
            }
            
            // Flecha derecha: mes siguiente (si no es futuro)
            if (e.key === 'ArrowRight') {
                const nextLink = document.querySelector('a[href*="mes="]:has(.bi-chevron-right)');
                if (nextLink && !nextLink.classList.contains('disabled')) {
                    window.location.href = nextLink.href;
                }
            }
            
            // Tecla M: seleccionar mes
            if (e.key === 'm' || e.key === 'M') {
                document.getElementById('mesDropdown').click();
            }
        });
        
        // Actualizar URL para compartir
        const currentUrl = new URL(window.location.href);
        if (!currentUrl.searchParams.has('mes')) {
            currentUrl.searchParams.set('mes', '<?= $mes_param ?>');
            window.history.replaceState({}, '', currentUrl.toString());
        }
    });
    </script>
    <?php
    // NO usar exit() - dejar que se siga cargando el resto de la página
    // Solo terminar la ejecución de este archivo
    return;
}
// ============================================================================
// FUNCIONES PARA EL DASHBOARD MEO (ACTUALIZADAS CON PARÁMETRO DE MES)
// ============================================================================

function obtenerEstadisticasMEO($conn_mysql, $zona_seleccionada, $mes = null) {
    $mes_actual = $mes ?? date('Y-m');
    
    // CONSULTA SEPARADA PARA CAPTACIONES (PRODUCTOS)
    $captaciones_productos_query = "
        SELECT 
            COUNT(DISTINCT c.id_captacion) as total_captaciones,
            COALESCE(SUM(cd.total_kilos), 0) as total_kilos,
            COALESCE(SUM(p.precio * cd.total_kilos), 0) as costo_productos
        FROM captacion c
        LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
        LEFT JOIN precios p ON cd.id_pre_compra = p.id_precio
        WHERE c.zona = '$zona_seleccionada' 
        AND c.status = 1
        AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes_actual'
    ";
    
    $captaciones_productos_data = $conn_mysql->query($captaciones_productos_query)->fetch_assoc();
    
    // CONSULTA SEPARADA PARA FLETES DE CAPTACIONES
    $captaciones_fletes_query = "
        SELECT 
            COUNT(DISTINCT cf.id_captacion) as captaciones_con_flete,
            COALESCE(SUM(pf.precio), 0) as costo_flete_total
        FROM captacion c
        LEFT JOIN captacion_flete cf ON c.id_captacion = cf.id_captacion
        LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
        WHERE c.zona = '$zona_seleccionada' 
        AND c.status = 1
        AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes_actual'
        AND cf.id_captacion IS NOT NULL
    ";
    
    $captaciones_fletes_data = $conn_mysql->query($captaciones_fletes_query)->fetch_assoc();
    
    // CONSULTA SEPARADA PARA VENTAS (PRODUCTOS)
    $ventas_productos_query = "
        SELECT 
            COUNT(DISTINCT v.id_venta) as total_ventas,
            COALESCE(SUM(vd.total_kilos), 0) as total_kilos,
            COALESCE(SUM(p.precio * vd.total_kilos), 0) as ingreso_productos
        FROM ventas v
        LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
        LEFT JOIN precios p ON vd.id_pre_venta = p.id_precio
        WHERE v.zona = '$zona_seleccionada' 
        AND v.status = 1
        AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes_actual'
    ";
    
    $ventas_productos_data = $conn_mysql->query($ventas_productos_query)->fetch_assoc();
    
    // CONSULTA SEPARADA PARA FLETES DE VENTAS
    $ventas_fletes_query = "
        SELECT 
            COUNT(DISTINCT vf.id_venta) as ventas_con_flete,
            COALESCE(SUM(pf.precio), 0) as ingreso_flete_total
        FROM ventas v
        LEFT JOIN venta_flete vf ON v.id_venta = vf.id_venta
        LEFT JOIN precios pf ON vf.id_pre_flete = pf.id_precio
        WHERE v.zona = '$zona_seleccionada' 
        AND v.status = 1
        AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes_actual'
        AND vf.id_venta IS NOT NULL
    ";
    
    $ventas_fletes_data = $conn_mysql->query($ventas_fletes_query)->fetch_assoc();
    
    // Calcular totales combinados
    $total_kilos_captados = floatval($captaciones_productos_data['total_kilos']);
    $total_kilos_vendidos = floatval($ventas_productos_data['total_kilos']);
    
    $costo_productos = floatval($captaciones_productos_data['costo_productos']);
    $costo_flete = floatval($captaciones_fletes_data['costo_flete_total']);
    $costo_total_captaciones = $costo_productos + $costo_flete;
    
    $ingreso_productos = floatval($ventas_productos_data['ingreso_productos']);
    $ingreso_flete = floatval($ventas_fletes_data['ingreso_flete_total']);
    $ingreso_total_ventas = $ingreso_productos - $ingreso_flete;
    
    $utilidad_neta = $ingreso_total_ventas - $costo_total_captaciones;
    
    // Calcular promedios
    $costo_por_kilo = $total_kilos_captados > 0 ? $costo_total_captaciones / $total_kilos_captados : 0;
    $precio_venta_por_kilo = $total_kilos_vendidos > 0 ? $ingreso_total_ventas / $total_kilos_vendidos : 0;
    $margen_por_kilo = $precio_venta_por_kilo - $costo_por_kilo;
    
    return [
        'total_captaciones' => intval($captaciones_productos_data['total_captaciones']),
        'total_ventas' => intval($ventas_productos_data['total_ventas']),
        'total_kilos_captados' => $total_kilos_captados,
        'total_kilos_vendidos' => $total_kilos_vendidos,
        'costo_total_captaciones' => $costo_total_captaciones,
        'costo_productos_captaciones' => $costo_productos,
        'costo_flete_captaciones' => $costo_flete,
        'captaciones_con_flete' => intval($captaciones_fletes_data['captaciones_con_flete']),
        'ingreso_total_ventas' => $ingreso_total_ventas,
        'ingreso_productos_ventas' => $ingreso_productos,
        'ingreso_flete_ventas' => $ingreso_flete,
        'ventas_con_flete' => intval($ventas_fletes_data['ventas_con_flete']),
        'utilidad_neta' => $utilidad_neta,
        'costo_por_kilo' => $costo_por_kilo,
        'precio_venta_por_kilo' => $precio_venta_por_kilo,
        'margen_por_kilo' => $margen_por_kilo
    ];
}

function obtenerUltimasCaptaciones($conn_mysql, $zona_seleccionada, $limit = 5, $mes = null) {
    $where_mes = "";
    if ($mes) {
        $where_mes = "AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes'";
    }
    
    $query = "
        SELECT 
            c.id_captacion,
            CONCAT('C-', z.cod, '-', DATE_FORMAT(c.fecha_captacion, '%y%m'), LPAD(c.folio, 4, '0')) as folio,
            DATE_FORMAT(c.fecha_captacion, '%d/%m/%Y') as fecha,
            p.rs as proveedor,
            COALESCE(productos.total_kilos, 0) as kilos,
            COALESCE(productos.costo_productos, 0) as costo_productos,
            COALESCE(fletes.costo_flete, 0) as costo_flete,
            (COALESCE(productos.costo_productos, 0) + COALESCE(fletes.costo_flete, 0)) as costo_total
        FROM captacion c
        LEFT JOIN zonas z ON c.zona = z.id_zone
        LEFT JOIN proveedores p ON c.id_prov = p.id_prov
        LEFT JOIN (
            SELECT 
                cd.id_captacion,
                SUM(cd.total_kilos) as total_kilos,
                SUM(pc.precio * cd.total_kilos) as costo_productos
            FROM captacion_detalle cd
            LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
            WHERE cd.status = 1
            GROUP BY cd.id_captacion
        ) productos ON c.id_captacion = productos.id_captacion
        LEFT JOIN (
            SELECT 
                cf.id_captacion,
                pf.precio as costo_flete
            FROM captacion_flete cf
            LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
        ) fletes ON c.id_captacion = fletes.id_captacion
        WHERE c.zona = '$zona_seleccionada' 
        AND c.status = 1
        $where_mes
        GROUP BY c.id_captacion, c.folio, c.fecha_captacion, z.cod, p.rs
        ORDER BY c.fecha_captacion DESC, c.id_captacion DESC
        LIMIT $limit
    ";
    
    $result = $conn_mysql->query($query);
    $captaciones = [];
    
    while ($row = $result->fetch_assoc()) {
        $captaciones[] = [
            'id' => $row['id_captacion'],
            'folio' => $row['folio'],
            'fecha' => $row['fecha'],
            'proveedor' => $row['proveedor'],
            'kilos' => floatval($row['kilos']),
            'costo' => floatval($row['costo_total']),
            'costo_productos' => floatval($row['costo_productos']),
            'costo_flete' => floatval($row['costo_flete'])
        ];
    }
    
    return $captaciones;
}

function obtenerUltimasVentas($conn_mysql, $zona_seleccionada, $limit = 5, $mes = null) {
    $where_mes = "";
    if ($mes) {
        $where_mes = "AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes'";
    }
    
    $query = "
        SELECT 
            v.id_venta,
            CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio, 4, '0')) as folio,
            DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha,
            c.nombre as cliente,
            COALESCE(productos.total_kilos, 0) as kilos,
            COALESCE(productos.ingreso_productos, 0) as ingreso_productos,
            COALESCE(fletes.ingreso_flete, 0) as ingreso_flete,
            (COALESCE(productos.ingreso_productos, 0) - COALESCE(fletes.ingreso_flete, 0)) as ingreso_total
        FROM ventas v
        LEFT JOIN zonas z ON v.zona = z.id_zone
        LEFT JOIN clientes c ON v.id_cliente = c.id_cli
        LEFT JOIN (
            SELECT 
                vd.id_venta,
                SUM(vd.total_kilos) as total_kilos,
                SUM(p.precio * vd.total_kilos) as ingreso_productos
            FROM venta_detalle vd
            LEFT JOIN precios p ON vd.id_pre_venta = p.id_precio
            WHERE vd.status = 1
            GROUP BY vd.id_venta
        ) productos ON v.id_venta = productos.id_venta
        LEFT JOIN (
            SELECT 
                vf.id_venta,
                pf.precio as ingreso_flete
            FROM venta_flete vf
            LEFT JOIN precios pf ON vf.id_pre_flete = pf.id_precio
        ) fletes ON v.id_venta = fletes.id_venta
        WHERE v.zona = '$zona_seleccionada' 
        AND v.status = 1
        $where_mes
        GROUP BY v.id_venta, v.folio, v.fecha_venta, z.cod, c.nombre
        ORDER BY v.fecha_venta DESC, v.id_venta DESC
        LIMIT $limit
    ";
    
    $result = $conn_mysql->query($query);
    $ventas = [];
    
    while ($row = $result->fetch_assoc()) {
        $ventas[] = [
            'id' => $row['id_venta'],
            'folio' => $row['folio'],
            'fecha' => $row['fecha'],
            'cliente' => $row['cliente'],
            'kilos' => floatval($row['kilos']),
            'total' => floatval($row['ingreso_total']),
            'ingreso_productos' => floatval($row['ingreso_productos']),
            'ingreso_flete' => floatval($row['ingreso_flete'])
        ];
    }
    
    return $ventas;
}

function obtenerResumenMensualMEO($conn_mysql, $zona_seleccionada, $mes = null) {
    $mes_actual = $mes ?? date('Y-m');
    
    // Obtener el número de días en el mes
    $fecha_mes = DateTime::createFromFormat('Y-m', $mes_actual);
    $dias_mes = $fecha_mes ? $fecha_mes->format('t') : date('t');
    
    // CAPTACIONES - Consulta separada para productos
    $captaciones_productos_query = "
        SELECT 
            COUNT(DISTINCT c.id_captacion) as total_captaciones,
            COALESCE(SUM(p.precio * cd.total_kilos), 0) as costo_productos
        FROM captacion c
        LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
        LEFT JOIN precios p ON cd.id_pre_compra = p.id_precio
        WHERE c.zona = '$zona_seleccionada' 
        AND c.status = 1
        AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes_actual'
    ";
    
    $captaciones_productos_data = $conn_mysql->query($captaciones_productos_query)->fetch_assoc();
    
    // CAPTACIONES - Consulta separada para fletes
    $captaciones_fletes_query = "
        SELECT 
            COALESCE(SUM(pf.precio), 0) as costo_flete
        FROM captacion c
        LEFT JOIN captacion_flete cf ON c.id_captacion = cf.id_captacion
        LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
        WHERE c.zona = '$zona_seleccionada' 
        AND c.status = 1
        AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes_actual'
        AND cf.id_captacion IS NOT NULL
    ";
    
    $captaciones_fletes_data = $conn_mysql->query($captaciones_fletes_query)->fetch_assoc();
    
    // VENTAS - Consulta separada para productos
    $ventas_productos_query = "
        SELECT 
            COUNT(DISTINCT v.id_venta) as total_ventas,
            COALESCE(SUM(p.precio * vd.total_kilos), 0) as ingreso_productos
        FROM ventas v
        LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
        LEFT JOIN precios p ON vd.id_pre_venta = p.id_precio
        WHERE v.zona = '$zona_seleccionada' 
        AND v.status = 1
        AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes_actual'
    ";
    
    $ventas_productos_data = $conn_mysql->query($ventas_productos_query)->fetch_assoc();
    
    // VENTAS - Consulta separada para fletes
    $ventas_fletes_query = "
        SELECT 
            COALESCE(SUM(pf.precio), 0) as ingreso_flete
        FROM ventas v
        LEFT JOIN venta_flete vf ON v.id_venta = vf.id_venta
        LEFT JOIN precios pf ON vf.id_pre_flete = pf.id_precio
        WHERE v.zona = '$zona_seleccionada' 
        AND v.status = 1
        AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes_actual'
        AND vf.id_venta IS NOT NULL
    ";
    
    $ventas_fletes_data = $conn_mysql->query($ventas_fletes_query)->fetch_assoc();
    
    // Calcular totales
    $costo_entradas_total = floatval($captaciones_productos_data['costo_productos']) + 
                           floatval($captaciones_fletes_data['costo_flete']);
    
    $ingreso_salidas_total = floatval($ventas_productos_data['ingreso_productos']) - 
                            floatval($ventas_fletes_data['ingreso_flete']);
    
    // Kilos promedio por captación
    $kilos_promedio = "
        SELECT 
            COALESCE(AVG(total_kilos), 0) as promedio_kilos
        FROM (
            SELECT COALESCE(SUM(cd.total_kilos), 0) as total_kilos
            FROM captacion c
            LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
            WHERE c.zona = '$zona_seleccionada' 
            AND c.status = 1
            AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = '$mes_actual'
            GROUP BY c.id_captacion
        ) as subquery
    ";
    
    $kilos_data = $conn_mysql->query($kilos_promedio)->fetch_assoc();
    
    // Valor promedio por venta
    $valor_promedio = "
        SELECT 
            COALESCE(AVG(total_venta), 0) as promedio_valor
        FROM (
            SELECT 
                v.id_venta,
                COALESCE(SUM(p.precio * vd.total_kilos), 0) - 
                COALESCE(pf.precio, 0) as total_venta
            FROM ventas v
            LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
            LEFT JOIN precios p ON vd.id_pre_venta = p.id_precio
            LEFT JOIN venta_flete vf ON v.id_venta = vf.id_venta
            LEFT JOIN precios pf ON vf.id_pre_flete = pf.id_precio
            WHERE v.zona = '$zona_seleccionada' 
            AND v.status = 1
            AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = '$mes_actual'
            GROUP BY v.id_venta, pf.precio
        ) as subquery
    ";
    
    $valor_data = $conn_mysql->query($valor_promedio)->fetch_assoc();
    
    // Calcular promedios diarios
    $total_captaciones = intval($captaciones_productos_data['total_captaciones']);
    $total_ventas = intval($ventas_productos_data['total_ventas']);
    
    $promedio_captaciones_dia = $dias_mes > 0 ? round($total_captaciones / $dias_mes, 1) : 0;
    $promedio_ventas_dia = $dias_mes > 0 ? round($total_ventas / $dias_mes, 1) : 0;
    
    return [
        'entradas_captaciones' => $total_captaciones,
        'costo_entradas' => $costo_entradas_total,
        'costo_entradas_productos' => floatval($captaciones_productos_data['costo_productos']),
        'costo_entradas_flete' => floatval($captaciones_fletes_data['costo_flete']),
        'salidas_ventas' => $total_ventas,
        'ingreso_salidas' => $ingreso_salidas_total,
        'ingreso_salidas_productos' => floatval($ventas_productos_data['ingreso_productos']),
        'ingreso_salidas_flete' => floatval($ventas_fletes_data['ingreso_flete']),
        'balance' => $ingreso_salidas_total - $costo_entradas_total,
        'promedio_captaciones_dia' => $promedio_captaciones_dia,
        'promedio_ventas_dia' => $promedio_ventas_dia,
        'kilos_promedio_captacion' => floatval($kilos_data['promedio_kilos']),
        'valor_promedio_venta' => floatval($valor_data['promedio_valor'])
    ];
}

if ($tipoZonaActual === 'NOR') {
// ============================================================================
// DASHBOARD PARA ZONAS NOR - DISEÑO ACTUALIZADO
// ============================================================================

// Título de la página
$titulo_pagina = "Dashboard";

// Obtener estadísticas generales
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

// AVISOS IMPORTANTES
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

// Obtener últimas recolecciones
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
<div class="container py-4" data-zona-tipo="<?= $tipoZonaActual ?>">
    
    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow rounded-4">
                <div class="card-header encabezado-col border-0 py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold text-light mb-1">
                                <i class="bi bi-truck me-2"></i>Dashboard - <?= htmlspecialchars($nombre_zona) ?>
                            </h2>
                            <p class="text-light mb-0">
                                <span class="badge bg-success me-2">
                                    <i class="bi bi-clipboard-data me-1"></i> Recolección Tradicional
                                </span>
                                <!--<span class="badge bg-primary">
                                    <?php
                                        //$fmt = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, 'LLLL yyyy');
                                        //$fecha_es = $fmt->format(new DateTime());
                                    ?>
                                    <i class="bi bi-calendar me-1"></i> <?= htmlspecialchars(ucfirst($fecha_es)) ?>
                                </span>-->
                            </p>
                        </div>
                        <div>
                            <small class="text-light">
                                <i class="bi bi-clock me-1"></i> Actualizado: <?= date('H:i') ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MÉTRICAS PRINCIPALES -->
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
                <div class="card border-0 shadow h-100 hover-lift">
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
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3">
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
                                <div class="card border border-warning-subtle bg-warning-subtle hover-lift">
                                    <div class="card-body text-center p-2">
                                        <div class="mb-1">
                                            <i class="bi bi-calendar-x text-warning fs-1"></i>
                                        </div>
                                        <h3 class="text-warning mb-2"><?= $aviso_precios ?></h3>
                                        <p class="text-warning-emphasis mb-0">Precios por Caducar</p>
                                        <small class="text-muted">Productos</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($aviso_sin_precio > 0): ?>
                        <div class="col-md-4">
                            <a href="?p=productos&filtro=sinprecio" class="text-decoration-none">
                                <div class="card border border-danger-subtle bg-danger-subtle hover-lift">
                                    <div class="card-body text-center p-2">
                                        <div class="mb-1">
                                            <i class="bi bi-tag text-danger fs-1"></i>
                                        </div>
                                        <h3 class="text-danger mb-2"><?= $aviso_sin_precio ?></h3>
                                        <p class="text-danger-emphasis mb-0">Sin Precio Actual</p>
                                        <small class="text-muted">Productos</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($aviso_fleteros > 0): ?>
                        <div class="col-md-4">
                            <a href="?p=transportes&filtro=sincorreo" class="text-decoration-none">
                                <div class="card border border-info-subtle bg-info-subtle hover-lift">
                                    <div class="card-body text-center p-2">
                                        <div class="mb-1">
                                            <i class="bi bi-envelope text-info fs-1"></i>
                                        </div>
                                        <h3 class="text-info mb-2"><?= $aviso_fleteros ?></h3>
                                        <p class="text-info-emphasis mb-0">Fleteros sin Correo</p>
                                        <small class="text-muted">Transportes</small>
                                    </div>
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
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3 bg-danger-subtle">
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
                <div class="card-footer border-0 py-3 bg-danger-subtle">
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
            <div class="card border-0 shadow">
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
            <div class="card border-0 shadow">
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
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow h-100">
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
                                <?php if ($ultimasRecolecciones->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-clipboard-data display-6 opacity-25"></i>
                                            <p class="mt-2">No hay recolecciones recientes</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
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

    <!-- RESUMEN DEL MES -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-calendar2-month me-2 text-primary"></i>Resumen del Mes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <div class="bg-primary-subtle p-3 rounded-circle me-3">
                                    <i class="bi bi-truck text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Total Recolecciones</h5>
                                    <p class="text-muted mb-0">
                                        <strong><?= $stats['recolecciones_mes'] ?></strong> este mes
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <div class="bg-success-subtle p-3 rounded-circle me-3">
                                    <i class="bi bi-check-circle text-success fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Completadas</h5>
                                    <p class="text-muted mb-0">
                                        <strong><?= $stats['recolecciones_completas_mes'] ?></strong> recolecciones
                                        <br>
                                        <small>Remisión y factura completas</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <div class="bg-warning-subtle p-3 rounded-circle me-3">
                                    <i class="bi bi-clock text-warning fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Pendientes</h5>
                                    <p class="text-muted mb-0">
                                        <strong><?= $stats['recolecciones_pendientes'] ?></strong> recolecciones
                                        <br>
                                        <small>Faltan documentos por subir</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="text-center p-2">
                                <div class="fs-4 text-primary"><?= $stats['proveedores_activos'] ?></div>
                                <small class="text-muted">Proveedores</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2">
                                <div class="fs-4 text-success"><?= $stats['clientes_activos'] ?></div>
                                <small class="text-muted">Clientes</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2">
                                <div class="fs-4 text-warning"><?= $stats['productos_activos'] ?></div>
                                <small class="text-muted">Productos</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2">
                                <div class="fs-4 text-info"><?= $stats['transportes_activos'] ?></div>
                                <small class="text-muted">Fleteros</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}

?>

<!-- Utilidades de Bootstrap 5 -->

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