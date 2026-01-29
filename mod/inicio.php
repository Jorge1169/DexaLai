<?php
// Obtener tipo de zona actual - USANDO LA FUNCIÓN EXISTENTE
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql);

// ============================================================================
// ESCENARIO 1: SIN ZONA SELECCIONADA
// ============================================================================
if ($zona_seleccionada == '0' || empty($zona_seleccionada)) {
    mostrarSelectorZonaUI();
    return;
}

// ============================================================================
// ESCENARIO 2: ZONA MEO SELECCIONADA
// ============================================================================
if ($tipoZonaActual === 'MEO') {
    mostrarDashboardMEO($conn_mysql, $zona_seleccionada);
    return;
}

// ============================================================================
// ESCENARIO 3: ZONA NOR SELECCIONADA
// ============================================================================
if ($tipoZonaActual === 'NOR') {
    mostrarDashboardNOR($conn_mysql, $zona_seleccionada);
    return;
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function mostrarSelectorZonaUI() {
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
        const zoneSelect = document.getElementById('zoneSelect');
        if (zoneSelect) {
            zoneSelect.focus();
            
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
}

function mostrarDashboardMEO($conn_mysql, $zona_seleccionada) {
    // Configuración del mes
    $mes_param = $_GET['mes'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $mes_param)) {
        $mes_param = date('Y-m');
    }
    
    $mes_actual = DateTime::createFromFormat('Y-m', $mes_param);
    if (!$mes_actual) {
        $mes_actual = new DateTime();
        $mes_param = $mes_actual->format('Y-m');
    }
    
    // Obtener datos
    $nombre_zona = obtenerNombreZonaDashboard($conn_mysql, $zona_seleccionada);
    $stats = obtenerEstadisticasMEODashboard($conn_mysql, $zona_seleccionada, $mes_param);
    $ultimasCaptaciones = obtenerUltimasCaptacionesDashboard($conn_mysql, $zona_seleccionada, 5, $mes_param);
    $ultimasVentas = obtenerUltimasVentasDashboard($conn_mysql, $zona_seleccionada, 5, $mes_param);
    $resumenMes = obtenerResumenMensualMEODashboard($conn_mysql, $zona_seleccionada, $mes_param);
    
    // Formatear nombre del mes
    $nombre_mes = $mes_actual->format('F Y');
    
    ?>
    <div class="container py-4">
        <!-- Header -->
        <?php mostrarHeaderMEODashboard($nombre_zona, $mes_param, $nombre_mes, $mes_actual); ?>
        
        <!-- Métricas principales -->
        <?php mostrarMetricasPrincipalesMEODashboard($stats); ?>
        
        <!-- Resumen financiero -->
        <?php mostrarResumenFinancieroMEODashboard($stats, $nombre_mes); ?>
        
        <!-- Actividad reciente -->
        <?php mostrarActividadRecienteMEODashboard($ultimasCaptaciones, $ultimasVentas, $nombre_mes); ?>
        
        <!-- Resumen detallado -->
        <?php mostrarResumenDetalladoMEODashboard($resumenMes, $nombre_mes); ?>
    </div>
    <?php
}

function mostrarDashboardNOR($conn_mysql, $zona_seleccionada) {
    // Obtener datos
    $nombre_zona = obtenerNombreZonaDashboard($conn_mysql, $zona_seleccionada);
    $stats = obtenerEstadisticasNORDashboard($conn_mysql, $zona_seleccionada);
    $ultimasRecolecciones = obtenerUltimasRecoleccionesNORDashboard($conn_mysql, $zona_seleccionada, 5);
    $recoleccionesRechazadas = obtenerRecoleccionesRechazadasNORDashboard($conn_mysql, $zona_seleccionada, 10);
    
    // Alertas del sistema
    $alertas = obtenerAlertasSistemaNORDashboard($conn_mysql, $zona_seleccionada);
    
    ?>
    <div class="container py-4" data-zona-tipo="NOR">
        <!-- Header -->
        <?php mostrarHeaderNORDashboard($nombre_zona); ?>
        
        <!-- Métricas principales -->
        <?php mostrarMetricasPrincipalesNORDashboard($stats); ?>
        
        <!-- Alertas del sistema -->
        <?php if ($alertas['total'] > 0): ?>
            <?php mostrarAlertasSistemaNORDashboard($alertas); ?>
        <?php endif; ?>
        
        <!-- Facturas rechazadas -->
        <?php if (!empty($recoleccionesRechazadas)): ?>
            <?php mostrarFacturasRechazadasNORDashboard($recoleccionesRechazadas); ?>
        <?php endif; ?>
        
        <!-- Estado de recolecciones -->
        <?php mostrarEstadoRecoleccionesNORDashboard($stats); ?>
        
        <!-- Reporte financiero - SE MANTIENE TU FORMATO ORIGINAL -->
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

        <!-- Resultado del reporte (AJAX) - ESTO ES LO QUE FALTABA -->
        <div class="row mb-4" id="res1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <h5 class="text-muted">Generando reporte...</h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Últimas recolecciones -->
        <?php mostrarUltimasRecoleccionesNORDashboard($ultimasRecolecciones); ?>
        
        <!-- Resumen del mes -->
        <?php mostrarResumenMesNORDashboard($stats); ?>
    </div>
    
    <!-- SCRIPT PARA EL REPORTE FINANCIERO - SE MANTIENE TU SCRIPT ORIGINAL -->
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
    
    // EJECUTAR AUTOMÁTICAMENTE AL CARGAR LA PÁGINA
    document.addEventListener('DOMContentLoaded', function() {
        pre1();
    });
    </script>
    <?php
}

// ============================================================================
// FUNCIONES DE DATOS MEO (CORREGIDAS)
// ============================================================================

function obtenerNombreZonaDashboard($conn_mysql, $zona_id) {
    $query = "SELECT nom FROM zonas WHERE id_zone = ?";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("s", $zona_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['nom'] ?? '';
}

function obtenerEstadisticasMEODashboard($conn_mysql, $zona_id, $mes) {
    $stats = [
        'total_captaciones' => 0,
        'total_ventas' => 0,
        'total_kilos_captados' => 0,
        'total_kilos_vendidos' => 0,
        'costo_total_captaciones' => 0,
        'ingreso_total_ventas' => 0,
        'utilidad_neta' => 0,
        'costo_por_kilo' => 0,
        'precio_venta_por_kilo' => 0,
        'margen_por_kilo' => 0
    ];
    
    // CORREGIDO: Consultas para captaciones con cálculo de kilos
    $captaciones_query = "
        SELECT 
            COUNT(DISTINCT c.id_captacion) as total,
            COALESCE(SUM(cd.total_kilos), 0) as kilos,
            COALESCE(SUM(cd.total_kilos * COALESCE(pc.precio, 0)), 0) as costo
        FROM captacion c
        LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
        LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
        WHERE c.zona = ? 
        AND c.status = 1 
        AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = ?
    ";
    
    $ventas_query = "
        SELECT 
            COUNT(DISTINCT v.id_venta) as total,
            COALESCE(SUM(vd.total_kilos), 0) as kilos,
            COALESCE(SUM(vd.total_kilos * COALESCE(pv.precio, 0)), 0) as ingreso
        FROM ventas v
        LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
        LEFT JOIN precios pv ON vd.id_pre_venta = pv.id_precio
        WHERE v.zona = ? 
        AND v.status = 1 
        AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = ?
    ";
    
    // Ejecutar consulta de captaciones
    $stmt = $conn_mysql->prepare($captaciones_query);
    $stmt->bind_param("ss", $zona_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stats['total_captaciones'] = (int)($data['total'] ?? 0);
    $stats['total_kilos_captados'] = (float)($data['kilos'] ?? 0);
    $stats['costo_total_captaciones'] = (float)($data['costo'] ?? 0);
    
    // Ejecutar consulta de ventas
    $stmt = $conn_mysql->prepare($ventas_query);
    $stmt->bind_param("ss", $zona_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stats['total_ventas'] = (int)($data['total'] ?? 0);
    $stats['total_kilos_vendidos'] = (float)($data['kilos'] ?? 0);
    $stats['ingreso_total_ventas'] = (float)($data['ingreso'] ?? 0);
    
    // Cálculos adicionales
    $stats['utilidad_neta'] = $stats['ingreso_total_ventas'] - $stats['costo_total_captaciones'];
    $stats['costo_por_kilo'] = $stats['total_kilos_captados'] > 0 ? $stats['costo_total_captaciones'] / $stats['total_kilos_captados'] : 0;
    $stats['precio_venta_por_kilo'] = $stats['total_kilos_vendidos'] > 0 ? $stats['ingreso_total_ventas'] / $stats['total_kilos_vendidos'] : 0;
    $stats['margen_por_kilo'] = $stats['precio_venta_por_kilo'] - $stats['costo_por_kilo'];
    
    return $stats;
}

function obtenerUltimasCaptacionesDashboard($conn_mysql, $zona_id, $limit = 5, $mes = null) {
    $where_mes = $mes ? "AND DATE_FORMAT(c.fecha_captacion, '%Y-%m') = ?" : "";
    $params = [$zona_id];
    if ($mes) $params[] = $mes;
    
    // CORREGIDO: Consulta con cálculo correcto de kilos
    $query = "
        SELECT 
            c.id_captacion, 
            CONCAT('C-', z.cod, '-', DATE_FORMAT(c.fecha_captacion, '%y%m'), LPAD(c.folio, 4, '0')) as folio,
            DATE_FORMAT(c.fecha_captacion, '%d/%m/%Y') as fecha,
            p.rs as proveedor,
            COALESCE(SUM(cd.total_kilos), 0) as kilos,
            COALESCE(SUM(cd.total_kilos * COALESCE(pc.precio, 0)), 0) as costo
        FROM captacion c
        LEFT JOIN zonas z ON c.zona = z.id_zone
        LEFT JOIN proveedores p ON c.id_prov = p.id_prov
        LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
        LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
        WHERE c.zona = ? 
        AND c.status = 1 
        $where_mes
        GROUP BY c.id_captacion, c.folio, c.fecha_captacion, z.cod, p.rs
        ORDER BY c.fecha_captacion DESC
        LIMIT ?
    ";
    
    $params[] = $limit;
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $captaciones = [];
    while ($row = $result->fetch_assoc()) {
        $captaciones[] = $row;
    }
    
    return $captaciones;
}

function obtenerUltimasVentasDashboard($conn_mysql, $zona_id, $limit = 5, $mes = null) {
    $where_mes = $mes ? "AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = ?" : "";
    $params = [$zona_id];
    if ($mes) $params[] = $mes;
    
    // CORREGIDO: Consulta con cálculo correcto de kilos
    $query = "
        SELECT 
            v.id_venta,
            CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio, 4, '0')) as folio,
            DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha,
            c.nombre as cliente,
            COALESCE(SUM(vd.total_kilos), 0) as kilos,
            COALESCE(SUM(vd.total_kilos * COALESCE(pv.precio, 0)), 0) as total
        FROM ventas v
        LEFT JOIN zonas z ON v.zona = z.id_zone
        LEFT JOIN clientes c ON v.id_cliente = c.id_cli
        LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
        LEFT JOIN precios pv ON vd.id_pre_venta = pv.id_precio
        WHERE v.zona = ? 
        AND v.status = 1 
        $where_mes
        GROUP BY v.id_venta, v.folio, v.fecha_venta, z.cod, c.nombre
        ORDER BY v.fecha_venta DESC
        LIMIT ?
    ";
    
    $params[] = $limit;
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    
    return $ventas;
}

function obtenerResumenMensualMEODashboard($conn_mysql, $zona_id, $mes = null) {
    $resumen = [
        'entradas_captaciones' => 0,
        'costo_entradas' => 0,
        'salidas_ventas' => 0,
        'ingreso_salidas' => 0,
        'balance' => 0,
        'promedio_captaciones_dia' => 0,
        'promedio_ventas_dia' => 0,
        'kilos_promedio_captacion' => 0,
        'valor_promedio_venta' => 0
    ];
    
    // Obtener estadísticas básicas
    $stats = obtenerEstadisticasMEODashboard($conn_mysql, $zona_id, $mes);
    
    // Calcular días del mes
    $mes_actual = $mes ?? date('Y-m');
    $fecha = DateTime::createFromFormat('Y-m', $mes_actual);
    $dias_mes = $fecha ? $fecha->format('t') : 30;
    
    $resumen['entradas_captaciones'] = $stats['total_captaciones'];
    $resumen['costo_entradas'] = $stats['costo_total_captaciones'];
    $resumen['salidas_ventas'] = $stats['total_ventas'];
    $resumen['ingreso_salidas'] = $stats['ingreso_total_ventas'];
    $resumen['balance'] = $stats['utilidad_neta'];
    
    // Calcular promedios diarios
    $resumen['promedio_captaciones_dia'] = $dias_mes > 0 ? round($stats['total_captaciones'] / $dias_mes, 1) : 0;
    $resumen['promedio_ventas_dia'] = $dias_mes > 0 ? round($stats['total_ventas'] / $dias_mes, 1) : 0;
    
    // Calcular promedios por transacción
    $resumen['kilos_promedio_captacion'] = $stats['total_captaciones'] > 0 ? round($stats['total_kilos_captados'] / $stats['total_captaciones'], 2) : 0;
    $resumen['valor_promedio_venta'] = $stats['total_ventas'] > 0 ? round($stats['ingreso_total_ventas'] / $stats['total_ventas'], 2) : 0;
    
    return $resumen;
}

// ============================================================================
// FUNCIONES DE DATOS NOR
// ============================================================================

function obtenerEstadisticasNORDashboard($conn_mysql, $zona_id) {
    $stats = [
        'proveedores_activos' => 0,
        'clientes_activos' => 0,
        'transportes_activos' => 0,
        'productos_activos' => 0,
        'recolecciones_mes' => 0,
        'recolecciones_completas_mes' => 0,
        'recolecciones_pendientes' => 0
    ];
    
    // Consultas básicas - ajustar según tu estructura real
    $consultas = [
        'proveedores_activos' => "SELECT COUNT(*) as total FROM proveedores WHERE status = 1 AND zona = ?",
        'clientes_activos' => "SELECT COUNT(*) as total FROM clientes WHERE status = 1 AND zona = ?",
        'transportes_activos' => "SELECT COUNT(*) as total FROM transportes WHERE status = 1 AND zona = ?",
        'productos_activos' => "SELECT COUNT(*) as total FROM productos WHERE status = 1 AND zona = ?",
        'recolecciones_mes' => "SELECT COUNT(*) as total FROM recoleccion WHERE status = 1 AND zona = ? AND MONTH(fecha_r) = MONTH(CURRENT_DATE()) AND YEAR(fecha_r) = YEAR(CURRENT_DATE())",
        'recolecciones_completas_mes' => "SELECT COUNT(*) as total FROM recoleccion WHERE status = 1 AND remision IS NOT NULL AND factura_fle IS NOT NULL AND MONTH(fecha_r) = MONTH(CURRENT_DATE()) AND YEAR(fecha_r) = YEAR(CURRENT_DATE()) AND zona = ?",
        'recolecciones_pendientes' => "SELECT COUNT(*) as total FROM recoleccion WHERE status = 1 AND (remision IS NULL OR factura_fle IS NULL) AND zona = ?"
    ];
    
    foreach ($consultas as $key => $sql) {
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param("s", $zona_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stats[$key] = (int)($data['total'] ?? 0);
    }
    
    return $stats;
}

function obtenerUltimasRecoleccionesNORDashboard($conn_mysql, $zona_id, $limit = 5) {
    $query = "
        SELECT r.id_recol, r.folio, r.fecha_r, p.rs as proveedor, 
               c.nombre as cliente, t.razon_so as fletero, pr.nom_pro as producto
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN clientes c ON r.id_cli = c.id_cli
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
        WHERE r.zona = ? AND r.status = 1
        ORDER BY r.fecha_r DESC
        LIMIT ?
    ";
    
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("si", $zona_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recolecciones = [];
    while ($row = $result->fetch_assoc()) {
        $recolecciones[] = $row;
    }
    
    return $recolecciones;
}

function obtenerRecoleccionesRechazadasNORDashboard($conn_mysql, $zona_id, $limit = 10) {
    $query = "
        SELECT r.id_recol, r.folio, r.fecha_r, p.rs as proveedor, 
               t.razon_so as fletero, r.FacFexis as contador_rechazos
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        WHERE r.zona = ? AND r.status = 1 AND r.factura_fle IS NULL AND r.FacFexis > 0
        ORDER BY r.FacFexis DESC
        LIMIT ?
    ";
    
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("si", $zona_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rechazadas = [];
    while ($row = $result->fetch_assoc()) {
        $rechazadas[] = $row;
    }
    
    return $rechazadas;
}

function obtenerAlertasSistemaNORDashboard($conn_mysql, $zona_id) {
    $alertas = [
        'precios_caducando' => 0,
        'productos_sin_precio' => 0,
        'fleteros_sin_correo' => 0,
        'total' => 0
    ];
    
    // Consultas simplificadas
    $consultas = [
        'precios_caducando' => "SELECT COUNT(*) as total FROM precios WHERE (tipo = 'c' or tipo = 'v') AND status = 1 AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)",
        'fleteros_sin_correo' => "SELECT COUNT(*) as total FROM transportes WHERE status = 1 AND zona = ? AND (correo IS NULL OR correo = '' OR correo = '0')"
    ];
    
    foreach ($consultas as $key => $sql) {
        $stmt = $conn_mysql->prepare($sql);
        if (strpos($sql, '?') !== false) {
            $stmt->bind_param("s", $zona_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $alertas[$key] = (int)($data['total'] ?? 0);
    }
    
    $alertas['total'] = array_sum(array_slice($alertas, 0, -1)); // Sumar todas excepto 'total'
    
    return $alertas;
}

// ============================================================================
// COMPONENTES VISUALES MEO
// ============================================================================

function mostrarHeaderMEODashboard($nombre_zona, $mes_param, $nombre_mes, $mes_actual) {
    ?>
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
                                <div class="dropdown">
                                    <button class="btn btn-light dropdown-toggle" type="button" id="mesDropdown" data-bs-toggle="dropdown">
                                        <i class="bi bi-calendar-month me-2"></i><?= htmlspecialchars($nombre_mes) ?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="mesDropdown">
                                        <?php for ($i = 0; $i < 12; $i++): 
                                            $mes_opcion = clone $mes_actual;
                                            $mes_opcion->modify("-$i months");
                                            $mes_valor = $mes_opcion->format('Y-m');
                                            $mes_nombre = $mes_opcion->format('F Y');
                                            $activo = ($mes_valor === $mes_param) ? 'active' : '';
                                        ?>
                                            <li><a class="dropdown-item <?= $activo ?>" href="?mes=<?= $mes_valor ?>"><?= $mes_nombre ?></a></li>
                                        <?php endfor; ?>
                                    </ul>
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

function mostrarMetricasPrincipalesMEODashboard($stats) {
    $metricas = [
        ['titulo' => 'Total Captaciones', 'valor' => $stats['total_captaciones'], 'icono' => 'inbox-fill', 'color' => 'primary', 'link' => 'captacion'],
        ['titulo' => 'Total Ventas', 'valor' => $stats['total_ventas'], 'icono' => 'cart-check', 'color' => 'success', 'link' => 'ventas'],
        ['titulo' => 'Material Captado', 'valor' => number_format($stats['total_kilos_captados'], 2), 'icono' => 'boxes', 'color' => 'warning', 'link' => 'captacion'],
        ['titulo' => 'Material Vendido', 'valor' => number_format($stats['total_kilos_vendidos'], 2), 'icono' => 'truck', 'color' => 'info', 'link' => 'ventas']
    ];
    ?>
    <div class="row mb-4 g-3">
        <?php foreach ($metricas as $metrica): ?>
        <div class="col-xl-3 col-md-6">
            <a href="?p=<?= $metrica['link'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow h-100 hover-lift">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2"><?= $metrica['titulo'] ?></h6>
                                <h2 class="mb-1 text-<?= $metrica['color'] ?>"><?= $metrica['valor'] ?></h2>
                                <p class="text-muted small mb-0">Mes seleccionado</p>
                            </div>
                            <div class="bg-<?= $metrica['color'] ?>-subtle p-3 rounded-circle">
                                <i class="bi bi-<?= $metrica['icono'] ?> text-<?= $metrica['color'] ?> fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function mostrarResumenFinancieroMEODashboard($stats, $nombre_mes) {
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Resumen Financiero</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border border-primary-subtle bg-primary-subtle hover-lift">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-cash-coin text-primary fs-1"></i>
                                    </div>
                                    <h3 class="text-primary mb-2">$<?= number_format($stats['costo_total_captaciones'], 2) ?></h3>
                                    <p class="text-primary-emphasis mb-0">Costo Captaciones</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border border-success-subtle bg-success-subtle hover-lift">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-currency-dollar text-success fs-1"></i>
                                    </div>
                                    <h3 class="text-success mb-2">$<?= number_format($stats['ingreso_total_ventas'], 2) ?></h3>
                                    <p class="text-success-emphasis mb-0">Ingreso Ventas</p>
                                </div>
                            </div>
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

function mostrarActividadRecienteMEODashboard($captaciones, $ventas, $nombre_mes) {
    ?>
    <div class="row mb-4">
        <!-- Últimas Captaciones -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-subtle rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:46px;height:46px;">
                            <i class="bi bi-inbox text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">Últimas Captaciones</h6>
                            <small class="text-muted">Mes: <?= htmlspecialchars($nombre_mes) ?></small>
                        </div>
                    </div>
                    <div>
                        <button onclick="window.open('?p=captacion', '_blank')" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list me-1"></i> Ver todas
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($captaciones)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($captaciones as $captacion): ?>
                            <div class="p-2 rounded-3 my-2 py-3 border border-primary-subtle d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary-subtle rounded p-2 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-file-earmark-check text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($captacion['folio']) ?></div>
                                        <div class="text-muted small"><?= $captacion['fecha'] ?> · <?= htmlspecialchars($captacion['proveedor']) ?></div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="mb-1">
                                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                                            <i class="bi bi-bag-fill me-1"></i> <?= number_format($captacion['kilos'], 2) ?> kg
                                        </span>
                                    </div>
                                    <div class="fw-semibold text-success mb-2">$<?= number_format($captacion['costo'], 2) ?></div>
                                    <button onclick="window.open('?p=V_captacion&id=<?= $captacion['id_captacion'] ?>', '_blank')" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                            </div>
                            <p class="mb-1 text-muted">No hay captaciones en <?= htmlspecialchars($nombre_mes) ?></p>
                            <small class="text-muted">Cuando se registren captaciones, aparecerán aquí.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas Ventas -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-subtle rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:46px;height:46px;">
                            <i class="bi bi-cart text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">Últimas Ventas</h6>
                            <small class="text-muted">Mes: <?= htmlspecialchars($nombre_mes) ?></small>
                        </div>
                    </div>
                    <div>
                        <button onclick="window.open('?p=ventas', '_blank')" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-list me-1"></i> Ver todas
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($ventas)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($ventas as $venta): ?>
                            <div class="p-2 rounded-3 my-2 py-3 border border-success-subtle d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-success-subtle rounded p-2 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-receipt text-success fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($venta['folio']) ?></div>
                                        <div class="text-muted small"><?= $venta['fecha'] ?> · <?= htmlspecialchars($venta['cliente']) ?></div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="mb-1">
                                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                                            <i class="bi bi-box-seam me-1"></i> <?= number_format($venta['kilos'], 2) ?> kg
                                        </span>
                                    </div>
                                    <div class="fw-semibold text-success mb-2">$<?= number_format($venta['total'], 2) ?></div>
                                    <button onclick="window.open('?p=V_venta&id=<?= $venta['id_venta'] ?>', '_blank')" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-cart display-4 text-muted"></i>
                            </div>
                            <p class="mb-1 text-muted">No hay ventas en <?= htmlspecialchars($nombre_mes) ?></p>
                            <small class="text-muted">Las ventas registradas aparecerán aquí.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function mostrarResumenDetalladoMEODashboard($resumenMes, $nombre_mes) {
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-calendar2-month me-2 text-primary"></i>Resumen Detallado</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-primary-subtle hover-lift">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-box-seam fs-4"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Entradas (Captaciones)</small>
                                        <h4 class="mb-0 text-primary fw-bold"><?= $resumenMes['entradas_captaciones'] ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-success-subtle hover-lift">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-cart-check fs-4"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Salidas (Ventas)</small>
                                        <h4 class="mb-0 text-success fw-bold"><?= $resumenMes['salidas_ventas'] ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <?php $balClass = $resumenMes['balance'] >= 0 ? 'success' : 'danger'; ?>
                            <div class="card h-100 border-0 bg-<?= $balClass ?>-subtle hover-lift">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="bg-<?= $balClass ?> text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-graph-up-arrow fs-4"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Balance Neto</small>
                                        <h4 class="mb-0 text-<?= $balClass ?> fw-bold">$<?= number_format($resumenMes['balance'], 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-3 text-center hover-lift">
                                <div class="mb-2">
                                    <i class="bi bi-calendar2-day fs-3 text-primary"></i>
                                </div>
                                <div class="fs-5 fw-semibold text-primary"><?= $resumenMes['promedio_captaciones_dia'] ?></div>
                                <small class="text-muted">Captaciones / día</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-3 text-center hover-lift">
                                <div class="mb-2">
                                    <i class="bi bi-speedometer2 fs-3 text-success"></i>
                                </div>
                                <div class="fs-5 fw-semibold text-success"><?= $resumenMes['promedio_ventas_dia'] ?></div>
                                <small class="text-muted">Ventas / día</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-3 text-center hover-lift">
                                <div class="mb-2">
                                    <i class="bi bi-boxes fs-3 text-warning"></i>
                                </div>
                                <div class="fs-5 fw-semibold text-warning"><?= number_format($resumenMes['kilos_promedio_captacion'], 2) ?> kg</div>
                                <small class="text-muted">Kilos / captación</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-3 text-center hover-lift">
                                <div class="mb-2">
                                    <i class="bi bi-currency-dollar fs-3 text-info"></i>
                                </div>
                                <div class="fs-5 fw-semibold text-info">$<?= number_format($resumenMes['valor_promedio_venta'], 2) ?></div>
                                <small class="text-muted">Valor promedio venta</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// ============================================================================
// COMPONENTES VISUALES NOR
// ============================================================================

function mostrarHeaderNORDashboard($nombre_zona) {
    ?>
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
    <?php
}

function mostrarMetricasPrincipalesNORDashboard($stats) {
    $metricas = [
        ['titulo' => 'Proveedores', 'valor' => $stats['proveedores_activos'], 'icono' => 'building', 'color' => 'primary', 'link' => 'proveedores'],
        ['titulo' => 'Clientes', 'valor' => $stats['clientes_activos'], 'icono' => 'people-fill', 'color' => 'success', 'link' => 'clientes'],
        ['titulo' => 'Recolecciones', 'valor' => $stats['recolecciones_mes'], 'icono' => 'truck', 'color' => 'warning', 'link' => 'recoleccion'],
        ['titulo' => 'Productos', 'valor' => $stats['productos_activos'], 'icono' => 'box-seam', 'color' => 'info', 'link' => 'productos']
    ];
    ?>
    <div class="row mb-4 g-3">
        <?php foreach ($metricas as $metrica): ?>
        <div class="col-xl-3 col-md-6">
            <a href="?p=<?= $metrica['link'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow h-100 hover-lift">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2"><?= $metrica['titulo'] ?></h6>
                                <h2 class="mb-1 text-<?= $metrica['color'] ?>"><?= $metrica['valor'] ?></h2>
                                <p class="text-muted small mb-0">Activos en sistema</p>
                            </div>
                            <div class="bg-<?= $metrica['color'] ?>-subtle p-3 rounded-circle">
                                <i class="bi bi-<?= $metrica['icono'] ?> text-<?= $metrica['color'] ?> fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function mostrarAlertasSistemaNORDashboard($alertas) {
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Alertas del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if ($alertas['precios_caducando'] > 0): ?>
                        <div class="col-md-4">
                            <div class="card border border-warning-subtle bg-warning-subtle hover-lift">
                                <div class="card-body text-center p-3">
                                    <h3 class="text-warning mb-2"><?= $alertas['precios_caducando'] ?></h3>
                                    <p class="text-warning-emphasis mb-0">Precios por Caducar</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($alertas['fleteros_sin_correo'] > 0): ?>
                        <div class="col-md-4">
                            <div class="card border border-info-subtle bg-info-subtle hover-lift">
                                <div class="card-body text-center p-3">
                                    <h3 class="text-info mb-2"><?= $alertas['fleteros_sin_correo'] ?></h3>
                                    <p class="text-info-emphasis mb-0">Fleteros sin Correo</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function mostrarFacturasRechazadasNORDashboard($rechazadas) {
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3 bg-danger-subtle">
                    <h5 class="mb-0 text-danger-emphasis"><i class="bi bi-exclamation-octagon-fill me-2"></i>Facturas Rechazadas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Proveedor</th>
                                    <th>Fletero</th>
                                    <th>Rechazos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rechazadas as $rechazada): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rechazada['folio']) ?></td>
                                    <td><?= htmlspecialchars($rechazada['proveedor']) ?></td>
                                    <td><?= htmlspecialchars($rechazada['fletero']) ?></td>
                                    <td><span class="badge bg-danger"><?= $rechazada['contador_rechazos'] ?></span></td>
                                    <td>
                                        <a href="?p=V_recoleccion&id=<?= $rechazada['id_recol'] ?>" class="btn btn-sm btn-danger">
                                            Corregir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function mostrarEstadoRecoleccionesNORDashboard($stats) {
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2 text-primary"></i>Estado de Recolecciones</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border border-success-subtle bg-success-subtle hover-lift">
                                <div class="card-body text-center p-4">
                                    <h3 class="text-success mb-2"><?= $stats['recolecciones_completas_mes'] ?></h3>
                                    <p class="text-success-emphasis mb-0">Completadas (Mes)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border border-warning-subtle bg-warning-subtle hover-lift">
                                <div class="card-body text-center p-4">
                                    <h3 class="text-warning mb-2"><?= $stats['recolecciones_pendientes'] ?></h3>
                                    <p class="text-warning-emphasis mb-0">Pendientes</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border border-info-subtle bg-info-subtle hover-lift">
                                <div class="card-body text-center p-4">
                                    <h3 class="text-info mb-2"><?= $stats['transportes_activos'] ?></h3>
                                    <p class="text-info-emphasis mb-0">Fleteros Activos</p>
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

function mostrarUltimasRecoleccionesNORDashboard($recolecciones) {
    ?>
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
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recolecciones)): ?>
                                    <?php foreach ($recolecciones as $recoleccion): ?>
                                    <tr class="hover-lift">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary-subtle p-2 rounded me-3">
                                                    <i class="bi bi-clipboard-data text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($recoleccion['folio']) ?></strong><br>
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
                                        <td class="text-center">
                                            <a href="?p=V_recoleccion&id=<?= $recoleccion['id_recol'] ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Ver detalles">
                                               <i class="bi bi-eye me-1"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
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
            </div>
        </div>
    </div>
    <?php
}

function mostrarResumenMesNORDashboard($stats) {
    ?>
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
    <?php
}