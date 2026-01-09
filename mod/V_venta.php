<?php
// V_venta.php - Módulo para ver detalles de una venta (Versión Dashboard)

// Obtener ID de la venta
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_venta <= 0) {
    alert("ID de venta no válido", 0, "ventas_info");
    exit;
}

// Obtener información de la venta
$sql_venta = "SELECT v.*, 
                     CONCAT('V-', z.cod, '-', 
                           DATE_FORMAT(v.fecha_venta, '%y%m'), 
                           LPAD(v.folio, 4, '0')) as folio_compuesto,
                     v.folio as folio_simple,
                     c.cod as cod_cliente, c.nombre as nombre_cliente,
                     a.cod as cod_almacen, a.nombre as nombre_almacen,
                     z.PLANTA as nombre_zona, z.cod as cod_zona,
                     t.placas as placas_fletero, t.razon_so as nombre_fletero,
                     d_alm.cod_al as cod_bodega_almacen, d_alm.noma as nombre_bodega_almacen,
                     d_cli.cod_al as cod_bodega_cliente, d_cli.noma as nombre_bodega_cliente,
                     u.nombre as nombre_usuario,
                     DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_formateada,
                     DATE_FORMAT(v.created_at, '%d/%m/%Y %H:%i') as fecha_creacion_formateada
              FROM ventas v
              LEFT JOIN clientes c ON v.id_cliente = c.id_cli
              LEFT JOIN almacenes a ON v.id_alma = a.id_alma
              LEFT JOIN zonas z ON v.zona = z.id_zone
              LEFT JOIN transportes t ON v.id_transp = t.id_transp
              LEFT JOIN direcciones d_alm ON v.id_direc_alma = d_alm.id_direc
              LEFT JOIN direcciones d_cli ON v.id_direc_cliente = d_cli.id_direc
              LEFT JOIN usuarios u ON v.id_user = u.id_user
              WHERE v.id_venta = ? AND v.status = 1";
$stmt_venta = $conn_mysql->prepare($sql_venta);
$stmt_venta->bind_param('i', $id_venta);
$stmt_venta->execute();
$result_venta = $stmt_venta->get_result();

if (!$result_venta || $result_venta->num_rows == 0) {
    alert("Venta no encontrada", 0, "ventas_info");
    exit;
}

$venta = $result_venta->fetch_assoc();

// Obtener detalles del producto vendido
$sql_detalle = "SELECT vd.*, 
                       p.cod as cod_producto, p.nom_pro as nombre_producto,
                       pr.precio as precio_venta
                FROM venta_detalle vd
                LEFT JOIN productos p ON vd.id_prod = p.id_prod
                LEFT JOIN precios pr ON vd.id_pre_venta = pr.id_precio
                WHERE vd.id_venta = ? AND vd.status = 1";
$stmt_detalle = $conn_mysql->prepare($sql_detalle);
$stmt_detalle->bind_param('i', $id_venta);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();

// Obtener información del flete
$sql_flete = "SELECT vf.*, 
                     p.precio as precio_flete,
                     CASE 
                         WHEN p.tipo = 'MFT' THEN 'Por tonelada'
                         WHEN p.tipo = 'MFV' THEN 'Por viaje'
                         ELSE p.tipo
                     END as tipo_flete
              FROM venta_flete vf
              LEFT JOIN precios p ON vf.id_pre_flete = p.id_precio
              WHERE vf.id_venta = ?";
$stmt_flete = $conn_mysql->prepare($sql_flete);
$stmt_flete->bind_param('i', $id_venta);
$stmt_flete->execute();
$flete = $stmt_flete->get_result();

// Calcular totales
$total_pacas = 0;
$total_kilos = 0;
$total_venta = 0;
$total_flete = 0;

while ($detalle = $detalles->fetch_assoc()) {
    $total_pacas += $detalle['pacas_cantidad'];
    $total_kilos += $detalle['total_kilos'];
    $total_venta += ($detalle['total_kilos'] * $detalle['precio_venta']);
}

// Reiniciar punteros
mysqli_data_seek($detalles, 0);

// Obtener flete
if ($flete_data = $flete->fetch_assoc()) {
    $total_flete = $flete_data['precio_flete'];
    if ($flete_data['tipo_flete'] == 'Por tonelada') {
        $total_flete = $total_flete * ($total_kilos / 1000);
    }
}
$total_general = $total_venta - $total_flete;

