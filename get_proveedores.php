<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión

if (isset($_POST['zona_id'])) {
    $zonaId = $_POST['zona_id'];
    
    // Asegúrate de que tu tabla proveedores tenga un campo zona_id o similar
    $query = "SELECT id_prov, cod, rs FROM proveedores WHERE status = 1 AND zona = ? ORDER BY rs";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $zonaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $proveedores
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó ID de zona'
    ]);
}
?>