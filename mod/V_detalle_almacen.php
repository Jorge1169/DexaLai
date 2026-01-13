<?php
// V_detalle_almacen.php - Detalle de almacén (VERSIÓN MEJORADA CON DISEÑO DASHBOARD)

// Obtener ID del almacén
$id_almacen = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_almacen <= 0) {
    alert("ID de almacén no válido", 0, "almacenes_info");
    exit;
}

// Obtener información del almacén
$sql_almacen = "SELECT a.*, z.PLANTA as nombre_zona, 
                       (SELECT COUNT(*) FROM direcciones WHERE id_alma = a.id_alma AND status = 1) as total_bodegas
                FROM almacenes a
                LEFT JOIN zonas z ON a.zona = z.id_zone
                WHERE a.id_alma = ? AND a.status = 1";
$stmt_almacen = $conn_mysql->prepare($sql_almacen);
$stmt_almacen->bind_param('i', $id_almacen);
$stmt_almacen->execute();
$result_almacen = $stmt_almacen->get_result();

if (!$result_almacen || $result_almacen->num_rows == 0) {
    alert("Almacén no encontrado", 0, "almacenes");
    exit;
}

$almacen = $result_almacen->fetch_assoc();

// Obtener mes y año para el reporte
$mes_actual = date('n');
$anio_actual = date('Y');
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m');

if ($fecha_seleccionada && preg_match('/^(\d{4})-(\d{2})$/', $fecha_seleccionada, $matches)) {
    $anio_actual = intval($matches[1]);
    $mes_actual = intval($matches[2]);
}

$mes_nombre = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Obtener todos los meses disponibles con movimientos para este almacén
$sql_meses_disponibles = "SELECT DISTINCT 
                          DATE_FORMAT(mi.created_at, '%Y-%m') as mes_anio,
                          DATE_FORMAT(mi.created_at, '%M %Y') as mes_nombre,
                          YEAR(mi.created_at) as anio,
                          MONTH(mi.created_at) as mes
                          FROM movimiento_inventario mi
                          LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                          WHERE ib.id_alma = ?
                          ORDER BY mes_anio DESC";
$stmt_meses = $conn_mysql->prepare($sql_meses_disponibles);
$stmt_meses->bind_param('i', $id_almacen);
$stmt_meses->execute();
$meses_disponibles = $stmt_meses->get_result();

// Crear array de meses disponibles para el datalist
$meses_options = [];
while ($mes = $meses_disponibles->fetch_assoc()) {
    $meses_options[] = [
        'value' => $mes['mes_anio'],
        'label' => $mes['mes_nombre']
    ];
}

// Función para obtener inventario acumulado hasta un mes específico (INCLUYE VENTAS)
function obtenerInventarioAcumulado($id_alma, $mes, $anio, $conn_mysql) {
    // Calcular fecha límite (último día del mes seleccionado)
    $fecha_limite = date("$anio-$mes-t 23:59:59");
    
    // Obtener inventario acumulado hasta esa fecha
    $sql = "SELECT ib.id_prod, p.cod, p.nom_pro,
                   SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                            THEN mi.granel_kilos_movimiento ELSE -mi.granel_kilos_movimiento END) as granel_acumulado,
                   SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                            THEN mi.pacas_cantidad_movimiento ELSE -mi.pacas_cantidad_movimiento END) as pacas_cant_acumulado,
                   SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                            THEN mi.pacas_kilos_movimiento ELSE -mi.pacas_kilos_movimiento END) as pacas_kilos_acumulado,
                   COUNT(DISTINCT CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_venta IS NOT NULL 
                                       THEN mi.id_venta END) as total_ventas,
                   COUNT(DISTINCT CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_captacion IS NOT NULL 
                                       THEN mi.id_captacion END) as total_salidas_captacion
            FROM inventario_bodega ib
            LEFT JOIN productos p ON ib.id_prod = p.id_prod
            LEFT JOIN movimiento_inventario mi ON ib.id_inventario = mi.id_inventario
            WHERE ib.id_alma = ?
              AND DATE(mi.created_at) <= ?
            GROUP BY ib.id_prod, p.cod, p.nom_pro
            HAVING granel_acumulado > 0 OR pacas_cant_acumulado > 0 OR pacas_kilos_acumulado > 0
            ORDER BY p.cod";
    
    $stmt = $conn_mysql->prepare($sql);
    $stmt->bind_param('is', $id_alma, $fecha_limite);
    $stmt->execute();
    return $stmt->get_result();
}

// Obtener inventario actual (acumulado hasta hoy)
$inventario_actual = obtenerInventarioAcumulado($id_almacen, $mes_actual, $anio_actual, $conn_mysql);

// Obtener movimientos del mes seleccionado
$primer_dia_mes = date("$anio_actual-$mes_actual-01");
$ultimo_dia_mes = date("$anio_actual-$mes_actual-t");

$sql_movimientos = "SELECT mi.*, p.cod as cod_producto, p.nom_pro as nombre_producto,
                           c.folio as folio_captacion,
                           v.folio as folio_venta,
                           cli.cod as cod_cliente, cli.nombre as nombre_cliente,
                           alm.cod as cod_almacen, alm.nombre as nombre_almacen,
                           DATE_FORMAT(mi.created_at, '%d/%m/%Y %H:%i') as fecha_formateada,
                           CASE 
                                WHEN mi.tipo_movimiento = 'entrada' THEN 'ENTRADA'
                                WHEN mi.tipo_movimiento = 'ajuste' THEN 'AJUSTE'
                                WHEN mi.tipo_movimiento = 'conversion' THEN 'CONVERSIÓN'
                                WHEN mi.tipo_movimiento = 'salida' AND mi.id_venta IS NOT NULL THEN 'SALIDA (VENTA)'
                                WHEN mi.tipo_movimiento = 'salida' AND mi.id_captacion IS NOT NULL THEN 'SALIDA (CAPTACIÓN)'
                                WHEN mi.tipo_movimiento = 'salida' AND mi.observaciones LIKE '%Transformación%' THEN 'SALIDA (TRANSFORMACIÓN)'
                                ELSE 'SALIDA'
                            END as tipo_movimiento_detalle
                    FROM movimiento_inventario mi
                    LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                    LEFT JOIN productos p ON ib.id_prod = p.id_prod
                    LEFT JOIN captacion c ON mi.id_captacion = c.id_captacion
                    LEFT JOIN ventas v ON mi.id_venta = v.id_venta
                    LEFT JOIN clientes cli ON v.id_cliente = cli.id_cli
                    LEFT JOIN almacenes alm ON v.id_alma = alm.id_alma
                    WHERE ib.id_alma = ?
                      AND DATE(mi.created_at) BETWEEN ? AND ?
                    ORDER BY mi.created_at DESC
                    LIMIT 100";
$stmt_movimientos = $conn_mysql->prepare($sql_movimientos);
$stmt_movimientos->bind_param('iss', $id_almacen, $primer_dia_mes, $ultimo_dia_mes);
$stmt_movimientos->execute();
$movimientos_mes = $stmt_movimientos->get_result();

// Obtener totales del mes
$sql_totales_mes = "SELECT 
                    -- Entradas (captaciones)
                    SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                             THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_entrada,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_captacion IS NOT NULL
                             THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_salida_captacion,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_venta IS NOT NULL
                             THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_salida_venta,
                    SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                             THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_entrada_kilos,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_captacion IS NOT NULL
                             THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_salida_kilos_captacion,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' AND mi.id_venta IS NOT NULL
                             THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_salida_kilos_venta
                    FROM movimiento_inventario mi
                    LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                    WHERE ib.id_alma = ?
                      AND DATE(mi.created_at) BETWEEN ? AND ?";
$stmt_totales = $conn_mysql->prepare($sql_totales_mes);
$stmt_totales->bind_param('iss', $id_almacen, $primer_dia_mes, $ultimo_dia_mes);
$stmt_totales->execute();
$totales_mes = $stmt_totales->get_result()->fetch_assoc();

