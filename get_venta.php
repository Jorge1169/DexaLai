<?php
// get_venta.php - Funciones AJAX para el módulo de ventas
require_once 'config/conexiones.php';

$accion = $_POST['accion'] ?? '';

// 1. Generación de folio para venta
if ($accion == 'folio_venta' && isset($_POST['zona']) && isset($_POST['fecha_venta'])) {
    $zonaId = $_POST['zona']; 
    $fechaVenta = $_POST['fecha_venta'];
    
    $z_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zonaId'");
    $z_s1 = mysqli_fetch_array($z_s0);
    
    $anio_seleccionado = date('Y', strtotime($fechaVenta));
    $mes_seleccionado = date('m', strtotime($fechaVenta));
    
    $qry = "SELECT folio FROM ventas WHERE status = '1' 
    AND YEAR(fecha_venta) = '$anio_seleccionado' 
    AND MONTH(fecha_venta) = '$mes_seleccionado' 
    AND zona = '".$z_s1['id_zone']."'
    ORDER BY folio DESC 
    LIMIT 1";
    
    $Rc00 = $conn_mysql->query($qry);
    
    $fe = date('ym', strtotime($fechaVenta));
    
    if ($Rc00 && $Rc00->num_rows > 0) {
        $Rc01 = $Rc00->fetch_assoc();
        $u_folio = intval($Rc01['folio']);
        $nuevo_n = $u_folio + 1;
        
        if ($nuevo_n > 9999) {
            $fol = 'ERROR: Límite alcanzado';
        } else {
            $fol = str_pad($nuevo_n, 4, '0', STR_PAD_LEFT);
        }
    } else {
        $fol = '0001';
    }
    
    $zona_tipo = strtoupper(trim($z_s1['tipo'] ?? ''));
    $prefijo_folio = ($zona_tipo === 'SUR') ? 'E' : 'V';
    $folM = $prefijo_folio."-".$z_s1['cod']."-".$fe.$fol;
    
    ?>
    <label for="folio" class="form-label">Folio</label>
    <input type="text" id="folio01" class="form-control" value="<?=$folM?>" disabled>
    <input type="hidden" name="folio" value="<?=$fol?>">
    <?php
    exit;
}

// 2. Cargar almacenes por zona para venta
if ($accion == 'almacenes_venta' && isset($_POST['zonaAlmacen'])) {
    $zonaId0 = $_POST['zonaAlmacen'];
    ?>
    <label for="idAlmacen" class="form-label">Almacén</label>
    <select class="form-select" name="idAlmacen" id="idAlmacen" onchange="cargarBodegasAlmacenVenta()" required>
        <option selected disabled value="">Selecciona un almacén...</option>
        <?php
        $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$zonaId0'");
        while ($Alm_id1 = mysqli_fetch_array($Alm_id0)) {
            ?>
            <option value="<?=$Alm_id1['id_alma']?>"><?=$Alm_id1['cod']." - ".$Alm_id1['nombre']?></option>
            <?php
        } 
        ?>
    </select>
    <?php
    exit;
}

// 3. Cargar clientes por zona
if ($accion == 'clientes_venta' && isset($_POST['zonaCliente'])) {
    $zonaId0 = $_POST['zonaCliente'];
    ?>
    <label for="idCliente" class="form-label">Cliente</label>
    <select class="form-select" name="idCliente" id="idCliente" onchange="cargarBodegasCliente()" required>
        <option selected disabled value="">Selecciona un cliente...</option>
        <?php
        $Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '$zonaId0'");
        while ($Cli_id1 = mysqli_fetch_array($Cli_id0)) {
            ?>
            <option value="<?=$Cli_id1['id_cli']?>"><?=$Cli_id1['cod']." / ".$Cli_id1['nombre']?></option>
            <?php
        } 
        ?>
    </select>
    <?php
    exit;
}

// 4. Cargar fleteros por zona
if ($accion == 'fleteros_venta' && isset($_POST['zonaFletero'])) {
    $zonaId0 = $_POST['zonaFletero'];
    ?>
    <label for="idFletero" class="form-label">Fletero</label>
    <select class="form-select" name="idFletero" id="idFletero" onchange="cargarPrecioFleteVenta()" required>
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
    <?php
    exit;
}

