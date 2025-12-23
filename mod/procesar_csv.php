<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] == 'procesar' && isset($_SESSION['csv_data'])) {
            // Procesar el archivo CSV
            $lineas = explode("\n", $_SESSION['csv_data']);
            $i = 0;
            foreach ($lineas as $linea) {
                if ($i != 0 && !empty(trim($linea))) {
                    $datos = explode(",", $linea);
                    
                    // Limpieza y formateo de datos (igual que en el código anterior)
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
                    // ... (repetir para todos los campos)
                    
                    // Validaciones y procesamiento (igual que en tu código original)
                    $BuC0 = $conn_mysql->query("SELECT * FROM compras WHERE status = '1' AND fact = '$remC'");
                    $BuC1 = mysqli_fetch_array($BuC0);
                            $remC0 = (empty($BuC1['id_compra'])) ? 0 : 1; //Buscamos si existe la compra, si es 0 se procede a subir porque no existe y es 1 se cancela su subida a la base

                            $BuV0 = $conn_mysql->query("SELECT * FROM ventas WHERE status = '1' AND fact = '$remC'");
                            $BuV1 = mysqli_fetch_array($BuV0);
                            $remV0 = (empty($BuV1['id_venta'])) ? 0 : 1; //Buscamos si existe la Venta, si es 0 se procede a subir porque no existe y es 1 se cancela su subida a la base

                            $BuDP0 = $conn_mysql->query("SELECT * FROM direcciones WHERE status = '1' AND cod_al = '$cod_p'");
                            $BuDP1 = mysqli_fetch_array($BuDP0);
                            $cod_p0 = (empty($BuDP1['id_direc'])) ? 0 : $BuDP1['id_direc']; // Buscamos la direccion si existe tomamos su id, si no existe y es 0 se cancela la subida a la base
                            $id_prov = (empty($BuDP1['id_direc'])) ? 0 : $BuDP1['id_prov']; // baciamos el id del proveedor que esta en la direccion

                            $BuP0 = $conn_mysql->query("SELECT * FROM productos WHERE status = '1' AND nom_pro = '$producto'");
                            $BuP1 = mysqli_fetch_array($BuP0);
                            $producto0 = (empty($BuP1['id_prod'])) ? 0 : $BuP1['id_prod']; // Buscamos el producto si existe tomamos su id, si no existe y es 0 se cancela la subida a la base
                            $zona = (empty($BuP1['id_prod'])) ? 0 : $BuP1['zona'] ;

                            $BuCli0 = $conn_mysql->query("SELECT * FROM clientes WHERE status = '1' AND cod = '$cod_c'");
                            $BuCli1 = mysqli_fetch_array($BuCli0);
                            $cod_c0 = (empty($BuCli1['id_cli'])) ? 0 : $BuCli1['id_cli'];  // Buscamos el cliente si existe tomamos su id, si no existe y es 0 se cancela la subida a la base
                            $BuDirC0 = $conn_mysql->query("SELECT * FROM direcciones WHERE status = '1' AND id_us = '$cod_c0'");
                            $BuDirC1 = mysqli_fetch_array($BuDirC0);
                            $DirCli1 = (empty($BuDirC1['id_direc'])) ? 0 : $BuDirC1['id_direc'];

                            $BuPL0 = $conn_mysql->query("SELECT * FROM transportes WHERE status = '1' AND placas = '$placa_t'");
                            $BuPL1 = mysqli_fetch_array($BuPL0);
                            $placa_t0 = (empty($BuPL1['id_transp'])) ? 0 : $BuPL1['id_transp']; // Buscamos el transporte si existe tomamos su id, si no existe y es 0 se cancela la subida a la base
                    // ... (resto de validaciones)
                            
                            if ($remC0 == 0 AND $remV0 == 0 AND $cod_p0 != 0 AND $producto0 != 0 AND $cod_c0 != 0 AND $placa_t0 != 0) {

                                $conn_mysql->query("INSERT INTO compras (fact,nombre,id_prov,id_direc,id_transp,id_prod,tara,bruto,neto,pres,fecha,id_user,status,zona,ex) VALUES ('$remC','$remC','$id_prov','$cod_p0','$placa_t0','$producto0','$tara','$bruto','$neto','$pre_c','$fech_c','$idUser','1','$zona','2')");
                                $id_compra = $conn_mysql->insert_id;
                                $conn_mysql->query("INSERT almacen (id_compra, id_prod, entrada, id_user, status,zona) VALUES ('$id_compra','$producto0','$neto','$idUser','1','$zona')");

                                $conn_mysql->query("INSERT ventas (fact, nombre, factura, id_cli, id_direc, id_compra, id_prod, costo_flete, flete, peso_cliente, precio, fecha, id_user, status, zona, ex) VALUES ('$remV','$remV', '$facV','$cod_c0','$DirCli1','$id_compra','$producto0','$pre_fle','$placa_t0','$cant_v','$pre_v','$fech_v','$idUser','1','$zona','2')");
                                $id_venta = $conn_mysql->insert_id;

                                $conn_mysql->query("INSERT almacen (id_venta, id_prod, salida, id_user, status,zona) VALUES ('$id_venta','$producto0','$cant_v','$idUser','1','$zona')");

                            }
                        }
                        $i++;
                    }
                    
            // Limpiar sesión y mostrar mensaje de éxito
                    unset($_SESSION['csv_data']);
                    unset($_SESSION['csv_filename']);
                    alert("Archivo procesado correctamente", 1, "masiva_flujo");
                    exit();
                    
                } elseif ($_POST['accion'] == 'cancelar') {
            // Limpiar sesión y redirigir
                    unset($_SESSION['csv_data']);
                    unset($_SESSION['csv_filename']);
                    alert("Proceso cancelado", 2, "masiva_flujo");
                    exit();
                }
            }
        }

// Si llega aquí sin procesar, redirigir
        alert("Regresar", 0, "masiva_flujo");
        exit();
    ?>