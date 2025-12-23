<?php
require_once 'config/conexiones.php';

if (isset($_POST['zona_id'])) {
    $zonaId = $_POST['zona_id'];
    
    $query = "SELECT id_cli, cod, rs FROM clientes WHERE status = 1 AND zona = ? ORDER BY rs";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $zonaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $clientes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó ID de zona'
    ]);
}
?>