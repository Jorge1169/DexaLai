<?php
session_start();
require_once 'config/conexiones.php';

// Función para enviar errores en formato JSON
function sendError($message) {
    header('Content-Type: application/json');
    echo json_encode(["error" => $message]);
    exit;
}

// Verificar conexión
if ($conn_mysql->connect_error) {
    sendError("Conexión fallida: " . $conn_mysql->connect_error);
}

try {
    // Parámetros de DataTables
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $search = $conn_mysql->real_escape_string($_POST['search']['value'] ?? '');
    $order_column = intval($_POST['order'][0]['column'] ?? 20);
    $order_dir = in_array(strtoupper($_POST['order'][0]['dir'] ?? 'DESC'), ['ASC', 'DESC']) ? $_POST['order'][0]['dir'] : 'DESC';
    $filtroEstado = $_POST['filtroEstado'] ?? 'todas';

    // Mapeo de columnas - ACTUALIZADO para nueva columna
    $columns = [
        1 => 'folio_completo',
        2 => 'p.cod',
        3 => 'dp.cod_al',
        4 => 't.placas',
        5 => 'c.cod',
        6 => 'r.peso_prov',
        7 => 'pc.precio',
        8 => 'total_compra',
        9 => 'r.remision',
        10 => 'r.factura_pro',
        11 => 'folio_proveedor',
        12 => 'r.peso_fle',
        13 => 'pf.precio',
        14 => 'precio_flete_real', // Esta es para ordenar por el IMPORTE flete (columna 14 en HTML)
        15 => 'precio_flete_real', // IMPORTANTE: Para ordenar por precio por kilo necesitamos agregar un cálculo
        16 => 'r.factura_fle',
        17 => 'folio_fletero',
        18 => 'pv.precio',
        19 => 'total_venta',
        21 => 'utilidad_estimada',
        22 => 'r.fecha_r' // Índice actualizado
    ];

    // FUNCIÓN PARA CALCULAR PRECIO FLETE REAL
    // FUNCIÓN PARA CALCULAR PRECIO FLETE REAL Y PRECIO POR KILO
function calcularPrecioFleteReal($precio_base, $tipo_flete, $peso_minimo, $peso_flete_kg, &$precio_por_kilo = null) {
    if (empty($peso_flete_kg) || $peso_flete_kg <= 0) {
        $precio_por_kilo = 0;
        return floatval($precio_base);
    }

    $peso_flete_ton = floatval($peso_flete_kg) / 1000;
    $precio_base = floatval($precio_base);
    $peso_minimo = floatval($peso_minimo);
    $precio_flete_real = 0;

    if ($tipo_flete == 'FV') {
        // Flete por viaje
        $precio_flete_real = $precio_base;
        $precio_por_kilo = ($peso_flete_kg > 0) ? $precio_flete_real / $peso_flete_kg : 0;
    } elseif ($tipo_flete == 'FT') {
        // Flete por tonelada
        if ($peso_minimo > 0) {
            if ($peso_flete_ton <= $peso_minimo) {
                $precio_flete_real = $precio_base * $peso_minimo;
            } else {
                $precio_flete_real = $precio_base * $peso_flete_ton;
            }
        } else {
            $precio_flete_real = $precio_base * $peso_flete_ton;
        }
        $precio_por_kilo = ($peso_flete_kg > 0) ? $precio_flete_real / $peso_flete_kg : 0;
    } else {
        // Tipo desconocido
        $precio_flete_real = $precio_base;
        $precio_por_kilo = 0;
    }

    return $precio_flete_real;
}

    // CONSULTA BASE SIN LIMIT PARA CONTEO
$baseQuery = "SELECT 
r.*,
r.Vfac_com AS factura_complemento, -- NUEVO: Complemento de factura
r.Vfech_com AS fecha_complemento, -- NUEVO: Fecha complemento
r.Vcomp_com AS comprobar_complemento, -- NUEVO: Comprobación complemento
r.remision AS remision_compra,
r.alias_inv_fle AS alias_fletero,
r.folio_inv_fle AS folio_fletero,
r.alias_inv_pro AS alias_proveedor,
r.folio_inv_pro AS folio_proveedor,
r.doc_pro AS documento_proveedor,      -- NUEVO: Factura proveedor
r.d_f_p AS evidencia_proveedor,        -- NUEVO: Evidencia proveedor
r.doc_fle AS documento_fletero,        -- NUEVO: Factura fletero
r.d_f_f AS evidencia_fletero,          -- NUEVO: Evidencia fletero

p.cod AS cod_proveedor,
dp.cod_al AS cod_bodega_proveedor,

t.placas AS cod_fletero,

c.cod AS cod_cliente,
c.fac_rem AS factura_remision, -- Nuevo: factura o recoleccion
pc.precio AS precio_compra,
pv.precio AS precio_venta,
pf.precio AS precio_flete_base,
pf.tipo AS tipo_flete,
pf.conmin AS peso_minimo,

z.cod AS cod_zona,
z.PLANTA AS planta_zona,

pr.nom_pro AS nombre_producto,

CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo

FROM recoleccion r
LEFT JOIN proveedores p ON r.id_prov = p.id_prov
LEFT JOIN direcciones dp ON r.id_direc_prov = dp.id_direc
LEFT JOIN transportes t ON r.id_transp = t.id_transp
LEFT JOIN clientes c ON r.id_cli = c.id_cli
LEFT JOIN direcciones dc ON r.id_direc_cli = dc.id_direc
LEFT JOIN zonas z ON r.zona = z.id_zone
LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
WHERE r.status = '1'";

    // Filtros de fecha
if (isset($_POST['fechaInicio']) && !empty($_POST['fechaInicio'])) {
    $fechaInicio = $conn_mysql->real_escape_string($_POST['fechaInicio']);
    $baseQuery .= " AND r.fecha_r >= '$fechaInicio'";
} else {
        // Por defecto, mes actual
    $primerDiaMes = date('Y-m-01');
    $baseQuery .= " AND r.fecha_r >= '$primerDiaMes'";
}

if (isset($_POST['fechaFin']) && !empty($_POST['fechaFin'])) {
    $fechaFin = $conn_mysql->real_escape_string($_POST['fechaFin']);
    $baseQuery .= " AND r.fecha_r <= '$fechaFin 23:59:59'";
}

    // Filtro de zona
if (isset($_POST['zona']) && $_POST['zona'] != '0') {
    $zona = intval($_POST['zona']);
    $baseQuery .= " AND r.zona = $zona";
}

    // Filtro por estado de contrarecibos - ACTUALIZADO
if ($filtroEstado != 'todas') {
    switch ($filtroEstado) {
        case 'completas':
            // Ambos contrarecibos presentes (incluyendo N/A para flete)
        $baseQuery .= " AND (r.folio_inv_pro IS NOT NULL AND r.folio_inv_pro != '') 
        AND (r.folio_inv_fle IS NOT NULL OR r.factura_fle = 'N/A')";
        break;

        case 'solo-flete':
            // Solo tiene contra recibo de flete (o N/A) pero NO tiene de compra
        $baseQuery .= " AND ((r.folio_inv_fle IS NOT NULL AND r.folio_inv_fle != '') 
        OR (pf.precio = 0)) 
        AND (r.folio_inv_pro IS NULL OR r.folio_inv_pro = '')";
        break;

        case 'solo-compra':
            // Solo tiene contra recibo de compra pero NO tiene de flete (ni N/A)
        $baseQuery .= " AND (r.folio_inv_pro IS NOT NULL AND r.folio_inv_pro != '') 
        AND (r.folio_inv_fle IS NULL OR r.folio_inv_fle = '') 
        AND pf.precio != 0";
        break;

        case 'pendientes':
            // No tiene ningún contra recibo (ni N/A para flete)
        $baseQuery .= " AND (r.folio_inv_pro IS NULL OR r.folio_inv_pro = '') 
        AND (r.folio_inv_fle IS NULL OR r.folio_inv_fle = '') 
        AND pf.precio != 0";
        break;
    }
}

    // Búsqueda - AGREGADO BÚSQUEDA DE COMPLEMENTO
