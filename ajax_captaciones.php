<?php
// ajax_captaciones.php
session_start();
require_once 'config/conexiones.php';

$zona_seleccionada = intval($_SESSION['selected_zone'] ?? 0);
if (isset($_POST['zona'])) {
    $zona_post = intval($_POST['zona']);
    if ($zona_post > 0) {
        $zona_seleccionada = $zona_post;
    }
}

// Parámetros de DataTables
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$order_column = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';

// Mapeo de columnas (debe coincidir con el orden en la tabla)
$columns = [
    0 => 'c.id_captacion', // #
    2 => 'c.fecha_captacion', // Folio/Fecha
    3 => 'p.rs', // Proveedor/Almacén
    4 => 'z.PLANTA', // Zona
    5 => 'cantidad_productos', // Productos
    6 => 'total_kilos', // Peso Total
    7 => 'costo_productos', // Costo Prod.
    8 => 'costo_flete', // Costo Flete
    9 => 'costo_total', // Total
    10 => 't.razon_so' // Fletero
];

// Modificar la consulta principal para incluir contra recibos
$query = "SELECT 
c.id_captacion,
c.folio,
c.fecha_captacion,
c.status,
z.cod as cod_zona,
z.PLANTA as nombre_zona,
z.tipo as tipo_zona,
p.cod as cod_proveedor,
p.rs as nombre_proveedor,
a.cod as cod_almacen,
a.nombre as nombre_almacen,
t.placas as placas_fletero,
t.razon_so as nombre_fletero,
COUNT(DISTINCT cd.id_detalle) as cantidad_productos,
COALESCE(SUM(cd.total_kilos), 0) as total_kilos,
COALESCE(SUM(pc.precio * cd.total_kilos), 0) as costo_productos,
COALESCE(pf.precio, 0) as costo_flete,
(COALESCE(SUM(pc.precio * cd.total_kilos), 0) + COALESCE(pf.precio, 0)) as costo_total,
GROUP_CONCAT(DISTINCT cd.numero_factura SEPARATOR ', ') as facturas_productos,
GROUP_CONCAT(DISTINCT cd.comprobante_ticket SEPARATOR '; ') as comprobantes_productos_list,
-- Documentos de factura de productos
GROUP_CONCAT(DISTINCT cd.doc_factura SEPARATOR '; ') as doc_facturas_list,
GROUP_CONCAT(DISTINCT cd.com_factura SEPARATOR '; ') as com_facturas_list,
-- Documentos de factura de flete
cf.numero_factura_flete,
cf.doc_factura_flete,
cf.com_factura_flete,
cf.id_capt_flete,
-- Contra recibos de productos
GROUP_CONCAT(DISTINCT CONCAT(cd.aliascap, '-', cd.foliocap) SEPARATOR '; ') as contra_recibos_productos,
GROUP_CONCAT(DISTINCT cd.foliocap SEPARATOR '; ') as folios_cr_productos,
-- Contra recibo de flete
cf.aliascap_flete,
cf.foliocap_flete

FROM captacion c
LEFT JOIN zonas z ON c.zona = z.id_zone
LEFT JOIN proveedores p ON c.id_prov = p.id_prov
LEFT JOIN almacenes a ON c.id_alma = a.id_alma
LEFT JOIN transportes t ON c.id_transp = t.id_transp
LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
LEFT JOIN captacion_flete cf ON c.id_captacion = cf.id_captacion
LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
WHERE 1=1";

if ($zona_seleccionada > 0) {
    $query .= " AND c.zona = " . $zona_seleccionada;
}

// Filtro de estado (activo/inactivo)
if (isset($_POST['mostrarInactivos'])) {
    if ($_POST['mostrarInactivos'] == 'true') {
        $query .= " AND c.status = '0'";
    } else {
        $query .= " AND c.status = '1'";
    }
} else {
    // Por defecto, mostrar solo activos
    $query .= " AND c.status = '1'";
}

// Filtro por fechas
if (isset($_POST['fechaInicio']) && !empty($_POST['fechaInicio'])) {
    $fechaInicio = $conn_mysql->real_escape_string($_POST['fechaInicio']);
    $query .= " AND c.fecha_captacion >= '$fechaInicio'";
}

