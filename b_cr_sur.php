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

function tieneColumnaCR(mysqli $conn, string $tabla, string $columna): bool {
    $tabla = $conn->real_escape_string($tabla);
    $columna = $conn->real_escape_string($columna);
    $res = $conn->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
    return $res && $res->num_rows > 0;
}

function obtenerFacturaIdInvoice(mysqli $inv, string $folio, string $codigoProveedor = '', bool $estrictoCodigo = false): int {
    $folio = trim($folio);
    if ($folio === '') {
        return 0;
    }

    $folioEsc = $inv->real_escape_string($folio);

    if ($codigoProveedor !== '') {
        $codigoEsc = $inv->real_escape_string($codigoProveedor);
        $sql = "SELECT id FROM facturas WHERE folio = '{$folioEsc}' AND codigoProveedor = '{$codigoEsc}' ORDER BY id DESC LIMIT 1";
        $res = $inv->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return intval($row['id'] ?? 0);
        }

        if ($estrictoCodigo) {
            return 0;
        }
    }

    $sql = "SELECT id FROM facturas WHERE folio = '{$folioEsc}' ORDER BY id DESC LIMIT 1";
    $res = $inv->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return intval($row['id'] ?? 0);
    }

    return 0;
}

function obtenerCRPorFactura(mysqli $inv, int $idFactura): ?array {
    if ($idFactura <= 0) {
        return null;
    }

    $sql = "SELECT cr.aliasGrupo AS alias, cr.folio AS folio
            FROM contrafacturas cf
            INNER JOIN contrarrecibos cr ON cr.id = cf.idContrarrecibo
            WHERE cf.idFactura = {$idFactura}
            ORDER BY cr.id DESC
            LIMIT 1";
    $res = $inv->query($sql);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    return null;
}

$pagosTieneAlias = tieneColumnaCR($conn_mysql, 'pagos', 'aliaspag');
$pagosTieneFolio = tieneColumnaCR($conn_mysql, 'pagos', 'foliopag');

$stats = [
    'transportista' => ['total' => 0, 'actualizados' => 0, 'sin_cr' => 0],
    'servicio' => ['total' => 0, 'actualizados' => 0, 'sin_cr' => 0],
    'pagos' => ['total' => 0, 'actualizados' => 0, 'sin_cr' => 0],
];

ob_start();
?>
<div class="container my-3">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Zona SUR: se buscan C.R. para <strong>transportista</strong>, <strong>servicio</strong> y <strong>pagos</strong>.
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white"><strong>Transportista</strong></div>
                <div class="card-body" style="max-height:55vh;overflow:auto;">
<?php
$sqlTrans = "SELECT vf.id_venta, vf.factura_transportista, vf.aliasven, vf.folioven, t.placas AS codigo_transportista
             FROM venta_flete vf
             INNER JOIN ventas v ON v.id_venta = vf.id_venta
             LEFT JOIN transportes t ON t.id_transp = vf.id_fletero
             WHERE v.status = 1 AND v.zona = {$zona}
               AND vf.factura_transportista IS NOT NULL AND vf.factura_transportista <> ''
               AND (vf.folioven IS NULL OR vf.folioven = '')
             ORDER BY vf.id_venta DESC";
$resTrans = $conn_mysql->query($sqlTrans);

