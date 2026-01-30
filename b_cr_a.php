<?php
// conexion a la base de datos
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';
$zona = $_POST['zona'] ?? '';

// Incluimos Bootstrap CSS si no está ya incluido en tu proyecto
echo '
    <div class="container mt-4">';

// Variables para contar resultados
$ventas_encontradas = 0;
$ventas_actualizadas = 0;
$ventas_sin_cr = 0;
$captaciones_encontradas = 0;
$captaciones_actualizadas = 0;
$captaciones_sin_cr = 0;

// Busquemos las ventas sin C.R asignado
$query_ventas = "SELECT v.*,
                vf.factura_transportista AS factura_flete_venta,
                tv.placas AS codigo_transporte_venta
                FROM ventas v
                LEFT JOIN venta_flete vf on v.id_venta = vf.id_venta
                LEFT JOIN transportes tv on vf.id_fletero = tv.id_transp 
                WHERE v.status = 1 and v.zona = ? and folioven IS NULL";
$stmt_ven = $conn_mysql->prepare($query_ventas);
$stmt_ven->bind_param('s', $zona);
$stmt_ven->execute();
$result_ventas = $stmt_ven->get_result();

// Sección de Ventas
if ($result_ventas->num_rows > 0) {
    echo '<div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cart-check"></i> Ventas sin C.R.
                            <span class="badge bg-light text-primary ms-2">' . $result_ventas->num_rows . '</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th width="10%">ID Venta</th>
                                        <th width="20%">Factura Flete</th>
                                        <th width="20%">Transporte</th>
                                        <th width="25%">C.R. Encontrado</th>
                                        <th width="15%">Alias</th>
                                        <th width="10%">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>';
    
    while ($row = $result_ventas->fetch_assoc()) {
        $ventas_encontradas++;
        // ahora que conocemos las ventas sin C.R buscaremos en invoice si existen los C.R
        $BuscarCR_V = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra 
                    FROM facturas f 
                    INNER JOIN contrafacturas cf ON f.id=cf.idFactura 
                    INNER JOIN contrarrecibos cr ON cf.idContrarrecibo=cr.id 
                    AND f.codigoProveedor=cr.codigoProveedor 
                    AND f.rfcGrupo=cr.rfcGrupo 
                    INNER JOIN grupo g ON f.rfcGrupo=g.rfc 
                    WHERE f.folio='" . $row['factura_flete_venta'] . "' AND f.codigoProveedor = '" . $row['codigo_transporte_venta'] . "'");
        
        $estado = '';
        $folio_cr = '';
        $alias = '';
        
        if ($BuscarCR_V->num_rows > 0) {
            $cr_venta = $BuscarCR_V->fetch_assoc();
            // actualizar la venta con el folio del C.R
            $update_venta = $conn_mysql->prepare("UPDATE venta_flete SET folioven = ? , aliasven = ? WHERE id_venta = ?");
            $update_venta->bind_param("ssi", $cr_venta['FolioContra'], $cr_venta['alias'], $row['id_venta']);
            
            if ($update_venta->execute()) {
                $ventas_actualizadas++;
                $estado = '<span class="badge bg-success badge-status">Actualizado</span>';
                $folio_cr = $cr_venta['FolioContra'];
                $alias = $cr_venta['alias'];
            } else {
                $estado = '<span class="badge bg-danger badge-status">Error</span>';
            }
        } else {
            $ventas_sin_cr++;
            $estado = '<span class="badge bg-warning text-dark badge-status">No encontrado</span>';
            $folio_cr = '---';
            $alias = '---';
        }
        
        echo '<tr class="venta-row">
                <td><strong>' . htmlspecialchars($row['id_venta']) . '</strong></td>
                <td>' . htmlspecialchars($row['factura_flete_venta'] ?? 'No asignada') . '</td>
                <td>' . htmlspecialchars($row['codigo_transporte_venta'] ?? 'No asignado') . '</td>
                <td><span class="fw-bold">' . htmlspecialchars($folio_cr) . '</span></td>
                <td><span class="text-muted">' . htmlspecialchars($alias) . '</span></td>
                <td>' . $estado . '</td>
              </tr>';
    }
    
    echo '</tbody>
        </table>
    </div>
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i> 
        <strong>Resumen:</strong> 
        Encontradas: ' . $ventas_encontradas . ' | 
        Actualizadas: ' . $ventas_actualizadas . ' | 
        Sin C.R.: ' . $ventas_sin_cr . '
    </div>
</div>
</div>
</div>
</div>';
} else {
    echo '<div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cart-x"></i> Ventas sin C.R.
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            No se encontraron ventas sin C.R. en la zona seleccionada.
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}

