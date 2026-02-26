<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

$zona = intval($_POST['zona'] ?? 0);

if ($zona <= 0 || !esZonaSurSinFlete($zona, $conn_mysql)) {
    echo '<div class="alert alert-warning mb-0">Seleccione una zona SUR para ejecutar esta búsqueda.</div>';
    exit;
}

if ($var_exter == 0 || !$inv_mysql) {
    echo '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>No hay conexión con Invoice.</div>';
    exit;
}

function tieneColumna(mysqli $conn, string $tabla, string $columna): bool {
    $tabla = $conn->real_escape_string($tabla);
    $columna = $conn->real_escape_string($columna);
    $res = $conn->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
    return $res && $res->num_rows > 0;
}

function obtenerFacturaInvoice(mysqli $inv, string $folio, string $codigoProveedor = '', bool $estrictoCodigo = false): ?array {
    $folioEsc = $inv->real_escape_string($folio);
    if ($folioEsc === '') {
        return null;
    }

    if ($codigoProveedor !== '') {
        $codigoEsc = $inv->real_escape_string($codigoProveedor);
        $sql = "SELECT * FROM facturas WHERE folio = '{$folioEsc}' AND codigoProveedor = '{$codigoEsc}' ORDER BY id DESC LIMIT 1";
        $res = $inv->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }

        if ($estrictoCodigo) {
            return null;
        }
    }

    $sql = "SELECT * FROM facturas WHERE folio = '{$folioEsc}' ORDER BY id DESC LIMIT 1";
    $res = $inv->query($sql);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    return null;
}

function construirEA(array $factura): string {
    if (($factura['ea'] ?? '0') != '1') {
        return '';
    }
    $fecha = strtotime($factura['fechaFactura'] ?? '');
    if (!$fecha) {
        return '';
    }
    $fechaForm = date('ymd', $fecha);
    return ($factura['ubicacion'] ?? '') . 'EA_' . str_replace('-', '', ($factura['codigoProveedor'] ?? '') . '_' . ($factura['folio'] ?? '') . '_' . $fechaForm);
}

$pagosTieneDoc = tieneColumna($conn_mysql, 'pagos', 'doc_factura_pago');
$pagosTieneCom = tieneColumna($conn_mysql, 'pagos', 'com_factura_pago');
$pagosTieneSubtotalInvoice = tieneColumna($conn_mysql, 'pagos', 'subtotal_invoice');
$pagosTieneTotalInvoice = tieneColumna($conn_mysql, 'pagos', 'total_invoice');

$stats = [
    'transportista' => ['total' => 0, 'actualizados' => 0, 'sin_factura' => 0],
    'servicio' => ['total' => 0, 'actualizados' => 0, 'sin_factura' => 0],
    'pagos' => ['total' => 0, 'actualizados' => 0, 'sin_factura' => 0],
];

ob_start();
?>
<div class="container my-3">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Zona SUR: se validan facturas de <strong>transportista</strong>, <strong>servicio</strong> y <strong>pagos</strong>.
    </div>
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-primary text-white"><strong>Transportista</strong></div>
                <div class="card-body" style="max-height:55vh;overflow:auto;">
<?php
$sqlTrans = "SELECT vf.id_venta, vf.factura_transportista, vf.doc_factura_ven, vf.com_factura_ven,
                                        vf.impuestoTraslado_v, vf.impuestoRetenido_v, vf.subtotal_v, vf.total_v,
                                        t.placas AS codigo_transportista
                         FROM venta_flete vf
                         INNER JOIN ventas v ON v.id_venta = vf.id_venta
                         LEFT JOIN transportes t ON t.id_transp = vf.id_fletero
                WHERE v.status = 1 AND v.zona = {$zona}
                             AND vf.factura_transportista IS NOT NULL AND vf.factura_transportista <> ''
                         ORDER BY vf.id_venta DESC";
$resTrans = $conn_mysql->query($sqlTrans);

