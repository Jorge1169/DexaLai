<?php
session_start();
require_once 'config/conexiones.php';

// Verificar que no haya salida antes de este punto
if (ob_get_length()) ob_clean();

// 1. Obtener todos los parámetros
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';
$minDate = $_POST['minDate'] ?? '';
$maxDate = $_POST['maxDate'] ?? '';
$zona_seleccionada = $_POST['zona'] ?? $_SESSION['zona'] ?? '0';
$statusFilter = $_POST['status'] ?? '';
$docsFilter = $_POST['docsFilter'] ?? 'all-docs';
$contraFilter = $_POST['contraFilter'] ?? 'all-contra';

// 2. Mapear columnas
$columns = [
    0 => 'v.id_venta',
    2 => 'v.fact',
    3 => 'v.nombre',
    4 => 'c.cod',
    5 => 'co.fact',
    6 => 'p.cod',
    7 => 't.placas',
    8 => 'v.costo_flete',
    9 => 'v.peso_cliente',
    10 => 'v.precio',
    11 => 'z.nom',
    12 => 'v.factura',
    15 => 'v.fecha',
    16 => 'v.status'
];

$orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] . ' ' . $orderDir : 'v.fecha desc';

// 3. Construir consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS v.*,
co.id_compra AS id_compraV,
co.factura AS factura_compra,
co.d_prov AS documento_compra,
co.aliasInv AS Alias_Contra_com,
co.folio_contra AS folio_contra_com,
v.d_prov AS documento_venta,
v.d_fletero AS documento_flete,
v.fact_fle AS factura_fletero,
v.factura AS factura_venta,
v.aliasInv AS Alias_Contra,
v.folio_contra AS folio_contra,
v.acciones AS autorizar, 
t.placas AS id_transportista,
c.cod AS cod_cliente,
c.rs AS razon_social,
d.cod_al AS cod_direccion,
co.fact AS fact_compra,
co.nombre AS nombre_compra,
p.cod AS cod_producto,
p.nom_pro AS nombre_producto,
u.nombre AS nombre_usuario,
z.nom AS nom_zone,
z.PLANTA AS nom_planta
FROM ventas v
LEFT JOIN clientes c ON v.id_cli = c.id_cli
LEFT JOIN direcciones d ON v.id_direc = d.id_direc
LEFT JOIN compras co ON v.id_compra = co.id_compra
LEFT JOIN transportes t ON co.id_transp = t.id_transp
LEFT JOIN productos p ON v.id_prod = p.id_prod
LEFT JOIN usuarios u ON v.id_user = u.id_user
LEFT JOIN zonas z ON v.zona = z.id_zone
WHERE 1=1";

// 4. Aplicar filtros
if ($statusFilter !== '') {
    $query .= " AND v.status = '" . $conn_mysql->real_escape_string($statusFilter) . "'";
} else {
    $query .= " AND v.status = '1'";
}

// Filtro de zona
if ($zona_seleccionada != '0') {
    $query .= " AND v.zona = '" . $conn_mysql->real_escape_string($zona_seleccionada) . "'";
}

// Filtro de fechas
if (!empty($minDate)) {
    $query .= " AND v.fecha >= '" . $conn_mysql->real_escape_string($minDate) . "'";
}

if (!empty($maxDate)) {
    $query .= " AND v.fecha <= '" . $conn_mysql->real_escape_string($maxDate) . " 23:59:59'";
}

// Filtro de búsqueda
if (!empty($search)) {
    $safeSearch = $conn_mysql->real_escape_string($search);
    $query .= " AND (v.fact LIKE '%$safeSearch%' 
    OR v.nombre LIKE '%$safeSearch%' 
    OR c.cod LIKE '%$safeSearch%'
    OR co.fact LIKE '%$safeSearch%')";
}

// Filtro de documentos
if ($docsFilter !== 'all-docs') {
    if ($docsFilter === 'with-docs') {
        $query .= " AND (v.fact_fle IS NOT NULL OR co.factura IS NOT NULL)";
    } elseif ($docsFilter === 'without-docs') {
        $query .= " AND (v.fact_fle IS NULL AND co.factura IS NULL)";
    }
}

// Filtro de contrarecibos
if ($contraFilter !== 'all-contra') {
    if ($contraFilter === 'with-contra') {
        $query .= " AND (v.folio_contra IS NOT NULL AND v.folio_contra != '' OR co.folio_contra IS NOT NULL AND co.folio_contra != '')";
    } elseif ($contraFilter === 'without-contra') {
        $query .= " AND (v.folio_contra IS NULL OR v.folio_contra = '') AND (co.folio_contra IS NULL OR co.folio_contra = '')";
    }
}

// 5. Ordenamiento y límite
$query .= " ORDER BY $orderBy LIMIT $start, $length";

// 6. Ejecutar consulta
$result = $conn_mysql->query($query);
$data = [];


        function urlExists($url) {
    $headers = @get_headers($url);
    return ($headers && strpos($headers[0], '200') !== false);
}
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {


// Obtener el mes actual en español
        $meses = [
            'January' => 'ENERO', 'February' => 'FEBRERO', 'March' => 'MARZO',
            'April' => 'ABRIL', 'May' => 'MAYO', 'June' => 'JUNIO',
            'July' => 'JULIO', 'August' => 'AGOSTO', 'September' => 'SEPTIEMBRE',
            'October' => 'OCTUBRE', 'November' => 'NOVIEMBRE', 'December' => 'DICIEMBRE'
        ];  
        $mesActual = $meses[date('F', strtotime($row['fecha']))];
        $anioActual = date('Y', strtotime($row['fecha']));
        // Generar URL dinámica solo si existe factura

if (!empty($row['factura_venta'])) {
    $url = 'https://glama.esasacloud.com/doctos/'.$row['nom_planta'].'/FACTURAS/'.$anioActual.'/'. $mesActual.'/SIGN_'.$row['factura_venta'].'.pdf';
    
    if (urlExists($url)) {
        $row['url'] = $url;
        $row['pdf_exists'] = true;
    } else {
        $row['url'] = '';
        $row['pdf_exists'] = false;
    }
} else {
    $row['url'] = '';
    $row['pdf_exists'] = false;
}
        
        $data[] = $row;
    }
}

// 7. Obtener total de registros
$totalResult = $conn_mysql->query("SELECT FOUND_ROWS() as total");
$totalData = mysqli_fetch_assoc($totalResult);
$total = $totalData ? $totalData['total'] : 0;

// 8. Devolver respuesta JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => intval($_POST['draw'] ?? 1),
    'recordsTotal' => $total,
    'recordsFiltered' => $total,
    'data' => $data
]);

exit();
?>