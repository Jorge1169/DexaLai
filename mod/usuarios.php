<?php
// Verificar permisos

// Verificar permisos (solo admin y usuarioA pueden ver esta página)
if ($TipoUserSession != 100) {
    alert("No tienes permisos para esta sección", 0, "inicio");
    exit();
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Usuarios del Sistema</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Clien_Crear'];?> href="?p=N_usuario" class="btn btn-primary btn-sm rounded-3">
                    <i class="bi bi-plus"></i> Nuevo Usuario
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3" onclick="toggleInactive()">
                    <i class="bi bi-eye"></i> Mostrar Inactivos
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover table-sm" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th data-priority="1">Usuario</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Tipo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                    // Consulta para usuarios activos (status = 1)
                        $query = "SELECT * FROM usuarios";
                        $result = $conn_mysql->query($query);
                        $Contador = 0;// baciar contador
                        $Activos = 1;// contador de activos
                        $Desacti = 1;// contador de inactivos
                        while ($Usuario = mysqli_fetch_array($result)) {
                            ($Usuario['status'] == '1') ? $Contador = $Activos++ : $Contador = $Desacti++ ;// codigo de contador

                        // Determinar tipo de usuario
                            $tipoUsuario = '';
                            $badgeClass = '';
                            $status = $Usuario['status'] == '1' ? 'Activo' : 'Inactivo';
                            $badgeClass1 = $Usuario['status'] == '1' ? 'bg-success' : 'bg-danger';
                            switch($Usuario['tipo']) {
                                case 100:
                                $tipoUsuario = 'Administrador';
                                $badgeClass = 'bg-danger';
                                break;
                                case 50:
                                $tipoUsuario = 'Usuario A';
                                $badgeClass = 'bg-primary';
                                break;
                                case 30:
                                $tipoUsuario = 'Usuario B';
                                $badgeClass = 'bg-info';
                                break;
                                case 10:
                                $tipoUsuario = 'Usuario C';
                                $badgeClass = 'bg-secondary';
                                break;
                                default:
                                $tipoUsuario = 'Desconocido';
                                $badgeClass = 'bg-warning text-dark';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?= $Contador ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-2">
                                     <?php if ($Usuario['status'] == '1'): ?>
                                        <a <?= $perm['Clien_Editar'];?> href="?p=E_usuario&id=<?= $Usuario['id_user'] ?>" 
                                         class="btn btn-info btn-sm rounded-3">
                                         <i class="bi bi-pencil"></i> Editar
                                     </a>

                                     <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-user-btn" 
                                         data-id="<?= $Usuario['id_user'] ?>">
                                         <i class="bi bi-x-circle"></i> Desactivar
                                     </button>
                                 <?php else: ?>
                                    <button class="btn btn-info btn-sm rounded-3 activar-user-btn" 
                                    data-id="<?= $Usuario['id_user'] ?>" 
                                    title="Activar Usuario">
                                    <i class="bi bi-person-check"></i> Activar
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="?p=V_usuarios&id=<?= $Usuario['id_user'] ?>" class="text-primary"><?= htmlspecialchars($Usuario['usuario']) ?></a>  </td>
                    <td><?= htmlspecialchars($Usuario['nombre']) ?></td>
                    <td><?= htmlspecialchars($Usuario['correo']) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $tipoUsuario ?></span></td>
                    <td><span class="badge <?= $badgeClass1 ?>"><?= $status ?></span></td>
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
<!-- Modal de confirmación para usuarios -->
<div class="modal fade" id="confirmUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="prueb" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalUserMessage">¿Estás seguro de que deseas desactivar este usuario?</p>
                <input type="hidden" id="userId">
                <input type="hidden" id="userAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmUserBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
         // Inicializar DataTable con filtro inicial para mostrar solo activos
        const dataTable = $('#miTabla').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "responsive": true,
            "initComplete": function() {
                // Aplicar filtro inicial para mostrar solo activos
                this.api().column(6).search("^Activo$", true, false).draw();
            }
        });

        // Variable para rastrear el estado actual
        let showingInactives = false;

        // Función para alternar entre activos/inactivos
        window.toggleInactive = function() {
            const btn = $('button[onclick="toggleInactive()"]');
            
            if (showingInactives) {
                // Mostrar solo activos
                dataTable.column(6).search("^Activo$", true, false).draw();
                btn.html('<i class="bi bi-eye"></i> Mostrar Inactivos');
                btn.removeClass('btn-info').addClass('btn-secondary');
            } else {
                // Mostrar solo inactivos
                dataTable.column(6).search("^Inactivo$", true, false).draw();
                btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivos');
                btn.removeClass('btn-secondary').addClass('btn-info');
            }
            
            showingInactives = !showingInactives;
        };
        
        // Mostrar/ocultar usuarios inactivos
        window.toggleInactiveUsers = function() {
            $('.inactive-user-row').toggle();
            const btn = $('button[onclick="toggleInactiveUsers()"]');
            if ($('.inactive-user-row:visible').length > 0) {
                btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivos');
                btn.removeClass('btn-secondary').addClass('btn-info');
            } else {
                btn.html('<i class="bi bi-eye"></i> Mostrar Inactivos');
                btn.removeClass('btn-info').addClass('btn-secondary');
            }
        };
        
        // Configurar modal para desactivar/activar usuarios
        $(document).on('click', '.desactivar-user-btn', function() {
            const id = $(this).data('id');
            $('#userId').val(id);
            $('#userAccion').val('desactivar');
            $('#modalUserMessage').text('¿Estás seguro de que deseas desactivar este usuario?');
            $('#confirmUserModal').modal('show');
            $('#prueb').addClass('text-bg-warning');
            $('#confirmUserBtn').addClass('btn-warning');
        });
        
        $(document).on('click', '.activar-user-btn', function() {
            const id = $(this).data('id');
            $('#userId').val(id);
            $('#userAccion').val('activar');
            $('#modalUserMessage').text('¿Estás seguro de que deseas reactivar este usuario?');
            $('#confirmUserModal').modal('show');
            $('#prueb').addClass('text-bg-info');
            $('#confirmUserBtn').addClass('btn-info');
        });
        
        // Confirmar acción para usuarios
        $('#confirmUserBtn').click(function() {
            const id = $('#userId').val();
            const accion = $('#userAccion').val();
            
            $.post('actualizar_status_u.php', {
                id: id,
                accion: accion,
                tabla: 'usuarios'  // Identificar la tabla
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                alert('Error en la solicitud: ' + textStatus + ', ' + errorThrown);
            });
            
            $('#confirmUserModal').modal('hide');
        });
    });
</script>