if (isset($_POST['fechaFin']) && !empty($_POST['fechaFin'])) {
    $fechaFin = $conn_mysql->real_escape_string($_POST['fechaFin']);
    $query .= " AND c.fecha_captacion <= '$fechaFin'";
}

// Búsqueda global
if (!empty($search)) {
    $search = $conn_mysql->real_escape_string($search);
    $query .= " AND (
        c.folio LIKE '%$search%' 
        OR c.fecha_captacion LIKE '%$search%'
        OR z.cod LIKE '%$search%'
        OR z.PLANTA LIKE '%$search%'
        OR p.cod LIKE '%$search%'
        OR p.rs LIKE '%$search%'
        OR a.cod LIKE '%$search%'
        OR a.nombre LIKE '%$search%'
        OR t.placas LIKE '%$search%'
        OR t.razon_so LIKE '%$search%'
        -- Búsqueda por facturas de productos
        OR EXISTS (
            SELECT 1 FROM captacion_detalle cd2 
            WHERE cd2.id_captacion = c.id_captacion 
            AND cd2.status = 1 
            AND cd2.numero_factura LIKE '%$search%'
        )
        -- Búsqueda por facturas de flete
        OR EXISTS (
            SELECT 1 FROM captacion_flete cf2 
            WHERE cf2.id_captacion = c.id_captacion 
            AND cf2.numero_factura_flete LIKE '%$search%'
        )
        -- Búsqueda por contra recibos de productos
        OR EXISTS (
            SELECT 1 FROM captacion_detalle cd3 
            WHERE cd3.id_captacion = c.id_captacion 
            AND cd3.status = 1 
            AND CONCAT(cd3.aliascap, '-', cd3.foliocap) LIKE '%$search%'
        )
        -- Búsqueda por contra recibo de flete
        OR EXISTS (
            SELECT 1 FROM captacion_flete cf3 
            WHERE cf3.id_captacion = c.id_captacion 
            AND CONCAT(cf3.aliascap_flete, '-', cf3.foliocap_flete) LIKE '%$search%'
        )
    )";
}

// Agrupar
$query .= " GROUP BY c.id_captacion, c.folio, c.fecha_captacion, z.cod, z.PLANTA, z.tipo, p.cod, p.rs, 
           a.cod, a.nombre, t.placas, t.razon_so, pf.precio, cf.numero_factura_flete, cf.id_capt_flete";

// Ordenamiento
$order_by = $columns[$order_column] ?? 'c.fecha_captacion';
$query .= " ORDER BY $order_by $order_dir";

// Paginación
$query .= " LIMIT $start, $length";

// Ejecutar consulta con manejo de errores
$result = $conn_mysql->query($query);

// Validar si la consulta falló
if (!$result) {
    header('Content-Type: application/json');
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Error en la consulta SQL: " . $conn_mysql->error
    ]);
    exit;
}

$data = [];
$total_count = 0;

// Primero obtenemos el total de registros con filtros
// En la sección donde construyes la $count_query, actualízala así:
// Primero obtenemos el total de registros con filtros
$count_query = "SELECT COUNT(DISTINCT c.id_captacion) as total_count 
FROM captacion c
LEFT JOIN zonas z ON c.zona = z.id_zone
LEFT JOIN proveedores p ON c.id_prov = p.id_prov
LEFT JOIN almacenes a ON c.id_alma = a.id_alma
LEFT JOIN transportes t ON c.id_transp = t.id_transp
LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
LEFT JOIN captacion_flete cf ON c.id_captacion = cf.id_captacion
LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
WHERE 1=1";

if ($zona_seleccionada > 0) {
    $count_query .= " AND c.zona = " . $zona_seleccionada;
}

// Aplicar los mismos filtros que en la consulta principal
if (isset($_POST['mostrarInactivos'])) {
    if ($_POST['mostrarInactivos'] == 'true') {
        $count_query .= " AND c.status = '0'";
    } else {
        $count_query .= " AND c.status = '1'";
    }
} else {
    $count_query .= " AND c.status = '1'";
}

