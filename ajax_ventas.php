<?php
// ajax_ventas.php
session_start();
require_once 'config/conexiones.php';

// Limpiar buffer de salida
ob_clean();

// Configuración de DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 25;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 3;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Filtros adicionales
$mostrarInactivos = $_POST['mostrarInactivos'] ?? false;
$fechaInicio = $_POST['fechaInicio'] ?? '';
$fechaFin = $_POST['fechaFin'] ?? '';
$clienteId = $_POST['clienteId'] ?? '';

// Validar y limpiar variables
$mostrarInactivos = filter_var($mostrarInactivos, FILTER_VALIDATE_BOOLEAN);
$clienteId = intval($clienteId);

// Construir la consulta base SIMPLIFICADA
$query = "SELECT SQL_CALC_FOUND_ROWS 
            v.id_venta,
            CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio, 4, '0')) as folio_compuesto,
            DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_formateada,
            c.cod as cod_cliente,
            c.nombre as nombre_cliente,
            a.cod as cod_almacen,
            a.nombre as nombre_almacen,
            COALESCE(SUM(vd.pacas_cantidad), 0) as total_pacas,
            COALESCE(SUM(vd.total_kilos), 0) as total_kilos,
            COALESCE(SUM(vd.total_kilos * p.precio), 0) as total_venta,
            z.PLANTA as nombre_zona,
            u.nombre as nombre_usuario,
            v.status
          FROM ventas v
          LEFT JOIN clientes c ON v.id_cliente = c.id_cli
          LEFT JOIN almacenes a ON v.id_alma = a.id_alma
          LEFT JOIN zonas z ON v.zona = z.id_zone
          LEFT JOIN usuarios u ON v.id_user = u.id_user
          LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
          LEFT JOIN precios p ON vd.id_pre_venta = p.id_precio
          WHERE 1=1 ";

// Filtro de status
if ($mostrarInactivos) {
    // Mostrar solo las INACTIVAS
    $query .= " AND v.status = 0";
} else {
    // Mostrar solo las ACTIVAS (por defecto)
    $query .= " AND v.status = 1";
}

// Filtro por fechas
if (!empty($fechaInicio) && !empty($fechaFin)) {
    // Validar formato de fecha
    if (DateTime::createFromFormat('Y-m-d', $fechaInicio) !== false && 
        DateTime::createFromFormat('Y-m-d', $fechaFin) !== false) {
        $query .= " AND DATE(v.fecha_venta) BETWEEN '$fechaInicio' AND '$fechaFin'";
    }
}

// Filtro por cliente
if ($clienteId > 0) {
    $query .= " AND v.id_cliente = $clienteId";
}

// Filtro de búsqueda global
if (!empty($searchValue)) {
    $searchValue = $conn_mysql->real_escape_string($searchValue);
    $query .= " AND (
        CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio, 4, '0')) LIKE '%$searchValue%' OR
        c.cod LIKE '%$searchValue%' OR
        c.nombre LIKE '%$searchValue%' OR
        a.cod LIKE '%$searchValue%' OR
        a.nombre LIKE '%$searchValue%' OR
        z.PLANTA LIKE '%$searchValue%' OR
        u.nombre LIKE '%$searchValue%'
    )";
}

// Agrupar por venta
$query .= " GROUP BY v.id_venta";

// Ordenar
$columns = [
    0 => 'v.id_venta',           // Numeración
    1 => 'v.id_venta',           // Acciones
    2 => 'folio_compuesto',      // Folio
    3 => 'v.fecha_venta',        // Fecha
    4 => 'c.nombre',             // Cliente
    5 => 'a.nombre',             // Almacén
    6 => 'total_pacas',          // Pacas
    7 => 'total_kilos',          // Kilos
    8 => 'total_venta',          // Venta
    9 => 'v.id_venta',           // Flete (placeholder)
    10 => 'v.id_venta',          // Total (placeholder)
    11 => 'z.PLANTA',            // Zona
    12 => 'u.nombre',            // Usuario
    13 => 'v.status'             // Status
];

