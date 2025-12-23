<?php
require_once 'config/conexiones.php';

if (isset($_POST['zona_id'])) {
    $zonaId = $_POST['zona_id'];
    
    $query = "SELECT id_transp, placas FROM transportes WHERE status = 1 AND zona = ? ORDER BY placas";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $zonaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transportes = [];
    while ($row = $result->fetch_assoc()) {
        $transportes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transportes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó ID de zona'
    ]);
}
?>