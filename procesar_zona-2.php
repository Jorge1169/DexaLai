<?php
session_start();
require_once 'config/conexiones.php';

// Verificar si el usuario tiene permiso para cambiar de zona
if (!isset($_SESSION['id_cliente'])) {
    // Si es AJAX, responder con JSON
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit();
    } else {
        header('Location: ?p=inicio');
        exit();
    }
}

// Obtener datos del usuario
$user_query = mysqli_query($conn_mysql, "SELECT zona FROM usuarios WHERE id_user = '".$_SESSION['id_cliente']."'");
$user_data = mysqli_fetch_assoc($user_query);
$zona_user = $user_data['zona'];

// *** CAMBIO 1: Comparar como string en lugar de número ***
// Cambiar esto:
// if ($zona_user != 0) {
// Por esto:
if ($zona_user !== '0') {
    $_SESSION['selected_zone'] = $zona_user;
    
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para cambiar zonas']);
        exit();
    } else {
        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?p=inicio';
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Procesar cambio de zona solo si el usuario tiene permiso (zona_user = 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_zone'])) {
    $selected_zone = mysqli_real_escape_string($conn_mysql, $_POST['selected_zone']);
    
    // Validar que la zona seleccionada exista
    if ($selected_zone === '0') {
        $_SESSION['selected_zone'] = '0';
    } else {
        $query = "SELECT id_zone FROM zonas WHERE id_zone = '$selected_zone' AND status = 1";
        $result = mysqli_query($conn_mysql, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $_SESSION['selected_zone'] = $selected_zone;
        } else {
            $_SESSION['selected_zone'] = '0';
        }
        
        if ($result) mysqli_free_result($result);
    }
    
    // Marcar que ya se seleccionó zona inicial (si viene del modal)
    if (isset($_POST['zona_inicial']) && $_POST['zona_inicial'] == 'true') {
        $_SESSION['zona_inicial_seleccionada'] = true;
    }
    
    // Si es una solicitud AJAX (del modal), responder con JSON
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Zona seleccionada correctamente',
            'zona' => $_SESSION['selected_zone']
        ]);
        exit();
    }
}

// Para marcar zona inicial sin cambiar (cuando el modal se cierra)
if (isset($_GET['set_initial']) && $_GET['set_initial'] == 'true') {
    $_SESSION['zona_inicial_seleccionada'] = true;
    
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'true') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Zona inicial configurada']);
        exit();
    } else {
        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?p=inicio';
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Redirigir de vuelta (para solicitudes normales del select)
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?p=inicio';
header('Location: ' . $redirect_url);
exit();
?>