<?php
// ajax_captaciones.php
session_start();
require_once 'config/conexiones.php';

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

// Construir consulta base
$query = "SELECT 
c.id_captacion,
c.folio,
c.fecha_captacion,
c.status,
z.cod as cod_zona,
z.PLANTA as nombre_zona,
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
COUNT(*) OVER() AS total_count
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
    )";
}

// Agrupar
$query .= " GROUP BY c.id_captacion, c.folio, c.fecha_captacion, z.cod, z.PLANTA, p.cod, p.rs, 
           a.cod, a.nombre, t.placas, t.razon_so, pf.precio";

// Ordenamiento
$order_by = $columns[$order_column] ?? 'c.fecha_captacion';
$query .= " ORDER BY $order_by $order_dir";

// Paginación
$query .= " LIMIT $start, $length";

// Ejecutar consulta
$result = $conn_mysql->query($query);
$data = [];
$total_count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_count = $row['total_count'];
    
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
    if (!empty($row['placas_fletero'])) {
        $fletero_info = htmlspecialchars($row['placas_fletero']);
        if (!empty($row['nombre_fletero'])) {
            $fletero_info .= '<br><small class="text-muted">' . htmlspecialchars($row['nombre_fletero']) . '</small>';
        }
    } else {
        $fletero_info = '<span class="text-muted">Sin fletero</span>';
    }
    
    // Preparar datos para DataTables
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
        ($costo_flete > 0 ? 
            '<div class="fw-bold text-warning">$' . number_format($costo_flete, 2) . '</div>' .
            '<small class="text-muted">flete</small>' : 
            '<div class="text-muted small">Sin flete</div>'),
        '<div class="fw-bold text-success">$' . number_format($costo_total, 2) . '</div>' .
        '<small class="text-muted">$' . number_format($costo_por_kilo_total, 4) . '/kg</small>',
        $fletero_info,
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