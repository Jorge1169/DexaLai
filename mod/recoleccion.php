<?php
// recoleccion.php
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');
?>
<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    td {
        font-size: 13px;
    }
    /* ESTILOS MINIMALISTAS */
    .badge-xs {
        font-size: 0.65rem;
        padding: 0.25em 0.4em;
    }
    .text-truncate-xs {
        max-width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .folio-link-sm {
        font-weight: 600;
        font-size: 0.8rem;
    }
    .price-sm {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    /* HEADER COMPACTO Y RESPONSIVE */
    .compact-header {
        padding: 0.75rem 1rem;
    }
    .compact-toolbar {
        gap: 0.5rem;
    }
    .compact-toolbar .btn {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
    
    /* RESPONSIVE PARA MÓVIL */
    @media (max-width: 768px) {
        .header-mobile {
            flex-direction: column;
            align-items: start !important;
            gap: 0.5rem;
        }
        .toolbar-mobile {
            flex-wrap: wrap;
            justify-content: start;
        }
        .toolbar-mobile .btn {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
        }
        .title-mobile {
            font-size: 1rem !important;
        }
    }
    
    /* FILTROS COMPACTOS */
    .filter-row {
        gap: 0.5rem;
        padding: 0.75rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        margin-bottom: 0.75rem;
    }
    .filter-row .form-control-sm {
        font-size: 0.75rem;
        height: calc(1.5em + 0.5rem + 2px);
    }
    .filter-row label {
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: var(--bs-secondary-color);
    }
    
    /* LEYENDA MINI */
    .leyenda-mini {
        font-size: 0.7rem;
        padding: 0.4rem 0.6rem;
        margin-bottom: 0.75rem;
    }
    .color-box-sm {
        width: 12px;
        height: 12px;
        border-radius: 2px;
        margin-right: 4px;
    }
    .leyenda-item-sm {
        margin-right: 10px;
    }
    
    /* ESTADOS DE FILA MÁS SUTILES */
    .table-success {
        background-color: rgba(var(--bs-success-rgb), 0.05) !important;
    }
    .table-warning {
        background-color: rgba(var(--bs-warning-rgb), 0.05) !important;
    }
    .table-primary {
        background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
    }
    
    /* SCROLL PERSONALIZADO */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: var(--bs-secondary-bg);
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--bs-border-color);
        border-radius: 3px;
    }
</style>

<div class="container-fluid mt-1 fade-in">
    <div class="card border-0 shadow-none">
        <!-- HEADER RESPONSIVE CON BOTÓN DE VERIFICAR DOCUMENTOS -->
        <div class="card-header compact-header encabezado-col border-bottom py-2">
            <div class="d-flex justify-content-between align-items-center header-mobile">
                <h5 class="mb-0 fw-bold text-white title-mobile">
                    <i class="bi bi-truck me-1"></i> Recolecciones
                </h5>
                <div class="d-flex compact-toolbar toolbar-mobile">
                    <a href="?p=N_recoleccion" class="btn btn-primary btn-xs align-items-center gap-1" target="_blank" <?= $perm['Recole_Crear'];?>>
                        <i class="bi bi-plus"></i> <span class="d-none d-sm-inline">Nueva</span>
                    </a>
                    <button class="btn btn-secondary btn-xs align-items-center gap-1" id="toggleInactiveBtn" <?= $perm['INACTIVO'];?>>
                        <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Inactivas</span>
                    </button>
                    <button type="button" class="btn btn-teal btn-xs align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#BuscarFac" <?= $perm['ACT_FAC'];?>>
                        <i class="bi bi-file-text"></i> <span class="d-none d-sm-inline">Facturas</span>
                    </button>
                    <button type="button" class="btn btn-indigo btn-xs align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#BuscarContra" <?= $perm['ACT_CR'];?>>
                        <i class="bi bi-receipt"></i> <span class="d-none d-sm-inline">C. Recibos</span>
                    </button>
                    <!-- BOTÓN DE VERIFICAR DOCUMENTOS AGREGADO -->
                    <button type="button" class="btn btn-warning btn-xs align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#VerificarDocumentos" <?= $perm['ACT_FAC'];?>>
                        <i class="bi bi-shield-check"></i> <span class="d-none d-sm-inline">Verificar</span>
                    </button>
                    <!-- Boton para buscar tickets de conpro -->
                     <button type="button" class="btn btn-info btn-xs align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#BuscarTickets" <?= $perm['ACT_FAC'];?>>
                        <i class="bi bi-ticket-perforated"></i> <span class="d-none d-sm-inline">Tickets</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-2">
            <!-- LEYENDA MINI -->
            <div class="alert alert-light border-0 leyenda-mini py-1 px-2 mb-2">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="text-muted fw-medium">Estados:</span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-success"></span>
                        <span>Ambos CR</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-warning"></span>
                        <span>Solo Compra</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-primary"></span>
                        <span>Solo Flete</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-body border"></span>
                        <span>Sin CR</span>
                    </span>
                </div>
            </div>

            <!-- FILA DE FILTROS COMPACTA -->
            <div class="d-flex flex-wrap align-items-center filter-row">
                <div class="d-flex align-items-center gap-1">
                    <label class="mb-1 fw-bolder">Desde:</label>
                    <input type="date" id="minDate" value="<?= $fechaInicioDefault ?>" class="form-control form-control-sm" style="width: 130px;">
                </div>
                <div class="d-flex align-items-center gap-1">
                    <label class="mb-1 fw-bolder">Hasta:</label>
                    <input type="date" id="maxDate" value="<?= $fechaFinDefault ?>" class="form-control form-control-sm" style="width: 130px;">
                </div>
                <div class="d-flex gap-1">
                    <button id="filterBtn" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <button id="resetBtn" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            
            <!-- TABLA ULTRA COMPACTA -->
            <div class="table-responsive shadow-lg border rounded-4 p-3 mb-2">
                <table class="table table-hover table-bordered table-sm table-compact" id="tablaRecolecciones" style="width:100%">
                    <thead>
                        <tr>
                            <th width="35">#</th>
                            <th width="70">Acciones</th>
                            <th width="95">Folio</th>
                            <th width="90">Proveedor</th>
                            <th width="110">Bod. Proveedor</th>
                            <th width="120">Fletero</th>
                            <th width="100">Cliente</th>
                            <th width="110">Bod. Cliente</th>
                            <th width="130">Producto</th>
                            <th width="80">Remisión</th>
                            <th width="80">Remi Ixt</th>
                            <th width="90">F. Venta</th>
                            <th width="90">F. Compra</th>
                            <th width="75">CR Compra</th>
                            <th width="90">F. Flete</th>
                            <th width="75">CR Flete</th>
                            <th width="85" class="text-end">P. Flete</th>
                            <th width="85" class="text-end">P. Compra</th>
                            <th width="85" class="text-end">P. Venta</th>
                            <th width="60">Zona</th>
                            <th width="90">Fecha</th>
                            <th width="50">Correos</th>
                            <th width="65">Status</th>
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
<!-- Modal para verificar documentos eliminados -->
<div class="modal fade" id="VerificarDocumentos" tabindex="-1" data-bs-backdrop="static" aria-labelledby="verificarDocumentosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h1 class="modal-title fs-5" id="verificarDocumentosLabel">
                    <i class="bi bi-shield-check me-2"></i>Verificar Documentos Eliminados
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                        <div>
                            <strong>Verificación de Documentos en Sistema Externo</strong>
                            <p class="mb-0 mt-1">Este proceso verificará si las facturas y contrarecibos aún existen en INVOICE. Los documentos eliminados serán marcados y se incrementará su contador de eliminaciones.</p>
                        </div>
                    </div>
                </div>
                <div id="resultadoVerificacion">
                    <!-- Aquí aparecerán los resultados de la verificación -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-warning btn-sm rounded-3" id="iniciarVerificacion">
                    <i class="bi bi-shield-check me-1"></i> Iniciar Verificación
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    // Función para verificar documentos eliminados
    function verificarDocumentos() {
    // Deshabilitar botón para evitar múltiples clics
        $('#iniciarVerificacion').prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner me-1"></i> Verificando...');

    // Mostrar estado de progreso
        $('#resultadoVerificacion').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-warning mb-3" role="status">
                <span class="visually-hidden">Verificando...</span>
            </div>
            <p class="mt-2">Verificando documentos en sistema externo...</p>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
        `);

        $.ajax({
            url: 'verificar_documentos.php',
            type: 'POST',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
success: function(response) {
    setTimeout(function() {
        var resultado = JSON.parse(response);

        if (resultado.success) {
            var data = resultado.data;
            var html = `
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Verificación Completada</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- PROVEEDOR -->
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-danger">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="bi bi-file-earmark-x fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-danger">${data.facturas_proveedor_eliminadas}</h3>
                                    <p class="card-text text-muted">Facturas proveedor eliminadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <div class="text-warning mb-2">
                                        <i class="bi bi-file-earmark-excel fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-warning">${data.facturas_proveedor_rechazadas}</h3>
                                    <p class="card-text text-muted">Facturas proveedor rechazadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-danger">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="bi bi-receipt-cutoff fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-danger">${data.contrarecibos_proveedor_eliminados}</h3>
                                    <p class="card-text text-muted">CR proveedor eliminados</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FLETERO -->
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-danger">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="bi bi-file-earmark-x fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-danger">${data.facturas_fletero_eliminadas}</h3>
                                    <p class="card-text text-muted">Facturas fletero eliminadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <div class="text-warning mb-2">
                                        <i class="bi bi-file-earmark-excel fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-warning">${data.facturas_fletero_rechazadas}</h3>
                                    <p class="card-text text-muted">Facturas fletero rechazadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-danger">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="bi bi-receipt-cutoff fs-1"></i>
                                    </div>
                                    <h3 class="card-title text-danger">${data.contrarecibos_fletero_eliminados}</h3>
                                    <p class="card-text text-muted">CR fletero eliminados</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Se procesaron <strong>${data.total_actualizaciones}</strong> recolecciones en total.
                    </div>
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Lógica aplicada:</strong><br>
                        • Factura eliminada/rechazada → Se elimina factura + contrarecibo<br>
                        • Solo contrarecibo eliminado → Se elimina solo contrarecibo
                    </div>
                </div>
            </div>
            `;
        } else {
            var html = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                Error en la verificación: ${resultado.message}
            </div>
            `;
        }

        $('#resultadoVerificacion').html(html);
        $('#iniciarVerificacion').prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i> Iniciar Verificación');
    }, 500);
},
error: function() {
    $('#resultadoVerificacion').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    Error al procesar la verificación. Por favor intente nuevamente.
                </div>
    `);
    $('#iniciarVerificacion').prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i> Iniciar Verificación');
}
});
}

// Event listener para el botón de verificación
document.getElementById('iniciarVerificacion').addEventListener('click', verificarDocumentos);

// Recargar página al cerrar el modal de verificación
document.addEventListener('DOMContentLoaded', function() {
    var modalVerificacion = document.getElementById('VerificarDocumentos');
    
    modalVerificacion.addEventListener('hidden.bs.modal', function () {
        location.reload();
    });
});
</script>
<!-- Modal para buscar contra recibos-->
<div class="modal fade" id="BuscarContra" tabindex="-1" data-bs-backdrop="static" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-indigo text-white">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Buscar Contra Recibos</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="recargarPagina()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                        <div>
                            <strong>Búsqueda de Contra Recibos de Compra y Fletes</strong>
                            <p class="mb-0 mt-1">Se buscarán los contra recibos de proveedores y documentos de fleteros.</p>
                        </div>
                    </div>
                </div>
                <div id="AreCR">
                    <!-- Aquí aparecerán los resultados de la búsqueda -->
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="zonCR" id="zonCR" value="<?=$zona_seleccionada?>">
                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm" data-bs-dismiss="modal" onclick="recargarPagina()">
                    <i class="bi bi-x-circle me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-teal btn-sm rounded-3 mt-1" id="ActualizarCR" onclick="actualizarCR()">
                    <i class="bi bi-search me-1"></i> Iniciar Búsqueda
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function actualizarCR() {
        var zonCR = document.getElementById('zonCR').value;

    // Deshabilitar botón para evitar múltiples clics
        $('#ActualizarCR').prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner me-1"></i> Procesando...');

    // Limpiar área de resultados
        $('#AreCR').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-teal mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Buscando facturas y documentos...</p>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
        `);

        var parametros = {
            "zonCR": zonCR, 
        };

        $.ajax({
            data: parametros,
            url: 'b_Contra.php',
            type: 'POST',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(mensaje) {
                setTimeout(function() {
                    $('#AreCR').html(mensaje);
                // Habilitar botón nuevamente
                    $('#ActualizarCR').prop('disabled', false).html('<i class="bi bi-search me-1"></i> Iniciar Búsqueda');
                }, 500);
            },
            error: function() {
                $('#AreCR').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i>
                    Error al procesar la solicitud. Por favor intente nuevamente.
                </div>
                `);
                $('#ActualizarCR').prop('disabled', false).html('<i class="bi bi-search me-1"></i> Iniciar Búsqueda');
            }
        });
    }

    // Recargar página al cerrar el modal
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = document.getElementById('BuscarContra');

        myModal.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>
<!-- Modal para buscar facturas -->
<div class="modal fade" id="BuscarFac" tabindex="-1" data-bs-backdrop="static" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-teal text-white">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <i class="bi bi-file-earmark-ruled me-2"></i>Buscar facturas
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="recargarPagina()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                        <div>
                            <strong>Búsqueda de facturas y documentos faltantes</strong>
                            <p class="mb-0 mt-1">Se buscarán facturas de proveedores y documentos de fleteros.</p>
                        </div>
                    </div>
                </div>
                <div id="AreFact">
                    <!-- Aquí aparecerán los resultados de la búsqueda -->
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="zon" id="zon" value="<?=$zona_seleccionada?>">
                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm" data-bs-dismiss="modal" onclick="recargarPagina()">
                    <i class="bi bi-x-circle me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-teal btn-sm rounded-3 mt-1" id="Actualizar" onclick="actualizar()">
                    <i class="bi bi-search me-1"></i> Iniciar Búsqueda
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function actualizar() {
        var zon = document.getElementById('zon').value;

    // Deshabilitar botón para evitar múltiples clics
        $('#Actualizar').prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner me-1"></i> Procesando...');

    // Limpiar área de resultados
        $('#AreFact').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-teal mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Buscando facturas y documentos...</p>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
        `);

        var parametros = {
            "zon": zon,
        };

        $.ajax({
            data: parametros,
            url: 'b_factura.php',
            type: 'POST',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(mensaje) {
                setTimeout(function() {
                    $('#AreFact').html(mensaje);
                // Habilitar botón nuevamente
                    $('#Actualizar').prop('disabled', false).html('<i class="bi bi-search me-1"></i> Iniciar Búsqueda');
                }, 500);
            },
            error: function() {
                $('#AreFact').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i>
                    Error al procesar la solicitud. Por favor intente nuevamente.
                </div>
                `);
                $('#Actualizar').prop('disabled', false).html('<i class="bi bi-search me-1"></i> Iniciar Búsqueda');
            }
        });
    }

// Recargar página al cerrar el modal
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = document.getElementById('BuscarFac');

        myModal.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>
<!-- Modal de confirmación para recolecciones -->
<div class="modal fade" id="confirmRecoleccionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="modalRecoleccionHeader" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalRecoleccionMessage">¿Estás seguro de que deseas desactivar esta recolección?</p>
                <input type="hidden" id="recoleccionId">
                <input type="hidden" id="recoleccionAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmRecoleccionBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
    // Variables globales
        let showingInactivesRecolecciones = false;
        let table;

    // Inicializar DataTable con AJAX
        function initDataTable() {
            table = $('#tablaRecolecciones').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "ajax_recolecciones.php",
                    "type": "POST",
                    "data": function(d) {
                    // Pasar parámetros adicionales al servidor
                        d.zona = '<?= $zona_seleccionada ?>';
                        d.mostrarInactivos = showingInactivesRecolecciones;
                        d.fechaInicio = $('#minDate').val();
                        d.fechaFin = $('#maxDate').val();
                    },
                    "timeout": 30000
                },
                "fixedHeader": {
                    "header": true,
                "headerOffset": $('.navbar').outerHeight() || 0 // Ajusta según tu layout
            },
            "scrollX": true, // Importante para tablas anchas
            "scrollY": "500px", // Altura fija para el scroll
            "scrollCollapse": false,
            "paging": true,
            "createdRow": function(row, data, dataIndex) {
            // Aquí aplicamos las clases según los contrarecibos
            // data[11] = C.R compra, data[13] = C.R flete
            const crCompra = data[13]; // Contra recibo de compra
            const crFlete = data[15]; // Contra recibo de flete
            
            // Verificar si los elementos contienen texto (no están vacíos)
            const tieneCompra = crCompra && crCompra.trim() !== '' && !crCompra.includes('text-danger');
            const tieneFlete = crFlete && crFlete.trim() !== '' && !crFlete.includes('text-danger');
            
            if (tieneCompra && tieneFlete) {
                // Tiene ambos contrarecibos
                $(row).addClass('table-success');
            } else if (tieneCompra) {
                // Solo tiene contra recibo de compra
                $(row).addClass('table-warning');
            } else if (tieneFlete) {
                // Solo tiene contra recibo de flete
                $(row).addClass('table-primary');
            } else {
                // No tiene ningún contra recibo
                $(row).addClass('');
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
        "responsive": false,

        "columnDefs": [
            { 
                "targets": 0, 
                "data": null,
                "render": function(data, type, row, meta) {
                        // Numeración de filas
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                "orderable": false
            },
            { 
                "targets": 1, 
                "data": null,
                "render": function(data, type, row) {
                        // Columna de acciones
                        const id = row[24]; // ID en la última posición
                        const status = row[23]; // Status (1 = activo, 0 = inactivo)
                        
                        let buttons = '';
                        if (status === '1') {
                            buttons = `
                                <div class="d-flex gap-2">
                                    <a href="?p=E_recoleccion&id=${id}" class="btn btn-info btn-sm rounded-3" title="Editar" target="_blank" <?= $perm['Recole_Editar'];?>>
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-recoleccion-btn" 
                                        data-id="${id}" title="Borrar / Desactivar">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            `;
                        } else {
                            buttons = `
                                <div class="d-flex gap-2">
                                    <button class="btn btn-info btn-sm rounded-3 activar-recoleccion-btn" 
                                        data-id="${id}" title="Activar recolección">
                                        Activar
                                    </button>
                                </div>
                            `;
                        }
                        return buttons;
                    },
                    "orderable": false
                },
            ],
            "order": [[2, 'desc']] // Ordenar por fecha de recolección por defecto
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
    showingInactivesRecolecciones = !showingInactivesRecolecciones;

    if (showingInactivesRecolecciones) {
        $(this).html('<i class="bi bi-eye-slash"></i> Ocultar');
        $(this).removeClass('btn-secondary').addClass('btn-info');
    } else {
        $(this).html('<i class="bi bi-eye"></i> Mostrar');
        $(this).removeClass('btn-info').addClass('btn-secondary');
    }

    reloadTable();
});

    // Filtrar por rango de fechas
$('#filterBtn').click(function() {
    reloadTable();
});

// Resetear filtros - ACTUALIZADO
$('#resetBtn').click(function() {
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    const lastDay = new Date();

    $('#minDate').val(firstDay.toISOString().split('T')[0]);
    $('#maxDate').val(lastDay.toISOString().split('T')[0]);
    showingInactivesRecolecciones = false;
    $('#toggleInactiveBtn').html('<i class="bi bi-eye"></i> Mostrar Inactivas')
    .removeClass('btn-info').addClass('btn-secondary');
    reloadTable();
});

    // Configurar modal para desactivar/activar recolecciones
$(document).on('click', '.desactivar-recoleccion-btn', function() {
    const id = $(this).data('id');
    $('#recoleccionId').val(id);
    $('#recoleccionAccion').val('desactivar');
    $('#modalRecoleccionMessage').text('¿Estás seguro de que deseas desactivar esta recolección?');
    $('#confirmRecoleccionModal').modal('show');
    $('#modalRecoleccionHeader').addClass('text-bg-warning');
    $('#confirmRecoleccionBtn').addClass('btn-warning').removeClass('btn-info');
});

$(document).on('click', '.activar-recoleccion-btn', function() {
    const id = $(this).data('id');
    $('#recoleccionId').val(id);
    $('#recoleccionAccion').val('activar');
    $('#modalRecoleccionMessage').text('¿Estás seguro de que deseas reactivar esta recolección?');
    $('#confirmRecoleccionModal').modal('show');
    $('#modalRecoleccionHeader').addClass('text-bg-info');
    $('#confirmRecoleccionBtn').addClass('btn-info').removeClass('btn-warning');
});

    // Confirmar acción para recolecciones
$('#confirmRecoleccionBtn').click(function() {
    const id = $('#recoleccionId').val();
    const accion = $('#recoleccionAccion').val();

    $.post('actualizar_status_recoleccion.php', {
        id: id,
        accion: accion,
        tabla: 'recoleccion'
    }, function(response) {
        if (response.success) {
                // Recargar la tabla después de cambiar el status
            reloadTable();

                // Si estamos viendo inactivos y activamos uno, podría desaparecer de la vista
                // Podemos mostrar un mensaje o mantener la vista actual
            if (accion === 'activar' && showingInactivesRecolecciones) {
                    // Si activamos un inactivo mientras vemos inactivos, recargar para que desaparezca
                setTimeout(function() {
                    reloadTable();
                }, 500);
            }
        } else {
            alert('Error: ' + response.message);
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        alert('Error en la solicitud: ' + textStatus + ', ' + errorThrown);
    });

    $('#confirmRecoleccionModal').modal('hide');
});
});
</script>
<!-- Modal para buscar Ticket's -->
  <div class="modal fade" id="BuscarTickets" tabindex="-1" aria-labelledby="exampleModalLabelCR" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabelCR">Buscar Tickets</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body " id="resultadoCR">
        <!-- Aquí se cargarán los C.R mediante AJAX -->
        <!-- Hay que crear una alerta de boostrap para advertir que la validacion de C.R buscara los C.R de la zona seleccionada en invoice -->
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle-fill me-2"></i>
          <div>
            La búsqueda de Tickets se realizará en la zona seleccionada actualmente. Asegúrese de que la zona sea correcta antes de proceder.
          </div>    
          </div>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <input type="hidden" id="filtroZonaTK" value="<?= htmlspecialchars($zona_seleccionada); ?>" class="form-control" placeholder="Filtrar por zona">
        <button type="button" class="btn btn-primary" onclick="cambiarZonaCR()">Buscar</button>
      </div>
    </div>
  </div>
</div>
<script>
    // enviar zona a b_cr_a.php al precionar el boton buscar C.R
    function cambiarZonaCR() {
        var zonaId = $('#filtroZonaTK').val();
        var spinner = '<div class="d-flex justify-content-center align-items-center" style="height:200px;">' +
                      '<div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>' +
                      '</div>';

        $.ajax({
            url: 'b_tickets.php',
            type: 'POST',
            data: { zona: zonaId },
            beforeSend: function() {
                $('#resultadoCR').html(spinner);
            },
            success: function(response) {
                $('#resultadoCR').html(response);
            },
            error: function(xhr, status, error) {
                $('#resultadoCR').html('<div class="alert alert-danger">Error al cargar Tickets. Intente nuevamente.</div>');
                console.error('AJAX error:', status, error);
            }
        });
    }
</script>