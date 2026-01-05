<?php
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

// Obtener mes y año para el reporte (mes actual por defecto)
$mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$mes_nombre = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

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
            
            <!-- Selector de Mes/Año -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar me-2"></i>Reporte Mensual
                    </h5>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="p" value="V_detalle_almacen">
                        <input type="hidden" name="id" value="<?= $id_almacen ?>">
                        <div class="col-md-3">
                            <label class="form-label">Mes</label>
                            <select name="mes" class="form-select" onchange="this.form.submit()">
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $mes_actual ? 'selected' : '' ?>>
                                        <?= $mes_nombre[$m] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Año</label>
                            <select name="anio" class="form-select" onchange="this.form.submit()">
                                <?php 
                                $anio_inicio = 2023; // Puedes ajustar esto
                                $anio_fin = date('Y') + 1;
                                for($a = $anio_fin; $a >= $anio_inicio; $a--): ?>
                                    <option value="<?= $a ?>" <?= $a == $anio_actual ? 'selected' : '' ?>>
                                        <?= $a ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="alert alert-info w-100 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Mostrando: <strong><?= $mes_nombre[$mes_actual] ?> <?= $anio_actual ?></strong>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resumen del Mes -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Entradas Granel</h6>
                            <h4><?= number_format($totales_mes['granel_entrada'] ?? 0, 2) ?> kg</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Salidas Granel</h6>
                            <h4><?= number_format($totales_mes['granel_salida'] ?? 0, 2) ?> kg</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Entradas Pacas</h6>
                            <h4><?= number_format($totales_mes['pacas_entrada_kilos'] ?? 0, 2) ?> kg</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Salidas Pacas</h6>
                            <h4><?= number_format($totales_mes['pacas_salida_kilos'] ?? 0, 2) ?> kg</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs para diferentes secciones -->
            <ul class="nav nav-tabs mb-4" id="detalleAlmacenTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="inventario-tab" data-bs-toggle="tab" 
                            data-bs-target="#inventario" type="button" role="tab">
                        <i class="bi bi-box-seam me-1"></i> Inventario Actual
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="movimientos-tab" data-bs-toggle="tab" 
                            data-bs-target="#movimientos" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimientos del Mes
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="detalleAlmacenTabContent">
                
                <!-- Tab 1: Inventario Actual -->
                <div class="tab-pane fade show active" id="inventario" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Granel (kg)</th>
                                    <th>Pacas (cant)</th>
                                    <th>Pacas (kg)</th>
                                    <th>Total (kg)</th>
                                    <th>Peso Promedio</th>
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
                                        <td colspan=\"8\" class=\"text-center\">
                                            <div class=\"alert alert-warning\">
                                                <i class=\"bi bi-exclamation-triangle me-2\"></i>
                                                No hay inventario en este almacén
                                            </div>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTALES:</td>
                                    <td class="text-end"><?= number_format($total_general_granel, 2) ?> kg</td>
                                    <td class="text-end"><?= number_format($total_general_pacas_cant, 0) ?></td>
                                    <td class="text-end"><?= number_format($total_general_pacas_kilos, 2) ?> kg</td>
                                    <td class="text-end"><?= number_format($total_general_kilos, 2) ?> kg</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Tab 2: Movimientos del Mes -->
                <div class="tab-pane fade" id="movimientos" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Granel (kg)</th>
                                    <th>Pacas (cant)</th>
                                    <th>Pacas (kg)</th>
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
                                            <td>{$mov['fecha_formateada']}</td>
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
                                                    "{$signo}" . number_format($mov['granel_kilos_movimiento'], 2) . " kg" : 
                                                    '<span class="text-muted">-</span>') . "
                                            </td>
                                            <td class=\"text-end\">
                                                " . ($mov['pacas_cantidad_movimiento'] > 0 ? 
                                                    "{$signo}" . number_format($mov['pacas_cantidad_movimiento'], 0) : 
                                                    '<span class="text-muted">-</span>') . "
                                            </td>
                                            <td class=\"text-end\">
                                                " . ($mov['pacas_kilos_movimiento'] > 0 ? 
                                                    "{$signo}" . number_format($mov['pacas_kilos_movimiento'], 2) . " kg" : 
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
                                        <td colspan=\"8\" class=\"text-center\">
                                            <div class=\"alert alert-info\">
                                                <i class=\"bi bi-info-circle me-2\"></i>
                                                No hay movimientos en {$mes_nombre[$mes_actual]} de {$anio_actual}
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

<script>
// Inicializar tabs
$(document).ready(function() {
    $('#detalleAlmacenTab button').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
</script>