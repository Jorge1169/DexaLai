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

                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                    <button type="button" id="resetBtn" class="btn btn-outline-secondary btn-sm" title="Restablecer filtros">
                        <i class="bi bi-arrow-clockwise"></i> Restablecer
                    </button>
                </div>

                <div class="col-12 col-md-auto ms-lg-auto d-grid d-md-block">
                    <button type="button" id="toggleInactiveBtn" class="btn btn-info btn-sm" <?= $perm['INACTIVO'];?> title="Ver captaciones inactivas">
                        <i class="bi bi-eye"></i> Ver Inactivas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Captaciones con DataTables y AJAX -->
    <div class="card shadow-sm">
        <div class="card-body p-3">
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
                            <th>Facturas Productos</th>
                            <th>Factura Flete</th>
                            <!-- NUEVAS COLUMNAS -->
                            <th>CR Productos</th>
                            <th>CR Flete</th>
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

<!-- Modal para visualizar comprobantes -->
<div class="modal fade" id="modalViewComprobante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalViewComprobanteTitle">
                    <i class="bi bi-file-earmark me-2"></i>Comprobante
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalViewComprobanteBody">
                <p class="text-muted">Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnDownloadComprobante">
                    <i class="bi bi-download me-1"></i>Descargar
                </button>
                <button type="button" class="btn btn-info" id="btnRotateImage" style="display: none;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Rotar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para cambiar status - VERSIÓN CORREGIDA -->
<div class="modal fade" id="modalCaptacionModal" tabindex="-1" aria-labelledby="modalCaptacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalCaptacionHeader">
                <h5 class="modal-title" id="modalCaptacionLabel">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalCaptacionMessage">¿Estás seguro de que deseas desactivar esta captación?</p>
                
                <!-- Campo para motivo de cancelación -->
                <div id="motivoContainer" style="display: none;">
                    <div class="form-group mt-3">
                        <label for="motivoCancelacion" class="form-label">
                            <strong>Motivo de cancelación:</strong>
                            <span class="text-danger">*</span>
                        </label>
                        <textarea id="motivoCancelacion" class="form-control" rows="3" 
                                placeholder="Explique por qué se cancela esta captación (requerido)"></textarea>
                        <small class="text-muted">Este motivo quedará registrado en el historial de movimientos.</small>
                    </div>
                </div>
                
                <input type="hidden" id="captacionId">
                <input type="hidden" id="captacionAccion">
                <input type="hidden" id="usuarioId" value="<?= $idUser ?>">
                <input type="hidden" id="usuarioNombre" value="<?= $Usuario ?>">
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
.factura-link {
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.factura-link:hover {
    text-decoration: underline;
    opacity: 0.8;
    transform: scale(1.05);
}
.factura-link i {
    margin-right: 0.3rem;
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
                        const id = row[15]; // ID en la nueva posición (antes 11)
                        const status = row[16]; // Status en la nueva posición (antes 12)
                        
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
                const status = data[14]; // Status en la nueva posición (antes 12)
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
            $(this).html('<i class="bi bi-eye-slash"></i> Ver Activas');
            $(this).removeClass('btn-info').addClass('btn-warning');
            $(this).attr('title', 'Ver captaciones activas');
        } else {
            $(this).html('<i class="bi bi-eye"></i> Ver Inactivas');
            $(this).removeClass('btn-warning').addClass('btn-info');
            $(this).attr('title', 'Ver captaciones inactivas');
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
        $('#toggleInactiveBtn').html('<i class="bi bi-eye"></i> Ver Inactivas')
        .removeClass('btn-warning').addClass('btn-info')
        .attr('title', 'Ver captaciones inactivas');
        reloadTable();
    });

    // Configurar modal para desactivar/activar captaciones
    $(document).on('click', '.desactivar-captacion-btn', function() {
        const id = $(this).data('id');
        $('#captacionId').val(id);
        $('#captacionAccion').val('desactivar');
        
        // Mostrar campo de motivo
        $('#motivoContainer').show();
        $('#motivoCancelacion').val('').prop('required', true);
        
        $('#modalCaptacionMessage').text('¿Estás seguro de que deseas cancelar esta captación?');
        $('#modalCaptacionModal').modal('show');
        $('#modalCaptacionHeader').addClass('text-bg-danger');
        $('#confirmCaptacionBtn').addClass('btn-danger').removeClass('btn-success')
                                .html('<i class="bi bi-x-circle me-1"></i>Cancelar Captación');
    });

    $(document).on('click', '.activar-captacion-btn', function() {
        const id = $(this).data('id');
        $('#captacionId').val(id);
        $('#captacionAccion').val('activar');
        
        // Ocultar campo de motivo
        $('#motivoContainer').hide();
        $('#motivoCancelacion').val('').prop('required', false);
        
        $('#modalCaptacionMessage').text('¿Estás seguro de que deseas reactivar esta captación?');
        $('#modalCaptacionModal').modal('show');
        $('#modalCaptacionHeader').addClass('text-bg-success');
        $('#confirmCaptacionBtn').addClass('btn-success').removeClass('btn-danger')
                                .html('<i class="bi bi-check-circle me-1"></i>Reactivar Captación');
    });

