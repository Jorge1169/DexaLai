<?php
require_once 'config/conexiones.php';/// conexion a la base

$fol = '';// Folio a guardar
$folM = '';// folio a mostrar
$fe = date('ym');// fecha para codigo
$fecha_actual = date('Y-m-d H:i:s'); 
$m_actual = date('m');
$a_actual = date('Y');

// Determinar fecha a usar (seleccionada o actual)
$fecha_recoleccion = isset($_POST['fecha_recoleccion']) ? $_POST['fecha_recoleccion'] : date('Y-m-d');
$fe = date('ym', strtotime($fecha_recoleccion));// fecha para codigo
$m_actual = date('m', strtotime($fecha_recoleccion));
$a_actual = date('Y', strtotime($fecha_recoleccion));
$fecha_actual = $fecha_recoleccion . ' ' . date('H:i:s');

// Manejo de folio con fecha seleccionada
if (isset($_POST['zona']) && isset($_POST['fecha_recoleccion'])) {
    $zonaId = $_POST['zona']; 
    $fechaRecoleccion = $_POST['fecha_recoleccion'];
    
    $z_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zonaId'");
    $z_s1 = mysqli_fetch_array($z_s0);

    // Usar año y mes de la fecha seleccionada
    $anio_seleccionado = date('Y', strtotime($fechaRecoleccion));
    $mes_seleccionado = date('m', strtotime($fechaRecoleccion));
    
    $qry = "SELECT folio FROM recoleccion WHERE status = '1' 
    AND YEAR(fecha_r) = '$anio_seleccionado' 
    AND MONTH(fecha_r) = '$mes_seleccionado' 
    AND zona = '".$z_s1['id_zone']."'
    ORDER BY folio DESC 
    LIMIT 1";
    
    $Rc00 = $conn_mysql->query($qry);

    if ($Rc00 && $Rc00->num_rows > 0) {
        $Rc01 = $Rc00->fetch_assoc();
        $u_folio = intval($Rc01['folio']);
        $nuevo_n = $u_folio + 1;

        if ($nuevo_n > 1111) {
            $fol = 'ERROR: Límite alcanzado';
        } else {
            $fol = str_pad($nuevo_n, 4, '0', STR_PAD_LEFT);
        }
    } else {
        $fol = '0001';
    }

    $folM = $z_s1['cod']."-".$fe.$fol;
    ?>
    
    <?php
}
// También manejar el caso cuando solo se envía zona (para compatibilidad)
if (isset($_POST['zona']) && !isset($_POST['fecha_recoleccion'])) {
    $zonaId = $_POST['zona']; 
    
    $z_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zonaId'");
    $z_s1 = mysqli_fetch_array($z_s0);

    $qry = "SELECT folio FROM recoleccion WHERE status = '1' 
    AND YEAR(fecha_r) = '$a_actual' 
    AND MONTH(fecha_r) = '$m_actual' 
    AND zona = '".$z_s1['id_zone']."'
    ORDER BY folio DESC 
    LIMIT 1";
    
    $Rc00 = $conn_mysql->query($qry);

    if ($Rc00 && $Rc00->num_rows > 0) {
        $Rc01 = $Rc00->fetch_assoc();
        $u_folio = intval($Rc01['folio']);
        $nuevo_n = $u_folio + 1;

        if ($nuevo_n > 1111) {
            $fol = 'ERROR: Límite alcanzado';
        } else {
            $fol = str_pad($nuevo_n, 4, '0', STR_PAD_LEFT);
        }
    } else {
        $fol = '0001';
    }

    $folM = $z_s1['cod']."-".$fe.$fol;
    ?>
    <label for="folio" class="form-label">Folio</label>
    <input type="text" id="folio01" class="form-control" value="<?=$folM?>" disabled>
    <input type="hidden" name="folio" value="<?=$fol?>">
    <?php
}


