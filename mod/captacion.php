<?php
// captacion.php
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Captaciones Registradas
            </h5>
            <a class="btn btn-sm btn-light" href="?p=N_captacion" <?= $perm['captacion_crear'];?> target="_blank">
                <i class="bi bi-plus-circle me-1"></i> Nueva Captación
            </a>
        </div>
    </div>

    <!-- Filtros SIMPLIFICADOS (solo fechas) -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" id="fechaInicio" class="form-control" 
                           value="<?= htmlspecialchars($fechaInicioDefault) ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" id="fechaFin" class="form-control" 
                           value="<?= htmlspecialchars($fechaFinDefault) ?>">
                </div>
                
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="button" id="filterBtn" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <button type="button" id="toggleInactiveBtn" class="btn btn-info" <?= $perm['INACTIVO'];?>>
                            <i class="bi bi-eye"></i> Inactivas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Captaciones con DataTables y AJAX -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaCaptaciones" class="table table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Acciones</th>
                            <th>Folio / Fecha</th>
                            <th>Proveedor / Almacén</th>
                            <th>Zona</th>
                            <th class="text-end">Productos</th>
                            <th class="text-end">Peso Total</th>
                            <th class="text-end">Costo Prod.</th>
                            <th class="text-end">Costo Flete</th>
                            <th class="text-end">Total</th>
                            <th>Fletero</th>
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

<!-- Modal de confirmación para cambiar status -->
<div class="modal fade" id="confirmCaptacionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="modalCaptacionHeader" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalCaptacionMessage">¿Estás seguro de que deseas desactivar esta captación?</p>
                <input type="hidden" id="captacionId">
                <input type="hidden" id="captacionAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmCaptacionBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
#tablaCaptaciones th {
    white-space: nowrap;
    font-size: 0.85rem;
}
#tablaCaptaciones td {
    vertical-align: middle;
    font-size: 0.9rem;
}
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.text-indigo {
    color: #6610f2 !important;
}
.text-warning {
    color: #ffc107 !important;
}
</style>

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

    // Inicializar DataTable con AJAX
    function initDataTable() {
        table = $('#tablaCaptaciones').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "ajax_captaciones.php",
                "type": "POST",
                "data": function(d) {
                    // Pasar parámetros adicionales al servidor
                    d.mostrarInactivos = showingInactives;
                    d.fechaInicio = $('#fechaInicio').val();
                    d.fechaFin = $('#fechaFin').val();
                },
                "timeout": 30000
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
            "order": [[2, 'desc']], // Ordenar por fecha descendente
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
                        const id = row[11]; // ID en la última posición
                        const status = row[12]; // Status (1 = activo, 0 = inactivo)
                        
                        let buttons = '';
                        if (status === '1') {
                            buttons = `
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?p=V_captacion&id=${id}" class="btn btn-info" 
                                       title="Ver detalle completo" data-bs-toggle="tooltip" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                        <a href="?p=E_captacion&id=${id}" 
                                           class="btn btn-warning" title="Editar captación" data-bs-toggle="tooltip" target="_blank" <?= $perm['captacion_editar'];?>>
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-danger desactivar-captacion-btn" 
                                            data-id="${id}" title="Desactivar captación" data-bs-toggle="tooltip" <?= $perm['ACT_DES'];?>>
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    
                                </div>
                            `;
                        } else {
                            buttons = `
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?p=V_captacion&id=${id}" class="btn btn-info" 
                                       title="Ver detalle completo" data-bs-toggle="tooltip" target="_blank" <?= $perm['ACT_DES'];?>>
                                        <i class="bi bi-eye"></i>
                                    </a>
                                        <button class="btn btn-success activar-captacion-btn" 
                                            data-id="${id}" title="Activar captación" data-bs-toggle="tooltip">
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
                    "targets": [5, 6, 7, 8, 9], // Columnas numéricas
                    "render": function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            // Para ordenar, extraer solo el número
                            if (data.includes('$')) {
                                return parseFloat(data.replace(/[^0-9.]/g, ''));
                            } else if (data.includes('kg')) {
                                return parseFloat(data.replace(/[^0-9.]/g, ''));
                            } else {
                                // Para productos
                                var match = data.match(/\d+/);
                                return match ? parseInt(match[0]) : 0;
                            }
                        }
                        return data;
                    }
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                // Aplicar estilos según el status
                const status = data[12]; // Status
                if (status === '0') {
                    $(row).addClass('table-secondary text-muted');
                }
            }
        });
    }

    // Inicializar la tabla
    initDataTable();

    // Función para recargar la tabla con los filtros actuales
    function reloadTable() {
        table.ajax.reload(null, false);
    }

    // Alternar entre activas/inactivas
    $('#toggleInactiveBtn').click(function() {
        showingInactives = !showingInactives;

        if (showingInactives) {
            $(this).html('<i class="bi bi-eye-slash"></i> Activas');
            $(this).removeClass('btn-info').addClass('btn-warning');
        } else {
            $(this).html('<i class="bi bi-eye"></i> Inactivas');
            $(this).removeClass('btn-warning').addClass('btn-info');
        }

        reloadTable();
    });

    // Filtrar por rango de fechas
    $('#filterBtn').click(function() {
        reloadTable();
    });

    // Resetear filtros
    $('#resetBtn').click(function() {
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        const lastDay = new Date();

        $('#fechaInicio').val(firstDay.toISOString().split('T')[0]);
        $('#fechaFin').val(lastDay.toISOString().split('T')[0]);
        showingInactives = false;
        $('#toggleInactiveBtn').html('<i class="bi bi-eye"></i> Inactivas')
        .removeClass('btn-warning').addClass('btn-info');
        reloadTable();
    });

    // Configurar modal para desactivar/activar captaciones
    $(document).on('click', '.desactivar-captacion-btn', function() {
        const id = $(this).data('id');
        $('#captacionId').val(id);
        $('#captacionAccion').val('desactivar');
        $('#modalCaptacionMessage').text('¿Estás seguro de que deseas desactivar esta captación?');
        $('#confirmCaptacionModal').modal('show');
        $('#modalCaptacionHeader').addClass('text-bg-danger');
        $('#confirmCaptacionBtn').addClass('btn-danger').removeClass('btn-success');
    });

    $(document).on('click', '.activar-captacion-btn', function() {
        const id = $(this).data('id');
        $('#captacionId').val(id);
        $('#captacionAccion').val('activar');
        $('#modalCaptacionMessage').text('¿Estás seguro de que deseas reactivar esta captación?');
        $('#confirmCaptacionModal').modal('show');
        $('#modalCaptacionHeader').addClass('text-bg-success');
        $('#confirmCaptacionBtn').addClass('btn-success').removeClass('btn-danger');
    });

    // Confirmar acción para captaciones
    $('#confirmCaptacionBtn').click(function() {
        const id = $('#captacionId').val();
        const accion = $('#captacionAccion').val();

        $.ajax({
            url: 'actualizar_status_captacion.php',
            type: 'POST',
            data: {
                id: id,
                accion: accion
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Recargar la tabla después de cambiar el status
                    reloadTable();
                    // Mostrar mensaje de éxito
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showToast('Error en la solicitud: ' + error, 'error');
            }
        });

        $('#confirmCaptacionModal').modal('hide');
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

    // Inicializar tooltips
    $(document).on('draw.dt', function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
});
</script>