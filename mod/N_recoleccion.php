<?php
$folio = '';// Folio a guardar
$folioM = '';// folio a mostrar
$fecha_seleccionada = $_POST['fecha_recoleccion'] ?? date('Y-m-d'); // Nueva variable para fecha seleccionada
$fecha = date('ym', strtotime($fecha_seleccionada));// fecha para codigo basada en fecha seleccionada
$mes_actual = date('m', strtotime($fecha_seleccionada));
$anio_actual = date('Y', strtotime($fecha_seleccionada));
$fecha_actual = $fecha_seleccionada . ' ' . date('H:i:s'); // Usar fecha seleccionada

if ($zona_seleccionada == 0) {
	$zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1'");
} else {
	$zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
} 
$zona_s1 = mysqli_fetch_array($zona_s0);

// Consulta para obtener el último folio del mes de la fecha seleccionada
$query = "SELECT folio FROM recoleccion WHERE status = '1' 
AND YEAR(fecha_r) = '$anio_actual'  
AND MONTH(fecha_r) = '$mes_actual' 
AND zona = '".$zona_s1['id_zone']."'
ORDER BY folio DESC 
LIMIT 1";

$Reco00 = $conn_mysql->query($query);

if ($Reco00 && $Reco00->num_rows > 0) {
    // Hay registros este mes
	$Reco01 = $Reco00->fetch_assoc();
	$ultimo_folio = intval($Reco01['folio']);

    // Incrementar y validar 
	$nuevo_numero = $ultimo_folio + 1;

	if ($nuevo_numero > 1111) {
		$folio = 'ERROR: Límite alcanzado';
	} else {
        $folio = str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT); // 4 dígitos
      }
    } else {
    // No hay registros este mes, empezar desde 001
    	$folio = '0001';
    }
    $folioM = $zona_s1['cod']."-".$fecha.$folio;
    if (isset($_POST['guardar01'])) {
    	  $fecha_recoleccion = $_POST['fecha_recoleccion'] ?? date('Y-m-d');
	//receteamos la fecha
    	$fecha_factura = $_POST['FecaFac'] ?? date('Y-m-d');
    	if (!DateTime::createFromFormat('Y-m-d', $fecha_factura)) {
    		alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "N_recoleccion");
    		exit;
    }// fecha formateada
    $fech_actu = date('Y-m-d'); //fecha de actualizacion

	$PFC =  $_POST['id_preFle'] ?? 0; //precio flete 
	$PDC =  $_POST['id_prePD'] ?? 0; //precio compra
	$PDV =  $_POST['id_prePDv'] ?? 0; //precio venta
	$BODprov = $_POST['bodgeProv'] ?? 0; // id del proveedor
	$idFle = $_POST['idFletero'] ?? 0; // id del proveedor
	// nuevas validaciones
	$tipo_flete = $_POST['tipo_flete'] ?? '';
	$bodega_proveedor = $_POST['bodgeProv'] ?? 0;
	$bodega_cliente = $_POST['bodgeCli'] ?? 0;
    // validar

    // Validar que se haya seleccionado el tipo de flete
	if (empty($tipo_flete)) {
		alert("Debe seleccionar el tipo de flete", 0, "N_recoleccion");
		exit;
	}

    // Validar bodegas seleccionadas
	if ($bodega_proveedor <= 0) {
		alert("Debe seleccionar una bodega de proveedor válida", 0, "N_recoleccion");
		exit;
	}
	if ($bodega_cliente <= 0) {
		alert("Debe seleccionar una bodega de cliente válida", 0, "N_recoleccion");
		exit;
	}

	// Validar que los precios sean válidos (mayor a 0)
	if ($PFC <= 0) {
		alert("Debe seleccionar un precio de flete válido", 0, "N_recoleccion");
		exit;
	}
	if ($PDC <= 0) {
		alert("Debe seleccionar un precio de compra válido", 0, "N_recoleccion");
		exit;
	}
	if ($PDV <= 0) {
		alert("Debe seleccionar un precio de venta válido", 0, "N_recoleccion");
		exit;
	}

	$VerBP0 = $conn_mysql->query("SELECT * FROM direcciones WHERE id_direc = '$BODprov' AND email != ''");
	$VerBP1 = mysqli_fetch_array($VerBP0);
	if (empty($VerBP1['id_direc'])) {
		alert("Bodega del Proveedor sin correo", 0, "N_recoleccion"); 
		exit;
	}

	$Verfle0 = $conn_mysql->query("SELECT * FROM transportes WHERE id_transp = '$idFle' AND correo != ''");
	$Verfle1 = mysqli_fetch_array($Verfle0);
	if (empty($Verfle1['id_transp'])) {
		alert("El fletero no cuenta con correo", 0, "N_recoleccion"); 
		exit;
	}

	// Vamoa a subir los datos
	try {
		$conn_mysql->begin_transaction();

		$RecoleData = [
            'folio' => $folio, // Usar la variable $folio generada
            'fecha_r' => $fecha_recoleccion,
            'zona' => $_POST['zona'],
            'id_prov' => $_POST['idProveedor'],
            'id_direc_prov' => $BODprov,
            'id_transp' => $idFle,
            'pre_flete' => $PFC,
            'id_cli' => $_POST['idCliente'],
            'id_direc_cli' => $_POST['bodgeCli'],
            'factura_v' => $_POST['FacVen'],
            'fecha_v' => $fecha_factura,
            'id_user' => $idUser,
            'status' => 1
          ];
        // Insertar recoleccion
          $columns = implode(', ', array_keys($RecoleData));
          $placeholders = implode(', ', array_fill(0, count($RecoleData), '?'));
          $sql = "INSERT INTO recoleccion ($columns) VALUES ($placeholders)";
          $stmt = $conn_mysql->prepare($sql);

          if (!$stmt) {
          	throw new Exception("Error preparing statement: " . $conn_mysql->error);
          }

          $types = str_repeat('s', count($RecoleData));
          $stmt->bind_param($types, ...array_values($RecoleData));
          $stmt->execute();

          if ($stmt->errno) {
          	throw new Exception("Error executing statement: " . $stmt->error);
          }

          $id_recol = $conn_mysql->insert_id;

      // SI EL PRECIO TIENE UN COSTO DE 0 PESOS SE DEBE DE ACTUALIZAR LA FACTURA Y EL PRECIO

          $BusPrecio0 = $conn_mysql->query("SELECT * FROM precios WHERE id_precio = '$PFC'");

          $BusPrecio1 = mysqli_fetch_array($BusPrecio0);
          if ($BusPrecio1['precio'] == 0) {

          	$conn_mysql->query("UPDATE recoleccion SET factura_fle = 'N/A' WHERE id_recol = '$id_recol'");
          }


        // Registrar datos del producto
          $ProReData = [
          	'id_recol' => $id_recol,
          	'id_prod' => $_POST['idProd'],
          	'id_cprecio_c' => $PDC,
          	'id_cprecio_v' => $PDV,
          	'id_user' => $idUser,
          	'status' => 1
          ];

          $columnsPro = implode(', ', array_keys($ProReData));
          $placeholdersAlm = implode(', ', array_fill(0, count($ProReData), '?'));
          $sqlPro = "INSERT INTO producto_recole ($columnsPro) VALUES ($placeholdersAlm)";
          $stmtPro = $conn_mysql->prepare($sqlPro);

          if (!$stmtPro) {
          	throw new Exception("Error preparing almacén statement: " . $conn_mysql->error);
          }

          $typesPro = str_repeat('s', count($ProReData));
          $stmtPro->bind_param($typesPro, ...array_values($ProReData));
          $stmtPro->execute();

          if ($stmtPro->errno) {
          	throw new Exception("Error executing almacén statement: " . $stmtPro->error);
          }

          $conn_mysql->commit();
          alert("Recoleccion registrada exitosamente", 1, "V_recoleccion&id=" .$id_recol);
          logActivity('CREAR', 'Dio de alta una nueva recolección '. $id_recol);

        } catch (mysqli_sql_exception $e) {
        	$conn_mysql->rollback();
        	alert("Error: " . $e->getMessage(), 0, "N_recoleccion");
        } catch (Exception $e) {
        	$conn_mysql->rollback();
        	alert("Error: " . $e->getMessage(), 0, "N_recoleccion");
        }



      }
      $Primera_zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
      $Primera_zona1 = mysqli_fetch_array($Primera_zona0);
      $Primer_zona_select =  $Primera_zona1['id_zone'];

      ?>
      <div class="container mt-2">
      	<div class="card shadow-sm">
      		<div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
      			<h5 class="mb-0">Nueva recolección</h5>
      			<!-- Botón Cerrar -->
      			<button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
      			<script>
      				document.getElementById('btnCerrar').addEventListener('click', function() {
      					window.close();
      				});
      			</script>
      		</div>
      		<div class="card-body">
      			<form class="forms-sample" method="post" action="">
      				<div class="form-section shadow-sm ">
      					<h5 class="section-header">Información Básica</h5>
      					<div class="row g-3">
      						<div class="col-md-4">
      							<label for="zona" class="form-label">Zona</label>
      							<select class="form-select" name="zona" id="zona" onchange="cFolio1();cFolio2();cFolio3();cFolio4();cFolio5()">
      								<?php
      								if ($zona_seleccionada == 0) {
      									$zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
      								}else{
      									$zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
      								}
      								while ($zona1 = mysqli_fetch_array($zona0)) {
      									$Id_zona = $zona1['id_zone'];
      									?>
      									<option value="<?=$zona1['id_zone']?>"><?=$zona1['PLANTA']?></option>
      									<?php
      								} 
      								?>
      							</select>
      						</div>
      						<div class="col-md-4" id="resulFolio">
      							<label for="folio" class="form-label">Folio</label>
      							<input type="text" id="folio01" class="form-control" value="<?=$folioM?>" disabled>
      							<input type="hidden" name="folio" value="<?=$folio?>">
      						</div>
      						<div class="col-md-4">
      							<label for="fecha_recoleccion" class="form-label">Fecha de Recolección</label>
      							<input type="date" name="fecha_recoleccion" id="fecha_recoleccion" class="form-control" 
      							value="<?=$fecha_seleccionada?>" max="<?=date('Y-m-d')?>" 
      							onchange="actualizarFolioYPrecios()" required>
      							<small class="text-muted">Puede seleccionar fechas anteriores</small>
      						</div>
      					</div>
      				</div>

      				<div class="form-section shadow-sm">
      					<h5 class="section-header">Proveedor y bodega:</h5>
      					<div class="row g-3">
      						<div class="col-md-4" id="resulProv">
      							<label for="IdProveedor" class="form-label">Proveedor</label>
      							<select class="form-select" name="idProveedor" id='idProveedor' onchange="idProv1()" required>
      								<option selected disabled value="">Selecciona un proveedor...</option>
      								<?php
      								if ($zona_seleccionada == 0) {
      									$Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$Primer_zona_select'");	
      								}else{
      									$Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$zona_seleccionada'");
      								}
      								while ($Prov_id1 = mysqli_fetch_array($Prov_id0)) {
      									?>
      									<option value="<?=$Prov_id1['id_prov']?>"><?=$Prov_id1['cod']." / ".$Prov_id1['rs']?></option>
      									<?php
      								} 
      								?>
      							</select>
      						</div>
      						<div class="col-md-4" id="BodePro">
      							<label for="Bodegas" class="form-label">Bodegas</label>
      							<select class="form-select" disabled>
      								<option>Selecciona un proveedor</option>
      							</select>
      						</div>
      					</div>
      				</div>

      				<div class="form-section shadow-sm">
      					<h5 class="section-header">Cliente y bodega:</h5>
      					<div class="row g-3">
      						<div class="col-md-4" id="resulCli">
      							<label for="Cliente" class="form-label">Cliente</label>
      							<select class="form-select" name="idCliente" id="idCliente" onchange="idcl()" required>
      								<option selected disabled value="">Selecciona un cliente...</option>
      								<?php
      								if ($zona_seleccionada == 0) {
      									$Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$Primer_zona_select'");	
      								}else{
      									$Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$zona_seleccionada'");
      								}
      								while ($Cli_id1 = mysqli_fetch_array($Cli_id0)) {
      									?>
      									<option value="<?=$Cli_id1['id_cli']?>"><?=$Cli_id1['cod']." - ".$Cli_id1['nombre']?></option>
      									<?php
      								} 
      								?>
      							</select>
      						</div>
      						<div class="col-md-4" id="CliEntrega">
      							<label for="BodCli" class="form-label">Bodega Cliente</label>
      							<select class="form-select" id="bodgeCli" disabled>
      								<option>Selecciona un cliente</option>
      							</select>
      						</div>
      					</div>
      				</div>

      				<!-- Nuevas secciones -->

      				<div class="form-section shadow-sm">
      					<h5 class="section-header">Fletero y tipo de flete:</h5>
      					<div class="row g-3">
      						<div class="col-md-4" id="resulfLE">
      							<label for="Fletero" class="form-label">Fletero</label>
      							<select class="form-select" name="idFletero" id="idFletero" onchange="idFl()" required>
      								<option selected disabled value="">Selecciona un transportista...</option>
      								<?php
      								if ($zona_seleccionada == 0) {
      									$Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$Primer_zona_select'");	
      								}else{
      									$Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$zona_seleccionada'");
      								}
      								while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
      									$verCorF = (empty($Fle_id1['correo'])) ? ' ' : '📧' ;
      									?>
      									<option value="<?=$Fle_id1['id_transp']?>"><?=$Fle_id1['placas']." - ".$Fle_id1['razon_so']." ".$verCorF?></option>
      									<?php
      								} 
      								?>
      							</select>
      						</div>
      						<div class="col-md-3" id="TipoFlete">
      							<label for="tipo_flete" class="form-label">Tipo de Flete</label>
      							<select class="form-select" name="tipo_flete" id="tipo_flete" onchange="idFl()" required>
      								<option selected disabled value="">Selecciona tipo...</option>
      								<option value="FT">Por tonelada</option>
      								<option value="FV">Por viaje</option>
      							</select>
      						</div>
      						<div class="col-md-3" id="PreFle">
      							<label for="preFl" class="form-label">Precio del flete</label>
      							<select class="form-select" disabled>
      								<option>Selecciona fletero y tipo</option>
      							</select>
      						</div>
      					</div>
      				</div>

      				<div class="form-section shadow-sm">
      					<h5 class="section-header">Producto y precios:</h5>
      					<div class="row g-3">
      						<div class="col-md-4" id="resulProd">
      							<label for="Fletero" class="form-label">Producto</label>
      							<select class="form-select" name="idProd" id="idProd" onchange="idPd();idPdv()" required>
      								<option selected disabled value="">Selecciona un producto...</option>
      								<?php
      								if ($zona_seleccionada == 0) {
      									$Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$Primer_zona_select'");	
      								}else{
      									$Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$zona_seleccionada'");
      								}
      								while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
      									?>
      									<option value="<?=$Prod_id1['id_prod']?>"><?=$Prod_id1['cod']." - ".$Prod_id1['nom_pro']?></option>
      									<?php
      								} 
      								?>
      							</select>
      						</div>
      						<div class="col-md-4" id="PrePro">
      							<label for="prePD" class="form-label">Precio de compra</label>
      							<select class="form-select" disabled>
      								<option>Selecciona un producto</option>
      							</select>
      						</div>
      						<div class="col-md-4" id="PreProv">
      							<label for="prePDv" class="form-label">Precio de venta</label>
      							<select class="form-select" disabled>
      								<option>Selecciona un producto</option>
      							</select>
      						</div>
      					</div>
      				</div>


      				<div class="form-section shadow-sm">
      					<h5 class="section-header">Información de factura: </h5>
      					<div class="row g-3">
      						<div class="col-md-4">
      							<label for="FacVen" class="form-label">Factura de venta</label>
      							<input type="text" name="FacVen" id="FacVen" class="form-control" required>
      						</div>
      						<script>
                        const FacVenInput = document.getElementById('FacVen');
                        // Eliminar espacios en tiempo real
                        FacVenInput.addEventListener('input', function() {
                            this.value = this.value.replace(/\s+/g, '');
                        });
                        
                        // Validar antes de enviar formulario
                        document.querySelector('form').addEventListener('submit', function(e) {
                            const FacVenValue = FacVenInput.value.trim();
                            
                            if (FacVenValue.includes(' ')) {
                                e.preventDefault();
                                alert('El campo remisión no debe contener espacios');
                                FacVenInput.focus();
                            }
                        });
                    </script>
      						<div class="col-md-4">
      							<label for="FecaFac" class="form-label">Fecha de factura</label>
      							<input type="date" name="FecaFac" id="FecaFac" class="form-control" required>
      						</div>
      					</div>
      				</div>

      				<div class="d-flex justify-content-md-end mt-4">
      					<button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
      				</div>
      			</form>
      		</div>
      	</div>
      </div>

