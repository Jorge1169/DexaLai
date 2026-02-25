<?php
// ventas.php
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');

$esZonaSur = false;
if (isset($zona_seleccionada) && intval($zona_seleccionada) > 0) {
    $stmt_zona_tipo = $conn_mysql->prepare("SELECT tipo FROM zonas WHERE id_zone = ? LIMIT 1");
    if ($stmt_zona_tipo) {
        $zona_id_eval = intval($zona_seleccionada);
        $stmt_zona_tipo->bind_param('i', $zona_id_eval);
        $stmt_zona_tipo->execute();
        $res_zona_tipo = $stmt_zona_tipo->get_result();
        if ($res_zona_tipo && $res_zona_tipo->num_rows > 0) {
            $zona_row_tipo = $res_zona_tipo->fetch_assoc();
            $esZonaSur = (strtoupper(trim($zona_row_tipo['tipo'] ?? '')) === 'SUR');
        }
    }
}

$moduloSingular = $esZonaSur ? 'Entrega' : 'Venta';
$moduloPlural = $esZonaSur ? 'Entregas' : 'Ventas';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-cart-check me-2"></i><?= $moduloPlural ?> Registradas
            </h5>
            <a class="btn btn-sm btn-light" href="?p=N_venta" <?= $perm['ventas_crear']; ?> target="_blank">
                <i class="bi bi-plus-circle me-1"></i> Nueva <?= $moduloSingular ?>
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-1">
                    <label class="form-label small fw-semibold mb-1">Fecha Inicio</label>
                    <input type="date" id="fechaInicio" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($fechaInicioDefault) ?>">
                    </div>

                    <div class="col-12 col-sm-6 col-lg-1">
                    <label class="form-label small fw-semibold mb-1">Fecha Fin</label>
                    <input type="date" id="fechaFin" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($fechaFinDefault) ?>">
                    </div>
                    <div class="col-12 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <select id="filtroCliente" class="form-select">
                        <option value="">Todos los clientes</option>
                        <?php
                        $clientes_query = $conn_mysql->query("SELECT id_cli, cod, nombre FROM clientes WHERE status = 1 AND zona = '$zona_seleccionada' ORDER BY nombre");
                        while ($cliente = $clientes_query->fetch_assoc()) {
                            echo '<option value="' . $cliente['id_cli'] . '">' . htmlspecialchars($cliente['cod'] . ' - ' . $cliente['nombre']) . '</option>';
                        }
                        ?>
                    </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline-secondary btn-sm" title="Restablecer filtros">
                            <i class="bi bi-arrow-clockwise"></i> Restablecer
                        </button>
                    </div>

                    <div class="col-12 col-md-auto ms-lg-auto d-grid d-md-block">
                    <button type="button" id="toggleInactiveBtn" class="btn btn-info btn-sm" <?= $perm['INACTIVO']; ?>>
                        <i class="bi bi-eye"></i> Inactivas
                    </button>
                    </div>
                </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="card shadow-sm">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="tablaVentas" class="table table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Acciones</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Almacén</th>
                            <th class="text-end">Pacas</th>
                            <th class="text-end">Kilos</th>
                            <th class="text-end">Venta</th>
                            <th class="text-end">Flete</th>
                            <?php if ($esZonaSur): ?>
                            <th class="text-end">Servicios</th>
                            <?php endif; ?>
                            <th class="text-end">Total</th>
                            <th class="text-end">Factura Flete</th>
                            <th class="text-end">Contra recibo</th>
                            <th>Zona</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán mediante AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para cambiar status - VERSIÓN CON MOTIVO -->
