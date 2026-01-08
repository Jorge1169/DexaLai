<?php
// V_venta.php - Módulo para ver detalles de una venta

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

// Reiniciar puntero para usarlo después
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

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-receipt me-2"></i>Detalle de Venta: 
                <span class="badge bg-light text-dark"><?= htmlspecialchars($venta['folio_venta']) ?></span>
            </h5>
            <div>
                <a href="?p=ventas_info" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Lista
                </a>
                <button onclick="window.print()" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>
        <div class="card-body">
            
            <!-- Información General -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-primary">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><i class="bi bi-calendar me-2"></i>Fecha:</strong><br>
                                <?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="bi bi-geo-alt me-2"></i>Zona:</strong><br>
                                <?= htmlspecialchars($venta['nombre_zona']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="bi bi-person me-2"></i>Vendedor:</strong><br>
                                <?= htmlspecialchars($venta['nombre_usuario']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de Resumen -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="card-title text-muted">Total Pacas</h6>
                            <h2 class="text-success"><?= number_format($total_pacas, 0) ?></h2>
                            <small class="text-muted">pacas vendidas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h6 class="card-title text-muted">Total Kilos</h6>
                            <h2 class="text-info"><?= number_format($total_kilos, 2) ?></h2>
                            <small class="text-muted">kilogramos vendidos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h6 class="card-title text-muted">Venta Total</h6>
                            <h2 class="text-warning">$<?= number_format($total_venta, 2) ?></h2>
                            <small class="text-muted">valor de productos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h6 class="card-title text-muted">Total General</h6>
                            <h2 class="text-danger">$<?= number_format($total_general, 2) ?></h2>
                            <small class="text-muted">incluye flete</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs para diferentes secciones -->
            <ul class="nav nav-tabs mb-4" id="ventaTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="detalles-tab" data-bs-toggle="tab" 
                            data-bs-target="#detalles" type="button" role="tab">
                        <i class="bi bi-box-seam me-1"></i> Detalles de Venta
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="partes-tab" data-bs-toggle="tab" 
                            data-bs-target="#partes" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Partes Involucradas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventario-tab" data-bs-toggle="tab" 
                            data-bs-target="#inventario" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimientos Inventario
                        <span class="badge bg-primary ms-1"><?= $movimientos->num_rows ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="flete-tab" data-bs-toggle="tab" 
                            data-bs-target="#flete" type="button" role="tab">
                        <i class="bi bi-truck me-1"></i> Información de Flete
                        <span class="badge bg-primary ms-1"><?= $flete->num_rows ?></span>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ventaTabContent">
                
                <!-- Tab 1: Detalles de Venta -->
                <div class="tab-pane fade show active" id="detalles" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Precio Unitario</th>
                                    <th class="text-end">Pacas</th>
                                    <th class="text-end">Kilos</th>
                                    <th class="text-end">Subtotal</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($detalles->num_rows > 0) {
                                    while ($detalle = $detalles->fetch_assoc()) {
                                        $subtotal = $detalle['total_kilos'] * $detalle['precio_venta'];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($detalle['cod_producto']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($detalle['nombre_producto']) ?></small>
                                            </td>
                                            <td class="text-end">$<?= number_format($detalle['precio_venta'], 2) ?>/kg</td>
                                            <td class="text-end"><?= number_format($detalle['pacas_cantidad'], 0) ?></td>
                                            <td class="text-end"><?= number_format($detalle['total_kilos'], 2) ?> kg</td>
                                            <td class="text-end"><strong>$<?= number_format($subtotal, 2) ?></strong></td>
                                            <td><small><?= htmlspecialchars($detalle['observaciones']) ?></small></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="alert alert-warning mb-0">
                                                No hay detalles de producto registrados para esta venta
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">TOTALES:</td>
                                    <td class="text-end"><?= number_format($total_pacas, 0) ?></td>
                                    <td class="text-end"><?= number_format($total_kilos, 2) ?> kg</td>
                                    <td class="text-end">$<?= number_format($total_venta, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Tab 2: Partes Involucradas -->
                <div class="tab-pane fade" id="partes" role="tabpanel">
                    <div class="row">
                        <!-- Cliente -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($venta['nombre_cliente']) ?></h5>
                                    <p class="mb-1"><strong>Código:</strong> <?= htmlspecialchars($venta['cod_cliente']) ?></p>
                                    <p class="mb-1"><strong>Bodega Destino:</strong></p>
                                    <div class="alert alert-info py-2">
                                        <i class="bi bi-building me-2"></i>
                                        <strong><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></strong><br>
                                        <small><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Almacén -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-building me-2"></i>Almacén de Origen</h6>
                                </div>
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($venta['nombre_almacen']) ?></h5>
                                    <p class="mb-1"><strong>Código:</strong> <?= htmlspecialchars($venta['cod_almacen']) ?></p>
                                    <p class="mb-1"><strong>Bodega de Salida:</strong></p>
                                    <div class="alert alert-warning py-2">
                                        <i class="bi bi-box-arrow-up me-2"></i>
                                        <strong><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></strong><br>
                                        <small><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 3: Movimientos de Inventario -->
                <div class="tab-pane fade" id="inventario" role="tabpanel">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Movimientos de inventario generados por esta venta
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Pacas Movidas</th>
                                    <th class="text-end">Kilos Movidos</th>
                                    <th>Inventario Anterior</th>
                                    <th>Inventario Nuevo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($movimientos->num_rows > 0) {
                                    while ($mov = $movimientos->fetch_assoc()) {
                                        $pacas_anterior = $mov['pacas_cantidad_anterior'];
                                        $pacas_nuevo = $mov['pacas_cantidad_nuevo'];
                                        $kilos_anterior = $mov['pacas_kilos_anterior'];
                                        $kilos_nuevo = $mov['pacas_kilos_nuevo'];
                                        ?>
                                        <tr>
                                            <td><small><?= htmlspecialchars($mov['fecha_formateada']) ?></small></td>
                                            <td>
                                                <strong><?= htmlspecialchars($mov['cod_producto']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($mov['nombre_producto']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">SALIDA</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-danger">-<?= number_format($mov['pacas_cantidad_movimiento'], 0) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-danger">-<?= number_format($mov['pacas_kilos_movimiento'], 2) ?> kg</span>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Pacas:</strong> <?= number_format($pacas_anterior, 0) ?><br>
                                                    <strong>Kilos:</strong> <?= number_format($kilos_anterior, 2) ?> kg
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Pacas:</strong> <?= number_format($pacas_nuevo, 0) ?><br>
                                                    <strong>Kilos:</strong> <?= number_format($kilos_nuevo, 2) ?> kg
                                                </small>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="alert alert-warning mb-0">
                                                No se registraron movimientos de inventario para esta venta
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab 4: Información de Flete -->
                <div class="tab-pane fade" id="flete" role="tabpanel">
                    <?php if ($flete->num_rows > 0): 
                        mysqli_data_seek($flete, 0);
                        $flete_data = $flete->fetch_assoc();
                        ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Fletero</h6>
                                    </div>
                                    <div class="card-body">
                                        <h5><?= htmlspecialchars($venta['nombre_fletero']) ?></h5>
                                        <p class="mb-1"><strong>Placas:</strong> <?= htmlspecialchars($venta['placas_fletero']) ?></p>
                                        <p class="mb-1"><strong>Tipo de Flete:</strong> <?= htmlspecialchars($flete_data['tipo_flete']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Costos de Flete</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Precio Base:</strong></td>
                                                <td class="text-end">$<?= number_format($flete_data['precio_flete'], 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Kilos:</strong></td>
                                                <td class="text-end"><?= number_format($total_kilos, 2) ?> kg</td>
                                            </tr>
                                            <?php if ($flete_data['tipo_flete'] == 'Por tonelada'): ?>
                                            <tr>
                                                <td><strong>Toneladas:</strong></td>
                                                <td class="text-end"><?= number_format($total_kilos / 1000, 3) ?> ton</td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr class="table-success">
                                                <td><strong>Total Flete:</strong></td>
                                                <td class="text-end fw-bold">$<?= number_format($total_flete, 2) ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Esta venta no incluye servicio de flete
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- Resumen Final -->
            <div class="card mt-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumen Financiero</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Subtotal Productos:</strong></td>
                                    <td class="text-end">$<?= number_format($total_venta, 2) ?></td>
                                </tr>
                                <?php if ($total_flete > 0): ?>
                                <tr>
                                    <td><strong>Costo de Flete:</strong></td>
                                    <td class="text-end">$<?= number_format($total_flete, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-primary fw-bold">
                                    <td><strong>TOTAL GENERAL:</strong></td>
                                    <td class="text-end fs-5">$<?= number_format($total_general, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar esta venta?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción eliminará la venta y revertirá el movimiento de inventario.
                </div>
                <form id="formEliminar" method="post" action="?p=eliminar_venta">
                    <input type="hidden" name="id_venta" id="id_venta_eliminar">
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de eliminación:</label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('formEliminar').submit()">
                    <i class="bi bi-trash me-1"></i> Eliminar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tabs
$(document).ready(function() {
    $('#ventaTab button').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>

<!-- Estilos para impresión -->
<style>
@media print {
    .card-header, .nav-tabs, .btn, .modal {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }
    body {
        font-size: 12pt !important;
    }
    .table th, .table td {
        padding: 4px !important;
        font-size: 11pt !important;
    }
    .alert {
        padding: 8px !important;
        margin-bottom: 8px !important;
    }
}
</style>