<?php
// contra_recibos.php
?>
<style>
   .accordion-button:not(.collapsed) {
    background-color: var(--bs-primary-bg-subtle);
    color: #0c63e4;
}
.badge-counter {
    font-size: 0.75rem;
}
/* Estilos para el área de detalles */
.detalles-contenido {
    background-color: var(--bs-tertiary-bg);
    border-radius: 0.375rem;
    padding: 1rem;
    border-left: 4px solid var(--bs-primary);
}

.detalles-contenido .table {
    margin-bottom: 0;
}

.detalles-contenido h6 {
    font-weight: 600;
}

/* Mejoras responsive */
@media (max-width: 768px) {
    .detalles-contenido {
        padding: 0.5rem;
    }

    .detalles-contenido .table-responsive {
        font-size: 0.8rem;
    }
}
</style>

<div class="container-fluid mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">
            <i class="bi bi-files me-2"></i>Módulo de Contra Recibos
        </h5>
        <div class="card-body">
            <!-- Filtros y controles -->
            <div class="mb-3">
                <!-- PRIMERA FILA: Filtros principales -->
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <!-- Selector de tipo -->
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="tipoContra" id="todos" checked>
                        <label class="btn btn-outline-primary btn-sm" for="todos">
                            <i class="bi bi-grid-3x3-gap"></i> Todos
                        </label>
                        
                        <input type="radio" class="btn-check" name="tipoContra" id="compras">
                        <label class="btn btn-outline-success btn-sm" for="compras">
                            <i class="bi bi-cart-check"></i> Compras
                        </label>
                        
                        <input type="radio" class="btn-check" name="tipoContra" id="fletes">
                        <label class="btn btn-outline-indigo btn-sm" for="fletes">
                            <i class="bi bi-truck"></i> Fletes
                        </label>
                    </div>
                    
                    <!-- Filtros adicionales -->
                    <select class="form-select form-select-sm" style="width: 200px;" id="filtroProveedor">
                        <option value="">Todos los proveedores</option>
                        <!-- Opciones se cargarán via AJAX -->
                    </select>
                    
                    <select class="form-select form-select-sm" style="width: 200px;" id="filtroFletero">
                        <option value="">Todos los fleteros</option>
                        <!-- Opciones se cargarán via AJAX -->
                    </select>
                    <script>
                        $(document).ready(function() {
                            $('#filtroFletero').select2({
                                language: "es"
                            });
                        });
                    </script>
                    
                    <!-- Filtros de Fecha -->
                    <div class="d-flex align-items-center gap-1">
                        <label for="fechaDesde" class="small text-nowrap">Desde:</label>
                        <input type="date" class="form-control form-control-sm" id="fechaDesde" style="width: 140px;" 
                        value="<?= date('Y-m-01') ?>">
                    </div>
                    
                    <div class="d-flex align-items-center gap-1">
                        <label for="fechaHasta" class="small text-nowrap">Hasta:</label>
                        <input type="date" class="form-control form-control-sm" id="fechaHasta" style="width: 140px;" 
                        value="<?= date('Y-m-t') ?>">
                    </div>
                </div>
                
                <!-- SEGUNDA FILA: Nuevos filtros de búsqueda específica -->
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <!-- Búsqueda por folio de contra -->
                    <div class="d-flex align-items-center gap-1">
                        <label for="buscarFolio" class="small text-nowrap">Folio Contra:</label>
                        <input type="text" class="form-control form-control-sm" id="buscarFolio" style="width: 120px;" 
                        placeholder="Número" maxlength="10" title="Buscar por número de folio del contra recibo">
                    </div>
                    
                    <!-- Selector de alias (LAISA/DESA) -->
                    <div class="d-flex align-items-center gap-1">
                        <label for="filtroAlias" class="small text-nowrap">Compañía:</label>
                        <select class="form-select form-select-sm" id="filtroAlias" style="width: 130px;" title="Filtrar por compañía">
                            <option value="">Todas</option>
                            <option value="LAISA">LAISA</option>
                            <option value="DESA">DESA</option>
                        </select>
                    </div>
                    
                    <!-- Botones de acción -->
                    <button class="btn btn-primary btn-sm" id="btnFiltrar">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    
                    <button class="btn btn-outline-secondary btn-sm" id="btnReset">
                        <i class="bi bi-arrow-clockwise"></i> Resetear
                    </button>

                    <button class="btn btn-outline-teal btn-sm" id="btnMesActual">
                        <i class="bi bi-calendar-month"></i> Mes Actual
                    </button>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-primary bg-opacity-10 border-primary">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0 text-primary">Total Contra Recibos</h6>
                                    <small class="text-muted">Compras + Fletes</small>
                                </div>
                                <span class="badge bg-primary fs-6" id="totalContras">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success bg-opacity-10 border-success">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0 text-success">Contra Recibos Compras</h6>
                                    <small class="text-muted">Proveedores</small>
                                </div>
                                <span class="badge bg-success fs-6" id="totalCompras">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-indigo bg-opacity-10 border-indigo">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0 text-indigo">Contra Recibos Fletes</h6>
                                    <small class="text-muted">Fleteros</small>
                                </div>
                                <span class="badge bg-indigo fs-6" id="totalFletes">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Área de resultados -->
            <div id="resultadosContraRecibos">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando contra recibos...</p>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Establecer fechas por defecto (mes actual)
        establecerFechasMesActual();
        // Cargar datos iniciales
        cargarContraRecibos();
        cargarFiltros();

        // Eventos de filtros
        $('input[name="tipoContra"]').change(function() {
            cargarContraRecibos();
        });

        $('#btnFiltrar').click(function() {
            cargarContraRecibos();
        });

    // Actualizar función reset para incluir nuevos filtros
        $('#btnReset').click(function() {
            $('input[name="tipoContra"]').prop('checked', false);
            $('#todos').prop('checked', true);
            $('#filtroProveedor, #filtroFletero, #filtroAlias').val('');
            $('#buscarFolio').val('');
            $('#fechaDesde, #fechaHasta').val('');
            establecerFechasMesActual();
            cargarContraRecibos();
        });
        // Permitir búsqueda con Enter en el campo de folio
        $('#buscarFolio').keypress(function(e) {
            if (e.which === 13) { // Enter key
                cargarContraRecibos();
            }
        });
        // Nuevo: Botón para mes actual 
        $('#btnMesActual').click(function() {
            establecerFechasMesActual();
            cargarContraRecibos();
        });
    });
    function establecerFechasMesActual() {
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

    // Formatear a YYYY-MM-DD
        const formatoFecha = (fecha) => {
            return fecha.toISOString().split('T')[0];
        };

        $('#fechaDesde').val(formatoFecha(primerDiaMes));
        $('#fechaHasta').val(formatoFecha(ultimoDiaMes));
    }

    function cargarFiltros() {
    // Cargar proveedores
        $.post('ajax_contra_recibos.php', {accion: 'cargarProveedores'}, function(data) {
            $('#filtroProveedor').html('<option value="">Todos los proveedores</option>' + data);
        });

    // Cargar fleteros
        $.post('ajax_contra_recibos.php', {accion: 'cargarFleteros'}, function(data) {
            $('#filtroFletero').html('<option value="">Todos los fleteros</option>' + data);
        });
    }
    function debugFiltros() {
        const filtros = {
            tipo: $('input[name="tipoContra"]:checked').attr('id'),
            proveedor: $('#filtroProveedor').val(),
            fletero: $('#filtroFletero').val(),
            fechaDesde: $('#fechaDesde').val(),
            fechaHasta: $('#fechaHasta').val(),
            buscarFolio: $('#buscarFolio').val(),
            filtroAlias: $('#filtroAlias').val()
        };
        
        console.log('Filtros enviados:', filtros);
        return filtros;
    }

    function cargarContraRecibos() {
    const filtros = debugFiltros(); // Debug
    
    $('#resultadosContraRecibos').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando contra recibos...</p>
            <div class="text-muted small">
                Filtros: ${JSON.stringify(filtros)}
            </div>
        </div>
    `);
    
    $.post('ajax_contra_recibos.php', {
        accion: 'cargarContraRecibos',
        ...filtros
    }, function(data) {
        $('#resultadosContraRecibos').html(data);
    }).fail(function(xhr, status, error) {
        console.error('Error AJAX:', error);
        $('#resultadosContraRecibos').html(`
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Error al cargar los datos: ${error}
            </div>
        `);
    });
}
// Función para validar contra recibos antes de guardar
function validarContraRecibo(tipo, alias, folio, entidadId, callback) {
    $.post('ajax_contra_recibos.php', {
        accion: 'validarContraRecibo',
        tipo: tipo,
        alias: alias,
        folio: folio,
        entidadId: entidadId
    }, function(response) {
        if (typeof callback === 'function') {
            callback(response);
        }
    }).fail(function() {
        if (typeof callback === 'function') {
            callback({valido: false, mensaje: 'Error al validar el contra recibo'});
        }
    });
}

// Ejemplo de uso cuando se crea un nuevo contra recibo
function crearContraRecibo(tipo, alias, folio, entidadId) {
    validarContraRecibo(tipo, alias, folio, entidadId, function(validacion) {
        if (validacion.valido) {
            // Proceder con la creación del contra recibo
            alert('Contra recibo válido, procediendo...');
            // ... código para guardar ...
        } else {
            // Mostrar error y no permitir guardar
            alert(validacion.mensaje);
            // Opcional: enfocar el campo de folio para corrección
            $('#campoFolio').focus();
        }
    });
}
function cargarDetallesAutomáticos(collapseId, alias, folio) {
    const collapseElement = document.querySelector(collapseId);
    if (!collapseElement) return;
    
    // Encontrar todos los elementos de detalles dentro de este collapse
    const detallesElements = collapseElement.querySelectorAll('.detalles-contenido');
    
    detallesElements.forEach(detallesElement => {
        // Extraer información del elemento
        const idParts = detallesElement.id.split('-');
        const index = idParts[1];
        const entidadId = idParts[2];
        
        // Determinar el tipo basado en el contenido
        const entidadHeader = detallesElement.closest('.entidad-detalle').querySelector('.entidad-header');
        const tipo = entidadHeader.textContent.includes('Compra') ? 'compras' : 'fletes';
        
        // Cargar detalles via AJAX
        $.post('ajax_contra_recibos.php', {
            accion: 'cargarDetallesContra',
            tipo: tipo,
            alias: alias,
            folio: folio,
            entidadId: entidadId
        }, function(data) {
            detallesElement.innerHTML = data;
        }).fail(function() {
            detallesElement.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles.</div>';
        });
    });
}

</script>