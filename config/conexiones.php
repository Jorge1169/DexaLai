<?php 
@session_start();
@extract($_REQUEST);
/*******************************
 * CONEXIONES A MYSQL
 *******************************/
$conn_mysql_R = '0'; //Resultados de conexion exitosa 1 no exitosa 0
// 4. Conexión principal a MySQL (Pruebas)
$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pass = "";
$mysql_dbname = "dexa_lai";

try {
// Conexión MySQL con mysqli
    $conn_mysql = mysqli_connect($mysql_host, $mysql_user, $mysql_pass, $mysql_dbname);
    $conn_mysql_R = '1'; // conexion exitosa
} catch (Exception $e) {
 die("Error en conexión a MySQL: " . mysqli_connect_error() . "<br>");
    $conn_mysql_R = '0'; // conexion xon error
}

// Incluir sistema de permisos
require_once __DIR__ . '/permisos.php';


// Función para conexión MySQL con configuración de caracteres
function getMysqlConnection() {
    global $mysql_host, $mysql_user, $mysql_pass, $mysql_dbname;
    
    $connection = mysqli_connect($mysql_host, $mysql_user, $mysql_pass, $mysql_dbname);
    
    if (!$connection) {
        die("Error en conexión a la base de datos: " . mysqli_connect_error());
        $conn_mysql_R = '0'; // conexion xon error
    }
    
    // Establecer codificación de caracteres
    $connection->set_charset("utf8mb4");
    
    return $connection;
}
// Función para limpiar datos para prevenir inyección SQL y XSS
function clear($var)
{
    return htmlspecialchars($var);
}

// Función para verificar si el usuario es un administrador
function check_admin()
{
    if (!isset($_SESSION['id'])) {
        redir("./");
    }
}

// Función para redireccionar
function redir($var)
{
    echo "<script>window.location = '$var';</script>";
    die();
}

