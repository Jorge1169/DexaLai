<?php
session_start();
require_once 'config/conexiones.php';

// OBTENER TEMA DE LA SESIÓN O COOKIE
$tema = 'light';
if (isset($_COOKIE['theme'])) {
    $tema = $_COOKIE['theme'];
}
if (isset($_SESSION['theme'])) {
    $tema = $_SESSION['theme'];
}
$tema = ($tema === 'dark' || $tema === 'light') ? $tema : 'light';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?p=almacenes_info");
    exit;
}

// Obtener datos del formulario
$id_almacen = isset($_POST['id_almacen']) ? intval($_POST['id_almacen']) : 0;
$id_producto = isset($_POST['producto_entrada']) ? intval($_POST['producto_entrada']) : 0;
$id_bodega = isset($_POST['bodega_entrada']) ? intval($_POST['bodega_entrada']) : 0;
$tipo_entrada = isset($_POST['tipo_entrada']) ? trim($_POST['tipo_entrada']) : 'granel';
$kilos_granel = isset($_POST['kilos_granel']) ? floatval($_POST['kilos_granel']) : 0;
$cantidad_pacas = isset($_POST['cantidad_pacas_entrada']) ? intval($_POST['cantidad_pacas_entrada']) : 0;
$peso_pacas = isset($_POST['peso_pacas_entrada']) ? floatval($_POST['peso_pacas_entrada']) : 0;
$tipo_movimiento = isset($_POST['tipo_movimiento']) ? trim($_POST['tipo_movimiento']) : 'entrada';
$observaciones = isset($_POST['observacionesEntrada']) ? trim($_POST['observacionesEntrada']) : '';
$id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
$fecha_entrada = isset($_POST['fecha_entrada']) ? trim($_POST['fecha_entrada']) : date('Y-m-d H:i:s');
// Variables para mensaje
$mensaje = '';
$tipo_mensaje = '';
$redirect_url = '';

