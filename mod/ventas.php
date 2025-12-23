<?php
if (isset($_POST['Actualizar'])) { /// autoriza la venta
    $ID_venta = $_POST['ventaSt'] ?? '';
    $ID_compr = $_POST['comprSt'] ?? '';
    $conn_mysql->query("UPDATE ventas SET acciones = '1' WHERE id_venta = '$ID_venta'");
    $conn_mysql->query("UPDATE compras SET acciones = '1' WHERE id_compra = '$ID_compr'");
    alert("Venta autorizada", 1, "ventas");
}
?>
<div class="container-fluid mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Ventas</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Clien_Crear'];?> href="?p=N_venta" class="btn btn-primary btn-sm rounded-3 mt-1" target="_blank">
                    <i class="bi bi-plus"></i> Nueva Venta
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3 mt-1" onclick="toggleInactiveVentas()">
                    <i class="bi bi-eye"></i> Mostrar Inactivas
                </button>
                <button type="button" class="btn btn-teal btn-sm rounded-3 mt-1" data-bs-toggle="modal" data-bs-target="#ActualizarF"  <?= $perm['ACT_FAC'];?>>
                  <i class="bi bi-file-earmark-break-fill"></i> Actualizar facturas
              </button>
              <button type="button" class="btn btn-purple btn-sm rounded-3 mt-1" data-bs-toggle="modal" data-bs-target="#ActualizarR" <?= $perm['ACT_CR'];?>>
                  <i class="bi bi-file-earmark-medical-fill"></i> Buscar Contra R.
              </button>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <!-- Fecha inicial -->
            <label for="minDate">Desde:</label>
            <input type="date" id="minDate" max="<?= date('Y-m-d') ?>" class="form-control form-control-sm" style="width: 160px;">

            <!-- Fecha final -->
            <label for="maxDate">Hasta:</label>
            <input type="date" id="maxDate" max="<?= date('Y-m-d') ?>" class="form-control form-control-sm" style="width: 160px;">

            <!-- Botones -->
            <button id="filterBtn" class="btn btn-sm rounded-3 btn-primary px-3"><i class="bi bi-funnel"></i> Filtrar</button>
            <!-- Nuevos botones de filtro para Docs y Contra -->
            <!-- Menú desplegable para Docs -->
            <div class="dropdown">
                <button class="btn btn-sm btn-info dropdown-toggle rounded-3 btn-primary" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-files"></i>Evidencias Docs
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-docs-btn" href="#" data-filter="all-docs">Sin filtro Docs</a></li>
                    <li><a class="dropdown-item filter-docs-btn" href="#" data-filter="with-docs">Con Documentos</a></li>
                    <li><a class="dropdown-item filter-docs-btn" href="#" data-filter="without-docs">Sin Documentos</a></li>
                </ul>
            </div>

            <!-- Menú desplegable para Contra -->
            <div class="dropdown">
                <button class="btn btn-sm btn-purple dropdown-toggle rounded-3 btn-primary" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-receipt"></i> Contra R
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-contra-btn" href="#" data-filter="all-contra">Sin filtro</a></li>
                    <li><a class="dropdown-item filter-contra-btn" href="#" data-filter="with-contra">Con Contra</a></li>
                    <li><a class="dropdown-item filter-contra-btn" href="#" data-filter="without-contra">Sin Contra</a></li>
                </ul>
            </div>
            <button id="resetBtn" class="btn btn-sm rounded-3 btn-secondary px-3"><i class="bi bi-arrow-clockwise"></i> Resetear</button>


        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm" id="tablaVentas" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Acciones</th>
                        <th data-priority="1">Remisión</th>
                        <th>Nombre</th>
                        <th>Cliente</th>
                        <th>Compra</th>
                        <th>Producto</th>
                        <th>Fletero</th>
                        <th>Costo Flete</th>
                        <th>Peso Cliente</th>
                        <th>Precio</th>
                        <th>Zona</th>
                        <th>Factura</th>
                        <th>Docs</th>
                        <th>Contra</th>
                        <th>Fecha</th>
                        <th>Status</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se cargarán via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<!-- Modal de contra recibo -->