if (isset($columns[$orderColumn])) {
    $orderColumnName = $columns[$orderColumn];
    $query .= " ORDER BY $orderColumnName $orderDir";
} else {
    $query .= " ORDER BY v.fecha_venta DESC";
}

// Límites para paginación
$start = intval($start);
$length = intval($length);
$query .= " LIMIT $start, $length";

// Ejecutar consulta
$result = $conn_mysql->query($query);

if (!$result) {
    // Error en la consulta
    $error = $conn_mysql->error;
    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Error en la consulta: $error"
    ]);
    exit;
}

// Obtener total de registros
$totalRecordsResult = $conn_mysql->query("SELECT FOUND_ROWS() as total");
if ($totalRecordsResult) {
    $totalRecordsRow = $totalRecordsResult->fetch_row();
    $totalRecords = intval($totalRecordsRow[0]);
} else {
    $totalRecords = 0;
}

// Preparar datos para DataTables
$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Obtener el flete para esta venta
        $id_venta = intval($row['id_venta']);
        $total_flete = 0;
        
        // Consulta separada para flete
        $flete_query = "SELECT 
                         COALESCE(SUM(CASE 
                             WHEN pr.tipo = 'MFT' THEN pr.precio * (vd.total_kilos / 1000)
                             ELSE pr.precio 
                         END), 0) as precio_flete
                       FROM venta_flete vf
                       LEFT JOIN precios pr ON vf.id_pre_flete = pr.id_precio
                       LEFT JOIN venta_detalle vd ON vf.id_venta = vd.id_venta AND vd.status = 1
                       WHERE vf.id_venta = $id_venta";
        
        $flete_result = $conn_mysql->query($flete_query);
        if ($flete_result && $flete_row = $flete_result->fetch_assoc()) {
            $total_flete = floatval($flete_row['precio_flete']);
        }
        
        // Calcular totales
        $total_venta = floatval($row['total_venta']);
        $total_general = $total_venta - $total_flete;
        $total_kilos = floatval($row['total_kilos']);
        $total_pacas = intval($row['total_pacas']);
        
        // Formatear datos para mostrar
        $data[] = [
            '', // Columna 0: Numeración
            '', // Columna 1: Acciones
            '<strong class="text-primary">' . htmlspecialchars($row['folio_compuesto'] ?? 'N/A').'</strong>',
            htmlspecialchars($row['fecha_formateada'] ?? 'N/A'),
            '<strong class="badge bg-teal bg-opacity-25 text-teal">' . htmlspecialchars($row['cod_cliente'] ?? '') . '</strong><br>' . 
            '<small>' . htmlspecialchars($row['nombre_cliente'] ?? '') . '</small>',
            '<strong class="badge bg-primary bg-opacity-25 text-primary">' . htmlspecialchars($row['cod_almacen'] ?? '') . '</strong><br>' . 
            '<small>' . htmlspecialchars($row['nombre_almacen'] ?? '') . '</small>',
            '<span class="badge bg-primary rounded-pill">' . number_format($total_pacas, 0) . '</span>',
            number_format($total_kilos, 2) . ' kg',
            '<span class="text-success fw-bold">$' . number_format($total_venta, 2) . '</span>',
            '<span class="text-danger">$' . number_format($total_flete, 2) . '</span>',
            '<strong class="' . ($total_general >= 0 ? 'text-success' : 'text-danger') . '">$' . number_format($total_general, 2) . '</strong>',
            '<span class="badge bg-primary">' . htmlspecialchars($row['nombre_zona'] ?? '') . '</span>',
            '<small>' . htmlspecialchars($row['nombre_usuario'] ?? '') . '</small>',
            $id_venta, // ID para acciones
            intval($row['status']) // Status para estilos
        ];
    }
}

// Establecer encabezado JSON
header('Content-Type: application/json; charset=utf-8');

// Respuesta JSON para DataTables
$response = [
    "draw" => intval($draw),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $data
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>