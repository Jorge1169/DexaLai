<?php
require_once 'config/conexiones.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $accion = $_POST['accion'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID de almacén no proporcionado']);
        exit;
    }
    
    $nuevoStatus = ($accion === 'desactivar') ? '0' : '1';
    $registro = ($accion === 'desactivar') ? 'Desactivó' : 'Activó';
    $mensaje = ($accion === 'desactivar') ? 'Almacén desactivado correctamente' : 'Almacén activado correctamente';
    
    try {
        // Actualizar status del almacén
        $sql = "UPDATE almacenes SET status = ? WHERE id_alma = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param('si', $nuevoStatus, $id);
        $stmt->execute();

        // También actualizar direcciones asociadas al almacén
        $conn_mysql->query("UPDATE direcciones SET status = '$nuevoStatus' WHERE id_alma = '$id'");
        
        // Registrar en el log de actividades
        logActivity('ACT/DES ALMACEN', $registro . ' el almacén ' . $id);
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => $mensaje]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios en el almacén']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>