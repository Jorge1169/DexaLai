<?php
$decimal = '15';
$binario = decbin($decimal);
// Definir los estados
$estados = [
    0 => "Pendiente",
    1 => "Aceptada", 
    2 => "Contra Recibo",
    4 => "Exportada",
    8 => "Respaldada",
    16 => "PROGPAGO",
    32 => "Pagada",
    64 => "RECPAGO",
    128 => "Rechazada"
];

// Obtener valores reales de los bits en 1
$valoresReales = [];
for ($i = 0; $i < strlen($binario); $i++) {
    $bit = $binario[$i];
    $posicion = strlen($binario) - $i - 1;
    if ($bit === '1') {
        $valoresReales[] = pow(2, $posicion);
    }
}
// Solo hacer visible en caso de usarlos
$estadosActivos = [];
$estadosEncontrados = [];
foreach ($valoresReales as $valor) {
    if (isset($estados[$valor])) {
        $estadosActivos[] = $estados[$valor];
        $estadosEncontrados[] = $valor;
        //echo "✓ Valor $valor: <strong>" . $estados[$valor] . "</strong><br>";
    } else {
        //echo "✗ Valor $valor: Estado no definido<br>";
    }
}
// Determinar y mostrar el ESTADO ACTUAL (el valor más alto)
echo "<strong>ESTADO ACTUAL:</strong><br>";
if (!empty($valoresReales)) {
    $estadoActualValor = max($valoresReales);
    $estadoActualNombre = $estados[$estadoActualValor] ?? "Desconocido";
    echo "🔹 <strong>$estadoActualNombre</strong> (Valor: $estadoActualValor)";
} else {
    echo "🔹 <strong>Pendiente</strong> (Valor: 0)";
}
?>