<!-- Nuevas secciones -->
<!-- Nuevos scripts -->
<script>
    // Inicializar Select2 para los nuevos campos
	$(document).ready(function() {
		$('#tipo_flete').select2({
			placeholder: "Selecciona tipo de flete",
			allowClear: false,
			language: "es",
			width: '100%'
		});

        // ... (otros select2 iguales)
	});

    // Función actualizada para obtener precios de flete
	function idFl() {
		var idFletero = document.getElementById('idFletero').value;
		var tipoFlete = document.getElementById('tipo_flete').value;
		var bodgeProv = document.getElementById('bodgeProv') ? document.getElementById('bodgeProv').value : 0;
		var bodgeCli = document.getElementById('bodgeCli') ? document.getElementById('bodgeCli').value : 0;

    // Validar que todos los campos estén llenos
		if (!idFletero || !tipoFlete || !bodgeProv || !bodgeCli) {
			$('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Complete todos los campos</option></select>');
			return;
		}

		var parametros = {
			"idFletero": idFletero,
			"tipoFlete": tipoFlete,
			"origen": bodgeProv,
			"destino": bodgeCli
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control">Buscando precios...</div>');
			},
			success: function (mensaje) {
				$('#PreFle').html(mensaje);
			},
			error: function(xhr, status, error) {
				console.error('Error AJAX:', error);
				$('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control text-danger">Error al cargar precios</div>');
			}
		});
	}

    // Actualizar precios de flete cuando cambien las bodegas
	function actualizarPrecioFlete() {
		if (document.getElementById('idFletero') && document.getElementById('tipo_flete')) {
			idFl();
		}
	}
