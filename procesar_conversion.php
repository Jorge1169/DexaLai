<?php
session_start();
require_once 'config/conexiones.php';
// OBTENER TEMA DE LA SESIÓN O COOKIE
$tema = 'light'; // Tema por defecto

// Primero intentar obtener de la cookie
if (isset($_COOKIE['theme'])) {
    $tema = $_COOKIE['theme'];
}
// Luego de la sesión (si existe)
if (isset($_SESSION['theme'])) {
    $tema = $_SESSION['theme'];
}
// Validar que el tema sea válido
$tema = ($tema === 'dark' || $tema === 'light') ? $tema : 'light';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirigir directamente sin alerta
    header("Location: ?p=almacenes_info");
    exit;
}

// Obtener datos del formulario
$id_almacen = isset($_POST['id_almacen']) ? intval($_POST['id_almacen']) : 0;
$id_inventario_origen = isset($_POST['producto_origen']) ? intval($_POST['producto_origen']) : 0;
$id_producto_destino = isset($_POST['producto_destino']) ? intval($_POST['producto_destino']) : 0;
$kilos_transformar = isset($_POST['kilos_transformar']) ? floatval($_POST['kilos_transformar']) : 0;
$cantidad_pacas = isset($_POST['cantidad_pacas']) ? intval($_POST['cantidad_pacas']) : 0;
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;

// Calcular peso por paca
$peso_por_paca = $cantidad_pacas > 0 ? $kilos_transformar / $cantidad_pacas : 0;

// Variables para mensaje
$mensaje = '';
$tipo_mensaje = ''; // success, error, info
$redirect_url = '';

