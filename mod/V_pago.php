<?php
$id_pago = intval($_GET['id'] ?? 0);
if ($id_pago <= 0) {
    alert("ID de pago no válido", 0, "pagos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_factura_pago'])) {
    $factura_pago = trim($_POST['factura_pago'] ?? '');
    $factura_pago_anterior = '';

    $stmtActual = $conn_mysql->prepare("SELECT factura_pago FROM pagos WHERE id_pago = ? LIMIT 1");
    if ($stmtActual) {
        $stmtActual->bind_param('i', $id_pago);
        $stmtActual->execute();
        $resActual = $stmtActual->get_result();
        if ($resActual && $resActual->num_rows > 0) {
            $rowActual = $resActual->fetch_assoc();
            $factura_pago_anterior = trim($rowActual['factura_pago'] ?? '');
        }
    }

    if ($factura_pago !== '') {
        $stmtDup = $conn_mysql->prepare("SELECT id_pago, folio FROM pagos WHERE factura_pago = ? AND status = 1 AND id_pago != ? LIMIT 1");
        if ($stmtDup) {
            $stmtDup->bind_param('si', $factura_pago, $id_pago);
            $stmtDup->execute();
            $resDup = $stmtDup->get_result();
            if ($resDup && $resDup->num_rows > 0) {
                $dup = $resDup->fetch_assoc();
                $error_factura_pago = "La factura '{$factura_pago}' ya está usada en otro pago activo (folio interno {$dup['folio']}).";
            }
        }
    }

    if (!isset($error_factura_pago)) {
        $facturaCambio = ($factura_pago !== $factura_pago_anterior);

        $setSql = [
            "factura_pago = ?",
            "factura_actualizada = NOW()",
            "updated_at = NOW()"
        ];

        if ($facturaCambio) {
            $setSql[] = "doc_factura_pago = NULL";
            $setSql[] = "com_factura_pago = NULL";
            $setSql[] = "impuesto_traslado = NULL";
            $setSql[] = "impuesto_retenido = NULL";
            $setSql[] = "subtotal_invoice = NULL";
            $setSql[] = "total_invoice = NULL";
            $setSql[] = "aliaspag = NULL";
            $setSql[] = "foliopag = NULL";
        }

        $stmtUpd = $conn_mysql->prepare("UPDATE pagos SET " . implode(', ', $setSql) . " WHERE id_pago = ?");
        if ($stmtUpd) {
            $stmtUpd->bind_param('si', $factura_pago, $id_pago);
            if ($stmtUpd->execute()) {
                logActivity('PAGO_FACTURA', "Actualizó factura de pago {$id_pago}: {$factura_pago}");
                alert('Factura de pago actualizada con éxito', 1, "V_pago&id={$id_pago}");
                exit;
            }
        }
        alert('No fue posible actualizar la factura del pago', 0, "V_pago&id={$id_pago}");
        exit;
    }
}

$sqlPago = "SELECT p.*, z.cod as cod_zona, z.tipo as tipo_zona, z.PLANTA as nombre_zona, 
                   prov.cod as cod_proveedor, prov.rs as nombre_proveedor,
                   DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_fmt,
                   DATE_FORMAT(p.factura_actualizada, '%d/%m/%Y %H:%i') as factura_actualizada_fmt
            FROM pagos p
            INNER JOIN zonas z ON p.zona = z.id_zone
            LEFT JOIN proveedores prov ON p.id_prov = prov.id_prov
            WHERE p.id_pago = ? LIMIT 1";
$stmtPago = $conn_mysql->prepare($sqlPago);
$stmtPago->bind_param('i', $id_pago);
$stmtPago->execute();
$resPago = $stmtPago->get_result();

if (!$resPago || $resPago->num_rows === 0) {
    alert("Pago no encontrado", 0, "pagos");
    exit;
}

$pago = $resPago->fetch_assoc();
if (strtoupper(trim($pago['tipo_zona'] ?? '')) !== 'SUR') {
    alert("Este módulo aplica para pagos SUR", 0, "pagos");
    exit;
}

$docFacturaPago = trim($pago['doc_factura_pago'] ?? '');
$comFacturaPago = trim($pago['com_factura_pago'] ?? '');
$aliasCRPago = trim($pago['aliaspag'] ?? '');
$folioCRPago = trim($pago['foliopag'] ?? '');
$subtotalLocalPago = floatval($pago['subtotal'] ?? 0);
$totalLocalPago = floatval($pago['total'] ?? 0);
$subtotalInvoicePago = isset($pago['subtotal_invoice']) && $pago['subtotal_invoice'] !== null ? floatval($pago['subtotal_invoice']) : null;
$totalInvoicePago = isset($pago['total_invoice']) && $pago['total_invoice'] !== null ? floatval($pago['total_invoice']) : null;
$diffSubtotalPago = ($subtotalInvoicePago !== null) ? ($subtotalInvoicePago - $subtotalLocalPago) : null;
$diffTotalPago = ($totalInvoicePago !== null) ? ($totalInvoicePago - $totalLocalPago) : null;

$folioCompuesto = 'P-' . $pago['cod_zona'] . '-' . date('ym', strtotime($pago['fecha_pago'])) . str_pad((string)$pago['folio'], 4, '0', STR_PAD_LEFT);

$sqlDet = "SELECT pd.*, cd.numero_bascula, p.cod as cod_producto, p.nom_pro as nombre_producto
           FROM pagos_detalle pd
           LEFT JOIN captacion_detalle cd ON pd.id_detalle = cd.id_detalle
           LEFT JOIN productos p ON pd.id_prod = p.id_prod
           WHERE pd.id_pago = ? AND pd.status = 1
           ORDER BY pd.id_pago_detalle ASC";
$stmtDet = $conn_mysql->prepare($sqlDet);
$stmtDet->bind_param('i', $id_pago);
$stmtDet->execute();
$detalles = $stmtDet->get_result();

$totalTickets = 0;
$totalImporte = 0;
$rows = [];
while ($d = $detalles->fetch_assoc()) {
    $rows[] = $d;
    $totalTickets++;
    $totalImporte += floatval($d['importe'] ?? 0);
}
?>

<style>
.pago-view .info-label {
    font-size: .78rem;
    letter-spacing: .02em;
}
.pago-view .metric-card {
    border: 1px solid var(--bs-border-color);
    border-radius: .75rem;
    padding: .85rem 1rem;
    background: var(--bs-body-bg);
    height: 100%;
}
.pago-view .metric-value {
    font-weight: 700;
    font-size: 1.05rem;
}
.pago-view .table thead th {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .02em;
    white-space: nowrap;
}
.pago-view .section-title {
    font-size: .9rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    margin-bottom: .65rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.pago-view .metric-card.compact {
    padding: .75rem .85rem;
}
.pago-view .metric-value.big {
    font-size: 1.2rem;
}
</style>

<div class="container py-3 pago-view">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff fs-5"></i>
                <div>
                    <h5 class="mb-0">Detalle de Pago</h5>
                    <small class="opacity-75">Consulta de tickets y montos aplicados</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalFacturaPago">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <?= !empty($pago['factura_pago']) ? 'Editar Factura' : 'Agregar Factura' ?>
                </button>
                <button class="btn btn-danger btn-sm" onclick="window.close();">
                    <i class="bi bi-x-lg me-1"></i>Cerrar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="section-title"><i class="bi bi-info-circle"></i>Información general</div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <div class="metric-card">
                        <div class="info-label text-muted">Folio</div>
                        <div class="metric-value text-primary"><?= htmlspecialchars($folioCompuesto) ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="metric-card">
                        <div class="info-label text-muted">Fecha Pago</div>
                        <div class="metric-value"><?= htmlspecialchars($pago['fecha_pago_fmt']) ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="metric-card">
                        <div class="info-label text-muted">Proveedor</div>
                        <div class="metric-value fs-6">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-1"><?= htmlspecialchars($pago['cod_proveedor'] ?? 'N/A') ?></span>
                            <?= htmlspecialchars($pago['nombre_proveedor'] ?? 'Sin proveedor') ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="metric-card">
                        <div class="info-label text-muted">Factura</div>
                        <div class="metric-value fs-6">
                            <?php if (!empty($pago['factura_pago'])): ?>
                                <?php if (!empty($docFacturaPago) || !empty($comFacturaPago)): ?>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-success btn-sm rounded-4 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-file-earmark-pdf"></i> <?= htmlspecialchars($pago['factura_pago']) ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (!empty($docFacturaPago)): ?>
                                                <li><a class="dropdown-item" href="<?= $invoiceLK . $docFacturaPago ?>.pdf" target="_blank">Ver Factura de Pago</a></li>
                                            <?php endif; ?>
                                            <?php if (!empty($comFacturaPago)): ?>
                                                <li><a class="dropdown-item" href="<?= $invoiceLK . $comFacturaPago ?>.pdf" target="_blank">Ver Comprobante de Pago</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($pago['factura_pago']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Pendiente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-chat-left-text"></i>Concepto</div>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="metric-card">
                        <div class="info-label text-muted mb-1">Concepto</div>
                        <div class="fw-semibold"><?= htmlspecialchars($pago['concepto'] ?? '') ?></div>
                        <?php if (!empty($pago['observaciones'])): ?>
                        <hr class="my-2">
                        <div class="info-label text-muted mb-1">Observaciones</div>
                        <div><?= nl2br(htmlspecialchars($pago['observaciones'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-calculator"></i>Resumen local del pago</div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="metric-card compact bg-body-tertiary">
                        <div class="info-label text-muted">Tickets</div>
                        <div class="metric-value"><?= $totalTickets ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card compact bg-body-tertiary">
                        <div class="info-label text-muted">Subtotal</div>
                        <div class="metric-value text-success">$<?= number_format($subtotalLocalPago, 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card compact bg-body-tertiary">
                        <div class="info-label text-muted">Importe acumulado tickets</div>
                        <div class="metric-value">$<?= number_format((float)$totalImporte, 2) ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="metric-card compact border-primary bg-primary bg-opacity-10">
                        <div class="info-label text-primary-emphasis">Total local</div>
                        <div class="metric-value big text-primary">$<?= number_format($totalLocalPago, 2) ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($docFacturaPago) || !empty($comFacturaPago) || $subtotalInvoicePago !== null || $totalInvoicePago !== null): ?>
            <div class="section-title"><i class="bi bi-receipt"></i>Comparativo con Invoice</div>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Desglose Fiscal Factura de Pago</h6>
                            <?php if (!empty($pago['factura_actualizada_fmt'])): ?>
                                <small class="text-muted">Última actualización: <?= htmlspecialchars($pago['factura_actualizada_fmt']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact bg-body-tertiary">
                                        <div class="info-label text-muted">Subtotal local</div>
                                        <div class="metric-value text-secondary">$<?= number_format($subtotalLocalPago, 2) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact bg-body-tertiary">
                                        <div class="info-label text-muted">Subtotal Invoice</div>
                                        <div class="metric-value text-info">
                                            <?= $subtotalInvoicePago !== null ? '$' . number_format($subtotalInvoicePago, 2) : '<span class="text-muted">Sin dato</span>' ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact bg-body-tertiary">
                                        <div class="info-label text-muted">IVA traslado local</div>
                                        <div class="metric-value text-success">$<?= number_format((float)$pago['impuesto_traslado'], 2) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact bg-body-tertiary">
                                        <div class="info-label text-muted">IVA retenido local</div>
                                        <div class="metric-value text-danger">$<?= number_format((float)$pago['impuesto_retenido'], 2) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact border-primary bg-primary bg-opacity-10">
                                        <div class="info-label text-primary-emphasis">Total Neto Local</div>
                                        <div class="metric-value text-primary">$<?= number_format($totalLocalPago, 2) ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="metric-card compact border-info bg-info bg-opacity-10">
                                        <div class="info-label text-info-emphasis">Total Invoice</div>
                                        <div class="metric-value text-info">
                                            <?= $totalInvoicePago !== null ? '$' . number_format($totalInvoicePago, 2) : '<span class="text-muted">Sin dato</span>' ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="metric-card compact border-warning bg-warning bg-opacity-10">
                                        <div class="info-label text-warning-emphasis">Diferencias (Invoice - Local)</div>
                                        <div class="small">
                                            <div>
                                                Subtotal: 
                                                <?php if ($diffSubtotalPago === null): ?>
                                                    <span class="text-muted">Sin dato</span>
                                                <?php else: ?>
                                                    <strong class="<?= abs($diffSubtotalPago) > 0.009 ? 'text-danger' : 'text-success' ?>">
                                                        <?= ($diffSubtotalPago >= 0 ? '+' : '') . '$' . number_format($diffSubtotalPago, 2) ?>
                                                    </strong>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                Total: 
                                                <?php if ($diffTotalPago === null): ?>
                                                    <span class="text-muted">Sin dato</span>
                                                <?php else: ?>
                                                    <strong class="<?= abs($diffTotalPago) > 0.009 ? 'text-danger' : 'text-success' ?>">
                                                        <?= ($diffTotalPago >= 0 ? '+' : '') . '$' . number_format($diffTotalPago, 2) ?>
                                                    </strong>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($folioCRPago)): ?>
                                <div class="col-12 col-md-6">
                                    <div class="metric-card compact border-info bg-info bg-opacity-10">
                                        <div class="info-label text-info-emphasis">Folio C.R. Pago</div>
                                        <?php if (!empty($aliasCRPago)): ?>
                                            <a href="<?= $link . urlencode($aliasCRPago) . '-' . $folioCRPago ?>" target="_blank" class="btn btn-sm btn-info rounded-5">
                                                <i class="bi bi-file-earmark-text me-1"></i>
                                                <?= htmlspecialchars($aliasCRPago) ?> - <?= htmlspecialchars($folioCRPago) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($folioCRPago) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Detalle de Tickets Pagados</h6>
                    <small class="text-muted">Importe acumulado: $<?= number_format((float)$totalImporte, 2) ?></small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Producto</th>
                                    <th>Ticket</th>
                                    <th class="text-end">Kilos</th>
                                    <th class="text-end">Precio/kg</th>
                                    <th class="text-end pe-3">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin detalles registrados para este pago</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($rows as $idx => $item): ?>
                                <tr>
                                    <td class="ps-3"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary me-1"><?= htmlspecialchars($item['cod_producto'] ?? 'N/A') ?></span>
                                        <?= htmlspecialchars($item['nombre_producto'] ?? 'Producto') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($item['numero_ticket'] ?? '-') ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format((float)$item['total_kilos'], 2) ?></td>
                                    <td class="text-end">$<?= number_format((float)$item['precio_unitario'], 4) ?></td>
                                    <td class="text-end pe-3 fw-bold">$<?= number_format((float)$item['importe'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFacturaPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Factura de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error_factura_pago)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_factura_pago) ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Número de factura</label>
                        <input type="text" name="factura_pago" id="factura_pago" class="form-control <?= isset($error_factura_pago) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($pago['factura_pago'] ?? '') ?>" maxlength="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="actualizar_factura_pago" class="btn btn-primary">Guardar Factura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    <?php if (isset($error_factura_pago)): ?>
    const modal = new bootstrap.Modal(document.getElementById('modalFacturaPago'));
    modal.show();
    <?php endif; ?>
});
</script>