if (!$resTrans || $resTrans->num_rows === 0) {
    echo '<div class="alert alert-secondary mb-0">Sin registros pendientes.</div>';
} else {
    while ($row = $resTrans->fetch_assoc()) {
        $stats['transportista']['total']++;
        $folioFactura = trim($row['factura_transportista'] ?? '');
        $codigo = trim($row['codigo_transportista'] ?? '');

        $idFactura = obtenerFacturaIdInvoice($inv_mysql, $folioFactura, $codigo);
        $cr = obtenerCRPorFactura($inv_mysql, $idFactura);

        $estado = '<span class="badge bg-danger">C.R no encontrado</span>';
        if ($cr) {
            $alias = $conn_mysql->real_escape_string($cr['alias'] ?? '');
            $folioCr = $conn_mysql->real_escape_string($cr['folio'] ?? '');
            $upd = "UPDATE venta_flete SET aliasven='{$alias}', folioven='{$folioCr}', fecha_actualizacion=NOW() WHERE id_venta=" . intval($row['id_venta']);
            if ($conn_mysql->query($upd)) {
                $stats['transportista']['actualizados']++;
                $estado = '<span class="badge bg-success">C.R asignado: '.htmlspecialchars($cr['alias']).'-'.htmlspecialchars($cr['folio']).'</span>';
                //$inv_mysql->query("UPDATE contrarrecibos SET crExterno='1' WHERE aliasGrupo='" . $inv_mysql->real_escape_string($cr['alias']) . "' AND folio='" . $inv_mysql->real_escape_string($cr['folio']) . "'");
            }
        } else {
            $stats['transportista']['sin_cr']++;
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<div><strong>Venta #'.intval($row['id_venta']).'</strong></div>';
        echo '<div class="small text-muted">Factura: '.htmlspecialchars($folioFactura).' | Fletero: '.htmlspecialchars($codigo).'</div>';
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
$sqlServicio = "SELECT vs.id_venta_servicio, vs.id_venta, vs.factura_servicio, vs.aliasser, vs.folioser,
                                             a.cod AS codigo_almacen
                FROM venta_servicio vs
                INNER JOIN ventas v ON v.id_venta = vs.id_venta
                                INNER JOIN almacenes a ON a.id_alma = v.id_alma
                WHERE v.status = 1 AND v.zona = {$zona}
                  AND vs.factura_servicio IS NOT NULL AND vs.factura_servicio <> ''
                  AND (vs.folioser IS NULL OR vs.folioser = '')
                ORDER BY vs.id_venta DESC";
$resServ = $conn_mysql->query($sqlServicio);

if (!$resServ || $resServ->num_rows === 0) {
    echo '<div class="alert alert-secondary mb-0">Sin registros pendientes.</div>';
} else {
    while ($row = $resServ->fetch_assoc()) {
        $stats['servicio']['total']++;
        $folioFactura = trim($row['factura_servicio'] ?? '');
        $codigoAlmacen = trim($row['codigo_almacen'] ?? '');

        $idFactura = obtenerFacturaIdInvoice($inv_mysql, $folioFactura, $codigoAlmacen, true);
        $cr = obtenerCRPorFactura($inv_mysql, $idFactura);

        $estado = '<span class="badge bg-danger">C.R no encontrado</span>';
        if ($cr) {
            $alias = $conn_mysql->real_escape_string($cr['alias'] ?? '');
            $folioCr = $conn_mysql->real_escape_string($cr['folio'] ?? '');
            $upd = "UPDATE venta_servicio SET aliasser='{$alias}', folioser='{$folioCr}', fecha_actualizacion=NOW() WHERE id_venta_servicio=" . intval($row['id_venta_servicio']);
            if ($conn_mysql->query($upd)) {
                $stats['servicio']['actualizados']++;
                $estado = '<span class="badge bg-success">C.R asignado: '.htmlspecialchars($cr['alias']).'-'.htmlspecialchars($cr['folio']).'</span>';
                //$inv_mysql->query("UPDATE contrarrecibos SET crExterno='1' WHERE aliasGrupo='" . $inv_mysql->real_escape_string($cr['alias']) . "' AND folio='" . $inv_mysql->real_escape_string($cr['folio']) . "'");
            }
        } else {
            $stats['servicio']['sin_cr']++;
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<div><strong>Venta #'.intval($row['id_venta']).'</strong></div>';
        echo '<div class="small text-muted">Factura servicio: '.htmlspecialchars($folioFactura).' | Almacén: '.htmlspecialchars($codigoAlmacen).'</div>';
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
if (!$pagosTieneAlias || !$pagosTieneFolio) {
    echo '<div class="alert alert-warning mb-0">Faltan columnas alias/folio para C.R en <strong>pagos</strong> (aliaspag, foliopag).</div>';
} else {
    $sqlPagos = "SELECT p.id_pago, p.folio, p.factura_pago, p.aliaspag, p.foliopag, prov.cod AS codigo_proveedor
                 FROM pagos p
                 LEFT JOIN proveedores prov ON prov.id_prov = p.id_prov
                 WHERE p.status = 1 AND p.zona = {$zona}
                   AND p.factura_pago IS NOT NULL AND p.factura_pago <> ''
                   AND (p.foliopag IS NULL OR p.foliopag = '')
                 ORDER BY p.id_pago DESC";
    $resPagos = $conn_mysql->query($sqlPagos);

    if (!$resPagos || $resPagos->num_rows === 0) {
        echo '<div class="alert alert-secondary mb-0">Sin registros pendientes.</div>';
    } else {
        while ($row = $resPagos->fetch_assoc()) {
            $stats['pagos']['total']++;
            $folioFactura = trim($row['factura_pago'] ?? '');
            $codigoProv = trim($row['codigo_proveedor'] ?? '');

            $idFactura = obtenerFacturaIdInvoice($inv_mysql, $folioFactura, $codigoProv, true);
            $cr = obtenerCRPorFactura($inv_mysql, $idFactura);

            $estado = '<span class="badge bg-danger">C.R no encontrado</span>';
            if ($cr) {
                $alias = $conn_mysql->real_escape_string($cr['alias'] ?? '');
                $folioCr = $conn_mysql->real_escape_string($cr['folio'] ?? '');
                $upd = "UPDATE pagos SET aliaspag='{$alias}', foliopag='{$folioCr}', updated_at=NOW() WHERE id_pago=" . intval($row['id_pago']);
                if ($conn_mysql->query($upd)) {
                    $stats['pagos']['actualizados']++;
                    $estado = '<span class="badge bg-success">C.R asignado: '.htmlspecialchars($cr['alias']).'-'.htmlspecialchars($cr['folio']).'</span>';
                    //$inv_mysql->query("UPDATE contrarrecibos SET crExterno='1' WHERE aliasGrupo='" . $inv_mysql->real_escape_string($cr['alias']) . "' AND folio='" . $inv_mysql->real_escape_string($cr['folio']) . "'");
                }
            } else {
                $stats['pagos']['sin_cr']++;
            }

            echo '<div class="border rounded p-2 mb-2">';
            echo '<div><strong>Pago #'.intval($row['folio']).'</strong></div>';
            echo '<div class="small text-muted">Factura pago: '.htmlspecialchars($folioFactura).' | Proveedor: '.htmlspecialchars($codigoProv).'</div>';
            echo '<div class="mt-1">'.$estado.'</div>';
            echo '</div>';
        }
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
                    <small class="text-muted">Total: <?= $stats['transportista']['total']; ?> | Actualizados: <?= $stats['transportista']['actualizados']; ?> | Sin C.R: <?= $stats['transportista']['sin_cr']; ?></small>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="fw-semibold">Servicio</div>
                    <small class="text-muted">Total: <?= $stats['servicio']['total']; ?> | Actualizados: <?= $stats['servicio']['actualizados']; ?> | Sin C.R: <?= $stats['servicio']['sin_cr']; ?></small>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="fw-semibold">Pagos</div>
                    <small class="text-muted">Total: <?= $stats['pagos']['total']; ?> | Actualizados: <?= $stats['pagos']['actualizados']; ?> | Sin C.R: <?= $stats['pagos']['sin_cr']; ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
echo ob_get_clean();
