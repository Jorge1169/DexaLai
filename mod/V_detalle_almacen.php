<?php
// V_detalle_almacen.php - Detalle de almacén

// Obtener ID del almacén
$id_almacen = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_almacen <= 0) {
    alert("ID de almacén no válido", 0, "almacenes");
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

// Obtener mes y año para el reporte - Ahora usando input type="month"
$mes_actual = date('n');
$anio_actual = date('Y');
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m');

// Si hay fecha seleccionada, extraer mes y año
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

// Función para obtener inventario acumulado hasta un mes específico
function obtenerInventarioAcumulado($id_alma, $mes, $anio, $conn_mysql) {
    // Calcular fecha límite (último día del mes seleccionado)
    $fecha_limite = date("$anio-$mes-t 23:59:59");
    
    // Obtener inventario acumulado hasta esa fecha
    $sql = "SELECT ib.id_prod, p.cod, p.nom_pro,
                   SUM(CASE WHEN mi.tipo_movimiento = 'entrada' OR mi.tipo_movimiento = 'ajuste' 
                            THEN mi.granel_kilos_movimiento ELSE -mi.granel_kilos_movimiento END) as granel_acumulado,
                   SUM(CASE WHEN mi.tipo_movimiento = 'entrada' OR mi.tipo_movimiento = 'ajuste' 
                            THEN mi.pacas_cantidad_movimiento ELSE -mi.pacas_cantidad_movimiento END) as pacas_cant_acumulado,
                   SUM(CASE WHEN mi.tipo_movimiento = 'entrada' OR mi.tipo_movimiento = 'ajuste' 
                            THEN mi.pacas_kilos_movimiento ELSE -mi.pacas_kilos_movimiento END) as pacas_kilos_acumulado
            FROM inventario_bodega ib
            LEFT JOIN productos p ON ib.id_prod = p.id_prod
            LEFT JOIN movimiento_inventario mi ON ib.id_inventario = mi.id_inventario
            WHERE ib.id_alma = ?
              AND DATE(mi.created_at) <= ?
            GROUP BY ib.id_prod, p.cod, p.nom_pro
            HAVING granel_acumulado > 0 OR pacas_cant_acumulado > 0 OR pacas_kilos_acumulado > 0";
    
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
                           DATE_FORMAT(mi.created_at, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM movimiento_inventario mi
                    LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                    LEFT JOIN productos p ON ib.id_prod = p.id_prod
                    LEFT JOIN captacion c ON mi.id_captacion = c.id_captacion
                    WHERE ib.id_alma = ?
                      AND DATE(mi.created_at) BETWEEN ? AND ?
                    ORDER BY mi.created_at DESC
                    LIMIT 50";
$stmt_movimientos = $conn_mysql->prepare($sql_movimientos);
$stmt_movimientos->bind_param('iss', $id_almacen, $primer_dia_mes, $ultimo_dia_mes);
$stmt_movimientos->execute();
$movimientos_mes = $stmt_movimientos->get_result();

// Obtener totales del mes
$sql_totales_mes = "SELECT 
                    SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                             THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_entrada,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' 
                             THEN mi.granel_kilos_movimiento ELSE 0 END) as granel_salida,
                    SUM(CASE WHEN mi.tipo_movimiento IN ('entrada', 'ajuste') 
                             THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_entrada_kilos,
                    SUM(CASE WHEN mi.tipo_movimiento = 'salida' 
                             THEN mi.pacas_kilos_movimiento ELSE 0 END) as pacas_salida_kilos
                    FROM movimiento_inventario mi
                    LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                    WHERE ib.id_alma = ?
                      AND DATE(mi.created_at) BETWEEN ? AND ?";
$stmt_totales = $conn_mysql->prepare($sql_totales_mes);
$stmt_totales->bind_param('iss', $id_almacen, $primer_dia_mes, $ultimo_dia_mes);
$stmt_totales->execute();
$totales_mes = $stmt_totales->get_result()->fetch_assoc();

// Obtener bodegas asociadas
$sql_bodegas = "SELECT * FROM direcciones 
                WHERE id_alma = ? AND status = 1 
                ORDER BY noma";
$stmt_bodegas = $conn_mysql->prepare($sql_bodegas);
$stmt_bodegas->bind_param('i', $id_almacen);
$stmt_bodegas->execute();
$bodegas = $stmt_bodegas->get_result();
?>

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-building me-2"></i>Detalle de Almacén: 
                <span class="badge bg-light text-dark"><?= htmlspecialchars($almacen['nombre']) ?></span>
            </h5>
            <div>
                <a href="?p=almacenes_info" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Lista
                </a>
            </div>
        </div>
        <div class="card-body">
            
            <!-- Información del Almacén -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Información General</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Código:</strong> <?= htmlspecialchars($almacen['cod']) ?></p>
                                    <p><strong>Nombre:</strong> <?= htmlspecialchars($almacen['nombre']) ?></p>
                                    <p><strong>Zona:</strong> <?= htmlspecialchars($almacen['nombre_zona']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Bodegas:</strong> <span class="badge bg-info"><?= $almacen['total_bodegas'] ?></span></p>
                                    <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
                                    <p><strong>Registrado:</strong> <?= date('d/m/Y', strtotime($almacen['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">Inventario Actual</h5>
                            <?php
                            // Calcular inventario total actual
                            $sql_total_actual = "SELECT SUM(total_kilos_disponible) as total 
                                                FROM inventario_bodega 
                                                WHERE id_alma = ?";
                            $stmt_total = $conn_mysql->prepare($sql_total_actual);
                            $stmt_total->bind_param('i', $id_almacen);
                            $stmt_total->execute();
                            $total_actual = $stmt_total->get_result()->fetch_assoc()['total'];
                            ?>
                            <h3 class="text-primary"><?= number_format($total_actual, 2) ?> kg</h3>
                            <small class="text-muted">Acumulado hasta hoy</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Selector de Fecha Mejorado -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar me-2"></i>Reporte Mensual
                    </h5>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="p" value="V_detalle_almacen">
                        <input type="hidden" name="id" value="<?= $id_almacen ?>">
                        
                        <div class="col-md-4">
                            <label class="form-label">Seleccionar Mes</label>
                            <div class="input-group">
                                <input type="month" name="fecha" id="fecha_mes" 
                                       class="form-control" 
                                       value="<?= $fecha_seleccionada ?>"
                                       onchange="this.form.submit()">
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="document.getElementById('fecha_mes').value = '<?= date('Y-m') ?>'; this.form.submit()">
                                    <i class="bi bi-arrow-clockwise"></i> Hoy
                                </button>
                            </div>
                            <small class="text-muted">Formato: AAAA-MM</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Navegación Rápida</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="cambiarMes(-1)">
                                    <i class="bi bi-chevron-left"></i> Mes Anterior
                                </button>
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="cambiarMes(1)">
                                    Mes Siguiente <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="alert alert-info w-100 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Mostrando: <strong><?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?></strong>
                                <br>
                                <small>
                                    <?php 
                                    $count_movimientos = $movimientos_mes ? $movimientos_mes->num_rows : 0;
                                    echo $count_movimientos . " movimiento(s) registrado(s)";
                                    ?>
                                </small>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Lista de meses disponibles (sugerencias) - etiquetas en español -->
                    <?php if(count($meses_options) > 0): ?>
                    <div class="mt-3">
                        <small class="text-muted">Meses disponibles con registros:</small>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php 
                            $mostrados = 0;
                            foreach($meses_options as $mes_option):
                                if($mostrados < 6):
                                    // Extraer año y mes desde el value (AAAA-MM)
                                    $parts = explode('-', $mes_option['value']);
                                    $anio_opt = isset($parts[0]) ? intval($parts[0]) : '';
                                    $mes_num = isset($parts[1]) ? intval($parts[1]) : 0;
                                    $label_es = (isset($mes_nombre[$mes_num]) ? $mes_nombre[$mes_num] : $mes_option['label']) . ' ' . $anio_opt;
                            ?>
                                <a href="?p=V_detalle_almacen&id=<?= intval($id_almacen) ?>&fecha=<?= htmlspecialchars($mes_option['value']) ?>" 
                                   class="badge bg-secondary text-decoration-none">
                                    <?= htmlspecialchars($label_es) ?>
                                </a>
                            <?php 
                                $mostrados++;
                                endif;
                            endforeach; 
                            
                            if(count($meses_options) > 6): ?>
                                <span class="badge bg-light text-dark">
                                    +<?= count($meses_options) - 6 ?> más...
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resumen del Mes -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Entradas Granel</h6>
                            <h4><?= number_format($totales_mes['granel_entrada'] ?? 0, 2) ?> kg</h4>
                            <small class="opacity-75">
                                <?= $totales_mes['granel_entrada'] > 0 ? '+' . number_format($totales_mes['granel_entrada'], 2) : '0.00' ?> kg
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Salidas Granel</h6>
                            <h4><?= number_format($totales_mes['granel_salida'] ?? 0, 2) ?> kg</h4>
                            <small class="opacity-75">
                                <?= $totales_mes['granel_salida'] > 0 ? '-' . number_format($totales_mes['granel_salida'], 2) : '0.00' ?> kg
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Entradas Pacas</h6>
                            <h4><?= number_format($totales_mes['pacas_entrada_kilos'] ?? 0, 2) ?> kg</h4>
                            <small class="opacity-75">
                                <?= $totales_mes['pacas_entrada_kilos'] > 0 ? '+' . number_format($totales_mes['pacas_entrada_kilos'], 2) : '0.00' ?> kg
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Salidas Pacas</h6>
                            <h4><?= number_format($totales_mes['pacas_salida_kilos'] ?? 0, 2) ?> kg</h4>
                            <small class="opacity-75">
                                <?= $totales_mes['pacas_salida_kilos'] > 0 ? '-' . number_format($totales_mes['pacas_salida_kilos'], 2) : '0.00' ?> kg
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Balance Neto del Mes -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card <?= ($totales_mes['granel_entrada'] - $totales_mes['granel_salida']) >= 0 ? 'bg-info' : 'bg-warning' ?> text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Balance Neto Granel</h6>
                            <h3>
                                <?= number_format(($totales_mes['granel_entrada'] ?? 0) - ($totales_mes['granel_salida'] ?? 0), 2) ?> kg
                            </h3>
                            <small class="opacity-75">
                                Entradas: <?= number_format($totales_mes['granel_entrada'] ?? 0, 2) ?> kg | 
                                Salidas: <?= number_format($totales_mes['granel_salida'] ?? 0, 2) ?> kg
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card <?= ($totales_mes['pacas_entrada_kilos'] - $totales_mes['pacas_salida_kilos']) >= 0 ? 'bg-info' : 'bg-warning' ?> text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Balance Neto Pacas</h6>
                            <h3>
                                <?= number_format(($totales_mes['pacas_entrada_kilos'] ?? 0) - ($totales_mes['pacas_salida_kilos'] ?? 0), 2) ?> kg
                            </h3>
                            <small class="opacity-75">
                                Entradas: <?= number_format($totales_mes['pacas_entrada_kilos'] ?? 0, 2) ?> kg | 
                                Salidas: <?= number_format($totales_mes['pacas_salida_kilos'] ?? 0, 2) ?> kg
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs para diferentes secciones -->
            <ul class="nav nav-tabs mb-4" id="detalleAlmacenTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="inventario-tab" data-bs-toggle="tab" 
                            data-bs-target="#inventario" type="button" role="tab">
                        <i class="bi bi-box-seam me-1"></i> Inventario Acumulado
                        <span class="badge bg-primary ms-1">
                            <?= $inventario_actual ? $inventario_actual->num_rows : 0 ?>
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="movimientos-tab" data-bs-toggle="tab" 
                            data-bs-target="#movimientos" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimientos del Mes
                        <span class="badge bg-primary ms-1">
                            <?= $movimientos_mes ? $movimientos_mes->num_rows : 0 ?>
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resumen-tab" data-bs-toggle="tab" 
                            data-bs-target="#resumen" type="button" role="tab">
                        <i class="bi bi-graph-up me-1"></i> Resumen Mensual
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="detalleAlmacenTabContent">
                
                <!-- Tab 1: Inventario Acumulado -->
                <div class="tab-pane fade show active" id="inventario" role="tabpanel">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Inventario acumulado hasta <?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?>:</strong>
                        Este es el saldo total de productos que había en el almacén al final del mes seleccionado.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Granel (kg)</th>
                                    <th class="text-end">Pacas (cant)</th>
                                    <th class="text-end">Pacas (kg)</th>
                                    <th class="text-end">Total (kg)</th>
                                    <th class="text-end">Peso Promedio</th>
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
                                        
                                        echo "<tr>
                                            <td>{$contador}</td>
                                            <td>
                                                <strong>{$prod['cod']}</strong><br>
                                                <small class=\"text-muted\">{$prod['nom_pro']}</small>
                                            </td>
                                            <td>
                                                " . ($prod['granel_acumulado'] > 0 ? '<span class="badge bg-warning">Granel</span>' : '') . "
                                                " . ($prod['pacas_cant_acumulado'] > 0 ? '<span class="badge bg-info">Pacas</span>' : '') . "
                                            </td>
                                            <td class=\"text-end\">" . number_format($prod['granel_acumulado'], 2) . " kg</td>
                                            <td class=\"text-end\">" . number_format($prod['pacas_cant_acumulado'], 0) . "</td>
                                            <td class=\"text-end\">" . number_format($prod['pacas_kilos_acumulado'], 2) . " kg</td>
                                            <td class=\"text-end\"><strong>" . number_format($total_kilos, 2) . " kg</strong></td>
                                            <td class=\"text-end\">" . number_format($peso_promedio, 2) . " kg</td>
                                        </tr>";
                                        $contador++;
                                    }
                                } else {
                                    echo "<tr>
                                        <td colspan=\"8\" class=\"text-center py-4\">
                                            <div class=\"alert alert-warning mb-0\">
                                                <i class=\"bi bi-exclamation-triangle me-2\"></i>
                                                No hay inventario acumulado en este almacén para {$mes_nombre[$mes_actual]} {$anio_actual}
                                            </div>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTALES ACUMULADOS:</td>
                                    <td class="text-end"><?= number_format($total_general_granel, 2) ?> kg</td>
                                    <td class="text-end"><?= number_format($total_general_pacas_cant, 0) ?></td>
                                    <td class="text-end"><?= number_format($total_general_pacas_kilos, 2) ?> kg</td>
                                    <td class="text-end"><?= number_format($total_general_kilos, 2) ?> kg</td>
                                    <td class="text-end">-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Tab 2: Movimientos del Mes -->
                <div class="tab-pane fade" id="movimientos" role="tabpanel">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Movimientos de <?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?>:</strong>
                        Todos los movimientos de entrada y salida registrados durante el mes seleccionado.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Producto</th>
                                    <th>Tipo Movimiento</th>
                                    <th class="text-end">Granel (kg)</th>
                                    <th class="text-end">Pacas (cant)</th>
                                    <th class="text-end">Pacas (kg)</th>
                                    <th>Origen/Destino</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($movimientos_mes && $movimientos_mes->num_rows > 0) {
                                    while ($mov = $movimientos_mes->fetch_assoc()) {
                                        // Determinar color según tipo de movimiento
                                        $badge_color = 'secondary';
                                        $signo = '';
                                        if ($mov['tipo_movimiento'] == 'entrada' || $mov['tipo_movimiento'] == 'ajuste') {
                                            $badge_color = 'success';
                                            $signo = '+';
                                        } elseif ($mov['tipo_movimiento'] == 'salida') {
                                            $badge_color = 'danger';
                                            $signo = '-';
                                        }
                                        
                                        echo "<tr>
                                            <td><small>{$mov['fecha_formateada']}</small></td>
                                            <td>
                                                <strong>{$mov['cod_producto']}</strong><br>
                                                <small class=\"text-muted\">{$mov['nombre_producto']}</small>
                                            </td>
                                            <td>
                                                <span class=\"badge bg-{$badge_color}\">
                                                    " . strtoupper($mov['tipo_movimiento']) . "
                                                </span>
                                            </td>
                                            <td class=\"text-end\">
                                                " . ($mov['granel_kilos_movimiento'] > 0 ? 
                                                    "<span class=\"text-{$badge_color}\">{$signo}" . number_format($mov['granel_kilos_movimiento'], 2) . " kg</span>" : 
                                                    '<span class="text-muted">-</span>') . "
                                            </td>
                                            <td class=\"text-end\">
                                                " . ($mov['pacas_cantidad_movimiento'] > 0 ? 
                                                    "<span class=\"text-{$badge_color}\">{$signo}" . number_format($mov['pacas_cantidad_movimiento'], 0) . "</span>" : 
                                                    '<span class="text-muted">-</span>') . "
                                            </td>
                                            <td class=\"text-end\">
                                                " . ($mov['pacas_kilos_movimiento'] > 0 ? 
                                                    "<span class=\"text-{$badge_color}\">{$signo}" . number_format($mov['pacas_kilos_movimiento'], 2) . " kg</span>" : 
                                                    '<span class="text-muted">-</span>') . "
                                            </td>
                                            <td>
                                                " . ($mov['folio_captacion'] ? 
                                                    "Captación: <strong>{$mov['folio_captacion']}</strong>" : 
                                                    '<span class="text-muted">Ajuste manual</span>') . "
                                            </td>
                                            <td><small>{$mov['observaciones']}</small></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr>
                                        <td colspan=\"8\" class=\"text-center py-4\">
                                            <div class=\"alert alert-info mb-0\">
                                                <i class=\"bi bi-info-circle me-2\"></i>
                                                No hay movimientos registrados en {$mes_nombre[$mes_actual]} de {$anio_actual}
                                            </div>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab 3: Resumen Mensual -->
                <div class="tab-pane fade" id="resumen" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Resumen por Tipo</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoResumen" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Cálculos del Mes</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Entradas Totales:</strong></td>
                                            <td class="text-end text-success">
                                                <?= number_format(($totales_mes['granel_entrada'] ?? 0) + ($totales_mes['pacas_entrada_kilos'] ?? 0), 2) ?> kg
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Salidas Totales:</strong></td>
                                            <td class="text-end text-danger">
                                                <?= number_format(($totales_mes['granel_salida'] ?? 0) + ($totales_mes['pacas_salida_kilos'] ?? 0), 2) ?> kg
                                            </td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td><strong>Balance Neto:</strong></td>
                                            <td class="text-end fw-bold">
                                                <?php 
                                                $balance = (($totales_mes['granel_entrada'] ?? 0) + ($totales_mes['pacas_entrada_kilos'] ?? 0)) - 
                                                          (($totales_mes['granel_salida'] ?? 0) + ($totales_mes['pacas_salida_kilos'] ?? 0));
                                                $color_class = $balance >= 0 ? 'text-success' : 'text-danger';
                                                ?>
                                                <span class="<?= $color_class ?>">
                                                    <?= number_format($balance, 2) ?> kg
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Movimientos Registrados:</strong></td>
                                            <td class="text-end">
                                                <span class="badge bg-primary">
                                                    <?= $movimientos_mes ? $movimientos_mes->num_rows : 0 ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Productos con Movimiento:</strong></td>
                                            <td class="text-end">
                                                <?php
                                                if ($movimientos_mes && $movimientos_mes->num_rows > 0) {
                                                    mysqli_data_seek($movimientos_mes, 0);
                                                    $productos_unicos = [];
                                                    while ($mov = $movimientos_mes->fetch_assoc()) {
                                                        $productos_unicos[$mov['cod_producto']] = true;
                                                    }
                                                    echo '<span class="badge bg-info">' . count($productos_unicos) . '</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">0</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumen por día -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Movimientos por Día</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Día</th>
                                            <th class="text-end">Entradas Granel</th>
                                            <th class="text-end">Salidas Granel</th>
                                            <th class="text-end">Entradas Pacas</th>
                                            <th class="text-end">Salidas Pacas</th>
                                            <th class="text-end">Total Entradas</th>
                                            <th class="text-end">Total Salidas</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                        
                                        if ($movimientos_dia && $movimientos_dia->num_rows > 0) {
                                            while ($dia = $movimientos_dia->fetch_assoc()) {
                                                $total_entradas = $dia['granel_entrada'] + $dia['pacas_entrada'];
                                                $total_salidas = $dia['granel_salida'] + $dia['pacas_salida'];
                                                $balance_dia = $total_entradas - $total_salidas;
                                                $balance_class = $balance_dia >= 0 ? 'text-success' : 'text-danger';
                                                
                                                echo "<tr>
                                                    <td><strong>" . date('d/m', strtotime($dia['fecha'])) . "</strong></td>
                                                    <td class=\"text-end\">" . ($dia['granel_entrada'] > 0 ? '+' . number_format($dia['granel_entrada'], 2) : '-') . "</td>
                                                    <td class=\"text-end\">" . ($dia['granel_salida'] > 0 ? '-' . number_format($dia['granel_salida'], 2) : '-') . "</td>
                                                    <td class=\"text-end\">" . ($dia['pacas_entrada'] > 0 ? '+' . number_format($dia['pacas_entrada'], 2) : '-') . "</td>
                                                    <td class=\"text-end\">" . ($dia['pacas_salida'] > 0 ? '-' . number_format($dia['pacas_salida'], 2) : '-') . "</td>
                                                    <td class=\"text-end text-success\">" . number_format($total_entradas, 2) . "</td>
                                                    <td class=\"text-end text-danger\">" . number_format($total_salidas, 2) . "</td>
                                                    <td class=\"text-end fw-bold {$balance_class}\">" . number_format($balance_dia, 2) . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr>
                                                <td colspan=\"8\" class=\"text-center\">
                                                    <div class=\"alert alert-warning mb-0\">
                                                        No hay movimientos por día en este mes
                                                    </div>
                                                </td>
                                            </tr>";
                                        }
                                        ?>
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

<script>
// Función para cambiar de mes (navegación)
function cambiarMes(direccion) {
    const fechaInput = document.getElementById('fecha_mes');
    const fecha = new Date(fechaInput.value + '-01');
    
    fecha.setMonth(fecha.getMonth() + direccion);
    
    const nuevoMes = fecha.getMonth() + 1;
    const nuevoAnio = fecha.getFullYear();
    const nuevaFecha = nuevoAnio + '-' + nuevoMes.toString().padStart(2, '0');
    
    fechaInput.value = nuevaFecha;
    
    // Enviar el formulario
    const form = fechaInput.closest('form');
    form.submit();
}

// Función para inicializar gráfico
function inicializarGrafico() {
    const ctx = document.getElementById('graficoResumen').getContext('2d');
    
    const datos = {
        labels: ['Entradas Granel', 'Salidas Granel', 'Entradas Pacas', 'Salidas Pacas'],
        datasets: [{
            label: 'Kilogramos',
            data: [
                <?= $totales_mes['granel_entrada'] ?? 0 ?>,
                <?= $totales_mes['granel_salida'] ?? 0 ?>,
                <?= $totales_mes['pacas_entrada_kilos'] ?? 0 ?>,
                <?= $totales_mes['pacas_salida_kilos'] ?? 0 ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.7)',    // Verde para entradas granel
                'rgba(220, 53, 69, 0.7)',    // Rojo para salidas granel
                'rgba(23, 162, 184, 0.7)',   // Azul para entradas pacas
                'rgba(255, 193, 7, 0.7)'     // Amarillo para salidas pacas
            ],
            borderColor: [
                'rgb(40, 167, 69)',
                'rgb(220, 53, 69)',
                'rgb(23, 162, 184)',
                'rgb(255, 193, 7)'
            ],
            borderWidth: 1
        }]
    };
    
    const config = {
        type: 'bar',
        data: datos,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Resumen de Movimientos - <?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kilogramos (kg)'
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    // Inicializar tabs
    $('#detalleAlmacenTab button').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Inicializar gráfico si existe el canvas
    if (document.getElementById('graficoResumen')) {
        inicializarGrafico();
    }
    
    // Mejorar el input de fecha
    $('#fecha_mes').focus(function() {
        this.showPicker ? this.showPicker() : this.type = 'date';
    }).blur(function() {
        if (this.type === 'date') {
            this.type = 'month';
        }
    });
});
</script>