// Calcular totales combinados
$granel_salida_total = ($totales_mes['granel_salida_captacion'] ?? 0) + ($totales_mes['granel_salida_venta'] ?? 0);
$pacas_salida_kilos_total = ($totales_mes['pacas_salida_kilos_captacion'] ?? 0) + ($totales_mes['pacas_salida_kilos_venta'] ?? 0);

// Obtener bodegas asociadas
$sql_bodegas = "SELECT * FROM direcciones 
                WHERE id_alma = ? AND status = 1 
                ORDER BY noma";
$stmt_bodegas = $conn_mysql->prepare($sql_bodegas);
$stmt_bodegas->bind_param('i', $id_almacen);
$stmt_bodegas->execute();
$bodegas = $stmt_bodegas->get_result();

$hay_bodegas = $bodegas && $bodegas->num_rows > 0;

// Calcular inventario total actual
$sql_total_actual = "SELECT SUM(total_kilos_disponible) as total 
                    FROM inventario_bodega 
                    WHERE id_alma = ?";
$stmt_total = $conn_mysql->prepare($sql_total_actual);
$stmt_total->bind_param('i', $id_almacen);
$stmt_total->execute();
$total_actual = $stmt_total->get_result()->fetch_assoc()['total'];

// Contador de movimientos
$count_movimientos = $movimientos_mes ? $movimientos_mes->num_rows : 0;
?>

<!-- ESTILOS ADICIONALES PARA MEJORAR EL DISEÑO -->
<style>
/* Estilos para dashboard */
.card-dashboard {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.card-dashboard:hover {
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}


.stats-card {
    border-radius: 10px;
    border: none;
    overflow: hidden;
    position: relative;
    min-height: 120px;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: currentColor;
    opacity: 0.3;
}

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.8;
    position: absolute;
    right: 15px;
    bottom: 10px;
}

.stats-value {
    font-size: 1.8rem;
    font-weight: 700;
}

.stats-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}

.table-modern {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}


.table-modern tbody tr {
    transition: all 0.2s ease;
}

.table-modern tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
    transform: translateX(2px);
}

.badge-pill-modern {
    border-radius: 20px;
    padding: 0.35em 0.9em;
    font-weight: 500;
    font-size: 0.8em;
}

.tabs-modern .nav-link {
    border: none;
    border-radius: 8px 8px 0 0;
    padding: 0.8rem 1.5rem;
    font-weight: 500;
    color: #6c757d;
    margin-right: 2px;
    transition: all 0.3s;
}

.tabs-modern .nav-link:hover {
    color: #495057;
    background-color: rgba(108, 117, 125, 0.1);
}

.tabs-modern .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 5px rgba(102, 126, 234, 0.4);
}