// 5. Cargar productos por zona
if ($accion == 'productos_venta' && isset($_POST['zonaProducto'])) {
    $zonaId0 = $_POST['zonaProducto'];
    ?>
    <label for="id_producto" class="form-label">Producto</label>
    <select class="form-select" name="id_producto" id="id_producto" onchange="cargarPrecioVentaYStock()" required>
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
    <?php
    exit;
}

// 6. Bodegas del almacén para venta
if ($accion == 'bodegas_almacen_venta' && isset($_POST['idAlmacen'])) {
    $idAlmacen = $_POST['idAlmacen'];
    $BodAlm0 = $conn_mysql->query("SELECT * FROM direcciones where id_alma = '$idAlmacen' AND status = '1'");
    ?>
    <label for="bodgeAlm" class="form-label">Bodega del Almacén</label>
    <select class="form-select" name="bodgeAlm" id="bodgeAlm" required>
        <?php
        if ($BodAlm0 && $BodAlm0->num_rows > 0) {
            while ($BodAlm1 = mysqli_fetch_array($BodAlm0)) {
                ?>
                <option value="<?=$BodAlm1['id_direc']?>"><?=$BodAlm1['cod_al']." / ".$BodAlm1['noma']?></option>
                <?php
            }
        } else {
            ?>
            <option value="" disabled>No hay bodegas registradas</option>
            <?php
        }
        ?>
    </select>
    <?php
    exit;
}

// 7. Bodegas del cliente
if ($accion == 'bodegas_cliente' && isset($_POST['idCliente'])) {
    $idCliente = $_POST['idCliente'];
    $BodCli0 = $conn_mysql->query("SELECT * FROM direcciones where id_us = '$idCliente' AND status = '1'");
    ?>
    <label for="bodgeCli" class="form-label">Bodega del Cliente</label>
    <select class="form-select" name="bodgeCli" id="bodgeCli" required>
        <?php
        if ($BodCli0 && $BodCli0->num_rows > 0) {
            while ($BodCli1 = mysqli_fetch_array($BodCli0)) {
                $verCor = ($BodCli1['email'] == '') ? '' : '📧' ;
                ?>
                <option value="<?=$BodCli1['id_direc']?>"><?=$BodCli1['cod_al']." / ".$BodCli1['noma']." ".$verCor?></option>
                <?php
            }
        } else {
            ?>
            <option value="" disabled>No hay bodegas registradas</option>
            <?php
        }
        ?>
    </select>
    <?php
    exit;
}

