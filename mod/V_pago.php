<?php
$id_pago = intval($_GET['id'] ?? 0);
if ($id_pago <= 0) {
    alert("ID de pago no válido", 0, "pagos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_factura_pago'])) {
    $factura_pago = trim($_POST['factura_pago'] ?? '');

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
        $stmtUpd = $conn_mysql->prepare("UPDATE pagos SET factura_pago = ?, factura_actualizada = NOW(), updated_at = NOW() WHERE id_pago = ?");
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
                                <span class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($pago['factura_pago']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Pendiente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

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

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="metric-card bg-body-tertiary">
                        <div class="info-label text-muted">Tickets</div>
                        <div class="metric-value"><?= $totalTickets ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card bg-body-tertiary">
                        <div class="info-label text-muted">Subtotal</div>
                        <div class="metric-value text-success">$<?= number_format((float)$pago['subtotal'], 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card bg-body-tertiary">
                        <div class="info-label text-muted">Impuestos (+)</div>
                        <div class="metric-value">$<?= number_format((float)$pago['impuesto_traslado'], 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="metric-card bg-body-tertiary">
                        <div class="info-label text-muted">Retenciones (-)</div>
                        <div class="metric-value">$<?= number_format((float)$pago['impuesto_retenido'], 2) ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="metric-card border-primary bg-primary bg-opacity-10">
                        <div class="info-label text-primary-emphasis">Total</div>
                        <div class="metric-value text-primary">$<?= number_format((float)$pago['total'], 2) ?></div>
                    </div>
                </div>
            </div>

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
