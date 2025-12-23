<?php
session_start();
require_once 'config/conexiones.php';

// Verificar si el usuario tiene permiso para cambiar de zona
if (!isset($_SESSION['id_cliente'])) {
    header('Location: ?p=inicio');
    exit();
}

// Obtener datos del usuario
$user_id = $_SESSION['id_cliente'];
$user_query = $conn_mysql->prepare("SELECT zona, zona_adm FROM usuarios WHERE id_user = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    header('Location: ?p=login');
    exit();
}

$zona_user = $user_data['zona'];
$zona_adm = $user_data['zona_adm'];

// Determinar zonas permitidas
$zonasPermitidas = [];
if ($zona_user == '0' || empty($zona_user)) {
    $zonasPermitidas = 'todas';
} else {
    $zonasPermitidas = explode(',', $zona_user);
}

// Procesar cambio de zona
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_zone'])) {
    $selected_zone = intval($_POST['selected_zone']);
    
    // Validar que la zona seleccionada sea mayor a 0 (ya que no existe la zona 0)
    if ($selected_zone <= 0) {
        // Si alguien intenta enviar zona 0 o negativa, buscar una zona válida
        if ($zonasPermitidas === 'todas') {
            // Buscar primera zona disponible
            $zones_query = mysqli_query($conn_mysql, "SELECT id_zone FROM zonas WHERE status = 1 ORDER BY id_zone LIMIT 1");
            if ($zone = mysqli_fetch_assoc($zones_query)) {
                $selected_zone = $zone['id_zone'];
            }
        } elseif (!empty($zonasPermitidas)) {
            $selected_zone = $zonasPermitidas[0];
        }
    }
    
    // Validar la zona seleccionada según las zonas permitidas
    $zona_valida = false;
    
    if ($zonasPermitidas === 'todas') {
        // Usuario puede acceder a todas las zonas
        // Verificar que la zona exista y esté activa
        $query = $conn_mysql->prepare("SELECT id_zone FROM zonas WHERE id_zone = ? AND status = 1");
        $query->bind_param('i', $selected_zone);
        $query->execute();
        $result = $query->get_result();
        
        if ($result->num_rows > 0) {
            $zona_valida = true;
            $_SESSION['selected_zone'] = $selected_zone;
        }
    } else {
        // Usuario solo puede acceder a zonas específicas
        if (in_array($selected_zone, $zonasPermitidas)) {
            // Verificar que la zona exista y esté activa
            $query = $conn_mysql->prepare("SELECT id_zone FROM zonas WHERE id_zone = ? AND status = 1");
            $query->bind_param('i', $selected_zone);
            $query->execute();
            $result = $query->get_result();
            
            if ($result->num_rows > 0) {
                $zona_valida = true;
                $_SESSION['selected_zone'] = $selected_zone;
            }
        }
    }
    
    // Si la zona no es válida, usar la primera zona permitida
    if (!$zona_valida) {
        if ($zonasPermitidas === 'todas') {
            // Buscar primera zona disponible
            $zones_query = mysqli_query($conn_mysql, "SELECT id_zone FROM zonas WHERE status = 1 ORDER BY id_zone LIMIT 1");
            if ($zone = mysqli_fetch_assoc($zones_query)) {
                $_SESSION['selected_zone'] = $zone['id_zone'];
            }
        } elseif (!empty($zonasPermitidas)) {
            $_SESSION['selected_zone'] = $zonasPermitidas[0];
        }
    }
    
    // Registrar el cambio de zona
    logActivity('ZONA_CAMBIO', 'Cambió de zona a: ' . $_SESSION['selected_zone']);
}

// Redirigir de vuelta
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?p=inicio';
header('Location: ' . $redirect_url);
exit();
?>