<?php
require_once 'config/conexiones.php'; // Asegúrate de incluir tu archivo de conexión

header('Content-Type: application/json');

if (isset($_POST['id_prov'])) {
    $idProv = $_POST['id_prov'];
    
    $query = "SELECT id_direc, cod_al, noma FROM direcciones WHERE id_prov = ? AND status = 1 ORDER BY noma";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param('i', $idProv);
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
        'message' => 'ID de proveedor no recibido'
    ]);
}
?>