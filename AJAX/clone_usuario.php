<?php
header('Content-Type: application/json');
// Incluir archivos de configuración
require_once __DIR__ . '/../config/conexiones.php';
require_once __DIR__ . '/../config/BusinessContext.php';

// Asegurar sesión / permisos
if (session_status() === PHP_SESSION_NONE) session_start();

$TipoUserSession = $TipoUserSession ?? ($_SESSION['TipoUserSession'] ?? ($_SESSION['TipoUser'] ?? 0));
if ($TipoUserSession != 100) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para clonar usuarios']);
    exit;
}

$source_id = intval($_POST['source_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');

if (!$source_id || !$nombre || !$correo || !$usuario) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

// Verificar que usuario o correo no existan
$chk = $conn_mysql->prepare("SELECT id_user FROM usuarios WHERE usuario = ? OR correo = ? LIMIT 1");
$chk->bind_param('ss', $usuario, $correo);
$chk->execute();
$resChk = $chk->get_result();
if ($resChk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'El nombre de usuario o correo ya existe']);
    exit;
}

// Obtener usuario origen
$stmt = $conn_mysql->prepare("SELECT * FROM usuarios WHERE id_user = ? LIMIT 1");
$stmt->bind_param('i', $source_id);
$stmt->execute();
$orig = $stmt->get_result()->fetch_assoc();
if (!$orig) {
    echo json_encode(['success' => false, 'message' => 'Usuario origen no encontrado']);
    exit;
}

// Construir datos a insertar: copiar tipo, zona y permisos del origen
$UsuarioData = [];
$UsuarioData['nombre'] = $nombre;
$UsuarioData['correo'] = $correo;
$UsuarioData['usuario'] = $usuario;
$UsuarioData['pass'] = md5('12345');
$UsuarioData['tipo'] = $orig['tipo'];
$UsuarioData['zona'] = $orig['zona'] ?? '0';

// Copiar permisos definidos en PERMISOS_CATALOGO si existe
if (defined('PERMISOS_CATALOGO') && is_array(PERMISOS_CATALOGO)) {
    foreach (PERMISOS_CATALOGO as $nombrePerm => $config) {
        $columna = $config['columna'];
        $UsuarioData[$columna] = isset($orig[$columna]) ? $orig[$columna] : 0;
    }
} else {
    // Fallback: intentar copiar columnas comunes si existen
    $possible = ['zona_adm','ADMIN','Clien_Crear'];
    foreach ($possible as $col) {
        if (isset($orig[$col])) $UsuarioData[$col] = $orig[$col];
    }
}

// Asegurar status activo por defecto
$UsuarioData['status'] = 1;

// Preparar inserción
$columns = implode(', ', array_keys($UsuarioData));
$placeholders = rtrim(str_repeat('?,', count($UsuarioData)), ',');
$sql = "INSERT INTO usuarios ($columns) VALUES ($placeholders)";
$stmtIns = $conn_mysql->prepare($sql);
if (!$stmtIns) {
    echo json_encode(['success' => false, 'message' => 'Error interno al preparar la consulta']);
    exit;
}

$types = str_repeat('s', count($UsuarioData));
$values = array_values($UsuarioData);
$stmtIns->bind_param($types, ...$values);
try {
    $stmtIns->execute();
    if ($stmtIns->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuario clonado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el usuario']);
    }
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
}

exit;

?>