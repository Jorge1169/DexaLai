<?php
require_once 'config/conexiones.php';

// Variables iniciales
$fol = '';
$folM = '';
$fecha_captacion = isset($_POST['fecha_captacion']) ? $_POST['fecha_captacion'] : date('Y-m-d');
$fe = date('ym', strtotime($fecha_captacion));
$m_actual = date('m', strtotime($fecha_captacion));
$a_actual = date('Y', strtotime($fecha_captacion));

// Determinar la acción solicitada
$accion = $_POST['accion'] ?? '';

// 1. Generación de folio
if ($accion == 'folio' && isset($_POST['zona']) && isset($_POST['fecha_captacion'])) {
    $zonaId = $_POST['zona']; 
    $fechaCaptacion = $_POST['fecha_captacion'];
    
    $z_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zonaId'");
    $z_s1 = mysqli_fetch_array($z_s0);
    
    $anio_seleccionado = date('Y', strtotime($fechaCaptacion));
    $mes_seleccionado = date('m', strtotime($fechaCaptacion));
    
    $qry = "SELECT folio FROM captacion WHERE status = '1' 
    AND YEAR(fecha_captacion) = '$anio_seleccionado' 
    AND MONTH(fecha_captacion) = '$mes_seleccionado' 
    AND zona = '".$z_s1['id_zone']."'
    ORDER BY folio DESC 
    LIMIT 1";
    
    $Rc00 = $conn_mysql->query($qry);
    
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
    
    $folM = "C-".$z_s1['cod']."-".$fe.$fol;
    
    ?>
    <label for="folio" class="form-label">Folio</label>
    <input type="text" id="folio01" class="form-control" value="<?=$folM?>" disabled>
    <input type="hidden" name="folio" value="<?=$fol?>">
    <?php
    exit;
}

// 2. Cargar proveedores por zona
if ($accion == 'proveedores' && isset($_POST['zonaProveedor'])) {
    $zonaId0 = $_POST['zonaProveedor'];
    ?>
    <label for="idProveedor" class="form-label">Proveedor</label>
    <select class="form-select" name="idProveedor" id='idProveedor' onchange="cargarBodegasProveedor()" required>
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
    <?php
    exit;
}