// Obtener movimientos de inventario relacionados
$sql_movimientos = "SELECT mi.*, 
                           p.cod as cod_producto, p.nom_pro as nombre_producto,
                           DATE_FORMAT(mi.created_at, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM movimiento_inventario mi
                    LEFT JOIN inventario_bodega ib ON mi.id_inventario = ib.id_inventario
                    LEFT JOIN productos p ON ib.id_prod = p.id_prod
                    WHERE mi.id_venta = ?
                    ORDER BY mi.created_at DESC";
$stmt_movimientos = $conn_mysql->prepare($sql_movimientos);
$stmt_movimientos->bind_param('i', $id_venta);
$stmt_movimientos->execute();
$movimientos = $stmt_movimientos->get_result();
?>

<div class="container mt-3">
    <!-- Encabezado mejorado -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-header encabezado-col text-white py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="bi bi-receipt fs-5"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-white">
                                        Venta: <span class="fw-bold"><?= htmlspecialchars($venta['folio_compuesto']) ?></span>
                                    </h4>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-calendar me-1"></i><?= htmlspecialchars($venta['fecha_formateada']) ?>
                                        </span>
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($venta['nombre_zona']) ?>
                                        </span>
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($venta['nombre_usuario']) ?>
                                        </span>
                                        <span class="badge bg-info">
                                            <i class="bi bi-clock-history me-1"></i>Creado: <?= htmlspecialchars($venta['fecha_creacion_formateada']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                            <div class="d-flex justify-content-md-end gap-2">
                                <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger align-items-center">
                                    <i class="bi bi-x-circle"></i> Cerrar
                                </button>
                                <script>
                                    document.getElementById('btnCerrar').addEventListener('click', function() {
                                        window.close();
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas Compactas -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-box-seam text-primary fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-primary"><?= number_format($total_pacas, 0) ?></h5>
                            <small class="text-muted">Pacas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-bar-chart-line text-success fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success"><?= number_format($total_kilos, 2) ?> kg</h5>
                            <small class="text-muted">Peso Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-cash-coin text-warning fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-warning">$<?= number_format($total_venta, 2) ?></h5>
                            <small class="text-muted">Venta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-currency-dollar text-danger fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-danger">$<?= number_format($total_general, 2) ?></h5>
                            <small class="text-muted">Total General</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal - Diseño en 2 columnas -->
    <div class="row">
        <!-- Columna Izquierda: Productos y Partes -->
        <div class="col-lg-8">
            <!-- Productos Vendidos - Diseño Compacto -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-box-seam me-2"></i>Productos Vendidos
                        </h6>
                        <span class="badge bg-primary"><?= $detalles->num_rows ?> producto(s)</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php
                    if ($detalles->num_rows > 0) {
                        $contador = 0;
                        while ($detalle = $detalles->fetch_assoc()) {
                            $contador++;
                            $subtotal = $detalle['total_kilos'] * $detalle['precio_venta'];
                            ?>
                            <div class="border-bottom p-3 <?= $contador % 2 == 0 ? 'bg-light' : '' ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                                <i class="bi bi-box text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($detalle['cod_producto']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($detalle['nombre_producto']) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="row text-center">
                                            <div class="col-3">
                                                <small class="text-muted d-block">Precio</small>
                                                <strong>$<?= number_format($detalle['precio_venta'], 2) ?></strong>
                                            </div>
                                            <div class="col-2">
                                                <small class="text-muted d-block">Pacas</small>
                                                <strong><?= number_format($detalle['pacas_cantidad'], 0) ?></strong>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted d-block">Kilos</small>
                                                <strong><?= number_format($detalle['total_kilos'], 2) ?></strong>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Subtotal</small>
                                                <strong class="text-success">$<?= number_format($subtotal, 2) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($detalle['observaciones'])): ?>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="bi bi-chat-left-text me-1"></i>
                                            <?= htmlspecialchars($detalle['observaciones']) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="text-center py-4">
                            <i class="bi bi-box-seam text-muted fs-1 mb-2"></i>
                            <p class="text-muted mb-0">No hay productos registrados</p>
                        </div>
                        <?php
                    }
                    ?>
                    
                    <!-- Totales Compactos -->
                    <div class="p-3">
                        <div class="row">
                            <div class="col-md-5">
                                <h6 class="mb-0">Totales:</h6>
                            </div>
                            <div class="col-md-7">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <small class="text-muted d-block">Prom. Precio</small>
                                        <strong>$<?= number_format($total_kilos > 0 ? $total_venta / $total_kilos : 0, 2) ?></strong>
                                    </div>
                                    <div class="col-2">
                                        <small class="text-muted d-block">Pacas</small>
                                        <strong><?= number_format($total_pacas, 0) ?></strong>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted d-block">Kilos</small>
                                        <strong><?= number_format($total_kilos, 2) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Total Venta</small>
                                        <strong class="text-success">$<?= number_format($total_venta, 2) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Partes Involucradas - Diseño Compacto -->
            <div class="row">
                <!-- Cliente -->
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0">
                                <i class="bi bi-person-check me-2"></i>Cliente
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-person fs-5 text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-success"><?= htmlspecialchars($venta['nombre_cliente']) ?></h5>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_cliente']) ?></small>
                                </div>
                            </div>
                            
                            <div class="card border">
                                <div class="card-header py-1">
                                    <small class="fw-bold">Bodega Destino</small>
                                </div>
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building text-info me-2"></i>
                                        <div>
                                            <small class="fw-bold d-block"><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></small>
                                            <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Almacén -->
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0">
                                <i class="bi bi-building me-2"></i>Almacén de Origen
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-box-arrow-up fs-5 text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-primary"><?= htmlspecialchars($venta['nombre_almacen']) ?></h5>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_almacen']) ?></small>
                                </div>
                            </div>
                            
                            <div class="card border">
                                <div class="card-header py-1">
                                    <small class="fw-bold">Bodega de Salida</small>
                                </div>
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-box-arrow-up text-warning me-2"></i>
                                        <div>
                                            <small class="fw-bold d-block"><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></small>
                                            <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Resumen y Detalles -->
        <div class="col-lg-4">
            <!-- Resumen Financiero -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-warning text-white py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-calculator me-2"></i>Resumen Financiero
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td><small>Venta Productos:</small></td>
                                <td class="text-end text-success fw-bold">+ $<?= number_format($total_venta, 2) ?></td>
                            </tr>
                            <?php if ($total_flete > 0): ?>
                            <tr>
                                <td><small>Costo de Flete:</small></td>
                                <td class="text-end text-danger">- $<?= number_format($total_flete, 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-top">
                                <td><strong>Total Ganancia:</strong></td>
                                <td class="text-end fw-bold <?= $total_general >= 0 ? 'text-success' : 'text-danger' ?> fs-5">
                                    $<?= number_format($total_general, 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="mt-3">
                        <div class="alert alert-info mb-2 py-2">
                            <div class="d-flex justify-content-between">
                                <small>Precio promedio:</small>
                                <strong>$<?= number_format($total_kilos > 0 ? $total_venta / $total_kilos : 0, 2) ?> /kg</strong>
                            </div>
                        </div>
                        <div class="alert alert-success py-2 mb-0">
                            <div class="d-flex justify-content-between">
                                <small>Peso por paca:</small>
                                <strong><?= number_format($total_pacas > 0 ? $total_kilos / $total_pacas : 0, 2) ?> kg</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Flete (si existe) -->
            <?php if ($flete->num_rows > 0): 
                mysqli_data_seek($flete, 0);
                $flete_data = $flete->fetch_assoc();
                ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-truck me-2"></i>Flete
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="bi bi-truck fs-5 text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-info"><?= htmlspecialchars($venta['nombre_fletero']) ?></h6>
                                <small class="text-muted">Placas: <?= htmlspecialchars($venta['placas_fletero']) ?></small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Tipo:</small>
                                <span class="badge bg-info"><?= htmlspecialchars($flete_data['tipo_flete']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small>Total Flete:</small>
                                <strong>$<?= number_format($total_flete, 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Movimientos de Inventario -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-arrow-left-right me-2"></i>Movimientos
                        </h6>
                        <span class="badge bg-light text-dark"><?= $movimientos->num_rows ?></span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if ($movimientos->num_rows > 0): 
                        $contador_mov = 0;
                        while ($mov = $movimientos->fetch_assoc()):
                            if ($contador_mov++ >= 10) break;
                    ?>
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted"><?= htmlspecialchars($mov['fecha_formateada']) ?></small>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="badge bg-danger me-2">Salida</span>
                                    <small><?= htmlspecialchars($mov['cod_producto']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="text-danger d-block">-<?= number_format($mov['pacas_cantidad_movimiento'], 0) ?> pacas</small>
                                <small class="text-muted"><?= number_format($mov['pacas_kilos_movimiento'], 2) ?> kg</small>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                        if ($movimientos->num_rows > 10):
                    ?>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            <i class="bi bi-ellipsis me-1"></i>
                            <?= $movimientos->num_rows - 10 ?> movimientos más
                        </small>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-arrow-left-right text-muted fs-3 mb-2"></i>
                        <p class="text-muted mb-0">Sin movimientos</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Estilos adicionales -->
<style>
.card {
    border-radius: 10px;
}
.card-header {
    border-radius: 10px 10px 0 0 !important;
}
.badge {
    font-size: 0.75em;
    padding: 0.25em 0.6em;
}
.timeline-item {
    position: relative;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #0d6efd;
}
</style>