<div class="modal fade" id="confirmVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="modalVentaHeader" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalVentaMessage">¿Estás seguro de que deseas desactivar esta venta?</p>
                
                <!-- Campo para motivo de cancelación (igual que captaciones) -->
                <div id="motivoContainer" style="display: none;">
                    <div class="form-group mt-3">
                        <label for="motivoCancelacion" class="form-label">
                            <strong>Motivo de cancelación:</strong>
                            <span class="text-danger">*</span>
                        </label>
                        <textarea id="motivoCancelacion" class="form-control" rows="3" 
                                placeholder="Explique por qué se cancela esta venta (requerido)"></textarea>
                        <small class="text-muted">Este motivo quedará registrado en el historial de movimientos.</small>
                    </div>
                </div>
                
                <input type="hidden" id="ventaId">
                <input type="hidden" id="ventaAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmVentaBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>


<!-- Incluir DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Variables globales
    let showingInactives = false;
    let table;
    const esZonaSur = <?= $esZonaSur ? 'true' : 'false' ?>;
    const idIndex = esZonaSur ? 16 : 15;
    const statusIndex = esZonaSur ? 17 : 16;
    const totalColIndex = esZonaSur ? 11 : 10;
    const totalTdPosition = totalColIndex + 1;
    const columnasNumericas = esZonaSur ? [6, 7, 8, 9, 10, 11] : [6, 7, 8, 9, 10];
    const columnasSecundarias = esZonaSur ? [14, 15] : [13, 14];
    const moduloSingular = <?= json_encode($moduloSingular) ?>;

    // Inicializar DataTable con AJAX
    function initDataTable() {
        table = $('#tablaVentas').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "ajax_ventas.php",
                "type": "POST",
                "data": function(d) {
                    // Pasar parámetros adicionales al servidor
                    d.mostrarInactivos = showingInactives;
                    d.fechaInicio = $('#fechaInicio').val();
                    d.fechaFin = $('#fechaFin').val();
                    d.clienteId = $('#filtroCliente').val();
                    d.zona = <?= (int) $zona_seleccionada ?>;
                },
                "timeout": 30000,
                "error": function(xhr, error, thrown) {
                    console.error("Error en AJAX:", xhr.responseText);
                    alert("Error al cargar los datos. Por favor, revisa la consola para más detalles.");
                }
            },
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
                "search": "Búsqueda rápida:",
                "searchPlaceholder": "Buscar en todos los campos...",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "",
                "processing": "Procesando...",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "responsive": true,
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            "order": [[3, 'desc']], // Ordenar por fecha descendente
            "columnDefs": [
                { 
                    "targets": 0, 
                    "data": null,
                    "render": function(data, type, row, meta) {
                        // Numeración de filas
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    "orderable": false,
                    "responsivePriority": 1
                },
                { 
                    "targets": 1, 
                    "data": null,
                    "render": function(data, type, row) {
                        // Columna de acciones
                        const id = row[idIndex]; // ID en la posición dinámica
                        const status = row[statusIndex]; // Status (1 = activo, 0 = inactivo)
                        
                        console.log("ID:", id, "Status:", status, "Tipo:", typeof status); // DEBUG
                        
                        let buttons = '';
                        if (status === 1) {
                            buttons = `
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?p=V_venta&id=${id}" class="btn btn-info" 
                                    title="Ver detalle completo" data-bs-toggle="tooltip" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <a href="?p=E_venta&id=${id}" 
                                    class="btn btn-warning" title="Editar ${moduloSingular.toLowerCase()}" data-bs-toggle="tooltip" <?= $perm['ventas_editar']; ?> target="_blank">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-danger desactivar-venta-btn" 
                                        data-id="${id}" title="Desactivar ${moduloSingular.toLowerCase()}" data-bs-toggle="tooltip" <?= $perm['ACT_DES']; ?>>
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    
                                </div>
                            `;
                        } else {
                            buttons = `
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?p=V_venta&id=${id}" class="btn btn-info" 
                                    title="Ver detalle completo" data-bs-toggle="tooltip" target="_blank" <?= $perm['ACT_DES']; ?>>
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-success activar-venta-btn" 
                                        data-id="${id}" title="Activar ${moduloSingular.toLowerCase()}" data-bs-toggle="tooltip">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </div>
                            `;
                        }
                        return buttons;
                    },
                    "orderable": false,
                    "responsivePriority": 1
                },
                {
                    "targets": columnasNumericas, // Columnas numéricas (pacas,kilos,venta,flete,servicio,total)
                    "render": function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            // Para ordenar, extraer solo el número
                            if (typeof data === 'string' && data.includes('$')) {
                                return parseFloat(data.replace(/[^0-9.]/g, ''));
                            } else if (typeof data === 'string' && data.includes('kg')) {
                                return parseFloat(data.replace(/[^0-9.]/g, ''));
                            } else {
                                // Para pacas
                                return parseInt(data) || 0;
                            }
                        }
                        return data;
                    },
                    "className": "text-end"
                },
                {
                    "targets": columnasSecundarias, // Zona y Usuario
                    "responsivePriority": 2
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                const status = data[statusIndex]; // Status como número (posición dinámica)

                if (status === 0) {
                    $(row).addClass('table-secondary text-muted');
                    // Agregar badge de "Inactiva"
                    $(row).find('td:eq(2)').append('<br><span class="badge bg-danger badge-venta">Inactiva</span>');
                } else {
                    // Resaltar totales altos solo en activas
                    const totalText = data[totalColIndex]; // columna Total según zona
                    if (typeof totalText === 'string') {
                        const totalMatch = totalText.match(/\$([\d,]+\.\d{2})/);
                        if (totalMatch) {
                            const total = parseFloat(totalMatch[1].replace(/,/g, ''));
                            if (total > 50000) {
                                $(row).find(`td:nth-child(${totalTdPosition})`).addClass('fw-bold text-success');
                            } else if (total < 0) {
                                $(row).find(`td:nth-child(${totalTdPosition})`).addClass('fw-bold text-danger');
                            }
                        }
                    }
                }
                
                // Inicializar Bootstrap Dropdowns en esta fila
                const dropdownElements = $(row).find('[data-bs-toggle="dropdown"]');
                dropdownElements.each(function() {
                    new bootstrap.Dropdown(this);
                });
            }
        });
    }

    // Inicializar la tabla
    initDataTable();

    // Función para recargar la tabla con los filtros actuales
    function reloadTable() {
        if (table) {
            table.ajax.reload(null, false);
        }
    }

    // Alternar entre activas/inactivas
    $('#toggleInactiveBtn').click(function() {
        showingInactives = !showingInactives;

        if (showingInactives) {
            $(this).html('<i class="bi bi-eye-slash"></i> Ver Activas');
            $(this).removeClass('btn-info').addClass('btn-warning');
            $(this).attr('title', 'Ver ventas activas');
        } else {
            $(this).html('<i class="bi bi-eye"></i> Ver Inactivas');
            $(this).removeClass('btn-warning').addClass('btn-info');
            $(this).attr('title', 'Ver ventas inactivas');
        }

        reloadTable();
    });

    // Filtrar por rango de fechas y cliente
    $('#filterBtn').click(function() {
        reloadTable();
    });

    // Resetear filtros
    $('#resetBtn').click(function() {
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        const lastDay = new Date();

        $('#fechaInicio').val(firstDay.toISOString().split('T')[0]);
        $('#fechaFin').val(lastDay.toISOString().split('T')[0]);
        $('#filtroCliente').val('');
        showingInactives = false;
        $('#toggleInactiveBtn').html('<i class="bi bi-eye"></i> Ver Inactivas')
            .removeClass('btn-warning').addClass('btn-info')
            .attr('title', 'Ver ventas inactivas');
        reloadTable();
    });

    // Configurar modal para desactivar/activar ventas
    $(document).on('click', '.desactivar-venta-btn', function() {
        const id = $(this).data('id');
        $('#ventaId').val(id);
        $('#ventaAccion').val('desactivar');
        
        // Mostrar campo de motivo
        $('#motivoContainer').show();
        $('#motivoCancelacion').val('').prop('required', true);
        
        $('#modalVentaMessage').text(`¿Estás seguro de que deseas cancelar esta ${moduloSingular.toLowerCase()}?`);
        $('#confirmVentaModal').modal('show');
        $('#modalVentaHeader').addClass('text-bg-danger');
        $('#confirmVentaBtn').addClass('btn-danger').removeClass('btn-success')
                    .html(`<i class="bi bi-x-circle me-1"></i>Cancelar ${moduloSingular}`);
    });

    $(document).on('click', '.activar-venta-btn', function() {
        const id = $(this).data('id');
        $('#ventaId').val(id);
        $('#ventaAccion').val('activar');
        
        // Ocultar campo de motivo
        $('#motivoContainer').hide();
        $('#motivoCancelacion').val('').prop('required', false);
        
        $('#modalVentaMessage').text(`¿Estás seguro de que deseas reactivar esta ${moduloSingular.toLowerCase()}?`);
        $('#confirmVentaModal').modal('show');
        $('#modalVentaHeader').addClass('text-bg-success');
        $('#confirmVentaBtn').addClass('btn-success').removeClass('btn-danger')
                    .html(`<i class="bi bi-check-circle me-1"></i>Reactivar ${moduloSingular}`);
    });

    // Confirmar acción para ventas
