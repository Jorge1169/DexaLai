<?php
// vamos a buscar el cliente en las recolecciones y solo traeremos de LAISA, BIDASOA Y DESA
require_once 'config/conexiones.php';

// 1. DEFINIR LA FUNCIÓN FUERA DEL BUCLE
function fetchRemision(string $apiBaseUrl, string $remision, string $empresa, bool $debug = false): array {
    // Construir URL como especificaste: ?remision=XXX&empresa=YYY
    $query = http_build_query([
        'remision' => $remision,
        'empresa' => $empresa
    ]);
    
    // Asegurar que la URL termine exactamente como necesitas
    $url = rtrim($apiBaseUrl, "/") . '/remisiones-dexalai.aspx?' . $query;
    
    // Debug: ver URL generada
    if ($debug) {
        echo "<!-- URL generada: " . htmlspecialchars($url) . " -->\n";
    }
    
    $ch = curl_init($url);
    
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false, // Temporal para desarrollo
        CURLOPT_SSL_VERIFYHOST => false, // Temporal para desarrollo
    ];
    
    curl_setopt_array($ch, $options);
    
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($resp === false) {
        return [
            'success' => false,
            'error' => "cURL error ($errno): $err",
            'DOCUMENTO' => '',
            'FOLIO' => '',
            'NETO' => ''
        ];
    }
    
    if ($http >= 400) {
        return [
            'success' => false,
            'error' => "HTTP $http",
            'DOCUMENTO' => '',
            'FOLIO' => '',
            'NETO' => ''
        ];
    }
    
    $data = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'JSON inválido: ' . json_last_error_msg(),
            'raw' => substr($resp, 0, 200), // Primeros 200 chars para debug
            'DOCUMENTO' => '',
            'FOLIO' => '',
            'NETO' => ''
        ];
    }
    
    // Manejar diferentes formatos de respuesta
    if (isset($data['result']) && is_array($data['result'])) {
        $result = $data['result'];
    } else {
        $result = $data;
    }
    
    $documento = isset($result['DOCUMENTO']) ? (string)$result['DOCUMENTO'] : '';
    $folio     = isset($result['FOLIO'])     ? (string)$result['FOLIO']     : '';
    $neto      = isset($result['NETO'])      ? (string)$result['NETO']      : '';
    
    return [
        'success' => true,
        'DOCUMENTO' => $documento,
        'FOLIO' => $folio,
        'NETO' => $neto
    ];
}

// 2. VALIDAR Y SANITIZAR ENTRADA
$zona = $_POST['zona'] ?? '';
if (empty($zona)) {
    die('<div class="alert alert-danger">Error: Zona no especificada</div>');
}

// 3. PREPARAR CONSULTA SEGURA (EVITA SQL INJECTION)
$query_recolecciones = "SELECT r.*, r.remision, c.nombre as nombre_cliente
                        FROM recoleccion r
                        LEFT JOIN clientes c ON r.id_cli = c.id_cli
                        WHERE c.zona = ? 
                          AND r.status = 1 
                          AND c.nombre IN ('LAISA', 'BIDASOA', 'DEXA') 
                          AND r.peso_conpro IS NULL 
                          AND r.remision != ''
                          AND r.remision IS NOT NULL";

$stmt_recolecciones = $conn_mysql->prepare($query_recolecciones);
$stmt_recolecciones->bind_param('s', $zona);
$stmt_recolecciones->execute();
$result_recolecciones = $stmt_recolecciones->get_result();

// 4. URL BASE DE LA API

if ($result_recolecciones->num_rows > 0) {
    echo '<div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID Recolección</th>
                        <th>ID Cliente</th>
                        <th>Cliente</th>
                        <th>Fecha Recolección</th>
                        <th>Detalles</th>
                        <th>Remisión</th>
                        <th>Peso del ticket</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
    
    while ($row = $result_recolecciones->fetch_assoc()) {
        // Determinar nombre de la planta para la API
        $nombre_cliente = $row['nombre_cliente'];

        if ($nombre_cliente == 'DEXA') {

            $nombre_planta = 'DESA'; // API espera DESA, no DEXA
            $apiBaseUrl = 'https://globaltycloud.com.mx:4013/';
        } else if  ($nombre_cliente == 'BIDASOA') {

            $nombre_planta = 'BIDASOA';
            $apiBaseUrl = 'https://globaltycloud.com.mx:4012/';
            
        } else {
            $nombre_planta = $nombre_cliente;
            $apiBaseUrl = 'https://globaltycloud.com.mx:4013/';
        }
        // Verificar que tenemos remisión
        $remision = trim($row['remision']);
        if (empty($remision)) {
            $peso_ticket = '';
            $estado = '<span class="badge bg-warning">Sin remisión</span>';
        } else {
            // 5. LLAMAR A LA API CON LOS PARÁMETROS CORRECTOS
            try {
                $resultado_api = fetchRemision($apiBaseUrl, $remision, $nombre_planta, false);
                
                if ($resultado_api['success']) {
                    if (!empty($resultado_api['NETO'])) {
                        $peso_ticket = $resultado_api['NETO'];
                        $estado = '<span class="badge bg-success">Encontrado</span>';
                        
                        // OPCIONAL: Guardar el peso en la base de datos
                        $update = $conn_mysql->prepare("UPDATE recoleccion SET peso_conpro = ?, remi_compro = 1 WHERE id_recol = ?");
                        $update->bind_param('si', $peso_ticket, $row['id_recol']);
                        $update->execute();
                    } else {
                        $peso_ticket = '';
                        $estado = '<span class="badge bg-warning">Sin peso</span>';
                    }
                } else {
                    $peso_ticket = '';
                    $estado = '<span class="badge bg-danger" title="' . htmlspecialchars($resultado_api['error']) . '">Error API</span>';
                }
                
            } catch (Exception $ex) {
                $peso_ticket = '';
                $estado = '<span class="badge bg-danger">Excepción</span>';
                error_log("Error consultando API: " . $ex->getMessage());
            }
        }
        
        // 6. MOSTRAR FILA EN LA TABLA
        echo '<tr>
                <td>' . htmlspecialchars($row['id_recol']) . '</td>
                <td>' . htmlspecialchars($row['id_cli']) . '</td>
                <td>' . htmlspecialchars($nombre_cliente) . '</td>
                <td>' . htmlspecialchars($row['fecha_r']) . '</td>
                <td>' . htmlspecialchars($row['folio'] ?? '') . '</td>
                <td>' . htmlspecialchars($remision) . '</td>
                <td>' . htmlspecialchars($peso_ticket) . '</td>
                <td>' . $estado . '</td>
              </tr>';
    }
    
    echo '      </tbody>
            </table>
          </div>';
} else {
    echo '<div class="alert alert-warning">No se encontraron recolecciones para la zona seleccionada.</div>';
}

// Cerrar conexión
$stmt_recolecciones->close();
?>