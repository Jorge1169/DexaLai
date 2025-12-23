<?php
// reporte_recole.php
$fechaInicioDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-d');
?>
<style>
    /* ANIMACIONES SUAVES */
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
    .utilidad-positiva { color: #198754; font-weight: bold; }
    .utilidad-negativa { color: #dc3545; font-weight: bold; }
    
    /* ESTILOS MINIMALISTAS PARA REPORTES */
    .table-compact {
        font-size: 0.8rem;
    }
    .table-compact th {
        font-weight: 600;
        padding: 8px 6px;
        background-color: var(--bs-table-bg);
        border-bottom: 1px solid var(--bs-border-color);
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
    }
    .table-compact td {
        padding: 6px;
        vertical-align: middle;
        border-color: var(--bs-border-color);
    }
    
    /* ESTADÍSTICAS COMPACTAS */
    .estadistica-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 12px 8px;
        margin-bottom: 8px;
        text-align: center;
        transition: all 0.2s;
    }
    .estadistica-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        border-color: var(--bs-primary);
    }
    .estadistica-card i {
        font-size: 1.2rem;
        margin-bottom: 5px;
    }
    .estadistica-card h6 {
        font-size: 0.9rem;
        margin-bottom: 2px;
        font-weight: 600;
    }
    .estadistica-card small {
        font-size: 0.7rem;
        color: var(--bs-secondary-color);
    }
    
    /* HEADER COMPACTO */
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
        .estadistica-card {
            padding: 8px 4px;
        }
        .estadistica-card h6 {
            font-size: 0.8rem;
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
    
    /* FILTROS RÁPIDOS COMPACTOS */
    .filtros-rapidos {
        gap: 0.25rem;
    }
    .filtros-rapidos .btn {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* ESTADOS DE FILA MÁS SUTILES */
    .table-success {
        background-color: rgba(var(--bs-success-rgb), 0.05) !important;
    }
    .table-primary {
        background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
    }
    .table-info {
        background-color: rgba(var(--bs-info-rgb), 0.05) !important;
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
    
    /* PRECIOS COMPACTOS */
    .price-sm {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: 500;
    }
</style>

<!-- Modal para detalle de productos -->

<div class="container-fluid mt-1 fade-in">
    <div class="card border-0 shadow-none">
        <!-- HEADER COMPACTO -->
        <div class="card-header compact-header encabezado-col border-bottom py-2">
            <div class="d-flex justify-content-between align-items-center header-mobile">
                <h5 class="mb-0 fw-bold text-white title-mobile">
                    <i class="bi bi-graph-up me-1"></i> Reporte Financiero
                </h5>
                <div class="d-flex compact-toolbar toolbar-mobile">
                    <button id="excelBtn" class="btn btn-success btn-xs d-flex align-items-center gap-1">
                        <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">Excel</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-2">
            <!-- ESTADÍSTICAS COMPACTAS -->
            <div class="row g-2 mb-3">
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-clipboard-data text-primary"></i>
                        <h6 class="mb-0" id="totalRecolecciones">0</h6>
                        <small>Recolecciones</small>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-currency-dollar text-success"></i>
                        <h6 class="mb-0" id="utilidadTotal">$0</h6>
                        <small>Utilidad</small>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-cash-coin text-info"></i>
                        <h6 class="mb-0" id="totalVentas">$0</h6>
                        <small>Ventas</small>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-graph-down text-warning"></i>
                        <h6 class="mb-0" id="totalCompras">$0</h6>
                        <small>Compras</small>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-box-seam text-secondary"></i>
                        <h6 class="mb-0" id="totalProductos">0 kg</h6>
                        <small>Producto</small>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="estadistica-card">
                        <i class="bi bi-truck text-danger"></i>
                        <h6 class="mb-0" id="totalFletes">$0</h6>
                        <small>Fletes</small>
                    </div>
                </div>
            </div>

            <!-- LEYENDA MINI -->
            <div class="alert alert-light border-0 leyenda-mini py-1 px-2 mb-2">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="text-muted fw-medium">Contrarecibos:</span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-success"></span>
                        <span>Ambos CR</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-primary"></span>
                        <span>Solo Flete</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-info"></span>
                        <span>Solo Compra</span>
                    </span>
                    <span class="leyenda-item-sm d-flex align-items-center">
                        <span class="color-box-sm bg-body border"></span>
                        <span>Sin CR</span>
                    </span>
                </div>
            </div>

            <!-- FILTROS RÁPIDOS COMPACTOS -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="small fw-bold text-muted">Filtros CR:</span>
                <div class="d-flex flex-wrap filtros-rapidos">
                    <button class="btn btn-outline-success btn-xs filtro-rapido-btn" data-estado="completas">
                        <i class="bi bi-check-circle"></i> <span class="d-none d-sm-inline">Completas</span>
                    </button>
                    <button class="btn btn-outline-primary btn-xs filtro-rapido-btn" data-estado="solo-flete">
                        <i class="bi bi-truck"></i> <span class="d-none d-sm-inline">Solo Flete</span>
                    </button>
                    <button class="btn btn-outline-info btn-xs filtro-rapido-btn" data-estado="solo-compra">
                        <i class="bi bi-building"></i> <span class="d-none d-sm-inline">Solo Compra</span>
                    </button>
                    <button class="btn btn-outline-secondary btn-xs filtro-rapido-btn" data-estado="pendientes">
                        <i class="bi bi-exclamation-triangle"></i> <span class="d-none d-sm-inline">Pendientes</span>
                    </button>
                    <button class="btn btn-outline-indigo btn-xs filtro-rapido-btn active" data-estado="todas">
                        <i class="bi bi-list-ul"></i> <span class="d-none d-sm-inline">Todas</span>
                    </button>
                </div>
            </div>

            <!-- FILA DE FILTROS COMPACTA -->
            <div class="d-flex flex-wrap align-items-center filter-row">
                <div class="d-flex align-items-center gap-1">
                    <label class="mb-0">Desde:</label>
                    <input type="date" id="minDate" value="<?= $fechaInicioDefault ?>" class="form-control form-control-sm" style="width: 130px;">
                </div>
                <div class="d-flex align-items-center gap-1">
                    <label class="mb-0">Hasta:</label>
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
                <table class="table table-striped table-hover table-bordered table-sm" id="tablaReporteRecolecciones" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th data-priority="1">Folio</th>
                            <th>Proveedor</th>
                            <th>Bodega Prov</th>
                            <th>Fletero</th>
                            <th>Cliente</th>
                            <th>Peso Compra</th>
                            <th>Precio Compra</th>
                            <th>Total Compra</th>
                            <th>Remisión</th>
                            <th>Factura Compra</th>
                            <th>C.R Compra</th>
                            <th>Peso Flete</th>
                            <th>Precio Flete</th>
                            <th>Precio Flete/Kg</th> <!-- NUEVA COLUMNA -->
                            <th>Importe Flete</th>
                            <th>Factura Flete</th>
                            <th>C.R Flete</th>
                            <th>Precio Venta</th>
                            <th>Total Venta</th>
                            <th>Factura Venta</th>
                            <th>Utilidad</th>
                            <th>Fecha</th>
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

<!-- Incluir las librerías necesarias para Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        let table;
        let filtroEstadoActual = 'todas';

        function initDataTable() {
            table = $('#tablaReporteRecolecciones').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "ajax_reporte_recolecciones.php",
                    "type": "POST",
                    "data": function(d) {
                        d.zona = '<?= $zona_seleccionada ?>';
                        d.fechaInicio = $('#minDate').val();
                        d.fechaFin = $('#maxDate').val();
                        d.filtroEstado = filtroEstadoActual;
                    },
                    "dataSrc": function(json) {
                        console.log("Respuesta recibida:", json);

                        if (json.error) {
                            console.error("Error del servidor:", json.error);
                            alert("Error: " + json.error);
                            return [];
                        }

                        // Actualizar todas las estadísticas
                        $('#totalRecolecciones').text(json.totalRegistros || 0);
                        $('#utilidadTotal').text('$' + (json.utilidadTotal || 0).toLocaleString('es-MX'));
                        $('#totalVentas').text('$' + (json.totalVentas || 0).toLocaleString('es-MX'));
                        $('#totalCompras').text('$' + (json.totalCompras || 0).toLocaleString('es-MX'));
                        $('#totalProductos').text((json.totalProductos || 0).toLocaleString('es-MX') + ' kg');
                        $('#totalFletes').text('$' + (json.totalFletes || 0).toLocaleString('es-MX'));

                        // Guardar datos para el modal
                        window.detalleProductos = json.detalleProductos || [];
                        window.fechaInicioActual = $('#minDate').val();
                        window.fechaFinActual = $('#maxDate').val();

                        return json.data || [];
                    }
                },
                "createdRow": function(row, data, dataIndex) {
            const contraCompra = data[11]; // Índice 10: C.R compra
            const contraFlete = data[17];  // Índice 17: C.R flete

            // Verificar si tienen contrarecibos (considerando N/A como válido para flete)
            const tieneCompra = !contraCompra.includes('bg-secondary') && 
            contraCompra.trim() !== '' && 
            !contraCompra.includes('Pendiente');

            const tieneFlete = (!contraFlete.includes('bg-secondary') && 
             contraFlete.trim() !== '' && 
             !contraFlete.includes('Pendiente')) || 
            contraFlete.includes('N/A');

            // NUEVA LÓGICA SIMPLIFICADA - SOLO CONTRA RECIBOS
            if (tieneCompra && tieneFlete) {
                $(row).addClass('table-success'); // VERDE: Ambos contrarecibos
            } else if (tieneFlete) {
                $(row).addClass('table-primary');  //  AZUL: Solo C.R Flete (incluye N/A)
            } else if (tieneCompra) {
                $(row).addClass('table-info');     //  AZUL INFO: Solo C.R Compra
            } else {
            //  BLANCO: No tiene ninguno (no se aplica clase)
            }

    // Colorear utilidad (se mantiene igual)
            const utilidad = parseFloat(data[21]?.replace(/[^\d.-]/g, '') || 0);
            if (utilidad > 0) {
                $(row).find('td:eq(19)').addClass('utilidad-positiva');
            } else if (utilidad < 0) {
                $(row).find('td:eq(19)').addClass('utilidad-negativa');
            }
        },
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            "infoFiltered": ""
        },

        "fixedHeader": {
            "header": true,
                "headerOffset": $('.navbar').outerHeight() || 0 // Ajusta según tu layout
            },
            "scrollX": true, // Importante para tablas anchas
            "scrollY": "400px", // Altura fija para el scroll
            "scrollCollapse": false,
            "paging": true,
            "order": [[1, 'desc']],
            "columnDefs": [
                { 
                    "targets": 0, 
                    "data": null,
                    "render": function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    "orderable": false,
                    "width": "50px"
                },
                { 
                    "targets": 1, 
                    "width": "120px",
                    "render": function(data, type, row) {
                        const id = row[23] || 0;
                        return `<a href="?p=V_recoleccion&id=${id}" target="_blank" class="text-decoration-none fw-bold">${data}</a>`;
                    }
                },
                // Columnas que NO se pueden ordenar (calculadas)
                { "targets": [8, 9, 13, 14, 15, 18, 19, 21], "orderable": false }, // Actualizado índices

                // Clases para alineación
                { "targets": [7,8,9,12,13,14,15,18,19,21], "className": "text-end" }, // Actualizado índices
                { "targets": [9,19,21], "className": "text-end fw-bold" } // Actualizado índices
            ]
        });

        // CONFIGURACIÓN SIMPLE DEL BOTÓN CSV - COMO EN TU CÓDIGO ANTERIOR
