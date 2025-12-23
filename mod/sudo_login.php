<?php
// mod/sudo_login.php

// Verificar que es una petición POST y tiene permisos de admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alert("Método no permitido", 0, "usuarios");
    exit();
}

if ($TipoUserSession != 100) {
    alert("No tienes permisos para realizar esta acción", 0, "usuarios");
    logActivity('SUDO_ATTEMPT', 'Intento no autorizado de sudo login');
    exit();
}

// Obtener y validar el ID del usuario objetivo
$target_user_id = $_POST['target_user'] ?? 0;
if (!$target_user_id) {
    alert("ID de usuario no válido", 0, "usuarios");
    exit();
}

// Evitar que un admin inicie sesión como sí mismo
if ($target_user_id == $idUser) {
    alert("No puedes iniciar sesión como tú mismo", 0, "V_usuarios&id=" . $target_user_id);
    exit();
}

// Ejecutar el sudo login
$result = sudoLogin($target_user_id);

if ($result['success']) {
    alert($result['message'], 1, "inicio");
} else {
    alert($result['message'], 0, "V_usuarios&id=" . $target_user_id);
}
?>