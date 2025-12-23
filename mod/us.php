<div class="container mt-2">
<div class="card shadow-sm">
    <h5 class="card-header encabezado-col text-white">Usuarios del Sistema</h5>
    <div class="card-body">
        <div class="mb-3">
            <a <?= $perm['Clien_Crear'];?> href="?p=N_usuario" class="btn btn-primary btn-sm">
                <i class="bi bi-plus"></i> Nuevo Usuario
            </a>
            <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm" onclick="toggleInactiveUsers()">
                <i class="bi bi-eye"></i> Mostrar Inactivos
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="tablaUsuarios" style="width:100%">
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
                    $Contador = 0;
                    // Consulta para usuarios activos (status = 1)
                    $UsuariosActivos = $conn_mysql->query("
                        SELECT * FROM usuarios WHERE status = '1' ORDER BY tipo DESC, nombre ASC
                    ");
                    
                    while ($Usuario = mysqli_fetch_array($UsuariosActivos)) {
                        $Contador++;
                        
                        // Determinar tipo de usuario
                        $tipoUsuario = '';
                        $badgeClass = '';
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
                                    <a <?= $perm['Clien_Editar'];?> href="?p=E_usuario&id=<?= $Usuario['id_user'] ?>" 
                                       class="btn btn-info btn-sm">
                                       <i class="bi bi-pencil"></i> Editar
                                   </a>

                                   <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm desactivar-user-btn" 
                                   data-id="<?= $Usuario['id_user'] ?>">
                                   <i class="bi bi-x-circle"></i> Desactivar
                               </button>
                           </div>
                       </td>
                       <td><a href="?p=V_usuarios&id=<?= $Usuario['id_user'] ?>" class="text-primary"><?= htmlspecialchars($Usuario['usuario']) ?></a>  </td>
                       <td><?= htmlspecialchars($Usuario['nombre']) ?></td>
                       <td><?= htmlspecialchars($Usuario['correo']) ?></td>
                       <td><span class="badge <?= $badgeClass ?>"><?= $tipoUsuario ?></span></td>
                       <td><span class="badge bg-success">Activo</span></td>
                    </tr>
                    <?php
                    }

                    // Consulta para usuarios inactivos (status = 0) - inicialmente ocultas
                    $UsuariosInactivos = $conn_mysql->query("
                        SELECT * FROM usuarios WHERE status = '0' ORDER BY tipo DESC, nombre ASC
                    ");

                    while ($UsuarioInact = mysqli_fetch_array($UsuariosInactivos)) {
                        $Contador++;
                        
                        // Determinar tipo de usuario
                        $tipoUsuario = '';
                        $badgeClass = '';
                        switch($UsuarioInact['tipo']) {
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
                        <tr class="inactive-user-row" style="display: none;">
                            <td class="text-center"><?= $Contador ?></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-info btn-sm activar-user-btn" 
                                    data-id="<?= $UsuarioInact['id_user'] ?>" 
                                    title="Activar usuario">
                                    <i class="bi bi-check-circle"></i> Activar
                                </button>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($UsuarioInact['usuario']) ?></td>
                        <td><?= htmlspecialchars($UsuarioInact['nombre']) ?></td>
                        <td><?= htmlspecialchars($UsuarioInact['correo']) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $tipoUsuario ?></span></td>
                        <td><span class="badge bg-danger">Inactivo</span></td>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
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
                <button type="button" class="btn btn-primary" id="confirmUserBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tablaUsuarios').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": [1] }, // Deshabilitar ordenación en columna de acciones
                { "searchable": false, "targets": [1] }  // Deshabilitar búsqueda en columna de acciones
            ]
        });
        
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
        });
        
        $(document).on('click', '.activar-user-btn', function() {
            const id = $(this).data('id');
            $('#userId').val(id);
            $('#userAccion').val('activar');
            $('#modalUserMessage').text('¿Estás seguro de que deseas reactivar este usuario?');
            $('#confirmUserModal').modal('show');
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