</script>

<!-- Nuevos scripts -->
<script>
	$(document).ready(function() {
		$('#idFletero').select2({
			placeholder: "Selecciona o busca una opción",
			allowClear: true,
			language: "es",
			width: '100%'
		});
	});
</script>
<script>
	$(document).ready(function() {
		$('#idProd').select2({
			placeholder: "Selecciona o busca una opción",
			allowClear: true,
			language: "es",
			width: '100%'
		});
	});
</script>
<script>
	$(document).ready(function() {
		$('#idCliente').select2({
			placeholder: "Selecciona o busca una opción",
			allowClear: true,
			language: "es",
			width: '100%'
		});
	});
</script>
<script>
	$(document).ready(function() {
		$('#idProveedor').select2({
			placeholder: "Selecciona o busca una opción",
			allowClear: true,
			language: "es",
			width: '100%'
		});
	});
</script>
<script>
	function idcl() {
		var idCliente = document.getElementById('idCliente').value;
		var parametros = {
			"idCliente": idCliente
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#CliEntrega').html("");
			},
			success: function (mensaje) {
				$('#CliEntrega').html(mensaje);
                // Actualizar precio flete cuando cambie la bodega cliente
				$(document).ready(function() {
					if ($('#bodgeCli').length) {
						$('#bodgeCli').select2({
							placeholder: "Selecciona bodega",
							allowClear: true,
							language: "es"
						}).on('change', actualizarPrecioFlete);
					}
				});
			}
		});
	}
