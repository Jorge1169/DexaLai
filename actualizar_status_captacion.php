<?php
// actualizar_status_captacion.php
session_start();
require_once 'config/conexiones.php';

// Verificar permisos
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Validar datos recibidos
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

// Determinar el nuevo status
$nuevo_status = ($accion == 'activar') ? 1 : 0;

// Actualizar el status en la base de datos
$sql = "UPDATE captacion SET status = ? WHERE id_captacion = ?";
$stmt = $conn_mysql->prepare($sql);

if ($stmt) {
    $stmt->bind_param('ii', $nuevo_status, $id);
    
    if ($stmt->execute()) {
        // También actualizar el status de los productos relacionados
        $sql_detalle = "UPDATE captacion_detalle SET status = ? WHERE id_captacion = ?";
        $stmt_detalle = $conn_mysql->prepare($sql_detalle);
        
        if ($stmt_detalle) {
            $stmt_detalle->bind_param('ii', $nuevo_status, $id);
            $stmt_detalle->execute();
            $stmt_detalle->close();
        }
        
        // Registrar la acción en el historial
        $usuario_id = $_SESSION['user_id'] ?? 0;
        $accion_desc = ($accion == 'activar') ? 'Activación' : 'Desactivación';
        $sql_log = "INSERT INTO logs_captaciones (id_captacion, usuario_id, accion, fecha) VALUES (?, ?, ?, NOW())";
        $stmt_log = $conn_mysql->prepare($sql_log);
        
        if ($stmt_log) {
            $stmt_log->bind_param('iis', $id, $usuario_id, $accion_desc);
            $stmt_log->execute();
            $stmt_log->close();
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Captación ' . ($accion == 'activar' ? 'activada' : 'desactivada') . ' correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn_mysql->error]);
}
?>