<?php
// almacenes_info.php
$zona_filtro = intval($zona_seleccionada ?? 0);
$filtro_zona_almacenes = $zona_filtro > 0 ? " AND a.zona = {$zona_filtro}" : '';
$filtro_zona_inventario = $zona_filtro > 0 ? " AND a.zona = {$zona_filtro}" : '';

// Obtener almacenes activos separados por bodega (direcciones)
$sql = "SELECT a.*, z.PLANTA as nombre_zona,
           d.id_direc as id_bodega,
           d.cod_al as cod_bodega,
           d.noma as nombre_bodega,
           COUNT(DISTINCT ib.id_prod) as total_productos,
           COALESCE(SUM(ib.total_kilos_disponible), 0) as total_kilos
    FROM almacenes a
    LEFT JOIN zonas z ON a.zona = z.id_zone
    LEFT JOIN direcciones d ON d.id_alma = a.id_alma AND d.status = 1
    LEFT JOIN inventario_bodega ib ON ib.id_bodega = d.id_direc AND ib.total_kilos_disponible > 0
    WHERE a.status = 1{$filtro_zona_almacenes}
    GROUP BY a.id_alma, d.id_direc
    ORDER BY a.zona, a.nombre, d.noma";
$result = $conn_mysql->query($sql);

?>

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header encabezado-col text-white d-flex align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-building me-2"></i>Almacenes
            </h5>

            <div class="ms-auto d-flex gap-2">

                <button type="button" class="btn btn-sm btn-orange"
                        data-bs-toggle="modal"
                        data-bs-target="#vincularFacturasModal" <?= $perm['ACT_FAC'];?>>
                    Buscar Facturas
                </button>
                <button type="button" class="btn btn-sm btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#vincularCRModal" <?= $perm['ACT_CR'];?>>
                    Buscar C.R
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Tabla de almacenes -->
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="tablaAlmacenes">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Bodega</th>
                            <th>Zona</th>
                            <th>Productos</th>
                            <th>Inventario Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contador = 1;
                        if ($result && $result->num_rows > 0) {
                            while ($almacen = mysqli_fetch_array($result)) {
                                // Calcular porcentaje de uso (ejemplo: si capacidad máxima es 100,000 kg)
                                $capacidad_maxima = 100000; // Puedes ajustar esto o agregar campo en BD
                                $porcentaje_uso = $capacidad_maxima > 0 ? 
                                    (floatval($almacen['total_kilos']) / $capacidad_maxima) * 100 : 0;
                                
                                // Determinar color de la barra según porcentaje
                                $color_barra = 'bg-success';
                                if ($porcentaje_uso > 70) $color_barra = 'bg-warning';
                                if ($porcentaje_uso > 90) $color_barra = 'bg-danger';
                                
                                // Formatear inventario
                                $total_kilos_formateado = floatval($almacen['total_kilos']) > 0 ? 
                                    number_format($almacen['total_kilos'], 2) . ' kg' : 
                                    '<span class="text-muted">Sin inventario</span>';

                                $bodega_texto = '<span class="text-muted">Sin bodega</span>';
                                if (!empty($almacen['id_bodega'])) {
                                    $cod_bodega = htmlspecialchars($almacen['cod_bodega'] ?? '');
                                    $nom_bodega = htmlspecialchars($almacen['nombre_bodega'] ?? '');
                                    $bodega_texto = trim($cod_bodega . ' - ' . $nom_bodega);
                                }

                                $url_detalle = '?p=V_detalle_almacen&id=' . intval($almacen['id_alma']);
                                if (!empty($almacen['id_bodega'])) {
                                    $url_detalle .= '&id_bodega=' . intval($almacen['id_bodega']);
                                }
                                
                                echo "<tr>
                                    <td>{$contador}</td>
                                    <td><strong>{$almacen['cod']}</strong></td>
                                    <td>{$almacen['nombre']}</td>
                                    <td>{$bodega_texto}</td>
                                    <td>{$almacen['nombre_zona']}</td>
                                    <td>
                                        <span class=\"badge bg-info\">{$almacen['total_productos']}</span>
                                    </td>
                                    <td>
                                        <div>{$total_kilos_formateado}</div>
                                    </td>
                                    <td>
                                        <span class=\"badge bg-success\">Activo</span>
                                    </td>
                                    <td>
                                        <a href=\"{$url_detalle}\" 
                                           class=\"btn btn-sm btn-primary\">
                                            <i class=\"bi bi-eye me-1\"></i> Ver Detalle
                                        </a>
                                    </td>
                                </tr>";
                                $contador++;
                            }
                        } else {
                            echo "<tr>
                                <td colspan=\"9\" class=\"text-center\">
                                    <div class=\"alert alert-info\">
                                        <i class=\"bi bi-info-circle me-2\"></i>
                                        No hay almacenes registrados
                                    </div>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal para vincular facturas -->

<!-- Modal -->
<div class="modal fade" id="vincularFacturasModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-orange text-white">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Buscar Facturas</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="resultadoFactura">
        <!-- Aquí se cargarán las facturas mediante AJAX -->
        <!-- Hay que crear una alerta de boostrap para advertir que la validacion de facturas buscara las facturas de la zona seleccionada en invoice -->
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle-fill me-2"></i>
          <div>
            La búsqueda de facturas se realizará en la zona seleccionada actualmente. Asegúrese de que la zona sea correcta antes de proceder.
          </div>    
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <input type="hidden" id="filtroZona" value="<?= htmlspecialchars($zona_seleccionada); ?>" class="form-control" placeholder="Filtrar por zona">
        <button type="button" class="btn btn-orange" onclick="cambiarZonaVenta()"><i class="bi bi-search me-2"></i>Buscar</button>
      </div>
    </div>
  </div>
</div>
<script>
    // enviar zona a b_factura_a.php al precionar el boton buscar facturas
    function cambiarZonaVenta() {
        var zonaId = $('#filtroZona').val();
        var spinner = '<div class="d-flex justify-content-center align-items-center" style="height:200px;">' +
                      '<div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>' +
                      '</div>';

        $.ajax({
            url: 'b_factura_a.php',
            type: 'POST',
            data: { zona: zonaId },
            beforeSend: function() {
                $('#resultadoFactura').html(spinner);
            },
            success: function(response) {
                $('#resultadoFactura').html(response);
            },
            error: function(xhr, status, error) {
                $('#resultadoFactura').html('<div class="alert alert-danger">Error al cargar facturas. Intente nuevamente.</div>');
                console.error('AJAX error:', status, error);
            }
        });
    }
</script>
<!-- Modal para vincular facturas -->
<!-- Modal para vincular C.R -->
 <div class="modal fade" id="vincularCRModal" tabindex="-1" aria-labelledby="exampleModalLabelCR" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h1 class="modal-title fs-5" id="exampleModalLabelCR">Buscar C.R</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body " id="resultadoCR">
        <!-- Aquí se cargarán los C.R mediante AJAX -->
        <!-- Hay que crear una alerta de boostrap para advertir que la validacion de C.R buscara los C.R de la zona seleccionada en invoice -->
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle-fill me-2"></i>
          <div>
            La búsqueda de C.R se realizará en la zona seleccionada actualmente. Asegúrese de que la zona sea correcta antes de proceder.
          </div>    
          </div>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <input type="hidden" id="filtroZonaCR" value="<?= htmlspecialchars($zona_seleccionada); ?>" class="form-control" placeholder="Filtrar por zona">
        <button type="button" class="btn btn-warning" onclick="cambiarZonaCR()">  <i class="bi bi-search me-2"></i  > Buscar</button>
      </div>
    </div>
  </div>
</div>
<script>
    // enviar zona a b_cr_a.php al precionar el boton buscar C.R
    function cambiarZonaCR() {
        var zonaId = $('#filtroZonaCR').val();
        var spinner = '<div class="d-flex justify-content-center align-items-center" style="height:200px;">' +
                      '<div class="spinner-grow text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>' +
                      '</div>';

        $.ajax({
            url: 'b_cr_a.php',
            type: 'POST',
            data: { zona: zonaId },
            beforeSend: function() {
                $('#resultadoCR').html(spinner);
            },
            success: function(response) {
                $('#resultadoCR').html(response);
            },
            error: function(xhr, status, error) {
                $('#resultadoCR').html('<div class="alert alert-danger">Error al cargar C.R. Intente nuevamente.</div>');
                console.error('AJAX error:', status, error);
            }
        });
    }
</script>
<!-- Modal para vincular C.R -->
<script>
function filtrarAlmacenes() {
    var filtroZona = $('#filtroZona').val().toLowerCase();
    var buscarAlmacen = $('#buscarAlmacen').val().toLowerCase();
    
    $('#tablaAlmacenes tbody tr').each(function() {
        var zona = $(this).find('td:nth-child(5)').text().toLowerCase();
        var almacen = $(this).find('td:nth-child(3)').text().toLowerCase();
        var bodega = $(this).find('td:nth-child(4)').text().toLowerCase();
        var codigo = $(this).find('td:nth-child(2)').text().toLowerCase();
        
        var mostrar = true;
        
        // Filtrar por zona
        if (filtroZona && zona.indexOf(filtroZona) === -1) {
            mostrar = false;
        }
        
        // Filtrar por búsqueda
        if (buscarAlmacen && 
            almacen.indexOf(buscarAlmacen) === -1 && 
            bodega.indexOf(buscarAlmacen) === -1 && 
            codigo.indexOf(buscarAlmacen) === -1) {
            mostrar = false;
        }
        
        if (mostrar) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Inicializar DataTables si está disponible
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tablaAlmacenes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
            },
            pageLength: 25,
            order: [[3, 'asc'], [2, 'asc']]
        });
    }
});
</script>