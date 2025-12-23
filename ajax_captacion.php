<?php
require_once 'config/conexiones.php';

$accion = $_POST['accion'] ?? '';

if ($accion == 'agregar_producto') {
    // Verificar que todos los campos necesarios estén presentes
    $campos_requeridos = [
        'zona', 'fecha_captacion', 'idProveedor', 'bodgeProv',
        'idAlmacen', 'bodgeAlm', 'idFletero', 'tipo_flete', 'id_preFle',
        'idProd', 'id_prePD', 'tipo_almacen'
    ];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "Falta el campo: $campo"]);
            exit;
        }
    }
    
    // Guardar datos del formulario principal en sesión
    $_SESSION['form_data'] = [
        'zona' => $_POST['zona'],
        'fecha_captacion' => $_POST['fecha_captacion'],
        'idProveedor' => $_POST['idProveedor'],
        'bodgeProv' => $_POST['bodgeProv'],
        'idAlmacen' => $_POST['idAlmacen'],
        'bodgeAlm' => $_POST['bodgeAlm'],
        'idFletero' => $_POST['idFletero'],
        'tipo_flete' => $_POST['tipo_flete'],
        'id_preFle' => $_POST['id_preFle']
    ];
    
    // Inicializar array de productos si no existe
    if (!isset($_SESSION['productos_agregados'])) {
        $_SESSION['productos_agregados'] = [];
    }
    
    $id_producto = $_POST['idProd'];
    $precio_compra = $_POST['id_prePD'];
    $tipo_almacen = $_POST['tipo_almacen'];
    
    // Campos según tipo
    if ($tipo_almacen == 'granel') {
        $granel_kilos = $_POST['granel_kilos'] ?? 0;
        $pacas_cantidad = 0;
        $pacas_kilos = 0;
    } else {
        $granel_kilos = 0;
        $pacas_cantidad = $_POST['pacas_cantidad'] ?? 0;
        $pacas_kilos = $_POST['pacas_kilos'] ?? 0;
    }
    
    $observaciones = $_POST['observaciones_prod'] ?? '';
    
    // Validaciones
    if ($id_producto <= 0) {
        echo json_encode(['success' => false, 'message' => "Seleccione un producto válido"]);
        exit;
    }
    
    if ($tipo_almacen == 'granel' && $granel_kilos <= 0) {
        echo json_encode(['success' => false, 'message' => "Ingrese un peso en kilos válido"]);
        exit;
    }
    
    if ($tipo_almacen == 'pacas' && ($pacas_cantidad <= 0 || $pacas_kilos <= 0)) {
        echo json_encode(['success' => false, 'message' => "Ingrese cantidad y peso de pacas válidos"]);
        exit;
    }
    
    // Verificar que el producto no esté ya agregado
    foreach ($_SESSION['productos_agregados'] as $prod) {
        if ($prod['id_producto'] == $id_producto) {
            echo json_encode(['success' => false, 'message' => "Este producto ya fue agregado"]);
            exit;
        }
    }
    
    // Obtener datos del producto
    $prod_nombre = '';
    $prod_cod = '';
    $sql_prod = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
    $stmt_prod = $conn_mysql->prepare($sql_prod);
    $stmt_prod->bind_param('i', $id_producto);
    $stmt_prod->execute();
    $res_prod = $stmt_prod->get_result();
    
    if ($res_prod && $res_prod->num_rows > 0) {
        $prod_data = $res_prod->fetch_assoc();
        $prod_cod = $prod_data['cod'];
        $prod_nombre = $prod_data['nom_pro'];
    }
    
    // Obtener precio
    $precio_valor = 0;
    $sql_precio = "SELECT precio FROM precios WHERE id_precio = ?";
    $stmt_precio = $conn_mysql->prepare($sql_precio);
    $stmt_precio->bind_param('i', $precio_compra);
    $stmt_precio->execute();
    $res_precio = $stmt_precio->get_result();
    
    if ($res_precio && $res_precio->num_rows > 0) {
        $precio_data = $res_precio->fetch_assoc();
        $precio_valor = $precio_data['precio'];
    }
    
    // Calcular peso promedio por paca si aplica
    $peso_promedio = 0;
    if ($pacas_cantidad > 0 && $pacas_kilos > 0) {
        $peso_promedio = $pacas_kilos / $pacas_cantidad;
    }
    
    // Calcular total de kilos
    $total_kilos = $granel_kilos + $pacas_kilos;
    
    // Agregar al array
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado correctamente',
        'total_productos' => count($_SESSION['productos_agregados'])
    ]);
    
} elseif ($accion == 'eliminar_producto') {
    $index = $_POST['indice_producto'] ?? null;
    
    if ($index !== null && isset($_SESSION['productos_agregados'][$index])) {
        unset($_SESSION['productos_agregados'][$index]);
        $_SESSION['productos_agregados'] = array_values($_SESSION['productos_agregados']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado',
            'total_productos' => count($_SESSION['productos_agregados'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    
} elseif ($accion == 'obtener_productos') {
    // Devolver HTML de la tabla de productos
    $productos_agregados = $_SESSION['productos_agregados'] ?? [];
    
    if (empty($productos_agregados)) {
        echo '<div class="alert alert-info">No hay productos agregados</div>';
        exit;
    }
    
    ob_start();
    ?>
    <h5 class="section-header">Productos Agregados 
        <span class="badge bg-primary"><?= count($productos_agregados) ?> productos</span>
        <span class="badge bg-success"><?= array_sum(array_column($productos_agregados, 'total_kilos')) ?> kg total</span>
    </h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Granel (kg)</th>
                    <th>Pacas (cant)</th>
                    <th>Pacas (kg)</th>
                    <th>Promedio</th>
                    <th>Total (kg)</th>
                    <th>Precio</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_general_kilos = 0;
                $total_general_granel = 0;
                $total_general_pacas = 0;
                $total_general_cantidad = 0;
                ?>
                <?php foreach ($productos_agregados as $index => $producto): ?>
                    <?php
                    $total_general_kilos += $producto['total_kilos'];
                    $total_general_granel += $producto['granel_kilos'];
                    $total_general_pacas += $producto['pacas_kilos'];
                    $total_general_cantidad += $producto['pacas_cantidad'];
                    ?>
                    <tr id="producto-<?=$index?>">
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= $producto['cod_producto'] ?></strong><br>
                            <small class="text-muted"><?= $producto['nombre_producto'] ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $producto['tipo_almacen'] == 'granel' ? 'bg-warning' : 'bg-info' ?>">
                                <?= ucfirst($producto['tipo_almacen']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?= $producto['granel_kilos'] > 0 ? number_format($producto['granel_kilos'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['pacas_cantidad'] > 0 ? $producto['pacas_cantidad'] : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['pacas_kilos'] > 0 ? number_format($producto['pacas_kilos'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['peso_promedio'] > 0 ? number_format($producto['peso_promedio'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <strong><?= number_format($producto['total_kilos'], 2) ?> kg</strong>
                        </td>
                        <td class="text-end">$<?= number_format($producto['precio_valor'], 2) ?></td>
                        <td><small><?= $producto['observaciones'] ?></small></td>
                        <td>
                            <button type="button" onclick="eliminarProductoAjax(<?=$index?>)" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Totales -->
                <tr class="table-secondary fw-bold">
                    <td colspan="3" class="text-end">TOTALES:</td>
                    <td class="text-end"><?= number_format($total_general_granel, 2) ?> kg</td>
                    <td class="text-end"><?= $total_general_cantidad ?></td>
                    <td class="text-end"><?= number_format($total_general_pacas, 2) ?> kg</td>
                    <td class="text-end">-</td>
                    <td class="text-end"><?= number_format($total_general_kilos, 2) ?> kg</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
    echo ob_get_clean();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>