<div class="modal fade" id="ActualizarR" data-bs-backdrop="static" tabindex="-1" aria-labelledby="contraReciboModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-purple">
        <h1 class="modal-title fs-5 text-white" id="contraReciboModalLabel">Gestión de Contra Recibos</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <!-- Información del proceso -->
        <div class="alert alert-info mb-4">
          <div class="d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-2 fs-4"></i>
            <div>
              <strong>Proceso de actualización</strong>
              <p class="mb-0">El sistema buscará contra recibos para las facturas existentes que aún no los tienen registrados.</p>
          </div>
      </div>
  </div>

  <?php
        // Consulta para obtener todos los clientes según zona seleccionada
  $ventascr = 0;
  $compracr = 0;
  $ContraContador = 0;
  $contadorComp = 0;

  if ($zona_seleccionada == '0') {
    $queryVCR = "SELECT v.*, z.nom AS nom_zone, v.folio_contra AS FolioDeContra 
    FROM ventas v 
    LEFT JOIN zonas z ON v.zona = z.id_zone
    WHERE v.status = '1' AND v.fact_fle IS NOT NULL";
    $queryCOM = "SELECT * FROM compras 
    WHERE status = '1' AND factura IS NOT NULL";
} else {
    $queryVCR = "SELECT v.*, z.nom AS nom_zone, v.folio_contra AS FolioDeContra 
    FROM ventas v 
    LEFT JOIN zonas z ON v.zona = z.id_zone
    WHERE v.zona = '$zona_seleccionada' 
    AND v.status = '1' 
    AND v.fact_fle IS NOT NULL";
    $queryCOM = "SELECT * FROM compras 
    WHERE status = '1' 
    AND factura IS NOT NULL 
    AND zona = '$zona_seleccionada'";
}

$resultCR = $conn_mysql->query($queryVCR);
$resultComp = $conn_mysql->query($queryCOM);

        // Contar facturas de compras
while ($CompraCon = mysqli_fetch_array($resultComp)) {
    $compracr++;
    if (empty($CompraCon['folio_contra'])) {
        $contadorComp++;
    }
}

        // Contar facturas de ventas
while ($VentFCR = mysqli_fetch_array($resultCR)) {
    $ventascr++;
    if (empty($VentFCR['FolioDeContra'])) {
        $ContraContador++;
    }
}

        // Calcular porcentajes para la barra de progreso
$porcentajeVentas = $ventascr > 0 ? ($ContraContador / $ventascr) * 100 : 0;
$porcentajeCompras = $compracr > 0 ? ($contadorComp / $compracr) * 100 : 0;
?>

<!-- Resumen de facturas -->
<div class="card mb-4">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-card-checklist me-2"></i>Resumen de Facturas</h6>
</div>
<div class="card-body">
    <!-- Facturas de fletes -->
    <div class="row mb-3">
      <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-muted">Facturas de flete</span>
          <span class="badge bg-primary"><?=$ventascr?> total</span>
      </div>
      <div class="d-flex justify-content-between">
          <small class="text-muted">Sin contra recibo:</small>
          <strong class="<?=($ContraContador > 0) ? 'text-danger' : 'text-success'?>">
            <?=number_format($ContraContador)?>
        </strong>
    </div>
</div>

<!-- Facturas de compras -->
<div class="col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="text-muted">Facturas de compra</span>
      <span class="badge bg-teal"><?=$compracr?> total</span>
  </div>
  <div class="d-flex justify-content-between">
      <small class="text-muted">Sin contra recibo:</small>
      <strong class="<?=($contadorComp > 0) ? 'text-danger' : 'text-success'?>">
        <?=number_format($contadorComp)?>
    </strong>
</div>
</div>
</div>

