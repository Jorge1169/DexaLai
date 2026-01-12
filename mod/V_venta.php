<?php
// V_venta.php - Módulo para ver detalles de una venta (Rediseño ERP compacto)

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

// Obtener el primer producto (ya que solo se vende uno)
$producto = $detalles->fetch_assoc();
?>

<div class="container-fluid px-3 py-3" style="max-width: 1400px; margin: 0 auto;">
    <!-- Header compacto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <button onclick="window.close()" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <h3 class="mb-1 fw-bold">Detalle de Venta</h3>
                        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                        </nav>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-printer me-2"></i>Imprimir
                    </button>
                    <button class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Exportar
                    </button>
                </div>
            </div>
            
            <!-- Tarjeta de estado -->
            <div class="card border-0 shadow mb-3">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="bi bi-file-text text-primary fs-2"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($venta['folio_compuesto']) ?></h4>
                                    <small class="text-muted">Folio del documento</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-4">
                                    <small class="text-muted d-block">Fecha Venta</small>
                                    <strong><?= htmlspecialchars($venta['fecha_formateada']) ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Zona</small>
                                    <strong><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Responsable</small>
                                    <strong><?= htmlspecialchars($venta['nombre_usuario']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-end">
                                <div class="me-3">
                                    <small class="text-muted d-block">Estado</small>
                                    <span class="badge bg-success">Completada</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Creado</small>
                                    <small><?= htmlspecialchars($venta['fecha_creacion_formateada']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Columna principal - Información del producto -->
        <div class="col-lg-8">
            <!-- Tarjeta principal del producto -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header py-2 px-3 border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam text-primary me-2"></i>Detalles del Producto
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">#</th>
                                    <th>Producto</th>
                                    <th class="text-center">Precio Unitario</th>
                                    <th class="text-center">Pacas</th>
                                    <th class="text-center">Kilos</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($producto): ?>
                                <tr>
                                    <td class="text-center align-middle">1</td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($producto['cod_producto']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($producto['nombre_producto']) ?></div>
                                        <?php if (!empty($producto['observaciones'])): ?>
                                        <div class="mt-1">
                                            <small class="text-info">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <?= htmlspecialchars($producto['observaciones']) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark fs-6">
                                            $<?= number_format($producto['precio_venta'], 2) ?>
                                        </span>
                                        <div class="text-muted small mt-1">por kg</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="fw-bold fs-5"><?= number_format($producto['pacas_cantidad'], 0) ?></div>
                                        <div class="text-muted small">unidades</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="fw-bold fs-5"><?= number_format($producto['total_kilos'], 2) ?></div>
                                        <div class="text-muted small">kilogramos</div>
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="fw-bold fs-5 text-success">
                                            $<?= number_format($producto['total_kilos'] * $producto['precio_venta'], 2) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Totales:</td>
                                    <td class="text-center fw-bold">
                                        <?= number_format($total_kilos, 2) ?> kg
                                    </td>
                                    <td class="text-end fw-bold fs-5 text-success">
                                        $<?= number_format($total_venta, 2) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Información de origen y destino en tarjetas compactas -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-header py-2 px-3 border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-shop text-primary me-2"></i>Origen
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($venta['nombre_almacen']) ?></div>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_almacen']) ?></small>
                                </div>
                            </div>
                            <div class="border-start border-3 border-primary ps-3 mt-3">
                                <small class="text-muted d-block">Bodega de Salida</small>
                                <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-header py-2 px-3 border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-person-badge text-success me-2"></i>Destino
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-person text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($venta['nombre_cliente']) ?></div>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_cliente']) ?></small>
                                </div>
                            </div>
                            <div class="border-start border-3 border-success ps-3 mt-3">
                                <small class="text-muted d-block">Bodega de Destino</small>
                                <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral - Resumen y flete -->
        <div class="col-lg-4">
            <!-- Resumen financiero compacto -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header py-2 px-3 border-bottom">
                    <h6 class="mb-0">
                        <i class="bi bi-calculator text-warning me-2"></i>Resumen Financiero
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Valor de venta:</span>
                                <span class="fw-semibold">$<?= number_format($total_venta, 2) ?></span>
                            </div>
                            <?php if ($total_flete > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Costo de flete:</span>
                                <span class="text-danger">-$<?= number_format($total_flete, 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Ganancia neta:</span>
                                <span class="fw-bold fs-5 <?= $total_general >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($total_general, 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Métricas rápidas -->
                        <div class="border rounded p-2 bg-body-tertiary">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Precio promedio</small>
                                    <strong>$<?= number_format($total_kilos > 0 ? $total_venta / $total_kilos : 0, 2) ?>/kg</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Peso por paca</small>
                                    <strong><?= number_format($total_pacas > 0 ? $total_kilos / $total_pacas : 0, 2) ?> kg</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de flete (si aplica) -->
            <?php if ($flete->num_rows > 0): 
                mysqli_data_seek($flete, 0);
                $flete_data = $flete->fetch_assoc();
            ?>
            <div class="card border-0 shadow mb-3">
                <div class="card-header py-2 px-3 border-bottom">
                    <h6 class="mb-0">
                        <i class="bi bi-truck text-info me-2"></i>Información de Flete
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="bi bi-truck text-info"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($venta['nombre_fletero']) ?></div>
                            <small class="text-muted">
                                <i class="bi bi-upc-scan me-1"></i>
                                <?= htmlspecialchars($venta['placas_fletero']) ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="border rounded p-2 bg-body-tertiary">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Tipo</small>
                                <span class="badge bg-info"><?= htmlspecialchars($flete_data['tipo_flete']) ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Costo</small>
                                <strong>$<?= number_format($total_flete, 2) ?></strong>
                            </div>
                            <?php if ($flete_data['tipo_flete'] == 'Por tonelada'): ?>
                            <div class="col-12">
                                <small class="text-muted d-block">Precio por tonelada</small>
                                <strong>$<?= number_format($flete_data['precio_flete'], 2) ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información adicional -->
            <div class="card border-0 shadow">
                <div class="card-header py-2 px-3 border-bottom">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle text-secondary me-2"></i>Información Adicional
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Zona</small>
                            <strong><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Folio Simple</small>
                            <strong>#<?= htmlspecialchars($venta['folio_simple']) ?></strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Creado</small>
                            <strong><?= htmlspecialchars($venta['fecha_creacion_formateada']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para ERP compacto -->
<style>
:root {
    --erp-primary: #2c3e50;
    --erp-secondary: #f8f9fa;
    --erp-border: #dee2e6;
    --erp-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}


.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background-color: #f8f9fa;
    padding: 10px 12px;
}

.table td {
    padding: 12px;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.03);
    transition: background-color 0.2s ease;
}

/* Estilos para métricas */
.border-start {
    border-left-width: 3px !important;
}

.text-muted {
    color: #6c757d !important;
    font-size: 0.85rem;
}

/* Mejoras responsivas */
@media (max-width: 768px) {
    .container-fluid {
        padding: 10px !important;
    }
    
    .card-body {
        padding: 12px !important;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}

/* Estilos para números y montos */
.fw-bold {
    font-weight: 600 !important;
}

.text-success {
    color: #198754 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}

/* Scroll suave */
.table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
}

.table-responsive::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// Script para mejor experiencia de usuario
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar ventana con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.close();
        }
    });
    
    // Agregar tooltips si se necesitan
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mejorar la experiencia de impresión
    document.querySelector('[class*="btn-outline-primary"]').addEventListener('click', function() {
        window.print();
    });
});
</script>