if (isset($_POST['zona'])) {
	$zonaId = $_POST['zona']; 
	
	$z_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zonaId'");
	$z_s1 = mysqli_fetch_array($z_s0);

	$qry = "SELECT folio FROM recoleccion WHERE status = '1' 
	AND YEAR(fecha_r) = '$a_actual' 
	AND MONTH(fecha_r) = '$m_actual' 
	AND zona = '".$z_s1['id_zone']."'
	ORDER BY folio DESC 
	LIMIT 1";
	$Rc00 = $conn_mysql->query($qry);

	if ($Rc00 && $Rc00->num_rows > 0) {
    // Hay registros este mes
		$Rc01 = $Rc00->fetch_assoc();
		$u_folio = intval($Rc01['folio']);

    // Incrementar y validar
		$nuevo_n = $u_folio + 1;

		if ($nuevo_n > 1111) {
			$fol = 'ERROR: Límite alcanzado';
		} else {
			$fol = str_pad($nuevo_n, 4, '0', STR_PAD_LEFT);
		}

	} else {
    // No hay registros este mes, empezar desde 001
		$fol = '0001';
	}

	$folM = $z_s1['cod']."-".$fe.$fol;
	?>
	<label for="folio" class="form-label">Folio</label>
	<input type="text" id="folio01" class="form-control" value="<?=$folM?>" disabled>
	<input type="hidden" name="folio" value="<?=$fol?>">
	<?php
}
if (isset($_POST['zona2'])) {
	$zonaId0 = $_POST['zona2'];
	?>
	<label for="IdProveedor" class="form-label">Proveedor</label>
	<select class="form-select" name="idProveedor" id='idProveedor' onchange="idProv1()" required>
		<option selected disabled value="">Selecciona un proveedor...</option>
		<?php
		$Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$zonaId0'");
		while ($Prov_id1 = mysqli_fetch_array($Prov_id0)) {
			?>
			<option value="<?=$Prov_id1['id_prov']?>"><?=$Prov_id1['cod']." / ".$Prov_id1['rs']?></option>
			<?php
		} 
		?>
	</select>
	<script>
		$(document).ready(function() {
			$('#idProveedor').select2({
				placeholder: "Selecciona o busca una opción",
				allowClear: true,
				language: "es"
			});
		});
	</script>
	<?php
}
if (isset($_POST['zona3'])) {
	$zonaId0 = $_POST['zona3'];
	?>
	<label for="Fletero" class="form-label">Fletero</label>
	<select class="form-select" name="idFletero" id="idFletero" onchange="idFl()" required>
		<option selected disabled value="">Selecciona un transportista...</option>
		<?php

		$Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$zonaId0'");
		while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
			$verCorF = (empty($Fle_id1['correo'])) ? ' ' : '📧' ;
			?>
			<option value="<?=$Fle_id1['id_transp']?>"><?=$Fle_id1['placas']." - ".$Fle_id1['razon_so']." ".$verCorF?></option>
			<?php
		} 
		?>
	</select>
	<script>
		$(document).ready(function() {
			$('#idFletero').select2({
				placeholder: "Selecciona o busca una opción",
				allowClear: true,
				language: "es"
			});
		});
	</script>
	<?php
}
if (isset($_POST['zona4'])) {
	$zonaId0 = $_POST['zona4'];
	?>
	<label for="Fletero" class="form-label">Producto</label>
	<select class="form-select" name="idProd" id="idProd" onchange="idPd();idPdv()" required>
		<option selected disabled value="">Selecciona un producto...</option>
		<?php

		$Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$zonaId0'");
		while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
			?>
			<option value="<?=$Prod_id1['id_prod']?>"><?=$Prod_id1['cod']." - ".$Prod_id1['nom_pro']?></option>
			<?php
		} 
		?>
	</select>
	<script>
		$(document).ready(function() {
			$('#idProd').select2({
				placeholder: "Selecciona o busca una opción",
				allowClear: true,
				language: "es"
			});
		});
	</script>
	<?php
}
if (isset($_POST['zona5'])) {
	$zonaId0 = $_POST['zona5'];
	?>
	<label for="Cliente" class="form-label">Cliente</label>
	<select class="form-select" name="idCliente" id="idCliente" onchange="idcl()" required>
		<option selected disabled value="">Selecciona un cliente...</option>
		<?php

		$Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$zonaId0'");
		while ($Cli_id1 = mysqli_fetch_array($Cli_id0)) {
			?>
			<option value="<?=$Cli_id1['id_cli']?>"><?=$Cli_id1['cod']." - ".$Cli_id1['nombre']?></option>
			<?php
		} 
		?>
	</select>
	<script>
		$(document).ready(function() {
			$('#idCliente').select2({
				placeholder: "Selecciona o busca una opción",
				allowClear: true,
				language: "es"
			});
		});
	</script>
	<?php
}
if (isset($_POST['idProveedor'])) { /// bodegas del proveedor
	$idProveedor = $_POST['idProveedor'];
	$BodPro0 = $conn_mysql->query("SELECT * FROM direcciones where id_prov = '$idProveedor' AND status = '1'");
	?>
	<label for="Bodegas" class="form-label">Bodegas</label>
	<select class="form-select" name="bodgeProv" id="bodgeProv" required>
		<?php
		while ($BodPro1 = mysqli_fetch_array($BodPro0)) {
			$verCor = ($BodPro1['email'] == '') ? '' : '📧' ;

			?>
			<option value="<?=$BodPro1['id_direc']?>"><?=$BodPro1['cod_al']." / ".$BodPro1['noma']." ".$verCor?></option>
			<?php
		}
		?>
	</select>
	<script>
		$(document).ready(function() {
			$('#bodgeProv').select2({
				placeholder: "Selecciona o busca una opción",
				allowClear: true,
				language: "es"
			});
		});
	</script>
	<?php
}
if (isset($_POST['idCliente'])) {
	$idCliente = $_POST['idCliente'];
	$BodCli0 = $conn_mysql->query("SELECT * FROM direcciones where id_us = '$idCliente' AND status = '1'");
	?>
	<label for="BodCli" class="form-label">Entrega en</label>
	<select class="form-select" name="bodgeCli" id="bodgeCli" required>
		<?php
		while ($BodCli1 = mysqli_fetch_array($BodCli0)) {
			?>
			<option value="<?=$BodCli1['id_direc']?>"><?=$BodCli1['cod_al']." / ".$BodCli1['noma']?></option>
			<?php
		}
		?>
	</select>
	<?php
}
// NUEVA CONSULTA PARA PRECIOS DE FLETE CON LOS NUEVOS PARÁMETROS
if (isset($_POST['idFletero']) && isset($_POST['tipoFlete']) && isset($_POST['origen']) && isset($_POST['destino'])) {
	$idFletero = $_POST['idFletero'];
    $tipoFlete = $_POST['tipoFlete'];
    $origen = $_POST['origen'];
    $destino = $_POST['destino'];
    
    // Usar fecha de recolección si está disponible, sino fecha actual
    $fechaConsulta = isset($_POST['fechaRecoleccion']) ? $_POST['fechaRecoleccion'] : $fecha_actual;

    $precFl0 = $conn_mysql->query("
        SELECT p.*, 
        o.cod_al as cod_origen, o.noma as nom_origen,
        d.cod_al as cod_destino, d.noma as nom_destino
        FROM precios p
        LEFT JOIN direcciones o ON p.origen = o.id_direc
        LEFT JOIN direcciones d ON p.destino = d.id_direc
        WHERE p.id_prod = '$idFletero' 
        AND p.tipo = '$tipoFlete'
        AND p.origen = '$origen'
        AND p.destino = '$destino'
        AND p.status = '1'
        AND p.fecha_ini <= '$fechaConsulta' 
        AND p.fecha_fin >= '$fechaConsulta'
        ORDER BY p.fecha_ini DESC
        ");

	if ($precFl0 && $precFl0->num_rows > 0) {
		?>
		<label for="preFl" class="form-label">Precio flete</label>
		<select class="form-select" name="id_preFle" id="id_preFle" required>
			<?php
			while ($precFl1 = mysqli_fetch_array($precFl0)) {
				$fecha_fin_text = ($precFl1['fecha_fin'] && $precFl1['fecha_fin'] != '0000-00-00') 
				? date('d/m/Y', strtotime($precFl1['fecha_fin'])) 
				: 'Indefinido';

				$peso_minimo = $precFl1['conmin'] > 0 ? " - Mín. " . $precFl1['conmin'] . " ton" : "";
				?>
				<option value="<?=$precFl1['id_precio']?>">
					$<?=number_format($precFl1['precio'], 2)?> 
					(<?=$tipoFlete == 'FT' ? 'Por tonelada' : 'Por viaje'?><?=$peso_minimo?>)
					- Hasta: <?=$fecha_fin_text?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
	} else {
        // Si no encuentra precio exacto, mostrar opciones similares
		$precFlAlt = $conn_mysql->query("
			SELECT p.*, 
			o.cod_al as cod_origen, o.noma as nom_origen,
			d.cod_al as cod_destino, d.noma as nom_destino
			FROM precios p
			LEFT JOIN direcciones o ON p.origen = o.id_direc
			LEFT JOIN direcciones d ON p.destino = d.id_direc
			WHERE p.id_prod = '$idFletero' 
			AND p.tipo = '$tipoFlete'
			AND p.status = '1'
			AND p.fecha_ini <= '$fecha_actual' 
			AND p.fecha_fin >= '$fecha_actual'
			ORDER BY p.fecha_ini DESC
			LIMIT 5
			");

		if ($precFlAlt && $precFlAlt->num_rows > 0) {
			?>
			<label for="preFl" class="form-label">Precio flete (No se encontró para la ruta exacta)</label>
			<select class="form-select" name="id_preFle" id="id_preFle" required>
				<option disabled selected>Verifique sus datos</option>
			</select>
			<small class="text-warning">No se encontró precio para la ruta exacta</small>
			<?php
		} else {
			?>
			<label for="preFl" class="form-label">Precio flete</label>
			<input type="text" class="form-control is-invalid" value="Sin precio vigente para estos parámetros" disabled>
			<small class="text-danger">No hay precios configurados para este fletero y tipo</small>
			<?php
		}
	}
}
if (isset($_POST['idProd'])) {
	$idProd = $_POST['idProd'];
    $fechaConsulta = isset($_POST['fechaRecoleccion']) ? $_POST['fechaRecoleccion'] : $fecha_actual;

    $precPD0 = $conn_mysql->query("SELECT * FROM precios 
        WHERE id_prod = '$idProd' 
        AND tipo = 'c'
        AND status = '1'
        AND fecha_ini <= '$fechaConsulta' 
        AND fecha_fin >= '$fechaConsulta'
        ORDER BY fecha_ini DESC");

	if ($precPD0 && $precPD0->num_rows > 0) {
		?>
		<label for="prePD" class="form-label">Precio de compra</label>
		<select class="form-select" name="id_prePD" id="id_prePD" required>
			<?php
			while ($precPD1 = mysqli_fetch_array($precPD0)) {
				$fecha_fin_text = ($precPD1['fecha_fin'] && $precPD1['fecha_fin'] != '0000-00-00 00:00:00') 
				? date('d/m/Y', strtotime($precPD1['fecha_fin'])) 
				: 'Indefinido';
				?>
				<option value="<?=$precPD1['id_precio']?>">
					$<?=number_format($precPD1['precio'], 2)?> 
					(Hasta: <?=$fecha_fin_text?>)
				</option>
				<?php
			}
			?>
		</select>
		<?php
	} else {
		?>
		<label for="preFl" class="form-label">Precio de compra</label>
		<input type="text" class="form-control" value="Sin precio vigente" disabled>
		<?php
	}
}
if (isset($_POST['idProdV'])) {
    $idProdV = $_POST['idProdV'];
    $bodgeCli = $_POST['bodgeCli'];
    $fechaConsulta = isset($_POST['fechaRecoleccion']) ? $_POST['fechaRecoleccion'] : $fecha_actual;
    
    if ($bodgeCli == 'Selecciona un cliente' or empty($bodgeCli)) {
        ?>
        <label for="preFl" class="form-label">Precio de venta</label>
        <input type="text" class="form-control" value="Selecciona un cliente" disabled>
        <?php
    } else {
        $precPDV0 = $conn_mysql->query("SELECT * FROM precios 
            WHERE id_prod = '$idProdV' 
            AND tipo = 'v'
            AND status = '1'
            AND fecha_ini <= '$fechaConsulta' 
            AND fecha_fin >= '$fechaConsulta'
            AND destino = '$bodgeCli'
            ORDER BY fecha_ini DESC");

        if ($precPDV0 && $precPDV0->num_rows > 0) {
            ?>
            <label for="prePDv" class="form-label">Precio de venta</label>
            <select class="form-select" name="id_prePDv" id="id_prePDv" required>
                <?php
                while ($precPDV1 = mysqli_fetch_array($precPDV0)) {
                    $fecha_fin_textV = ($precPDV1['fecha_fin'] && $precPDV1['fecha_fin'] != '0000-00-00 00:00:00') 
                    ? date('d/m/Y', strtotime($precPDV1['fecha_fin'])) 
                    : 'Indefinido';
                    ?>
                    <option value="<?=$precPDV1['id_precio']?>">
                        $<?=number_format($precPDV1['precio'], 2)?> 
                        (Hasta: <?=$fecha_fin_textV?>)
                    </option>
                    <?php
                }
                ?>
            </select>
            <?php
        } else {
            ?>
            <label for="preFl" class="form-label">Precio de venta</label>
            <input type="text" class="form-control" value="Sin precio vigente" disabled>
            <?php
        }
    }
}


// NUEVA FUNCIONALIDAD PARA OBTENER EL TIPO DE FLETE ACTUAL
if (isset($_POST['obtenerTipoFlete'])) {
	$precioFleteId = $_POST['obtenerTipoFlete'];

	$queryTipo = "SELECT tipo FROM precios WHERE id_precio = ?";
	$stmtTipo = $conn_mysql->prepare($queryTipo);
	$stmtTipo->bind_param("i", $precioFleteId);
	$stmtTipo->execute();
	$resultTipo = $stmtTipo->get_result();

	if ($resultTipo->num_rows > 0) {
		$tipoFlete = $resultTipo->fetch_assoc();
		echo $tipoFlete['tipo'];
	} else {
        echo 'FT'; // Valor por defecto
    }
    exit;
}

// NUEVA CONSULTA PARA PRECIOS EN EDICIÓN (USA FECHA DE RECOLECCIÓN)
if (isset($_POST['idFleteroEdit']) && isset($_POST['tipoFleteEdit']) && isset($_POST['origenEdit']) && isset($_POST['destinoEdit']) && isset($_POST['fechaRecoleccion'])) {
    $idFletero = $_POST['idFleteroEdit'];
    $tipoFlete = $_POST['tipoFleteEdit'];
    $origen = $_POST['origenEdit'];
    $destino = $_POST['destinoEdit'];
    $fechaRecoleccion = $_POST['fechaRecoleccion'];
    $recoleccionId = $_POST['recoleccionId'] ?? 0;

    // Para edición, usamos la fecha de recolección en lugar de la fecha actual
    $precFl0 = $conn_mysql->query("
        SELECT p.*, 
        o.cod_al as cod_origen, o.noma as nom_origen,
        d.cod_al as cod_destino, d.noma as nom_destino
        FROM precios p
        LEFT JOIN direcciones o ON p.origen = o.id_direc
        LEFT JOIN direcciones d ON p.destino = d.id_direc
        WHERE p.id_prod = '$idFletero' 
        AND p.tipo = '$tipoFlete'
        AND p.origen = '$origen'
        AND p.destino = '$destino'
        AND p.status = '1'
        AND p.fecha_ini <= '$fechaRecoleccion' 
        AND p.fecha_fin >= '$fechaRecoleccion'
        ORDER BY p.fecha_ini DESC
        ");

    if ($precFl0 && $precFl0->num_rows > 0) {
        ?>
        <label for="preFl" class="form-label">Precio flete (vigente al <?=date('d/m/Y', strtotime($fechaRecoleccion))?>)</label>
        <select class="form-select" name="id_preFle" id="id_preFle" required>
            <?php
            while ($precFl1 = mysqli_fetch_array($precFl0)) {
                $fecha_fin_text = ($precFl1['fecha_fin'] && $precFl1['fecha_fin'] != '0000-00-00') 
                ? date('d/m/Y', strtotime($precFl1['fecha_fin'])) 
                : 'Indefinido';

                $peso_minimo = $precFl1['conmin'] > 0 ? " - Mín. " . $precFl1['conmin'] . " ton" : "";
                ?>
                <option value="<?=$precFl1['id_precio']?>">
                    $<?=number_format($precFl1['precio'], 2)?> 
                    (<?=$tipoFlete == 'FT' ? 'Por tonelada' : 'Por viaje'?><?=$peso_minimo?>)
                    - Hasta: <?=$fecha_fin_text?>
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    } else {
        // Buscar precios históricos para esa fecha
        $precFlHist = $conn_mysql->query("
            SELECT p.*, 
            o.cod_al as cod_origen, o.noma as nom_origen,
            d.cod_al as cod_destino, d.noma as nom_destino
            FROM precios p
            LEFT JOIN direcciones o ON p.origen = o.id_direc
            LEFT JOIN direcciones d ON p.destino = d.id_direc
            WHERE p.id_prod = '$idFletero' 
            AND p.tipo = '$tipoFlete'
            AND p.origen = '$origen'
            AND p.destino = '$destino'
            AND p.status = '1'
            AND p.fecha_ini <= '$fechaRecoleccion' 
            ORDER BY p.fecha_ini DESC
            LIMIT 1
            ");

        if ($precFlHist && $precFlHist->num_rows > 0) {
            $precFl1 = mysqli_fetch_array($precFlHist);
            ?>
            <label for="preFl" class="form-label">Precio flete (histórico - vigente al <?=date('d/m/Y', strtotime($fechaRecoleccion))?>)</label>
            <select class="form-select" name="id_preFle" id="id_preFle" required>
                <option value="<?=$precFl1['id_precio']?>">
                    $<?=number_format($precFl1['precio'], 2)?> 
                    (<?=$tipoFlete == 'FT' ? 'Por tonelada' : 'Por viaje'?>)
                    - Vigente hasta: <?=date('d/m/Y', strtotime($precFl1['fecha_fin']))?>
                </option>
            </select>
            <small class="text-warning">Precio histórico (ya no vigente)</small>
            <?php
        } else {
            ?>
            <label for="preFl" class="form-label">Precio flete</label>
            <input type="text" class="form-control" value="Sin precio para la fecha de recolección" disabled>
            <small class="text-danger">No hay precios configurados para esta fecha</small>
            <?php
        }
    }
}
// CONSULTAS SIMILARES PARA PRECIOS DE COMPRA Y VENTA EN EDICIÓN
if (isset($_POST['idProdEdit'])) {
    $idProd = $_POST['idProdEdit'];
    $fechaRecoleccion = $_POST['fechaRecoleccion'] ?? $fecha_actual;

    $precPD0 = $conn_mysql->query("SELECT * FROM precios 
        WHERE id_prod = '$idProd' 
        AND tipo = 'c' 
        AND fecha_ini <= '$fechaRecoleccion' 
        AND fecha_fin >= '$fechaRecoleccion'
        ORDER BY fecha_ini DESC");

    if ($precPD0 && $precPD0->num_rows > 0) {
        ?>
        <label for="prePD" class="form-label">Precio de compra (vigente al <?=date('d/m/Y', strtotime($fechaRecoleccion))?>)</label>
        <select class="form-select" name="id_prePD" id="id_prePD" required>
            <?php
            while ($precPD1 = mysqli_fetch_array($precPD0)) {
                $fecha_fin_text = ($precPD1['fecha_fin'] && $precPD1['fecha_fin'] != '0000-00-00 00:00:00') 
                ? date('d/m/Y', strtotime($precPD1['fecha_fin'])) 
                : 'Indefinido';
                ?>
                <option value="<?=$precPD1['id_precio']?>">
                    $<?=number_format($precPD1['precio'], 2)?> 
                    (Hasta: <?=$fecha_fin_text?>)
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    } else {
        // Buscar precio histórico
        $precPDHist = $conn_mysql->query("SELECT * FROM precios 
            WHERE id_prod = '$idProd' 
            AND tipo = 'c' 
            AND fecha_ini <= '$fechaRecoleccion'
            ORDER BY fecha_ini DESC
            LIMIT 1");

        if ($precPDHist && $precPDHist->num_rows > 0) {
            $precPD1 = mysqli_fetch_array($precPDHist);
            ?>
            <label for="prePD" class="form-label">Precio de compra (histórico)</label>
            <select class="form-select" name="id_prePD" id="id_prePD" required>
                <option value="<?=$precPD1['id_precio']?>">
                    $<?=number_format($precPD1['precio'], 2)?> 
                    (Vigente hasta: <?=date('d/m/Y', strtotime($precPD1['fecha_fin']))?>)
                </option>
            </select>
            <small class="text-warning">Precio histórico</small>
            <?php
        } else {
            ?>
            <label for="preFl" class="form-label">Precio de compra</label>
            <input type="text" class="form-control" value="Sin precio vigente" disabled>
            <?php
        }
    }
}

if (isset($_POST['idProdVEdit'])) {
    $idProdV = $_POST['idProdVEdit'];
    $bodgeCli = $_POST['bodgeCli'];
    $fechaRecoleccion = $_POST['fechaRecoleccion'] ?? $fecha_actual;
    
    if ($bodgeCli == 'Selecciona un cliente' or empty($bodgeCli)) {
        ?>
        <label for="preFl" class="form-label">Precio de venta</label>
        <input type="text" class="form-control" value="Selecciona un cliente" disabled>
        <?php
    } else {
        $precPDV0 = $conn_mysql->query("SELECT * FROM precios 
            WHERE id_prod = '$idProdV' 
            AND tipo = 'v' 
            AND fecha_ini <= '$fechaRecoleccion' 
            AND fecha_fin >= '$fechaRecoleccion'
            AND destino = '$bodgeCli'
            ORDER BY fecha_ini DESC");

        if ($precPDV0 && $precPDV0->num_rows > 0) {
            ?>
            <label for="prePDv" class="form-label">Precio de venta (vigente al <?=date('d/m/Y', strtotime($fechaRecoleccion))?>)</label>
            <select class="form-select" name="id_prePDv" id="id_prePDv" required>
                <?php
                while ($precPDV1 = mysqli_fetch_array($precPDV0)) {
                    $fecha_fin_textV = ($precPDV1['fecha_fin'] && $precPDV1['fecha_fin'] != '0000-00-00 00:00:00') 
                    ? date('d/m/Y', strtotime($precPDV1['fecha_fin'])) 
                    : 'Indefinido';
                    ?>
                    <option value="<?=$precPDV1['id_precio']?>">
                        $<?=number_format($precPDV1['precio'], 2)?> 
                        (Hasta: <?=$fecha_fin_textV?>)
                    </option>
                    <?php
                }
                ?>
            </select>
            <?php
        } else {
            // Buscar precio histórico
            $precPDVHist = $conn_mysql->query("SELECT * FROM precios 
                WHERE id_prod = '$idProdV' 
                AND tipo = 'v' 
                AND destino = '$bodgeCli'
                AND fecha_ini <= '$fechaRecoleccion'
                ORDER BY fecha_ini DESC
                LIMIT 1");

            if ($precPDVHist && $precPDVHist->num_rows > 0) {
                $precPDV1 = mysqli_fetch_array($precPDVHist);
                ?>
                <label for="prePDv" class="form-label">Precio de venta (histórico)</label>
                <select class="form-select" name="id_prePDv" id="id_prePDv" required>
                    <option value="<?=$precPDV1['id_precio']?>">
                        $<?=number_format($precPDV1['precio'], 2)?> 
                        (Vigente hasta: <?=date('d/m/Y', strtotime($precPDV1['fecha_fin']))?>)
                    </option>
                </select>
                <small class="text-warning">Precio histórico</small>
                <?php
            } else {
                ?>
                <label for="preFl" class="form-label">Precio de venta</label>
                <input type="text" class="form-control" value="Sin precio vigente" disabled>
                <?php
            }
        }
    }
}

?> 
