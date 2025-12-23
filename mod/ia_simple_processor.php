<?php
require_once __DIR__ . '/../config/groq_key.php';
require_once __DIR__ . '/../config/conexiones.php';

class SimpleAIProcessor {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function processQuery($userMessage) {
        $sql = $this->generateSimpleSQL($userMessage);
        $data = $this->executeQuery($sql);
        
        return [
            'data' => $data,
            'sql' => $sql,
            'results_count' => $data ? count($data) : 0
        ];
    }
    
    private function generateSimpleSQL($query) {
        $query = strtolower(trim($query));
        
        $baseSQL = "SELECT 
            r.id_recol,
            CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
            r.fecha_r,
            p.rs as proveedor,
            t.razon_so as fletero,
            c.nombre as cliente,
            pr.nom_pro as producto,
            r.remision,
            r.factura_fle,
            r.peso_prov,
            r.peso_fle,
            r.status,
            pc.precio as precio_compra,
            pv.precio as precio_venta,
            pf.precio as precio_flete,
            pf.tipo as tipo_flete
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN clientes c ON r.id_cli = c.id_cli
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
        LEFT JOIN zonas z ON r.zona = z.id_zone
        LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
        LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
        LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
        WHERE r.status = 1";
        
        // Detectar filtros básicos
        $conditions = [];
        
        // Filtro por mes
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        
        foreach ($meses as $mes => $numero) {
            if (strpos($query, $mes) !== false) {
                $conditions[] = "MONTH(r.fecha_r) = $numero AND YEAR(r.fecha_r) = YEAR(CURDATE())";
                break;
            }
        }
        
        // Filtro sin factura
        if (strpos($query, 'sin factura') !== false) {
            $conditions[] = "(r.factura_fle IS NULL OR r.factura_fle = '')";
        }
        
        // Filtro por tipo de flete
        if (strpos($query, 'tipo viaje') !== false || strpos($query, 'por viaje') !== false) {
            $conditions[] = "pf.tipo = 'FV'";
        }
        
        if (strpos($query, 'tipo tonelada') !== false || strpos($query, 'por tonelada') !== false) {
            $conditions[] = "pf.tipo = 'FT'";
        }
        
        // Filtro por cliente
        if (preg_match('/cliente\s+([^\d\s][^,.\?]+)/i', $query, $matches)) {
            $cliente = $this->conn->real_escape_string(trim($matches[1]));
            $conditions[] = "c.nombre LIKE '%$cliente%'";
        }
        
        // Filtro por proveedor
        if (preg_match('/proveedor\s+([^\d\s][^,.\?]+)/i', $query, $matches)) {
            $proveedor = $this->conn->real_escape_string(trim($matches[1]));
            $conditions[] = "p.rs LIKE '%$proveedor%'";
        }
        
        // Agregar condiciones si existen
        if (!empty($conditions)) {
            $baseSQL .= " AND " . implode(" AND ", $conditions);
        }
        
        // Ordenamiento por defecto
        $baseSQL .= " ORDER BY r.fecha_r DESC LIMIT 50";
        
        return $baseSQL;
    }
    
    private function executeQuery($sql) {
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("SQL Error: " . $this->conn->error . " - SQL: " . $sql);
            return null;
        }
        
        if ($result->num_rows > 0) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        }
        
        return null;
    }
}

// --- PROCESAMIENTO PRINCIPAL SIMPLIFICADO ---
header('Content-Type: text/html; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo "Por favor, escribe tu consulta sobre el sistema de recolecciones.";
    exit;
}

try {
    $processor = new SimpleAIProcessor($conn_mysql);
    $result = $processor->processQuery($userMessage);
    
    // Preparar prompt simple
    $results_count = $result['results_count'];
    $data_preview = $result['data'] ? json_encode(array_slice($result['data'], 0, 5), JSON_UNESCAPED_UNICODE) : "No se encontraron datos";
    
    $prompt = "Eres un asistente del sistema de recolecciones. 

CONSULTA DEL USUARIO:
\"$userMessage\"

RESULTADOS ENCONTRADOS ($results_count registros):
$data_preview

INSTRUCCIONES:
1. Analiza los datos y proporciona una respuesta útil
2. Si hay datos, destaca la información más relevante
3. Si no hay datos, sugiere una consulta alternativa
4. Usa un lenguaje natural y amigable
5. Sé específico con números y fechas

Responde directamente al usuario:";

    $payload = [
        "model" => "llama-3.1-8b-instant",
        "messages" => [
            ["role" => "system", "content" => "Eres un asistente especializado en sistemas de recolecciones logísticas."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.3,
        "max_tokens" => 1500
    ];

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . GROQ_API_KEY
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($status == 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            echo nl2br(htmlspecialchars($data['choices'][0]['message']['content']));
        } else {
            echo "❌ La IA no pudo generar una respuesta. Intenta con otra pregunta.";
        }
    } else {
        error_log("Groq API Error: $status - $response");
        echo "❌ Error al conectar con el servicio de IA. Intenta nuevamente.";
    }
    
    curl_close($ch);
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo "❌ Ocurrió un error inesperado. Por favor, intenta con una consulta más simple.";
}
?>