// Función para mostrar alertas
function alert($txt, $type, $url) {
    // "error", "success" and "info".
    $t = ($type == 0) ? "error" : (($type == 1) ? "success" : "info");
    
    echo "<script>
    // Función para leer cookies
    function getCookie(name) {
        const value = `; \${document.cookie}`;
        const parts = value.split(`; \${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return 'light'; // Tema predeterminado si no hay cookie
    }
    
    const currentTheme = getCookie('theme') || 'light';
    
    Swal.fire({
        title: 'Alerta',
        text: '".addslashes($txt)."',
        icon: '$t',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
        // Configuración dinámica del tema
        theme: currentTheme,
        // Personalización de colores según el tema
        color: currentTheme === 'dark' ? '#ffffff' : '#212529',
        background: currentTheme === 'dark' ? '#111827' : '#ffffff',

        confirmButtonColor: currentTheme === 'dark' ? '#6f42c1' : '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location='?p=".$url."';
            }
            });
            </script>";
        }

        //===========
        // Permisos - La función permisos() ahora está en config/permisos.php
        // Mantiene compatibilidad hacia atrás pero usa el nuevo sistema
        //===========

        // ============================================================================
// SISTEMA UNIFICADO DE PERMISOS
// ============================================================================

function verificarPermiso($modulo, $conn_mysql, $permisosUsuario = null) {
    // 1. Verificar si el módulo está disponible para el tipo de zona
    if (!moduloDisponibleParaZona($modulo, $conn_mysql)) {
        return false;
    }
    
    // 2. Si se proporcionan permisos de usuario, verificar también
    if ($permisosUsuario !== null) {
        $permisosEspeciales = [
            'reportes' => 'REPORTES',
            'utilerias' => 'UTILERIAS',
            'usuarios' => 'ADMIN',
            'zonas' => 'ADMIN',
            'reportes_actividad' => 'ACT_AC',
            'ia_test' => 'ACT_AC',
            'contra_recibos' => 'REPORTES'
        ];
        
        if (isset($permisosEspeciales[$modulo])) {
            $permisoRequerido = $permisosEspeciales[$modulo];
            return strpos($permisosUsuario[$permisoRequerido], 'display: none') === false;
        }
    }
    
    return true;
}

function mostrarElementoMenu($modulo, $conn_mysql, $permisosUsuario = null) {
    $tienePermiso = verificarPermiso($modulo, $conn_mysql, $permisosUsuario);
    return $tienePermiso ? '' : 'style="display: none"';
}

function obtenerClaseMenu($modulo, $conn_mysql, $permisosUsuario = null) {
    $tienePermiso = verificarPermiso($modulo, $conn_mysql, $permisosUsuario);
    return $tienePermiso ? '' : 'd-none';
}

// funcion para convertir fechas
        function convertirFecha($fecha) {
            $fecha = trim($fecha);
            
            if (empty($fecha)) {
                return '0000-00-00';
            }
            
            try {
                $date = DateTime::createFromFormat('d/m/Y', $fecha);
                if ($date === false) {
                    return '0000-00-00';
                }
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                return '0000-00-00';
            }
        }


         $invoiceLK = "https://invoice.globaltycloud.com.mx/invoice/"; /// link de invoice
         $link = "http://localhost/DexaLai/doc/contra_recibos.php?id=";


// ============================================================================
// FUNCIÓN PARA REGISTRAR ACTIVIDADES - AGREGAR ESTA NUEVA FUNCIÓN
// ============================================================================
function logActivity($action, $description = '') {
    global $conn_mysql;
    
    $user_id = $_SESSION['id_cliente'] ?? 0;
    $username = $_SESSION['username'] ?? 'Invitado';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Limpiar los datos para prevenir inyección SQL
    $user_id = mysqli_real_escape_string($conn_mysql, $user_id);
    $username = mysqli_real_escape_string($conn_mysql, $username);
    $action = mysqli_real_escape_string($conn_mysql, $action);
    $description = mysqli_real_escape_string($conn_mysql, $description);
    $ip_address = mysqli_real_escape_string($conn_mysql, $ip_address);
    $user_agent = mysqli_real_escape_string($conn_mysql, $user_agent);
    
    $sql = "INSERT INTO user_activity_logs (user_id, username, action, description, ip_address, user_agent) 
            VALUES ('$user_id', '$username', '$action', '$description', '$ip_address', '$user_agent')";
    
    return mysqli_query($conn_mysql, $sql);
}

// ============================================================================
// FUNCIÓN PARA OBTENER LOGS (PARA REPORTES) - AGREGAR ESTA NUEVA FUNCIÓN
// ============================================================================
function getActivityLogs($filters = []) {
    global $conn_mysql;
    
    $sql = "SELECT * FROM user_activity_logs WHERE 1=1";
    
    // Aplicar filtros
    if (!empty($filters['user_id'])) {
        $user_id = mysqli_real_escape_string($conn_mysql, $filters['user_id']);
        $sql .= " AND user_id = '$user_id'";
    }
    
    if (!empty($filters['start_date'])) {
        $start_date = mysqli_real_escape_string($conn_mysql, $filters['start_date']);
        $sql .= " AND DATE(created_at) >= '$start_date'";
    }
    
    if (!empty($filters['end_date'])) {
        $end_date = mysqli_real_escape_string($conn_mysql, $filters['end_date']);
        $sql .= " AND DATE(created_at) <= '$end_date'";
    }
    
    if (!empty($filters['action'])) {
        $action = mysqli_real_escape_string($conn_mysql, $filters['action']);
        $sql .= " AND action LIKE '%$action%'";
    }
    
    if (!empty($filters['username'])) {
        $username = mysqli_real_escape_string($conn_mysql, $filters['username']);
        $sql .= " AND username LIKE '%$username%'";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    // Limitar resultados si se especifica
    if (!empty($filters['limit'])) {
        $limit = intval($filters['limit']);
        $sql .= " LIMIT $limit";
    }
    
    $result = mysqli_query($conn_mysql, $sql);
    $logs = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

// ============================================================================
// FUNCIÓN PARA INICIAR SESIÓN COMO OTRO USUARIO (SUDO)
// ============================================================================
function sudoLogin($target_user_id) {
    global $conn_mysql;

    
    // Obtener datos del usuario objetivo
    $targetQuery = $conn_mysql->prepare("SELECT * FROM usuarios WHERE id_user = ? AND status = 1");
    $targetQuery->bind_param('i', $target_user_id);
    $targetQuery->execute();
    $targetUser = $targetQuery->get_result()->fetch_assoc();
    
    if (!$targetUser) {
        return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
    }
    
    // Guardar la sesión original del administrador
    $_SESSION['original_admin'] = [
        'id_user' => $_SESSION['id_cliente'],
        'username' => $_SESSION['username'],
        'TipoUserSession' => $_SESSION['TipoUserSession']
    ];
    
    // Actualizar la sesión con los datos del usuario objetivo
    $_SESSION['id_cliente'] = $targetUser['id_user'];
    $_SESSION['username'] = $targetUser['usuario'];
    $_SESSION['TipoUserSession'] = $targetUser['tipo'];
    
    // Registrar la actividad
    $admin_username = $_SESSION['original_admin']['username'];
    logActivity('SUDO_LOGIN', "Admin $admin_username inició sesión como usuario: " . $targetUser['usuario']);
    
    return ['success' => true, 'message' => 'Sesión iniciada como ' . $targetUser['nombre']];
}

// ============================================================================
// FUNCIÓN PARA REGRESAR A LA SESIÓN ORIGINAL
// ============================================================================
function sudoLogout() {
    if (!isset($_SESSION['original_admin'])) {
        return ['success' => false, 'message' => 'No hay sesión sudo activa'];
    }
    
    $original_admin = $_SESSION['original_admin'];
    $current_username = $_SESSION['username'];
    
    // Restaurar sesión original
    $_SESSION['id_cliente'] = $original_admin['id_user'];
    $_SESSION['username'] = $original_admin['username'];
    $_SESSION['TipoUserSession'] = $original_admin['TipoUserSession'];
    
    // Limpiar datos de sudo
    unset($_SESSION['original_admin']);
    
    // Registrar la actividad
    logActivity('SUDO_LOGOUT', "Admin $original_admin[username] regresó a su sesión desde: $current_username");
    
    return ['success' => true, 'message' => 'Sesión restaurada'];
}

// ============================================================================
// FUNCIÓN PARA VERIFICAR SI ES UNA SESIÓN SUDO
// ============================================================================
function isSudoSession() {
    return isset($_SESSION['original_admin']);
}        
// ============================================================================
// CONTRASEÑA GENÉRICA - DETECCIÓN Y SEGURIDAD
// ============================================================================
define('GENERIC_PASSWORD', '12345');
define('GENERIC_PASSWORD_MD5', '827ccb0eea8a706c4c34a16891f84e7b');

// Función para verificar si el usuario tiene contraseña genérica
function hasGenericPassword($user_id) {
    global $conn_mysql;
    
    $query = $conn_mysql->prepare("SELECT pass FROM usuarios WHERE id_user = ?");
    $query->bind_param('i', $user_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['pass'] === GENERIC_PASSWORD_MD5;
    }
    
    return false;
}

// Función para cambiar la contraseña
function changePassword($user_id, $new_password) {
    global $conn_mysql;
    
    $hashed_password = md5($new_password);
    $query = $conn_mysql->prepare("UPDATE usuarios SET pass = ? WHERE id_user = ?");
    $query->bind_param('si', $hashed_password, $user_id);
    
    if ($query->execute()) {
        // Registrar el cambio de contraseña
        logActivity('PASSWORD_CHANGE', 'Usuario cambió su contraseña genérica');
        return true;
    }
    
    return false;
}
// Función para obtener el nombre de la zona actual
function obtenerNombreZona($zona_id, $conn_mysql) {
    if ($zona_id <= 0) {
        // Buscar la primera zona disponible
        $query = $conn_mysql->query("SELECT nom FROM zonas WHERE status = 1 ORDER BY id_zone LIMIT 1");
        if ($row = $query->fetch_assoc()) {
            return $row['nom'] . ' (default)';
        }
        return 'Sin zona asignada';
    }
    
    $query = $conn_mysql->prepare("SELECT nom FROM zonas WHERE id_zone = ?");
    $query->bind_param('i', $zona_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['nom'];
    }
    
    return 'Zona no encontrada';
}

// Función para obtener zonas disponibles para un usuario
function obtenerZonasDisponibles($user_id, $conn_mysql) {
    $query = $conn_mysql->prepare("SELECT zona FROM usuarios WHERE id_user = ?");
    $query->bind_param('i', $user_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['zona'] == '0' || empty($row['zona'])) {
            // Todas las zonas activas
            $queryZonas = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1 ORDER BY nom");
            $zonas = [];
            while ($zona = $queryZonas->fetch_assoc()) {
                $zonas[] = $zona;
            }
            return $zonas;
        } else {
            // Zonas específicas
            $zonasUsuario = explode(',', $row['zona']);
            if (empty($zonasUsuario)) return [];
            
            $placeholders = str_repeat('?,', count($zonasUsuario) - 1) . '?';
            $stmt = $conn_mysql->prepare("SELECT * FROM zonas WHERE id_zone IN ($placeholders) AND status = 1 ORDER BY nom");
            $stmt->bind_param(str_repeat('i', count($zonasUsuario)), ...$zonasUsuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $zonas = [];
            while ($zona = $result->fetch_assoc()) {
                $zonas[] = $zona;
            }
            return $zonas;
        }
    }
    
    return [];
}
// ============================================================================
// FUNCIONES PARA MANEJO DE TIPOS DE ZONAS
// ============================================================================

// Función para obtener el tipo de zona actual
function obtenerTipoZonaActual($conn_mysql) {
    if (!isset($_SESSION['selected_zone']) || $_SESSION['selected_zone'] <= 0) {
        return 'NOR'; // Tipo por defecto
    }
    
    $zone_id = intval($_SESSION['selected_zone']);
    $query = $conn_mysql->prepare("SELECT tipo FROM zonas WHERE id_zone = ?");
    $query->bind_param('i', $zone_id);
    $query->execute();
    $result = $query->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['tipo'] ?? 'NOR';
    }
    
    return 'NOR';
}

function esZonaMEOCompatible($tipoZona = null, $conn_mysql = null) {
    if ($tipoZona === null) {
        if ($conn_mysql === null) {
            return false;
        }
        $tipoZona = obtenerTipoZonaActual($conn_mysql);
    }

    return in_array($tipoZona, ['MEO', 'SUR'], true);
}

// Función para verificar si un módulo está disponible para el tipo de zona actual
function moduloDisponibleParaZona($modulo, $conn_mysql) {
    $tipoZona = obtenerTipoZonaActual($conn_mysql);
    
    // Definir qué módulos están disponibles para cada tipo de zona
    $modulosPorTipo = [
        'NOR' => ['inicio','clientes','V_cliente','E_Cliente','N_cliente','N_direccion','E_direccion', 
                'proveedores', 'V_proveedores', 'E_proveedor', 'N_proveedor', 'N_direccion_p','E_direccion_p', 
                'transportes','V_transporte','E_transportista','N_transportista', 'subir_precios_masivo',
                'productos', 'reporte_precios', 'V_producto','E_producto','N_producto',
                'recoleccion', 'V_recoleccion','E_recoleccion','N_recoleccion','correoPF.php',
                'contra_recibos',
                'reporte_recole', 
                'importar_recolecciones', 
                'reportes_actividad',
                //'ia_test', 
                'zonas','N_zona','E_zona',
                'usuarios', 'V_usuarios', 'E_usuario', 'N_usuario','sudo_login','sudo_logout','salir'],
        
        'MEO' => ['inicio','clientes','V_cliente','E_Cliente','N_cliente','N_direccion','E_direccion',
                  'proveedores', 'V_proveedores', 'E_proveedor', 'N_proveedor', 'N_direccion_p','E_direccion_p', 
                  'transportes','V_transporte','E_transportista','N_transportista', 'subir_precios_masivo',
                  'productos', 'reporte_precios', 'V_producto','E_producto','N_producto',
                  'captacion','N_captacion', 'usuarios','V_captacion','E_captacion',
                  'almacenes_info','V_detalle_almacen',
                  'V_usuarios', 'E_usuario', 'N_usuario',
                  'almacenes','N_almacen','V_almacen','E_almacen','N_direccion_almacen','E_direccion_almacen',
                  'ventas','N_venta','V_venta','E_venta',
                  'reportes_actividad',
                  'zonas','N_zona','E_zona',
                  'usuarios', 'V_usuarios', 'E_usuario', 'N_usuario','sudo_login','sudo_logout','salir'],

        'SUR' => ['inicio','clientes','V_cliente','E_Cliente','N_cliente','N_direccion','E_direccion',
                  'proveedores', 'V_proveedores', 'E_proveedor', 'N_proveedor', 'N_direccion_p','E_direccion_p', 
                  'transportes','V_transporte','E_transportista','N_transportista', 'subir_precios_masivo',
                  'productos', 'reporte_precios', 'V_producto','E_producto','N_producto',
                  'captacion','N_captacion', 'usuarios','V_captacion','E_captacion',
                  'almacenes_info','V_detalle_almacen',
                  'V_usuarios', 'E_usuario', 'N_usuario',
                  'almacenes','N_almacen','V_almacen','E_almacen','N_direccion_almacen','E_direccion_almacen',
                  'ventas','N_venta','V_venta','E_venta',
                  'reportes_actividad',
                  'zonas','N_zona','E_zona',
                  'usuarios', 'V_usuarios', 'E_usuario', 'N_usuario','sudo_login','sudo_logout','salir'],
        
        // Puedes agregar más tipos aquí
    ];
    
    // Si el tipo de zona no está definido, usar NOR por defecto
    if (!isset($modulosPorTipo[$tipoZona])) {
        $tipoZona = 'NOR';
    }
    
    return in_array($modulo, $modulosPorTipo[$tipoZona]);
}

// Función para obtener la URL correcta según el tipo de zona
function obtenerUrlSegunZona($moduloBase, $conn_mysql) {
    $tipoZona = obtenerTipoZonaActual($conn_mysql);
    
    // Mapeo de módulos base a módulos específicos por zona
    $mapeoModulos = [
        'NOR' => [
            'recoleccion' => 'recoleccion',
            'reporte_recole' => 'reporte_recole',
        ],
        'MEO' => [],
        'SUR' => [],
    ];
    
    // Si hay un mapeo específico para este tipo de zona, usarlo
    if (isset($mapeoModulos[$tipoZona]) && isset($mapeoModulos[$tipoZona][$moduloBase])) {
        return $mapeoModulos[$tipoZona][$moduloBase];
    }
    
    // Si no, usar el módulo base
    return $moduloBase;
}

// Función para verificar si una sección del menú debe mostrarse
function mostrarSeccionMenu($seccion, $conn_mysql) {
    $tipoZona = obtenerTipoZonaActual($conn_mysql);
    
    // Definir qué secciones mostrar para cada tipo de zona
    $seccionesPorTipo = [
        'NOR' => ['catalogos', 'flujo', 'reportes', 'utilerias', 'usuarios'],
        'MEO' => ['catalogos', 'flujo','reportes', 'utilerias', 'usuarios'],
        'SUR' => ['catalogos', 'flujo','reportes', 'utilerias', 'usuarios'],
        // 'MEO' no muestra 'utilerias'
    ];
    
    // Si el tipo de zona no está definido, usar NOR por defecto
    if (!isset($seccionesPorTipo[$tipoZona])) {
        $tipoZona = 'NOR';
    }
    
    return in_array($seccion, $seccionesPorTipo[$tipoZona]);
}
?>