new $.fn.dataTable.Buttons(table, {
    buttons: [
        {
            extend: 'csvHtml5',
            text: '<i class="bi bi-file-earmark-excel"></i> CSV',
            title: 'Reporte_de_Recolecciones',
            className: 'btn btn-success btn-sm',
            exportOptions: {
                columns: ':visible',
                modifier: {
                    search: 'applied',
                    order: 'applied'
                }
            },
                    // Opcional: personalizar el nombre del archivo
            filename: function() {
                const fechaInicio = $('#minDate').val() || 'inicio';
                const fechaFin = $('#maxDate').val() || 'fin';
                return `Reporte_Recolecciones_${fechaInicio}_a_${fechaFin}`;
            }
        }
    ]
});
}


    // Función para recargar tabla
function reloadTable() {
    if (table) {
        table.ajax.reload(null, false);
    }
}

    // Eventos de filtros
$('#filterBtn').click(reloadTable);

$('#resetBtn').click(function() {
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    const lastDay = new Date();

    $('#minDate').val(firstDay.toISOString().split('T')[0]);
    $('#maxDate').val(lastDay.toISOString().split('T')[0]);
    filtroEstadoActual = 'todas';
    $('.filtro-rapido-btn').removeClass('active');
    $('.filtro-rapido-btn[data-estado="todas"]').addClass('active');
    reloadTable();
});

    // Filtros rápidos
$('.filtro-rapido-btn').click(function() {
    $('.filtro-rapido-btn').removeClass('active');
    $(this).addClass('active');
    filtroEstadoActual = $(this).data('estado');
    reloadTable();
});

// BOTÓN CSV SIMPLE - CORREGIDO
$('#excelBtn').on('click', function() {
    const fechaInicio = $('#minDate').val();
    const fechaFin = $('#maxDate').val();
    const zona = '<?= $zona_seleccionada ?>'; // Usar directamente la variable PHP
    const filtroEstado = filtroEstadoActual;
    const search = table.search();

    const url = `export_recolecciones_excel.php?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&zona=${zona}&filtroEstado=${filtroEstado}&search=${encodeURIComponent(search)}`;
    window.location.href = url;
});


$('#minDate, #maxDate').keypress(function(e) {
    if (e.which === 14) reloadTable();
});

    // Inicializar
initDataTable();
});
</script>