// buscar captaciones de la zona seleccionada y que tengan facturas
$query_captaciones = "SELECT ca.*,
                        cd.id_detalle,
                        cd.numero_factura as factura_captacuion,
                        cf.numero_factura_flete as factura_flete,
                        t.placas as codigo_transporte,
                        p.cod as cod_prov
                        FROM captacion ca
                        LEFT JOIN captacion_detalle cd on ca.id_captacion = cd.id_captacion
                        LEFT JOIN captacion_flete cf on ca.id_captacion = cf.id_capt_flete
                        LEFT JOIN proveedores p on ca.id_prov = p.id_prov
                        LEFT JOIN transportes t on cf.id_fletero  = t.id_transp
                        WHERE ca.status = 1 and ca.zona = ? and (cd.foliocap IS NULL OR cf.foliocap_flete IS NULL)";
$stmt = $conn_mysql->prepare($query_captaciones);
$stmt->bind_param("s", $zona);
$stmt->execute();
$result_captaciones = $stmt->get_result();

// Sección de Captaciones
if ($result_captaciones->num_rows > 0) {
    echo '<div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-box-seam"></i> Captaciones sin C.R.
                            <span class="badge bg-light text-success ms-2">' . $result_captaciones->num_rows . '</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionCaptaciones">';
    
    $counter = 0;
    while ($row = $result_captaciones->fetch_assoc()) {
        $captaciones_encontradas++;
        $counter++;
        $accordionId = 'captacion' . $counter;
        
        echo '<div class="accordion-item">
                <h2 class="accordion-header" id="heading' . $accordionId . '">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapse' . $accordionId . '" aria-expanded="false" 
                            aria-controls="collapse' . $accordionId . '">
                        <div class="d-flex justify-content-between w-100 me-3">
                            <span>
                                <strong>Captación ID: ' . htmlspecialchars($row['id_captacion']) . '</strong>
                                <span class="text-muted ms-2">Proveedor: ' . htmlspecialchars($row['cod_prov']) . '</span>
                            </span>
                            <span class="badge bg-secondary">Detalle ID: ' . htmlspecialchars($row['id_detalle']) . '</span>
                        </div>
                    </button>
                </h2>
                <div id="collapse' . $accordionId . '" class="accordion-collapse collapse" 
                     aria-labelledby="heading' . $accordionId . '" data-bs-parent="#accordionCaptaciones">
                    <div class="accordion-body">
                        <div class="row">';
        
        // Buscar C.R. para factura de captación
        $cr_captacion_encontrado = false;
        $cr_flete_encontrado = false;
        
        $BuscarCR_C = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra 
                    FROM facturas f 
                    INNER JOIN contrafacturas cf ON f.id=cf.idFactura 
                    INNER JOIN contrarrecibos cr ON cf.idContrarrecibo=cr.id 
                    AND f.codigoProveedor=cr.codigoProveedor 
                    AND f.rfcGrupo=cr.rfcGrupo 
                    INNER JOIN grupo g ON f.rfcGrupo=g.rfc 
                    WHERE f.folio='" . $row['factura_captacuion'] . "' AND f.codigoProveedor = '" . $row['cod_prov'] . "'");
        
        if ($BuscarCR_C->num_rows > 0) {
            $cr_captacion = $BuscarCR_C->fetch_assoc();
            $update_captacion = $conn_mysql->prepare("UPDATE captacion_detalle SET foliocap = ? , aliascap = ? WHERE id_detalle = ?");
            $update_captacion->bind_param("ssi", $cr_captacion['FolioContra'], $cr_captacion['alias'], $row['id_detalle']);
            
            if ($update_captacion->execute()) {
                $captaciones_actualizadas++;
                $cr_captacion_encontrado = true;
                
                echo '<div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-file-earmark-text"></i> Factura Captación
                                    <span class="badge bg-light text-success ms-2">C.R. Encontrado</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Factura:</strong> ' . htmlspecialchars($row['factura_captacuion'] ?? 'No asignada') . '</p>
                                <p><strong>C.R. Asignado:</strong> <span class="text-success fw-bold">' . htmlspecialchars($cr_captacion['FolioContra']) . '</span></p>
                                <p><strong>Alias:</strong> ' . htmlspecialchars($cr_captacion['alias']) . '</p>
                            </div>
                        </div>
                    </div>';
            }
        } else {
            echo '<div class="col-md-6 mb-3">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark py-2">
                            <h6 class="mb-0">
                                <i class="bi bi-file-earmark-text"></i> Factura Captación
                                <span class="badge bg-light text-warning ms-2">Sin C.R.</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Factura:</strong> ' . htmlspecialchars($row['factura_captacuion'] ?? 'No asignada') . '</p>
                            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No se encontró C.R. para esta factura</p>
                        </div>
                    </div>
                </div>';
            $captaciones_sin_cr++;
        }
        
        // Buscar C.R. para factura de flete
        $BuscarCR_F = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra 
                    FROM facturas f 
                    INNER JOIN contrafacturas cf ON f.id=cf.idFactura 
                    INNER JOIN contrarrecibos cr ON cf.idContrarrecibo=cr.id 
                    AND f.codigoProveedor=cr.codigoProveedor 
                    AND f.rfcGrupo=cr.rfcGrupo 
                    INNER JOIN grupo g ON f.rfcGrupo=g.rfc 
                    WHERE f.folio='" . $row['factura_flete'] . "' AND f.codigoProveedor = '" . $row['codigo_transporte'] . "'");
        
        if ($BuscarCR_F->num_rows > 0) {
            $cr_flete = $BuscarCR_F->fetch_assoc();
            $update_captacion_flete = $conn_mysql->prepare("UPDATE captacion_flete SET foliocap_flete = ? , aliascap_flete = ? WHERE id_capt_flete = ?");
            $update_captacion_flete->bind_param("ssi", $cr_flete['FolioContra'], $cr_flete['alias'], $row['id_captacion']);
            
            if ($update_captacion_flete->execute()) {
                $captaciones_actualizadas++;
                $cr_flete_encontrado = true;
                
                echo '<div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-truck"></i> Factura Flete
                                    <span class="badge bg-light text-success ms-2">C.R. Encontrado</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Factura:</strong> ' . htmlspecialchars($row['factura_flete'] ?? 'No asignada') . '</p>
                                <p><strong>Transporte:</strong> ' . htmlspecialchars($row['codigo_transporte'] ?? 'No asignado') . '</p>
                                <p><strong>C.R. Asignado:</strong> <span class="text-success fw-bold">' . htmlspecialchars($cr_flete['FolioContra']) . '</span></p>
                                <p><strong>Alias:</strong> ' . htmlspecialchars($cr_flete['alias']) . '</p>
                            </div>
                        </div>
                    </div>';
            }
        } else {
            echo '<div class="col-md-6 mb-3">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark py-2">
                            <h6 class="mb-0">
                                <i class="bi bi-truck"></i> Factura Flete
                                <span class="badge bg-light text-warning ms-2">Sin C.R.</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Factura:</strong> ' . htmlspecialchars($row['factura_flete'] ?? 'No asignada') . '</p>
                            <p><strong>Transporte:</strong> ' . htmlspecialchars($row['codigo_transporte'] ?? 'No asignado') . '</p>
                            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No se encontró C.R. para esta factura</p>
                        </div>
                    </div>
                </div>';
            $captaciones_sin_cr++;
        }
        
        echo '</div>
            </div>
        </div>
    </div>';
    }
    
    echo '</div>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> 
            <strong>Resumen Captaciones:</strong> 
            Encontradas: ' . $captaciones_encontradas . ' | 
            Actualizaciones realizadas: ' . $captaciones_actualizadas . ' | 
            C.R. no encontrados: ' . $captaciones_sin_cr . '
        </div>
    </div>
</div>
</div>
</div>';
} else {
    echo '<div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-box-seam"></i> Captaciones sin C.R.
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            No se encontraron captaciones sin C.R. en la zona seleccionada.
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}
?>