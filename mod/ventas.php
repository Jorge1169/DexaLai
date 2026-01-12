<?php
// ventas.php
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-cart-check me-2"></i>Ventas Registradas
            </h5>
            <a class="btn btn-sm btn-light" href="?p=N_venta" target="_blank">
                <i class="bi bi-plus-circle me-1"></i> Nueva Venta
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" id="fechaInicio" class="form-control" 
                           value="<?= htmlspecialchars($fechaInicioDefault) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" id="fechaFin" class="form-control" 
                           value="<?= htmlspecialchars($fechaFinDefault) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
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
                
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="button" id="filterBtn" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <button type="button" id="toggleInactiveBtn" class="btn btn-info" <?= $perm['INACTIVO']; ?>>
                            <i class="bi bi-eye"></i> Inactivas
                        </button>
                    </div>
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
                            <th class="text-end">Total</th>
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

<!-- Modal de confirmación para cambiar status -->
<div class="modal fade" id="confirmVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="modalVentaHeader" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalVentaMessage">¿Estás seguro de que deseas desactivar esta venta?</p>
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

<!-- Estilos adicionales -->
<style>
#tablaVentas th {
    white-space: nowrap;
    font-size: 0.85rem;
}
#tablaVentas td {
    vertical-align: middle;
    font-size: 0.9rem;
}
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.text-success {
    color: #198754 !important;
}
.text-warning {
    color: #ffc107 !important;
}
.badge-venta {
    font-size: 0.7em;
    padding: 0.2em 0.5em;
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
                        const id = row[13]; // ID en la última posición
                        const status = row[14]; // Status (1 = activo, 0 = inactivo) - COMO NÚMERO
                        
                        console.log("ID:", id, "Status:", status, "Tipo:", typeof status); // DEBUG
                        
                        let buttons = '';
                        // CORREGIR: Comparar con número, no con string
                        if (status === 1) {  // <-- CAMBIAR '1' por 1
                            buttons = `
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?p=V_venta&id=${id}" class="btn btn-info" 
                                    title="Ver detalle completo" data-bs-toggle="tooltip" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <a href="?p=E_venta&id=${id}" 
                                    class="btn btn-warning" title="Editar venta" data-bs-toggle="tooltip" target="_blank">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-danger desactivar-venta-btn" 
                                        data-id="${id}" title="Desactivar venta" data-bs-toggle="tooltip" <?= $perm['ACT_DES']; ?>>
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
                                        data-id="${id}" title="Activar venta" data-bs-toggle="tooltip">
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
                    "targets": [6, 7, 8, 9, 10], // Columnas numéricas
                    "render": function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            // Para ordenar, extraer solo el número
                            if (data.includes('$')) {
                                return parseFloat(data.replace(/[^0-9.]/g, ''));
                            } else if (data.includes('kg')) {
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
                    "targets": [11, 12], // Zona y Usuario
                    "responsivePriority": 2
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                const status = data[14]; // Status como número
                
                if (status === 0) {  // <-- CAMBIAR '0' por 0
                    $(row).addClass('table-secondary text-muted');
                    // Agregar badge de "Inactiva"
                    $(row).find('td:eq(2)').append('<br><span class="badge bg-danger badge-venta">Inactiva</span>');
                } else {
                    // Resaltar totales altos solo en activas
                    const totalText = data[10];
                    const totalMatch = totalText.match(/\$([\d,]+\.\d{2})/);
                    if (totalMatch) {
                        const total = parseFloat(totalMatch[1].replace(/,/g, ''));
                        if (total > 50000) {
                            $(row).find('td:nth-child(11)').addClass('fw-bold text-success');
                        } else if (total < 0) {
                            $(row).find('td:nth-child(11)').addClass('fw-bold text-danger');
                        }
                    }
                }
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
        $('#modalVentaMessage').text('¿Estás seguro de que deseas desactivar esta venta?');
        $('#confirmVentaModal').modal('show');
        $('#modalVentaHeader').addClass('text-bg-danger');
        $('#confirmVentaBtn').addClass('btn-danger').removeClass('btn-success');
    });

    $(document).on('click', '.activar-venta-btn', function() {
        const id = $(this).data('id');
        $('#ventaId').val(id);
        $('#ventaAccion').val('activar');
        $('#modalVentaMessage').text('¿Estás seguro de que deseas reactivar esta venta?');
        $('#confirmVentaModal').modal('show');
        $('#modalVentaHeader').addClass('text-bg-success');
        $('#confirmVentaBtn').addClass('btn-success').removeClass('btn-danger');
    });

    // Confirmar acción para ventas
    $('#confirmVentaBtn').click(function() {
        const id = $('#ventaId').val();
        const accion = $('#ventaAccion').val();

        $.ajax({
            url: 'actualizar_status_venta.php',
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

        $('#confirmVentaModal').modal('hide');
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