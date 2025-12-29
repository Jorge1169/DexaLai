<?php
// actualizar_status_captacion.php
session_start();
require_once 'config/conexiones.php';

// Solo validar datos básicos, sin verificar permisos
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
    // Verificar que la captación existe
    $sql_check = "SELECT id_captacion FROM captacion WHERE id_captacion = ?";
    $stmt_check = $conn_mysql->prepare($sql_check);
    
    if (!$stmt_check) {
        throw new Exception("Error al preparar consulta de verificación: " . $conn_mysql->error);
    }
    
    $stmt_check->bind_param('i', $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows == 0) {
        $stmt_check->close();
        echo json_encode(['success' => false, 'message' => 'La captación no existe']);
        exit;
    }
    $stmt_check->close();

    // Actualizar el status en la base de datos
    $sql = "UPDATE captacion SET status = ? WHERE id_captacion = ?";
    $stmt = $conn_mysql->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar consulta de actualización: " . $conn_mysql->error);
    }
    
    $stmt->bind_param('ii', $nuevo_status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    // También actualizar el status de los productos relacionados
    $sql_detalle = "UPDATE captacion_detalle SET status = ? WHERE id_captacion = ?";
    $stmt_detalle = $conn_mysql->prepare($sql_detalle);
    
    if ($stmt_detalle) {
        $stmt_detalle->bind_param('ii', $nuevo_status, $id);
        $stmt_detalle->execute();
        $stmt_detalle->close();
    }
    
    // Intentar registrar la acción en el historial (si la tabla existe)
    $usuario_id = $_SESSION['user_id'] ?? 0;
    $usuario_nombre = $_SESSION['user_name'] ?? 'Usuario desconocido';
    $accion_desc = ($accion == 'activar') ? 'Activación' : 'Desactivación';
    
    // Verificar si la tabla de logs existe antes de intentar insertar
    $sql_check_table = "SHOW TABLES LIKE 'logs_captaciones'";
    $result = $conn_mysql->query($sql_check_table);
    
    if ($result && $result->num_rows > 0) {
        // La tabla existe, podemos insertar el log
        $sql_log = "INSERT INTO logs_captaciones (id_captacion, usuario_id, usuario_nombre, accion, fecha) VALUES (?, ?, ?, ?, NOW())";
        $stmt_log = $conn_mysql->prepare($sql_log);
        
        if ($stmt_log) {
            $stmt_log->bind_param('iiss', $id, $usuario_id, $usuario_nombre, $accion_desc);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }
    // Si la tabla no existe, simplemente continuamos sin error
    
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Captación ' . ($accion == 'activar' ? 'activada' : 'desactivada') . ' correctamente'
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