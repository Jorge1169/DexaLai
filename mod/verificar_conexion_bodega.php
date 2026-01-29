<?php
@session_start();
set_time_limit(300); // permite hasta 5 minutos de ejecución

$bodega = $_GET['bodega'] ?? '';
$_SESSION['bodega_seleccionada'] = $bodega;
$_SESSION['ip_bodega'] = ''; // Inicializar

// Obtener bodega seleccionada
$bodega = $_GET['bodega'] ?? '';

// 1. Conectar a BD de usuarios primero
$usersdb_server = "192.168.1.31\sqldesa";
$usersdb_name = "BD Usuarios";
$usersdb_user = "sa";
$usersdb_pass = "ADMINPG";

try {
    $dsn = "sqlsrv:Server=$usersdb_server;Database=$usersdb_name";
    $conn_usersdb = new PDO($dsn, $usersdb_user, $usersdb_pass);
    $conn_usersdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     $_SESSION['error_conexion'] = 'Error al conectar con BD Usuarios';
    header('Location: '.$_SERVER['HTTP_REFERER']);
    exit;
}

// 2. Obtener IP y probar conexión
$ip = obtenerIPBodega($bodega, $conn_usersdb);
$conexionExitosa = $ip ? probarConexion($ip) : false;

// 3. Actualizar la sesión con el estado real
$_SESSION['conexion_bodega'] = $conexionExitosa ? '1' : '0';


// 2. Funciones
function obtenerIPBodega($codigoBodega, $conn) {
    $stmt = $conn->prepare("SELECT IP FROM regiones_y_zonas WHERE CodigoBodega = :codigo");
    $stmt->bindParam(':codigo', $codigoBodega);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['IP'] ?? null;
}

function probarConexion($ip) {
    $server = "{$ip}\\sqlbodega";
    $dbname = "BD MELO";
    $user = "sa";
    $pass = "corpo";

    try {
        // Timeout de 30 segundos
        $dsn = "sqlsrv:Server=$server;Database=$dbname";
        $conn = new PDO($dsn, $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

         // Consulta de prueba real
        $stmt = $conn->query("SELECT TOP 1 1 AS test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['test']) && $result['test'] == 1;
        return true;
    } catch (PDOException $e) {
         //error_log("Error de conexión a bodega: " . $e->getMessage());
        return false;
    }
}

// 3. Si se pidió verificar todas las bodegas
if ($bodega === '*') {
  $_SESSION['bodega_seleccionada'] = 'Todas las bodegas';
    $_SESSION['ip_bodega'] = 'Multiples IPs';
    $_SESSION['conexion_bodega'] = '1';
    header('Location: '.$_SERVER['HTTP_REFERER']);
    exit;
}



// 4. Si solo se pidió una bodega
if ($bodega) {
$ip = obtenerIPBodega($bodega, $conn_usersdb);
$_SESSION['ip_bodega'] = $ip; // Guardar también la IP
if ($ip) {
    $conexionExitosa = probarConexion($ip);

    $color = $conexionExitosa ? 'success' : 'danger';
    $mensaje = $conexionExitosa ? 'Exitoso' : 'Fallido';
    $icon = $conexionExitosa ? 'check-circle-fill' : 'x-circle-fill';

    /*echo '
    <div class="col-12 col-md-4">
      <div class="card text-bg-' . $color . ' text-white">
        <div class="card-body">
          <h6 class="card-title">
            <i class="bi bi-' . $icon . ' me-2"></i> Conexión a Bodega: <strong>' . $bodega . '</strong>
          </h6>
          <p class="card-text mb-0">IP: ' . $ip . '</p>
          <p class="card-text">Estado: <strong>' . $mensaje . '</strong></p>
        </div>
      </div>
    </div>';*/
} else {

    //echo '<div class="col-12"><div class="card text-bg-warning"><div class="card-body">No se encontró IP para la bodega seleccionada.</div></div></div>';
}
}
?>