<!-- Resumen general -->
<div class="alert <?=(($ContraContador + $contadorComp) > 0) ? 'alert-warning' : 'alert-success'?> mt-3 mb-0 py-2">
  <div class="d-flex align-items-center">
    <i class="bi <?=(($ContraContador + $contadorComp) > 0) ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'?> me-2"></i>
    <div>
      <strong>
        <?=number_format($ContraContador + $contadorComp)?> facturas requieren contra recibo
    </strong>
    <span class="d-block small">
        De un total de <?=number_format($ventascr + $compracr)?> facturas en el sistema
    </span>
</div>
</div>
</div>
</div>
</div>

<!-- Resultados de búsqueda -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Resultados de la búsqueda</h6>
</div>
<div class="card-body p-0">
    <div id="AreFactCR" class="log-container" style="max-height: 300px; overflow-y: auto; padding: 1rem;">
      <div class="text-center text-muted py-4">
        <i class="bi bi-search fs-1 d-block mb-2"></i>
        <p>Los resultados de la búsqueda aparecerán aquí</p>
    </div>
</div>
</div>
</div>
</div>

<div class="modal-footer">
    <input type="hidden" name="zonCR" id="zonCR" value="<?=$zona_seleccionada?>">
    <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm" data-bs-dismiss="modal">
      <i class="bi bi-x-circle me-1"></i> Cerrar
  </button>
  <button type="button" class="btn btn-purple rounded-3 btn-sm" id="ActualizarCR" onclick="actualizarCR()" 
  <?=($ContraContador + $contadorComp == 0) ? 'disabled' : ''?>>
  <i class="bi bi-arrow-repeat me-1"></i> Buscar Contra Recibos
</button>
</div>
</div>
</div>
</div>

<script>
    function actualizarCR() {
        var zonCR = document.getElementById('zonCR').value;

    // Deshabilitar botón para evitar múltiples clics
        $('#ActualizarCR').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Procesando...');

    // Limpiar área de resultados
        $('#AreFactCR').html(`
      <div class="text-center py-3">
        <div class="spinner-border text-purple" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2">Buscando contra recibos...</p>
      </div>
        `);

        var parametros = {
            "zonCR": zonCR,
        };

    // Contador para mostrar el progreso
        var procesados = 0;
        var totalFacturas = <?=($ContraContador + $contadorComp)?>;

        $.ajax({
            data: parametros,
            url: 'buscar_contra.php',
            type: 'POST',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                // Puedes implementar una barra de progreso aquí si lo deseas
                }, false);
                return xhr;
            },
            success: function(mensaje) {
                setTimeout(function() {
                    $('#AreFactCR').html(mensaje);

                // Habilitar botón nuevamente
                    $('#ActualizarCR').prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Buscar Nuevamente');

                // Scroll automático al final del contenedor
                    var container = document.getElementById('AreFactCR');
                    container.scrollTop = container.scrollHeight;
                }, 500);
            },
            error: function() {
                $('#AreFactCR').html(`
              <div class="alert alert-danger">
                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                Error al procesar la solicitud. Por favor intente nuevamente.
              </div>
                `);
                $('#ActualizarCR').prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Reintentar');
            }
        });
    }

// Recargar página al cerrar el modal
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = document.getElementById('ActualizarR');
        myModal.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>