</script>
<script>
	function idPd() {
		var idProd = document.getElementById('idProd').value;
		var parametros = {
			"idProd": idProd
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PrePro').html("");
			},
			success: function (mensaje) {
				$('#PrePro').html(mensaje);
			}
		});
	}
</script>
<script>
	function idPdv() {
		var idProd = document.getElementById('idProd').value;
		var bodgeCli = document.getElementById('bodgeCli').value;
		var parametros = {
			"idProdV": idProd,
			"bodgeCli": bodgeCli
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PreProv').html("");
			},
			success: function (mensaje) {
				$('#PreProv').html(mensaje);
			}
		});
	}
</script>
<script>
	function idProv1() {
		var idProveedor = document.getElementById('idProveedor').value;
		var parametros = {
			"idProveedor": idProveedor
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#BodePro').html("");
			},
			success: function (mensaje) {
				$('#BodePro').html(mensaje);
                // Inicializar Select2 y actualizar precio flete
				$(document).ready(function() {
					if ($('#bodgeProv').length) {
						$('#bodgeProv').select2({
							placeholder: "Selecciona bodega",
							allowClear: true,
							language: "es"
						}).on('change', actualizarPrecioFlete);
					}
				});
			}
		});
	}
</script>
<script>
	function cFolio1() {
		var zona = document.getElementById('zona').value;
		var parametros = {
			"zona": zona
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulFolio').html("");
			},
			success: function (mensaje) {
				$('#resulFolio').html(mensaje);
			}
		});
	}
