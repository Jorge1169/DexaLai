<?php
require_once 'config/conexiones.php';

$accion = $_POST['accion'] ?? '';

if ($accion === 'tickets_disponibles') {
    $zona = intval($_POST['zona'] ?? 0);
    $idProv = intval($_POST['id_prov'] ?? 0);

    if ($zona <= 0 || $idProv <= 0 || !esZonaSurSinFlete($zona, $conn_mysql)) {
        echo '<div class="alert alert-warning mb-0">Seleccione proveedor válido en zona SUR.</div>';
        exit;
    }

    $sql = "SELECT 
                cd.id_detalle,
                c.id_captacion,
                c.folio as folio_captacion,
                c.fecha_captacion,
                p.cod as cod_producto,
                p.nom_pro as producto,
                cd.numero_ticket,
                cd.total_kilos,
                COALESCE(pc.precio, 0) as precio_unitario,
                (cd.total_kilos * COALESCE(pc.precio, 0)) as importe
            FROM captacion_detalle cd
            INNER JOIN captacion c ON c.id_captacion = cd.id_captacion
            INNER JOIN productos p ON p.id_prod = cd.id_prod
            LEFT JOIN precios pc ON pc.id_precio = cd.id_pre_compra
            LEFT JOIN pagos_detalle pd ON pd.id_detalle = cd.id_detalle AND pd.status = 1
            LEFT JOIN pagos pg ON pg.id_pago = pd.id_pago AND pg.status = 1
            WHERE c.status = 1
              AND cd.status = 1
              AND c.zona = ?
              AND c.id_prov = ?
              AND cd.numero_ticket IS NOT NULL
              AND cd.numero_ticket != ''
              AND pg.id_pago IS NULL
            ORDER BY c.fecha_captacion DESC, cd.id_detalle DESC";

    $stmt = $conn_mysql->prepare($sql);
    if (!$stmt) {
        echo '<div class="alert alert-danger mb-0">Error al preparar consulta de tickets disponibles.</div>';
        exit;
    }

    $stmt->bind_param('ii', $zona, $idProv);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        echo '<div class="alert alert-info mb-0">No hay tickets disponibles para pago con este proveedor.</div>';
        exit;
    }

    echo '<div class="table-responsive">';
    echo '<table id="tablaTicketsDisponibles" class="table table-sm table-hover align-middle mb-0" style="width:100%">';
    echo '<thead class="table-light"><tr>';
    echo '<th style="width:40px;"><input type="checkbox" id="marcarTodosTickets" class="form-check-input"></th>';
    echo '<th>Captación</th>';
    echo '<th>Producto</th>';
    echo '<th>Ticket</th>';
    echo '<th class="text-end">Kilos</th>';
    echo '<th class="text-end">Precio/kg</th>';
    echo '<th class="text-end">Importe</th>';
    echo '</tr></thead><tbody>';

    while ($row = $res->fetch_assoc()) {
        $folioCap = 'C-' . date('ym', strtotime($row['fecha_captacion'])) . str_pad((string)($row['folio_captacion'] ?? '0'), 4, '0', STR_PAD_LEFT);
        $importe = (float)($row['importe'] ?? 0);

        echo '<tr>';
        echo '<td><input type="checkbox" class="form-check-input chk-ticket" name="tickets[]" value="' . intval($row['id_detalle']) . '" data-importe="' . htmlspecialchars(number_format($importe, 2, '.', '')) . '"></td>';
        echo '<td><span class="badge bg-light text-dark">' . htmlspecialchars($folioCap) . '</span></td>';
        echo '<td><span class="badge bg-primary bg-opacity-10 text-primary me-1">' . htmlspecialchars($row['cod_producto']) . '</span>' . htmlspecialchars($row['producto']) . '</td>';
        echo '<td><span class="badge bg-info bg-opacity-10 text-info">' . htmlspecialchars($row['numero_ticket']) . '</span></td>';
        echo '<td class="text-end">' . number_format((float)$row['total_kilos'], 2) . '</td>';
        echo '<td class="text-end">$' . number_format((float)$row['precio_unitario'], 4) . '</td>';
        echo '<td class="text-end fw-bold">$' . number_format($importe, 2) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
    exit;
}

echo '<div class="alert alert-warning mb-0">Acción no válida.</div>';