<!-- Modal de facturas -->
<div class="modal fade" id="ActualizarF" data-bs-backdrop="static" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <div class="modal-header bg-teal text-white">
            <h1 class="modal-title fs-5">Actualización de Facturas</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="recargarPagina()"></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info mb-4">
              <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                <div>
                  <strong>Proceso de actualización</strong>
                  <p class="mb-0">Este proceso buscará y actualizará las facturas faltantes en la base de datos.</p>
              </div>
          </div>
      </div>

      <?php
        // Consulta para obtener todos los clientes según zona seleccionada
      $ventas = 0;
      $Factura = 0;
      $Factura_fel = 0;
      if ($zona_seleccionada == '0') {
          $queryV = "SELECT v.*, z.nom AS nom_zone, c.factura AS factura_compra FROM ventas v LEFT JOIN zonas z ON v.zona = z.id_zone LEFT JOIN compras c ON v.id_compra = c.id_compra WHERE v.status = '1'";
      } else {
          $queryV = "SELECT v.*, z.nom AS nom_zone, c.factura AS factura_compra FROM ventas v LEFT JOIN zonas z ON v.zona = z.id_zone LEFT JOIN compras c ON v.id_compra = c.id_compra WHERE v.zona = '$zona_seleccionada' AND v.status = '1'";
      }
      $result = $conn_mysql->query($queryV);
      while ($VentF = mysqli_fetch_array($result)) {
          $ventas++;
          $ContadorF = (empty($VentF['factura_compra'])) ? 1 : 0 ;
          $ContadorF_fle = (empty($VentF['fact_fle'])) ? 1 : 0 ;
          $Factura = $Factura + $ContadorF; 
          $Factura_fel = $Factura_fel + $ContadorF_fle; 
      }
      ?>

      <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-card-checklist me-2"></i>Resumen de Facturas</h6>
        </div>
        <div class="card-body p-0">
            <div class="row g-0 text-center">
              <div class="col-md-4 p-3 border-end">
                <div class="d-flex flex-column align-items-center">
                  <span class="text-muted mb-1">Ventas Totales</span>
                  <h3 class="text-primary mb-0"><?=number_format($ventas)?></h3>
              </div>
          </div>
          <div class="col-md-4 p-3 border-end">
            <div class="d-flex flex-column align-items-center">
              <span class="text-muted mb-1">Compras sin factura</span>
              <h3 class="text-teal mb-0"><?=number_format($Factura)?></h3>
          </div>
      </div>
      <div class="col-md-4 p-3">
        <div class="d-flex flex-column align-items-center">
          <span class="text-muted mb-1">Fletes sin factura</span>
          <h3 class="text-success mb-0"><?=number_format($Factura_fel)?></h3>
      </div>
  </div>
</div>
</div>
</div>

<div class="card">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Resultados de la búsqueda</h6>
</div>
<div class="card-body p-0">
    <div id="AreFact" class="log-container" style="max-height: 300px; overflow-y: auto; padding: 1rem;">
      <!-- Aquí aparecerán los resultados de la búsqueda -->
  </div>
</div>
</div>
</div>
<div class="modal-footer">
    <input type="hidden" name="zon" id="zon" value="<?=$zona_seleccionada?>">
    <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm" data-bs-dismiss="modal" onclick="recargarPagina()">
      <i class="bi bi-x-circle me-1"></i> Cerrar
  </button>
  <button type="button" class="btn btn-teal rounded-3 btn-sm" id="Actualizar" onclick="actualizar()">
      <i class="bi bi-arrow-repeat me-1"></i> Iniciar Actualización
  </button>
