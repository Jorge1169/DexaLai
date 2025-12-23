<?php
require_once '../config/conexiones.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID de dirección no proporcionado']);
        exit;
    }
    
    try {
        $sql = "UPDATE direcciones SET status = '0' WHERE id_direc = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        logActivity('ACT/DEC', 'Elimino la direccion '. $id);
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la dirección o ya fue eliminada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>