<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

if ($var_exter == 0) {
    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error de conexión. Contactar a SISTEMAS</div>';
    logActivity('INV', 'Error de conexión en buscar contras');
} else {
    if(isset($_POST['zonCR'])) { 
        $zon = $_POST['zonCR'];
        logActivity('INV', ' Se buscaron contra recibos en invoice ');

        // Iniciar contadores
        $totalRegistros = 0;
        $registrosCumplen = 0;
        $proveedoresActualizados = 0;
        $fleterosActualizados = 0;
        $registrosProcesados = 0;
        
        // CORREGIR: Añadir is null a las condiciones
        if ($zon == 0) {
            $QueryR = "SELECT r.*, pr.cod AS codigo_proveedor, tr.placas AS codigo_fletero
            FROM recoleccion r
            LEFT JOIN proveedores pr on r.id_prov = pr.id_prov
            LEFT JOIN transportes tr on r.id_transp = tr.id_transp
            WHERE r.status = '1' AND (r.folio_inv_fle is null OR r.folio_inv_pro is null)";
        } else {
            // CORREGIR: Vulnerabilidad SQL Injection - usar prepared statements
            $QueryR = "SELECT r.*, pr.cod AS codigo_proveedor, tr.placas AS codigo_fletero
            FROM recoleccion r
            LEFT JOIN proveedores pr on r.id_prov = pr.id_prov
            LEFT JOIN transportes tr on r.id_transp = tr.id_transp
            WHERE r.status = '1' AND (r.folio_inv_fle is null OR r.folio_inv_pro is null) AND r.zona = '$zon'";
        }

        $slv = $conn_mysql->query($QueryR);
        $totalRegistros = mysqli_num_rows($slv);

        while ($slRecole = mysqli_fetch_array($slv)) {
            $id_recol = $slRecole['id_recol'];
            $cumpleRequisitos = false;
            $registrosProcesados++;

            // Procesar facturas de proveedores
            if (!empty($slRecole['doc_pro']) && empty($slRecole['folio_inv_pro'])) {
                $FacturaPro = $slRecole['factura_pro'];
                $id_proveed = $slRecole['codigo_proveedor'];
                $cumpleRequisitos = true;

                $Bcp0 = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra 
                    FROM facturas f 
                    INNER JOIN contrafacturas cf ON f.id=cf.idFactura 
                    INNER JOIN contrarrecibos cr ON cf.idContrarrecibo=cr.id 
                    AND f.codigoProveedor=cr.codigoProveedor 
                    AND f.rfcGrupo=cr.rfcGrupo 
                    INNER JOIN grupo g ON f.rfcGrupo=g.rfc 
                    WHERE f.folio='$FacturaPro' AND f.codigoProveedor = '$id_proveed'");
                
                $Bcp1 = mysqli_fetch_array($Bcp0);

                if (!empty($Bcp1['alias'])) {
                    $aliasP = $Bcp1['alias'];
                    $folioP = $Bcp1['FolioContra'];
                    $conn_mysql->query("UPDATE recoleccion SET alias_inv_pro = '$aliasP', folio_inv_pro = '$folioP' WHERE id_recol = '$id_recol'");
                    $proveedoresActualizados++;
                    $inv_mysql->query("UPDATE contrarrecibos SET crExterno = '1' where folio = '$folioP' and aliasGrupo = '$aliasP'");
                }
            }

            // Procesar facturas de fleteros
            if (!empty($slRecole['doc_fle']) && empty($slRecole['folio_inv_fle'])) {
                $FacturaFle = $slRecole['factura_fle'];
                $id_fletero = $slRecole['codigo_fletero'];
                $cumpleRequisitos = true;

                $Bcf0 = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra 
                    FROM facturas f 
                    INNER JOIN contrafacturas cf ON f.id=cf.idFactura 
                    INNER JOIN contrarrecibos cr ON cf.idContrarrecibo=cr.id 
                    AND f.codigoProveedor=cr.codigoProveedor 
                    AND f.rfcGrupo=cr.rfcGrupo 
                    INNER JOIN grupo g ON f.rfcGrupo=g.rfc 
                    WHERE f.folio='$FacturaFle' AND f.codigoProveedor = '$id_fletero'");
                
                $Bcf1 = mysqli_fetch_array($Bcf0);

                if (!empty($Bcf1['alias'])) {
                    $aliasF = $Bcf1['alias'];
                    $folioF = $Bcf1['FolioContra'];
                    $conn_mysql->query("UPDATE recoleccion SET alias_inv_fle = '$aliasF', folio_inv_fle = '$folioF' WHERE id_recol = '$id_recol'");
                    $fleterosActualizados++;
                    $inv_mysql->query("UPDATE contrarrecibos SET crExterno = '1' where folio = '$folioF' and aliasGrupo = '$aliasF'");
                }
            }

            if ($cumpleRequisitos) {
                $registrosCumplen++;
            }
        }

        ?>

        <!-- INTERFAZ DE USUARIO - SIMILAR AL BUSCADOR DE FACTURAS -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Búsqueda de Contrarecibos</h5>
            </div>
            <div class="card-body">
                <!-- Tarjetas de estadísticas -->
                <div class="row">
                    <!-- Total de registros -->
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <div class="text-primary mb-2">
                                    <i class="bi bi-inboxes fs-1"></i>
                                </div>
                                <h3 class="card-title text-primary"><?= $totalRegistros ?></h3>
                                <p class="card-text text-muted">Recolecciones encontradas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Procesadas -->
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-warning">
                            <div class="card-body text-center">
                                <div class="text-warning mb-2">
                                    <i class="bi bi-gear-fill fs-1"></i>
                                </div>
                                <h3 class="card-title text-warning"><?= $registrosProcesados ?></h3>
                                <p class="card-text text-muted">Registros procesados</p>
                            </div>
                        </div>
                    </div>

                    <!-- Proveedores actualizados -->
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="bi bi-building-check fs-1"></i>
                                </div>
                                <h3 class="card-title text-success"><?= $proveedoresActualizados ?></h3>
                                <p class="card-text text-muted">Contrarecibos proveedor</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fleteros actualizados -->
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="bi bi-truck fs-1"></i>
                                </div>
                                <h3 class="card-title text-success"><?= $fleterosActualizados ?></h3>
                                <p class="card-text text-muted">Contrarecibos fletero</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso -->
                <div class="progress mb-4" style="height: 25px;">
                    <?php
                    $porcentaje = $totalRegistros > 0 ? ($registrosProcesados / $totalRegistros) * 100 : 0;
                    ?>
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?= $porcentaje ?>%" 
                         aria-valuenow="<?= $porcentaje ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= round($porcentaje) ?>% Completado
                    </div>
                </div>

                <!-- Resumen detallado -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Resumen del proceso</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Recolecciones en estado activo</span>
                                        <span class="badge bg-primary rounded-pill"><?= $totalRegistros ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Registros con documentos listos</span>
                                        <span class="badge bg-warning rounded-pill"><?= $registrosCumplen ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Contrarecibos encontrados (Proveedor)</span>
                                        <span class="badge bg-success rounded-pill"><?= $proveedoresActualizados ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Contrarecibos encontrados (Fletero)</span>
                                        <span class="badge bg-success rounded-pill"><?= $fleterosActualizados ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Eficiencia del proceso</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="p-3 bg-light rounded">
                                            <h4 class="text-info"><?= $totalRegistros > 0 ? round(($proveedoresActualizados / $totalRegistros) * 100, 1) : 0 ?>%</h4>
                                            <small class="text-muted">Éxito proveedores</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="p-3 bg-light rounded">
                                            <h4 class="text-info"><?= $totalRegistros > 0 ? round(($fleterosActualizados / $totalRegistros) * 100, 1) : 0 ?>%</h4>
                                            <small class="text-muted">Éxito fleteros</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded">
                                            <h4 class="text-primary"><?= $totalRegistros > 0 ? round(($registrosProcesados / $totalRegistros) * 100, 1) : 0 ?>%</h4>
                                            <small class="text-muted">Procesamiento total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>-->

                <!-- Mensaje de resultado -->
                <div class="alert alert-<?= ($proveedoresActualizados + $fleterosActualizados) > 0 ? 'success' : 'warning' ?> mt-3">
                    <i class="bi bi-<?= ($proveedoresActualizados + $fleterosActualizados) > 0 ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?php if (($proveedoresActualizados + $fleterosActualizados) > 0): ?>
                        Se encontraron <strong><?= $proveedoresActualizados + $fleterosActualizados ?></strong> contrarecibos correctamente.
                    <?php else: ?>
                        No se encontraron contrarecibos para las recolecciones procesadas.
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                <small><i class="bi bi-clock me-1"></i>Proceso completado el <?= date('d/m/Y H:i:s') ?></small>
            </div>
        </div>

        <?php
    } else {
        echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No se recibió el parámetro de zona.</div>';
    }
}
?>