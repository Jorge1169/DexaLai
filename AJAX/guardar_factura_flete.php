<?php
require_once '../config/conexiones.php';

header('Content-Type: application/json');

$id_captacion = $_POST['id_captacion'] ?? 0;
$id_flete = $_POST['id_flete'] ?? 0;
$numero_factura_flete = trim($_POST['numero_factura_flete'] ?? '');
$validar_duplicado = $_POST['validar_duplicado'] ?? 0;

if ($id_captacion <= 0 || $id_flete <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

if (empty($numero_factura_flete)) {
    echo json_encode(['success' => false, 'message' => 'El número de factura es requerido']);
    exit;
}

// Validar duplicado si se solicita
if ($validar_duplicado) {
    // Obtener el ID del fletero de esta captación
    $sql_fletero = "SELECT id_transp FROM captacion WHERE id_captacion = ?";
    $stmt = $conn_mysql->prepare($sql_fletero);
    $stmt->bind_param('i', $id_captacion);
    $stmt->execute();
    $result = $stmt->get_result();
    $captacion = $result->fetch_assoc();
    $id_fletero = $captacion['id_transp'] ?? 0;
    
    // Verificar si existe duplicado con el mismo fletero
    $sql_duplicado = "SELECT cf.id_flete 
                      FROM captacion_flete cf
                      INNER JOIN captacion c ON cf.id_captacion = c.id_captacion
                      WHERE cf.numero_factura_flete = ? 
                      AND cf.id_flete != ? 
                      AND c.id_transp = ?";
    
    $stmt = $conn_mysql->prepare($sql_duplicado);
    $stmt->bind_param('sii', $numero_factura_flete, $id_flete, $id_fletero);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El número de factura ya existe para este fletero']);
        exit;
    }
}

// Actualizar la factura
$sql = "UPDATE captacion_flete 
        SET numero_factura_flete = ? 
        WHERE id_flete = ? AND id_captacion = ?";

$stmt = $conn_mysql->prepare($sql);
$stmt->bind_param('sii', $numero_factura_flete, $id_flete, $id_captacion);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Factura de flete actualizada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la factura']);
}
?>