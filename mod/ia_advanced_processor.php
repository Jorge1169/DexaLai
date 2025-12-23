<?php
// CORRIGE ESTAS RUTAS - versión corregida
require_once __DIR__ . '/../config/groq_key.php';
require_once __DIR__ . '/../config/conexiones.php';
require_once __DIR__ . '/../config/database_context.php';
require_once __DIR__ . '/../config/BusinessContext.php';
require_once __DIR__ . '/../config/ConversationalAIProcessor.php';

// AGREGA ESTO PARA DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

// DEBUG: Verificar si estamos recibiendo el mensaje
error_log("DexaLai AI - Mensaje recibido: " . $userMessage);

if (!$userMessage) {
    echo "¡Hola! 👋 Soy tu asistente **DexaLai** para el sistema de recolección de cartón. ¿En qué puedo ayudarte hoy?";
    exit;
}

try {
    // DEBUG: Verificar conexión a BD
    error_log("DexaLai AI - Inicializando conexión...");
    
    // Inicializar el procesador conversacional
    $conversationalAI = new ConversationalAIProcessor($conn_mysql);
    error_log("DexaLai AI - Procesador inicializado");
    
    // Procesar la consulta de forma natural
    $dbResponse = $conversationalAI->processNaturalQuery($userMessage);
    error_log("DexaLai AI - Respuesta de BD: " . substr($dbResponse, 0, 100));
    
    // Preparar contexto enriquecido para Groq
    $businessContext = BusinessContext::getBusinessKnowledge();
    $personality = BusinessContext::getPersonalityTraits();
    $conversationExamples = BusinessContext::getConversationExamples();
    
    $prompt = "Eres **DexaLai AI** - Asistente especializado en recolección de merma de cartón.

CONTEXTO DEL SISTEMA DEXALAI:
- Sistema: {$businessContext['sistema']}
- Negocio: {$businessContext['negocio']['descripcion']}
- Misión principal: {$businessContext['negocio']['mision']}

PROCESOS CRÍTICOS:
- Validación de recolecciones reales mediante contrarecibos
- Control de 3 facturas por recolección: compra, venta, flete
- Cálculo: Utilidad = Venta - Compra - Flete

CONSULTA DEL USUARIO:
\"{$userMessage}\"

RESULTADO DE LA CONSULTA A BASE DE DATOS:
{$dbResponse}

INSTRUCCIONES:
1. Responde como DexaLai AI - experto en recolección de cartón
2. Mantén el tono conversacional pero profesional
3. Usa emojis relevantes
4. Destaca números importantes
5. Ofrece seguir profundizando
6. Sé conciso pero útil
7. Usa **negritas** para puntos clave

Responde directamente al usuario:";

    error_log("DexaLai AI - Enviando a Groq API...");

    $payload = [
        "model" => "llama-3.1-8b-instant",
        "messages" => [
            ["role" => "system", "content" => "Eres DexaLai AI - asistente inteligente para sistema de recolección de merma de cartón."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.4,
        "max_tokens" => 1000
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    error_log("DexaLai AI - Respuesta HTTP: " . $httpCode);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $aiResponse = $data['choices'][0]['message']['content'];
            $aiResponse = htmlspecialchars($aiResponse);
            $aiResponse = nl2br($aiResponse);
            echo $aiResponse;
            error_log("DexaLai AI - Respuesta enviada al usuario");
        } else {
            error_log("DexaLai AI - Fallback a respuesta de BD");
            echo nl2br(htmlspecialchars($dbResponse));
        }
    } else {
        error_log("DexaLai AI - Error HTTP, fallback a BD");
        echo nl2br(htmlspecialchars($dbResponse));
    }
    
    curl_close($ch);
    
} catch (Exception $e) {
    error_log("DexaLai AI Error: " . $e->getMessage());
    echo "⚠️ **¡Ups!** Ocurrió un error. Por favor, intenta con: \"¿Cuánto vendimos este mes?\" o \"Recolecciones pendientes\"";
}
?>