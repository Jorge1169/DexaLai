<?php
require_once 'config/conexiones.php';

if (isset($_POST['zona_id'])) {
    $zonaId = $_POST['zona_id'];
    
    $query = "SELECT id_prod, nom_pro FROM productos WHERE status = 1 AND zona = ? ORDER BY nom_pro";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $zonaId);
    $stmt->execute();
    $result = $stmt->get_result(); 
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $productos
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó ID de zona'
    ]);
}
?>