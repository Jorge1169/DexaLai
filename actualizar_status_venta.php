<?php
// actualizar_status_venta.php
session_start();
require_once 'config/conexiones.php';

// Solo validar datos básicos
if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$accion = $_POST['accion'];

// Validar ID
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Validar acción
if (!in_array($accion, ['activar', 'desactivar'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// Determinar el nuevo status
$nuevo_status = ($accion == 'activar') ? 1 : 0;

try {
    // Verificar que la venta existe
    $sql_check = "SELECT id_venta FROM ventas WHERE id_venta = ?";
    $stmt_check = $conn_mysql->prepare($sql_check);
    
    if (!$stmt_check) {
        throw new Exception("Error al preparar consulta de verificación: " . $conn_mysql->error);
    }
    
    $stmt_check->bind_param('i', $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows == 0) {
        $stmt_check->close();
        echo json_encode(['success' => false, 'message' => 'La venta no existe']);
        exit;
    }
    $stmt_check->close();

    // Actualizar el status en la base de datos
    $sql = "UPDATE ventas SET status = ? WHERE id_venta = ?";
    $stmt = $conn_mysql->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar consulta de actualización: " . $conn_mysql->error);
    }
    
    $stmt->bind_param('ii', $nuevo_status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    // También actualizar el status de los detalles relacionados
    $sql_detalle = "UPDATE venta_detalle SET status = ? WHERE id_venta = ?";
    $stmt_detalle = $conn_mysql->prepare($sql_detalle);
    
    if ($stmt_detalle) {
        $stmt_detalle->bind_param('ii', $nuevo_status, $id);
        $stmt_detalle->execute();
        $stmt_detalle->close();
    }
    
    
    // Intentar registrar la acción en el historial
    $usuario_id = $_SESSION['id_cliente'] ?? 0;
    $usuario_nombre = $_SESSION['username'] ?? 'Usuario desconocido';
    $accion_desc = ($accion == 'activar') ? 'Activación' : 'Desactivación';
    
    // Verificar si la tabla de logs existe
    $sql_check_table = "SHOW TABLES LIKE 'logs_ventas'";
    $result = $conn_mysql->query($sql_check_table);
    
    if ($result && $result->num_rows > 0) {
        // Insertar en logs
        $sql_log = "INSERT INTO logs_ventas (id_venta, usuario_id, usuario_nombre, accion, fecha) 
                   VALUES (?, ?, ?, ?, NOW())";
        $stmt_log = $conn_mysql->prepare($sql_log);
        
        if ($stmt_log) {
            $stmt_log->bind_param('iiss', $id, $usuario_id, $usuario_nombre, $accion_desc);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Venta ' . ($accion == 'activar' ? 'activada' : 'desactivada') . ' correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Cerrar conexión si es necesario
if (isset($conn_mysql) && $conn_mysql) {
    $conn_mysql->close();
}
?>