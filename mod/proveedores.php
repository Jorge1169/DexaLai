<div class="container mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Proveedores</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Prove_Crear'];?> href="?p=N_proveedor" class="btn btn-primary btn-sm rounded-3">
                    <i class="bi bi-plus"></i> Nuevo Proveedores
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3" onclick="toggleInactive()">
                    <i class="bi bi-eye"></i> Mostrar Inactivos
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th data-priority="1">Codigo</th>
                            <th>Razon social</th>
                            <th>RFC</th>
                            <th>Zona</th>
                            <th>Fecha de alta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                    // Consulta para obtener todos los clientes según zona seleccionada
                        if ($zona_seleccionada == '0') {
                            $query = "SELECT p.*, z.nom AS nom_zone FROM proveedores p LEFT JOIN zonas z ON p.zona = z.id_zone";
                        } else {
                            $query = "SELECT p.*, z.nom AS nom_zone FROM proveedores p LEFT JOIN zonas z ON p.zona = z.id_zone WHERE p.zona = '$zona_seleccionada'";
                        }
                        $result = $conn_mysql->query($query);
                        $contador = 0;// baciar contador
                        $Activos = 1;// contador de activos
                        $Desacti = 1;// contador de inactivos

                        while ($Clien01 = mysqli_fetch_array($result)) {
                           ($Clien01['status'] == '1') ? $contador = $Activos++ : $contador = $Desacti++ ;// codigo de contador


                           $fecha_alta = date('Y-m-d', strtotime($Clien01['fecha']));
                           $status = $Clien01['status'] == '1' ? 'Activo' : 'Inactivo';
                           $badgeClass = $Clien01['status'] == '1' ? 'bg-success' : 'bg-danger';



                           ?>
                           <tr>
                            <td class="text-center"><?= $contador ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-2">
                                   <?php if ($Clien01['status'] == '1'): ?>
                                    <a <?= $perm['Prove_Editar'];?> href="?p=E_proveedor&id=<?= $Clien01['id_prov'] ?>" 
                                     class="btn btn-info btn-sm rounded-3" title="Editar proveedor">
                                     <i class="bi bi-pencil"></i> 
                                 </a>

                                 <a href="?p=N_direccion_p&id=<?= $Clien01['id_prov'] ?>" 
                                     class="btn btn-teal btn-sm rounded-3" title="Nueva bodega">
                                     <i class="bi bi-building"></i>
                                 </a>

                                 <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-btn" 
                                     data-id="<?= $Clien01['id_prov'] ?>" title="Desactivar  proveedor">
                                     <i class="bi bi-person-x"></i>
                                 </button>
                             <?php else: ?>
                                <button class="btn btn-info btn-sm rounded-3 activar-btn" 
                                data-id="<?= $Clien01['id_prov'] ?>" 
                                title="Activar cliente">
                                <i class="bi bi-person-check"></i> Activar
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($Clien01['cod']) ?></td>
                <td>
                 <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="?p=V_proveedores&id=<?= $Clien01['id_prov'] ?>" class="text-primary">
                    <?= htmlspecialchars($Clien01['rs']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($Clien01['rfc']) ?></td>
            <td><?= htmlspecialchars($Clien01['nom_zone']) ?></td>
            <td><?= $fecha_alta ?></td>
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
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header encabezado-col">
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>
<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="prueb" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">¿Estás seguro de que deseas desactivar este proveedores?</p>
                <input type="hidden" id="clienteId">
                <input type="hidden" id="accion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const dataTable = $('#miTabla').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "responsive": true,
            "initComplete": function() {
                // Aplicar filtro inicial para mostrar solo activos
                this.api().column(7).search("^Activo$", true, false).draw();
            }
        });
        
         // Variable para rastrear el estado actual
        let showingInactives = false;

        // Función para alternar entre activos/inactivos
        window.toggleInactive = function() {
            const btn = $('button[onclick="toggleInactive()"]');
            
            if (showingInactives) {
                // Mostrar solo activos
                dataTable.column(7).search("^Activo$", true, false).draw();
                btn.html('<i class="bi bi-eye"></i> Mostrar Inactivos');
                btn.removeClass('btn-info').addClass('btn-secondary');
            } else {
                // Mostrar solo inactivos
                dataTable.column(7).search("^Inactivo$", true, false).draw();
                btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivos');
                btn.removeClass('btn-secondary').addClass('btn-info');
            }
            
            showingInactives = !showingInactives;
        };
        // Configurar modal para desactivar/activar proveedores
        $(document).on('click', '.desactivar-btn', function() {
            const id = $(this).data('id');
            $('#clienteId').val(id);
            $('#accion').val('desactivar');
            $('#modalMessage').text('¿Estás seguro de que deseas desactivar este proveedor?');
            $('#confirmModal').modal('show');
            $('#prueb').addClass('text-bg-warning');
            $('#confirmBtn').addClass('btn-warning');
        });
        
        $(document).on('click', '.activar-btn', function() {
            const id = $(this).data('id');
            $('#clienteId').val(id);
            $('#accion').val('activar');
            $('#modalMessage').text('¿Estás seguro de que deseas reactivar este proveedor?');
            $('#confirmModal').modal('show');
            $('#prueb').addClass('text-bg-info');
            $('#confirmBtn').addClass('btn-info');
        });
        
        // Confirmar acción
        $('#confirmBtn').click(function() {
            const id = $('#clienteId').val();
            const accion = $('#accion').val();
            
            $.post('actualizar_status_p.php', {
                id: id,
                accion: accion
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                alert('Error en la solicitud: ' + textStatus + ', ' + errorThrown);
            });
            
            $('#confirmModal').modal('hide');
        });
    });
</script>