if (isset($_POST['fechaInicio']) && !empty($_POST['fechaInicio'])) {
    $fechaInicio = $conn_mysql->real_escape_string($_POST['fechaInicio']);
    $count_query .= " AND c.fecha_captacion >= '$fechaInicio'";
}

if (isset($_POST['fechaFin']) && !empty($_POST['fechaFin'])) {
    $fechaFin = $conn_mysql->real_escape_string($_POST['fechaFin']);
    $count_query .= " AND c.fecha_captacion <= '$fechaFin'";
}

if (!empty($search)) {
    $search = $conn_mysql->real_escape_string($search);
    $count_query .= " AND (
        c.folio LIKE '%$search%' 
        OR c.fecha_captacion LIKE '%$search%'
        OR z.cod LIKE '%$search%'
        OR z.PLANTA LIKE '%$search%'
        OR p.cod LIKE '%$search%'
        OR p.rs LIKE '%$search%'
        OR a.cod LIKE '%$search%'
        OR a.nombre LIKE '%$search%'
        OR t.placas LIKE '%$search%'
        OR t.razon_so LIKE '%$search%'
        OR EXISTS (
            SELECT 1 FROM captacion_detalle cd2 
            WHERE cd2.id_captacion = c.id_captacion 
            AND cd2.status = 1 
            AND cd2.numero_factura LIKE '%$search%'
        )
        OR EXISTS (
            SELECT 1 FROM captacion_flete cf2 
            WHERE cf2.id_captacion = c.id_captacion 
            AND cf2.numero_factura_flete LIKE '%$search%'
        )
        OR EXISTS (
            SELECT 1 FROM captacion_detalle cd3 
            WHERE cd3.id_captacion = c.id_captacion 
            AND cd3.status = 1 
            AND CONCAT(cd3.aliascap, '-', cd3.foliocap) LIKE '%$search%'
        )
        OR EXISTS (
            SELECT 1 FROM captacion_flete cf3 
            WHERE cf3.id_captacion = c.id_captacion 
            AND CONCAT(cf3.aliascap_flete, '-', cf3.foliocap_flete) LIKE '%$search%'
        )
    )";
}

// NO agregar GROUP BY a la consulta de conteo
// $count_query .= " GROUP BY ..." // ← ESTO ESTÁ MAL, ELIMÍNALO

$count_result = $conn_mysql->query($count_query);
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = intval($count_row['total_count'] ?? 0);
}
$count_query = preg_replace('/ORDER BY.*$/i', '', $count_query);
$count_query = preg_replace('/LIMIT.*$/i', '', $count_query);

$count_result = $conn_mysql->query($count_query);
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = intval($count_row['total_count'] ?? 0);
}

