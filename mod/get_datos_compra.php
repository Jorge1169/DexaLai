<?php
require_once '../config/conexiones.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_compra'])) {
    $id_compra = $_POST['id_compra'];
    
    // Obtener datos de la compra y el transporte asociado
    $query = "SELECT c.tara, c.bruto, c.neto, c.id_prod, t.placas, t.id_transp AS id_transporte , p.nom_pro, p.cod 
              FROM compras c
              LEFT JOIN transportes t ON c.id_transp = t.id_transp
              LEFT JOIN productos p ON c.id_prod = p.id_prod
              WHERE c.id_compra = ?";
    
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("i", $id_compra);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'tara' => $row['tara'],
            'bruto' => $row['bruto'],
            'neto' => $row['neto'],
            'id_prod' => $row['id_prod'],
            'id_transporte' => $row['id_transporte'],
            'transporte' => [
            'placas' => $row['placas']
            ],
            'nom_prod' => $row['cod']." - ".$row['nom_pro']
        ]);
    } else {
        echo json_encode([]);
    }
}
?>