</div>
</div>
</div>
</div>
<script>
    function actualizar() {
        var zon = document.getElementById('zon').value;

    // Deshabilitar botón para evitar múltiples clics
        $('#Actualizar').prop('disabled', true).html('<i class="bi bi-arrow-repeat me-1"></i> Procesando...');


    // Limpiar área de resultados
        $('#AreFact').html(`
        <div class="text-center py-3">
            <div class="spinner-border text-teal" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Preparando búsqueda de facturas...</p>
        </div>
        `);

        var parametros = {
            "zon": zon,
        };

        $.ajax({
            data: parametros,
            url: 'buscar_facturas.php',
            type: 'POST',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                }, false);
                return xhr;
            },
            success: function(mensaje) {
                setTimeout(function() {
                    $('#AreFact').html(mensaje);

                // Habilitar botón nuevamente
                    $('#Actualizar').prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Iniciar Actualización');

                // Scroll automático al final del contenedor
                    var container = document.getElementById('AreFact');
                    container.scrollTop = container.scrollHeight;
                }, 500);
            },
            error: function() {
                $('#AreFact').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i>
                    Error al procesar la solicitud. Por favor intente nuevamente.
                </div>
                `);
                $('#Actualizar').prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Iniciar Actualización');
            }
        });
    }

// Recargar página al cerrar el modal
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = document.getElementById('ActualizarF');

        myModal.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>
<!-- Modal de confirmación para ventas -->
<div class="modal fade" id="confirmVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalVentaMessage">¿Estás seguro de que deseas realizar esta acción?</p>
                <input type="hidden" id="ventaId">
                <input type="hidden" id="ventaAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmVentaBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        let showingInactivesVentas = false;
        let currentStatusFilter = '1';
        let currentDocsFilter = 'all-docs';
        let currentContraFilter = 'all-contra';

        const table = $('#tablaVentas').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "ajax": {
                "url": "ajax_ventas.php",
                "type": "POST",
                "data": function(d) {
                    d.minDate = $('#minDate').val();
                    d.maxDate = $('#maxDate').val();
                    d.zona = '<?= $zona_seleccionada ?>';
                    d.status = currentStatusFilter;
                    d.docsFilter = currentDocsFilter;
                    d.contraFilter = currentContraFilter;

                    console.log('Enviando parámetros:', {
                        minDate: d.minDate,
                        maxDate: d.maxDate,
                        status: d.status,
                        docsFilter: d.docsFilter,
                        contraFilter: d.contraFilter
                    });

                    return d;
                }
            },
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "columns": [
                { 
                    "data": null, 
                    "render": function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    "orderable": false 
                },
                { 
                    "data": "id_venta", 
                    "render": function(data, type, row) {
                        return renderAcciones(row);
                    },
                    "orderable": false
                },
                { 
                    "data": "fact",
                    "render": function(data, type, row) {
                        return data + (row.ex == 2 ? 
                            ' <i class="bi bi-filetype-svg text-teal" title="Cargado desde Excel"></i>' : '');
                    }
                },
                { 
                    "data": "nombre",
                    "render": function(data, type, row) {
                        return `<a class="link-primary" href="?p=V_venta&id=${row.id_venta}" target="_blank">${escapeHtml(data)}</a>`;
                    }
                },
                { "data": "cod_cliente" },
                { "data": "fact_compra" },
                { "data": "cod_producto" },
                { "data": "id_transportista" },
                { 
                    "data": "costo_flete",
                    "render": function(data) {
                        return `$${parseFloat(data || 0).toFixed(2)}`;
                    }
                },
                { 
                    "data": "peso_cliente",
                    "render": function(data) {
                        return `${parseFloat(data || 0).toFixed(2)}kg`;
                    }
                },
                { 
                    "data": "precio",
                    "render": function(data) {
                        return `$${parseFloat(data || 0).toFixed(2)}`;
                    }
                },
                { "data": "nom_zone" },
                { 
                    "data": "factura_venta",
                    "render": function(data, type, row) {
                        if (data && row.pdf_exists) {
                            return `<a class="btn btn-sm btn-success rounded-3" href="${row.url}" target="_blank" title="Ver factura">
                    <i class="bi bi-file-earmark-pdf-fill"></i> ${data}
                        </a>`;
                    } else if (data && !row.pdf_exists) {
                        return `<button type="button" class="btn btn-sm btn-danger rounded-3" disabled>
                    <i class="bi bi-exclamation-triangle-fill"></i> ${data}
                    </button>`;
                } else {
                    return '<span class="text-danger">Sin factura</span>';
                }
            }
        },
        { 
            "data": null,
            "render": function(data, type, row) {
                return renderDocumentos(row);
            },
            "orderable": false
        },
        { 
            "data": null,
            "render": function(data, type, row) {
                return renderContraRecibos(row);
            },
                "orderable": false  // ← Deshabilitar ordenamiento
            },
            { 
                "data": "fecha",
                "render": function(data) {
                    return data ? data.split(' ')[0] : '';
                }
            },
            { 
                "data": "status",
                "render": function(data) {
                    const status = data == '1' ? 'Activo' : 'Inactivo';
                    const badgeClass = data == '1' ? 'bg-success' : 'bg-danger';
                    return `<span class="badge ${badgeClass}">${status}</span>`;
                },
                "visible": true
            },
            { 
                "data": "autorizar",
                "render": function(data, type, row) {
                    return renderAutorizacion(row);
                },
                "orderable": false
            }
        ],
        "createdRow": function(row, data, dataIndex) {
            $(`#AutorizarMd${data.id_venta}`).remove();
            $(document.body).append(renderModalAutorizacion(data));
        },
        "drawCallback": function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-bs-toggle="popover"]').popover();
            
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });
        },
        "order": [[15, 'desc']]
    });


    // Funciones de renderizado
