<?php
session_start();
include "config/conexiones.php";

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_cliente'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Verificar que el usuario tenga permiso para todas las zonas
$user_id = $_SESSION['id_cliente'];
$user_query = mysqli_query($conn_mysql, "SELECT zona FROM usuarios WHERE id_user = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

if ($user_data['zona'] != 0) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para cambiar zonas']);
    exit;
}

// Validar y procesar la zona seleccionada
if (isset($_POST['selected_zone'])) {
    $selected_zone = mysqli_real_escape_string($conn_mysql, $_POST['selected_zone']);
    
    // Validar que la zona exista (si no es 0)
    if ($selected_zone != 0) {
        $zone_query = mysqli_query($conn_mysql, "SELECT id_zone FROM zonas WHERE id_zone = '$selected_zone' AND status = 1");
        if (mysqli_num_rows($zone_query) === 0) {
            echo json_encode(['success' => false, 'message' => 'Zona no válida']);
            exit;
        }
    }
    
    // Guardar en sesión
    $_SESSION['selected_zone'] = $selected_zone;
    $_SESSION['zona_inicial_seleccionada'] = true;
    
    // Registrar la actividad
    logActivity('ZONA_SELECCIONADA', 'Usuario seleccionó zona inicial: ' . $selected_zone);
    
    echo json_encode(['success' => true, 'message' => 'Zona seleccionada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ninguna zona']);
}
?>