// 8. Precios de flete para venta MEO
if ($accion == 'precio_flete_venta' && isset($_POST['idFletero']) && isset($_POST['tipoFlete'])) {
    $idFletero = $_POST['idFletero'];
    $tipoFlete = $_POST['tipoFlete'];
    $origen = $_POST['origen'] ?? 0;
    $destino = $_POST['destino'] ?? 0;
    $fechaConsulta = $_POST['fechaVenta'] ?? date('Y-m-d');
    $cap_ven = $_POST['cap_ven'] ?? 'VEN';
    
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
        AND p.cap_ven = '$cap_ven'
        AND p.status = '1'
        AND p.fecha_ini <= '$fechaConsulta' 
        AND p.fecha_fin >= '$fechaConsulta'
        ORDER BY p.fecha_ini DESC
        ");
    
    if ($precFl0 && $precFl0->num_rows > 0) {
        ?>
        <label for="id_preFle" class="form-label">Precio flete</label>
        <select class="form-select" name="id_preFle" id="id_preFle" required>
            <?php
            while ($precFl1 = mysqli_fetch_array($precFl0)) {
                $fecha_fin_text = ($precFl1['fecha_fin'] && $precFl1['fecha_fin'] != '0000-00-00') 
                ? date('d/m/Y', strtotime($precFl1['fecha_fin'])) 
                : 'Indefinido';
                
                $peso_minimo = $precFl1['conmin'] > 0 ? " - Mín. " . $precFl1['conmin'] . " ton" : "";
                $tipo_texto = ($tipoFlete == 'MFT') ? 'Por tonelada' : 'Por viaje';
                ?>
                <option value="<?=$precFl1['id_precio']?>">
                    $<?=number_format($precFl1['precio'], 2)?> 
                    (<?=$tipo_texto?><?=$peso_minimo?>)
                    - Hasta: <?=$fecha_fin_text?>
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    } else {
        ?>
        <label for="id_preFle" class="form-label">Precio flete</label>
        <input type="text" class="form-control is-invalid" value="Sin precio vigente para esta ruta" disabled>
        <small class="text-danger">Configure el precio en el módulo del fletero</small>
        <?php
    }
    exit;
}

// 9. Precios de servicio para venta/entrega SUR
if ($accion == 'precio_servicio_venta' && isset($_POST['idAlmacen']) && isset($_POST['tipoServicio'])) {
    $idAlmacen = $_POST['idAlmacen'];
    $tipoServicio = $_POST['tipoServicio'];
    $origen = $_POST['origen'] ?? 0;
    $destino = $_POST['destino'] ?? 0;
    $fechaConsulta = $_POST['fechaVenta'] ?? date('Y-m-d');

    if (!in_array($tipoServicio, ['SVT', 'SVV'], true)) {
        ?>
        <label for="id_preSer" class="form-label">Precio de servicio</label>
        <input type="text" class="form-control is-invalid" value="Tipo de servicio inválido" disabled>
        <?php
        exit;
    }

    $precSer0 = $conn_mysql->query("
        SELECT p.*, 
        o.cod_al as cod_origen, o.noma as nom_origen,
        d.cod_al as cod_destino, d.noma as nom_destino
        FROM precios p
        LEFT JOIN direcciones o ON p.origen = o.id_direc
        LEFT JOIN direcciones d ON p.destino = d.id_direc
        WHERE p.id_prod = '$idAlmacen'
        AND p.tipo = '$tipoServicio'
        AND p.origen = '$origen'
        AND p.destino = '$destino'
        AND p.cap_ven = 'VEN'
        AND p.status = '1'
        AND p.fecha_ini <= '$fechaConsulta'
        AND p.fecha_fin >= '$fechaConsulta'
        ORDER BY p.fecha_ini DESC
    ");

    if ($precSer0 && $precSer0->num_rows > 0) {
        ?>
        <label for="id_preSer" class="form-label">Precio de servicio</label>
        <select class="form-select" name="id_preSer" id="id_preSer" required>
            <?php
            while ($precSer1 = mysqli_fetch_array($precSer0)) {
                $fecha_fin_text = ($precSer1['fecha_fin'] && $precSer1['fecha_fin'] != '0000-00-00')
                ? date('d/m/Y', strtotime($precSer1['fecha_fin']))
                : 'Indefinido';

                $peso_minimo = $precSer1['conmin'] > 0 ? " - Mín. " . $precSer1['conmin'] . " ton" : "";
                $tipo_texto = ($tipoServicio == 'SVT') ? 'Servicio por tonelada' : 'Servicio por viaje';
                ?>
                <option value="<?=$precSer1['id_precio']?>">
                    $<?=number_format($precSer1['precio'], 2)?>
                    (<?=$tipo_texto?><?=$peso_minimo?>)
                    - Hasta: <?=$fecha_fin_text?>
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    } else {
        ?>
        <label for="id_preSer" class="form-label">Precio de servicio</label>
        <input type="text" class="form-control is-invalid" value="Sin precio vigente para esta ruta" disabled>
        <small class="text-danger">Configure el precio de servicio en el almacén</small>
        <?php
    }
    exit;
}

// 10. Precio de venta del producto - VERSIÓN MEJORADA CON DEBUG
if ($accion == 'precio_venta' && isset($_POST['idProd'])) {
    $idProd = $_POST['idProd'];
    $fechaConsulta = $_POST['fechaVenta'] ?? date('Y-m-d');
    $idCliente = $_POST['idCliente'] ?? 0;
    $idBodegaCliente = $_POST['idBodegaCliente'] ?? 0;
    
    // DEBUG: Registrar qué datos estamos recibiendo
    //error_log("DEBUG precio_venta: idProd=$idProd, fecha=$fechaConsulta, cliente=$idCliente, bodega=$idBodegaCliente");
    
    // Array para almacenar precios encontrados
    $precios_encontrados = [];
    
    // PRIMERO: Buscar precio específico para esta bodega exacta
    if ($idBodegaCliente > 0) {
        $sql1 = "SELECT p.*, 'Bodega Específica' as tipo_precio 
                FROM precios p 
                WHERE p.id_prod = ? 
                AND p.tipo = 'v'
                AND p.destino = ?
                AND p.status = '1'
                AND p.fecha_ini <= ? 
                AND p.fecha_fin >= ?
                ORDER BY p.fecha_ini DESC 
                LIMIT 1";
        
        $stmt1 = $conn_mysql->prepare($sql1);
        $stmt1->bind_param('iiss', $idProd, $idBodegaCliente, $fechaConsulta, $fechaConsulta);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        
        if ($result1->num_rows > 0) {
            while ($row = $result1->fetch_assoc()) {
                $precios_encontrados[] = $row;
                //error_log("DEBUG: Encontrado precio específico para bodega $idBodegaCliente: ID=" . $row['id_precio']);
            }
        } else {
            //error_log("DEBUG: No se encontró precio específico para bodega $idBodegaCliente");
        }
    }
    
    // SEGUNDO: Si no hay precio específico, buscar precio general (destino = 0)
    if (empty($precios_encontrados)) {
        $sql2 = "SELECT p.*, 'Precio General' as tipo_precio 
                FROM precios p 
                WHERE p.id_prod = ? 
                AND p.tipo = 'v'
                AND p.destino = '0'
                AND p.status = '1'
                AND p.fecha_ini <= ? 
                AND p.fecha_fin >= ?
                ORDER BY p.fecha_ini DESC 
                LIMIT 1";
        
        $stmt2 = $conn_mysql->prepare($sql2);
        $stmt2->bind_param('iss', $idProd, $fechaConsulta, $fechaConsulta);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        if ($result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $precios_encontrados[] = $row;
                //error_log("DEBUG: Encontrado precio general: ID=" . $row['id_precio']);
            }
        } else {
            //error_log("DEBUG: No se encontró precio general para producto $idProd");
        }
    }
    
    // TERCERO: Si aún no hay precios, buscar cualquier precio de venta activo (último recurso)
    if (empty($precios_encontrados)) {
        $sql3 = "SELECT p.*, 'Cualquier Precio' as tipo_precio 
                FROM precios p 
                WHERE p.id_prod = ? 
                AND p.tipo = 'v'
                AND p.status = '1'
                AND p.fecha_ini <= ? 
                AND p.fecha_fin >= ?
                ORDER BY p.destino DESC, p.fecha_ini DESC 
                LIMIT 1";
        
        $stmt3 = $conn_mysql->prepare($sql3);
        $stmt3->bind_param('iss', $idProd, $fechaConsulta, $fechaConsulta);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        
        if ($result3->num_rows > 0) {
            while ($row = $result3->fetch_assoc()) {
                $precios_encontrados[] = $row;
                //error_log("DEBUG: Encontrado cualquier precio: ID=" . $row['id_precio'] . ", destino=" . $row['destino']);
            }
        }
    }
    
    if (!empty($precios_encontrados)) {
        ?>
        <label for="id_precio_venta" class="form-label">Precio de venta</label>
        <select class="form-select" name="id_precio_venta" id="id_precio_venta" required>
            <?php
            foreach ($precios_encontrados as $precio) {
                $fecha_fin_text = ($precio['fecha_fin'] && $precio['fecha_fin'] != '0000-00-00 00:00:00') 
                ? date('d/m/Y', strtotime($precio['fecha_fin'])) 
                : 'Indefinido';
                
                // Determinar tipo de precio para mostrar
                $tipo_text = $precio['tipo_precio'];
                if ($precio['destino'] == 0) {
                    $tipo_text = 'Precio General';
                } elseif ($precio['destino'] > 0) {
                    // Intentar obtener info de la bodega
                    $sql_bodega = "SELECT cod_al, noma FROM direcciones WHERE id_direc = ?";
                    $stmt_bodega = $conn_mysql->prepare($sql_bodega);
                    $stmt_bodega->bind_param('i', $precio['destino']);
                    $stmt_bodega->execute();
                    $bodega_info = $stmt_bodega->get_result()->fetch_assoc();
                    
                    if ($bodega_info) {
                        $tipo_text = $bodega_info['cod_al'] . ' - ' . $bodega_info['noma'];
                    }
                }
                ?>
                <option value="<?=$precio['id_precio']?>">
                    $<?=number_format($precio['precio'], 2)?> 
                    (<?=$tipo_text?> - Hasta: <?=$fecha_fin_text?>)
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    } else {
        // Mostrar mensaje más informativo
        ?>
        <label for="id_precio_venta" class="form-label">Precio de venta</label>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No hay precio de venta vigente para este producto.
            <div class="small mt-1">
                <strong>Posibles causas:</strong>
                <ul class="mb-0">
                    <li>No hay precios de venta configurados para este producto</li>
                    <li>Los precios existentes no están vigentes para la fecha seleccionada</li>
                    <li>Los precios no están activos (status = 0)</li>
                </ul>
            </div>
        </div>
        <div class="mt-2">
            <a href="?p=V_producto&id=<?=$idProd?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i> Configurar precios
            </a>
        </div>
        <?php
    }
    exit;
}

// 11. Stock del producto en bodega específica - VERSIÓN CORREGIDA
if ($accion == 'stock_producto' && isset($_POST['idProd']) && isset($_POST['bodegaId'])) {
    $idProd = $_POST['idProd'];
    $bodegaId = $_POST['bodegaId'];
    
    // CONSULTA CORREGIDA: Solo considerar kilos en pacas
    $sql_stock = "SELECT 
                  IFNULL(SUM(pacas_cantidad_disponible), 0) as pacas_disponibles,
                  IFNULL(SUM(pacas_kilos_disponible), 0) as kilos_en_pacas,
                  IFNULL(SUM(granel_kilos_disponible), 0) as granel_kilos_disponibles
                  FROM inventario_bodega 
                  WHERE id_prod = ? AND id_bodega = ?";
    
    $stmt_stock = $conn_mysql->prepare($sql_stock);
    $stmt_stock->bind_param('ii', $idProd, $bodegaId);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    $stock_data = $result_stock->fetch_assoc();
    
    // Obtener nombre del producto
    $sql_prod = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
    $stmt_prod = $conn_mysql->prepare($sql_prod);
    $stmt_prod->bind_param('i', $idProd);
    $stmt_prod->execute();
    $prod_data = $stmt_prod->get_result()->fetch_assoc();
    
    // Calcular total de kilos disponibles (solo de pacas)
    $kilos_disponibles = $stock_data['kilos_en_pacas'];
    
    if ($stock_data['pacas_disponibles'] > 0 || $kilos_disponibles > 0) {
        ?>
        <div class="alert alert-success">
            <h6><i class="bi bi-box-seam me-2"></i><?=$prod_data['cod']?> - <?=$prod_data['nom_pro']?></h6>
            <div class="row mt-2">
                <div class="col-md-4">
                    <i class="bi bi-box me-1"></i> Pacas disponibles: 
                    <strong id="stock_pacas" data-value="<?=$stock_data['pacas_disponibles']?>">
                        <?=number_format($stock_data['pacas_disponibles'], 0)?>
                    </strong>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-scale me-1"></i> Kilos por paca: 
                    <strong>
                        <?php 
                        if ($stock_data['pacas_disponibles'] > 0) {
                            echo number_format($kilos_disponibles / $stock_data['pacas_disponibles'], 2);
                        } else {
                            echo '0.00';
                        }
                        ?> kg/paca
                    </strong>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-speedometer2 me-1"></i> Total kilos (pacas): 
                    <strong id="stock_kilos" data-value="<?=$kilos_disponibles?>">
                        <?=number_format($kilos_disponibles, 2)?> kg
                    </strong>
                </div>
            </div>
            <?php if ($stock_data['granel_kilos_disponibles'] > 0): ?>
            <div class="row mt-2">
                <div class="col-md-12">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Nota: Existen <?=number_format($stock_data['granel_kilos_disponibles'], 2)?> kg en granel, 
                        pero no se consideran para venta.
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong><?=$prod_data['cod']?> - <?=$prod_data['nom_pro']?></strong><br>
            No hay stock disponible de este producto en la bodega seleccionada.
            <div id="stock_pacas" data-value="0" style="display:none;"></div>
            <div id="stock_kilos" data-value="0" style="display:none;"></div>
        </div>
        <?php
    }
    exit;
}
// 11. Obtener información de venta para edición
if ($accion == 'info_venta_edicion' && isset($_POST['idVenta'])) {
    $idVenta = $_POST['idVenta'];
    
    $sql = "SELECT v.*, 
                   vd.id_prod, vd.id_pre_venta, vd.pacas_cantidad, vd.total_kilos, vd.observaciones,
                   p.cod as cod_producto, p.nom_pro as nombre_producto,
                   pr.precio as precio_actual,
                   DATE_FORMAT(v.fecha_venta, '%Y-%m-%d') as fecha_venta_form
            FROM ventas v
            LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
            LEFT JOIN productos p ON vd.id_prod = p.id_prod
            LEFT JOIN precios pr ON vd.id_pre_venta = pr.id_precio
            WHERE v.id_venta = ? AND v.status = 1";
    
    $stmt = $conn_mysql->prepare($sql);
    $stmt->bind_param('i', $idVenta);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
    
    if (!$venta) {
        echo json_encode(['error' => 'Venta no encontrada']);
        exit;
    }
    
    // Obtener stock disponible para modificación
    $sql_stock = "SELECT 
                  IFNULL(SUM(pacas_cantidad_disponible), 0) as pacas_disponibles,
                  IFNULL(SUM(pacas_kilos_disponible), 0) as kilos_en_pacas
                  FROM inventario_bodega 
                  WHERE id_prod = ? AND id_bodega = ?";
    
    $stmt_stock = $conn_mysql->prepare($sql_stock);
    $stmt_stock->bind_param('ii', $venta['id_prod'], $venta['id_direc_alma']);
    $stmt_stock->execute();
    $stock_data = $stmt_stock->get_result()->fetch_assoc();
    
    $response = [
        'venta' => $venta,
        'stock_extra' => [
            'pacas' => $stock_data['pacas_disponibles'],
            'kilos' => $stock_data['kilos_en_pacas']
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// 12. Obtener precios para fecha específica (para edición)
if ($accion == 'precios_edicion' && isset($_POST['idProd']) && isset($_POST['fecha']) && isset($_POST['idBodegaCliente'])) {
    $idProd = $_POST['idProd'];
    $fecha = $_POST['fecha'];
    $idBodegaCliente = $_POST['idBodegaCliente'];
    
    $sql = "SELECT p.*, 
                   CASE 
                       WHEN p.destino = ? THEN 'Precio específico para esta bodega'
                       WHEN p.destino = 0 THEN 'Precio general'
                       ELSE 'Otro precio'
                   END as tipo_precio
            FROM precios p 
            WHERE p.id_prod = ? 
            AND p.tipo = 'v'
            AND p.status = '1'
            AND p.fecha_ini <= ? 
            AND p.fecha_fin >= ?
            ORDER BY p.destino DESC, p.fecha_ini DESC";
    
    $stmt = $conn_mysql->prepare($sql);
    $stmt->bind_param('iiss', $idBodegaCliente, $idProd, $fecha, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $precios = [];
    while ($row = $result->fetch_assoc()) {
        $precios[] = $row;
    }
    
    echo json_encode($precios);
    exit;
}
// Si no se especificó ninguna acción válida
if (!empty($accion)) {
    die('Error: Acción no reconocida');
}