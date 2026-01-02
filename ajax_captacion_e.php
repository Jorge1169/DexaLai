<?php
// ajax_captacion.php - Manejo de AJAX para captaciones
require_once 'config/conexiones.php';

$accion = $_POST['accion'] ?? '';

// Agregar producto a sesión
if ($accion == 'agregar_producto') {
    $id_producto = (int)($_POST['idProd'] ?? 0);
    $id_precio_compra = (int)($_POST['id_prePD'] ?? 0);
    $tipo_almacen = $_POST['tipo_almacen'] ?? 'granel';
    $granel_kilos = floatval($_POST['granel_kilos'] ?? 0);
    $pacas_cantidad = intval($_POST['pacas_cantidad'] ?? 0);
    $pacas_kilos = floatval($_POST['pacas_kilos'] ?? 0);
    $observaciones = $_POST['observaciones_prod'] ?? '';
    
    // Validaciones
    if ($id_producto <= 0) {
        die('Error: Seleccione un producto válido');
    }
    
    if ($id_precio_compra <= 0) {
        die('Error: Seleccione un precio de compra válido');
    }
    
    // Validar valores según tipo
    if ($tipo_almacen == 'granel' && $granel_kilos <= 0) {
        die('Error: Ingrese un peso en granel mayor a 0');
    }
    
    if ($tipo_almacen == 'pacas') {
        if ($pacas_cantidad <= 0) {
            die('Error: Ingrese una cantidad de pacas mayor a 0');
        }
        if ($pacas_kilos <= 0) {
            die('Error: Ingrese un peso total de pacas mayor a 0');
        }
    }
    
    // Obtener datos del producto
    $sql_prod = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
    $stmt_prod = $conn_mysql->prepare($sql_prod);
    $stmt_prod->bind_param('i', $id_producto);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    
    if (!$result_prod || $result_prod->num_rows == 0) {
        die('Error: Producto no encontrado');
    }
    
    $prod_data = $result_prod->fetch_assoc();
    
    // Obtener precio
    $precio_valor = 0;
    $sql_precio = "SELECT precio FROM precios WHERE id_precio = ?";
    $stmt_precio = $conn_mysql->prepare($sql_precio);
    $stmt_precio->bind_param('i', $id_precio_compra);
    $stmt_precio->execute();
    $result_precio = $stmt_precio->get_result();
    
    if ($result_precio && $result_precio->num_rows > 0) {
        $precio_data = $result_precio->fetch_assoc();
        $precio_valor = $precio_data['precio'];
    }
    
    // Calcular valores
    $peso_promedio = 0;
    if ($pacas_cantidad > 0 && $pacas_kilos > 0) {
        $peso_promedio = $pacas_kilos / $pacas_cantidad;
    }
    
    $total_kilos = $granel_kilos + $pacas_kilos;
    
    // Inicializar sesión si no existe
    if (!isset($_SESSION['productos_agregados'])) {
        $_SESSION['productos_agregados'] = [];
    }
    
    // Verificar si el producto ya está en la sesión
    foreach ($_SESSION['productos_agregados'] as $index => $prod_sesion) {
        if ($prod_sesion['id_producto'] == $id_producto) {
            // Actualizar producto existente en sesión
            $_SESSION['productos_agregados'][$index] = [
                'id_detalle' => $prod_sesion['id_detalle'] ?? 0,
                'id_producto' => $id_producto,
                'cod_producto' => $prod_data['cod'],
                'nombre_producto' => $prod_data['nom_pro'],
                'id_precio_compra' => $id_precio_compra,
                'precio_valor' => $precio_valor,
                'tipo_almacen' => $tipo_almacen,
                'granel_kilos' => $granel_kilos,
                'pacas_cantidad' => $pacas_cantidad,
                'pacas_kilos' => $pacas_kilos,
                'peso_promedio' => $peso_promedio,
                'total_kilos' => $total_kilos,
                'observaciones' => $observaciones
            ];
            
            include 'generar_tabla_productos.php';
            exit;
        }
    }
    
    // Agregar producto nuevo
    $_SESSION['productos_agregados'][] = [
        'id_detalle' => 0, // 0 indica que es nuevo
        'id_producto' => $id_producto,
        'cod_producto' => $prod_data['cod'],
        'nombre_producto' => $prod_data['nom_pro'],
        'id_precio_compra' => $id_precio_compra,
        'precio_valor' => $precio_valor,
        'tipo_almacen' => $tipo_almacen,
        'granel_kilos' => $granel_kilos,
        'pacas_cantidad' => $pacas_cantidad,
        'pacas_kilos' => $pacas_kilos,
        'peso_promedio' => $peso_promedio,
        'total_kilos' => $total_kilos,
        'observaciones' => $observaciones
    ];
    
    // Devolver tabla actualizada
    include 'generar_tabla_productos.php';
    exit;
}

// Eliminar producto de sesión
if ($accion == 'eliminar_producto') {
    $index = intval($_POST['indice_producto'] ?? -1);
    
    if (isset($_SESSION['productos_agregados'][$index])) {
        unset($_SESSION['productos_agregados'][$index]);
        // Reindexar array
        $_SESSION['productos_agregados'] = array_values($_SESSION['productos_agregados']);
    }
    
    // Devolver tabla actualizada
    include 'generar_tabla_productos.php';
    exit;
}

// Limpiar todos los productos
if ($accion == 'limpiar_productos') {
    $_SESSION['productos_agregados'] = [];
    echo 'Productos limpiados';
    exit;
}

// Obtener productos
if ($accion == 'obtener_productos') {
    include 'generar_tabla_productos.php';
    exit;
}

// Si no hay acción válida
echo 'Acción no reconocida';