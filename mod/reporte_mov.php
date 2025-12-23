<style>
    /* Estilo para reducir el tamaño de fuente */
    .small-font {
        font-size: 0.8rem; /* Puedes ajustar este valor (0.7rem, 0.75rem, etc.) */
    }
    
    /* Opcional: ajustar el tamaño del encabezado */
    .small-font thead th {
        font-size: 0.8rem;
    }
    .small-font td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100px; /* Ajusta según necesidad */
        font-size: 0.9rem;
    }
    
    /* Opcional: asegurar que DataTable respete el tamaño */
    #miTabla.dataTable {
        font-size: inherit;
    }
    /* Estilos previos... */
    
    /* Nuevos estilos para el resumen */
    .summary-card {
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .summary-title {
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .summary-value {
        font-size: 1.2rem;
        font-weight: bold;
    }
    
    .summary-compras {
        border-left: 4px solid #2196f3;
    }
    
    .summary-ventas {

        border-left: 4px solid #a370f7;
    }
    
    .summary-utilidad {
        border-left: 4px solid #4caf50;
    }
    .summary-producto {

        border-left: 4px solid #008080;
    }
    
    .summary-container {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .summary-card {
        flex: 1;
        min-width: 200px;
    }

</style>
<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Reporte de movimientos</h5>
        <div class="card-body">
            <div class="summary-container">
                <div class="summary-card summary-producto bg-teal bg-opacity-25" data-bs-toggle="modal" data-bs-target="#productosVendidosModal" title="Ver Cantidad por producto">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="summary-title">Cantidad de producto vendido</div>
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="summary-value" id="total-cantidad">0.00</div>
                </div>
                <div class="summary-card summary-compras bg-primary bg-opacity-25">
                    <div class="row">
                        <div class="col-12 col-md-6"> 
                            <div class="summary-title">Total Compras</div>
                            <div class="summary-value" id="total-compras">$0.00</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-between border-bottom border-primary py-2">
                                <span class="text-muted">Compra</span>
                                <strong id="total-importe">$0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom border-primary py-2">
                                <span class="text-muted">Flete</span>
                                <strong id="total-flete">$0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="summary-card summary-ventas bg-purple bg-opacity-25 cursor-pointer">

                    <div class="summary-title">Total Ventas</div>
                    <div class="summary-value" id="total-ventas">$0.00</div>
                </div>
                <div class="summary-card summary-utilidad bg-success bg-opacity-25">
                    <div class="summary-title">Utilidad Estimada</div>
                    <div class="summary-value" id="total-utilidad">$0.00</div>
                </div>
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
                <button id="resetBtn" class="btn btn-sm rounded-3 btn-secondary px-3"><i class="bi bi-arrow-clockwise"></i> Resetear</button>
                <button id="exportCsvBtn" class="btn btn-sm rounded-3 btn-teal px-3">
                    <i class="bi bi-file-earmark-text"></i> Exportar CSV
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover table-sm small-font" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ZONA</th>
                            <th>FECHA COM</th>
                            <th data-priority="1">REMISION</th>
                            <th>FACTURA</th>
                            <th>PROVEEDOR</th>
                            <th>PRODUCTO</th>
                            <th>CANTIDAD</th>
                            <th>PRECIO</th>
                            <th>IMPORTE</th>
                            <th>FLETERO</th>
                            <th>FLETE</th>
                            <th>FAC. FL</th>
                            <th>TOTAL COM</th>
                            <th>FECHA VEN</th>
                            <th>REMISION</th>
                            <th>FACTURA</th>
                            <th>CLIENTE</th>
                            <th>PRODUCTO</th>
                            <th>CANTIDAD</th>
                            <th>PRECIO</th>
                            <th>TOTAL VEN</th>
                            <th>UE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $contador = 0;

                            // Consulta modificada para incluir zonas
                        if ($zona_seleccionada == '0') {
                            $query = "SELECT c.*, z.nom AS nom_zone, t.placas AS id_fletero
                            FROM compras c 
                            LEFT JOIN zonas z ON c.zona = z.id_zone
                            LEFT JOIN transportes t ON c.id_transp = t.id_transp 
                            WHERE c.status = '1'";
                        } else {
                            $query = "SELECT c.*, z.nom AS nom_zone, t.placas AS id_fletero
                            FROM compras c 
                            LEFT JOIN zonas z ON c.zona = z.id_zone
                            LEFT JOIN transportes t ON c.id_transp = t.id_transp 
                            WHERE c.status = '1' AND c.zona = '$zona_seleccionada'";
                        }

                        $Compr0 = $conn_mysql->query($query);

                        while ($Compr1 = mysqli_fetch_array($Compr0)) {
                            $contador++;
                            $fecha_alta = date('Y-m-d', strtotime($Compr1['fecha']));

                            $direc0 = $conn_mysql->query("SELECT * FROM direcciones WHERE id_direc = '".$Compr1['id_direc']."'");
                                $direc1 = mysqli_fetch_array($direc0);// direccion de proveedor

                                $prod0 = $conn_mysql->query("SELECT * FROM productos WHERE id_prod = '".$Compr1['id_prod']."'");
                                $prod1 = mysqli_fetch_array($prod0);// producto

                                $tot_com = ($Compr1['pres'] * $Compr1['neto']);// total de la compra

                                $venta0 = $conn_mysql->query("SELECT * FROM ventas WHERE status = '1' AND id_compra = '".$Compr1['id_compra']."'");
                                $venta1 = mysqli_fetch_array($venta0);
                                $ID_V = $venta1['id_venta'] ?? '0';
                                $flete = $venta1['costo_flete'] ?? '0.00';//flete
                                $f_fle = $venta1['fact_fle'] ?? '';
                                $totalC = ($tot_com + $flete);
                                $remi_v = $venta1['fact'] ?? '';
                                $factur_v = $venta1['factura'] ?? '';
                                $id_venta = $venta1['id_cli'] ?? '0';
                                $cli0 = $conn_mysql->query("SELECT * FROM clientes WHERE id_cli = '$id_venta'"); // cliente
                                $cli1 = mysqli_fetch_array($cli0);
                                $rs_c = $cli1['rs'] ?? '';
                                $id_proV = $venta1['id_prod'] ?? '0';
                                $proV0 = $conn_mysql->query("SELECT * FROM productos WHERE id_prod = '$id_proV'");// producto de venta
                                $proV1 = mysqli_fetch_array($proV0);
                                $n_proV = $proV1['nom_pro'] ?? '';
                                $CantV = $venta1['peso_cliente'] ?? '0';
                                $CostoV = $venta1['precio'] ?? '0';
                                $totV = $CantV * $CostoV;
                                $tot_m = $totV - $totalC;
                                $eu = ($totV == 0) ? '0.00' : $tot_m ;

                                $Fecha_v = (!empty($venta1['fecha'])) ? date('Y-m-d', strtotime($venta1['fecha'])) : '' ;
                                ?>
                                <tr>
                                    <td class="text-center"><?=$contador?></td>
                                    <td class="bg-warning bg-opacity-10 border-warning border-end-0"><?= $Compr1['nom_zone']?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?=$fecha_alta?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0">
                                        <a href="?p=V_compra&id=<?=$Compr1['id_compra']?>" target="_blank" class="link-underline-primary">
                                            <?= $Compr1['fact']?>
                                        </a>
                                    </td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= $Compr1['factura']?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= $direc1['noma']?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= $prod1['nom_pro']?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= number_format($Compr1['neto'], 2,'.')?>kg</td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0">$<?= number_format($Compr1['pres'], 2,'.')?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0">$<?= number_format($tot_com, 2,'.')?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= $Compr1['id_fletero']?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0">$<?= number_format($flete, 2,'.')?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0"><?= $f_fle?></td>
                                    <td class="bg-primary bg-opacity-10 border-primary border-end-0">$<?= number_format($totalC, 2,'.')?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0"><?= $Fecha_v?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0">
                                        <a href="?p=V_venta&id=<?=$ID_V?>" target="_blank" class="link-underline-primary">
                                            <?= $remi_v?>
                                        </a>
                                    </td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0"><?= $factur_v?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0"><?= $rs_c?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0"><?= $n_proV?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0"><?= number_format($CantV, 2,'.')?>kg</td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0">$<?= number_format($CostoV, 2,'.')?></td>
                                    <td class="bg-purple bg-opacity-10 border-purple border-end-0">$<?= number_format($totV, 2,'.')?></td>
                                    <td class="bg-success bg-opacity-10 border-success border-end-0">$<?= number_format($eu, 2,'.')?></td>
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
<!-- Modal para productos vendidos -->
<div class="modal fade" id="productosVendidosModal" tabindex="-1" aria-labelledby="productosVendidosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productosVendidosModalLabel">Productos Vendidos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered product-modal-table" id="tablaProductosVendidos">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Total Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se llenarán con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        var table = $('#miTabla').DataTable({
            "scrollX": true,
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "order": [[1, "desc"]],
            "footerCallback": function(row, data, start, end, display) {
                if (this.api().rows({search: 'applied'}).data().length > 0) {
                    updateSummary();
                    updateProductosVendidos();
                }
            }
        });
        
        // Configuración del botón CSV
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: '<i class="bi bi-file-earmark-text"></i> CSV',
                    title: 'Reporte_de_Movimientos',
                    className: 'btn-csv',
                    exportOptions: {
                        columns: ':visible',
                        modifier: {
                            search: 'applied'
                        }
                    }
                }
            ]
        });
        
        // Asociar el botón HTML al exportador CSV
        $('#exportCsvBtn').on('click', function() {
            table.button(0).trigger();
        });

        // Función para extraer valores numéricos de las celdas
        function extractNumber(value) {
            if (!value) return 0;
            
            // Eliminar caracteres no numéricos excepto el punto decimal
            var numericString = value.toString().replace(/[^\d.-]/g, '');
            
            // Convertir a número flotante
            var number = parseFloat(numericString);
            
            // Devolver 0 si no es un número válido
            return isNaN(number) ? 0 : number;
        }

        // Función para actualizar el resumen
        function updateSummary() {
            var totalCompras = 0;
            var totalVentas = 0;
            var totalUtilidad = 0;
            var totalimporte = 0;
            var totalflete = 0;
            var totalCantidad = 0;

            // Recorremos las filas visibles
            table.rows({search: 'applied'}).every(function() {
                var data = this.data();

                // Sumar cantidades (columna 7 - CANTIDAD)
                var cantidad = extractNumber(data[19]);
                if (!isNaN(cantidad)) totalCantidad += cantidad;

                // Sumar compras (columna 12 - TOTAL COM)
                var compra = extractNumber(data[13]);
                if (!isNaN(compra)) totalCompras += compra;

                // Sumar importe (columna 9 - IMPORTE)
                var importe = extractNumber(data[9]);
                if (!isNaN(importe)) totalimporte += importe;

                // Sumar flete (columna 10 - FLETE)
                var flete = extractNumber(data[11]);
                if (!isNaN(flete)) totalflete += flete;

                // Sumar ventas (columna 20 - TOTAL VEN)
                var venta = extractNumber(data[21]);
                if (!isNaN(venta)) totalVentas += venta;

                // Sumar utilidad (columna 21 - UE)
                var utilidad = extractNumber(data[22]);
                if (!isNaN(utilidad)) totalUtilidad += utilidad;
            });

            // Formatear y mostrar los totales
            $('#total-compras').text('$' + totalCompras.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total-importe').text('$' + totalimporte.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total-flete').text('$' + totalflete.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total-ventas').text('$' + totalVentas.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total-utilidad').text('$' + totalUtilidad.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#total-cantidad').text(totalCantidad.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Kg');
        }

        // Función para agrupar y mostrar productos vendidos
        function updateProductosVendidos() {
            var productos = {};
            
            // Recorremos las filas visibles
            table.rows({search: 'applied'}).every(function() {
                var data = this.data();
                
                // Obtener datos de producto vendido (columnas 17, 18, 20)
                var producto = data[18]; // Nombre del producto
                var cantidad = extractNumber(data[19]); // Cantidad vendida
                var totalVenta = extractNumber(data[21]); // Total de venta
                
                if (producto && producto.trim() !== '') {
                    if (!productos[producto]) {
                        productos[producto] = {
                            cantidad: 0,
                            total: 0
                        };
                    }
                    
                    productos[producto].cantidad += cantidad;
                    productos[producto].total += totalVenta;
                }
            });
            
            // Limpiar y llenar la tabla de productos vendidos
            var tablaBody = $('#tablaProductosVendidos tbody');
            tablaBody.empty();
            
            // Ordenar productos por total de ventas (descendente)
            var productosArray = Object.keys(productos).map(function(key) {
                return {
                    nombre: key,
                    cantidad: productos[key].cantidad,
                    total: productos[key].total
                };
            });
            
            productosArray.sort(function(a, b) {
                return b.total - a.total;
            });
            
            // Agregar filas a la tabla
            productosArray.forEach(function(producto) {
                var fila = $('<tr>');
                fila.append($('<td>').text(producto.nombre));
                fila.append($('<td>').text(producto.cantidad.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Kg'));
                fila.append($('<td>').text('$' + producto.total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
                
                tablaBody.append(fila);
            });
        }

        // Función para filtrar por rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var min = $('#minDate').val() ? new Date($('#minDate').val()) : null;
                var max = $('#maxDate').val() ? new Date($('#maxDate').val()) : null;
                var date = new Date(data[2]); // Columna 2 es la fecha de compra
                
                if ((min === null && max === null) ||
                    (min === null && date <= max) ||
                    (min <= date && max === null) ||
                    (min <= date && date <= max)) {
                    return true;
            }
            return false;
        }
        );

        // Aplicar filtro y actualizar resumen
        $('#filterBtn').click(function() {
            table.draw();
            updateSummary();
            updateProductosVendidos();
        });

        // Resetear filtros
        $('#resetBtn').click(function() {
            $('#minDate').val('');
            $('#maxDate').val('');
            table.draw();
            updateSummary();
            updateProductosVendidos();
        });

        // Actualizar al cargar la página
        updateSummary();
        updateProductosVendidos();
    });
</script>