if (!$resTrans || $resTrans->num_rows === 0) {
    echo '<div class="alert alert-secondary mb-0">Sin registros para validar.</div>';
} else {
        while ($row = $resTrans->fetch_assoc()) {
                $stats['transportista']['total']++;
                $folio = trim($row['factura_transportista'] ?? '');
                $codigo = trim($row['codigo_transportista'] ?? '');
                $factura = obtenerFacturaInvoice($inv_mysql, $folio, $codigo, true);

        $estado = '<span class="badge bg-danger">No encontrada</span>';
        if ($factura) {
            if (($factura['status'] ?? '') === 'Rechazado') {
                $estado = '<span class="badge bg-warning text-dark">Rechazada</span>';
            } else {
                $doc = ($factura['ubicacion'] ?? '') . ($factura['nombreInternoPDF'] ?? '');
                $ea = construirEA($factura);
                $imTras = floatval($factura['impuestoTraslado'] ?? 0);
                $imRet = floatval($factura['impuestoRetenido'] ?? 0);
                $sub = floatval($factura['subtotal'] ?? 0);
                $tot = floatval($factura['total'] ?? 0);

                $upd = "UPDATE venta_flete SET
                            doc_factura_ven='" . $conn_mysql->real_escape_string($doc) . "',
                            com_factura_ven='" . $conn_mysql->real_escape_string($ea) . "',
                            impuestoTraslado_v='{$imTras}',
                            impuestoRetenido_v='{$imRet}',
                            subtotal_v='{$sub}',
                            total_v='{$tot}',
                            fecha_actualizacion=NOW()
                        WHERE id_venta=" . intval($row['id_venta']);
                if ($conn_mysql->query($upd)) {
                    $stats['transportista']['actualizados']++;
                    $estado = '<span class="badge bg-success">Actualizada</span>';
                }
            }
        } else {
            $stats['transportista']['sin_factura']++;
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<div><strong>Venta #'.intval($row['id_venta']).'</strong></div>';
        echo '<div class="small text-muted">Factura: '.htmlspecialchars($folio).' | Fletero: '.htmlspecialchars($codigo).'</div>';
        echo '<div class="mt-1">'.$estado.'</div>';
        echo '</div>';
    }
}
?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white"><strong>Servicio</strong></div>
                <div class="card-body" style="max-height:55vh;overflow:auto;">
<?php
$sqlServicio = "SELECT vs.id_venta_servicio, vs.id_venta, vs.factura_servicio,
                       vs.doc_factura_ser, vs.com_factura_ser,
                                             vs.impuestoTraslado_ser, vs.impuestoRetenido_ser, vs.subtotal_ser, vs.total_ser,
                                             a.cod AS codigo_almacen
                FROM venta_servicio vs
                INNER JOIN ventas v ON v.id_venta = vs.id_venta
                                INNER JOIN almacenes a ON a.id_alma = v.id_alma
                WHERE v.status = 1 AND v.zona = {$zona}
                  AND vs.factura_servicio IS NOT NULL AND vs.factura_servicio <> ''
                ORDER BY vs.id_venta DESC";
$resServ = $conn_mysql->query($sqlServicio);

if (!$resServ || $resServ->num_rows === 0) {
    echo '<div class="alert alert-secondary mb-0">Sin registros para validar.</div>';
} else {
    while ($row = $resServ->fetch_assoc()) {
        $stats['servicio']['total']++;
        $folio = trim($row['factura_servicio'] ?? '');
        $codigoAlmacen = trim($row['codigo_almacen'] ?? '');
        $factura = obtenerFacturaInvoice($inv_mysql, $folio, $codigoAlmacen, true);

        $estado = '<span class="badge bg-danger">No encontrada</span>';
        if ($factura) {
            if (($factura['status'] ?? '') === 'Rechazado') {
                $estado = '<span class="badge bg-warning text-dark">Rechazada</span>';
            } else {
                $doc = ($factura['ubicacion'] ?? '') . ($factura['nombreInternoPDF'] ?? '');
                $ea = construirEA($factura);
                $imTras = floatval($factura['impuestoTraslado'] ?? 0);
                $imRet = floatval($factura['impuestoRetenido'] ?? 0);
                $sub = floatval($factura['subtotal'] ?? 0);
                $tot = floatval($factura['total'] ?? 0);

                $upd = "UPDATE venta_servicio SET
                            doc_factura_ser='" . $conn_mysql->real_escape_string($doc) . "',
                            com_factura_ser='" . $conn_mysql->real_escape_string($ea) . "',
                            impuestoTraslado_ser='{$imTras}',
                            impuestoRetenido_ser='{$imRet}',
                            subtotal_ser='{$sub}',
                            total_ser='{$tot}',
                            fecha_actualizacion=NOW()
                        WHERE id_venta_servicio=" . intval($row['id_venta_servicio']);
                if ($conn_mysql->query($upd)) {
                    $stats['servicio']['actualizados']++;
                    $estado = '<span class="badge bg-success">Actualizada</span>';
                }
            }
        } else {
            $stats['servicio']['sin_factura']++;
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<div><strong>Venta #'.intval($row['id_venta']).'</strong></div>';
        echo '<div class="small text-muted">Factura servicio: '.htmlspecialchars($folio).' | Almacén: '.htmlspecialchars($codigoAlmacen).'</div>';
        echo '<div class="mt-1">'.$estado.'</div>';
        echo '</div>';
    }
}
?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-warning"><strong>Pagos</strong></div>
                <div class="card-body" style="max-height:55vh;overflow:auto;">
<?php
$sqlPagos = "SELECT p.id_pago, p.folio, p.factura_pago, p.subtotal, p.impuesto_traslado, p.impuesto_retenido, p.total,
                    prov.cod AS codigo_proveedor
             FROM pagos p
             LEFT JOIN proveedores prov ON prov.id_prov = p.id_prov
             WHERE p.status = 1 AND p.zona = {$zona}
               AND p.factura_pago IS NOT NULL AND p.factura_pago <> ''
             ORDER BY p.id_pago DESC";
$resPagos = $conn_mysql->query($sqlPagos);

if (!$resPagos || $resPagos->num_rows === 0) {
    echo '<div class="alert alert-secondary mb-0">Sin registros para validar.</div>';
} else {
    while ($row = $resPagos->fetch_assoc()) {
        $stats['pagos']['total']++;
        $folioFactura = trim($row['factura_pago'] ?? '');
        $codigoProv = trim($row['codigo_proveedor'] ?? '');
        $factura = obtenerFacturaInvoice($inv_mysql, $folioFactura, $codigoProv, true);

        $estado = '<span class="badge bg-danger">No encontrada</span>';
        if ($factura) {
            if (($factura['status'] ?? '') === 'Rechazado') {
                $estado = '<span class="badge bg-warning text-dark">Rechazada</span>';
            } else {
                $doc = ($factura['ubicacion'] ?? '') . ($factura['nombreInternoPDF'] ?? '');
                $ea = construirEA($factura);
                $imTras = floatval($factura['impuestoTraslado'] ?? 0);
                $imRet = floatval($factura['impuestoRetenido'] ?? 0);
                $sub = floatval($factura['subtotal'] ?? 0);
                $tot = floatval($factura['total'] ?? 0);

                $sets = [
                    "impuesto_traslado='{$imTras}'",
                    "impuesto_retenido='{$imRet}'",
                    "factura_actualizada=NOW()",
                    "updated_at=NOW()"
                ];

                if ($pagosTieneSubtotalInvoice) {
                    $sets[] = "subtotal_invoice='{$sub}'";
                }
                if ($pagosTieneTotalInvoice) {
                    $sets[] = "total_invoice='{$tot}'";
                }

                if ($pagosTieneDoc) {
                    $sets[] = "doc_factura_pago='" . $conn_mysql->real_escape_string($doc) . "'";
                }
                if ($pagosTieneCom) {
                    $sets[] = "com_factura_pago='" . $conn_mysql->real_escape_string($ea) . "'";
                }

                $upd = "UPDATE pagos SET " . implode(', ', $sets) . " WHERE id_pago=" . intval($row['id_pago']);
                if ($conn_mysql->query($upd)) {
                    $stats['pagos']['actualizados']++;
                    $estado = '<span class="badge bg-success">Actualizada</span>';
                }
            }
        } else {
            $stats['pagos']['sin_factura']++;
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<div><strong>Pago #'.intval($row['folio']).'</strong></div>';
        echo '<div class="small text-muted">Factura pago: '.htmlspecialchars($folioFactura).' | Proveedor: '.htmlspecialchars($codigoProv).'</div>';
        echo '<div class="mt-1">'.$estado.'</div>';
        echo '</div>';
    }
}
?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4 mb-2">
                    <div class="fw-semibold">Transportista</div>
                    <small class="text-muted">Total: <?= $stats['transportista']['total']; ?> | Actualizados: <?= $stats['transportista']['actualizados']; ?> | No encontrados: <?= $stats['transportista']['sin_factura']; ?></small>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="fw-semibold">Servicio</div>
                    <small class="text-muted">Total: <?= $stats['servicio']['total']; ?> | Actualizados: <?= $stats['servicio']['actualizados']; ?> | No encontrados: <?= $stats['servicio']['sin_factura']; ?></small>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="fw-semibold">Pagos</div>
                    <small class="text-muted">Total: <?= $stats['pagos']['total']; ?> | Actualizados: <?= $stats['pagos']['actualizados']; ?> | No encontrados: <?= $stats['pagos']['sin_factura']; ?></small>
                </div>
            </div>
            <?php if (!$pagosTieneDoc || !$pagosTieneCom || !$pagosTieneSubtotalInvoice || !$pagosTieneTotalInvoice): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    La tabla <strong>pagos</strong> aún no tiene todas las columnas esperadas (doc/com/subtotal_invoice/total_invoice), por lo que algunas actualizaciones de Invoice no se guardan completas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
echo ob_get_clean();
