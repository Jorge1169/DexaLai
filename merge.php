<?php
// merge.php - Para combinar PDFs de una carpeta específica (VERSIÓN CORREGIDA)

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Utilerías
require_once 'libs/fpdf/fpdf.php';
require_once 'libs/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

// Configuración
$uploadDir = 'uploads/';

// Verificar parámetros
if (!isset($_GET['folder']) || empty($_GET['folder'])) {
    die('Error: No se especificó la carpeta');
}

$folder = $_GET['folder'];
$folderPath = $uploadDir . $folder . '/';

// Verificar que la carpeta exista
if (!is_dir($folderPath)) {
    die('Error: La carpeta no existe');
}

// Obtener todos los archivos PDF de la carpeta
$pdfFiles = glob($folderPath . '*.pdf');

// Verificar si hay archivos PDF
if (empty($pdfFiles)) {
    die('Error: No hay archivos PDF en la carpeta ' . $folder);
}

// Ordenar archivos alfabéticamente
sort($pdfFiles);

try {
    // Crear instancia de FPDI
    $pdf = new Fpdi();
    
    // Configurar metadatos del PDF
    $pdf->SetTitle('PDF Combinado - ' . $folder);
    $pdf->SetAuthor('Sistema de Gestión PDF');
    $pdf->SetCreator('PHP FPDF/FPDI');
    
    // Variables para control
    $currentPage = 1; // Contador de páginas en el PDF final
    
    // Combinar cada PDF
    foreach ($pdfFiles as $pdfFile) {
        // Obtener número de páginas del PDF actual
        $pageCount = $pdf->setSourceFile($pdfFile);
        
        // Importar cada página
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // Importar página
            $templateId = $pdf->importPage($pageNo);
            
            // Obtener tamaño de la página
            $size = $pdf->getTemplateSize($templateId);
            
            // Agregar nueva página (orientación basada en el tamaño)
            if ($size['width'] > $size['height']) {
                $pdf->AddPage('L', [$size['width'], $size['height']]);
            } else {
                $pdf->AddPage('P', [$size['width'], $size['height']]);
            }
            
            // Usar la página importada
            $pdf->useTemplate($templateId);
            
            $currentPage++;
        }
    }
    
    // Generar nombre del archivo combinado
    $fileName = $folder . '_combinado_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Salida: forzar descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('D', $fileName);
    
} catch (Exception $e) {
    die('Error al combinar PDFs: ' . $e->getMessage());
}
?>