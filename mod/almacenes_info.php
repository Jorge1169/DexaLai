<?php
// Obtener todos los almacenes activos
$sql = "SELECT a.*, z.PLANTA as nombre_zona,
               COUNT(DISTINCT ib.id_prod) as total_productos,
               SUM(ib.total_kilos_disponible) as total_kilos
        FROM almacenes a
        LEFT JOIN zonas z ON a.zona = z.id_zone
        LEFT JOIN inventario_bodega ib ON a.id_alma = ib.id_alma AND ib.total_kilos_disponible > 0
        WHERE a.status = 1
        GROUP BY a.id_alma
        ORDER BY a.zona, a.nombre";
$result = $conn_mysql->query($sql);
?>

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Almacenes</h5>
            <a href="?p=captacion" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Regresar
            </a>
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
                                    ($almacen['total_kilos'] / $capacidad_maxima) * 100 : 0;
                                
                                // Determinar color de la barra según porcentaje
                                $color_barra = 'bg-success';
                                if ($porcentaje_uso > 70) $color_barra = 'bg-warning';
                                if ($porcentaje_uso > 90) $color_barra = 'bg-danger';
                                
                                // Formatear inventario
                                $total_kilos_formateado = $almacen['total_kilos'] > 0 ? 
                                    number_format($almacen['total_kilos'], 2) . ' kg' : 
                                    '<span class="text-muted">Sin inventario</span>';
                                
                                echo "<tr>
                                    <td>{$contador}</td>
                                    <td><strong>{$almacen['cod']}</strong></td>
                                    <td>{$almacen['nombre']}</td>
                                    <td>{$almacen['nombre_zona']}</td>
                                    <td>
                                        <span class=\"badge bg-info\">{$almacen['total_productos']}</span>
                                    </td>
                                    <td>
                                        <div>{$total_kilos_formateado}</div>
                                        <div class=\"progress\" style=\"height: 5px;\">
                                            <div class=\"progress-bar {$color_barra}\" 
                                                 role=\"progressbar\" 
                                                 style=\"width: {$porcentaje_uso}%\"
                                                 aria-valuenow=\"{$porcentaje_uso}\" 
                                                 aria-valuemin=\"0\" 
                                                 aria-valuemax=\"100\">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class=\"badge bg-success\">Activo</span>
                                    </td>
                                    <td>
                                        <a href=\"?p=V_detalle_almacen&id={$almacen['id_alma']}\" 
                                           class=\"btn btn-sm btn-primary\">
                                            <i class=\"bi bi-eye me-1\"></i> Ver Detalle
                                        </a>
                                    </td>
                                </tr>";
                                $contador++;
                            }
                        } else {
                            echo "<tr>
                                <td colspan=\"8\" class=\"text-center\">
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
            
            <!-- Resumen -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">Total Almacenes</h5>
                            <h3 class="text-primary"><?= $contador - 1 ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">Con Inventario</h5>
                            <h3 class="text-success">
                                <?php
                                $con_inventario = $conn_mysql->query("
                                    SELECT COUNT(DISTINCT id_alma) as total 
                                    FROM inventario_bodega 
                                    WHERE total_kilos_disponible > 0
                                ")->fetch_assoc()['total'];
                                echo $con_inventario;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">Sin Inventario</h5>
                            <h3 class="text-warning"><?= ($contador - 1) - $con_inventario ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted">Kilos Totales</h5>
                            <h3 class="text-info">
                                <?php
                                $total_kilos = $conn_mysql->query("
                                    SELECT SUM(total_kilos_disponible) as total 
                                    FROM inventario_bodega
                                ")->fetch_assoc()['total'];
                                echo number_format($total_kilos, 2) . ' kg';
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filtrarAlmacenes() {
    var filtroZona = $('#filtroZona').val().toLowerCase();
    var buscarAlmacen = $('#buscarAlmacen').val().toLowerCase();
    
    $('#tablaAlmacenes tbody tr').each(function() {
        var zona = $(this).find('td:nth-child(4)').text().toLowerCase();
        var almacen = $(this).find('td:nth-child(3)').text().toLowerCase();
        var codigo = $(this).find('td:nth-child(2)').text().toLowerCase();
        
        var mostrar = true;
        
        // Filtrar por zona
        if (filtroZona && zona.indexOf(filtroZona) === -1) {
            mostrar = false;
        }
        
        // Filtrar por búsqueda
        if (buscarAlmacen && 
            almacen.indexOf(buscarAlmacen) === -1 && 
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