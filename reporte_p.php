<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión
$tp_mv = $_POST['tp_mv'];// tipo de vista ventas,  compras, todas = las dos
$dt_mvI = $_POST['dt_mvI'];// fecha de inicio Y-m-d
$dt_mvF = $_POST['dt_mvF'];// fecha final
$id_bodega = $_POST['id_bodega'];// bodega id
?>
<div class="table-responsive">
	<table class="table table-striped table-bordered" id="miTabla" style="width:100%">
		<thead>
			<tr>
				<th>#</th>
				<th data-priority="1">Nombre</th>
				<th>Producto</th>
				<th>Codigo </th>
				<th>Peso</th>
				<th>Precio</th>
				<th>Fecha</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$cont = 0;
			if ($tp_mv == 'todas') {
				?>
				<tr>
					<td>NS</td>
					<td>NS</td>
					<td>NS</td>
					<td>NS</td>
					<td>NS</td>
					<td>NS</td>
					<td>NS</td>
				</tr>
				<?php
			}else{
				$vc0 = $conn_mysql->query("SELECT * FROM $tp_mv WHERE  status = '1' AND id_direc = $id_bodega AND fecha BETWEEN '$dt_mvI' AND '$dt_mvF'");
				while ($vc1 = mysqli_fetch_array($vc0)) {
					$cont++;
					$Prod0 = $conn_mysql->query("SELECT * FROM productos WHERE id_prod = '".$vc1['id_prod']."'");
					$Prod1 = mysqli_fetch_array($Prod0);
					$peso = (isset($vc1['peso_cliente'])) ? $vc1['peso_cliente'] : $vc1['neto'] ;
					$precio = (isset($vc1['precio'])) ? $vc1['precio'] : $vc1['pres'] ;
					$fecha = date('Y-m-d', strtotime($vc1['fecha']));
					$id = (isset($vc1['id_venta'])) ? $vc1['id_venta'] : $vc1['id_compra'] ;
					$link = ($tp_mv == 'ventas') ? 'V_venta' : 'V_compra' ;
					?>
					<tr>
						<td><?= $cont?></td>
						<td><a href="?p=<?=$link?>&id=<?=$id?>" target="_blank" class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">
							<?= htmlspecialchars($vc1['nombre'] ?? '') ?>
							</a>
						</td>
						<td><?= $Prod1['nom_pro']?></td>
						<td><?= $Prod1['cod']?></td>
						<td><?= number_format($peso, 2,'.')?>kg</td>
						<td>$<?= number_format($precio, 2,'.')?></td>
						<td><?=$fecha?></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
</div>
<script>
	$(document).ready(function() {
		$('#miTabla').DataTable({
			"language": {
				"url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
			},
			"responsive": true
		});
	});
</script>