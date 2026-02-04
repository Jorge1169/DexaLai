<?php
// check_folder.php - Para verificar archivos en carpetas

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración
$uploadDir = 'uploads/';

// Verificar parámetros
if (!isset($_GET['folder']) || empty($_GET['folder'])) {
    echo json_encode(['error' => 'No se especificó la carpeta']);
    exit;
}

$folder = $_GET['folder'];
$folderPath = $uploadDir . $folder . '/';

// Verificar que la carpeta exista
if (!is_dir($folderPath)) {
    echo json_encode(['count' => 0, 'totalSize' => 0, 'files' => []]);
    exit;
}

// Obtener todos los archivos PDF de la carpeta
$pdfFiles = glob($folderPath . '*.pdf');

// Contar y calcular tamaño total
$count = count($pdfFiles);
$totalSize = 0;
$filesInfo = [];

foreach ($pdfFiles as $pdfFile) {
    $fileSize = filesize($pdfFile);
    $totalSize += $fileSize;
    
    if (isset($_GET['list']) && $_GET['list'] === 'true') {
        $filesInfo[] = [
            'name' => basename($pdfFile),
            'size' => $fileSize,
            'modified' => filemtime($pdfFile)
        ];
    }
}

// Ordenar archivos por nombre
if (isset($_GET['list']) && $_GET['list'] === 'true') {
    usort($filesInfo, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

// Preparar respuesta
$response = [
    'count' => $count,
    'totalSize' => $totalSize
];

if (isset($_GET['list']) && $_GET['list'] === 'true') {
    $response['files'] = $filesInfo;
}

echo json_encode($response);
?>