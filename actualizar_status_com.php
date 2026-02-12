<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $accion = $_POST['accion'] ?? '';
    $username = $_SESSION['username'] ?? 'Invitado';
    $userId = $_SESSION['id_cliente'] ?? 0;
    
    if (empty($id)) {  // Aquí estaba el error de sintaxis
        echo json_encode(['success' => false, 'message' => 'ID de Transportista no proporcionado']);
        exit;
    }
    
    $nuevoStatus = ($accion === 'desactivar') ? '0' : '1';
    
    try {
        $sql = "UPDATE compras SET status = ? WHERE id_compra = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param('si', $nuevoStatus, $id);
        $stmt->execute();
        $conn_mysql->query("UPDATE almacen SET status = $nuevoStatus WHERE id_compra = $id");
        
        if ($stmt->affected_rows > 0) {
            logActivity('COMPRA_STATUS', "{$username} (ID {$userId}) cambio status de compra #{$id} a {$nuevoStatus}");
            echo json_encode(['success' => true]);
        } else {
            logActivity('COMPRA_STATUS', "{$username} (ID {$userId}) solicitó cambio sin efecto para compra #{$id}");
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios']);
        }
    } catch (Exception $e) {
        logActivity('COMPRA_STATUS_ERROR', "Error al cambiar status de compra #{$id}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>