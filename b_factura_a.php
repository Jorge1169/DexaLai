<?php
// Conexión a la base de datos
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';
$zona = $_POST['zona'] ?? '';

// Encabezado HTML + Bootstrap
?>
<div class="container my-4">
    <h1 class="h3 mb-3">Revisión de facturas - Zona <?php echo htmlspecialchars($zona); ?></h1>

    <div class="row">
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Ventas (faltantes)</strong>
                </div>
                <div class="card-body" style="max-height:60vh; overflow:auto;">
<?php
// ventas
$query_ventas = "SELECT v.*,
                                        vf.factura_transportista AS factura_flete_venta,
                                        tv.placas AS codigo_transporte_venta
                                        FROM ventas v
                                        LEFT JOIN venta_flete vf on v.id_venta = vf.id_venta
                                        LEFT JOIN transportes tv on vf.id_fletero = tv.id_transp 
                                        WHERE v.zona = ? and (vf.doc_factura_ven IS NULL OR vf.com_factura_ven IS NULL)
                            ";
$stmt_ven = $conn_mysql->prepare($query_ventas);
$stmt_ven->bind_param('s', $zona);
$stmt_ven->execute();
$result_ven = $stmt_ven->get_result();

if ($result_ven->num_rows === 0) {
        echo '<div class="alert alert-secondary mb-0">No se encontraron ventas con facturas faltantes en esta zona.</div>';
} else {
        while ($row_ven = $result_ven->fetch_assoc()) {
                $idVenta = htmlspecialchars($row_ven['id_venta']);
                $facturaFlete = htmlspecialchars($row_ven['factura_flete_venta'] ?? '');
                $codigoTrans = htmlspecialchars($row_ven['codigo_transporte_venta'] ?? '');

                // default statuses
                $statusDoc = '<span class="badge bg-warning">No encontrada</span>';
                $statusEA = '<span class="badge bg-warning">No encontrada</span>';

                if (!empty($row_ven['factura_flete_venta'])) {
                        $Buscar_factura_flete_ven = $inv_mysql->query("select * from facturas where codigoProveedor = '" . $row_ven['codigo_transporte_venta'] . "' and folio = '" . $row_ven['factura_flete_venta'] . "'");
                        if ($Buscar_factura_flete_ven && $Buscar_factura_flete_ven->num_rows > 0) {
                                $Info_factura_flete_ven = mysqli_fetch_assoc($Buscar_factura_flete_ven);
                                if ($Info_factura_flete_ven['status'] != 'Rechazado') {
                                        $doc_factura_flete_ven = $Info_factura_flete_ven['ubicacion'].$Info_factura_flete_ven['nombreInternoPDF'];
                                        $statusDoc = '<span class="badge bg-success">Encontrada</span>';
                                        // actualizar doc en BD
                                        $conn_mysql->query("UPDATE venta_flete SET doc_factura_ven = '" . $conn_mysql->real_escape_string($doc_factura_flete_ven) . "' WHERE id_venta = " . intval($row_ven['id_venta']));
                                        if ($Info_factura_flete_ven['ea'] == 1) {
                                                $fecha_timestamp2 = strtotime($Info_factura_flete_ven['fechaFactura']);
                                                $fecha_form2 = date("ymd", $fecha_timestamp2);
                                                $ubicacionEA_flete_ven = $Info_factura_flete_ven['ubicacion'].'EA_'.str_replace("-", "", $Info_factura_flete_ven['codigoProveedor'].'_'.$Info_factura_flete_ven['folio'].'_'.$fecha_form2);
                                                $statusEA = '<span class="badge bg-success">Evidencia encontrada</span>';
                                                $conn_mysql->query("UPDATE venta_flete SET com_factura_ven = '" . $conn_mysql->real_escape_string($ubicacionEA_flete_ven) . "' WHERE id_venta = " . intval($row_ven['id_venta']));
                                        } else {
                                                $statusEA = '<span class="badge bg-danger">Sin evidencia</span>';
                                        }
                                } else {
                                        $statusDoc = '<span class="badge bg-danger">Rechazada</span>';
                                }
                        } else {
                                $statusDoc = '<span class="badge bg-danger">No existe en Invoice</span>';
                        }
                } else {
                        $statusDoc = '<span class="badge bg-secondary">Sin número</span>';
                        $statusEA = '<span class="badge bg-secondary">Sin número</span>';
                }

                echo '<div class="mb-3 p-2 border rounded">';
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<div><strong>ID Venta:</strong> ' . $idVenta . '<br><small>Flete: ' . $facturaFlete . ' — Fletero: ' . $codigoTrans . '</small></div>';
                echo '<div class="text-end">' . $statusDoc . '<br>' . $statusEA . '</div>';
                echo '</div>';
                echo '</div>';
        }
}
?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Captaciones (faltantes)</strong>
                </div>
                <div class="card-body" style="max-height:60vh; overflow:auto;">
