<?php
// config
include "../config/conexiones.php";

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_POST['id'])) {
    $id_direccion = clear($_POST['id']);
    
    try {
        // Cambiar status a 0 en lugar de eliminar físicamente
        $sql = "UPDATE direcciones SET status = '0' WHERE id_direc = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param('i', $id_direccion);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Dirección eliminada correctamente';
        } else {
            $response['message'] = 'No se pudo eliminar la dirección';
        }
    } catch (mysqli_sql_exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'ID no proporcionado';
}

echo json_encode($response);
?>