// 3. Cargar almacenes por zona
if ($accion == 'almacenes' && isset($_POST['zonaAlmacen'])) {
    $zonaId0 = $_POST['zonaAlmacen'];
    ?>
    <label for="idAlmacen" class="form-label">Almacén</label>
    <select class="form-select" name="idAlmacen" id="idAlmacen" onchange="cargarBodegasAlmacen()" required>
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

// 4. Cargar fleteros por zona
if ($accion == 'fleteros' && isset($_POST['zonaFletero'])) {
    $zonaId0 = $_POST['zonaFletero'];
    ?>
    <label for="idFletero" class="form-label">Fletero</label>
    <select class="form-select" name="idFletero" id="idFletero" onchange="cargarPrecioFlete()" required>
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
if ($accion == 'productos' && isset($_POST['zonaProducto'])) {
    $zonaId0 = $_POST['zonaProducto'];
    ?>
    <label for="idProd" class="form-label">Producto</label>
    <select class="form-select" name="idProd" id="idProd" onchange="cargarPrecioCompra()" required>
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

// 6. Bodegas del proveedor
if ($accion == 'bodegas_proveedor' && isset($_POST['idProveedor'])) {
    $idProveedor = $_POST['idProveedor'];
    $BodPro0 = $conn_mysql->query("SELECT * FROM direcciones where id_prov = '$idProveedor' AND status = '1'");
    ?>
    <label for="bodgeProv" class="form-label">Bodega del Proveedor</label>
    <select class="form-select" name="bodgeProv" id="bodgeProv" required>
        <?php
        if ($BodPro0 && $BodPro0->num_rows > 0) {
            while ($BodPro1 = mysqli_fetch_array($BodPro0)) {
                $verCor = ($BodPro1['email'] == '') ? '' : '📧' ;
                ?>
                <option value="<?=$BodPro1['id_direc']?>"><?=$BodPro1['cod_al']." / ".$BodPro1['noma']." ".$verCor?></option>
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

// 7. Bodegas del almacén
if ($accion == 'bodegas_almacen' && isset($_POST['idAlmacen'])) {
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

// 8. Precios de flete MEO
if ($accion == 'precio_flete' && isset($_POST['idFletero']) && isset($_POST['tipoFlete']) && isset($_POST['origen']) && isset($_POST['destino'])) {
    $idFletero = $_POST['idFletero'];
    $tipoFlete = $_POST['tipoFlete'];
    $origen = $_POST['origen'];
    $destino = $_POST['destino'];
    $fechaConsulta = isset($_POST['fechaCaptacion']) ? $_POST['fechaCaptacion'] : date('Y-m-d');
    
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
        AND (p.cap_ven = 'CAP' OR p.cap_ven IS NULL)
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

// 9. Precio de compra del producto
if ($accion == 'precio_compra' && isset($_POST['idProd'])) {
    $idProd = $_POST['idProd'];
    $fechaConsulta = isset($_POST['fechaCaptacion']) ? $_POST['fechaCaptacion'] : date('Y-m-d');
    
    $precPD0 = $conn_mysql->query("SELECT * FROM precios 
        WHERE id_prod = '$idProd' 
        AND tipo = 'c'
        AND status = '1'
        AND fecha_ini <= '$fechaConsulta' 
        AND fecha_fin >= '$fechaConsulta'
        ORDER BY fecha_ini DESC");
    
    if ($precPD0 && $precPD0->num_rows > 0) {
        ?>
        <label for="id_prePD" class="form-label">Precio de compra</label>
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
        <label for="id_prePD" class="form-label">Precio de compra</label>
        <input type="text" class="form-control is-invalid" value="Sin precio vigente" disabled>
        <small class="text-danger">Configure el precio en el módulo del producto</small>
        <?php
    }
    exit;
}

// 10. Agregar producto al array temporal (manejado con sesiones)
if ($accion == 'agregar_producto') {
    // Validar datos recibidos
    $id_producto = $_POST['idProd'] ?? 0;
    $precio_compra = $_POST['id_prePD'] ?? 0;
    $tipo_almacen = $_POST['tipo_almacen'] ?? 'granel';
    
    // Campos según tipo
    if ($tipo_almacen == 'granel') {
        $granel_kilos = floatval($_POST['granel_kilos'] ?? 0);
        $pacas_cantidad = 0;
        $pacas_kilos = 0;
    } else { // pacas
        $granel_kilos = 0;
        $pacas_cantidad = intval($_POST['pacas_cantidad'] ?? 0);
        $pacas_kilos = floatval($_POST['pacas_kilos'] ?? 0);
    }
    
    $observaciones = $_POST['observaciones_prod'] ?? '';
    
    // Validaciones
    if ($id_producto <= 0) {
        die('Error: Seleccione un producto válido');
    }
    
    // Verificar que el producto no esté ya agregado
    if (isset($_SESSION['productos_agregados'])) {
        foreach ($_SESSION['productos_agregados'] as $prod) {
            if ($prod['id_producto'] == $id_producto) {
                die('Error: Este producto ya fue agregado');
            }
        }
    }
    
    // Obtener datos del producto desde la BD
    $sql_prod = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
    $stmt_prod = $conn_mysql->prepare($sql_prod);
    $stmt_prod->bind_param('i', $id_producto);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    
    if (!$result_prod || $result_prod->num_rows == 0) {
        die('Error: Producto no encontrado en la base de datos');
    }
    
    $prod_data = $result_prod->fetch_assoc();
    $prod_cod = $prod_data['cod'];
    $prod_nombre = $prod_data['nom_pro'];
    
    // Obtener precio desde la BD
    $sql_precio = "SELECT precio FROM precios WHERE id_precio = ?";
    $stmt_precio = $conn_mysql->prepare($sql_precio);
    $stmt_precio->bind_param('i', $precio_compra);
    $stmt_precio->execute();
    $result_precio = $stmt_precio->get_result();
    $precio_valor = 0;
    
    if ($result_precio && $result_precio->num_rows > 0) {
        $precio_data = $result_precio->fetch_assoc();
        $precio_valor = $precio_data['precio'];
    }
    
    // Calcular datos adicionales
    $peso_promedio = 0;
    if ($pacas_cantidad > 0 && $pacas_kilos > 0) {
        $peso_promedio = $pacas_kilos / $pacas_cantidad;
    }
    
    $total_kilos = $granel_kilos + $pacas_kilos;
    
    // Inicializar array en sesión si no existe
    if (!isset($_SESSION['productos_agregados'])) {
        $_SESSION['productos_agregados'] = [];
    }
    
    // Agregar producto al array en sesión
    $_SESSION['productos_agregados'][] = [
        'id_producto' => $id_producto,
        'cod_producto' => $prod_cod,
        'nombre_producto' => $prod_nombre,
        'id_precio_compra' => $precio_compra,
        'precio_valor' => $precio_valor,
        'tipo_almacen' => $tipo_almacen,
        'granel_kilos' => $granel_kilos,
        'pacas_cantidad' => $pacas_cantidad,
        'pacas_kilos' => $pacas_kilos,
        'peso_promedio' => $peso_promedio,
        'total_kilos' => $total_kilos,
        'observaciones' => $observaciones
    ];
    
    // Devolver la tabla HTML actualizada
    include 'generar_tabla_productos.php';
    exit;
}

// 11. Eliminar producto del array temporal
if ($accion == 'eliminar_producto' && isset($_POST['indice_producto'])) {
    $index = intval($_POST['indice_producto']);
    
    if (isset($_SESSION['productos_agregados'][$index])) {
        unset($_SESSION['productos_agregados'][$index]);
        // Reindexar el array para mantener índices consecutivos
        $_SESSION['productos_agregados'] = array_values($_SESSION['productos_agregados']);
    }
    
    // Devolver la tabla HTML actualizada
    include 'generar_tabla_productos.php';
    exit;
}

// Función para actualizar inventario después de guardar captación
function actualizarInventarioCaptacion($id_captacion, $conn_mysql, $idUser) {
    // 1. Obtener datos de la captación
    $sql_capt = "SELECT c.*, d.id_alma, d.id_direc as id_bodega 
                 FROM captacion c 
                 LEFT JOIN direcciones d ON c.id_direc_alma = d.id_direc 
                 WHERE c.id_captacion = ?";
    $stmt_capt = $conn_mysql->prepare($sql_capt);
    $stmt_capt->bind_param('i', $id_captacion);
    $stmt_capt->execute();
    $captacion = $stmt_capt->get_result()->fetch_assoc();
    
    if (!$captacion) return false;
    
    // 2. Obtener productos de la captación
    $sql_productos = "SELECT * FROM captacion_detalle WHERE id_captacion = ? AND status = 1";
    $stmt_prod = $conn_mysql->prepare($sql_productos);
    $stmt_prod->bind_param('i', $id_captacion);
    $stmt_prod->execute();
    $productos = $stmt_prod->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $conn_mysql->begin_transaction();
    
    try {
        foreach ($productos as $producto) {
            // 3. Verificar si ya existe registro en inventario para esta bodega y producto
            $sql_check = "SELECT id_inventario FROM inventario_bodega 
                         WHERE id_bodega = ? AND id_prod = ?";
            $stmt_check = $conn_mysql->prepare($sql_check);
            $stmt_check->bind_param('ii', $captacion['id_bodega'], $producto['id_prod']);
            $stmt_check->execute();
            $existente = $stmt_check->get_result()->fetch_assoc();
            
            if ($existente) {
                // Actualizar inventario existente
                $sql_update = "UPDATE inventario_bodega SET 
                              granel_kilos_disponible = granel_kilos_disponible + ?,
                              pacas_cantidad_disponible = pacas_cantidad_disponible + ?,
                              pacas_kilos_disponible = pacas_kilos_disponible + ?,
                              total_kilos_disponible = total_kilos_disponible + ?,
                              ultima_entrada = NOW(),
                              updated_at = NOW(),
                              id_user = ?
                              WHERE id_inventario = ?";
                
                $total_kilos = $producto['granel_kilos'] + $producto['pacas_kilos'];
                
                $stmt_update = $conn_mysql->prepare($sql_update);
                $stmt_update->bind_param('diidii', 
                    $producto['granel_kilos'],
                    $producto['pacas_cantidad'],
                    $producto['pacas_kilos'],
                    $total_kilos,
                    $idUser,
                    $existente['id_inventario']
                );
                $stmt_update->execute();
                
                $id_inventario = $existente['id_inventario'];
            } else {
                // Crear nuevo registro de inventario
                $total_kilos = $producto['granel_kilos'] + $producto['pacas_kilos'];
                
                // Calcular peso promedio si hay pacas
                $peso_promedio = 0;
                if ($producto['pacas_cantidad'] > 0 && $producto['pacas_kilos'] > 0) {
                    $peso_promedio = $producto['pacas_kilos'] / $producto['pacas_cantidad'];
                }
                
                $sql_insert = "INSERT INTO inventario_bodega 
                              (id_bodega, id_prod, id_alma, 
                               granel_kilos_disponible, pacas_cantidad_disponible, 
                               pacas_kilos_disponible, pacas_peso_promedio,
                               total_kilos_disponible, ultima_entrada, id_user) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                
                $stmt_insert = $conn_mysql->prepare($sql_insert);
                $stmt_insert->bind_param('iiididddi',
                    $captacion['id_bodega'],
                    $producto['id_prod'],
                    $captacion['id_alma'],
                    $producto['granel_kilos'],
                    $producto['pacas_cantidad'],
                    $producto['pacas_kilos'],
                    $peso_promedio,
                    $total_kilos,
                    $idUser
                );
                $stmt_insert->execute();
                
                $id_inventario = $conn_mysql->insert_id;
            }
            
            // 4. Registrar movimiento en auditoría
            $sql_movimiento = "INSERT INTO movimiento_inventario 
                              (id_inventario, id_captacion, tipo_movimiento,
                               granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento,
                               granel_kilos_nuevo, pacas_cantidad_nuevo, pacas_kilos_nuevo,
                               id_user)
                              SELECT 
                                ?,
                                ?,
                                'entrada',
                                ?,
                                ?,
                                ?,
                                granel_kilos_disponible,
                                pacas_cantidad_disponible,
                                pacas_kilos_disponible,
                                ?
                              FROM inventario_bodega 
                              WHERE id_inventario = ?";
            
            $stmt_mov = $conn_mysql->prepare($sql_movimiento);
            $stmt_mov->bind_param('iiiddii',
                $id_inventario,
                $id_captacion,
                $producto['granel_kilos'],
                $producto['pacas_cantidad'],
                $producto['pacas_kilos'],
                $idUser,
                $id_inventario
            );
            $stmt_mov->execute();
        }
        
        $conn_mysql->commit();
        return true;
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        //error_log("Error actualizando inventario: " . $e->getMessage());
        return false;
    }
}

// Si no se especificó ninguna acción válida
if (!empty($accion)) {
    die('Error: Acción no reconocida');
}
?>