function renderAcciones(row) {
    if (row.autorizar == 0) {
        if (row.status == '1') {
            return `
                    <div class="d-flex gap-2">
                        <a <?= $perm['Clien_Editar'] ?> href="?p=E_venta&id=${row.id_venta}" class="btn btn-info btn-sm rounded-3" title="Editar" target="_blank">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button <?= $perm['ACT_DES'] ?> class="btn btn-warning btn-sm rounded-3 desactivar-venta-btn" 
                            data-id="${row.id_venta}" title="Borrar / Desactivar">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
            `;
        } else {
            return `
                    <button class="btn btn-info btn-sm rounded-3 activar-venta-btn" 
                        data-id="${row.id_venta}" title="Activar venta">
                        <i class="bi bi-check-circle"></i> Activar
                    </button>
            `;
        }
    } else {
        return `
                <div class="d-flex gap-2">
                    <a <?= $perm['Clien_Editar'] ?> class="btn btn-secondary btn-sm rounded-3" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button <?= $perm['ACT_DES'] ?> class="btn btn-secondary btn-sm rounded-3">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
        `;
    }
}

function renderDocumentos(row) {
    const factura = row.factura_compra ? 
`<a href="<?= $invoiceLK ?>${row.documento_compra}.pdf" target="_blank" title="Factura proveedor"><i class="bi text-success bi-file-earmark-check-fill fs-6"></i></a>` :
`<i class="bi text-danger bi-file-earmark-excel-fill fs-6"></i>`;

const flete = row.fact_fle ? 
`<a href="<?= $invoiceLK ?>${row.documento_flete}.pdf" target="_blank" title="Factura flete"><i class="bi text-success bi-file-earmark-check-fill fs-6"></i></a>` :
`<i class="bi text-danger bi-file-earmark-excel-fill fs-6"></i>`;

return `<div class="docs-container">${factura} ${flete}</div>`;
}

function renderContraRecibos(row) {
    const contraCompra = row.folio_contra_com ? 
`<span class="text-success" title="Compra ${row.Alias_Contra_com}-${row.folio_contra_com}"><i class="bi bi-check-circle-fill"></i> C:${row.folio_contra_com}</span>` :
`<span class="text-danger" title="Compra"><i class="bi bi-x-circle-fill"></i> C</span>`;

const contraFlete = row.folio_contra ? 
`<span class="text-success" title="Flete ${row.Alias_Contra}-${row.folio_contra}"><i class="bi bi-check-circle-fill"></i> F:${row.folio_contra}</span>` :
`<span class="text-danger" title="Flete"><i class="bi bi-x-circle-fill"></i> F</span>`;

return `${contraCompra} ${contraFlete}`;
}

function renderAutorizacion(row) {
    if (row.autorizar == 0) {
        return `
                <button class="btn btn-orange btn-sm rounded-3" title="Autorizar venta" 
                    data-bs-toggle="modal" data-bs-target="#AutorizarMd${row.id_venta}" <?= $perm['ACT_AC'] ?>>
                    <i class="bi bi-arrow-repeat"></i>
                </button>
        `;
    } else {
        return `
                <span class="text-teal fw-bold bg-teal bg-opacity-25 rounded-3 px-1">
                    <i class="bi bi-check-circle"></i> Autorizada
                </span>
        `;
    }
}