.input-group-modern {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.btn-modern {
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
}

.alert-modern {
    border: none;
    border-radius: 8px;
    border-left: 4px solid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.date-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(245, 87, 108, 0.3);
}
</style>

<div class="container mt-4">
    <!-- HEADER DEL DASHBOARD -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-building me-2"></i>
                        Detalle del Almacén
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="?p=almacenes_info">Almacenes</a></li>
                            <li class="breadcrumb-item active">Detalle</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="?p=almacenes_info" class="btn btn-modern btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Volver a Lista
                    </a>
                    <button class="btn btn-modern btn-outline-primary ms-2" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i> Imprimir Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RESUMEN PRINCIPAL DEL ALMACÉN -->
    <div class="row mb-4">
        <!-- Tarjeta de Información del Almacén -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header encabezado-col d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-info-circle me-2"></i> Información del Almacén
                    </h5>
                    <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($almacen['cod']) ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Nombre del Almacén</h6>
                                <h4 class="fw-bold"><?= htmlspecialchars($almacen['nombre']) ?></h4>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Ubicación / Zona</h6>
                                <p class="fs-5">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <?= htmlspecialchars($almacen['nombre_zona']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <h6 class="text-muted mb-1">Bodegas Activas</h6>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-boxes text-info me-2 fs-4"></i>
                                        <h3 class="fw-bold mb-0"><?= $almacen['total_bodegas'] ?></h3>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <h6 class="text-muted mb-1">Estado</h6>
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="bi bi-check-circle me-1"></i> Activo
                                    </span>
                                </div>
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Fecha de Registro</h6>
                                    <p class="mb-0">
                                        <i class="bi bi-calendar-event me-2 text-secondary"></i>
                                        <?= date('d/m/Y', strtotime($almacen['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Inventario Actual -->
        <div class="col-md-4">
            <div class="card card-dashboard" style="border-left-color: #667eea;">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-bar-chart-fill text-primary stats-icon"></i>
                        <h6 class="text-muted mb-2 stats-label">INVENTARIO ACTUAL</h6>
                        <h1 class="stats-value text-primary mb-0"><?= number_format($total_actual, 2) ?></h1>
                        <h4 class="text-muted mb-4">KILOGRAMOS</h4>
                        <small class="text-muted mt-2 d-block">Acumulado hasta la fecha actual</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTROL DE FECHA Y NAVEGACIÓN -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-month me-2"></i> 
                            Selección de Período
                        </h5>
                        <span class="date-badge">
                            <?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?>
                        </span>
                    </div>
                    
                    <form method="get" class="row g-3">
                        <input type="hidden" name="p" value="V_detalle_almacen">
                        <input type="hidden" name="id" value="<?= $id_almacen ?>">
                        
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Seleccionar Mes y Año</label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <input type="month" name="fecha" id="fecha_mes" 
                                       class="form-control border-start-0" 
                                       value="<?= $fecha_seleccionada ?>"
                                       onchange="this.form.submit()">
                                <button type="button" class="btn btn-primary" 
                                        onclick="document.getElementById('fecha_mes').value = '<?= date('Y-m') ?>'; this.form.submit()">
                                    <i class="bi bi-calendar-check me-1"></i> Mes Actual
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Navegación Rápida</label>
                            <div class="btn-group w-100 shadow-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="cambiarMes(-1)">
                                    <i class="bi bi-chevron-double-left"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary" 
                                        onclick="cambiarMes(0)">
                                    <i class="bi bi-calendar-event me-1"></i> Hoy
                                </button>
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="cambiarMes(1)">
                                    Siguiente <i class="bi bi-chevron-double-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="alert alert-modern alert-info mb-0 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <strong><?= $count_movimientos ?></strong> movimientos
                                        <br>
                                        <small>en este período</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Lista de meses disponibles -->
                    <?php if(count($meses_options) > 0): ?>
                    <div class="mt-4">
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-clock-history me-1"></i> Historial Disponible
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $mostrados = 0;
                            foreach($meses_options as $mes_option):
                                if($mostrados < 8):
                                    $parts = explode('-', $mes_option['value']);
                                    $anio_opt = isset($parts[0]) ? intval($parts[0]) : '';
                                    $mes_num = isset($parts[1]) ? intval($parts[1]) : 0;
                                    $label_es = (isset($mes_nombre[$mes_num]) ? $mes_nombre[$mes_num] : $mes_option['label']) . ' ' . $anio_opt;
                                    $active_class = ($mes_option['value'] == $fecha_seleccionada) ? 'active' : '';
                            ?>
                                <a href="?p=V_detalle_almacen&id=<?= intval($id_almacen) ?>&fecha=<?= htmlspecialchars($mes_option['value']) ?>" 
                                   class="badge bg-light text-dark text-decoration-none px-3 py-2 border <?= $active_class ?>">
                                    <i class="bi bi-calendar2-week me-1"></i>
                                    <?= htmlspecialchars($label_es) ?>
                                </a>
                            <?php 
                                $mostrados++;
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICAS DE MOVIMIENTOS -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card text-white" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="card-body p-4">
                    <i class="bi bi-box-arrow-in-down stats-icon"></i>
                    <h6 class="stats-label text-white">ENTRADAS GRANEL</h6>
                    <h2 class="stats-value mb-2"><?= number_format($totales_mes['granel_entrada'] ?? 0, 2) ?></h2>
                    <small class="opacity-75">Captaciones y ajustes</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card text-white" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <div class="card-body p-4">
                    <i class="bi bi-box-arrow-up stats-icon"></i>
                    <h6 class="stats-label text-white">SALIDAS GRANEL</h6>
                    <h2 class="stats-value mb-2"><?= number_format($granel_salida_total, 2) ?></h2>
                    <small class="opacity-75">Ventas: <?= number_format($totales_mes['granel_salida_venta'] ?? 0, 2) ?> kg</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card text-white" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                <div class="card-body p-4">
                    <i class="bi bi-box-seam stats-icon"></i>
                    <h6 class="stats-label text-white">ENTRADAS PACAS</h6>
                    <h2 class="stats-value mb-2"><?= number_format($totales_mes['pacas_entrada_kilos'] ?? 0, 2) ?></h2>
                    <small class="opacity-75">Conversiones de granel</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card text-white" style="background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);">
                <div class="card-body p-4">
                    <i class="bi bi-truck stats-icon"></i>
                    <h6 class="stats-label text-white">SALIDAS PACAS</h6>
                    <h2 class="stats-value mb-2"><?= number_format($pacas_salida_kilos_total, 2) ?></h2>
                    <small class="opacity-75">Ventas: <?= number_format($totales_mes['pacas_salida_kilos_venta'] ?? 0, 2) ?> kg</small>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS PRINCIPALES -->
    <div class="row">
        <div class="col-md-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <ul class="nav nav-tabs tabs-modern mb-4" id="detalleAlmacenTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active d-flex align-items-center" id="inventario-tab" data-bs-toggle="tab" 
                                    data-bs-target="#inventario" type="button" role="tab">
                                <i class="bi bi-box-seam me-2"></i> Inventario Acumulado
                                <span class="badge bg-primary badge-pill-modern ms-2">
                                    <?= $inventario_actual ? $inventario_actual->num_rows : 0 ?>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center" id="movimientos-tab" data-bs-toggle="tab" 
                                    data-bs-target="#movimientos" type="button" role="tab">
                                <i class="bi bi-arrow-left-right me-2"></i> Movimientos
                                <span class="badge bg-primary badge-pill-modern ms-2">
                                    <?= $count_movimientos ?>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center" id="resumen-tab" data-bs-toggle="tab" 
                                    data-bs-target="#resumen" type="button" role="tab">
                                <i class="bi bi-graph-up me-2"></i> Resumen Diario
                            </button>
                        </li>
                        
                    </ul>
                    <!-- Después del botón de Transformar Producto en el HEADER DEL DASHBOARD -->
                    <div class="card-toolbar mb-3 d-flex justify-content-end">
                        <button type="button" class="btn btn-modern btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalEntrada" <?= $perm['ACT_AC']; ?>>
                            <i class="bi bi-box-arrow-in-down me-2"></i> Nueva Entrada
                        </button>
                        <button type="button" class="btn btn-modern btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#modalConversion" <?= $perm['ACT_AC']; ?>>
                            <i class="bi bi-arrow-repeat me-2"></i> Transformar Producto
                        </button>
                    </div>
                    <div class="tab-content" id="detalleAlmacenTabContent">
                        
                        <!-- TAB 1: INVENTARIO ACUMULADO -->
                        <div class="tab-pane fade show active" id="inventario" role="tabpanel">
                            <div class="alert alert-modern alert-info mb-4">
                                <div class="d-flex">
                                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <strong>Inventario al <?= date('d', strtotime($ultimo_dia_mes)) ?> de <?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?>:</strong>
                                        <p class="mb-0">Saldo total de productos disponibles en el almacén al final del período seleccionado.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Producto</th>
                                            <th width="100">Tipo</th>
                                            <th class="text-end" width="120">Granel (kg)</th>
                                            <th class="text-end" width="120">Pacas (cant)</th>
                                            <th class="text-end" width="120">Pacas (kg)</th>
                                            <th class="text-end" width="140">Total (kg)</th>
                                            <th class="text-end" width="120">Peso Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador = 1;
                                        $total_general_granel = 0;
                                        $total_general_pacas_cant = 0;
                                        $total_general_pacas_kilos = 0;
                                        $total_general_kilos = 0;
                                        
                                        if ($inventario_actual && $inventario_actual->num_rows > 0) {
                                            while ($prod = $inventario_actual->fetch_assoc()) {
                                                $total_kilos = $prod['granel_acumulado'] + $prod['pacas_kilos_acumulado'];
                                                $peso_promedio = ($prod['pacas_cant_acumulado'] > 0) ? 
                                                    $prod['pacas_kilos_acumulado'] / $prod['pacas_cant_acumulado'] : 0;
                                                
                                                $total_general_granel += $prod['granel_acumulado'];
                                                $total_general_pacas_cant += $prod['pacas_cant_acumulado'];
                                                $total_general_pacas_kilos += $prod['pacas_kilos_acumulado'];
                                                $total_general_kilos += $total_kilos;
                                                
                                                $granel_badge = $prod['granel_acumulado'] > 0 ? 
                                                    '<span class="badge bg-warning badge-pill-modern">Granel</span>' : '';
                                                $pacas_badge = $prod['pacas_cant_acumulado'] > 0 ? 
                                                    '<span class="badge bg-info badge-pill-modern ms-1">Pacas</span>' : '';
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-muted"><?= $contador ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($prod['cod']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($prod['nom_pro']) ?></small>
                                            </td>
                                            <td><?= $granel_badge . $pacas_badge ?></td>
                                            <td class="text-end fw-bold"><?= number_format($prod['granel_acumulado'], 2) ?> kg</td>
                                            <td class="text-end fw-bold"><?= number_format($prod['pacas_cant_acumulado'], 0) ?></td>
                                            <td class="text-end fw-bold text-primary"><?= number_format($prod['pacas_kilos_acumulado'], 2) ?> kg</td>
                                            <td class="text-end fw-bold"><?= number_format($total_kilos, 2) ?> kg</td>
                                            <td class="text-end fw-semibold"><?= number_format($peso_promedio, 2) ?> kg</td>
                                        </tr>
                                        <?php
                                                $contador++;
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="alert alert-modern alert-warning mb-0 mx-auto" style="max-width: 500px;">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Sin inventario disponible</strong>
                                                    <p class="mb-0 mt-2">No hay inventario acumulado en este almacén para el período seleccionado.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                    <?php if($total_general_kilos > 0): ?>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">TOTALES ACUMULADOS:</td>
                                            <td class="text-end fw-bold"><?= number_format($total_general_granel, 2) ?> kg</td>
                                            <td class="text-end fw-bold"><?= number_format($total_general_pacas_cant, 0) ?></td>
                                            <td class="text-end fw-bold text-primary"><?= number_format($total_general_pacas_kilos, 2) ?> kg</td>
                                            <td class="text-end fw-bold"><?= number_format($total_general_kilos, 2) ?> kg</td>
                                            <td class="text-end fw-semibold">-</td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TAB 2: MOVIMIENTOS DEL MES -->
                        <div class="tab-pane fade" id="movimientos" role="tabpanel">
                            <div class="alert alert-modern alert-info mb-4">
                                <div class="d-flex">
                                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <strong>Registro completo de movimientos:</strong>
                                        <p class="mb-0">Todos los movimientos de entrada y salida registrados durante el mes seleccionado. Últimos 100 registros mostrados.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($count_movimientos > 0): ?>
                            <div class="mb-4">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="filtrarMovimientos('all')">
                                        <i class="bi bi-filter me-1"></i> Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="filtrarMovimientos('entrada')">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Entradas
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="filtrarMovimientos('salida')">
                                        <i class="bi bi-box-arrow-up me-1"></i> Salidas
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-modern" id="tablaMovimientos">
                                    <thead>
                                        <tr>
                                            <th width="140">Fecha / Hora</th>
                                            <th>Producto</th>
                                            <th width="120">Tipo</th>
                                            <th class="text-end" width="100">Granel</th>
                                            <th class="text-end" width="100">Pacas</th>
                                            <th class="text-end" width="120">Kilos Pacas</th>
                                            <th>Origen / Destino</th>
                                            <th width="200">Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($movimientos_mes && $movimientos_mes->num_rows > 0) {
                                            while ($mov = $movimientos_mes->fetch_assoc()) {
                                                $tipo = strtolower($mov['tipo_movimiento']);
                                                $badge_class = ($tipo == 'entrada' || $tipo == 'ajuste') ? 'success' : 'danger';
                                                $signo = ($tipo == 'entrada' || $tipo == 'ajuste') ? '+' : '-';
                                                
                                                $origen_html = '';
                                                if (!empty($mov['folio_captacion'])) {
                                                    $origen_html = "
                                                        <div class='fw-bold text-primary'>Captación</div>
                                                        <small class='text-muted'>Folio: {$mov['folio_captacion']}</small>
                                                    ";
                                                } elseif (!empty($mov['folio_venta'])) {
                                                    $origen_html = "
                                                        <div class='fw-bold text-danger'>Venta</div>
                                                        <small class='text-muted'>
                                                            <i class='bi bi-person me-1'></i>
                                                            {$mov['cod_cliente']} - {$mov['nombre_cliente']}
                                                        </small>
                                                    ";
                                                } else {
                                                    $origen_html = "<span class='text-muted'>Ajuste manual</span>";
                                                }
                                        ?>
                                        <tr data-tipo="<?= $tipo ?>">
                                            <td>
                                                <div class="fw-semibold"><?= date('d/m/Y', strtotime($mov['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('H:i', strtotime($mov['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($mov['cod_producto']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($mov['nombre_producto']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $badge_class ?> badge-pill-modern">
                                                    <?= strtoupper($tipo) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold <?= $tipo == 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                <?= $mov['granel_kilos_movimiento'] > 0 ? 
                                                    $signo . number_format($mov['granel_kilos_movimiento'], 2) . ' kg' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end fw-bold <?= $tipo == 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                <?= $mov['pacas_cantidad_movimiento'] > 0 ? 
                                                    $signo . number_format($mov['pacas_cantidad_movimiento'], 0) : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end fw-bold <?= $tipo == 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                <?= $mov['pacas_kilos_movimiento'] > 0 ? 
                                                    $signo . number_format($mov['pacas_kilos_movimiento'], 2) . ' kg' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td><?= $origen_html ?></td>
                                            <td>
                                                <small class="text-muted"><?= $mov['observaciones']?></small>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="alert alert-modern alert-warning mb-0 mx-auto" style="max-width: 500px;">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Sin movimientos registrados</strong>
                                                    <p class="mb-0 mt-2">No hay movimientos en <?= $mes_nombre[$mes_actual] ?> de <?= $anio_actual ?></p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TAB 3: RESUMEN DIARIO -->
                        <div class="tab-pane fade" id="resumen" role="tabpanel">
                            <div class="alert alert-modern alert-info mb-4">
                                <div class="d-flex">
                                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <strong>Análisis por día:</strong>
                                        <p class="mb-0">Desglose diario de movimientos para identificar tendencias y patrones de actividad.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            // Obtener movimientos por día
                            $sql_por_dia = "SELECT 
                                            DATE(mi.created_at) as fecha,
                                            SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                                                     THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_entrada,
                                            SUM(CASE WHEN mi.tipo_movimiento = 'salida' 
                                                     THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_salida,
                                            SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                                                     THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_entrada,
                                            SUM(CASE WHEN mi.tipo_movimiento = 'salida' 
                                                     THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_salida
                                           FROM movimiento_inventario mi
                                           LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                                           WHERE ib.id_alma = ?
                                             AND DATE(mi.created_at) BETWEEN ? AND ?
                                           GROUP BY DATE(mi.created_at)
                                           ORDER BY fecha DESC";
                            $stmt_dia = $conn_mysql->prepare($sql_por_dia);
                            $stmt_dia->bind_param('iss', $id_almacen, $primer_dia_mes, $ultimo_dia_mes);
                            $stmt_dia->execute();
                            $movimientos_dia = $stmt_dia->get_result();
                            
                            if ($movimientos_dia && $movimientos_dia->num_rows > 0):
                            ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th width="100">Día</th>
                                            <th class="text-end" width="120">Entradas Granel</th>
                                            <th class="text-end" width="120">Salidas Granel</th>
                                            <th class="text-end" width="120">Entradas Pacas</th>
                                            <th class="text-end" width="120">Salidas Pacas</th>
                                            <th class="text-end" width="140">Total Entradas</th>
                                            <th class="text-end" width="140">Total Salidas</th>
                                            <th class="text-end" width="140">Balance Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        while ($dia = $movimientos_dia->fetch_assoc()):
                                            $total_entradas = $dia['granel_entrada'] + $dia['pacas_entrada'];
                                            $total_salidas = $dia['granel_salida'] + $dia['pacas_salida'];
                                            $balance_dia = $total_entradas - $total_salidas;
                                            $balance_class = $balance_dia >= 0 ? 'success' : 'danger';
                                            $dow = date('w', strtotime($dia['fecha']));
                                            $dow_class = ($dow == 0 || $dow == 6) ? 'table-secondary' : '';
                                        ?>
                                        <tr class="<?= $dow_class ?>">
                                            <td>
                                                <div class="fw-bold"><?= date('d/m', strtotime($dia['fecha'])) ?></div>
                                                <small class="text-muted"><?= date('D', strtotime($dia['fecha'])) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <?= $dia['granel_entrada'] > 0 ? 
                                                    '<span class="fw-bold text-success">+' . number_format($dia['granel_entrada'], 2) . '</span>' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end">
                                                <?= $dia['granel_salida'] > 0 ? 
                                                    '<span class="fw-bold text-danger">-' . number_format($dia['granel_salida'], 2) . '</span>' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end">
                                                <?= $dia['pacas_entrada'] > 0 ? 
                                                    '<span class="fw-bold text-success">+' . number_format($dia['pacas_entrada'], 2) . '</span>' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end">
                                                <?= $dia['pacas_salida'] > 0 ? 
                                                    '<span class="fw-bold text-danger">-' . number_format($dia['pacas_salida'], 2) . '</span>' : 
                                                    '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td class="text-end fw-bold text-success"><?= number_format($total_entradas, 2) ?> kg</td>
                                            <td class="text-end fw-bold text-danger"><?= number_format($total_salidas, 2) ?> kg</td>
                                            <td class="text-end">
                                                <span class="badge bg-<?= $balance_class ?> badge-pill-modern px-3">
                                                    <?= number_format($balance_dia, 2) ?> kg
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-modern alert-warning text-center py-5">
                                <i class="bi bi-calendar-x fs-1 mb-3"></i>
                                <h4 class="fw-bold">Sin datos diarios</h4>
                                <p class="mb-0">No hay movimientos registrados por día en este período.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal para Transformación de Productos MODIFICADO -->
<div class="modal fade" id="modalConversion" tabindex="-1" aria-labelledby="modalConversionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header encabezado-col">
                <h5 class="modal-title text-white" id="modalConversionLabel">
                    <i class="bi bi-arrow-repeat me-2"></i> Transformación de Productos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConversion" action="procesar_conversion.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_almacen" value="<?= $id_almacen ?>">
                    <input type="hidden" name="id_usuario" value="<?= $idUser?>">
                    
                    <!-- Alert informativo -->
                    <div class="alert alert-modern alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Transformación de productos:</strong> Convierte granel de un producto en pacas de otro producto diferente. Los kilos totales se mantienen.
                    </div>
                    
                    <div class="row g-4">
                        <!-- Producto de Origen (Granel) -->
                        <div class="col-md-6">
                            <div class="card card-dashboard">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-box-arrow-down me-2"></i> Producto de Origen</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Seleccionar Producto <span class="text-danger">*</span></label>
                                        <select class="form-select" id="producto_origen" name="producto_origen" required onchange="actualizarDisponibilidadOrigen()">
                                            <option value="">Seleccione un producto...</option>
                                            <?php
                                            // Obtener productos con granel disponible
                                            $sql_granel_disponible = "SELECT ib.id_inventario, p.id_prod, p.cod, p.nom_pro,
                                                COALESCE(SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                                                THEN mi.granel_kilos_movimiento ELSE -mi.granel_kilos_movimiento END), 0) as granel_disponible
                                                FROM inventario_bodega ib
                                                LEFT JOIN productos p ON ib.id_prod = p.id_prod
                                                LEFT JOIN movimiento_inventario mi ON ib.id_inventario = mi.id_inventario
                                                WHERE ib.id_alma = ? AND p.status = 1 and p.zona = '$zona_seleccionada'
                                                GROUP BY ib.id_inventario, p.id_prod, p.cod, p.nom_pro
                                                HAVING granel_disponible > 0
                                                ORDER BY p.cod";
                                            
                                            $stmt_granel = $conn_mysql->prepare($sql_granel_disponible);
                                            $stmt_granel->bind_param('i', $id_almacen);
                                            $stmt_granel->execute();
                                            $productos_granel = $stmt_granel->get_result();
                                            
                                            if ($productos_granel && $productos_granel->num_rows > 0) {
                                                while ($prod = $productos_granel->fetch_assoc()) {
                                                    echo '<option value="' . $prod['id_inventario'] . '" 
                                                          data-granel-disponible="' . $prod['granel_disponible'] . '"
                                                          data-codigo="' . htmlspecialchars($prod['cod']) . '"
                                                          data-nombre="' . htmlspecialchars($prod['nom_pro']) . '"
                                                          data-id-prod="' . $prod['id_prod'] . '">
                                                          ' . htmlspecialchars($prod['cod']) . ' - ' . htmlspecialchars($prod['nom_pro']) . '
                                                          </option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Granel Disponible</label>
                                        <div class="input-group">
                                            <input type="text" id="granel_disponible" class="form-control" readonly>
                                            <span class="input-group-text">kg</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Kilos a Transformar <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" id="kilos_transformar" name="kilos_transformar" 
                                                   class="form-control" step="0.01" min="0.01" required
                                                   onchange="calcularPacasGeneradas()">
                                            <span class="input-group-text">kg</span>
                                        </div>
                                        <small class="text-muted">Máximo disponible: <span id="max_kilos">0</span> kg</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Producto de Destino (Pacas) -->
                        <div class="col-md-6">
                            <div class="card card-dashboard">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-box-arrow-up me-2"></i> Producto de Destino</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Seleccionar Producto <span class="text-danger">*</span></label>
                                        <select class="form-select" id="producto_destino" name="producto_destino" required onchange="calcularPacasGeneradas()">
                                            <option value="">Seleccione un producto...</option>
                                            <?php
                                            // Obtener todos los productos de la misma zona
                                            $zona_seleccionada = isset($_SESSION['selected_zone']) ? $_SESSION['selected_zone'] : 0;
                                            $sql_productos = "SELECT id_prod, cod, nom_pro FROM productos WHERE status = 1 AND zona = ? ORDER BY cod";
                                            $stmt_productos = $conn_mysql->prepare($sql_productos);
                                            $stmt_productos->bind_param('i', $zona_seleccionada);
                                            $stmt_productos->execute();
                                            $productos = $stmt_productos->get_result();
                                            
                                            if ($productos && $productos->num_rows > 0) {
                                                while ($prod = $productos->fetch_assoc()) {
                                                    echo '<option value="' . $prod['id_prod'] . '">' . 
                                                         htmlspecialchars($prod['cod']) . ' - ' . htmlspecialchars($prod['nom_pro']) . 
                                                         '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Cantidad de Pacas <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" id="cantidad_pacas" name="cantidad_pacas" 
                                                   class="form-control" step="1" min="1" value="1" required
                                                   onchange="calcularPesoPorPaca()">
                                            <span class="input-group-text">pacas</span>
                                        </div>
                                        <small class="text-muted">Número de pacas a generar</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Peso por Paca</label>
                                        <div class="input-group">
                                            <input type="text" id="peso_por_paca" class="form-control" readonly>
                                            <span class="input-group-text">kg/paca</span>
                                        </div>
                                        <small class="text-muted">Calculado automáticamente</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Peso Total Pacas</label>
                                        <div class="input-group">
                                            <input type="text" id="peso_total_pacas" class="form-control" readonly>
                                            <span class="input-group-text">kg</span>
                                        </div>
                                        <small class="text-muted">Igual a los kilos transformados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observaciones -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="3" 
                                          placeholder="Ej: Transformación de granel de Producto A a pacas de Producto B" id="observaciones_transformacion"></textarea>
                            </div>
                        </div>
                        
                        <!-- Resumen de la Transformación -->
                        <div class="col-12">
                            <div class="alert alert-modern alert-warning">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <i class="bi bi-arrow-repeat fs-2"></i>
                                    </div>
                                    <div class="col">
                                        <h6 class="fw-bold mb-1">Resumen de Transformación</h6>
                                        <div id="resumen_transformacion">
                                            <p class="mb-0">Seleccione los productos y cantidad a transformar para ver el resumen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-repeat me-2"></i> Procesar Transformación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal para Entrada de Productos SOLUCIÓN DEFINITIVA -->
<div class="modal fade" id="modalEntrada" tabindex="-1" aria-labelledby="modalEntradaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header encabezado-col">
                <h5 class="modal-title text-white" id="modalEntradaLabel">
                    <i class="bi bi-box-arrow-in-down me-2"></i> Nueva Entrada de Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEntrada" action="procesar_entrada.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id_almacen" value="<?= $id_almacen ?>">
                    <input type="hidden" name="id_usuario" value="<?= $idUser ?>">
                    
                    <!-- Alert informativo -->
                    <div class="alert alert-modern alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Nueva entrada:</strong> Registre una entrada de producto en granel o en pacas. El inventario se actualizará automáticamente.
                    </div>
                    
                    <div class="row g-4">
                        <!-- Selección de Producto -->
                        <div class="col-md-6">
                            <div class="card card-dashboard">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-box me-2"></i> Producto</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Seleccionar Producto <span class="text-danger">*</span></label>
                                        <select class="form-select" id="producto_entrada" name="producto_entrada">
                                            <option value="">Seleccione un producto...</option>
                                            <?php
                                            // Obtener productos de la zona actual
                                            $zona_seleccionada = isset($_SESSION['selected_zone']) ? $_SESSION['selected_zone'] : 0;
                                            $sql_productos_entrada = "SELECT id_prod, cod, nom_pro FROM productos 
                                                                    WHERE status = 1 AND zona = ? 
                                                                    ORDER BY cod";
                                            $stmt_productos_entrada = $conn_mysql->prepare($sql_productos_entrada);
                                            $stmt_productos_entrada->bind_param('i', $zona_seleccionada);
                                            $stmt_productos_entrada->execute();
                                            $productos_entrada = $stmt_productos_entrada->get_result();
                                            
                                            if ($productos_entrada && $productos_entrada->num_rows > 0) {
                                                while ($prod = $productos_entrada->fetch_assoc()) {
                                                    echo '<option value="' . $prod['id_prod'] . '">' . 
                                                         htmlspecialchars($prod['cod']) . ' - ' . htmlspecialchars($prod['nom_pro']) . 
                                                         '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Seleccionar Bodega <span class="text-danger">*</span></label>
                                        <select class="form-select" id="bodega_entrada" name="bodega_entrada">
                                            <option value="">Seleccione una bodega...</option>
                                            <?php
                                            if ($hay_bodegas) {
                                                $bodegas->data_seek(0); // Reiniciar el puntero
                                                while ($bodega = $bodegas->fetch_assoc()) {
                                                    echo '<option value="' . $bodega['id_direc'] . '">' . 
                                                        htmlspecialchars($bodega['noma']) . '</option>';
                                                }
                                            } else {
                                                echo '<option value="" disabled>No hay bodegas disponibles</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tipo de Entrada <span class="text-danger">*</span></label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="tipo_entrada" 
                                                       id="granel_radio" value="granel" checked 
                                                       onchange="toggleTipoEntrada()">
                                                <label class="form-check-label" for="granel_radio">
                                                    Granel (kg)
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="tipo_entrada" 
                                                       id="pacas_radio" value="pacas"
                                                       onchange="toggleTipoEntrada()">
                                                <label class="form-check-label" for="pacas_radio">
                                                    Pacas
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalles de la Entrada -->
                        <div class="col-md-6">
                            <div class="card card-dashboard">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-clipboard-data me-2"></i> Detalles de la Entrada</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Granel -->
                                    <div id="granel_section">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Kilos de Granel <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" id="kilos_granel" name="kilos_granel" 
                                                       class="form-control" step="0.01" min="0.01" 
                                                       value="0.00">
                                                <span class="input-group-text">kg</span>
                                            </div>
                                            <small class="text-muted">Cantidad en kilogramos</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Pacas -->
                                    <div id="pacas_section" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Cantidad de Pacas <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" id="cantidad_pacas_entrada" name="cantidad_pacas_entrada" 
                                                           class="form-control" step="1" min="1" value="1">
                                                    <span class="input-group-text">pacas</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Peso Total Pacas <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" id="peso_pacas_entrada" name="peso_pacas_entrada" 
                                                           class="form-control" step="0.01" min="0.01" value="0.00">
                                                    <span class="input-group-text">kg</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Peso por Paca (Calculado)</label>
                                            <div class="input-group">
                                                <input type="text" id="peso_por_paca_entrada" class="form-control" readonly>
                                                <span class="input-group-text">kg/paca</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tipo de Movimiento</label>
                                        <select class="form-select" name="tipo_movimiento">
                                            <option value="entrada" selected>Entrada Normal</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Fecha de Entrada</label>
                                        <input type="datetime-local" class="form-control" name="fecha_entrada" 
                                               value="<?= date('Y-m-d\TH:i') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observaciones -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Observaciones <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="observacionesEntrada" rows="3" 
                                          placeholder="Ej: Entrada por compra, ajuste por conteo físico, etc."></textarea>
                            </div>
                        </div>
                        
                        <!-- Resumen de la Entrada -->
                        <div class="col-12">
                            <div class="alert alert-modern alert-success">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <i class="bi bi-box-seam fs-2"></i>
                                    </div>
                                    <div class="col">
                                        <h6 class="fw-bold mb-1">Resumen de la Entrada</h6>
                                        <div id="resumen_entrada">
                                            <p class="mb-0">Complete los datos para ver el resumen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="validarYEnviarEntrada()">
                        <i class="bi bi-check-circle me-2"></i> Registrar Entrada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Función para cambiar de mes (navegación)
function cambiarMes(direccion) {
    const fechaInput = document.getElementById('fecha_mes');
    let valor = fechaInput.value;
    
    if (direccion === 0) {
        // Hoy
        const hoy = new Date();
        valor = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');
        fechaInput.value = valor;
        fechaInput.closest('form').submit();
        return;
    }
    
    // Si no hay valor, usar mes actual
    if (!valor) {
        const hoy = new Date();
        valor = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');
    }
    
    // Asegurar formato YYYY-MM y parsear partes
    const partes = valor.split('-');
    let anio = parseInt(partes[0], 10);
    let mes = parseInt(partes[1], 10);
    if (isNaN(anio) || isNaN(mes)) {
        const hoy = new Date();
        anio = hoy.getFullYear();
        mes = hoy.getMonth() + 1;
    }
    
    // Crear Date usando año y mes (mes en Date es 0-based)
    const fecha = new Date(anio, mes - 1, 1);
    fecha.setMonth(fecha.getMonth() + direccion);
    
    const nuevoMes = fecha.getMonth() + 1;
    const nuevoAnio = fecha.getFullYear();
    const nuevaFecha = nuevoAnio + '-' + String(nuevoMes).padStart(2, '0');
    
    fechaInput.value = nuevaFecha;
    
    // Enviar el formulario si existe
    const form = fechaInput.closest('form');
    if (form) form.submit();
}

// Función para filtrar movimientos por tipo
function filtrarMovimientos(tipo) {
    const tabla = document.getElementById('tablaMovimientos');
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let fila of filas) {
        const tipoFila = fila.getAttribute('data-tipo') || '';
        
        if (tipo === 'all' || tipo === tipoFila) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    }
    
    // Actualizar botones activos
    const botones = document.querySelectorAll('#movimientos .btn-group .btn');
    botones.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    // Inicializar tabs
    $('#detalleAlmacenTab button').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Animación de cards al cargar
    $('.card-dashboard').hide().fadeIn(600);
    $('.stats-card').hide().each(function(i) {
        $(this).delay(i * 200).fadeIn(400);
    });
    
    // Animación de las stats cards al pasar el mouse
    $('.stats-card').hover(
        function() {
            $(this).css('transform', 'translateY(-5px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
    
    // Mejorar la interacción con el input de fecha
    $('#fecha_mes').focus(function() {
        $(this).closest('.input-group').addClass('border-primary');
    }).blur(function() {
        $(this).closest('.input-group').removeClass('border-primary');
    });
    
    // Exportar datos
    $('#exportarPdf').click(function() {
        // Implementar exportación a PDF
        alert('Exportar a PDF - Funcionalidad en desarrollo');
    });
    
    $('#exportarExcel').click(function() {
        // Implementar exportación a Excel
        alert('Exportar a Excel - Funcionalidad en desarrollo');
    });
});
// Funciones para el modal de conversión MODIFICADO
function actualizarDisponibilidadOrigen() {
    const selectOrigen = document.getElementById('producto_origen');
    const selectedOption = selectOrigen.options[selectOrigen.selectedIndex];
    const granelDisponible = selectedOption.getAttribute('data-granel-disponible') || 0;
    const codigo = selectedOption.getAttribute('data-codigo') || '';
    const nombre = selectedOption.getAttribute('data-nombre') || '';
    
    document.getElementById('granel_disponible').value = parseFloat(granelDisponible).toFixed(2);
    document.getElementById('max_kilos').textContent = parseFloat(granelDisponible).toFixed(2);
    
    // Actualizar kilos a transformar
    const kilosInput = document.getElementById('kilos_transformar');
    kilosInput.max = granelDisponible;
    kilosInput.value = '';
    
    calcularPacasGeneradas();
}

function calcularPacasGeneradas() {
    const kilosTransformar = parseFloat(document.getElementById('kilos_transformar').value) || 0;
    const cantidadPacas = parseInt(document.getElementById('cantidad_pacas').value) || 1;
    const maxKilos = parseFloat(document.getElementById('max_kilos').textContent) || 0;
    
    // Validar que no exceda el máximo
    if (kilosTransformar > maxKilos) {
        document.getElementById('kilos_transformar').value = maxKilos;
        kilosTransformar = maxKilos;
    }
    
    // Calcular peso por paca
    const pesoPorPaca = cantidadPacas > 0 ? kilosTransformar / cantidadPacas : 0;
    const pesoTotalPacas = kilosTransformar;
    
    document.getElementById('peso_por_paca').value = pesoPorPaca.toFixed(2);
    document.getElementById('peso_total_pacas').value = pesoTotalPacas.toFixed(2);
    
    // Actualizar resumen
    actualizarResumenTransformacion(kilosTransformar, cantidadPacas, pesoPorPaca, pesoTotalPacas);
}

function calcularPesoPorPaca() {
    const kilosTransformar = parseFloat(document.getElementById('kilos_transformar').value) || 0;
    const cantidadPacas = parseInt(document.getElementById('cantidad_pacas').value) || 1;
    
    if (cantidadPacas <= 0) {
        document.getElementById('cantidad_pacas').value = 1;
        cantidadPacas = 1;
    }
    
    // Calcular peso por paca
    const pesoPorPaca = cantidadPacas > 0 ? kilosTransformar / cantidadPacas : 0;
    const pesoTotalPacas = kilosTransformar;
    
    document.getElementById('peso_por_paca').value = pesoPorPaca.toFixed(2);
    document.getElementById('peso_total_pacas').value = pesoTotalPacas.toFixed(2);
    
    // Actualizar resumen
    actualizarResumenTransformacion(kilosTransformar, cantidadPacas, pesoPorPaca, pesoTotalPacas);
}

function actualizarResumenTransformacion(kilos, pacas, pesoPorPaca, totalPacas) {
    const selectOrigen = document.getElementById('producto_origen');
    const selectDestino = document.getElementById('producto_destino');
    
    const origenText = selectOrigen.options[selectOrigen.selectedIndex].text;
    const destinoText = selectDestino.options[selectDestino.selectedIndex].text;
    
    let resumenHTML = '';
    
    if (origenText && destinoText && kilos > 0 && pacas > 0) {
        resumenHTML = `
            <div class="row">
                <div class="col-6">
                    <small class="text-muted d-block">Producto Origen:</small>
                    <strong>${origenText}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Producto Destino:</small>
                    <strong>${destinoText}</strong>
                </div>
            </div>
            <hr class="my-2">
            <div class="row">
                <div class="col-4">
                    <small class="text-muted d-block">Granel a transformar:</small>
                    <strong class="text-danger">${kilos.toFixed(2)} kg</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Pacas a generar:</small>
                    <strong class="text-success">${pacas} pacas</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Peso por paca:</small>
                    <strong>${pesoPorPaca.toFixed(2)} kg</strong>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <div class="alert alert-sm alert-light">
                        <i class="bi bi-arrow-left-right me-1"></i>
                        <strong>Proceso:</strong> Se descontarán ${kilos.toFixed(2)} kg de granel del producto origen y se agregarán ${pacas} pacas (${kilos.toFixed(2)} kg) al producto destino.
                    </div>
                </div>
            </div>
        `;
    } else {
        resumenHTML = '<p class="mb-0">Seleccione los productos y cantidad a transformar para ver el resumen</p>';
    }
    
    document.getElementById('resumen_transformacion').innerHTML = resumenHTML;
}

// Validar formulario antes de enviar
document.getElementById('formConversion').addEventListener('submit', function(e) {
    const kilosTransformar = parseFloat(document.getElementById('kilos_transformar').value) || 0;
    const maxKilos = parseFloat(document.getElementById('max_kilos').textContent) || 0;
    const productoOrigen = document.getElementById('producto_origen').value;
    const productoDestino = document.getElementById('producto_destino').value;
    const cantidadPacas = parseInt(document.getElementById('cantidad_pacas').value) || 0;
    const pesoPorPaca = parseFloat(document.getElementById('peso_por_paca').value) || 0;
    
    if (!productoOrigen) {
        e.preventDefault();
        alert('Por favor seleccione un producto de origen');
        return false;
    }
    
    if (!productoDestino) {
        e.preventDefault();
        alert('Por favor seleccione un producto de destino');
        return false;
    }
    
    if (kilosTransformar <= 0) {
        e.preventDefault();
        alert('Por favor ingrese una cantidad válida de kilos a transformar');
        return false;
    }
    
    if (kilosTransformar > maxKilos) {
        e.preventDefault();
        alert(`No hay suficiente granel disponible. Máximo: ${maxKilos.toFixed(2)} kg`);
        return false;
    }
    
    if (cantidadPacas <= 0) {
        e.preventDefault();
        alert('Debe generar al menos 1 paca');
        return false;
    }
    
    if (pesoPorPaca <= 0) {
        e.preventDefault();
        alert('El peso por paca debe ser mayor a 0');
        return false;
    }
    
    // Validar que no sea el mismo producto
    const selectOrigen = document.getElementById('producto_origen');
    const origenIdProd = selectOrigen.options[selectOrigen.selectedIndex].getAttribute('data-id-prod');
    
    if (origenIdProd == productoDestino) {
        e.preventDefault();
        alert('No puede transformar un producto en sí mismo. Seleccione un producto destino diferente.');
        return false;
    }
    
    // Confirmar transformación
    if (!confirm('¿Está seguro de procesar esta transformación?')) {
        e.preventDefault();
        return false;
    }
});

// Event listeners para el modal
document.addEventListener('DOMContentLoaded', function() {
    const modalConversion = document.getElementById('modalConversion');
    
    if (modalConversion) {
        modalConversion.addEventListener('shown.bs.modal', function() {
            // Inicializar valores cuando se abre el modal
            actualizarDisponibilidadOrigen();
        });
        
        modalConversion.addEventListener('hidden.bs.modal', function() {
            // Resetear formulario cuando se cierra
            document.getElementById('formConversion').reset();
            document.getElementById('cantidad_pacas').value = 1;
            document.getElementById('resumen_transformacion').innerHTML = 
                '<p class="mb-0">Seleccione los productos y cantidad a transformar para ver el resumen</p>';
        });
    }
});
// Funciones para el modal de entrada - VERSIÓN DEFINITIVA
function toggleTipoEntrada() {
    const granelRadio = document.getElementById('granel_radio');
    const granelSection = document.getElementById('granel_section');
    const pacasSection = document.getElementById('pacas_section');
    
    if (granelRadio.checked) {
        granelSection.style.display = 'block';
        pacasSection.style.display = 'none';
        
        // Resetear valores de pacas
        document.getElementById('cantidad_pacas_entrada').value = '1';
        document.getElementById('peso_pacas_entrada').value = '0.00';
        document.getElementById('peso_por_paca_entrada').value = '0.00';
    } else {
        granelSection.style.display = 'none';
        pacasSection.style.display = 'block';
        
        // Resetear valor de granel
        document.getElementById('kilos_granel').value = '0.00';
    }
    
    actualizarResumenEntrada();
}

function calcularPesoPorPacaEntrada() {
    const cantidad = parseInt(document.getElementById('cantidad_pacas_entrada').value) || 0;
    const peso = parseFloat(document.getElementById('peso_pacas_entrada').value) || 0;
    
    if (cantidad > 0 && peso > 0) {
        const pesoPorPaca = peso / cantidad;
        document.getElementById('peso_por_paca_entrada').value = pesoPorPaca.toFixed(2);
    } else {
        document.getElementById('peso_por_paca_entrada').value = '0.00';
    }
    
    actualizarResumenEntrada();
}

function actualizarResumenEntrada() {
    const productoSelect = document.getElementById('producto_entrada');
    const bodegaSelect = document.getElementById('bodega_entrada');
    const granelRadio = document.getElementById('granel_radio');
    const tipoMovimiento = document.querySelector('select[name="tipo_movimiento"]');
    
    const productoText = productoSelect.options[productoSelect.selectedIndex]?.text || '';
    const bodegaText = bodegaSelect.options[bodegaSelect.selectedIndex]?.text || '';
    
    let resumenHTML = '';
    
    if (productoText && bodegaText) {
        if (granelRadio.checked) {
            const kilos = parseFloat(document.getElementById('kilos_granel').value) || 0;
            if (kilos > 0) {
                resumenHTML = `
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Producto:</small>
                            <strong>${productoText}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bodega:</small>
                            <strong>${bodegaText}</strong>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-4">
                            <small class="text-muted d-block">Tipo:</small>
                            <span class="badge bg-info">Granel</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Cantidad:</small>
                            <strong class="text-success">${kilos.toFixed(2)} kg</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Movimiento:</small>
                            <strong>${tipoMovimiento?.value || 'entrada'}</strong>
                        </div>
                    </div>
                `;
            } else {
                resumenHTML = `
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Producto:</small>
                            <strong>${productoText}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bodega:</small>
                            <strong>${bodegaText}</strong>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="alert alert-sm alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Ingrese la cantidad de kilos para granel
                    </div>
                `;
            }
        } else {
            const cantidad = parseInt(document.getElementById('cantidad_pacas_entrada').value) || 0;
            const peso = parseFloat(document.getElementById('peso_pacas_entrada').value) || 0;
            const pesoPorPaca = parseFloat(document.getElementById('peso_por_paca_entrada').value) || 0;
            
            if (cantidad > 0 && peso > 0) {
                resumenHTML = `
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Producto:</small>
                            <strong>${productoText}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bodega:</small>
                            <strong>${bodegaText}</strong>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-3">
                            <small class="text-muted d-block">Tipo:</small>
                            <span class="badge bg-warning">Pacas</span>
                        </div>
                        <div class="col-3">
                            <small class="text-muted d-block">Cantidad:</small>
                            <strong class="text-success">${cantidad} pacas</strong>
                        </div>
                        <div class="col-3">
                            <small class="text-muted d-block">Peso total:</small>
                            <strong>${peso.toFixed(2)} kg</strong>
                        </div>
                        <div class="col-3">
                            <small class="text-muted d-block">Peso/paca:</small>
                            <strong>${pesoPorPaca.toFixed(2)} kg</strong>
                        </div>
                    </div>
                `;
            } else {
                resumenHTML = `
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Producto:</small>
                            <strong>${productoText}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Bodega:</small>
                            <strong>${bodegaText}</strong>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="alert alert-sm alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Ingrese cantidad y peso de las pacas
                    </div>
                `;
            }
        }
    } else {
        resumenHTML = '<p class="mb-0">Complete los datos para ver el resumen</p>';
    }
    
    document.getElementById('resumen_entrada').innerHTML = resumenHTML;
}

// Función principal de validación y envío
function validarYEnviarEntrada() {
    const productoSelect = document.getElementById('producto_entrada');
    const bodegaSelect = document.getElementById('bodega_entrada');
    const observacionesEntradaTextarea = document.querySelector('textarea[name="observacionesEntrada"]');
    
    // Obtener valores
    const producto = productoSelect ? productoSelect.value : '';
    const bodega = bodegaSelect ? bodegaSelect.value : '';
    const observaciones = observacionesEntradaTextarea ? observacionesEntradaTextarea.value.trim() : '';
    
    const granelRadio = document.getElementById('granel_radio').checked;
    const kilosGranel = parseFloat(document.getElementById('kilos_granel').value) || 0;
    const cantidadPacas = parseInt(document.getElementById('cantidad_pacas_entrada').value) || 0;
    const pesoPacas = parseFloat(document.getElementById('peso_pacas_entrada').value) || 0;
    const tipoMovimientoSelect = document.querySelector('select[name="tipo_movimiento"]');
    const tipoMovimiento = tipoMovimientoSelect ? tipoMovimientoSelect.value : 'entrada';
    
     console.log('Valores capturados:', {
        producto: producto,
        bodega: bodega,
        observaciones: observaciones,
        granelRadio: granelRadio,
        kilosGranel: kilosGranel,
        cantidadPacas: cantidadPacas,
        pesoPacas: pesoPacas,   
        fechadeentrada: document.querySelector('input[name="fecha_entrada"]').value,
    });

    // Validaciones
    let errores = [];
    
    if (!producto) {
        errores.push('Por favor seleccione un producto');
    }
    
    // Validar bodega
    if (!bodega || bodega === "") {
        errores.push('Por favor seleccione una bodega');
        if (bodegaSelect) bodegaSelect.classList.add('is-invalid');
    } else {
        if (bodegaSelect) bodegaSelect.classList.remove('is-invalid');
    }
    
    if (!observaciones) {
        errores.push('Por favor ingrese observaciones');
        if (observacionesEntradaTextarea) observacionesEntradaTextarea.classList.add('is-invalid');
    } else {
        if (observacionesEntradaTextarea) observacionesEntradaTextarea.classList.remove('is-invalid');
    }
    
    if (granelRadio) {
        if (kilosGranel <= 0) {
            errores.push('Por favor ingrese una cantidad válida de kilos para granel (mayor a 0)');
        }
    } else {
        if (cantidadPacas <= 0) {
            errores.push('Por favor ingrese una cantidad válida de pacas (mayor a 0)');
        }
        
        if (pesoPacas <= 0) {
            errores.push('Por favor ingrese un peso válido para las pacas (mayor a 0)');
        }
        
        if (pesoPacas / cantidadPacas <= 0) {
            errores.push('El peso por paca debe ser mayor a 0');
        }
    }
    
    if (errores.length > 0) {
        // Mostrar errores con SweetAlert2
        Swal.fire({
            title: 'Error de Validación',
            html: '<div class="text-start">' + 
                  '<i class="bi bi-exclamation-triangle text-danger me-2"></i>' +
                  '<strong>Por favor corrija los siguientes errores:</strong><br>' +
                  '<ul class="mt-2 mb-0">' + 
                  errores.map(error => `<li>${error}</li>`).join('') + 
                  '</ul></div>',
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Confirmar envío
    Swal.fire({
        title: '¿Registrar Entrada?',
        html: '¿Está seguro de registrar esta entrada en el inventario?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Registrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            // Deshabilitar botón para evitar doble clic
            const submitBtn = document.querySelector('#modalEntrada .btn-success');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Procesando...';
            
            // Enviar formulario
            document.getElementById('formEntrada').submit();
        }
    });
}

// Event listeners para campos de entrada
document.addEventListener('DOMContentLoaded', function() {
    // Modal de entrada
    const modalEntrada = document.getElementById('modalEntrada');
    if (modalEntrada) {
        modalEntrada.addEventListener('shown.bs.modal', function() {
            // Inicializar valores
            toggleTipoEntrada();
            
            // Enfocar el primer campo
            setTimeout(() => {
                document.getElementById('producto_entrada').focus();
            }, 500);
        });
        
        modalEntrada.addEventListener('hidden.bs.modal', function() {
            // Resetear formulario
            document.getElementById('formEntrada').reset();
            document.getElementById('cantidad_pacas_entrada').value = '1';
            document.getElementById('resumen_entrada').innerHTML = 
                '<p class="mb-0">Complete los datos para ver el resumen</p>';
            
            // Asegurar que el tipo granel esté seleccionado por defecto
            document.getElementById('granel_radio').checked = true;
            toggleTipoEntrada();
            
            // Restaurar botón
            const submitBtn = document.querySelector('#modalEntrada .btn-success');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Registrar Entrada';
            }
        });
    }
    
    // Event listeners para actualizar resumen
    const kilosGranelInput = document.getElementById('kilos_granel');
    const cantidadPacasInput = document.getElementById('cantidad_pacas_entrada');
    const pesoPacasInput = document.getElementById('peso_pacas_entrada');
    
    if (kilosGranelInput) {
        kilosGranelInput.addEventListener('input', actualizarResumenEntrada);
    }
    
    if (cantidadPacasInput) {
        cantidadPacasInput.addEventListener('input', function() {
            calcularPesoPorPacaEntrada();
            actualizarResumenEntrada();
        });
    }
    
    if (pesoPacasInput) {
        pesoPacasInput.addEventListener('input', function() {
            calcularPesoPorPacaEntrada();
            actualizarResumenEntrada();
        });
    }
    
    if (document.getElementById('producto_entrada')) {
        document.getElementById('producto_entrada').addEventListener('change', actualizarResumenEntrada);
    }
    
    if (document.getElementById('bodega_entrada')) {
        document.getElementById('bodega_entrada').addEventListener('change', actualizarResumenEntrada);
    }
    
    const tipoMovimientoSelect = document.querySelector('select[name="tipo_movimiento"]');
    if (tipoMovimientoSelect) {
        tipoMovimientoSelect.addEventListener('change', actualizarResumenEntrada);
    }
    
    // Permitir enviar con Enter en los campos numéricos
    if (kilosGranelInput) {
        kilosGranelInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validarYEnviarEntrada();
            }
        });
    }
    
    if (pesoPacasInput) {
        pesoPacasInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validarYEnviarEntrada();
            }
        });
    }
    
    // Evitar el envío automático del formulario
    document.getElementById('formEntrada').addEventListener('submit', function(e) {
        e.preventDefault();
        // El envío ahora se controla desde validarYEnviarEntrada()
    });
});

</script>