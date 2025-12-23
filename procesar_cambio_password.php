<?php
session_start(); // Asegúrate de que esto esté al inicio
include "config/conexiones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

if (!isset($_SESSION['id_cliente'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

$user_id = $_SESSION['id_cliente'];
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Validaciones
if (empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
    exit;
}

// Verificar que no sea la contraseña genérica
if ($new_password === GENERIC_PASSWORD) {
    echo json_encode(['success' => false, 'message' => 'No puedes usar la contraseña genérica. Elige una contraseña diferente.']);
    exit;
}

// Cambiar la contraseña
if (changePassword($user_id, $new_password)) {
    // IMPORTANTE: Marcar que la contraseña fue cambiada exitosamente
    $_SESSION['password_changed_successfully'] = true;
    
    // También podemos guardar un timestamp para seguimiento
    $_SESSION['password_change_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Contraseña cambiada exitosamente. La próxima vez que inicies sesión usa tu nueva contraseña.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al cambiar la contraseña. Intenta nuevamente.']);
}
?>