$searchQuery = $baseQuery;
if (!empty($search)) {
    $searchQuery .= " AND (
    r.folio LIKE '%$search%'
    OR p.cod LIKE '%$search%' 
    OR p.rs LIKE '%$search%' 
    OR dp.cod_al LIKE '%$search%' 
    OR t.placas LIKE '%$search%' 
    OR c.cod LIKE '%$search%'
    OR r.factura_pro LIKE '%$search%'
    OR r.peso_prov LIKE '%$search%'
    OR r.peso_fle LIKE '%$search'
    OR r.factura_fle LIKE '%$search%' 
    OR r.factura_v LIKE '%$search%'
    OR r.Vfac_com LIKE '%$search%' -- NUEVO: Búsqueda de complemento
    OR r.remision LIKE '%$search%'
    OR pv.precio LIKE '%$search%'
    OR pc.precio LIKE '%$search%'
    OR r.fecha_r LIKE '%$search%'
    OR CONCAT (r.alias_inv_pro,'-',r.folio_inv_pro) LIKE '%$search%'
    OR CONCAT (r.alias_inv_fle,'-',r.folio_inv_fle) LIKE '%$search%'
    OR CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) LIKE '%$search%'
)";
}

    // CONTAR TOTAL DE REGISTROS FILTRADOS (para recordsFiltered)
