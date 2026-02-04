<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración
$maxFileSize = 10 * 1024 * 1024; // 10MB máximo
$uploadDir = 'uploads/';

// Crear carpeta uploads si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Crear carpetas si no existen
$folders = ['GLAMA', 'MORYSAN'];
foreach ($folders as $folder) {
    $folderPath = $uploadDir . $folder;
    if (!is_dir($folderPath)) {
        if (!mkdir($folderPath, 0777, true)) {
            die(json_encode([
                'success' => false,
                'message' => "Error: No se pudo crear la carpeta $folderPath"
            ]));
        }
    }
}

// Función para determinar carpeta destino
function getDestinationFolder($filename) {
    $lowerName = strtolower($filename);
    if (strpos($lowerName, 'gl') !== false) {
        return 'GLAMA/';
    } elseif (strpos($lowerName, 'mor') !== false) {
        return 'MORYSAN/';
    } else {
        return false;
    }
}

// Función para limpiar nombre de archivo (mantener solo nombre base)
function getCleanFileName($filename) {
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Eliminar cualquier timestamp o ID previo
    // Esto remueve cualquier cosa después del último guión bajo si es un timestamp
    $parts = explode('_', $baseName);
    if (count($parts) > 1) {
        // Verificar si la última parte es un ID/timestamp
        $lastPart = end($parts);
        if (preg_match('/^[a-f0-9]{13}$/', $lastPart) || // uniqid
            preg_match('/^\d{10}$/', $lastPart) || // timestamp 10 dígitos
            preg_match('/^\d{13}$/', $lastPart)) { // timestamp 13 dígitos
            array_pop($parts);
            $baseName = implode('_', $parts);
        }
    }
    
    // Limpiar caracteres especiales pero mantener guiones bajos y guiones
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName);
    return $cleanName . '.' . strtolower($extension);
}

// Procesar archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verificar si hay archivos
    if (!isset($_FILES['archivos']) || empty($_FILES['archivos']['name'][0])) {
        $response = [
            'success' => false,
            'message' => 'No se seleccionaron archivos.'
        ];
        echo json_encode($response);
        exit;
    }
    
    $uploadedFiles = $_FILES['archivos'];
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    $replacedCount = 0;
    
    // Procesar cada archivo
    for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
        $originalName = $uploadedFiles['name'][$i];
        $fileName = getCleanFileName($originalName);
        $fileTmp = $uploadedFiles['tmp_name'][$i];
        $fileSize = $uploadedFiles['size'][$i];
        $fileError = $uploadedFiles['error'][$i];
        
        // Verificar errores de subida
        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            
            $errorMsg = isset($errorMessages[$fileError]) ? $errorMessages[$fileError] : "Error desconocido (Código: $fileError)";
            $results[] = "❌ $originalName: $errorMsg";
            $errorCount++;
            continue;
        }
        
        // Verificar tamaño
        if ($fileSize > $maxFileSize) {
            $results[] = "❌ $originalName: Tamaño excede el límite de 10MB";
            $errorCount++;
            continue;
        }
        
        // Verificar que sea PDF por extensión
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $results[] = "❌ $originalName: Solo se permiten archivos PDF (extensión .pdf)";
            $errorCount++;
            continue;
        }
        
        // Determinar carpeta destino
        $folder = getDestinationFolder($fileName);
        if (!$folder) {
            $results[] = "❌ $originalName: El nombre no contiene 'gl' ni 'mor'";
            $errorCount++;
            continue;
        }
        
        // Ruta destino final (sin ID único)
        $destination = $uploadDir . $folder . $fileName;
        
        // Verificar si el archivo ya existe
        $fileExists = file_exists($destination);
        
        // Si existe, eliminarlo primero
        if ($fileExists) {
            if (unlink($destination)) {
                $replacedCount++;
            } else {
                $results[] = "❌ $originalName: No se pudo eliminar la versión anterior";
                $errorCount++;
                continue;
            }
        }
        
        // Mover archivo
        if (move_uploaded_file($fileTmp, $destination)) {
            if ($fileExists) {
                $results[] = "🔄 $originalName → /$folder$fileName (REEMPLAZADO)";
            } else {
                $results[] = "✅ $originalName → /$folder$fileName (NUEVO)";
            }
            $successCount++;
        } else {
            // Verificar permisos
            $folderPath = dirname($destination);
            if (!is_writable($folderPath)) {
                $results[] = "❌ $originalName: La carpeta $folderPath no tiene permisos de escritura";
            } else {
                $results[] = "❌ $originalName: No se pudo mover el archivo";
            }
            $errorCount++;
        }
    }
    
    // Preparar respuesta
    $message = "Proceso completado. ";
    
    if ($successCount > 0) {
        $message .= "{$successCount} archivo(s) procesados correctamente. ";
        if ($replacedCount > 0) {
            $message .= "({$replacedCount} reemplazados) ";
        }
    }
    
    if ($errorCount > 0) {
        $message .= "{$errorCount} archivo(s) con errores.";
    }
    
    $response = [
        'success' => ($successCount > 0),
        'message' => $message,
        'details' => implode("\n", $results)
    ];
    
    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} else {
    $response = [
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>