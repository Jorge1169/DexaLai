<?php
require_once 'config/conexiones.php';

if (isset($_POST['zona_id'])) {
    $zonaId = $_POST['zona_id'];
    
    $query = "SELECT c.id_compra, c.fact, c.nombre 
              FROM compras c
              LEFT JOIN ventas v ON c.id_compra = v.id_compra
              WHERE c.status = 1 AND v.id_compra IS NULL AND c.zona = ?
              ORDER BY c.fecha DESC";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $zonaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $compras = [];
    while ($row = $result->fetch_assoc()) {
        $compras[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $compras
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se proporcionó ID de zona'
    ]);
}
?>