$countFilteredQuery = "SELECT COUNT(*) as total_count FROM ($searchQuery) as count_table";
$countFilteredResult = $conn_mysql->query($countFilteredQuery);
if (!$countFilteredResult) {
    throw new Exception("Error en consulta de conteo filtrado: " . $conn_mysql->error);
}
$total_count_filtered = $countFilteredResult->fetch_assoc()['total_count'];

    // CONTAR TOTAL DE REGISTROS GENERAL (para recordsTotal)
$countTotalQuery = "SELECT COUNT(*) as total FROM recoleccion WHERE status = '1'";
$countTotalResult = $conn_mysql->query($countTotalQuery);
$total_records = $countTotalResult ? $countTotalResult->fetch_assoc()['total'] : 0;

    // CONSULTA PRINCIPAL CON PAGINACIÓN
$order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'r.fecha_r';
$mainQuery = $searchQuery . " ORDER BY $order_by $order_dir LIMIT $start, $length";

    // Ejecutar consulta principal
$result = $conn_mysql->query($mainQuery);
if (!$result) {
    throw new Exception("Error en consulta principal: " . $conn_mysql->error);
}

$data = [];
$utilidad_total = 0;
$total_ventas = 0;
$total_compras = 0;
$total_fletes = 0;
$total_productos = 0;
$total_recolecciones = 0;
$detalle_productos = [];

