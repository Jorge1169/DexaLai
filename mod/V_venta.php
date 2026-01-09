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
                     v.folio as folio_venta,
                     c.cod as cod_cliente, c.nombre as nombre_cliente,
                     a.cod as cod_almacen, a.nombre as nombre_almacen,
                     z.PLANTA as nombre_zona,
                     t.placas as placas_fletero, t.razon_so as nombre_fletero,
                     d_alm.cod_al as cod_bodega_almacen, d_alm.noma as nombre_bodega_almacen,
                     d_cli.cod_al as cod_bodega_cliente, d_cli.noma as nombre_bodega_cliente,
                     u.nombre as nombre_usuario
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

if ($flete_data = $flete->fetch_assoc()) {
    $total_flete = $flete_data['precio_flete'];
    if ($flete_data['tipo_flete'] == 'Por tonelada') {
        $total_flete = $total_flete * ($total_kilos / 1000); // Convertir kilos a toneladas
    }
}
$total_general = $total_venta + $total_flete;

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
    <!-- Encabezado -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-primary">
                            <i class="bi bi-receipt me-2"></i>Venta: 
                            <span class="text-dark"><?= htmlspecialchars($venta['folio_venta']) ?></span>
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar me-1"></i><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?> 
                            • <i class="bi bi-geo-alt ms-2 me-1"></i><?= htmlspecialchars($venta['nombre_zona']) ?>
                            • <i class="bi bi-person ms-2 me-1"></i><?= htmlspecialchars($venta['nombre_usuario']) ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?p=ventas_info" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Volver
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bi bi-printer me-1"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Pacas</h6>
                            <h3 class="mb-0 text-success"><?= number_format($total_pacas, 0) ?></h3>
                            <small class="text-muted">pacas vendidas</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-box-seam fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Kilos</h6>
                            <h3 class="mb-0 text-info"><?= number_format($total_kilos, 2) ?></h3>
                            <small class="text-muted">kilogramos vendidos</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-scale fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Venta Total</h6>
                            <h3 class="mb-0 text-warning">$<?= number_format($total_venta, 2) ?></h3>
                            <small class="text-muted">valor de productos</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-cash-coin fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total General</h6>
                            <h3 class="mb-0 text-danger">$<?= number_format($total_general, 2) ?></h3>
                            <small class="text-muted">incluye flete</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-currency-dollar fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Principal - Tres columnas -->
    <div class="row">
        <!-- Columna 1: Detalles de Productos -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>Detalles de Productos Vendidos
                    </h6>
                    <span class="badge bg-primary"><?= $detalles->num_rows ?> productos</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Producto</th>
                                    <th class="border-0 text-end">Precio</th>
                                    <th class="border-0 text-end">Pacas</th>
                                    <th class="border-0 text-end">Kilos</th>
                                    <th class="border-0 text-end">Subtotal</th>
                                    <th class="border-0">Obs.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($detalles->num_rows > 0) {
                                    while ($detalle = $detalles->fetch_assoc()) {
                                        $subtotal = $detalle['total_kilos'] * $detalle['precio_venta'];
                                        ?>
                                        <tr class="border-bottom">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                                        <i class="bi bi-box text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block"><?= htmlspecialchars($detalle['cod_producto']) ?></strong>
                                                        <small class="text-muted"><?= htmlspecialchars($detalle['nombre_producto']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end align-middle">
                                                <span class="fw-bold">$<?= number_format($detalle['precio_venta'], 2) ?></span>
                                                <small class="d-block text-muted">/kg</small>
                                            </td>
                                            <td class="text-end align-middle">
                                                <span class="fw-bold"><?= number_format($detalle['pacas_cantidad'], 0) ?></span>
                                            </td>
                                            <td class="text-end align-middle">
                                                <span class="fw-bold"><?= number_format($detalle['total_kilos'], 2) ?></span>
                                                <small class="d-block text-muted">kg</small>
                                            </td>
                                            <td class="text-end align-middle">
                                                <span class="fw-bold text-success">$<?= number_format($subtotal, 2) ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <small class="text-muted"><?= htmlspecialchars($detalle['observaciones']) ?></small>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="alert alert-warning mb-0">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                No hay detalles de producto registrados
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="2" class="border-0"><strong>TOTALES:</strong></td>
                                    <td class="border-0 text-end fw-bold"><?= number_format($total_pacas, 0) ?></td>
                                    <td class="border-0 text-end fw-bold"><?= number_format($total_kilos, 2) ?> kg</td>
                                    <td class="border-0 text-end fw-bold text-success">$<?= number_format($total_venta, 2) ?></td>
                                    <td class="border-0"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna 2: Información de Partes -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-people me-2"></i>Partes Involucradas</h6>
                </div>
                <div class="card-body">
                    <!-- Cliente -->
                    <div class="card border mb-3">
                        <div class="card-header bg-success bg-opacity-10 border-bottom py-2">
                            <h6 class="mb-0"><i class="bi bi-person-check me-2 text-success"></i>Cliente</h6>
                        </div>
                        <div class="card-body py-3">
                            <h6 class="text-success mb-2"><?= htmlspecialchars($venta['nombre_cliente']) ?></h6>
                            <p class="mb-1"><small><strong>Código:</strong> <?= htmlspecialchars($venta['cod_cliente']) ?></small></p>
                            <div class="mt-3">
                                <label class="form-label small text-muted mb-1">Bodega Destino:</label>
                                <div class="alert alert-info py-2 mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    <strong><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></strong><br>
                                    <small><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Almacén -->
                    <div class="card border">
                        <div class="card-header bg-primary bg-opacity-10 border-bottom py-2">
                            <h6 class="mb-0"><i class="bi bi-building me-2 text-primary"></i>Almacén de Origen</h6>
                        </div>
                        <div class="card-body py-3">
                            <h6 class="text-primary mb-2"><?= htmlspecialchars($venta['nombre_almacen']) ?></h6>
                            <p class="mb-1"><small><strong>Código:</strong> <?= htmlspecialchars($venta['cod_almacen']) ?></small></p>
                            <div class="mt-3">
                                <label class="form-label small text-muted mb-1">Bodega de Salida:</label>
                                <div class="alert alert-warning py-2 mb-0">
                                    <i class="bi bi-box-arrow-up me-2"></i>
                                    <strong><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></strong><br>
                                    <small><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda Fila: Flete y Resumen -->
    <div class="row">
        <!-- Columna 3: Información de Flete -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Información de Flete</h6>
                </div>
                <div class="card-body">
                    <?php if ($flete->num_rows > 0): 
                        mysqli_data_seek($flete, 0);
                        $flete_data = $flete->fetch_assoc();
                        ?>
                        <!-- Fletero -->
                        <div class="card border mb-3">
                            <div class="card-header bg-info bg-opacity-10 border-bottom py-2">
                                <h6 class="mb-0"><i class="bi bi-truck me-2 text-info"></i>Fletero</h6>
                            </div>
                            <div class="card-body py-3">
                                <h6 class="text-info mb-2"><?= htmlspecialchars($venta['nombre_fletero']) ?></h6>
                                <p class="mb-2"><small><strong>Placas:</strong> <?= htmlspecialchars($venta['placas_fletero']) ?></small></p>
                                <span class="badge bg-info"><?= htmlspecialchars($flete_data['tipo_flete']) ?></span>
                            </div>
                        </div>

                        <!-- Costos -->
                        <div class="card border">
                            <div class="card-header bg-warning bg-opacity-10 border-bottom py-2">
                                <h6 class="mb-0"><i class="bi bi-calculator me-2 text-warning"></i>Costos de Flete</h6>
                            </div>
                            <div class="card-body py-3">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td><small>Precio Base:</small></td>
                                        <td class="text-end fw-bold">$<?= number_format($flete_data['precio_flete'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td><small>Total Kilos:</small></td>
                                        <td class="text-end"><?= number_format($total_kilos, 2) ?> kg</td>
                                    </tr>
                                    <?php if ($flete_data['tipo_flete'] == 'Por tonelada'): ?>
                                    <tr>
                                        <td><small>Toneladas:</small></td>
                                        <td class="text-end"><?= number_format($total_kilos / 1000, 3) ?> ton</td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="border-top">
                                        <td><strong><small>TOTAL FLETE:</small></strong></td>
                                        <td class="text-end fw-bold text-success">$<?= number_format($total_flete, 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Esta venta no incluye servicio de flete
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna 4: Movimientos de Inventario -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Movimientos de Inventario</h6>
                    <span class="badge bg-primary"><?= $movimientos->num_rows ?></span>
                </div>
                <div class="card-body">
                    <?php if ($movimientos->num_rows > 0): ?>
                        <div class="timeline">
                            <?php 
                            $count = 0;
                            while ($mov = $movimientos->fetch_assoc()):
                                if ($count++ >= 5) break; // Mostrar máximo 5 movimientos
                            ?>
                            <div class="timeline-item border-start border-primary ps-3 pb-3 position-relative">
                                <div class="position-absolute top-0 start-0 translate-middle bg-primary rounded-circle" style="width: 12px; height: 12px;"></div>
                                <small class="text-muted d-block"><?= htmlspecialchars($mov['fecha_formateada']) ?></small>
                                <strong class="d-block"><?= htmlspecialchars($mov['cod_producto']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($mov['nombre_producto']) ?></small>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="badge bg-danger">SALIDA</span>
                                    <small class="text-danger">-<?= number_format($mov['pacas_cantidad_movimiento'], 0) ?> pacas</small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            
                            <?php if ($movimientos->num_rows > 5): ?>
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-ellipsis me-1"></i>
                                        <?= $movimientos->num_rows - 5 ?> movimientos más...
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No se registraron movimientos de inventario
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna 5: Resumen Financiero -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumen Financiero</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Subtotal Productos:</span>
                            <span class="fw-bold">$<?= number_format($total_venta, 2) ?></span>
                        </div>
                        
                        <?php if ($total_flete > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Costo de Flete:</span>
                            <span class="fw-bold text-danger">$<?= number_format($total_flete, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="border-top pt-3 mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">TOTAL GENERAL:</h5>
                                <h4 class="mb-0 text-primary">$<?= number_format($total_general, 2) ?></h4>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <div class="d-flex justify-content-between">
                                    <small>Valor promedio por kilo:</small>
                                    <strong>$<?= number_format($total_kilos > 0 ? $total_venta / $total_kilos : 0, 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small>Peso promedio por paca:</small>
                                    <strong><?= number_format($total_pacas > 0 ? $total_kilos / $total_pacas : 0, 2) ?> kg</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
