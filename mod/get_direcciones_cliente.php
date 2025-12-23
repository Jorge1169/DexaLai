<?php
require_once '../config/conexiones.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cli'])) {
    $id_cli = $_POST['id_cli'];
    
    $query = "SELECT id_direc, cod_al FROM direcciones WHERE id_us = ? AND status = 1";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("i", $id_cli);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $direcciones = [];
    while ($row = $result->fetch_assoc()) { 
        $direcciones[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $direcciones
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID de cliente no proporcionado'
    ]);
}
?>