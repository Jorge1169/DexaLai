<?php
require_once '../config/conexiones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_recoleccion = $_POST['id_recoleccion'];
    $tipo = $_POST['tipo'];
    
    if ($tipo === 'proveedor') {
        $remision = $_POST['remision'];
        $peso_proveedor = $_POST['peso_proveedor'];
        
        $query = "UPDATE recoleccion SET remision = ?, peso_prov = ? WHERE id_recol = ?";
        $stmt = $conn_mysql->prepare($query);
        $stmt->bind_param("sdi", $remision, $peso_proveedor, $id_recoleccion);
        
    } elseif ($tipo === 'fletero') {
        $factura_flete = $_POST['factura_flete'];
        $peso_flete = $_POST['peso_flete'];
        
        $query = "UPDATE recoleccion SET factura_fle = ?, peso_fle = ? WHERE id_recol = ?";
        $stmt = $conn_mysql->prepare($query);
        $stmt->bind_param("sdi", $factura_flete, $peso_flete, $id_recoleccion);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
            'tipo' => $tipo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar los datos: ' . $conn_mysql->error
        ]);
    }
    
    $stmt->close();
    exit;
}