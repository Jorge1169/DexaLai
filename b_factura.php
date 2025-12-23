<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

if ($var_exter == 0) {
	echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error de conexión. Contactar a SISTEMAS</div>';
	logActivity('INV', 'Error de conexión en buscar facturas');
} else {
	if(isset($_POST['zon'])) { 
		$zon = $_POST['zon'];
		logActivity('INV', ' Se buscaron facturas en invoice ');

        // Inicializar contadores
		$totalRegistros = 0;
		$registrosCumplen = 0;
		$proveedoresActualizados = 0;
		$fleterosActualizados = 0;
		$facturasRechazadasProveedor = 0;
		$facturasRechazadasFletero = 0;

		if ($zon == 0) {
			$QueryR = "SELECT r.*, pr.cod AS codigo_proveedor, tr.placas AS codigo_fletero
			FROM recoleccion r
			LEFT JOIN proveedores pr on r.id_prov = pr.id_prov
			LEFT JOIN transportes tr on r.id_transp = tr.id_transp
			WHERE r.status = '1' AND (r.factura_pro is null OR r.doc_fle is null)";
		} else {
			$QueryR = "SELECT r.*, pr.cod AS codigo_proveedor, tr.placas AS codigo_fletero
			FROM recoleccion r
			LEFT JOIN proveedores pr on r.id_prov = pr.id_prov
			LEFT JOIN transportes tr on r.id_transp = tr.id_transp
			WHERE r.status = '1' AND (r.factura_pro is null OR r.doc_fle is null) AND r.zona = '$zon'";
		}

		$slv = $conn_mysql->query($QueryR);
		$totalRegistros = mysqli_num_rows($slv);

		while ($slRecole = mysqli_fetch_array($slv)) {
			$id_recol = $slRecole['id_recol'];
			$cumpleRequisitos = false;

            // Procesar facturas de proveedores
			if ((empty($slRecole['doc_pro']) OR empty($slRecole['d_f_p'])) && !empty($slRecole['remision'])) {
				$Remision = $slRecole['remision'];
				$proveedor = $slRecole['codigo_proveedor'];
				$cumpleRequisitos = true;

				$BuscaFac0 = $inv_mysql->query("SELECT * FROM facturas WHERE remision = '$Remision' AND codigoProveedor = '$proveedor'");
				//$BuscaFac0 = $inv_mysql->query("SELECT * FROM facturas WHERE remision like '%$Remision%' AND codigoProveedor = '$proveedor'");
				$BusFac1 = mysqli_fetch_array($BuscaFac0);

				if (!empty($BusFac1['id'])) {// existe
					// VERIFICAR SI LA FACTURA ESTÁ RECHAZADA
					if (isset($BusFac1['status']) && $BusFac1['status'] === 'Rechazado') {
						// Factura rechazada - Eliminar y sumar contador
						$nuevoValor = $slRecole['FacCexis'] + 1;
						$conn_mysql->query("UPDATE recoleccion SET 
							FacCexis = '$nuevoValor', 
							factura_pro = NULL, 
							doc_pro = NULL, 
							d_f_p = NULL,
							folio_inv_pro = NULL,
							alias_inv_pro = NULL
							WHERE id_recol = '$id_recol'");
						$facturasRechazadasProveedor++;
					} else {
						// Factura válida - Proceder con la actualización normal
						$ubicacionF = $BusFac1['ubicacion'].$BusFac1['nombreInternoPDF'];
						$FactInv = $BusFac1['folio'];
						$ubicacionEA = NULL;

						if ($BusFac1['ea'] == 1) {// existe la evidencia
							$fecha_timestamp1 = strtotime($BusFac1['fechaFactura']);
							$fecha_form = date("ymd", $fecha_timestamp1);
							$ubicacionEA = $BusFac1['ubicacion'].'EA_'.str_replace("-", "", $BusFac1['codigoProveedor'].'_'.$BusFac1['folio'].'_'.$fecha_form);
						}

						$conn_mysql->query("UPDATE recoleccion SET factura_pro = '$FactInv', doc_pro = '$ubicacionF', d_f_p = '$ubicacionEA' WHERE id_recol = '$id_recol'");
						$proveedoresActualizados++;
					}
				}
			}

            // Procesar documentos de fleteros
			if ((empty($slRecole['doc_fle']) OR empty($slRecole['d_f_f'])) && !empty($slRecole['factura_fle'])) {

				$folio = $slRecole['factura_fle'];
				$fletero = $slRecole['codigo_fletero'];

				$cumpleRequisitos = true;

				$BusFF0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$fletero' AND folio = '$folio'");// buscamos la factura
				$BusFF1 = mysqli_fetch_array($BusFF0);

				if (!empty($BusFF1['id'])) {// exite la factura
					// VERIFICAR SI LA FACTURA ESTÁ RECHAZADA
					if (isset($BusFF1['status']) && $BusFF1['status'] === 'Rechazado') {
						// Factura rechazada - Eliminar y sumar contador
						$nuevoValor = $slRecole['FacFexis'] + 1;
						$conn_mysql->query("UPDATE recoleccion SET 
							FacFexis = '$nuevoValor', 
							factura_fle = NULL, 
							doc_fle = NULL, 
							d_f_f = NULL,
							folio_inv_fle = NULL,
							alias_inv_fle = NULL,
							im_tras_inv = NULL, 
							im_rete_inv = NULL, 
							sub_tot_inv = NULL, 
							total_inv = NULL
							WHERE id_recol = '$id_recol'");
						$facturasRechazadasFletero++;
					} else {
						// Factura válida - Proceder con la actualización normal
						$UbicaFF = $BusFF1['ubicacion'].$BusFF1['nombreInternoPDF'];
						$im_tras_inv = $BusFF1['impuestoTraslado'];
						$im_rete_inv = $BusFF1['impuestoRetenido'];
						$sub_tot_inv = $BusFF1['subtotal'];
						$total_inv = $BusFF1['total'];

						$ubicacionFF = NULL;

						if ($BusFF1['ea'] == '1') {
							$fecha_timestampFF1 = strtotime($BusFF1['fechaFactura']);
							$fecha_formFF = date("ymd", $fecha_timestampFF1);
							$ubicacionFF = $BusFF1['ubicacion'].'EA_'.str_replace("-", "", $BusFF1['codigoProveedor'].'_'.$BusFF1['folio'].'_'.$fecha_formFF);
						}

						$conn_mysql->query("UPDATE recoleccion SET doc_fle = '$UbicaFF', d_f_f = '$ubicacionFF', im_tras_inv = '$im_tras_inv', im_rete_inv = '$im_rete_inv', sub_tot_inv = '$sub_tot_inv',total_inv = '$total_inv' WHERE id_recol = '$id_recol'");
						$fleterosActualizados++;
					}
				}
			}

			if ($cumpleRequisitos) {
				$registrosCumplen++;
			}
		}
		?>
		<div class="card">
			<div class="card-header bg-teal text-white">
				<h5 class="mb-0"><i class="bi bi-search me-2"></i>Resultados de la búsqueda</h5>
			</div>
			<div class="card-body">
				<!-- Tarjetas de estadísticas en grid 2x3 -->
				<div class="row">
					<!-- Recolecciones procesadas -->
					<div class="col-2">
						<div class="card h-100 shadow-sm">
							<div class="card-body text-center p-3">
								<div class="text-primary mb-2">
									<i class="bi bi-inboxes-fill fs-4"></i>
								</div>
								<h4 class="card-title text-primary fw-bold"><?= $totalRegistros ?></h4>
								<p class="card-text text-muted small">Recolecciones procesadas</p>
							</div>
						</div>
					</div>

					<!-- Cumplen requisitos -->
					<div class="col-2">
						<div class="card h-100 shadow-sm">
							<div class="card-body text-center p-3">
								<div class="text-info mb-2">
									<i class="bi bi-funnel-fill fs-4"></i>
								</div>
								<h4 class="card-title text-info fw-bold"><?= $registrosCumplen ?></h4>
								<p class="card-text text-muted small">Cumplen requisitos</p>
							</div>
						</div>
					</div>

					<!-- Facturas de proveedor -->
					<div class="col-2">
						<div class="card h-100 shadow-sm">
							<div class="card-body text-center p-3">
								<div class="text-success mb-2">
									<i class="bi bi-building-check fs-4"></i>
								</div>
								<h4 class="card-title text-success fw-bold"><?= $proveedoresActualizados ?></h4>
								<p class="card-text text-muted small">Facturas proveedor</p>
							</div>
						</div>
					</div>

					<!-- Documentos de fletero -->
					<div class="col-2">
						<div class="card h-100 shadow-sm">
							<div class="card-body text-center p-3">
								<div class="text-success mb-2">
									<i class="bi bi-truck fs-4"></i>
								</div>
								<h4 class="card-title text-success fw-bold"><?= $fleterosActualizados ?></h4>
								<p class="card-text text-muted small">Docs fletero</p>
							</div>
						</div>
					</div>

					<!-- Facturas rechazadas proveedor -->
					<div class="col-2">
						<div class="card h-100 shadow-sm border-warning">
							<div class="card-body text-center p-3">
								<div class="text-warning mb-2">
									<i class="bi bi-exclamation-triangle-fill fs-4"></i>
								</div>
								<h4 class="card-title text-warning fw-bold"><?= $facturasRechazadasProveedor ?></h4>
								<p class="card-text text-muted small">Rechazadas proveedor</p>
							</div>
						</div>
					</div>

					<!-- Facturas rechazadas fletero -->
					<div class="col-2">
						<div class="card h-100 shadow-sm border-warning">
							<div class="card-body text-center p-3">
								<div class="text-warning mb-2">
									<i class="bi bi-exclamation-triangle-fill fs-4"></i>
								</div>
								<h4 class="card-title text-warning fw-bold"><?= $facturasRechazadasFletero ?></h4>
								<p class="card-text text-muted small">Rechazadas fletero</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Resumen ejecutivo -->
				<div class="p-3 rounded mb-3 mt-3">
					<h6 class="border-bottom pb-2 mb-3"><i class="bi bi-list-check me-2"></i>Resumen ejecutivo</h6>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-inboxes text-primary me-2"></i>
							<span>Recolecciones procesadas</span>
						</div>
						<span class="badge bg-primary rounded-pill"><?= $totalRegistros ?></span>
					</div>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-funnel text-info me-2"></i>
							<span>Cumplen requisitos para búsqueda</span>
						</div>
						<span class="badge bg-info rounded-pill"><?= $registrosCumplen ?></span>
					</div>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-building text-success me-2"></i>
							<span>Facturas de proveedores actualizadas</span>
						</div>
						<span class="badge bg-success rounded-pill"><?= $proveedoresActualizados ?></span>
					</div>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-truck text-success me-2"></i>
							<span>Documentos de fleteros actualizados</span>
						</div>
						<span class="badge bg-success rounded-pill"><?= $fleterosActualizados ?></span>
					</div>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-exclamation-triangle text-warning me-2"></i>
							<span>Facturas de proveedor rechazadas (eliminadas)</span>
						</div>
						<span class="badge bg-warning rounded-pill"><?= $facturasRechazadasProveedor ?></span>
					</div>

					<div class="d-flex justify-content-between align-items-center py-2">
						<div class="d-flex align-items-center">
							<i class="bi bi-exclamation-triangle text-warning me-2"></i>
							<span>Facturas de fletero rechazadas (eliminadas)</span>
						</div>
						<span class="badge bg-warning rounded-pill"><?= $facturasRechazadasFletero ?></span>
					</div>
				</div>

				<!-- Nota informativa -->
				<?php if ($facturasRechazadasProveedor > 0 || $facturasRechazadasFletero > 0): ?>
					<div class="alert alert-warning mt-3">
						<i class="bi bi-info-circle me-2"></i>
						<strong>Nota:</strong> Se encontraron facturas con estado "Rechazada" en el sistema externo. 
						Estas facturas han sido eliminadas automáticamente y sus contadores de eliminación han sido incrementados.
					</div>
				<?php endif; ?>
			</div>
			<div class="card-footer text-muted text-center">
				<small>Proceso completado el <?= date('d/m/Y H:i:s') ?></small>
			</div>
		</div>
		<?php
	}
}
?>