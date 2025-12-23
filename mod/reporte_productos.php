<div class="container mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Reporte de productos con movimientos</h5>
        <div class="card-body">
            <div class="mb-3">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="miTabla" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th data-priority="1">Código</th>
                                <th>Nombre</th>
                                <th>Línea</th>
                                <th>Zona</th>
                                <th>Total Entradas</th>
                                <th>Total Salidas</th>
                                <th>Stock Actual</th>
                                <th>Fecha de alta</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $Contador = 0;
                            
                            // Consulta modificada para incluir zonas
                            if ($zona_seleccionada == '0') {
                                $query = "SELECT p.*, z.nom AS nom_zone FROM productos p 
                                         LEFT JOIN zonas z ON p.zona = z.id_zone";
                            } else {
                                $query = "SELECT p.*, z.nom AS nom_zone FROM productos p 
                                         LEFT JOIN zonas z ON p.zona = z.id_zone
                                         WHERE p.zona = '$zona_seleccionada'";
                            }
                            
                            $Prod00 = $conn_mysql->query($query);
                            
                            while ($Prod01 = mysqli_fetch_array($Prod00)) {
                                $Contador++;
                                $fecha_alta = date('Y-m-d', strtotime($Prod01['fecha']));
                                
                                // Consulta para calcular entradas (compras)
                                $entradas_query = $conn_mysql->query("SELECT SUM(a.entrada) as total_entradas 
                                    FROM almacen a
                                    LEFT JOIN compras c ON a.id_compra = c.id_compra
                                    WHERE a.id_prod = '".$Prod01['id_prod']."' 
                                    AND a.entrada > 0
                                    ".($zona_seleccionada != '0' ? " AND c.zona = '$zona_seleccionada'" : ""));
                                $entradas_data = mysqli_fetch_array($entradas_query);
                                $total_entradas = $entradas_data['total_entradas'] ?? 0;
                                
                                // Consulta para calcular salidas (ventas)
                                $salidas_query = $conn_mysql->query("SELECT SUM(a.salida) as total_salidas 
                                    FROM almacen a
                                    LEFT JOIN ventas v ON a.id_venta = v.id_venta
                                    WHERE a.id_prod = '".$Prod01['id_prod']."' 
                                    AND a.salida > 0
                                    ".($zona_seleccionada != '0' ? " AND v.zona = '$zona_seleccionada'" : ""));
                                $salidas_data = mysqli_fetch_array($salidas_query);
                                $total_salidas = $salidas_data['total_salidas'] ?? 0;
                                
                                $stock_actual = $total_entradas - $total_salidas;
                                ?>
                                <tr>
                                    <td class="text-center"><?= $Contador ?></td>
                                    <td><?= htmlspecialchars($Prod01['cod']) ?></td>
                                    <td><?= htmlspecialchars($Prod01['nom_pro']) ?></td>
                                    <td><?= htmlspecialchars($Prod01['lin']) ?></td>
                                    <td><?= htmlspecialchars($Prod01['nom_zone']) ?></td>
                                    <td class="text-success"><b><?= number_format($total_entradas, 2) ?> kg</b></td>
                                    <td class="text-danger"><b><?= number_format($total_salidas, 2) ?> kg</b></td>
                                    <td class="<?= $stock_actual < 0 ? 'text-danger' : 'text-primary' ?>">
                                        <b><?= number_format($stock_actual, 2) ?> kg</b>
                                    </td>
                                    <td><?= $fecha_alta ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($Prod01['status'] ?? 0) == 1 ? 'success' : 'danger' ?>">
                                            <?= ($Prod01['status'] ?? 0) == 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#miTabla').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
        },
        "responsive": true
    });
});
</script>