</script>
<script>
	function cFolio2() {
		var zona = document.getElementById('zona').value;
		var parametros = {
			"zona2": zona
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulProv').html("");
			},
			success: function (mensaje) {
				$('#resulProv').html(mensaje);
			}
		});
	}
</script>
<script>
	function cFolio3() {
		var zona = document.getElementById('zona').value;
		var parametros = {
			"zona3": zona
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulfLE').html("");
			},
			success: function (mensaje) { 
				$('#resulfLE').html(mensaje);
			}
		});
	}
</script>
<script>
	function cFolio4() {
		var zona = document.getElementById('zona').value;
		var parametros = {
			"zona4": zona
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulProd').html("");
			},
			success: function (mensaje) {
				$('#resulProd').html(mensaje);
			}
		});
	}
</script>
<script>
	function cFolio5() {
		var zona = document.getElementById('zona').value;
		var parametros = {
			"zona5": zona
		};
		console.log(parametros);
		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulCli').html("");
			},
			success: function (mensaje) {
				$('#resulCli').html(mensaje);
			}
		});
	}
</script>

<script>
	// Agregar esta función después de las demás funciones JavaScript
	function actualizarFolioYPrecios() {
		var fechaSeleccionada = document.getElementById('fecha_recoleccion').value;
		var zona = document.getElementById('zona').value;

		if (!fechaSeleccionada || !zona) {
			return;
		}

		var parametros = {
			"fecha_recoleccion": fechaSeleccionada,
			"zona": zona
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#resulFolio').html('<label class="form-label">Folio</label><div class="form-control">Actualizando...</div>');
			},
			success: function (mensaje) {
				$('#resulFolio').html(mensaje);
            // Actualizar precios basados en la nueva fecha
				if (document.getElementById('idFletero') && document.getElementById('tipo_flete')) {
					idFl();
				}
				if (document.getElementById('idProd')) {
					idPd();
					idPdv();
				}
			},
			error: function(xhr, status, error) {
				console.error('Error al actualizar folio:', error);
				$('#resulFolio').html('<label class="form-label">Folio</label><div class="form-control text-danger">Error al actualizar</div>');
			}
		});
	}

