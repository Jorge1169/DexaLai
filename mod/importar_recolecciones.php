<?php
// importar_recolecciones.php
$permiso_importar = 1; // Permiso temporal para testing

// Procesar el archivo subido
$procesamiento = null;
if (isset($_POST['procesar_importacion']) && isset($_POST['datos_validados'])) {
    $procesamiento = procesarImportacion($_POST['datos_validados'], $conn_mysql, $idUser, $zona_seleccionada);
}

// Mostrar resultados del procesamiento
if (isset($_GET['procesado']) && $_GET['procesado'] == '1') {
    echo mostrarResultadoProcesamiento();
}
?>

<style>
    .csv-preview table {
        font-size: 0.85rem;
    }
    .csv-preview th {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
    }
    .status-valid {
        background-color: #d1edff !important;
    }
    .status-error {
        background-color: #ffe6e6 !important;
    }
    .status-warning {
        background-color: #fff3cd !important;
    }
    .badge-custom {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    .upload-area {
        border: 4px dashed #dee2e6;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        background-color: var(--bs-body-bg);
    }
    .upload-area:hover {
        border-color: #0d6efd;
        background-color: var(--bs-primary-bg-subtle);
    }
    .upload-area.dragover {
        border-color: #0d6efd;
        background-color: #d1e7ff;
    }
    .file-info {
        background-color: var(--bs-success-bg-subtle);
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-top: 1rem;
    }
</style>

<div class="container-fluid mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-upload me-2"></i>Importar Recolecciones desde CSV
            </h5>
            <a href="?p=recoleccion" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i> Volver a Recolecciones
            </a>
        </div>
        
        <div class="card-body">
            <?php if (isset($_GET['procesado']) && $_GET['procesado'] == '1'): ?>
                <!-- Mostrar resultados del procesamiento -->
                <?= mostrarResultadoProcesamiento() ?>
            <?php elseif (isset($_POST['previsualizar']) && isset($_FILES['archivo_csv'])): ?>
                <!-- Mostrar previsualización y validación -->
                <?= mostrarPrevisualizacionCSV() ?>
            <?php else: ?>
                <!-- Formulario de subida inicial -->
                <?= mostrarFormularioSubida() ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// =============================================================================
// FUNCIONES PRINCIPALES
// =============================================================================

/**
 * Muestra el formulario inicial de subida de archivo
 */
function mostrarFormularioSubida() {
    global $permiso_importar;
    
    if (empty($permiso_importar)) {
        return '
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No tiene permisos para importar recolecciones.
        </div>';
    }
    
    return '
    <div class="row">
        <div class="col-md-8">
            <div class="upload-area" id="uploadArea">
                <i class="bi bi-file-earmark-spreadsheet display-4 text-primary mb-3"></i>
                <h5>Seleccione o arrastre su archivo CSV</h5>
                <p class="text-muted mb-3">
                    Formatos aceptados: .csv, .txt (Máximo 10MB)
                </p>
                
                <form method="post" enctype="multipart/form-data" id="uploadForm">
                    <input type="file" name="archivo_csv" id="archivo_csv" 
                           accept=".csv,.txt" class="d-none" required>
                    <button type="button" class="btn btn-primary btn-lg rounded-4" onclick="document.getElementById(\'archivo_csv\').click()">
                        <i class="bi bi-folder2-open me-2"></i>Seleccionar Archivo
                    </button>
                    <div id="fileInfo" class="file-info border border-success-subtle border-4 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="fileName"></span>
                            <button type="submit" name="previsualizar" class="btn btn-success btn-sm rounded-3">
                                <i class="bi bi-eye me-1"></i> Previsualizar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 bg-body-tertiary">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle me-2"></i>Instrucciones
                    </h6>
                    
                    <div class="mb-3">
                        <strong>Formato CSV requerido:</strong>
                        <ul class="small mt-2">
                            <li><strong>fecha_recoleccion</strong> (YYYY-MM-DD) o (DD/MM/YYYY)</li>
                            <li><strong>proveedor</strong> (código)</li>
                            <li><strong>bodega_proveedor</strong> (código)</li>
                            <li><strong>fletero</strong> (Código)</li>
                            <li><strong>tipo_flete</strong> (FT o FV)</li>
                            <li><strong>cliente</strong> (código)</li>
                            <li><strong>bodega_cliente</strong> (código)</li>
                            <li><strong>producto</strong> (código)</li>
                            <li><strong>factura_venta</strong></li>
                            <li><strong>fecha_factura</strong> (YYYY-MM-DD) o (DD/MM/YYYY)</li>
                            <li><strong>remision</strong> (opcional)</li>
                            <li><strong>peso_proveedor</strong> (opcional, kg)</li>
                            <li><strong>factura_flete</strong> (opcional)</li>
                            <li><strong>peso_flete</strong> (opcional, kg)</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Tip:</strong> Los precios se buscarán automáticamente según la fecha de recolección.
                    </div>
                    <a class="btn btn-teal rounded-4" href="descargas/Subir_recolecciones.csv" download="PlantillaExcelRecolecciones">
                        <i class="bi bi-filetype-csv"></i> Descargar plantilla
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Manejar drag & drop
    const uploadArea = document.getElementById(\'uploadArea\');
    const fileInput = document.getElementById(\'archivo_csv\');
    const fileInfo = document.getElementById(\'fileInfo\');
    const fileName = document.getElementById(\'fileName\');
    
    uploadArea.addEventListener(\'dragover\', (e) => {
        e.preventDefault();
        uploadArea.classList.add(\'dragover\');
    });
    
    uploadArea.addEventListener(\'dragleave\', () => {
        uploadArea.classList.remove(\'dragover\');
    });
    
    uploadArea.addEventListener(\'drop\', (e) => {
        e.preventDefault();
        uploadArea.classList.remove(\'dragover\');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });
    
    fileInput.addEventListener(\'change\', handleFileSelect);
    
    function handleFileSelect() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            fileName.textContent = file.name;
            fileInfo.classList.remove(\'d-none\');
        }
    }
    </script>';
}

/**
 * Muestra la previsualización y validación del CSV
 */
function mostrarPrevisualizacionCSV() {
    global $conn_mysql, $zona_seleccionada;
    
    $archivo = $_FILES['archivo_csv'];
    
    // Validar archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return '
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-octagon me-2"></i>
            Error al subir el archivo: ' . obtenerMensajeErrorUpload($archivo['error']) . '
        </div>
        <div class="text-center mt-3">
            <a href="?p=importar_recolecciones" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Intentar de nuevo
            </a>
        </div>';
    }
    
    // Leer y validar CSV
    $datos_csv = leerArchivoCSV($archivo['tmp_name']);
    if (!$datos_csv) {
        return '
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-octagon me-2"></i>
            No se pudo leer el archivo CSV. Verifique el formato.
        </div>
        <div class="text-center mt-3">
            <a href="?p=importar_recolecciones" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Intentar de nuevo
            </a>
        </div>';
    }
    
    // Validar estructura
    $validacion_estructura = validarEstructuraCSV($datos_csv);
    if (!$validacion_estructura['valido']) {
        return '
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-octagon me-2"></i>
            ' . $validacion_estructura['mensaje'] . '
        </div>
        <div class="text-center mt-3">
            <a href="?p=importar_recolecciones" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Intentar de nuevo
            </a>
        </div>';
    }
    
    // Validar datos y buscar IDs
    $datos_validados = validarYBuscarDatos($datos_csv, $conn_mysql, $zona_seleccionada);
    
    return generarVistaPreviaMejorada($datos_validados, $archivo['name']);
}

/**
 * Procesa la importación de datos validados
 */
function procesarImportacion($datos_validados_json, $conn, $id_user, $zona) {
    $datos_validados = json_decode($datos_validados_json, true);
    $resultados = [
        'exitosos' => 0,
        'errores' => 0,
        'detalles' => []
    ];
    
    // Procesar en lotes de 50 registros
    $lotes = array_chunk($datos_validados['datos'], 50);
    
    foreach ($lotes as $lote) {
        $conn->begin_transaction();
        
        try {
            foreach ($lote as $fila) {
                if ($fila['estado'] !== 'valid') continue;
                
                $resultado = insertarRecoleccion($fila, $conn, $id_user, $zona);
                
                if ($resultado['exito']) {
                    $resultados['exitosos']++;
                    $resultados['detalles'][] = [
                        'tipo' => 'exito',
                        'mensaje' => "Fila {$fila['numero_fila']}: Recolección creada - Folio {$resultado['folio']}",
                        'folio' => $resultado['folio']
                    ];
                } else {
                    $resultados['errores']++;
                    $resultados['detalles'][] = [
                        'tipo' => 'error',
                        'mensaje' => "Fila {$fila['numero_fila']}: {$resultado['error']}"
                    ];
                }
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $resultados['errores'] += count($lote);
            $resultados['detalles'][] = [
                'tipo' => 'error',
                'mensaje' => "Error en lote: " . $e->getMessage()
            ];
        }
    }
    
    // Guardar resultados en sesión para mostrar después
    $_SESSION['resultado_importacion'] = $resultados;
    
    // Redirigir para evitar reenvío del formulario
    alert("Recoleccion(s) Subidas con exito", 1, "importar_recolecciones");
    logActivity('EXCEL', 'Subio recolecciones de forma masiva');
    exit;
}

/**
 * Muestra el resultado del procesamiento
 */
function mostrarResultadoProcesamiento() {
    if (!isset($_SESSION['resultado_importacion'])) {
        return '
        <div class="alert alert-warning">
            No hay resultados de importación para mostrar.
        </div>
        <div class="text-center mt-3">
            <a href="?p=importar_recolecciones" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Volver a importar
            </a>
        </div>';
    }
    
    $resultados = $_SESSION['resultado_importacion'];
    unset($_SESSION['resultado_importacion']);
    
    $html = '
    <div class="alert ' . ($resultados['errores'] == 0 ? 'alert-success' : 'alert-warning') . '">
        <h5><i class="bi bi-check-circle me-2"></i>Procesamiento completado</h5>
        <p class="mb-0">
            <strong>Éxitos:</strong> ' . $resultados['exitosos'] . ' | 
            <strong>Errores:</strong> ' . $resultados['errores'] . '
        </p>
    </div>';
    
    if (!empty($resultados['detalles'])) {
        $html .= '
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Detalles del procesamiento</h6>
            </div>
            <div class="card-body">
                <div style="max-height: 400px; overflow-y: auto;">';
        
        foreach ($resultados['detalles'] as $detalle) {
            $icono = $detalle['tipo'] == 'exito' ? 'bi-check-circle text-success' : 'bi-exclamation-circle text-danger';
            $html .= '
            <div class="d-flex align-items-center mb-2">
                <i class="bi ' . $icono . ' me-2"></i>
                <span class="small">' . htmlspecialchars($detalle['mensaje']) . '</span>
            </div>';
        }
        
        $html .= '
                </div>
            </div>
        </div>';
    }
    
    $html .= '
    <div class="text-center mt-4">
        <a href="?p=recoleccion" class="btn btn-primary me-2">
            <i class="bi bi-list me-1"></i> Ver todas las recolecciones
        </a>
        <a href="?p=importar_recolecciones" class="btn btn-outline-primary">
            <i class="bi bi-upload me-1"></i> Importar más
        </a>
    </div>';
    
    return $html;
}

// =============================================================================
// FUNCIONES DE VALIDACIÓN Y PROCESAMIENTO
// =============================================================================

/**
 * Lee y parsea el archivo CSV
 */
function leerArchivoCSV($ruta_archivo) {
    if (!file_exists($ruta_archivo)) {
        return false;
    }
    
    $datos = [];
    $handle = fopen($ruta_archivo, 'r');
    
    if ($handle === false) {
        return false;
    }
    
    // Leer encabezados
    $encabezados = fgetcsv($handle, 0, ',');
    if ($encabezados === false) {
        fclose($handle);
        return false;
    }
    
    // Leer filas
    $numero_fila = 1;
    while (($fila = fgetcsv($handle, 0, ',')) !== false) {
        $numero_fila++;
        
        // Saltar filas vacías
        if (empty(implode('', $fila))) {
            continue;
        }
        
        $fila_data = [];
        foreach ($encabezados as $index => $encabezado) {
            $fila_data[trim($encabezado)] = isset($fila[$index]) ? trim($fila[$index]) : '';
        }
        
        $fila_data['numero_fila'] = $numero_fila;
        $datos[] = $fila_data;
    }
    
    fclose($handle);
    return $datos;
}

/**
 * Valida la estructura básica del CSV
 */
function validarEstructuraCSV($datos) {
    if (empty($datos)) {
        return ['valido' => false, 'mensaje' => 'El archivo CSV está vacío.'];
    }
    
    $campos_requeridos = [
        'fecha_recoleccion', 'proveedor', 'bodega_proveedor', 'fletero', 
        'tipo_flete', 'cliente', 'bodega_cliente', 'producto', 
        'factura_venta', 'fecha_factura'
    ];
    
    $primer_fila = $datos[0];
    
    foreach ($campos_requeridos as $campo) {
        if (!array_key_exists($campo, $primer_fila)) {
            return [
                'valido' => false, 
                'mensaje' => "Falta el campo requerido: '{$campo}'"
            ];
        }
    }
    
    return ['valido' => true, 'mensaje' => 'Estructura válida'];
}

/**
 * Valida y busca los IDs de las entidades en la base de datos
 */
function validarYBuscarDatos($datos_csv, $conn, $zona_seleccionada) {
    $resultados = [
        'total_filas' => count($datos_csv),
        'validas' => 0,
        'errores' => 0,
        'datos' => []
    ];
    
    foreach ($datos_csv as $fila) {
        $numero_fila = $fila['numero_fila'];
        $errores = [];
        $datos_mapeados = [];
        
        // 1. Validar y convertir fechas
        $fecha_recoleccion_original = $fila['fecha_recoleccion'];
        $fecha_recoleccion_convertida = convertirF($fecha_recoleccion_original);
        
        if (!$fecha_recoleccion_convertida) {
            $errores[] = "Fecha de recolección inválida: {$fecha_recoleccion_original} (use YYYY-MM-DD o DD/MM/YYYY)";
        }
        
        $fecha_factura_original = $fila['fecha_factura'];
        $fecha_factura_convertida = convertirF($fecha_factura_original);
        
        if (!$fecha_factura_convertida) {
            $errores[] = "Fecha de factura inválida: {$fecha_factura_original} (use YYYY-MM-DD o DD/MM/YYYY)";
        }
        
        // 2. Buscar IDs de entidades (solo si las fechas son válidas)
        if (empty($errores)) {
            $proveedor_id = buscarProveedor($fila['proveedor'], $conn, $zona_seleccionada);
            if (!$proveedor_id) {
                $errores[] = "Proveedor no encontrado: {$fila['proveedor']}";
            }
            
            $bodega_proveedor_id = buscarBodegaProveedor($fila['bodega_proveedor'], $proveedor_id, $conn);
            if (!$bodega_proveedor_id) {
                $errores[] = "Bodega de proveedor no encontrada: {$fila['bodega_proveedor']}";
            }
            
            $fletero_id = buscarFletero($fila['fletero'], $conn, $zona_seleccionada);
            if (!$fletero_id) {
                $errores[] = "Fletero no encontrado: {$fila['fletero']}";
            }
            
            $cliente_id = buscarCliente($fila['cliente'], $conn, $zona_seleccionada);
            if (!$cliente_id) {
                $errores[] = "Cliente no encontrado: {$fila['cliente']}";
            }
            
            $bodega_cliente_id = buscarBodegaCliente($fila['bodega_cliente'], $cliente_id, $conn);
            if (!$bodega_cliente_id) {
                $errores[] = "Bodega de cliente no encontrada: {$fila['bodega_cliente']}";
            }
            
            $producto_id = buscarProducto($fila['producto'], $conn, $zona_seleccionada);
            if (!$producto_id) {
                $errores[] = "Producto no encontrado: {$fila['producto']}";
            }
            
            // 3. Validar tipo de flete
            if (!in_array($fila['tipo_flete'], ['FT', 'FV'])) {
                $errores[] = "Tipo de flete inválido: {$fila['tipo_flete']} (debe ser FT o FV)";
            }
            
            // 4. Buscar precios según fecha de recolección (usando fecha convertida)
            if (empty($errores)) {
                $precios = buscarPreciosVigentes(
                    $fecha_recoleccion_convertida,
                    $fletero_id,
                    $fila['tipo_flete'],
                    $bodega_proveedor_id,
                    $bodega_cliente_id,
                    $producto_id,
                    $bodega_cliente_id,
                    $conn
                );
                
                if (!$precios['flete']) {
                    $errores[] = "No se encontró precio de flete vigente para la fecha {$fecha_recoleccion_convertida}";
                }
                if (!$precios['compra']) {
                    $errores[] = "No se encontró precio de compra vigente para la fecha {$fecha_recoleccion_convertida}";
                }
                if (!$precios['venta']) {
                    $errores[] = "No se encontró precio de venta vigente para la fecha {$fecha_recoleccion_convertida}";
                }
            }
        }
        
        // 5. Preparar datos finales
        if (empty($errores)) {
            $resultados['validas']++;
            $estado = 'valid';
        } else {
            $resultados['errores']++;
            $estado = 'error';
        }
        
        // Guardar tanto las fechas originales como las convertidas
        $fila_convertida = $fila;
        if ($fecha_recoleccion_convertida) {
            $fila_convertida['fecha_recoleccion_original'] = $fila['fecha_recoleccion'];
            $fila_convertida['fecha_recoleccion'] = $fecha_recoleccion_convertida;
        }
        if ($fecha_factura_convertida) {
            $fila_convertida['fecha_factura_original'] = $fila['fecha_factura'];
            $fila_convertida['fecha_factura'] = $fecha_factura_convertida;
        }
        
        $resultados['datos'][] = [
            'numero_fila' => $numero_fila,
            'estado' => $estado,
            'errores' => $errores,
            'datos_originales' => $fila_convertida,
            'datos_mapeados' => [
                'proveedor_id' => $proveedor_id ?? null,
                'bodega_proveedor_id' => $bodega_proveedor_id ?? null,
                'fletero_id' => $fletero_id ?? null,
                'cliente_id' => $cliente_id ?? null,
                'bodega_cliente_id' => $bodega_cliente_id ?? null,
                'producto_id' => $producto_id ?? null,
                'precio_flete_id' => $precios['flete'] ?? null,
                'precio_compra_id' => $precios['compra'] ?? null,
                'precio_venta_id' => $precios['venta'] ?? null,
                'folio' => 'PENDIENTE'
            ]
        ];
    }
    
    // 6. Generar folios temporales únicos
    $resultados = generarFoliosTemporalesUnicos($resultados, $zona_seleccionada, $conn);
    
    // 7. Validar remisiones duplicadas
    $resultados = validarRemisionesDuplicadas($resultados, $conn);
    
    return $resultados;
}

// =============================================================================
// FUNCIONES MEJORADAS PARA FOLIOS TEMPORALES Y VALIDACIÓN DE REMISIONES
// =============================================================================

/**
 * Genera folios temporales únicos para previsualización
 */
function generarFoliosTemporalesUnicos($datos_validados, $zona_seleccionada, $conn) {
    if ($zona_seleccionada == 0) {
        $zona_query = "SELECT * FROM zonas WHERE status = '1' LIMIT 1";
    } else {
        $zona_query = "SELECT * FROM zonas WHERE status = '1' AND id_zone = '$zona_seleccionada'";
    }
    
    $zona_result = $conn->query($zona_query);
    if (!$zona_result || $zona_result->num_rows === 0) {
        return $datos_validados;
    }
    
    $zona_data = $zona_result->fetch_assoc();
    
    // Agrupar por mes para generar folios consecutivos
    $folios_por_mes = [];
    
    foreach ($datos_validados['datos'] as &$fila) {
        if ($fila['estado'] !== 'valid') {
            $fila['datos_mapeados']['folio'] = 'N/A';
            continue;
        }
        
        $fecha = $fila['datos_originales']['fecha_recoleccion'];
        $mes_key = date('Ym', strtotime($fecha));
        
        if (!isset($folios_por_mes[$mes_key])) {
            // Buscar último folio real del mes
            $mes_actual = date('m', strtotime($fecha));
            $anio_actual = date('Y', strtotime($fecha));
            
            $folio_query = "SELECT folio FROM recoleccion 
                           WHERE status = '1' 
                           AND YEAR(fecha_r) = '$anio_actual'  
                           AND MONTH(fecha_r) = '$mes_actual' 
                           AND zona = '{$zona_data['id_zone']}'
                           ORDER BY folio DESC 
                           LIMIT 1";
            
            $folio_result = $conn->query($folio_query);
            $ultimo_folio = 0;
            
            if ($folio_result && $folio_result->num_rows > 0) {
                $ultimo_folio = intval($folio_result->fetch_assoc()['folio']);
            }
            
            $folios_por_mes[$mes_key] = $ultimo_folio + 1;
        } else {
            $folios_por_mes[$mes_key]++;
        }
        
        $folio_numero = str_pad($folios_por_mes[$mes_key], 4, '0', STR_PAD_LEFT);
        $fecha_formateada = date('ym', strtotime($fecha));
        $fila['datos_mapeados']['folio'] = $zona_data['cod'] . "-" . $fecha_formateada . $folio_numero;
    }
    
    return $datos_validados;
}

/**
 * Valida remisiones duplicadas en el archivo y en la base de datos
 */
function validarRemisionesDuplicadas($datos_validados, $conn) {
    $remisiones_archivo = [];
    $remisiones_base_datos = [];
    
    // 1. Buscar remisiones duplicadas en el archivo
    foreach ($datos_validados['datos'] as &$fila) {
        if ($fila['estado'] !== 'valid') continue;
        
        $remision = trim($fila['datos_originales']['remision'] ?? '');
        $proveedor_id = $fila['datos_mapeados']['proveedor_id'] ?? 0;
        
        if (!empty($remision)) {
            $clave = $proveedor_id . '_' . $remision;
            
            if (isset($remisiones_archivo[$clave])) {
                $fila['estado'] = 'error';
                $fila['errores'][] = "Remisión duplicada en el archivo: {$remision}";
                $datos_validados['validas']--;
                $datos_validados['errores']++;
            } else {
                $remisiones_archivo[$clave] = true;
                
                // 2. Verificar si ya existe en base de datos
                if (!isset($remisiones_base_datos[$proveedor_id])) {
                    $remisiones_base_datos[$proveedor_id] = obtenerRemisionesProveedor($proveedor_id, $conn);
                }
                
                if (in_array($remision, $remisiones_base_datos[$proveedor_id])) {
                    $fila['estado'] = 'error';
                    $fila['errores'][] = "Remisión ya existe en base de datos: {$remision}";
                    $datos_validados['validas']--;
                    $datos_validados['errores']++;
                }
            }
        }
    }
    
    return $datos_validados;
}

/**
 * Obtiene las remisiones de un proveedor de la base de datos
 */
function obtenerRemisionesProveedor($proveedor_id, $conn) {
    $proveedor_id = intval($proveedor_id);
    $remisiones = [];
    
    $query = "SELECT remision FROM recoleccion 
              WHERE id_prov = '$proveedor_id' 
              AND remision IS NOT NULL 
              AND remision != '' 
              AND status = '1'";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $remisiones[] = $row['remision'];
        }
    }
    
    return $remisiones;
}

// =============================================================================
// FUNCIONES DE BÚSQUEDA EN BASE DE DATOS
// =============================================================================

/**
 * Busca proveedor por código
 */
function buscarProveedor($codigo, $conn, $zona_seleccionada) {
    $codigo = $conn->real_escape_string($codigo);
    
    if ($zona_seleccionada == 0) {
        $query = "SELECT id_prov FROM proveedores WHERE cod = '$codigo' AND status = '1'";
    } else {
        $query = "SELECT id_prov FROM proveedores WHERE cod = '$codigo' AND zona = '$zona_seleccionada' AND status = '1'";
    }
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_prov'] : false;
}

/**
 * Busca bodega de proveedor por código
 */
function buscarBodegaProveedor($codigo_bodega, $proveedor_id, $conn) {
    $codigo_bodega = $conn->real_escape_string($codigo_bodega);
    $proveedor_id = intval($proveedor_id);
    
    $query = "SELECT id_direc FROM direcciones 
              WHERE cod_al = '$codigo_bodega' AND id_prov = '$proveedor_id' AND status = '1'";
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_direc'] : false;
}

/**
 * Busca fletero por placas
 */
function buscarFletero($placas, $conn, $zona_seleccionada) {
    $placas = $conn->real_escape_string($placas);
    
    if ($zona_seleccionada == 0) {
        $query = "SELECT id_transp FROM transportes WHERE placas = '$placas' AND status = '1'";
    } else {
        $query = "SELECT id_transp FROM transportes WHERE placas = '$placas' AND zona = '$zona_seleccionada' AND status = '1'";
    }
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_transp'] : false;
}

/**
 * Busca cliente por código
 */
function buscarCliente($codigo_cliente, $conn, $zona_seleccionada) {
    $codigo_cliente = $conn->real_escape_string($codigo_cliente);
    
    if ($zona_seleccionada == 0) {
        $query = "SELECT id_cli FROM clientes WHERE cod = '$codigo_cliente' AND status = '1'";
    } else {
        $query = "SELECT id_cli FROM clientes WHERE cod = '$codigo_cliente' AND zona = '$zona_seleccionada' AND status = '1'";
    }
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_cli'] : false;
}

/**
 * Busca bodega de cliente por código
 */
function buscarBodegaCliente($codigo_bodega, $cliente_id, $conn) {
    $codigo_bodega = $conn->real_escape_string($codigo_bodega);
    $cliente_id = intval($cliente_id);
    
    $query = "SELECT id_direc FROM direcciones 
              WHERE cod_al = '$codigo_bodega' AND id_us = '$cliente_id' AND status = '1'";
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_direc'] : false;
}

/**
 * Busca producto por código
 */
function buscarProducto($codigo_producto, $conn, $zona_seleccionada) {
    $codigo_producto = $conn->real_escape_string($codigo_producto);
    
    if ($zona_seleccionada == 0) {
        $query = "SELECT id_prod FROM productos WHERE cod = '$codigo_producto' AND status = '1'";
    } else {
        $query = "SELECT id_prod FROM productos WHERE cod = '$codigo_producto' AND zona = '$zona_seleccionada' AND status = '1'";
    }
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id_prod'] : false;
}

/**
 * Busca precios vigentes según fecha de recolección
 */
function buscarPreciosVigentes($fecha_recoleccion, $fletero_id, $tipo_flete, $bodega_origen, $bodega_destino, $producto_id, $bodega_cliente_id, $conn) {
    $fecha_recoleccion = $conn->real_escape_string($fecha_recoleccion);
    $fletero_id = intval($fletero_id);
    $tipo_flete = $conn->real_escape_string($tipo_flete);
    $bodega_origen = intval($bodega_origen);
    $bodega_destino = intval($bodega_destino);
    $producto_id = intval($producto_id);
    $bodega_cliente_id = intval($bodega_cliente_id);
    
    $precios = [
        'flete' => false,
        'compra' => false,
        'venta' => false
    ];
    
    // 1. Buscar precio de flete
    $query_flete = "SELECT id_precio FROM precios 
                   WHERE id_prod = '$fletero_id' 
                   AND tipo = '$tipo_flete'
                   AND origen = '$bodega_origen'
                   AND destino = '$bodega_destino'
                   AND status = '1'
                   AND fecha_ini <= '$fecha_recoleccion' 
                   AND fecha_fin >= '$fecha_recoleccion'
                   ORDER BY fecha_ini DESC 
                   LIMIT 1";
    
    $result_flete = $conn->query($query_flete);
    if ($result_flete && $result_flete->num_rows > 0) {
        $precios['flete'] = $result_flete->fetch_assoc()['id_precio'];
    }
    
    // 2. Buscar precio de compra
    $query_compra = "SELECT id_precio FROM precios 
                    WHERE id_prod = '$producto_id' 
                    AND tipo = 'c'
                    AND status = '1'
                    AND fecha_ini <= '$fecha_recoleccion' 
                    AND fecha_fin >= '$fecha_recoleccion'
                    ORDER BY fecha_ini DESC 
                    LIMIT 1";
    
    $result_compra = $conn->query($query_compra);
    if ($result_compra && $result_compra->num_rows > 0) {
        $precios['compra'] = $result_compra->fetch_assoc()['id_precio'];
    }
    
    // 3. Buscar precio de venta
    $query_venta = "SELECT id_precio FROM precios 
                   WHERE id_prod = '$producto_id' 
                   AND tipo = 'v'
                   AND destino = '$bodega_cliente_id'
                   AND status = '1'
                   AND fecha_ini <= '$fecha_recoleccion' 
                   AND fecha_fin >= '$fecha_recoleccion'
                   ORDER BY fecha_ini DESC 
                   LIMIT 1";
    
    $result_venta = $conn->query($query_venta);
    if ($result_venta && $result_venta->num_rows > 0) {
        $precios['venta'] = $result_venta->fetch_assoc()['id_precio'];
    }
    
    return $precios;
}

/**
 * Valida formato de fecha
 */
function validarFecha($fecha) {
    if (empty($fecha)) return false;
    
    // Intentar formato Y-m-d (2025-07-06)
    $d1 = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($d1 && $d1->format('Y-m-d') === $fecha) {
        return $fecha; // Ya está en formato correcto
    }
    
    // Intentar formato d/m/Y (06/07/2025)
    $d2 = DateTime::createFromFormat('d/m/Y', $fecha);
    if ($d2 && $d2->format('d/m/Y') === $fecha) {
        return $d2->format('Y-m-d'); // Convertir a Y-m-d
    }
    
    // Intentar formato d-m-Y (06-07-2025)
    $d3 = DateTime::createFromFormat('d-m-Y', $fecha);
    if ($d3 && $d3->format('d-m-Y') === $fecha) {
        return $d3->format('Y-m-d'); // Convertir a Y-m-d
    }
    
    return false;
}
/**
 * Convierte fecha a formato estándar Y-m-d
 */
function convertirF($fecha) {
    $fecha_convertida = validarFecha($fecha);
    return $fecha_convertida ? $fecha_convertida : false;
}

// =============================================================================
// FUNCIONES DE INSERCIÓN
// =============================================================================

/**
 * Inserta una recolección en la base de datos
 */
function insertarRecoleccion($fila, $conn, $id_user, $zona) {
    $datos = $fila['datos_originales'];
    $mapeados = $fila['datos_mapeados'];
    
    try {
        $conn->begin_transaction();
        // Usar las fechas ya convertidas
        $fecha_recoleccion = $datos['fecha_recoleccion'];
        $fecha_factura = $datos['fecha_factura'];
        
        // Generar folio real
        $folio_real = generarFolioReal(
            $datos['fecha_recoleccion'], 
            $zona, 
            $conn
        );
        
        if (!$folio_real) {
            throw new Exception("Error al generar folio");
        }
        
        // Validación final de remisión
        if (!empty($datos['remision'])) {
            $remision_existente = verificarRemisionExistente($datos['remision'], $mapeados['proveedor_id'], $conn);
            if ($remision_existente) {
                throw new Exception("La remisión {$datos['remision']} ya existe para este proveedor");
            }
        }
        
        // Insertar recolección principal
        $recoleccion_data = [
            'folio' => $folio_real['numero'],
            'fecha_r' => $fecha_recoleccion,
            'zona' => $zona,
            'id_prov' => $mapeados['proveedor_id'],
            'id_direc_prov' => $mapeados['bodega_proveedor_id'],
            'id_transp' => $mapeados['fletero_id'],
            'pre_flete' => $mapeados['precio_flete_id'],
            'id_cli' => $mapeados['cliente_id'],
            'id_direc_cli' => $mapeados['bodega_cliente_id'],
            'factura_v' => $datos['factura_venta'],
            'fecha_v' => $fecha_factura,
            'id_user' => $id_user,
            'status' => 1,
            'ex' => 1
        ];
        
        // Campos opcionales
        if (!empty($datos['remision'])) {
            $recoleccion_data['remision'] = $datos['remision'];
        }
        if (!empty($datos['peso_proveedor'])) {
            $recoleccion_data['peso_prov'] = floatval($datos['peso_proveedor']);
        }
        if (!empty($datos['factura_flete'])) {
            $recoleccion_data['factura_fle'] = $datos['factura_flete'];
        }
        if (!empty($datos['peso_flete'])) {
            $recoleccion_data['peso_fle'] = floatval($datos['peso_flete']);
        }
        
        // Insertar recolección
        $columns = implode(', ', array_keys($recoleccion_data));
        $placeholders = implode(', ', array_fill(0, count($recoleccion_data), '?'));
        $sql_recoleccion = "INSERT INTO recoleccion ($columns) VALUES ($placeholders)";
        
        $stmt_recoleccion = $conn->prepare($sql_recoleccion);
        if (!$stmt_recoleccion) {
            throw new Exception("Error preparando recolección: " . $conn->error);
        }
        
        $types = str_repeat('s', count($recoleccion_data));
        $stmt_recoleccion->bind_param($types, ...array_values($recoleccion_data));
        $stmt_recoleccion->execute();
        
        if ($stmt_recoleccion->errno) {
            throw new Exception("Error ejecutando recolección: " . $stmt_recoleccion->error);
        }
        
        $id_recoleccion = $conn->insert_id;
        
        // Si el precio del flete es 0, actualizar factura_fle a 'N/A'
        if ($mapeados['precio_flete_id']) {
            $precio_flete_query = $conn->query("SELECT precio FROM precios WHERE id_precio = '{$mapeados['precio_flete_id']}'");
            if ($precio_flete_query && $precio_flete_query->num_rows > 0) {
                $precio_flete_data = $precio_flete_query->fetch_assoc();
                if ($precio_flete_data['precio'] == 0) {
                    $conn->query("UPDATE recoleccion SET factura_fle = 'N/A' WHERE id_recol = '$id_recoleccion'");
                }
            }
        }
        
        // Insertar relación con producto
        $producto_recole_data = [
            'id_recol' => $id_recoleccion,
            'id_prod' => $mapeados['producto_id'],
            'id_cprecio_c' => $mapeados['precio_compra_id'],
            'id_cprecio_v' => $mapeados['precio_venta_id'],
            'id_user' => $id_user,
            'status' => 1
        ];
        
        $columns_pro = implode(', ', array_keys($producto_recole_data));
        $placeholders_pro = implode(', ', array_fill(0, count($producto_recole_data), '?'));
        $sql_producto = "INSERT INTO producto_recole ($columns_pro) VALUES ($placeholders_pro)";
        
        $stmt_producto = $conn->prepare($sql_producto);
        if (!$stmt_producto) {
            throw new Exception("Error preparando producto_recole: " . $conn->error);
        }
        
        $types_pro = str_repeat('s', count($producto_recole_data));
        $stmt_producto->bind_param($types_pro, ...array_values($producto_recole_data));
        $stmt_producto->execute();
        
        if ($stmt_producto->errno) {
            throw new Exception("Error ejecutando producto_recole: " . $stmt_producto->error);
        }
        
        $conn->commit();
        
        return [
            'exito' => true,
            'folio' => $folio_real['completo'],
            'id_recoleccion' => $id_recoleccion
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'exito' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Verifica si una remisión ya existe para un proveedor
 */
function verificarRemisionExistente($remision, $proveedor_id, $conn) {
    $remision = $conn->real_escape_string($remision);
    $proveedor_id = intval($proveedor_id);
    
    $query = "SELECT id_recol FROM recoleccion 
              WHERE remision = '$remision' 
              AND id_prov = '$proveedor_id' 
              AND status = '1'";
    
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0);
}

/**
 * Genera folio real para inserción
 */
function generarFolioReal($fecha_recoleccion, $zona_seleccionada, $conn) {
    if ($zona_seleccionada == 0) {
        $zona_query = "SELECT * FROM zonas WHERE status = '1' LIMIT 1";
    } else {
        $zona_query = "SELECT * FROM zonas WHERE status = '1' AND id_zone = '$zona_seleccionada'";
    }
    
    $zona_result = $conn->query($zona_query);
    if (!$zona_result || $zona_result->num_rows === 0) {
        return false;
    }
    
    $zona_data = $zona_result->fetch_assoc();
    $fecha_formateada = date('ym', strtotime($fecha_recoleccion));
    
    // Buscar último folio del mes
    $mes_actual = date('m', strtotime($fecha_recoleccion));
    $anio_actual = date('Y', strtotime($fecha_recoleccion));
    
    $folio_query = "SELECT folio FROM recoleccion 
                   WHERE status = '1' 
                   AND YEAR(fecha_r) = '$anio_actual'  
                   AND MONTH(fecha_r) = '$mes_actual' 
                   AND zona = '{$zona_data['id_zone']}'
                   ORDER BY folio DESC 
                   LIMIT 1";
    
    $folio_result = $conn->query($folio_query);
    
    if ($folio_result && $folio_result->num_rows > 0) {
        $ultimo_folio = intval($folio_result->fetch_assoc()['folio']);
        $nuevo_numero = $ultimo_folio + 1;
        
        if ($nuevo_numero > 1111) {
            return false;
        } else {
            $folio_numero = str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
        }
    } else {
        $folio_numero = '0001';
    }
    
    return [
        'numero' => $folio_numero,
        'completo' => $zona_data['cod'] . "-" . $fecha_formateada . $folio_numero
    ];
}

// =============================================================================
// FUNCIONES DE VISUALIZACIÓN
// =============================================================================

/**
 * Genera la vista previa mejorada con más datos
 */
function generarVistaPreviaMejorada($datos_validados, $nombre_archivo) {
    $html = '
      <div class="alert alert-info">
        <h5><i class="bi bi-eye me-2"></i>Vista previa - ' . htmlspecialchars($nombre_archivo) . '</h5>
        <p class="mb-0">
            <strong>Total filas:</strong> ' . $datos_validados['total_filas'] . ' | 
            <strong>Válidas:</strong> <span class="text-success">' . $datos_validados['validas'] . '</span> | 
            <strong>Con errores:</strong> <span class="text-danger">' . $datos_validados['errores'] . '</span>
        </p>
        <div class="mt-2 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Formatos de fecha aceptados:</strong> YYYY-MM-DD (2025-07-06) o DD/MM/YYYY (06/07/2025)
        </div>
    </div>';
    
    if ($datos_validados['validas'] == 0) {
        $html .= '
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No hay registros válidos para importar. Corrija los errores e intente nuevamente.
        </div>';
    }
    
    $html .= '
    <div class="csv-preview">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light sticky-top">
                    <tr>
                        <th># Fila</th>
                        <th>Estado</th>
                        <th>Fecha Rec.</th>
                        <th>Proveedor</th>
                        <th>Bod. Prov.</th>
                        <th>Fletero</th>
                        <th>Tipo Flete</th>
                        <th>Cliente</th>
                        <th>Bod. Cli.</th>
                        <th>Producto</th>
                        <th>Factura Venta</th>
                        <th>Remisión</th>
                        <th>Peso Prov (kg)</th>
                        <th>Factura Flete</th>
                        <th>Peso Flete (kg)</th>
                        <th>Folio Temporal</th>
                        <th>Errores/Advertencias</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($datos_validados['datos'] as $fila) {
        $clase_fila = $fila['estado'] == 'valid' ? 'status-valid' : 'status-error';
        $badge_estado = $fila['estado'] == 'valid' ? 
            '<span class="badge bg-success badge-custom">Válido</span>' : 
            '<span class="badge bg-danger badge-custom">Error</span>';
        
        $datos = $fila['datos_originales'];
        
        // Mostrar información de conversión de fechas si aplica
        $info_fecha_recoleccion = htmlspecialchars($datos['fecha_recoleccion']);
        if (isset($datos['fecha_recoleccion_original']) && $datos['fecha_recoleccion_original'] != $datos['fecha_recoleccion']) {
            $info_fecha_recoleccion .= '<br><small class="text-muted">Original: ' . htmlspecialchars($datos['fecha_recoleccion_original']) . '</small>';
        }
        
        $info_fecha_factura = htmlspecialchars($datos['fecha_factura']);
        if (isset($datos['fecha_factura_original']) && $datos['fecha_factura_original'] != $datos['fecha_factura']) {
            $info_fecha_factura .= '<br><small class="text-muted">Original: ' . htmlspecialchars($datos['fecha_factura_original']) . '</small>';
        }
        
        $html .= '
                <tr class="' . $clase_fila . '">
                    <td><strong>' . $fila['numero_fila'] . '</strong></td>
                    <td>' . $badge_estado . '</td>
                    <td>' . htmlspecialchars($datos['fecha_recoleccion']) . '</td>
                    <td>' . htmlspecialchars($datos['proveedor']) . '</td>
                    <td>' . htmlspecialchars($datos['bodega_proveedor']) . '</td>
                    <td>' . htmlspecialchars($datos['fletero']) . '</td>
                    <td><span class="badge bg-' . ($datos['tipo_flete'] == 'FT' ? 'primary' : 'indigo') . '">' . htmlspecialchars($datos['tipo_flete']) . '</span></td>
                    <td>' . htmlspecialchars($datos['cliente']) . '</td>
                    <td>' . htmlspecialchars($datos['bodega_cliente']) . '</td>
                    <td>' . htmlspecialchars($datos['producto']) . '</td>
                    <td>' . htmlspecialchars($datos['factura_venta']) . '</td>
                    <td>' . (empty($datos['remision']) ? '<span class="text-muted">-</span>' : htmlspecialchars($datos['remision'])) . '</td>
                    <td class="text-end">' . (empty($datos['peso_proveedor']) ? '<span class="text-muted">-</span>' : number_format(floatval($datos['peso_proveedor']), 2)) . '</td>
                    <td>' . (empty($datos['factura_flete']) ? '<span class="text-muted">-</span>' : htmlspecialchars($datos['factura_flete'])) . '</td>
                    <td class="text-end">' . (empty($datos['peso_flete']) ? '<span class="text-muted">-</span>' : number_format(floatval($datos['peso_flete']), 2)) . '</td>
                    <td><code>' . ($fila['datos_mapeados']['folio'] ?? 'N/A') . '</code></td>
                    <td>';
        
        if (!empty($fila['errores'])) {
            $html .= '<ul class="small mb-0 ps-3">';
            foreach ($fila['errores'] as $error) {
                $html .= '<li class="text-danger">' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul>';
        } else {
            // Mostrar advertencias si hay campos opcionales vacíos que podrían ser importantes
            $advertencias = [];
            
            if (empty($datos['remision'])) {
                $advertencias[] = "Sin remisión";
            }
            if (empty($datos['peso_proveedor'])) {
                $advertencias[] = "Sin peso proveedor";
            }
            if (empty($datos['factura_flete']) && !empty($fila['datos_mapeados']['precio_flete_id'])) {
                $advertencias[] = "Sin factura flete";
            }
            if (empty($datos['peso_flete']) && !empty($fila['datos_mapeados']['precio_flete_id'])) {
                $advertencias[] = "Sin peso flete";
            }
            
            if (!empty($advertencias)) {
                $html .= '<ul class="small mb-0 ps-3">';
                foreach ($advertencias as $adv) {
                    $html .= '<li class="text-warning"><small>' . htmlspecialchars($adv) . '</small></li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<span class="text-success small"><i class="bi bi-check-circle"></i> Completo</span>';
            }
        }
        
        $html .= '
                    </td>
                </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
    </div>';
    
    // Botones de acción
    if ($datos_validados['validas'] > 0) {
        $html .= '
        <div class="mt-4 p-3 bg-body-tertiary rounded">
            <form method="post">
                <input type="hidden" name="datos_validados" value="' . htmlspecialchars(json_encode($datos_validados)) . '">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>¿Continuar con la importación?</strong><br>
                    <ul class="mb-2">
                        <li>Se importarán <strong>' . $datos_validados['validas'] . '</strong> recolecciones</li>
                        <li>Los folios finales se generarán al momento de la importación</li>
                        <li>Las remisiones se validarán nuevamente antes de insertar</li>
                        <li>Esta acción no se puede deshacer</li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="procesar_importacion" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Sí, importar ' . $datos_validados['validas'] . ' recolecciones
                    </button>
                    <a href="?p=importar_recolecciones" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>';
    } else {
        $html .= '
        <div class="text-center mt-3">
            <a href="?p=importar_recolecciones" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Volver a importar
            </a>
        </div>';
    }
    
    return $html;
}

/**
 * Obtiene mensaje de error de upload
 */
function obtenerMensajeErrorUpload($error_code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
    ];
    
    return $errors[$error_code] ?? 'Error desconocido';
}
?>