<?php
// Configuración de permisos
$perm_almacenes = [
    'Alma_Crear' => isset($perm['Alma_Crear']) ? $perm['Alma_Crear'] : '',
    'Alma_Editar' => isset($perm['Alma_Editar']) ? $perm['Alma_Editar'] : '',
    'ACT_DES' => isset($perm['ACT_DES']) ? $perm['ACT_DES'] : '',
    'INACTIVO' => isset($perm['INACTIVO']) ? $perm['INACTIVO'] : ''
];

// Determinar si mostrar solo activos o todos
$mostrarSoloActivos = true;
if (isset($_GET['mostrar_inactivos']) && $_GET['mostrar_inactivos'] == '1') {
    $mostrarSoloActivos = false;
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Almacenes</h5>
            <div>
                <a href="?p=N_almacen" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle"></i> Nuevo Almacén
                </a>
                <?php if (!$mostrarSoloActivos): ?>
                    <a href="?p=almacenes" class="btn btn-sm btn-info">
                        <i class="bi bi-eye-slash"></i> Ocultar Inactivos
                    </a>
                <?php else: ?>
                    <a href="?p=almacenes&mostrar_inactivos=1" class="btn btn-sm btn-secondary" <?= $perm_almacenes['INACTIVO'];?>>
                        <i class="bi bi-eye"></i> Mostrar Inactivos
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="tablaAlmacenes">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Razón Social</th>
                            <th>RFC</th>
                            <th>Zona</th>
                            <th>Fecha de alta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Consultar almacenes según filtro
                        if ($mostrarSoloActivos) {
                            if ($zona_seleccionada == '0') {
                                $query = "SELECT a.*, z.nom as nombre_zona 
                                         FROM almacenes a 
                                         LEFT JOIN zonas z ON a.zona = z.id_zone 
                                         WHERE a.status = '1' 
                                         ORDER BY a.cod ASC";
                            } else {
                                $query = "SELECT a.*, z.nom as nombre_zona 
                                         FROM almacenes a 
                                         LEFT JOIN zonas z ON a.zona = z.id_zone 
                                         WHERE a.status = '1' AND a.zona = '$zona_seleccionada'
                                         ORDER BY a.cod ASC";
                            }
                        } else {
                            if ($zona_seleccionada == '0') {
                                $query = "SELECT a.*, z.nom as nombre_zona 
                                         FROM almacenes a 
                                         LEFT JOIN zonas z ON a.zona = z.id_zone 
                                         ORDER BY a.cod ASC";
                            } else {
                                $query = "SELECT a.*, z.nom as nombre_zona 
                                         FROM almacenes a 
                                         LEFT JOIN zonas z ON a.zona = z.id_zone 
                                         WHERE a.zona = '$zona_seleccionada'
                                         ORDER BY a.cod ASC";
                            }
                        }
                        
                        $queryAlmacenes = $conn_mysql->query($query);
                        $contador = 0;
                        
                        while ($almacen = mysqli_fetch_array($queryAlmacenes)): 
                            $contador++;
                            $fecha_alta = date('Y-m-d', strtotime($almacen['created_at'] ?? ''));
                            $status = $almacen['status'] == '1' ? 'Activo' : 'Inactivo';
                            $badgeClass = $almacen['status'] == '1' ? 'bg-success' : 'bg-danger';
                        ?>
                        <tr>
                            <td class="text-center"><?= $contador ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <?php if ($almacen['status'] == '1'): ?>
                                        <!-- Botones para almacenes activos -->
                                        <a href="?p=V_almacen&id=<?= $almacen['id_alma'] ?>" class="btn btn-sm btn-info" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="?p=E_almacen&id=<?= $almacen['id_alma'] ?>" class="btn btn-sm btn-warning" <?= $perm_almacenes['Alma_Editar'];?> title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?p=N_direccion_almacen&id=<?= $almacen['id_alma'] ?>" class="btn btn-sm btn-teal" title="Nueva dirección">
                                            <i class="bi bi-building"></i>
                                        </a>
                                        <button class="btn btn-sm btn-warning desactivar-almacen-btn" 
                                                data-id="<?= $almacen['id_alma'] ?>" 
                                                title="Desactivar"
                                                <?= $perm_almacenes['ACT_DES'];?>>
                                            <i class="bi bi-building-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Botón para almacenes inactivos -->
                                        <button class="btn btn-sm btn-info activar-almacen-btn" 
                                                data-id="<?= $almacen['id_alma'] ?>" 
                                                title="Activar">
                                            <i class="bi bi-building-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($almacen['cod']) ?></td>
                            <td>
                                <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" 
                                   href="?p=V_almacen&id=<?= $almacen['id_alma'] ?>">
                                    <?= htmlspecialchars($almacen['nombre']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($almacen['rs']) ?></td>
                            <td><?= htmlspecialchars($almacen['rfc']) ?></td>
                            <td><?= htmlspecialchars($almacen['nombre_zona']) ?></td>
                            <td><?= $fecha_alta ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast para notificaciones -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToastAlmacenes" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header encabezado-col">
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessageAlmacenes"></div>
    </div>
</div>

<!-- Modal de confirmación para almacenes -->
<div class="modal fade" id="confirmModalAlmacenes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="modalHeaderAlmacenes" class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessageAlmacenes">¿Estás seguro de que deseas desactivar este almacén?</p>
                <input type="hidden" id="almacenId">
                <input type="hidden" id="accionAlmacen">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmBtnAlmacenes">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tablaAlmacenes').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        "columnDefs": [
            { "orderable": false, "targets": [0, 1, 8] }, // Columnas no ordenables
            { "searchable": false, "targets": [0, 1] }    // Columnas no buscables
        ],
        "order": [[2, 'asc']] // Ordenar por Código (columna 2)
    });
    
    // Configurar modal para desactivar/activar almacenes
    $(document).on('click', '.desactivar-almacen-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#almacenId').val(id);
        $('#accionAlmacen').val('desactivar');
        $('#modalMessageAlmacenes').text('¿Estás seguro de que deseas desactivar este almacén?');
        $('#modalHeaderAlmacenes').removeClass().addClass('modal-header bg-warning text-white');
        $('#confirmBtnAlmacenes').removeClass().addClass('btn btn-warning');
        $('#confirmModalAlmacenes').modal('show');
    });
    
    $(document).on('click', '.activar-almacen-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#almacenId').val(id);
        $('#accionAlmacen').val('activar');
        $('#modalMessageAlmacenes').text('¿Estás seguro de que deseas reactivar este almacén?');
        $('#modalHeaderAlmacenes').removeClass().addClass('modal-header bg-info text-white');
        $('#confirmBtnAlmacenes').removeClass().addClass('btn btn-info');
        $('#confirmModalAlmacenes').modal('show');
    });
    
    // Confirmar acción para almacenes
    $('#confirmBtnAlmacenes').click(function() {
        const id = $('#almacenId').val();
        const accion = $('#accionAlmacen').val();
        
        $.post('actualizar_status_almacen.php', {
            id: id,
            accion: accion
        }, function(response) {
            if (response.success) {
                // Mostrar notificación de éxito
                const toast = new bootstrap.Toast(document.getElementById('liveToastAlmacenes'));
                $('#toastMessageAlmacenes').html('<i class="bi bi-check-circle-fill text-success me-2"></i> ' + response.message);
                toast.show();
                
                // Recargar después de 1.5 segundos
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                // Mostrar notificación de error
                const toast = new bootstrap.Toast(document.getElementById('liveToastAlmacenes'));
                $('#toastMessageAlmacenes').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error: ' + response.message);
                toast.show();
            }
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            const toast = new bootstrap.Toast(document.getElementById('liveToastAlmacenes'));
            $('#toastMessageAlmacenes').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error en la solicitud: ' + textStatus);
            toast.show();
        });
        
        $('#confirmModalAlmacenes').modal('hide');
    });
});
</script>