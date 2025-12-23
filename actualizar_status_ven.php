<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $accion = $_POST['accion'] ?? '';
    
    if (empty($id)) {  // Aquí estaba el error de sintaxis
        echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado']);
        exit;
    }
    
    $nuevoStatus = ($accion === 'desactivar') ? '0' : '1';
    
    try {
        $sql = "UPDATE ventas SET status = ? WHERE id_venta = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param('si', $nuevoStatus, $id);
        $stmt->execute();
        $conn_mysql->query("UPDATE almacen SET status = $nuevoStatus WHERE id_venta = $id");
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>