<?php
require_once '../config/conexiones.php';

header('Content-Type: application/json');

$id_captacion = $_POST['id_captacion'] ?? 0;
$id_detalle = $_POST['id_detalle'] ?? 0;
$tipo = $_POST['tipo'] ?? '';
$numero = trim($_POST['numero'] ?? '');
$id_proveedor = $_POST['id_proveedor'] ?? 0;

if (empty($numero) || empty($tipo) || $id_captacion <= 0) {
    echo json_encode(['existe' => false]);
    exit;
}

$campo = '';
switch($tipo) {
    case 'ticket': $campo = 'numero_ticket'; break;
    case 'bascula': $campo = 'numero_bascula'; break;
    case 'factura': $campo = 'numero_factura'; break;
    default: 
        echo json_encode(['existe' => false]);
        exit;
}

// Buscar duplicados con el mismo proveedor
$sql = "SELECT cd.id_detalle 
        FROM captacion_detalle cd
        INNER JOIN captacion c ON cd.id_captacion = c.id_captacion
        WHERE cd.$campo = ? 
        AND cd.id_detalle != ? 
        AND cd.status = 1 
        AND c.id_prov = ? 
        AND cd.id_detalle IS NOT NULL";

$stmt = $conn_mysql->prepare($sql);
$stmt->bind_param('sii', $numero, $id_detalle, $id_proveedor);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['existe' => $result->num_rows > 0]);
?>