// CORREGIDO - Manejo de confirmación de acción
$('#confirmCaptacionBtn').click(function() {
    const id = $('#captacionId').val();
    const accion = $('#captacionAccion').val();
    const motivo = $('#motivoCancelacion').val();
    const usuarioId = $('#usuarioId').val();
    const usuarioNombre = $('#usuarioNombre').val();
    
    // Validar motivo si es desactivar
    if (accion === 'desactivar' && !motivo.trim()) {
        showToast('Debe proporcionar un motivo para cancelar la captación', 'error');
        return;
    }
    
    // Deshabilitar botón mientras se procesa
    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Procesando...');
    
    $.ajax({
        url: 'actualizar_status_captacion.php',
        type: 'POST',
        data: {
            id: id,
            accion: accion,
            motivo: motivo,
            usuarioId: usuarioId,
            usuarioNombre: usuarioNombre
        },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta recibida:', response);
            
            if (response.success) {
                // Mostrar mensaje de éxito
                showToast(response.message, 'success');
                
                // Cerrar modal
                $('#modalCaptacionModal').modal('hide');
                
                // Recargar la tabla después de 1 segundo
                setTimeout(() => {
                    reloadTable();
                }, 1000);
            } else {
                // Mostrar error
                showToast(response.message || 'Error desconocido', 'error');
                $btn.prop('disabled', false).html(
                    accion === 'activar' ? 
                    '<i class="bi bi-check-circle me-1"></i>Reactivar Captación' : 
                    '<i class="bi bi-x-circle me-1"></i>Cancelar Captación'
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
                '<i class="bi bi-check-circle me-1"></i>Reactivar Captación' : 
                '<i class="bi bi-x-circle me-1"></i>Cancelar Captación'
            );
        }
    });
});

// Restablecer modal al cerrarse
$('#modalCaptacionModal').on('hidden.bs.modal', function() {
    $('#motivoCancelacion').val('').prop('required', false);
    $('#motivoContainer').hide();
    $('#modalCaptacionHeader').removeClass('text-bg-danger text-bg-success');
    $('#confirmCaptacionBtn')
        .removeClass('btn-danger btn-success')
        .prop('disabled', false)
        .html('Confirmar');
});



// Función mejorada para mostrar notificaciones
function showToast(message, type) {
    const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle';
    
    const toast = $(`
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icon} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    $('#toastContainer').append(toast);
    const bsToast = new bootstrap.Toast(toast[0], {
        autohide: true,
        delay: 5000
    });
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

    // ============================================
    // FUNCIONES PARA VISUALIZAR COMPROBANTES
    // ============================================
    let currentFilename = null;
    
    // Hacer las facturas clickeables
    $(document).on('click', '.factura-link', function(e) {
        e.preventDefault();
        const filename = $(this).data('filename');
        const filetype = $(this).data('filetype');
        const nombre = $(this).data('nombre');
        
        if (filename) {
            viewComprobante(filename, filetype, nombre);
        }
    });
    
    function viewComprobante(filename, filetype, productName) {
        const modal = new bootstrap.Modal(document.getElementById('modalViewComprobante'));
        const modalBody = document.getElementById('modalViewComprobanteBody');
        const modalTitle = document.getElementById('modalViewComprobanteTitle');
        const btnDownload = document.getElementById('btnDownloadComprobante');
        const btnRotate = document.getElementById('btnRotateImage');
        
        currentFilename = filename;
        modalTitle.innerHTML = `<i class="bi bi-file-earmark me-2"></i> ${escapeHtml(productName)}`;
        
        // Configurar enlace de descarga
        btnDownload.onclick = function() {
            downloadComprobante();
        };
        
        if (filetype && filetype.includes('pdf')) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <iframe src="uploads/comprobantes/${encodeURIComponent(filename)}" 
                            style="width: 100%; height: 600px; border: none; border-radius: 8px;">
                    </iframe>
                </div>
            `;
            btnRotate.style.display = 'none';
        } else if (filetype && filetype.includes('image')) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <img src="uploads/comprobantes/${encodeURIComponent(filename)}" 
                         alt="${escapeHtml(productName)}" 
                         style="max-width: 100%; max-height: 600px; border-radius: 8px; cursor: pointer;" />
                </div>
            `;
            btnRotate.style.display = 'block';
            btnRotate.onclick = function() {
                rotateImageInViewer(filename);
            };
        } else {
            modalBody.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i>
                    Tipo de archivo no soportado para vista previa
                </div>
            `;
            btnRotate.style.display = 'none';
        }
        
        modal.show();
    }
    
    function downloadComprobante() {
        if (currentFilename) {
            window.location.href = 'uploads/comprobantes/' + encodeURIComponent(currentFilename);
        }
    }
    
    function rotateImageInViewer(filename) {
        const img = document.querySelector('#modalViewComprobanteBody img');
        if (img) {
            const currentRotation = parseInt(img.style.transform.replace('rotate(', '').replace('deg)', '')) || 0;
            const newRotation = currentRotation + 90;
            img.style.transform = `rotate(${newRotation}deg)`;
            img.style.transition = 'transform 0.3s ease';
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>