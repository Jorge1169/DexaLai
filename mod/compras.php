<div class="container mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Compras</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Prove_Crear'];?> href="?p=N_compra" class="btn btn-primary btn-sm rounded-3 mt-1">
                    <i class="bi bi-plus"></i> Nueva Compra
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3 mt-1" onclick="toggleInactiveCompras()">
                    <i class="bi bi-eye"></i> Mostrar Inactivas
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
                <button id="resetBtn" class="btn btn-sm rounded-3 btn-secondary px-3"><i class="bi bi-arrow-clockwise"></i> Resetear</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="tablaCompras" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th data-priority="1">Remisión</th>
                            <th>Nombre</th>
                            <th>Proveedor</th>
                            <th>Transporte</th>
                            <th>Producto</th>
                            <th>Peso Neto</th>
                            <th>Precio</th>
                            <th>Zona</th>
                            <th>Docs</th>
                            <th>Fecha</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $Contador = 0;// baciar contador
                        $Activos = 1;// contador de activos
                        $Desacti = 1;// contador de inactivos

                        // Consulta modificada para incluir zonas
                        if ($zona_seleccionada == '0') {
                            $query = "SELECT c.*, 
                            c.factura AS factura_compra,
                            c.d_prov AS documento_compra,
                            c.acciones AS autorizar, 
                            p.cod AS cod_proveedor,
                            d.cod_al AS cod_direccion,
                            t.placas AS placas_transporte,
                            pr.cod AS cod_producto,
                            u.nombre AS nombre_usuario,
                            z.nom AS nom_zone
                            FROM compras c
                            LEFT JOIN proveedores p ON c.id_prov = p.id_prov
                            LEFT JOIN direcciones d ON c.id_direc = d.id_direc
                            LEFT JOIN transportes t ON c.id_transp = t.id_transp
                            LEFT JOIN productos pr ON c.id_prod = pr.id_prod
                            LEFT JOIN usuarios u ON c.id_user = u.id_user
                            LEFT JOIN zonas z ON c.zona = z.id_zone ORDER BY c.fecha DESC";
                        } else {
                            $query = "SELECT c.*, 
                            c.factura AS factura_compra,
                            c.d_prov AS documento_compra,
                            c.acciones AS autorizar, 
                            p.cod AS cod_proveedor,
                            d.cod_al AS cod_direccion,
                            t.placas AS placas_transporte,
                            pr.cod AS cod_producto,
                            u.nombre AS nombre_usuario,
                            z.nom AS nom_zone
                            FROM compras c
                            LEFT JOIN proveedores p ON c.id_prov = p.id_prov
                            LEFT JOIN direcciones d ON c.id_direc = d.id_direc
                            LEFT JOIN transportes t ON c.id_transp = t.id_transp
                            LEFT JOIN productos pr ON c.id_prod = pr.id_prod
                            LEFT JOIN usuarios u ON c.id_user = u.id_user
                            LEFT JOIN zonas z ON c.zona = z.id_zone
                            WHERE c.zona = '$zona_seleccionada' ORDER BY c.fecha DESC";
                        }

                        $result = $conn_mysql->query($query);

                        while ($Compra = mysqli_fetch_array($result)) {
                            ($Compra['status'] == '1') ? $Contador = $Activos++ : $Contador = $Desacti++ ;// codigo de contador
                            
                            $fecha_compra = date('Y-m-d', strtotime($Compra['fecha']));
                            $status = $Compra['status'] == '1' ? 'Activo' : 'Inactivo';
                            $badgeClass = $Compra['status'] == '1' ? 'bg-success' : 'bg-danger';

                            $Factura = (!empty($Compra['factura_compra'])) ? '<a href="'.$invoiceLK.$Compra['documento_compra'].'.pdf" target="_blank" title="Abrir documento de factura del proveedor"><i class="bi text-success bi-file-earmark-check-fill fs-5"></i></a>' : '<i class="bi text-danger bi-file-earmark-excel-fill fs-5"></i>' ;

                            $docValue = 0;
                            if (!empty($Compra['factura'])) {
                                    $docValue = 1; // Ambos documentos
                                } elseif (empty($Compra['factura'])) {
                                    $docValue = 0; // Solo factura
                                } 

                                $ExCompra = ($Compra['ex'] == 2) ? '<i class="bi bi-filetype-svg text-teal bg-teal bg-opacity-10 rounded-1 p-auto" style="font-size: 15px" title="Cargado desde Excel"></i>' : '' ;


                                ?>
                                <tr>
                                    <td class="text-center"><?= $Contador ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2">
                                            <?php 
                                            if ($Compra['autorizar'] == 0) {
                                                if ($Compra['status'] == '1'): 
                                                    ?>
                                                    <a <?= $perm['Prove_Editar'];?> href="?p=E_compra&id=<?= $Compra['id_compra'] ?>" 
                                                     class="btn btn-info btn-sm rounded-3" title="Editar">
                                                     <i class="bi bi-pencil"></i>
                                                 </a>

                                                 <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-compra-btn" 
                                                     data-id="<?= $Compra['id_compra'] ?>" title="Borrar / Desactivar">
                                                     <i class="bi bi-x-circle"></i>
                                                 </button>
                                             <?php else: ?>
                                                <button class="btn btn-info btn-sm rounded-3 activar-compra-btn" 
                                                data-id="<?= $Compra['id_compra'] ?>" 
                                                title="Activar compra">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                            <?php 
                                        endif; 
                                    }else {
                                        ?>
                                        <a <?= $perm['Prove_Editar'];?> 
                                                     class="btn btn-secondary btn-sm rounded-3" title="Editar">
                                                     <i class="bi bi-pencil"></i>
                                                 </a>

                                                 <button <?= $perm['ACT_DES'];?> class="btn btn-secondary btn-sm rounded-3" title="Borrar / Desactivar">
                                                     <i class="bi bi-x-circle"></i>
                                                 </button>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($Compra['fact']) .' '.$ExCompra?></td>
                            <td>
                                <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="?p=V_compra&id=<?= $Compra['id_compra'] ?>">
                                    <?= htmlspecialchars($Compra['nombre']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($Compra['cod_direccion']) ?></td>
                            <td><?= htmlspecialchars($Compra['placas_transporte']) ?></td>
                            <td><?= htmlspecialchars($Compra['cod_producto']) ?></td>
                            <td><?= number_format($Compra['neto'], 2)?>kg</td>
                            <td>$<?= number_format($Compra['pres'], 2)?></td>
                            <td><?= htmlspecialchars($Compra['nom_zone']) ?></td>
                            <td class="text-center" data-order="<?= $docValue ?>">
                                <div class="docs-container">
                                    <?= $Factura ?>
                                </div>
                            </td>
                            <td><?= $fecha_compra ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
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

<!-- Modal de confirmación para compras -->
<div class="modal fade" id="confirmCompraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="prueb" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalCompraMessage">¿Estás seguro de que deseas desactivar esta compra?</p>
                <input type="hidden" id="compraId">
                <input type="hidden" id="compraAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmCompraBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inicializar DataTable con las mejoras
        const table = $('#tablaCompras').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "responsive": true,
            "columnDefs": [
                { "targets": [1], "orderable": false }, // Deshabilitar ordenación para columna de acciones
                { "targets": [11], "type": "date" }, // Especificar que la columna 10 es de tipo fecha
                { "visible": true, "targets": [12] } // Ocultar columna de status (se filtra pero no se muestra)
            ],
            "initComplete": function() {
                // Aplicar filtro inicial para mostrar solo activos
                this.api().column(12).search("^Activo$", true, false).draw();
            }
        });

        // Variable para rastrear el estado actual
        let showingInactivesCompras = false;

        // Función para alternar entre activas/inactivas
        window.toggleInactiveCompras = function() {
            const btn = $('button[onclick="toggleInactiveCompras()"]');
            
            if (showingInactivesCompras) {
                // Mostrar solo activas
                table.column(12).search("^Activo$", true, false).draw();
                btn.html('<i class="bi bi-eye"></i> Mostrar Inactivas');
                btn.removeClass('btn-info').addClass('btn-secondary');
            } else {
                // Mostrar solo inactivas
                table.column(12).search("^Inactivo$", true, false).draw();
                btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivas');
                btn.removeClass('btn-secondary').addClass('btn-info');
            }
            
            showingInactivesCompras = !showingInactivesCompras;
        };

        // Filtrar por zona
        $('#zonaFilter').change(function() {
            const zonaId = $(this).val();
            window.location.href = window.location.pathname + '?p=compras&zona=' + zonaId;
        });

        // Función para filtrar por rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var min = $('#minDate').val() ? new Date($('#minDate').val()) : null;
                var max = $('#maxDate').val() ? new Date($('#maxDate').val()) : null;
                var date = new Date(data[11]); // Columna 10 es la fecha
                
                if ((min === null && max === null) ||
                    (min === null && date <= max) ||
                    (min <= date && max === null) ||
                    (min <= date && date <= max)) {
                    return true;
            }
            return false;
        }
        );

        // Aplicar filtro al hacer clic en el botón
        $('#filterBtn').click(function() {
            table.draw();
        });

        // Resetear filtros
        $('#resetBtn').click(function() {
            $('#minDate').val('');
            $('#maxDate').val('');
            table.draw();
        });

        // Configurar modal para desactivar/activar compras
        $(document).on('click', '.desactivar-compra-btn', function() {
            const id = $(this).data('id');
            $('#compraId').val(id);
            $('#compraAccion').val('desactivar');
            $('#modalCompraMessage').text('¿Estás seguro de que deseas desactivar esta compra?');
            $('#confirmCompraModal').modal('show');
            $('#prueb').addClass('text-bg-warning');
            $('#confirmCompraBtn').addClass('btn-warning');
        });

        $(document).on('click', '.activar-compra-btn', function() {
            const id = $(this).data('id');
            $('#compraId').val(id);
            $('#compraAccion').val('activar');
            $('#modalCompraMessage').text('¿Estás seguro de que deseas reactivar esta compra?');
            $('#confirmCompraModal').modal('show');
            $('#prueb').addClass('text-bg-info');
            $('#confirmCompraBtn').addClass('btn-info');
        });

        // Confirmar acción para compras
        $('#confirmCompraBtn').click(function() {
            const id = $('#compraId').val();
            const accion = $('#compraAccion').val();

            $.post('actualizar_status_com.php', {
                id: id,
                accion: accion,
                tabla: 'compras'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                alert('Error en la solicitud: ' + textStatus + ', ' + errorThrown);
            });

            $('#confirmCompraModal').modal('hide');
        });
    });
</script>