while ($row = $result->fetch_assoc()) {
    $total_recolecciones++;

    // Cálculos básicos - ahora con precio por kilo
    $precio_por_kilo = 0;
    $precio_flete_real = calcularPrecioFleteReal(
        $row['precio_flete_base'] ?? 0, 
        $row['tipo_flete'] ?? '', 
        $row['peso_minimo'] ?? 0, 
        $row['peso_fle'] ?? 0,
        $precio_por_kilo // Pasar por referencia
    );

    $peso_prov = floatval($row['peso_prov'] ?? 0);
    $precio_compra = floatval($row['precio_compra'] ?? 0);
    $peso_fle = floatval($row['peso_fle'] ?? 0);
    $precio_venta = floatval($row['precio_venta'] ?? 0);
    $total_compra = $peso_prov * $precio_compra;
    
    // VERIFICAR SI EL FLETE ES GRATIS (precio = 0)
    $flete_gratis = (floatval($row['precio_flete_base'] ?? 0) == 0 && $precio_flete_real == 0);
    
    // CÁLCULO DE UTILIDAD - MODIFICADO PARA FLETES GRATIS
    $utilidad_estimada = 0;
    $total_venta = 0;

    if ($flete_gratis) {
        $total_venta = $peso_prov * $precio_venta;
        // Si el flete es gratis, la utilidad es: venta - compra
        $utilidad_estimada = $total_venta - $total_compra;
    } else {
        if ($peso_fle == 0) {
            $total_venta = $peso_prov * $precio_venta;   
        } else {
            $total_venta = $peso_fle * $precio_venta;
        }

        if ($peso_prov > 0 && $precio_compra > 0 && $peso_fle > 0 && $precio_venta > 0) {
            // Si hay costo de flete, la utilidad es: venta - compra - flete
            $utilidad_estimada = $total_venta - $total_compra - $precio_flete_real;
        }
    }
    
    // Acumular totales
    $utilidad_total += $utilidad_estimada;
    $total_ventas += $total_venta;
    $total_compras += $total_compra;

    // Para el total de fletes, solo sumar si NO es gratis
    if (!$flete_gratis) {
        $total_fletes += $precio_flete_real;
    }

    $total_productos += $peso_prov;
    
    // NUEVO: FACTURA COMPRA CON DROPDOWN PARA DOS DOCUMENTOS
    $factura_compra = '<span class="badge badge-documento bg-secondary">Pendiente</span>';
    if (!empty($row['factura_pro'])) {
        if (!empty($row['documento_proveedor'])) {
            $facturaPro = $invoiceLK . htmlspecialchars($row['documento_proveedor']) . ".pdf";
            $evidenciaPro = !empty($row['evidencia_proveedor']) ? $invoiceLK . htmlspecialchars($row['evidencia_proveedor']) . ".pdf" : '';
            
            $factura_compra = '
            <div class="dropdown d-inline-block">
            <button class="btn btn-success dropdown-toggle btn-sm rounded-3 py-0" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> ' . htmlspecialchars($row['factura_pro']) . '
            </button>
            <ul class="dropdown-menu">
            <li><a class="dropdown-item small" href="' . $facturaPro . '" target="_blank"><i class="bi bi-file-text me-2"></i>Factura</a></li>
            ' . (!empty($row['evidencia_proveedor']) ? 
                '<li><a class="dropdown-item small" href="' . $evidenciaPro . '" target="_blank"><i class="bi bi-file-check me-2"></i>Evidencia</a></li>' 
                : '') . '
            </ul>   
            </div>';
        } else {
            $factura_compra = '<span class="badge badge-documento bg-danger">'.htmlspecialchars($row['factura_pro']).'</span>';
        }
    }

    $contra_recibo_compra = '<span class="badge badge-documento bg-secondary">Pendiente</span>';
    if (!empty($row['folio_proveedor'])) {
        $contra_recibo_compra = '<a href="'.$link.$row['alias_proveedor'].'-'.$row['folio_proveedor'].'" target="_blank"><span class="badge badge-documento bg-success">'.htmlspecialchars($row['alias_proveedor'] ?? '').'-'.htmlspecialchars($row['folio_proveedor']).'</a></span>';
    }

    // NUEVO: FACTURA FLETE CON DROPDOWN PARA DOS DOCUMENTOS - MODIFICADA PARA FLETES GRATIS
    $factura_flete = '<span class="badge badge-documento bg-secondary">Pendiente</span>';
    if ($flete_gratis) {
        $factura_flete = '<span class="badge badge-documento bg-indigo">N/A</span>';
    } elseif (!empty($row['factura_fle'])) {
        if (!empty($row['documento_fletero'])) {
            $facturaFle = $invoiceLK . htmlspecialchars($row['documento_fletero']) . ".pdf";
            $evidenciaFle = !empty($row['evidencia_fletero']) ? $invoiceLK . htmlspecialchars($row['evidencia_fletero']) . ".pdf" : '';
            
            $factura_flete = '
            <div class="dropdown d-inline-block">
            <button class="btn btn-success dropdown-toggle btn-sm rounded-3 py-0" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> ' . htmlspecialchars($row['factura_fle']) . '
            </button>
            <ul class="dropdown-menu">
            <li><a class="dropdown-item small" href="' . $facturaFle . '" target="_blank"><i class="bi bi-file-text me-2"></i>Factura</a></li>
            ' . (!empty($row['evidencia_fletero']) ? 
                '<li><a class="dropdown-item small" href="' . $evidenciaFle . '" target="_blank"><i class="bi bi-file-check me-2"></i>Evidencia</a></li>' 
                : '') . '
            </ul>   
            </div>';
        } else {
            $factura_flete = '<span class="badge badge-documento bg-danger">'.htmlspecialchars($row['factura_fle']).'</span>';
        }
    }

    // CONTRA RECIBO FLETE - MODIFICADA PARA FLETES GRATIS
    $contra_recibo_flete = '<span class="badge badge-documento bg-secondary">Pendiente</span>';
    if ($flete_gratis) {
        $contra_recibo_flete = '<span class="badge badge-documento bg-indigo">N/A</span>';
    } elseif (!empty($row['folio_fletero'])) {
        $contra_recibo_flete = '<a href="'.$link.$row['alias_fletero'].'-'.$row['folio_fletero'].'" target="_blank"><span class="badge badge-documento bg-success">'.htmlspecialchars($row['alias_fletero'] ?? '').'-'.htmlspecialchars($row['folio_fletero']).'</a></span>';
    }

    // =============================================
    // NUEVA SECCIÓN: FACTURA DE VENTA + COMPLEMENTO
    // =============================================
    $complemento_existe = !empty($row['factura_complemento']);
    
    // Factura venta principal
    $factura_venta_principal = '<span class="badge badge-documento bg-secondary">Pendiente</span>';
    if (!empty($row['factura_v'])) {
        $esCorrecto = isset($row['factus_v_corr']) && $row['factus_v_corr'] == 1;

        if ($esCorrecto) {
            $mesesA = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];
            $fecha_v = $row['fecha_v'] ?? $row['fecha_r'] ?? date('Y-m-d');
            $numero_mesA = date('m', strtotime($fecha_v)) - 1;
            $anoA = date('Y', strtotime($fecha_v));
            $planta = $row['planta_zona'] ?? 'default';
            // Seleccionar base de URL según si la factura pertenece al año actual o es anterior
            $base_actual = 'https://glama.esasacloud.com/doctos/';
            $base_antiguo = 'https://olddocs.esasacloud.com/olddocs-01/cpu27/';
            $base = ($anoA != date('Y')) ? $base_antiguo : $base_actual;

            if ($row['factura_remision'] == 'FAC') {
                $url_principal = $base . $planta.'/FACTURAS/'.$anoA.'/'.$mesesA[$numero_mesA].'/SIGN_'.$row['factura_v'].'.pdf';
            } else {
                $url_principal = $base . $planta.'/REMISIONES/'.$anoA.'/'.$mesesA[$numero_mesA].'/SIGN_'.$row['factura_v'].'.pdf';
            }

            $factura_venta_principal = '<a href="'.htmlspecialchars($url_principal).'" target="_blank" class="badge badge-documento bg-success text-decoration-none" title="Factura de venta">'.htmlspecialchars($row['factura_v']).'</a>';
        } else {
            $factura_venta_principal = '<span class="badge badge-documento bg-danger">'.htmlspecialchars($row['factura_v']).'</span>';
        }
    }

    // Complemento de factura
    $complemento_html = '';
    if ($complemento_existe) {
        // Generar URL del complemento
        $mesesA_complemento = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];
        $fecha_complemento = $row['fecha_complemento'] ?? $row['fecha_v'] ?? $row['fecha_r'] ?? date('Y-m-d');
        $numero_mesA_complemento = date('m', strtotime($fecha_complemento)) - 1;
        $anoA_complemento = date('Y', strtotime($fecha_complemento));
        $planta = $row['planta_zona'] ?? 'default';
        
        $url_complemento = 'https://glama.esasacloud.com/doctos/'.$planta.'/FACTURAS/'.$anoA_complemento.'/'.$mesesA_complemento[$numero_mesA_complemento].'/SIGN_'.$row['factura_complemento'].'.pdf';
        
        // Usar Vcomp_com para determinar estado del PDF
        if ($row['comprobar_complemento'] == 1) {
            $complemento_html = '
            <div class="mt-1">
                <a href="'.htmlspecialchars($url_complemento).'" target="_blank" class="badge badge-documento bg-primary text-decoration-none" title="Complemento de factura">
                    '.htmlspecialchars($row['factura_complemento']).'
                </a>
            </div>';
        } else {
            $complemento_html = '
            <div class="mt-1">
                <span class="badge badge-documento bg-danger">'.htmlspecialchars($row['factura_complemento']).'</span>
            </div>';
        }
    }

    // Combinar factura principal y complemento
    $factura_venta = '
    <div class="factura-venta-container">
        <div class="factura-principal">
            '.$factura_venta_principal.'
        </div>
        '.$complemento_html.'
    </div>';
    
    $TipoFleAler = '';
    if ($row['tipo_flete'] == 'FV') {
        $TipoFleAler = '<span class="badge bg-indigo">V</span>';
    } elseif ($row['tipo_flete'] == 'FT') {
        $TipoFleAler = '<span class="badge bg-primary">T</span>';
    }

    //comprovar ticket de remision

    $url_comprobacion_remision = '';
    $remision0 = $row['remision_compra'];
    
    if ($row['remi_compro'] == 1) {
        $url_comprobacion_remision = "https://globaltycloud.com.mx:4013/externo/laisa/bascula/ticket.aspx?&rem=" . urlencode($row['remision']);
        $remision0 = '<a href="'.$url_comprobacion_remision.'" target="_blank" class="link-underline link-underline-opacity-0 text-success fw-bold" title="Remisión '.$row['remision'].'"><i class="bi bi-ticket"></i>'.$row['remision'].'</a>';
    } else {
        $url_comprobacion_remision = '';
    }

    // Fecha formateada
    $fecha_recoleccion = !empty($row['fecha_r']) ? date('Y-m-d', strtotime($row['fecha_r'])) : '';

    $data[] = [
        '', // 0 - #
        $row['folio_completo'] ?? '', // 1 - Folio
        htmlspecialchars($row['cod_proveedor'] ?? ''), // 2 - Proveedor
        htmlspecialchars($row['cod_bodega_proveedor'] ?? ''), // 3 - Bodega Prov
        htmlspecialchars($row['cod_fletero'] ?? ''), // 4 - Fletero
        htmlspecialchars($row['cod_cliente'] ?? ''), // 5 - Cliente
        number_format($peso_prov, 2) . ' kg', // 6 - Peso Compra
        '$' . number_format($precio_compra, 2), // 7 - Precio Compra
        '$' . number_format($total_compra, 2), // 8 - Total Compra
        $remision0, // 9 - Remisión Compra
        $factura_compra, // 10 - Factura Compra
        $contra_recibo_compra, // 11 - C.R Compra
        number_format($peso_fle, 2) . ' kg', // 12 - Peso Flete
        '$' . number_format($row['precio_flete_base'] ?? 0, 2).' '.$TipoFleAler, // 13 - Precio Flete
        '$' . number_format($precio_por_kilo, 4), // 14 - NUEVO: Precio Flete por Kilo (4 decimales)
        '$' . number_format($precio_flete_real, 2), // 15 - Importe Flete
        $factura_flete, // 16 - Factura Flete
        $contra_recibo_flete, // 17 - C.R Flete
        '$' . number_format($precio_venta, 2), // 18 - Precio Venta
        '$' . number_format($total_venta, 2), // 19 - Total Venta
        $factura_venta, // 20 - Factura Venta
        '$' . number_format($utilidad_estimada, 2), // 21 - Utilidad
        $fecha_recoleccion, // 22 - Fecha
        $row['id_recol'] ?? 0 // 23 - ID (oculto)
    ];
} // CIERRA EL BUCLE WHILE AQUÍ

// Respuesta CORREGIDA (fuera del bucle while)
$response = [
    "draw" => intval($_POST['draw'] ?? 1),
    "recordsTotal" => intval($total_records),
    "recordsFiltered" => intval($total_count_filtered),
    "data" => $data,
    "totalRegistros" => $total_recolecciones,
    "utilidadTotal" => $utilidad_total,
    "totalVentas" => $total_ventas,
    "totalCompras" => $total_compras,
    "totalFletes" => $total_fletes,
    "totalProductos" => $total_productos,
    "detalleProductos" => $detalle_productos
];

header('Content-Type: application/json');
echo json_encode($response);

} catch (Exception $e) {
    //error_log("ERROR: " . $e->getMessage());
    sendError("Error en el servidor: " . $e->getMessage());
}

$conn_mysql->close();
?>