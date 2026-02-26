<?php
$zonaActual = intval($_SESSION['selected_zone'] ?? 0);
$esZonaSurVista = false;

if ($zonaActual > 0) {
    $stmtZonaTipo = $conn_mysql->prepare("SELECT tipo FROM zonas WHERE id_zone = ? LIMIT 1");
    if ($stmtZonaTipo) {
        $stmtZonaTipo->bind_param('i', $zonaActual);
        $stmtZonaTipo->execute();
        $resZonaTipo = $stmtZonaTipo->get_result();
        if ($resZonaTipo && $resZonaTipo->num_rows > 0) {
            $zonaTipoRow = $resZonaTipo->fetch_assoc();
            $esZonaSurVista = (strtoupper(trim($zonaTipoRow['tipo'] ?? '')) === 'SUR');
        }
    }
}

if (!$esZonaSurVista) {
    alert("El módulo de pagos está disponible solo para zona SUR", 0, "inicio");
    exit;
}
?>

<div class="container-fluid py-3">
    <div class="card shadow-sm mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Pagos Registrados</h5>
            <a class="btn btn-sm btn-light" href="?p=N_pago" target="_blank" <?= $perm['captacion_crear']; ?>>
                <i class="bi bi-plus-circle me-1"></i> Nuevo Pago
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Fecha Inicio</label>
                    <input type="date" id="fechaInicioPagos" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Fecha Fin</label>
                    <input type="date" id="fechaFinPagos" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="button" id="btnFiltrarPagos" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                    <button type="button" id="btnResetPagos" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Restablecer
                    </button>
                </div>
                <div class="col-12 col-md-auto ms-lg-auto d-grid d-md-block">
                    <button type="button" id="toggleInactivePagos" class="btn btn-info btn-sm" <?= $perm['INACTIVO']; ?>>
                        <i class="bi bi-eye"></i> Ver Inactivos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablaPagos" class="table table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th>Folio / Fecha</th>
                            <th>Proveedor</th>
                            <th class="text-end">Tickets</th>
                            <th class="text-end">Total</th>
                            <th>Factura</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPagoStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalPagoStatusHeader">
                <h5 class="modal-title" id="modalPagoStatusTitle">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalPagoStatusMsg" class="mb-0"></p>
                <div id="motivoPagoContainer" class="mt-3" style="display:none;">
                    <label for="motivoPagoCancelacion" class="form-label">
                        <strong>Motivo de cancelación:</strong>
                        <span class="text-danger">*</span>
                    </label>
                    <textarea id="motivoPagoCancelacion" class="form-control" rows="3" placeholder="Explique por qué se cancela este pago"></textarea>
                    <small class="text-muted">Esta observación reemplazará la observación actual del pago.</small>
                </div>
                <input type="hidden" id="pagoIdAccion">
                <input type="hidden" id="pagoAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="btnConfirmPagoStatus">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function() {
    let showingInactives = false;
    let tabla;
    const idIndex = 8;
    const statusIndex = 9;

    function initTabla() {
        tabla = $('#tablaPagos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax_pagos.php',
                type: 'POST',
                data: function(d) {
                    d.mostrarInactivos = showingInactives;
                    d.fechaInicio = $('#fechaInicioPagos').val();
                    d.fechaFin = $('#fechaFinPagos').val();
                    d.zona = <?= (int)$zonaActual ?>;
                }
            },
            language: {
                url: 'https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json'
            },
            order: [[2, 'desc']],
            columnDefs: [
                {
                    targets: 0,
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    orderable: false
                },
                {
                    targets: 1,
                    data: null,
                    render: function(data, type, row) {
                        const id = row[idIndex];
                        const status = String(row[statusIndex]);
                        let html = '<div class="btn-group btn-group-sm">';
                        html += `<a href="?p=V_pago&id=${id}" target="_blank" class="btn btn-info" title="Ver pago"><i class="bi bi-eye"></i></a>`;
                        if (status === '1') {
                            html += `<button class="btn btn-danger btn-cambiar-status-pago" data-id="${id}" data-accion="desactivar" title="Desactivar"><i class="bi bi-x-circle"></i></button>`;
                        } else {
                            html += `<button class="btn btn-success btn-cambiar-status-pago" data-id="${id}" data-accion="activar" title="Activar"><i class="bi bi-check-circle"></i></button>`;
                        }
                        html += '</div>';
                        return html;
                    },
                    orderable: false
                }
            ],
            createdRow: function(row, data) {
                if (String(data[statusIndex]) === '0') {
                    $(row).addClass('table-secondary text-muted');
                }
            }
        });
    }

    function reloadTabla() {
        if (tabla) {
            tabla.ajax.reload(null, false);
        }
    }

    $('#btnFiltrarPagos').on('click', reloadTabla);

    $('#btnResetPagos').on('click', function() {
        $('#fechaInicioPagos').val(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
        $('#fechaFinPagos').val(new Date().toISOString().split('T')[0]);
        showingInactives = false;
        $('#toggleInactivePagos').html('<i class="bi bi-eye"></i> Ver Inactivos').removeClass('btn-warning').addClass('btn-info');
        reloadTabla();
    });

    $('#toggleInactivePagos').on('click', function() {
        showingInactives = !showingInactives;
        if (showingInactives) {
            $(this).html('<i class="bi bi-eye-slash"></i> Ver Activos').removeClass('btn-info').addClass('btn-warning');
        } else {
            $(this).html('<i class="bi bi-eye"></i> Ver Inactivos').removeClass('btn-warning').addClass('btn-info');
        }
        reloadTabla();
    });

    $(document).on('click', '.btn-cambiar-status-pago', function() {
        const id = $(this).data('id');
        const accion = $(this).data('accion');

        $('#pagoIdAccion').val(id);
        $('#pagoAccion').val(accion);

        if (accion === 'desactivar') {
            $('#modalPagoStatusHeader').removeClass('text-bg-success').addClass('text-bg-danger');
            $('#modalPagoStatusTitle').text('Desactivar pago');
            $('#modalPagoStatusMsg').text('¿Deseas desactivar este pago?');
            $('#btnConfirmPagoStatus').removeClass('btn-success').addClass('btn-danger').text('Desactivar');
            $('#motivoPagoContainer').show();
            $('#motivoPagoCancelacion').val('').prop('required', true);
        } else {
            $('#modalPagoStatusHeader').removeClass('text-bg-danger').addClass('text-bg-success');
            $('#modalPagoStatusTitle').text('Activar pago');
            $('#modalPagoStatusMsg').text('¿Deseas activar este pago?');
            $('#btnConfirmPagoStatus').removeClass('btn-danger').addClass('btn-success').text('Activar');
            $('#motivoPagoContainer').hide();
            $('#motivoPagoCancelacion').val('').prop('required', false);
        }

        new bootstrap.Modal(document.getElementById('modalPagoStatus')).show();
    });

    $('#btnConfirmPagoStatus').on('click', function() {
        const id = $('#pagoIdAccion').val();
        const accion = $('#pagoAccion').val();
        const motivo = $('#motivoPagoCancelacion').val();
        const btn = $(this);

        if (accion === 'desactivar' && !motivo.trim()) {
            alert('Debe proporcionar un motivo para cancelar el pago');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Procesando...');

        $.ajax({
            url: 'actualizar_status_pago.php',
            type: 'POST',
            dataType: 'json',
            data: { id: id, accion: accion, motivo: motivo },
            success: function(resp) {
                if (resp.success) {
                    $('#modalPagoStatus').modal('hide');
                    reloadTabla();
                } else {
                    alert(resp.message || 'Error al cambiar el estatus del pago');
                }
            },
            error: function() {
                alert('Error de comunicación al actualizar estatus de pago');
            },
            complete: function() {
                btn.prop('disabled', false).text('Confirmar');
            }
        });
    });

    $('#modalPagoStatus').on('hidden.bs.modal', function() {
        $('#motivoPagoContainer').hide();
        $('#motivoPagoCancelacion').val('').prop('required', false);
        $('#modalPagoStatusHeader').removeClass('text-bg-danger text-bg-success');
        $('#btnConfirmPagoStatus').removeClass('btn-danger btn-success').text('Confirmar').prop('disabled', false);
    });

    initTabla();
});
</script>
