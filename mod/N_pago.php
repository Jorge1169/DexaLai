<?php
requirePermiso('CAPTACION_CREAR', 'pagos');

$zona_seleccionada = intval($_SESSION['selected_zone'] ?? 0);
if ($zona_seleccionada <= 0 || !esZonaSurSinFlete($zona_seleccionada, $conn_mysql)) {
    alert("El módulo de pagos está disponible solo para zona SUR", 0, "inicio");
    exit;
}

$stmtZona = $conn_mysql->prepare("SELECT cod, PLANTA FROM zonas WHERE id_zone = ? LIMIT 1");
$stmtZona->bind_param('i', $zona_seleccionada);
$stmtZona->execute();
$zonaData = $stmtZona->get_result()->fetch_assoc();
$codZona = $zonaData['cod'] ?? 'ZN';

if (isset($_POST['guardar_pago'])) {
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');
    $id_prov = intval($_POST['id_prov'] ?? 0);
    $concepto = trim($_POST['concepto'] ?? 'Pago de tickets');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $tickets = $_POST['tickets'] ?? [];

    $tickets = array_values(array_unique(array_filter(array_map('intval', (array)$tickets), function($id) {
        return $id > 0;
    })));

    if ($id_prov <= 0) {
        alert("Debe seleccionar un proveedor válido", 2, "N_pago");
        exit;
    }

    if (empty($tickets)) {
        alert("Debe seleccionar al menos un ticket para generar el pago", 2, "N_pago");
        exit;
    }

    $anio = date('Y', strtotime($fecha_pago));
    $mes = date('m', strtotime($fecha_pago));

    try {
        $conn_mysql->begin_transaction();

        $stmtFolio = $conn_mysql->prepare("SELECT IFNULL(MAX(folio), 0) as max_folio FROM pagos WHERE zona = ? AND YEAR(fecha_pago) = ? AND MONTH(fecha_pago) = ?");
        $stmtFolio->bind_param('iii', $zona_seleccionada, $anio, $mes);
        $stmtFolio->execute();
        $folioData = $stmtFolio->get_result()->fetch_assoc();
        $folio = intval($folioData['max_folio'] ?? 0) + 1;

        $subtotal = 0;
        $impuesto_traslado = 0;
        $impuesto_retenido = 0;
        $total = 0;

        $stmtPago = $conn_mysql->prepare("INSERT INTO pagos 
            (folio, fecha_pago, zona, id_prov, concepto, observaciones, subtotal, impuesto_traslado, impuesto_retenido, total, id_user, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmtPago->bind_param(
            'isiissddddi',
            $folio,
            $fecha_pago,
            $zona_seleccionada,
            $id_prov,
            $concepto,
            $observaciones,
            $subtotal,
            $impuesto_traslado,
            $impuesto_retenido,
            $total,
            $idUser
        );

        if (!$stmtPago->execute()) {
            throw new Exception("No se pudo crear el pago: " . $stmtPago->error);
        }

        $id_pago = intval($conn_mysql->insert_id);

        $idsCsv = implode(',', $tickets);
        $sqlDetalles = "SELECT 
                cd.id_detalle,
                cd.id_captacion,
                cd.id_prod,
                cd.numero_ticket,
                cd.total_kilos,
                COALESCE(pc.precio, 0) as precio_unitario,
                (cd.total_kilos * COALESCE(pc.precio, 0)) as importe,
                c.id_prov,
                c.zona
            FROM captacion_detalle cd
            INNER JOIN captacion c ON c.id_captacion = cd.id_captacion
            LEFT JOIN precios pc ON pc.id_precio = cd.id_pre_compra
            LEFT JOIN pagos_detalle pd ON pd.id_detalle = cd.id_detalle AND pd.status = 1
            LEFT JOIN pagos pg ON pg.id_pago = pd.id_pago AND pg.status = 1
            WHERE cd.id_detalle IN ($idsCsv)
              AND c.status = 1
              AND cd.status = 1
              AND c.id_prov = {$id_prov}
              AND c.zona = {$zona_seleccionada}
              AND cd.numero_ticket IS NOT NULL
              AND cd.numero_ticket != ''
              AND pg.id_pago IS NULL";

        $resDetalles = $conn_mysql->query($sqlDetalles);
        if (!$resDetalles) {
            throw new Exception("No se pudieron consultar tickets: " . $conn_mysql->error);
        }

        $rowsDetalles = [];
        while ($row = $resDetalles->fetch_assoc()) {
            $rowsDetalles[] = $row;
        }

        if (count($rowsDetalles) !== count($tickets)) {
            throw new Exception("Uno o más tickets seleccionados ya no están disponibles para pago. Recargue e intente de nuevo.");
        }

        $stmtDet = $conn_mysql->prepare("INSERT INTO pagos_detalle 
            (id_pago, id_detalle, id_captacion, id_prod, numero_ticket, total_kilos, precio_unitario, importe, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");

        if (!$stmtDet) {
            throw new Exception("No se pudo preparar el detalle del pago: " . $conn_mysql->error);
        }

        foreach ($rowsDetalles as $rowDet) {
            $id_detalle = intval($rowDet['id_detalle']);
            $id_captacion = intval($rowDet['id_captacion']);
            $id_prod = intval($rowDet['id_prod']);
            $numero_ticket = trim($rowDet['numero_ticket'] ?? '');
            $total_kilos = floatval($rowDet['total_kilos'] ?? 0);
            $precio_unitario = floatval($rowDet['precio_unitario'] ?? 0);
            $importe = floatval($rowDet['importe'] ?? 0);

            $stmtDet->bind_param(
                'iiiisddd',
                $id_pago,
                $id_detalle,
                $id_captacion,
                $id_prod,
                $numero_ticket,
                $total_kilos,
                $precio_unitario,
                $importe
            );

            if (!$stmtDet->execute()) {
                throw new Exception("Error al insertar detalle de pago: " . $stmtDet->error);
            }

            $subtotal += $importe;
        }

        $total = $subtotal + $impuesto_traslado - $impuesto_retenido;

        $stmtUpd = $conn_mysql->prepare("UPDATE pagos SET subtotal = ?, impuesto_traslado = ?, impuesto_retenido = ?, total = ?, updated_at = NOW() WHERE id_pago = ?");
        $stmtUpd->bind_param('ddddi', $subtotal, $impuesto_traslado, $impuesto_retenido, $total, $id_pago);
        if (!$stmtUpd->execute()) {
            throw new Exception("No se pudo actualizar total del pago: " . $stmtUpd->error);
        }

        $conn_mysql->commit();

        $folioCompuesto = 'P-' . $codZona . '-' . date('ym', strtotime($fecha_pago)) . str_pad((string)$folio, 4, '0', STR_PAD_LEFT);
        logActivity('PAGO_CREAR', 'Creó pago ' . $folioCompuesto . ' con ' . count($tickets) . ' tickets');
        alert("Pago creado exitosamente con folio: {$folioCompuesto}", 1, "V_pago&id={$id_pago}");
        exit;
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error al crear pago: " . $e->getMessage(), 2, "N_pago");
        exit;
    }
}

$proveedores = $conn_mysql->query("SELECT id_prov, cod, rs FROM proveedores WHERE status = 1 AND zona = {$zona_seleccionada} ORDER BY rs");
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Nuevo Pago (SUR)</h5>
            <button type="button" id="btnCerrarPago" class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
        </div>
        <div class="card-body">
            <form method="post" action="" id="formNuevoPago">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="date" name="fecha_pago" id="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Proveedor</label>
                        <select name="id_prov" id="id_prov" class="form-select" required>
                            <option value="">Selecciona un proveedor...</option>
                            <?php while ($prov = $proveedores->fetch_assoc()): ?>
                            <option value="<?= intval($prov['id_prov']) ?>"><?= htmlspecialchars($prov['cod'] . ' - ' . $prov['rs']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Concepto</label>
                        <input type="text" name="concepto" id="concepto" class="form-control" value="Pago de tickets" maxlength="255">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Opcional"></textarea>
                </div>

                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Tickets disponibles para pago</h6>
                            <small class="text-muted">Solo tickets no pagados</small>
                        </div>
                        <div id="contenedorTicketsPago">
                            <div class="alert alert-info mb-0">Seleccione un proveedor para cargar tickets disponibles.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary">Tickets seleccionados: <span id="totalTicketsSel">0</span></span>
                        <span class="badge bg-success">Total: $<span id="totalImporteSel">0.00</span></span>
                    </div>
                    <button type="submit" name="guardar_pago" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Guardar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#id_prov').select2({ width: '100%', language: 'es' });

    function actualizarTotalesSeleccion() {
        let count = 0;
        let total = 0;
        // Recorrer todas las filas del DataTable (incluidas paginadas)
        if (dtTickets) {
            dtTickets.rows().every(function() {
                $(this.node()).find('.chk-ticket:checked').each(function() {
                    count++;
                    total += parseFloat($(this).data('importe') || 0);
                });
            });
        } else {
            $('.chk-ticket:checked').each(function() {
                count++;
                total += parseFloat($(this).data('importe') || 0);
            });
        }
        $('#totalTicketsSel').text(count);
        $('#totalImporteSel').text(total.toFixed(2));
    }

    var dtTickets = null;

    function cargarTicketsDisponibles() {
        const idProv = $('#id_prov').val();
        if (!idProv) {
            if (dtTickets) { dtTickets.destroy(); dtTickets = null; }
            $('#contenedorTicketsPago').html('<div class="alert alert-info mb-0">Seleccione un proveedor para cargar tickets disponibles.</div>');
            actualizarTotalesSeleccion();
            return;
        }

        $.ajax({
            url: 'get_pago.php',
            type: 'POST',
            data: {
                accion: 'tickets_disponibles',
                id_prov: idProv,
                zona: <?= $zona_seleccionada ?>
            },
            beforeSend: function() {
                if (dtTickets) { dtTickets.destroy(); dtTickets = null; }
                $('#contenedorTicketsPago').html('<div class="text-muted">Cargando tickets...</div>');
            },
            success: function(html) {
                $('#contenedorTicketsPago').html(html);
                if ($('#tablaTicketsDisponibles').length) {
                    dtTickets = $('#tablaTicketsDisponibles').DataTable({
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
                        pageLength: 25,
                        order: [],
                        columnDefs: [
                            { orderable: false, targets: 0 }
                        ],
                        dom: '<"d-flex justify-content-between align-items-center mb-2"lf>tip'
                    });
                }
                actualizarTotalesSeleccion();
            },
            error: function() {
                $('#contenedorTicketsPago').html('<div class="alert alert-danger mb-0">No fue posible cargar tickets.</div>');
            }
        });
    }

    $('#id_prov').on('change', cargarTicketsDisponibles);

    $(document).on('change', '.chk-ticket', actualizarTotalesSeleccion);

    $(document).on('change', '#marcarTodosTickets', function() {
        var checked = $(this).is(':checked');
        // Marcar/desmarcar todos los checkboxes incluyendo los de páginas no visibles
        if (dtTickets) {
            dtTickets.rows().every(function() {
                $(this.node()).find('.chk-ticket').prop('checked', checked);
            });
        } else {
            $('.chk-ticket').prop('checked', checked);
        }
        actualizarTotalesSeleccion();
    });

    $('#formNuevoPago').on('submit', function(e) {
        // Recopilar tickets seleccionados de TODAS las páginas del DataTable
        var ticketsSeleccionados = [];
        if (dtTickets) {
            dtTickets.rows().every(function() {
                $(this.node()).find('.chk-ticket:checked').each(function() {
                    ticketsSeleccionados.push($(this).val());
                });
            });
        } else {
            $('.chk-ticket:checked').each(function() {
                ticketsSeleccionados.push($(this).val());
            });
        }

        if (ticketsSeleccionados.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un ticket para generar el pago.');
            return false;
        }

        // Eliminar hidden inputs anteriores y agregar los de todas las páginas
        $(this).find('input.hidden-ticket').remove();
        ticketsSeleccionados.forEach(function(val) {
            $('<input>').attr({ type: 'hidden', name: 'tickets[]', value: val, class: 'hidden-ticket' }).appendTo('#formNuevoPago');
        });
    });

    $('#btnCerrarPago').on('click', function() {
        window.close();
    });
});
</script>