// Validaciones
if ($id_almacen <= 0) {
    $mensaje = "Debe seleccionar un almacén.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} elseif ($id_producto <= 0) {
    $mensaje = "Debe seleccionar un producto.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} elseif ($id_bodega <= 0) {
    $mensaje = "Debe seleccionar una bodega.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} elseif (empty($observaciones)) {
    $mensaje = "Debe ingresar observaciones.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} elseif ($tipo_entrada === 'granel' && $kilos_granel <= 0) {
    $mensaje = "Debe ingresar una cantidad válida de kilos para entrada en granel.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} elseif ($tipo_entrada === 'pacas' && ($cantidad_pacas <= 0 || $peso_pacas <= 0)) {
    $mensaje = "Debe ingresar cantidad y peso válidos para entrada en pacas.";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} else {
    // Calcular peso por paca si es entrada en pacas
    $peso_por_paca = ($cantidad_pacas > 0 && $peso_pacas > 0) ? $peso_pacas / $cantidad_pacas : 0;
    
    $conn_mysql->begin_transaction();

    try {
        // 1. Obtener o crear registro en inventario_bodega
        $sql_inventario = "SELECT id_inventario FROM inventario_bodega 
                          WHERE id_alma = ? AND id_prod = ? AND id_bodega = ? AND status = 1";
        $stmt_inventario = $conn_mysql->prepare($sql_inventario);
        $stmt_inventario->bind_param('iii', $id_almacen, $id_producto, $id_bodega);
        $stmt_inventario->execute();
        $inventario = $stmt_inventario->get_result()->fetch_assoc();
        
        if ($inventario) {
            $id_inventario = $inventario['id_inventario'];
        } else {
            // Crear nuevo registro
            $sql_crear_inventario = "INSERT INTO inventario_bodega 
                                    (id_bodega, id_prod, id_alma, 
                                     granel_kilos_disponible, pacas_cantidad_disponible, pacas_kilos_disponible, 
                                     total_kilos_disponible, pacas_peso_promedio, status, created_at) 
                                    VALUES (?, ?, ?, 0, 0, 0, 0, 0, 1, NOW())";
            $stmt_crear = $conn_mysql->prepare($sql_crear_inventario);
            $stmt_crear->bind_param('iii', $id_bodega, $id_producto, $id_almacen);
            if (!$stmt_crear->execute()) {
                throw new Exception("Error al crear registro de inventario: " . $stmt_crear->error);
            }
            $id_inventario = $conn_mysql->insert_id;
        }
         // Preparar valores según tipo de entrada
        $granel_kilos = ($tipo_entrada === 'granel') ? $kilos_granel : 0;
        $pacas_cantidad = ($tipo_entrada === 'pacas') ? $cantidad_pacas : 0;
        $pacas_kilos = ($tipo_entrada === 'pacas') ? $peso_pacas : 0;
        
        // 2. Insertar movimiento en movimiento_inventario
        $conn_mysql->query("INSERT INTO movimiento_inventario 
                          (id_inventario, tipo_movimiento, granel_kilos_movimiento, 
                           pacas_cantidad_movimiento, pacas_kilos_movimiento, 
                           observaciones, id_user, created_at) 
                          VALUES ('$id_inventario', '$tipo_movimiento', '$granel_kilos', '$pacas_cantidad', '$pacas_kilos', '$observaciones', '$id_usuario', '$fecha_entrada')");
        
        
        // 3. Actualizar inventario_bodega según el tipo de entrada
        if ($tipo_entrada === 'granel') {
            $sql_update_inventario = "UPDATE inventario_bodega 
                                     SET granel_kilos_disponible = granel_kilos_disponible + ?,
                                         total_kilos_disponible = total_kilos_disponible + ?,
                                         updated_at = NOW()
                                     WHERE id_inventario = ?";
            $stmt_update = $conn_mysql->prepare($sql_update_inventario);
            $stmt_update->bind_param('ddi', $kilos_granel, $kilos_granel, $id_inventario);
        } else {
            // Entrada en pacas
            $sql_update_inventario = "UPDATE inventario_bodega 
                                     SET pacas_cantidad_disponible = pacas_cantidad_disponible + ?,
                                         pacas_kilos_disponible = pacas_kilos_disponible + ?,
                                         total_kilos_disponible = total_kilos_disponible + ?,
                                         pacas_peso_promedio = CASE 
                                             WHEN (pacas_cantidad_disponible + ?) > 0 
                                             THEN (pacas_kilos_disponible + ?) / (pacas_cantidad_disponible + ?)
                                             ELSE 0 
                                         END,
                                         updated_at = NOW()
                                     WHERE id_inventario = ?";
            $stmt_update = $conn_mysql->prepare($sql_update_inventario);
            $stmt_update->bind_param('idddddi', 
                $cantidad_pacas,
                $peso_pacas,
                $peso_pacas,
                $cantidad_pacas,
                $peso_pacas,
                $cantidad_pacas,
                $id_inventario
            );
        }
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar inventario: " . $stmt_update->error);
        }
        
        $conn_mysql->commit();
        
        // Obtener información del producto para el mensaje
        $sql_producto_info = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
        $stmt_producto_info = $conn_mysql->prepare($sql_producto_info);
        $stmt_producto_info->bind_param('i', $id_producto);
        $stmt_producto_info->execute();
        $producto_info = $stmt_producto_info->get_result()->fetch_assoc();
        
        // Obtener información de la bodega
        $sql_bodega_info = "SELECT noma FROM direcciones WHERE id_direc = ?";
        $stmt_bodega_info = $conn_mysql->prepare($sql_bodega_info);
        $stmt_bodega_info->bind_param('i', $id_bodega);
        $stmt_bodega_info->execute();
        $bodega_info = $stmt_bodega_info->get_result()->fetch_assoc();
        
        // Construir mensaje de éxito
        if ($tipo_entrada === 'granel') {
            $mensaje = "Entrada registrada exitosamente:<br>" .
                      "<strong>" . number_format($kilos_granel, 2) . " kg</strong> de granel de <strong>" . 
                      $producto_info['cod'] . " - " . $producto_info['nom_pro'] . "</strong><br>" .
                      "en la bodega <strong>" . $bodega_info['noma'] . "</strong><br>" .
                      "Tipo: <span class='badge bg-success'>" . ucfirst($tipo_movimiento) . "</span>";
        } else {
            $mensaje = "Entrada registrada exitosamente:<br>" .
                      "<strong>" . $cantidad_pacas . " pacas</strong> de <strong>" . 
                      $producto_info['cod'] . " - " . $producto_info['nom_pro'] . "</strong><br>" .
                      "Peso total: <strong>" . number_format($peso_pacas, 2) . " kg</strong><br>" .
                      "Peso por paca: <strong>" . number_format($peso_por_paca, 2) . " kg</strong><br>" .
                      "en la bodega <strong>" . $bodega_info['noma'] . "</strong><br>" .
                      "Tipo: <span class='badge bg-success'>" . ucfirst($tipo_movimiento) . "</span>";
        }
        
        $tipo_mensaje = 'success';
        $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        $mensaje = "Error al registrar la entrada: " . $e->getMessage();
        $tipo_mensaje = 'error';
        $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
    }
}

// Mostrar página con SweetAlert
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="<?php echo htmlspecialchars($tema); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando Entrada</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }
        
        body.light-mode {
            background-color: #f8f9fa;
            color: #212529;
        }
        
        .loading-container {
            text-align: center;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dark-mode .loading-container {
            background-color: #343a40;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .light-mode .loading-container {
            background-color: white;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        .dark-mode .spinner {
            border: 5px solid #495057;
            border-top: 5px solid #20c997;
        }
        
        .light-mode .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #20c997;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="<?php echo $tema === 'dark' ? 'dark-mode' : 'light-mode'; ?>">
    <div class="loading-container" id="loading">
        <div class="spinner"></div>
        <h4>Procesando entrada...</h4>
        <p class="text-muted">Por favor espere</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                Swal.fire({
                    title: '<?php echo ($tipo_mensaje == 'success') ? "Éxito" : "Error"; ?>',
                    html: '<?php echo addslashes($mensaje); ?>',
                    icon: '<?php echo $tipo_mensaje; ?>',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: true,
                    confirmButtonColor: '#198754',
                    background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#343a40' : '#ffffff',
                    color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f8f9fa' : '#212529'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.php?p=<?php echo $redirect_url; ?>';
                    }
                });
                
                document.getElementById('loading').style.display = 'none';
            }, 500);
            
            // Redirección automática de respaldo
            setTimeout(function() {
                window.location.href = 'index.php?p=<?php echo $redirect_url; ?>';
            }, 5000);
        });
    </script>
</body>
</html>
<?php exit; ?>