function renderModalAutorizacion(row) {
    return `
            <div class="modal fade" id="AutorizarMd${row.id_venta}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-orange text-white">
                            <h5 class="modal-title">Estado de la venta</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Autorizar la venta con remisión: <b>${row.fact}</b>
                            <hr>
                            Documentos
                            <div class="card">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Factura de compra
                                        ${row.factura_compra ? 
                                            `<span class="badge text-bg-success rounded-pill">${row.factura_compra}</span>` : 
                                            `<span class="badge text-bg-danger rounded-pill">X</span>`}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Factura de venta
                                        ${row.factura_venta ? 
                                            `<span class="badge text-bg-success rounded-pill">${row.factura_venta}</span>` : 
                                            `<span class="badge text-bg-danger rounded-pill">X</span>`}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Factura de flete
                                        ${row.factura_fletero ? 
                                            `<span class="badge text-bg-success rounded-pill">${row.factura_fletero}</span>` : 
                                            `<span class="badge text-bg-danger rounded-pill">X</span>`}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Contra Recibo del flete
                                        ${row.folio_contra ? 
                                            `<span class="badge text-bg-success rounded-pill">${row.Alias_Contra}-${row.folio_contra}</span>` : 
                                            `<span class="badge text-bg-danger rounded-pill">X</span>`}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Contra Recibo de la compra
                                        ${row.folio_contra_com ? 
                                            `<span class="badge text-bg-success rounded-pill">${row.Alias_Contra_com}-${row.folio_contra_com}</span>` : 
                                            `<span class="badge text-bg-danger rounded-pill">X</span>`}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <form method="post" action="">
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <input type="hidden" name="ventaSt" value="${row.id_venta}">
                                <input type="hidden" name="comprSt" value="${row.id_compraV}">
                                <button type="submit" name="Actualizar" class="btn btn-orange">Autorizar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                                        `;
                                    }

    // Utilidad para escape HTML
                                    function escapeHtml(text) {
                                        const map = {
                                            '&': '&amp;',
                                            '<': '&lt;',
                                            '>': '&gt;',
                                            '"': '&quot;',
                                            "'": '&#039;'
                                        };
                                        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
                                    }


                                    window.toggleInactiveVentas = function() {
                                        const btn = $('button[onclick="toggleInactiveVentas()"]');

                                        if (showingInactivesVentas) {
                                            currentStatusFilter = '1';
                                            btn.html('<i class="bi bi-eye"></i> Mostrar Inactivas');
                                            btn.removeClass('btn-info').addClass('btn-secondary');
                                        } else {
                                            currentStatusFilter = '0';
                                            btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivas');
                                            btn.removeClass('btn-secondary').addClass('btn-info');
                                        }

                                        showingInactivesVentas = !showingInactivesVentas;
                                        table.ajax.reload();
                                    };
 // Funciones para los filtros de dropdowns
                                    function aplicarFiltroDocs(filtro) {
                                        currentDocsFilter = filtro;

                                        let texto = '';
                                        switch(filtro) {
                                        case 'with-docs': texto = 'Con Documentos'; break;
                                        case 'without-docs': texto = 'Sin Documentos'; break;
                                        default: texto = 'Sin filtro Docs';
                                        }
                                        $('.dropdown:has(.filter-docs-btn) .dropdown-toggle').html(`<i class="bi bi-files"></i> ${texto}`);

                                        table.ajax.reload();
                                    }

                                    function aplicarFiltroContra(filtro) {
                                        currentContraFilter = filtro;

                                        let texto = '';
                                        switch(filtro) {
                                        case 'with-contra': texto = 'Con Contra'; break;
                                        case 'without-contra': texto = 'Sin Contra'; break;
                                        default: texto = 'Sin filtro';
                                        }
                                        $('.dropdown:has(.filter-contra-btn) .dropdown-toggle').html(`<i class="bi bi-receipt"></i> ${texto}`);

                                        table.ajax.reload();
                                    }

  // Event listeners para dropdowns
                                    $(document).on('click', '.dropdown-item.filter-docs-btn', function(e) {
                                        e.preventDefault();
                                        aplicarFiltroDocs($(this).data('filter'));
                                    });

                                    $(document).on('click', '.dropdown-item.filter-contra-btn', function(e) {
                                        e.preventDefault();
                                        aplicarFiltroContra($(this).data('filter'));
                                    });
  // ¡ESTOS SON LOS EVENT LISTENERS QUE FALTABAN!
                                    $('#filterBtn').click(function() {
                                        console.log('Aplicando filtros de fecha...');
                                        table.ajax.reload();
                                    });

                                    $('#resetBtn').click(function() {
                                        $('#minDate').val('');
                                        $('#maxDate').val('');
                                        currentDocsFilter = 'all-docs';
                                        currentContraFilter = 'all-contra';
                                        currentStatusFilter = '1';

        // Resetear textos de dropdowns
                                        $('.dropdown:has(.filter-docs-btn) .dropdown-toggle').html('<i class="bi bi-files"></i> Sin filtro Docs');
                                        $('.dropdown:has(.filter-contra-btn) .dropdown-toggle').html('<i class="bi bi-receipt"></i> Sin filtro');

        // Resetear botón de inactivas
                                        $('button[onclick="toggleInactiveVentas()"]')
                                        .html('<i class="bi bi-eye"></i> Mostrar Inactivas')
                                        .removeClass('btn-info')
                                        .addClass('btn-secondary');

                                        showingInactivesVentas = false;

                                        console.log('Reseteando todos los filtros...');
                                        table.ajax.reload();
                                    });

                                    $('#resetBtn').click(function() {
                                        $('#minDate').val('');
                                        $('#maxDate').val('');
                                        currentDocsFilter = 'all-docs';
                                        currentContraFilter = 'all-contra';
                                        $('.filter-docs-btn, .filter-contra-btn').removeClass('active');
                                        $('.filter-docs-btn[data-filter="all-docs"]').addClass('active');
                                        $('.filter-contra-btn[data-filter="all-contra"]').addClass('active');
                                        table.ajax.reload();
                                    });

                                    $(document).on('click', '.filter-docs-btn', function() {
                                        aplicarFiltroDocs($(this).data('filter'));
                                    });

                                    $(document).on('click', '.filter-contra-btn', function() {
                                        aplicarFiltroContra($(this).data('filter'));
                                    });

    // Inicializar botones activos
                                    $('.filter-docs-btn[data-filter="all-docs"]').addClass('active');
                                    $('.filter-contra-btn[data-filter="all-contra"]').addClass('active');

    // Delegación de eventos para activar/desactivar
                                    $(document).on('click', '.desactivar-venta-btn, .activar-venta-btn', function() {
        // ... [código existente]
                                    });

                                    $('#confirmVentaBtn').click(function() {
        // ... [código existente]
                                    });
    // Delegación de eventos
                                    $(document).on('click', '.desactivar-venta-btn', function() {
                                        const id = $(this).data('id');
                                        $('#ventaId').val(id);
                                        $('#ventaAccion').val('desactivar');
                                        $('#modalVentaMessage').text('¿Estás seguro de que deseas desactivar esta venta?');
                                        $('#confirmVentaModal').modal('show');
                                    });

                                    $(document).on('click', '.activar-venta-btn', function() {
                                        const id = $(this).data('id');
                                        $('#ventaId').val(id);
                                        $('#ventaAccion').val('activar');
                                        $('#modalVentaMessage').text('¿Estás seguro de que deseas reactivar esta venta?');
                                        $('#confirmVentaModal').modal('show');
                                    });

                                    $('#confirmVentaBtn').click(function() {
                                        const id = $('#ventaId').val();
                                        const accion = $('#ventaAccion').val();

                                        $.post('actualizar_status_ven.php', {
                                            id: id,
                                            accion: accion,
                                            tabla: 'ventas'
                                        }, function(response) {
                                            if (response.success) {
                                                table.ajax.reload();
                                                $('#confirmVentaModal').modal('hide');
                                            } else {
                                                alert('Error: ' + response.message);
                                            }
                                        }, 'json');
                                    });
                                });
                            </script>