// Validaciones básicas
if ($id_almacen <= 0 || $id_inventario_origen <= 0 || $id_producto_destino <= 0 || 
    $kilos_transformar <= 0 || $cantidad_pacas <= 0 || $peso_por_paca <= 0) {
    $mensaje = "Datos inválidos proporcionados";
    $tipo_mensaje = 'error';
    $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
} else {
    // Verificar que haya suficiente granel disponible
    $conn_mysql->begin_transaction();

    try {
        // 1. Obtener información del inventario origen
        $sql_info_origen = "SELECT ib.*, p.cod as cod_producto, p.nom_pro as nombre_producto 
                           FROM inventario_bodega ib
                           LEFT JOIN productos p ON ib.id_prod = p.id_prod
                           WHERE ib.id_inventario = ?";
        
        $stmt_info_origen = $conn_mysql->prepare($sql_info_origen);
        $stmt_info_origen->bind_param('i', $id_inventario_origen);
        $stmt_info_origen->execute();
        $info_origen = $stmt_info_origen->get_result()->fetch_assoc();
        
        if (!$info_origen) {
            throw new Exception("No se encontró el inventario de origen");
        }
        
        // 2. Verificar granel disponible en inventario_bodega (valores reales)
        $granel_disponible = $info_origen['granel_kilos_disponible'] ?? 0;
        
        if ($granel_disponible < $kilos_transformar) {
            throw new Exception("No hay suficiente granel disponible. Disponible: " . 
                              number_format($granel_disponible, 2) . " kg, Solicitado: " . 
                              number_format($kilos_transformar, 2) . " kg");
        }
        
        // 3. Obtener o crear inventario para el producto destino
        $sql_inventario_destino = "SELECT id_inventario FROM inventario_bodega 
                                  WHERE id_alma = ? AND id_prod = ? AND id_bodega = ?";
        $stmt_destino = $conn_mysql->prepare($sql_inventario_destino);
        $stmt_destino->bind_param('iii', $id_almacen, $id_producto_destino, $info_origen['id_bodega']);
        $stmt_destino->execute();
        $inventario_destino = $stmt_destino->get_result()->fetch_assoc();
        
        if ($inventario_destino) {
            $id_inventario_destino = $inventario_destino['id_inventario'];
        } else {
            // Crear nuevo registro de inventario para el producto destino
            $sql_crear_inventario = "INSERT INTO inventario_bodega 
                                    (id_bodega, id_prod, id_alma, 
                                     granel_kilos_disponible, pacas_cantidad_disponible, pacas_kilos_disponible, 
                                     total_kilos_disponible, pacas_peso_promedio, status) 
                                    VALUES (?, ?, ?, 0, 0, 0, 0, 0, 1)";
            $stmt_crear = $conn_mysql->prepare($sql_crear_inventario);
            $stmt_crear->bind_param('iii', 
                $info_origen['id_bodega'], 
                $id_producto_destino, 
                $id_almacen
            );
            $stmt_crear->execute();
            $id_inventario_destino = $conn_mysql->insert_id;
        }
        
        // 4. CREAR MOVIMIENTO DE SALIDA SIMPLE para el producto ORIGEN
        $observaciones_salida = "Transformación a producto destino ID " . $id_producto_destino . ": " . $observaciones;
        
        // INSERT simplificado para salida
        $sql_salida = "INSERT INTO movimiento_inventario 
            (id_inventario, tipo_movimiento, granel_kilos_movimiento, observaciones, id_user) 
            VALUES (?, 'salida', ?, ?, ?)";
        
        $kilos_negativo = $kilos_transformar;
        
        $stmt_salida = $conn_mysql->prepare($sql_salida);
        $stmt_salida->bind_param('idsi', 
            $id_inventario_origen,
            $kilos_negativo,
            $observaciones_salida,
            $id_usuario
        );
        
        if (!$stmt_salida->execute()) {
            throw new Exception("Error al registrar salida de transformación: " . $stmt_salida->error);
        }
        
        // 5. CREAR MOVIMIENTO DE ENTRADA SIMPLE para el producto DESTINO
        $observaciones_entrada = "Transformación desde producto origen ID " . $info_origen['id_prod'] . ": " . $observaciones;
        
        // INSERT simplificado para entrada
        $sql_entrada = "INSERT INTO movimiento_inventario 
            (id_inventario, tipo_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento, 
             conversion_kilos, conversion_pacas_generadas, conversion_peso_promedio, observaciones, id_user) 
            VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_entrada = $conn_mysql->prepare($sql_entrada);
        $stmt_entrada->bind_param('iiddddsi',
            $id_inventario_destino,
            $cantidad_pacas,
            $kilos_transformar,
            $kilos_transformar,
            $cantidad_pacas,
            $peso_por_paca,
            $observaciones_entrada,
            $id_usuario
        );
        
        if (!$stmt_entrada->execute()) {
            throw new Exception("Error al registrar entrada de transformación: " . $stmt_entrada->error);
        }
        
        // 6. Actualizar tabla inventario_bodega para el ORIGEN (solo granel)
        $sql_update_origen = "UPDATE inventario_bodega 
                             SET granel_kilos_disponible = granel_kilos_disponible - ?,
                                 total_kilos_disponible = total_kilos_disponible - ?,
                                 updated_at = NOW()
                             WHERE id_inventario = ?";
        $stmt_update_o = $conn_mysql->prepare($sql_update_origen);
        $stmt_update_o->bind_param('ddi', $kilos_transformar, $kilos_transformar, $id_inventario_origen);
        if (!$stmt_update_o->execute()) {
            throw new Exception("Error al actualizar inventario origen: " . $stmt_update_o->error);
        }
        
        // 7. Actualizar tabla inventario_bodega para el DESTINO (pacas)
        $sql_update_destino = "UPDATE inventario_bodega 
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
        $stmt_update_d = $conn_mysql->prepare($sql_update_destino);
        $stmt_update_d->bind_param('idddddi', 
            $cantidad_pacas,
            $kilos_transformar,
            $kilos_transformar,
            $cantidad_pacas,
            $kilos_transformar,
            $cantidad_pacas,
            $id_inventario_destino
        );
        if (!$stmt_update_d->execute()) {
            throw new Exception("Error al actualizar inventario destino: " . $stmt_update_d->error);
        }
        
        $conn_mysql->commit();
        
        // Obtener nombres de productos para el mensaje
        $sql_nombre_origen = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
        $stmt_nombre_o = $conn_mysql->prepare($sql_nombre_origen);
        $stmt_nombre_o->bind_param('i', $info_origen['id_prod']);
        $stmt_nombre_o->execute();
        $nombre_origen = $stmt_nombre_o->get_result()->fetch_assoc();
        
        $sql_nombre_destino = "SELECT cod, nom_pro FROM productos WHERE id_prod = ?";
        $stmt_nombre_d = $conn_mysql->prepare($sql_nombre_destino);
        $stmt_nombre_d->bind_param('i', $id_producto_destino);
        $stmt_nombre_d->execute();
        $nombre_destino = $stmt_nombre_d->get_result()->fetch_assoc();
        
        $mensaje = "Transformación realizada exitosamente:<br>" .
                  "<strong>" . number_format($kilos_transformar, 2) . " kg</strong> de <strong>" . 
                  $nombre_origen['cod'] . " - " . $nombre_origen['nom_pro'] . "</strong><br>" .
                  "transformados en <strong>" . $cantidad_pacas . " pacas</strong> de <strong>" . 
                  $nombre_destino['cod'] . " - " . $nombre_destino['nom_pro'] . "</strong><br>" .
                  "(" . number_format($peso_por_paca, 2) . " kg cada una)";
        $tipo_mensaje = 'success';
        $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        $mensaje = "Error en la transformación: " . $e->getMessage();
        $tipo_mensaje = 'error';
        $redirect_url = "V_detalle_almacen&id=" . $id_almacen;
    }
}

// Ahora mostramos una página HTML completa con SweetAlert
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="<?php echo htmlspecialchars($tema); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando Transformación</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap CSS (opcional, pero recomendado) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
          /* Estilos dinámicos según el tema */
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
            border-top: 5px solid #0dcaf0;
        }
        
        .light-mode .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0d6efd;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .text-muted {
            opacity: 0.8;
        }
        
        .dark-mode .text-muted {
            color: #adb5bd !important;
        }
    </style>
</head>
<body class="<?php echo $tema === 'dark' ? 'dark-mode' : 'light-mode'; ?>">
    <div class="loading-container" id="loading">
        <div class="spinner"></div>
        <h4>Procesando transformación...</h4>
        <p class="text-muted">Por favor espere</p>
    </div>

     <script>
        // Aplicar tema de Bootstrap según el atributo data-bs-theme
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que Bootstrap cargue
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
                    confirmButtonColor: '#0d6efd',
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