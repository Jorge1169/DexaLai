<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/groq_key.php';

echo "<h2>🚀 Iniciando prueba de conexión a Groq...</h2>";

$curl = curl_init();

$payload = [
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        ["role" => "system", "content" => "Eres un asistente útil conectado al sistema de recolección."],
        ["role" => "user", "content" => "Hola, Groq. ¿Puedes confirmar que la conexión funciona?"]
    ]
];

echo "<pre><b>Payload:</b> " . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . "</pre>";

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.groq.com/openai/v1/chat/completions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . GROQ_API_KEY
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($curl);

if ($response === false) {
    echo "<h3 style='color:red;'>❌ Error en cURL:</h3>";
    echo "<pre>" . curl_error($curl) . "</pre>";
} else {
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    echo "<h4>HTTP Status: $status</h4>";
    echo "<pre><b>Respuesta cruda:</b>\n" . htmlspecialchars($response) . "</pre>";

    if ($status == 200) {
        $data = json_decode($response, true);
        echo "<h3 style='color:green;'>✅ Conectado correctamente a Groq</h3>";
        echo "<pre>" . htmlspecialchars($data['choices'][0]['message']['content']) . "</pre>";
    } else {
        echo "<h3 style='color:red;'>❌ Error HTTP: $status</h3>";
    }
}

curl_close($curl);
