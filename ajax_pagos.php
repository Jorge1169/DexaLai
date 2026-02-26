<?php
session_start();
require_once 'config/conexiones.php';

$zona_seleccionada = intval($_SESSION['selected_zone'] ?? 0);
if (isset($_POST['zona'])) {
    $zona_post = intval($_POST['zona']);
    if ($zona_post > 0) {
        $zona_seleccionada = $zona_post;
    }
}

if ($zona_seleccionada <= 0 || !esZonaSurSinFlete($zona_seleccionada, $conn_mysql)) {
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit;
}

$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = trim($_POST['search']['value'] ?? '');
$order_column = intval($_POST['order'][0]['column'] ?? 2);
$order_dir = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$columns = [
    2 => 'p.fecha_pago',
    3 => 'prov.rs',
    4 => 'total_tickets',
    5 => 'p.total',
    6 => 'p.factura_pago',
    7 => 'p.status'
];

$order_by = $columns[$order_column] ?? 'p.fecha_pago';

$query = "SELECT 
            p.id_pago,
            p.folio,
            p.fecha_pago,
            p.status,
            p.total,
            p.factura_pago,
            p.fecha_factura,
            z.cod as cod_zona,
            prov.cod as cod_proveedor,
            prov.rs as nombre_proveedor,
            COUNT(pd.id_pago_detalle) as total_tickets
          FROM pagos p
          INNER JOIN zonas z ON p.zona = z.id_zone
          LEFT JOIN proveedores prov ON p.id_prov = prov.id_prov
          LEFT JOIN pagos_detalle pd ON p.id_pago = pd.id_pago AND pd.status = 1
          WHERE p.zona = {$zona_seleccionada}";

if (isset($_POST['mostrarInactivos']) && $_POST['mostrarInactivos'] === 'true') {
    $query .= " AND p.status = 0";
} else {
    $query .= " AND p.status = 1";
}

if (!empty($_POST['fechaInicio'])) {
    $fechaInicio = $conn_mysql->real_escape_string($_POST['fechaInicio']);
    $query .= " AND p.fecha_pago >= '{$fechaInicio}'";
}

if (!empty($_POST['fechaFin'])) {
    $fechaFin = $conn_mysql->real_escape_string($_POST['fechaFin']);
    $query .= " AND p.fecha_pago <= '{$fechaFin}'";
}

if ($search !== '') {
    $searchEsc = $conn_mysql->real_escape_string($search);
    $query .= " AND (
        prov.rs LIKE '%{$searchEsc}%'
        OR prov.cod LIKE '%{$searchEsc}%'
        OR p.factura_pago LIKE '%{$searchEsc}%'
        OR CONCAT('P-', z.cod, '-', DATE_FORMAT(p.fecha_pago, '%y%m'), LPAD(p.folio, 4, '0')) LIKE '%{$searchEsc}%'
        OR EXISTS (
            SELECT 1
            FROM pagos_detalle pdx
            WHERE pdx.id_pago = p.id_pago
              AND pdx.status = 1
              AND pdx.numero_ticket LIKE '%{$searchEsc}%'
        )
    )";
}

$query .= " GROUP BY p.id_pago";

$countQuery = "SELECT COUNT(*) as total FROM ({$query}) as x";
$countResult = $conn_mysql->query($countQuery);
$total_count = $countResult ? intval($countResult->fetch_assoc()['total'] ?? 0) : 0;

$query .= " ORDER BY {$order_by} {$order_dir}";
if ($length !== -1) {
    $query .= " LIMIT {$start}, {$length}";
}

$result = $conn_mysql->query($query);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $folioCompuesto = 'P-' . ($row['cod_zona'] ?? 'ZN') . '-' . date('ym', strtotime($row['fecha_pago'])) . str_pad((string)($row['folio'] ?? '0'), 4, '0', STR_PAD_LEFT);
        $fechaFormateada = date('d/m/Y', strtotime($row['fecha_pago']));

        $estadoHtml = intval($row['status']) === 1
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';

        $facturaHtml = !empty($row['factura_pago'])
            ? '<span class="badge bg-primary bg-opacity-10 text-primary">' . htmlspecialchars($row['factura_pago']) . '</span>'
            : '<span class="text-muted">Pendiente</span>';

        $fila = [
            '',
            '',
            '<div class="fw-bold text-primary">' . htmlspecialchars($folioCompuesto) . '</div>' .
            '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>' . $fechaFormateada . '</small>',
            '<div><span class="badge bg-primary bg-opacity-10 text-primary me-1">' . htmlspecialchars($row['cod_proveedor'] ?? 'N/A') . '</span>' .
            '<span class="small">' . htmlspecialchars($row['nombre_proveedor'] ?? 'Sin proveedor') . '</span></div>',
            '<div class="fw-bold text-end">' . number_format((float)($row['total_tickets'] ?? 0), 0) . '</div>',
            '<div class="fw-bold text-success text-end">$' . number_format((float)($row['total'] ?? 0), 2) . '</div>',
            $facturaHtml,
            $estadoHtml,
            $row['id_pago'],
            $row['status']
        ];

        $data[] = $fila;
    }
}

$totalQuery = "SELECT COUNT(*) as total FROM pagos WHERE zona = {$zona_seleccionada}";
$totalResult = $conn_mysql->query($totalQuery);
$recordsTotal = $totalResult ? intval($totalResult->fetch_assoc()['total'] ?? 0) : 0;

header('Content-Type: application/json');
echo json_encode([
    'draw' => intval($_POST['draw'] ?? 1),
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $total_count,
    'data' => $data
]);