<?php
// captaciones
$query_captaciones = "SELECT ca.*,
                                        cd.id_detalle,
                                        cd.numero_factura as factura_captacuion,
                                        cf.id_capt_flete,
                                        cf.numero_factura_flete as factura_flete,
                                        t.placas as codigo_transporte,
                                        p.cod as cod_prov
                                        FROM captacion ca
                                        LEFT JOIN captacion_detalle cd on ca.id_captacion = cd.id_captacion
                                        LEFT JOIN captacion_flete cf on ca.id_captacion = cf.id_capt_flete
                                        LEFT JOIN proveedores p on ca.id_prov = p.id_prov
                                        LEFT JOIN transportes t on cf.id_fletero  = t.id_transp
                                        WHERE ca.zona = ? AND ((cd.doc_factura IS NULL OR cd.com_factura IS NULL) OR (cf.doc_factura_flete IS NULL OR cf.com_factura_flete IS NULL))
                                        ";
$stmt = $conn_mysql->prepare($query_captaciones);
$stmt->bind_param('s', $zona);
$stmt->execute();
$result_cap = $stmt->get_result();

if ($result_cap->num_rows === 0) {
        echo '<div class="alert alert-secondary mb-0">No se encontraron captaciones con facturas faltantes en esta zona.</div>';
} else {
        while ($row = $result_cap->fetch_assoc()) {
                $idCapt = htmlspecialchars($row['id_captacion']);
                $idDetalle = htmlspecialchars($row['id_detalle'] ?? '');
                $provCod = htmlspecialchars($row['cod_prov'] ?? '');
                $facturaCapt = htmlspecialchars($row['factura_captacuion'] ?? '');
                $facturaFlete = htmlspecialchars($row['factura_flete'] ?? '');
                $codigoTrans = htmlspecialchars($row['codigo_transporte'] ?? '');

                // estados por defecto
                $statusDocCap = '<span class="badge bg-warning">No encontrada</span>';
                $statusEACap = '<span class="badge bg-warning">No encontrada</span>';
                $statusDocFlete = '<span class="badge bg-warning">No encontrada</span>';
                $statusEAFlete = '<span class="badge bg-warning">No encontrada</span>';

                // factura de captación
                if (!empty($row['factura_captacuion'])) {
                        $Buscar_factura_cap = $inv_mysql->query("select * from facturas where codigoProveedor = '" . $row['cod_prov'] . "' and folio = '" . $row['factura_captacuion'] . "'");
                        if ($Buscar_factura_cap && $Buscar_factura_cap->num_rows > 0) {
                                $Info_factura_cap = mysqli_fetch_assoc($Buscar_factura_cap);
                                if ($Info_factura_cap['status'] != 'Rechazado') {
                                        $doc_factura_cap = $Info_factura_cap['ubicacion'].$Info_factura_cap['nombreInternoPDF'];
                                        $statusDocCap = '<span class="badge bg-success">Encontrada</span>';
                                        $conn_mysql->query("UPDATE captacion_detalle SET doc_factura = '" . $conn_mysql->real_escape_string($doc_factura_cap) . "' WHERE id_detalle = " . intval($row['id_detalle']));
                                        if ($Info_factura_cap['ea'] == 1) {
                                                $fecha_timestamp1 = strtotime($Info_factura_cap['fechaFactura']);
                                                $fecha_form = date("ymd", $fecha_timestamp1);
                                                $ubicacionEA = $Info_factura_cap['ubicacion'].'EA_'.str_replace("-", "", $Info_factura_cap['codigoProveedor'].'_'.$Info_factura_cap['folio'].'_'.$fecha_form);
                                                $statusEACap = '<span class="badge bg-success">Evidencia encontrada</span>';
                                                $conn_mysql->query("UPDATE captacion_detalle SET com_factura = '" . $conn_mysql->real_escape_string($ubicacionEA) . "' WHERE id_detalle = " . intval($row['id_detalle']));
                                        } else {
                                                $statusEACap = '<span class="badge bg-danger">Sin evidencia</span>';
                                        }
                                } else {
                                        $statusDocCap = '<span class="badge bg-danger">Rechazada</span>';
                                }
                        } else {
                                $statusDocCap = '<span class="badge bg-danger">No existe en Invoice</span>';
                        }
                } else {
                        $statusDocCap = '<span class="badge bg-secondary">Sin número</span>';
                        $statusEACap = '<span class="badge bg-secondary">Sin número</span>';
                }

                // factura flete de captación
                if (!empty($row['factura_flete'])) {
                        $Buscar_factura_flete = $inv_mysql->query("select * from facturas where codigoProveedor = '" . $row['codigo_transporte'] . "' and folio = '" . $row['factura_flete'] . "'");
                        if ($Buscar_factura_flete && $Buscar_factura_flete->num_rows > 0) {
                                $Info_factura_flete = mysqli_fetch_assoc($Buscar_factura_flete);
                                if ($Info_factura_flete['status'] != 'Rechazado') {
                                        $doc_factura_flete = $Info_factura_flete['ubicacion'].$Info_factura_flete['nombreInternoPDF'];
                                        $statusDocFlete = '<span class="badge bg-success">Encontrada</span>';
                                        $conn_mysql->query("UPDATE captacion_flete SET doc_factura_flete = '" . $conn_mysql->real_escape_string($doc_factura_flete) . "' WHERE id_capt_flete = " . intval($row['id_capt_flete']));
                                        if ($Info_factura_flete['ea'] == 1) {
                                                $fecha_timestamp2 = strtotime($Info_factura_flete['fechaFactura']);
                                                $fecha_form2 = date("ymd", $fecha_timestamp2);
                                                $ubicacionEA_flete = $Info_factura_flete['ubicacion'].'EA_'.str_replace("-", "", $Info_factura_flete['codigoProveedor'].'_'.$Info_factura_flete['folio'].'_'.$fecha_form2);
                                                $statusEAFlete = '<span class="badge bg-success">Evidencia encontrada</span>';
                                                $conn_mysql->query("UPDATE captacion_flete SET com_factura_flete = '" . $conn_mysql->real_escape_string($ubicacionEA_flete) . "' WHERE id_capt_flete = " . intval($row['id_capt_flete']));
                                        } else {
                                                $statusEAFlete = '<span class="badge bg-danger">Sin evidencia</span>';
                                        }
                                } else {
                                        $statusDocFlete = '<span class="badge bg-danger">Rechazada</span>';
                                }
                        } else {
                                $statusDocFlete = '<span class="badge bg-danger">No existe en Invoice</span>';
                        }
                } else {
                        $statusDocFlete = '<span class="badge bg-secondary">Sin número</span>';
                        $statusEAFlete = '<span class="badge bg-secondary">Sin número</span>';
                }

                echo '<div class="mb-3 p-2 border rounded">';
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<div><strong>ID Captación:</strong> ' . $idCapt . '<br><small>Detalle: ' . $idDetalle . ' — Proveedor: ' . $provCod . '</small></div>';
                echo '<div class="text-end">';
                echo '<div><small>Factura captación:</small><br>' . $statusDocCap . ' ' . $statusEACap . '</div>';
                echo '<div class="mt-2"><small>Factura flete:</small><br>' . $statusDocFlete . ' ' . $statusEAFlete . '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
        }
}
?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-muted small mt-3">Resultados procesados localmente. Los cambios de doc/evidencia se han guardado en la base de datos cuando se encontraron en Invoice.</div>
</div>