while ($row = mysqli_fetch_assoc($result)) {
    $esZonaSur = strtoupper(trim($row['tipo_zona'] ?? '')) === 'SUR';
    
    // Formatear folio completo
    $folio_completo = $row['folio'];
    if (strlen($folio_completo) == 4 && is_numeric($folio_completo)) {
        $anio_mes = date('ym', strtotime($row['fecha_captacion']));
        $folio_numero = str_pad($folio_completo, 4, '0', STR_PAD_LEFT);
        $folio_completo = "C-" . $row['cod_zona'] . "-" . $anio_mes . $folio_numero;
    }
    
    // Formatear fecha
    $fecha_formateada = date('d/m/Y', strtotime($row['fecha_captacion']));
    
    // Datos numéricos
    $cantidad_productos = intval($row['cantidad_productos']);
    $total_kilos = floatval($row['total_kilos']);
    $costo_productos = floatval($row['costo_productos']);
    $costo_flete = floatval($row['costo_flete']);
    $costo_total = floatval($row['costo_total']);
    
    // Calcular promedios
    $costo_por_kilo_prod = $total_kilos > 0 ? $costo_productos / $total_kilos : 0;
    $costo_por_kilo_total = $total_kilos > 0 ? $costo_total / $total_kilos : 0;
    
    // Info fletero
    $fletero_info = '';
    if ($esZonaSur) {
        $fletero_info = '<span class="text-muted">Sin fletero</span>';
    } elseif (!empty($row['placas_fletero'])) {
        $fletero_info = htmlspecialchars($row['placas_fletero']);
        if (!empty($row['nombre_fletero'])) {
            $fletero_info .= '<br><small class="text-muted">' . htmlspecialchars($row['nombre_fletero']) . '</small>';
        }
    } else {
        $fletero_info = '<span class="text-muted">Sin fletero</span>';
    }

// Facturas de productos - Hacer clickeables si hay documentos de factura
$facturas_productos_html = '';
if ($esZonaSur) {
    $facturas_productos_html = '<span class="text-muted">-</span>';
} elseif (!empty($row['facturas_productos'])) {
    $facturas_arr = array_filter(explode(', ', $row['facturas_productos']));
    
    // Obtener documentos de factura para cada producto
    // Necesitas consultar estos datos adicionales
    $captacion_id = $row['id_captacion'] ?? 0;
    $documentos_factura = [];
    
    if ($captacion_id) {
        // Consultar documentos de factura para esta captación
        $sql_docs = "SELECT 
            cd.numero_factura,
            cd.doc_factura,
            cd.com_factura,
            p.cod AS cod_producto,
            p.nom_pro AS nombre_producto
            FROM captacion_detalle cd
            LEFT JOIN productos p ON cd.id_prod = p.id_prod
            WHERE cd.id_captacion = ? 
            AND cd.status = 1
            AND cd.numero_factura IS NOT NULL
            AND cd.numero_factura != ''";
        
        $stmt_docs = $conn_mysql->prepare($sql_docs);
        $stmt_docs->bind_param('i', $captacion_id);
        $stmt_docs->execute();
        $result_docs = $stmt_docs->get_result();
        
        while ($doc = $result_docs->fetch_assoc()) {
            $documentos_factura[$doc['numero_factura']] = [
                'doc_factura' => $doc['doc_factura'],
                'com_factura' => $doc['com_factura'],
                'producto' => $doc['nombre_producto']
            ];
        }
    }
    
    foreach ($facturas_arr as $idx => $factura) {
        $factura = trim($factura);
        
        // Verificar si hay documentos de factura para este número
        if (isset($documentos_factura[$factura])) {
            $doc_factura = $documentos_factura[$factura]['doc_factura'] ?? '';
            $com_factura = $documentos_factura[$factura]['com_factura'] ?? '';
            $producto_nombre = $documentos_factura[$factura]['producto'] ?? '';
            
            // Si hay al menos un documento de factura
            if (!empty($doc_factura) || !empty($com_factura)) {
                $facturas_productos_html .= '<div class="dropdown d-inline-block me-1">';
                $facturas_productos_html .= '<button class="btn btn-sm btn-teal dropdown-toggle rounded-4 mb-1" type="button" data-bs-toggle="dropdown">';
                $facturas_productos_html .= '<i class="bi bi-file-earmark-text"></i> ' . htmlspecialchars($factura);
                $facturas_productos_html .= '</button>';
                $facturas_productos_html .= '<ul class="dropdown-menu">';
                
                if (!empty($doc_factura)) {
                    $facturas_productos_html .= '<li>';
                    $facturas_productos_html .= '<a class="dropdown-item" href="' . $invoiceLK . $doc_factura . '.pdf" target="_blank">';
                    $facturas_productos_html .= '<i class="bi bi-file-text me-2"></i> Ver Factura';
                    $facturas_productos_html .= '</a>';
                    $facturas_productos_html .= '</li>';
                }
                
                if (!empty($com_factura)) {
                    $facturas_productos_html .= '<li>';
                    $facturas_productos_html .= '<a class="dropdown-item" href="' . $invoiceLK . $com_factura . '.pdf" target="_blank">';
                    $facturas_productos_html .= '<i class="bi bi-receipt me-2"></i> Ver Comprobante';
                    $facturas_productos_html .= '</a>';
                    $facturas_productos_html .= '</li>';
                }
                
                $facturas_productos_html .= '</ul>';
                $facturas_productos_html .= '</div>';
            } else {
                // Si no hay documentos, mostrar solo el número
                $facturas_productos_html .= '<span class="badge bg-secondary me-1">' . htmlspecialchars($factura) . '</span>';
            }
        } else {
            // Si no se encontraron documentos en la consulta
            $facturas_productos_html .= '<span class="badge bg-secondary me-1">' . htmlspecialchars($factura) . '</span>';
        }
    }
} else {
    $facturas_productos_html = '<span class="text-muted">-</span>';
}
    
    // Factura del fletero - Hacer clickeable si hay número
$factura_fletero_html = '';
if ($esZonaSur) {
    $factura_fletero_html = '<span class="text-muted">-</span>';
} elseif (!empty($row['numero_factura_flete'])) {
    $doc_flete = $row['doc_factura_flete'] ?? '';
    $com_flete = $row['com_factura_flete'] ?? '';
    
    // Si hay al menos un documento de factura de flete
    if (!empty($doc_flete) || !empty($com_flete)) {
        $factura_fletero_html .= '<div class="dropdown d-inline-block">';
        $factura_fletero_html .= '<button class="btn btn-sm btn-success rounded-4 dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        $factura_fletero_html .= '<i class="bi bi-file-earmark-text"></i> ' . htmlspecialchars($row['numero_factura_flete']);
        $factura_fletero_html .= '</button>';
        $factura_fletero_html .= '<ul class="dropdown-menu">';
        
        if (!empty($doc_flete) && $doc_flete !== 'null') {
            $factura_fletero_html .= '<li>';
            $factura_fletero_html .= '<a class="dropdown-item" href="' . $invoiceLK . $doc_flete . '.pdf" target="_blank">';
            $factura_fletero_html .= '<i class="bi bi-file-text me-2"></i> Ver Factura de Flete';
            $factura_fletero_html .= '</a>';
            $factura_fletero_html .= '</li>';
        }
        
        if (!empty($com_flete) && $com_flete !== 'null') {
            $factura_fletero_html .= '<li>';
            $factura_fletero_html .= '<a class="dropdown-item" href="' . $invoiceLK . $com_flete . '.pdf" target="_blank">';
            $factura_fletero_html .= '<i class="bi bi-receipt me-2"></i> Ver Comprobante de Flete';
            $factura_fletero_html .= '</a>';
            $factura_fletero_html .= '</li>';
        }
        
        $factura_fletero_html .= '</ul>';
        $factura_fletero_html .= '</div>';
    } else {
        // Si no hay documentos, mostrar solo el número
        $factura_fletero_html = '<span class="badge bg-secondary">' .
            '<i class="bi bi-file-earmark-text"></i> ' . htmlspecialchars($row['numero_factura_flete']) .
            '</span>';
    }
} else {
    $factura_fletero_html = '<span class="text-muted">-</span>';
}

// Contra recibos de productos
$contra_recibos_html = '';
if (!empty($row['contra_recibos_productos'])) {
    $cr_productos_arr = array_filter(explode('; ', $row['contra_recibos_productos']));
    $cr_productos_arr = array_unique($cr_productos_arr); // Eliminar duplicados
    
    foreach ($cr_productos_arr as $cr) {
        if (!empty($cr) && $cr !== 'null-null') {
            $cr_parts = explode('-', $cr);
            if (count($cr_parts) === 2 && !empty($cr_parts[0]) && !empty($cr_parts[1])) {
                $contra_recibos_html .= '<a href="'.$link . urlencode($cr) . '" ' .
                    'target="_blank" class="badge bg-primary bg-opacity-10 text-primary me-1 mb-1 d-inline-block" ' .
                    'style="text-decoration: none;" title="Ver contra recibo">' .
                    '<i class="bi bi-receipt me-1"></i>' . htmlspecialchars($cr) .
                    '</a>';
            }
        }
    }
} else {
    $contra_recibos_html = '<span class="text-muted">-</span>';
}

// Contra recibo de flete
$contra_recibo_flete_html = '';
if ($esZonaSur) {
    $contra_recibo_flete_html = '<span class="text-muted">-</span>';
} elseif (!empty($row['aliascap_flete']) && !empty($row['foliocap_flete'])) {
    $cr_flete = $row['aliascap_flete'] . '-' . $row['foliocap_flete'];
    $contra_recibo_flete_html = '<a href="'.$link . urlencode($cr_flete) . '" ' .
        'target="_blank" class="badge bg-success bg-opacity-10 text-success" ' .
        'style="text-decoration: none;" title="Ver contra recibo de flete">' .
        '<i class="bi bi-truck me-1"></i>' . htmlspecialchars($row['aliascap_flete'] . '-' . $row['foliocap_flete']) .
        '</a>';
} else {
    $contra_recibo_flete_html = '<span class="text-muted">-</span>';
}
    
    // En el array $data[], agrega las nuevas columnas al final:
    $data[] = [
        '', // Columna # (se llena en el frontend)
        '', // Columna Acciones (se genera en el frontend)
        '<div class="fw-bold text-primary mb-1">' . htmlspecialchars($folio_completo) . '</div>' .
        '<div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>' . $fecha_formateada . '</div>',
        '<div class="mb-1"><span class="badge bg-primary bg-opacity-10 text-primary me-1">' . 
        htmlspecialchars($row['cod_proveedor'] ?? 'N/A') . '</span>' . 
        '<span class="small">' . htmlspecialchars($row['nombre_proveedor'] ?? 'N/A') . '</span></div>' .
        '<div><span class="badge bg-success bg-opacity-10 text-success me-1">' . 
        htmlspecialchars($row['cod_almacen'] ?? 'N/A') . '</span>' . 
        '<span class="small">' . htmlspecialchars($row['nombre_almacen'] ?? 'N/A') . '</span></div>',
        '<div class="d-flex align-items-center">' .
        '<span class="badge bg-warning bg-opacity-25 text-dark me-2">' . 
        htmlspecialchars($row['cod_zona'] ?? 'N/A') . '</span>' .
        '<span class="fw-semibold small">' . htmlspecialchars($row['nombre_zona'] ?? 'N/A') . '</span>' .
        '</div>',
        '<div class="fw-bold"><span class="badge bg-primary rounded-pill">' . $cantidad_productos . '</span></div>' .
        '<small class="text-muted">productos</small>',
        '<div class="fw-bold text-success">' . number_format($total_kilos, 2) . ' kg</div>' .
        '<small class="text-muted">peso total</small>',
        '<div class="fw-bold text-indigo">$' . number_format($costo_productos, 2) . '</div>' .
        '<small class="text-muted">$' . number_format($costo_por_kilo_prod, 4) . '/kg</small>',
        (!$esZonaSur && $costo_flete > 0 ? 
            '<div class="fw-bold text-warning">$' . number_format($costo_flete, 2) . '</div>' .
            '<small class="text-muted">flete</small>' : 
            '<div class="text-muted small">Sin flete</div>'),
        '<div class="fw-bold text-success">$' . number_format($costo_total, 2) . '</div>' .
        '<small class="text-muted">$' . number_format($costo_por_kilo_total, 4) . '/kg</small>',
        $fletero_info,
        '<div class="small">' . $facturas_productos_html . '</div>',
        '<div class="small">' . $factura_fletero_html . '</div>',
        // NUEVAS COLUMNAS
        '<div class="small">' . $contra_recibos_html . '</div>',
        '<div class="small">' . $contra_recibo_flete_html . '</div>',
        $row['id_captacion'], // ID para las acciones
        $row['status'] // Status para filtrado
    ];
}

// Obtener el total de registros sin filtrar
$total_query = "SELECT COUNT(*) as total FROM captacion WHERE status = '1'";
$total_result = $conn_mysql->query($total_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];

// Respuesta para DataTables
$response = [
    "draw" => intval($_POST['draw'] ?? 1),
    "recordsTotal" => $total_records,
    "recordsFiltered" => $total_count,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>