// Modificar la función idFl para usar la fecha seleccionada
	function idFl() {
		var idFletero = document.getElementById('idFletero').value;
		var tipoFlete = document.getElementById('tipo_flete').value;
		var bodgeProv = document.getElementById('bodgeProv') ? document.getElementById('bodgeProv').value : 0;
		var bodgeCli = document.getElementById('bodgeCli') ? document.getElementById('bodgeCli').value : 0;
		var fechaRecoleccion = document.getElementById('fecha_recoleccion').value;

    // Validar que todos los campos estén llenos
		if (!idFletero || !tipoFlete || !bodgeProv || !bodgeCli || !fechaRecoleccion) {
			$('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Complete todos los campos</option></select>');
			return;
		}

		var parametros = {
			"idFletero": idFletero,
			"tipoFlete": tipoFlete,
			"origen": bodgeProv,
			"destino": bodgeCli,
			"fechaRecoleccion": fechaRecoleccion
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control">Buscando precios...</div>');
			},
			success: function (mensaje) {
				$('#PreFle').html(mensaje);
			},
			error: function(xhr, status, error) {
				console.error('Error AJAX:', error);
				$('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control text-danger">Error al cargar precios</div>');
			}
		});
	}

// Modificar funciones de precios para usar fecha seleccionada
	function idPd() {
		var idProd = document.getElementById('idProd').value;
		var fechaRecoleccion = document.getElementById('fecha_recoleccion').value;
		var parametros = {
			"idProd": idProd,
			"fechaRecoleccion": fechaRecoleccion
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PrePro').html("");
			},
			success: function (mensaje) {
				$('#PrePro').html(mensaje);
			}
		});
	}

	function idPdv() {
		var idProd = document.getElementById('idProd').value;
		var bodgeCli = document.getElementById('bodgeCli').value;
		var fechaRecoleccion = document.getElementById('fecha_recoleccion').value;
		var parametros = {
			"idProdV": idProd,
			"bodgeCli": bodgeCli,
			"fechaRecoleccion": fechaRecoleccion
		};

		$.ajax({
			data: parametros,
			url: 'get_recoleccion.php',
			type: 'POST',
			beforeSend: function () {
				$('#PreProv').html("");
			},
			success: function (mensaje) {
				$('#PreProv').html(mensaje);
			}
		});
	}
</script>