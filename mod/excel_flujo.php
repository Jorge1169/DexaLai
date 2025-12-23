<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Datos Completos del Archivo CSV</h5>
        <div class="card-body">
            <?php
            if (!isset($_FILES['dataCliente'])) {
            } else {

                $tipo = $_FILES['dataCliente']['type'];
                $tamanio = $_FILES['dataCliente']['size'];
                $archivotmp = $_FILES['dataCliente']['tmp_name'];
                $lineas = file($archivotmp);
                $cantidad_regist_agregados = (count($lineas) - 1);
                
                // Guardar el contenido del CSV en una variable de sesión para procesarlo después
                $_SESSION['csv_data'] = file_get_contents($archivotmp);
                $_SESSION['csv_filename'] = $_FILES['dataCliente']['name'];
                
                // Información del archivo
                echo '<div class="alert alert-info mb-4">';
                echo '<h6><i class="fas fa-file-csv"></i> Información del archivo subido:</h6>';
                echo '<ul class="mb-0">';
                echo '<li><strong>Nombre:</strong> '.htmlspecialchars($_FILES['dataCliente']['name']).'</li>';
                echo '<li><strong>Tipo:</strong> '.htmlspecialchars($tipo).'</li>';
                echo '<li><strong>Tamaño:</strong> '.number_format($tamanio/1024, 2).' KB</li>';
                echo '<li><strong>Registros:</strong> '.$cantidad_regist_agregados.'</li>';
                echo '</ul>';
                echo '</div>';
                
                if($cantidad_regist_agregados > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-bordered table-striped table-hover">';
                    echo '<thead class="thead-dark">';
                    echo '<tr>';
                    echo '<th>#</th>';
                    echo '<th>Remisión C</th>';
                    echo '<th>Fecha Compra</th>';
                    echo '<th>Cód. Prov.</th>';
                    echo '<th>Producto</th>';
                    echo '<th>ID fletero</th>';
                    echo '<th>Tara</th>';
                    echo '<th>Bruto</th>';
                    echo '<th>Neto</th>';
                    echo '<th>Precio C</th>';
                    echo '<th>Total C</th>';
                    echo '<th>Remisión V</th>';
                    echo '<th>Factura Venta</th>';
                    echo '<th>Fecha Venta</th>';
                    echo '<th>Cód. Cliente</th>';
                    echo '<th>Cant. Venta</th>';
                    echo '<th>Precio V</th>';
                    echo '<th>Total V</th>';
                    echo '<th>Precio Flete</th>';
                    echo '<th>Zona</th>';
                    echo '<th>Estado</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    $i = 0;
                    foreach ($lineas as $linea) {
                        if ($i != 0) {
                            $datos = explode(",", $linea);

                            // Limpieza y formateo de datos
                            $remC     = !empty($datos[0]) ? htmlspecialchars(trim($datos[0])): '-';
                            $fech_c   = !empty($datos[1]) ? convertirFecha($datos[1]) : '0000-00-00';
                            $cod_p    = !empty($datos[2]) ? htmlspecialchars(trim($datos[2])) : '-';
                            $producto = !empty($datos[3]) ? htmlspecialchars(trim($datos[3])) : '-';
                            $placa_t  = !empty($datos[4]) ? htmlspecialchars(trim($datos[4])) : '-';
                            $tara     = !empty($datos[5]) ? trim($datos[5]) : '0.00';
                            $bruto    = !empty($datos[6]) ? trim($datos[6]) : '0.00';
                            $neto     = !empty($datos[7]) ? trim($datos[7]) : '0.00';
                            $pre_c    = !empty($datos[8]) ? trim($datos[8]) : '0.00';
                            $t_comp   = !empty($datos[9]) ? trim($datos[9]) : '0.00';
                            $remV     = !empty($datos[10]) ? htmlspecialchars(trim($datos[10])) : '-';
                            $facV     = !empty($datos[11]) ? htmlspecialchars(trim($datos[11])) : '';
                            $fech_v   = !empty($datos[12]) ? convertirFecha($datos[12]) : '0000-00-00';
                            $cod_c    = !empty($datos[13]) ? htmlspecialchars(trim($datos[13])) : '-';
                            $cant_v   = !empty($datos[14]) ? trim($datos[14]) : '0.00';
                            $pre_v    = !empty($datos[15]) ? trim($datos[15]) : '0.00';
                            $tot_v    = !empty($datos[16]) ? trim($datos[16]) : '0.00';
                            $pre_fle  = !empty($datos[17]) ? trim($datos[17]) : '0.00';

                            // Validaciones (solo para mostrar estado, no ejecutar acciones)
                            $BuC0 = $conn_mysql->query("SELECT * FROM compras WHERE status = '1' AND fact = '$remC'");
                            $BuC1 = mysqli_fetch_array($BuC0);
                            $remC0 = (empty($BuC1['id_compra'])) ? 0 : 1;

                            $BuV0 = $conn_mysql->query("SELECT * FROM ventas WHERE status = '1' AND fact = '$remC'");
                            $BuV1 = mysqli_fetch_array($BuV0);
                            $remV0 = (empty($BuV1['id_venta'])) ? 0 : 1;

                            $BuDP0 = $conn_mysql->query("SELECT * FROM direcciones WHERE status = '1' AND cod_al = '$cod_p'");
                            $BuDP1 = mysqli_fetch_array($BuDP0);
                            $cod_p0 = (empty($BuDP1['id_direc'])) ? 0 : $BuDP1['id_direc'];
                            $id_prov = (empty($BuDP1['id_direc'])) ? 0 : $BuDP1['id_prov'];

                            $BuP0 = $conn_mysql->query("SELECT * FROM productos WHERE status = '1' AND nom_pro = '$producto'");
                            $BuP1 = mysqli_fetch_array($BuP0);
                            $producto0 = (empty($BuP1['id_prod'])) ? 0 : $BuP1['id_prod'];
                            $zona = (empty($BuP1['id_prod'])) ? 0 : $BuP1['zona'];

                            $BuCli0 = $conn_mysql->query("SELECT * FROM clientes WHERE status = '1' AND cod = '$cod_c'");
                            $BuCli1 = mysqli_fetch_array($BuCli0);
                            $cod_c0 = (empty($BuCli1['id_cli'])) ? 0 : $BuCli1['id_cli'];
                            $BuDirC0 = $conn_mysql->query("SELECT * FROM direcciones WHERE status = '1' AND id_us = '$cod_c0'");
                            $BuDirC1 = mysqli_fetch_array($BuDirC0);
                            $DirCli1 = (empty($BuDirC1['id_direc'])) ? 0 : $BuDirC1['id_direc'];

                            $BuPL0 = $conn_mysql->query("SELECT * FROM transportes WHERE status = '1' AND placas = '$placa_t'");
                            $BuPL1 = mysqli_fetch_array($BuPL0);
                            $placa_t0 = (empty($BuPL1['id_transp'])) ? 0 : $BuPL1['id_transp'];

                            // Determinar colores según validaciones
                            $retVal0 = ($remC0 == 0 ) ? 'success' : 'danger' ;
                            $retVal1 = ($remV0 == 0 ) ? 'success' : 'danger' ;
                            $retVal2 = ($cod_p0 != 0) ? 'success' : 'danger' ;
                            $retVal3 = ($producto0 != 0 ) ? 'success' : 'danger' ;
                            $retVal4 = ($placa_t0 != 0 ) ? 'success' : 'danger' ;
                            $retVal5 = ($cod_c0 != 0 ) ? 'success' : 'danger' ;

                            // Determinar estado del registro
                            $estado = '';
                            if ($remC0 == 0 && $remV0 == 0 && $cod_p0 != 0 && $producto0 != 0 && $cod_c0 != 0 && $placa_t0 != 0) {
                                $estado = 'Válido';
                            } else {
                                $estado = 'No se puede subir';
                            }

                            echo '<tr class="table-'.$retVal0.'">';
                            echo '<td>'.$i.'</td>';
                            echo '<td class="text-'.$retVal0.'">'.$remC.'</td>';
                            echo '<td>'.$fech_c.'</td>';
                            echo '<td class="text-'.$retVal2.'">'.$cod_p.'</td>';
                            echo '<td class="text-'.$retVal3.'">'.$producto.'</td>';
                            echo '<td class="text-'.$retVal4.'">'.$placa_t.'</td>';
                            echo '<td class="text-right">'.number_format($tara, 2).'</td>';
                            echo '<td class="text-right">'.number_format($bruto, 2).'</td>';
                            echo '<td class="text-right">'.number_format($neto, 2).'</td>';
                            echo '<td class="text-right">$'.number_format($pre_c, 2).'</td>';
                            echo '<td class="text-right font-weight-bold">$'.number_format($t_comp, 2).'</td>';
                            echo '<td class="text-'.$retVal1.'">'.$remV.'</td>';
                            echo '<td>'.$facV.'</td>';
                            echo '<td>'.$fech_v.'</td>';
                            echo '<td class="text-'.$retVal5.'">'.$cod_c.'</td>';
                            echo '<td class="text-right">'.number_format($cant_v, 2).'</td>';
                            echo '<td class="text-right">$'.number_format($pre_v, 2).'</td>';
                            echo '<td class="text-right font-weight-bold">$'.number_format($tot_v, 2).'</td>';
                            echo '<td class="text-right">$'.number_format($pre_fle, 2).'</td>';
                            echo '<td>'.$zona.'</td>';
                            echo '<td>'.$estado.'</td>';
                            echo '</tr>';
                        }
                        $i++;
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';

                    // Botones de acción
                    echo '<div class="mt-4 text-end">';
                    echo '<form method="post" action="?p=procesar_csv" style="display: inline-block;">';
                    echo '<input type="hidden" name="accion" value="cancelar">';
                    echo '<button type="submit" class="btn btn-warning me-2">';
                    echo '<i class="fas fa-times"></i> Cancelar';
                    echo '</button>';
                    echo '</form>';

                    echo '<form method="post" action="?p=procesar_csv" style="display: inline-block;">';
                    echo '<input type="hidden" name="accion" value="procesar">';
                    echo '<button type="submit" class="btn btn-teal">';
                    echo '<i class="fas fa-check"></i> Procesar Archivo';
                    echo '</button>';
                    echo '</form>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning">No se encontraron registros válidos en el archivo CSV.</div>';
                }
            }
            ?>
        </div>
    </div>
</div>
<!-- Estilos adicionales -->
<style>
	.table th {
		white-space: nowrap;
		vertical-align: middle;
		font-size: 0.85rem;
	}
	.table td {
		font-size: 0.82rem;
		vertical-align: middle;
	}
	.table thead th {
		background-color: #343a40;
		color: white;
	}
	@media (max-width: 768px) {
		.table-responsive {
			border: 0;
		}
	}
</style>