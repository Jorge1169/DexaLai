<?php
require_once 'config/conexiones.php';

header('Content-Type: application/json');

// Depuración - para ver qué datos llegan
//error_log("=== INICIO guardar_numeros_productos.php ===");
//error_log("ID Captación: " . ($_POST['id_captacion'] ?? 'NO'));
//error_log("Productos recibidos: " . print_r($_POST['productos'] ?? [], true));

$id_captacion = $_POST['id_captacion'] ?? 0;
$productos = $_POST['productos'] ?? [];

// Verificar si $productos es realmente un array
if (!is_array($productos)) {
    //error_log("ERROR: productos no es un array");
    echo json_encode(['success' => false, 'message' => 'Formato de datos inválido']);
    exit;
}

if ($id_captacion <= 0 || empty($productos)) {
    //error_log("ERROR: Datos inválidos o vacíos");
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o no hay productos']);
    exit;
}

try {
    $conn_mysql->begin_transaction();
    $actualizados = 0;
    $errores = [];
    
    foreach ($productos as $index => $producto) {
        $id_detalle = isset($producto['id_detalle']) ? intval($producto['id_detalle']) : 0;
        
        // Manejar valores vacíos como NULL
        $numero_ticket = isset($producto['numero_ticket']) ? trim($producto['numero_ticket']) : '';
        $numero_bascula = isset($producto['numero_bascula']) ? trim($producto['numero_bascula']) : '';
        $numero_factura = isset($producto['numero_factura']) ? trim($producto['numero_factura']) : '';
        
        //error_log("Procesando producto $index - ID Detalle: $id_detalle");
        //error_log("Ticket: '$numero_ticket', Báscula: '$numero_bascula', Factura: '$numero_factura'");
        
        if ($id_detalle <= 0) {
            //error_log("ERROR: ID detalle inválido en índice $index");
            $errores[] = "ID de detalle inválido en producto $index";
            continue;
        }
        
        // Preparar valores para SQL (convertir cadenas vacías a NULL)
        $ticket_sql = ($numero_ticket === '') ? null : $numero_ticket;
        $bascula_sql = ($numero_bascula === '') ? null : $numero_bascula;
        $factura_sql = ($numero_factura === '') ? null : $numero_factura;
        
        $sql = "UPDATE captacion_detalle 
                SET numero_ticket = ?, 
                    numero_bascula = ?, 
                    numero_factura = ? 
                WHERE id_detalle = ? AND id_captacion = ? AND status = 1";
        
        //error_log("SQL a ejecutar: $sql");
        //error_log("Parámetros: ticket=$ticket_sql, bascula=$bascula_sql, factura=$factura_sql, id_detalle=$id_detalle, id_captacion=$id_captacion");
        
        $stmt = $conn_mysql->prepare($sql);
        if (!$stmt) {
            $error_msg = "Error al preparar consulta para ID $id_detalle: " . $conn_mysql->error;
            //error_log($error_msg);
            $errores[] = $error_msg;
            continue;
        }
        
        // bind_param requiere que las variables sean pasadas por referencia
        $stmt->bind_param('sssii', $ticket_sql, $bascula_sql, $factura_sql, $id_detalle, $id_captacion);
        
        if (!$stmt->execute()) {
            $error_msg = "Error al ejecutar para ID $id_detalle: " . $stmt->error;
            //error_log($error_msg);
            $errores[] = $error_msg;
        } else {
            $actualizados++;
            //error_log("Producto ID $id_detalle actualizado correctamente");
        }
        
        $stmt->close();
    }
    
    if (empty($errores)) {
        $conn_mysql->commit();
        $mensaje = "Números actualizados correctamente para $actualizados productos";
        //error_log("ÉXITO: $mensaje");
        echo json_encode([
            'success' => true, 
            'message' => $mensaje,
            'actualizados' => $actualizados
        ]);
    } else {
        $conn_mysql->rollback();
        $mensaje = "Se encontraron errores al actualizar: " . implode(', ', $errores);
        //error_log("ERROR en transacción: $mensaje");
        echo json_encode([
            'success' => false, 
            'message' => 'Errores encontrados',
            'errores' => $errores,
            'actualizados' => $actualizados
        ]);
    }
    
} catch (Exception $e) {
    if (isset($conn_mysql)) {
        $conn_mysql->rollback();
    }
    $error_msg = "Excepción: " . $e->getMessage();
    //error_log("EXCEPCIÓN: $error_msg");
    echo json_encode(['success' => false, 'message' => $error_msg]);
}

//error_log("=== FIN guardar_numeros_productos.php ===");
?>