$('#confirmVentaBtn').click(function() {
    const id = $('#ventaId').val();
    const accion = $('#ventaAccion').val();
    const motivo = $('#motivoCancelacion').val();
    
    // Validar motivo si es desactivar
    if (accion === 'desactivar' && !motivo.trim()) {
        showToast('Debe proporcionar un motivo para cancelar la venta', 'error');
        return;
    }
    
    // Deshabilitar botón mientras se procesa
    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Procesando...');
    
    $.ajax({
        url: 'actualizar_status_venta.php',
        type: 'POST',
        data: {
            id: id,
            accion: accion,
            motivo: motivo
        },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta recibida:', response);
            
            if (response.success) {
                // Mostrar mensaje de éxito
                showToast(response.message, 'success');
                
                // Cerrar modal
                $('#confirmVentaModal').modal('hide');
                
                // Recargar la tabla después de 1 segundo
                setTimeout(() => {
                    reloadTable();
                }, 1000);
            } else {
                // Mostrar error
                showToast(response.message || 'Error desconocido', 'error');
                $btn.prop('disabled', false).html(
                    accion === 'activar' ? 
                    `<i class="bi bi-check-circle me-1"></i>Reactivar ${moduloSingular}` : 
                    `<i class="bi bi-x-circle me-1"></i>Cancelar ${moduloSingular}`
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'Error al procesar la solicitud';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch (e) {
                errorMsg = xhr.responseText.substring(0, 100);
            }
            
            showToast(errorMsg, 'error');
            $btn.prop('disabled', false).html(
                accion === 'activar' ? 
                `<i class="bi bi-check-circle me-1"></i>Reactivar ${moduloSingular}` : 
                `<i class="bi bi-x-circle me-1"></i>Cancelar ${moduloSingular}`
            );
        }
    });
});

// Restablecer modal al cerrarse
$('#confirmVentaModal').on('hidden.bs.modal', function() {
    $('#motivoCancelacion').val('').prop('required', false);
    $('#motivoContainer').hide();
    $('#modalVentaHeader').removeClass('text-bg-danger text-bg-success');
    $('#confirmVentaBtn')
        .removeClass('btn-danger btn-success')
        .prop('disabled', false)
        .html('Confirmar');
});

    // Función para mostrar notificaciones toast
    function showToast(message, type) {
        const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = $(`
            <div class="toast align-items-center text-white ${toastClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);
        
        $('#toastContainer').append(toast);
        const bsToast = new bootstrap.Toast(toast[0]);
        bsToast.show();
        
        // Remover el toast después de que se cierre
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Crear contenedor para toasts
    if (!$('#toastContainer').length) {
        $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999"></div>');
    }

    // Inicializar tooltips y dropdowns cada vez que DataTables se redibuja
    $(document).on('draw.dt', function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Reinicializar dropdowns
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(element) {
            new bootstrap.Dropdown(element);
        });
    });
});

</script>