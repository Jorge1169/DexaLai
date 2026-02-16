<?php
// Obtener ID del Cliente
$id_cliente = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona actual
// Obtener datos del cliente
$cliente = [];
$bodegas = [];

if ($id_cliente) {
    // Datos básicos del cliente
    $sqlCliente = "SELECT * FROM clientes WHERE id_cli = ?";
    $stmtCliente = $conn_mysql->prepare($sqlCliente);
    $stmtCliente->bind_param('i', $id_cliente);
    $stmtCliente->execute();
    $resultCliente = $stmtCliente->get_result();
    $cliente = $resultCliente->fetch_assoc();
    
    // Bodegas del cliente
    $sqlBodegas = "SELECT * FROM direcciones WHERE status = '1' AND id_us = ? ORDER BY noma ASC";
    $stmtBodegas = $conn_mysql->prepare($sqlBodegas);
    $stmtBodegas->bind_param('i', $id_cliente);
    $stmtBodegas->execute();
    $resultBodegas = $stmtBodegas->get_result();
    $bodegas = $resultBodegas->fetch_all(MYSQLI_ASSOC);

    $zon0 = $conn_mysql->query("SELECT * FROM zonas where id_zone = '".$cliente['zona']."'");
    $zon1 = mysqli_fetch_array($zon0);

// Verificar que el cliente pertenece a la zona seleccionada en sesión
$zona_actual = $_SESSION['selected_zone'] ?? '0';
if ($id_cliente && $cliente) {
    if ($zona_actual !== '0' && isset($cliente['zona']) && (string)$cliente['zona'] !== (string)$zona_actual) {
        alert("El cliente no pertenece a la zona seleccionada", 2, "clientes");
        exit;
    }
}
}
?>

<div class="container mt-2">
    <!-- Tarjeta principal del cliente -->
    <div class="card mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Datos del cliente</h5>
                <span class="small">Código: <?= htmlspecialchars($cliente['cod'] ?? 'N/A') ?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="?p=clientes" class="btn btn-sm rounded-3 btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
                <a href="?p=E_Cliente&id=<?= $id_cliente ?>" class="btn btn-sm rounded-3 btn-light" <?= $perm['Clien_Editar'];?>>
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">

                <div class="col-md-6">
                <div class="card border-1 mb-3 h-100"  style="background-color: var(--color-acento);">
                    <div class="card-body">
                        <h6 class="text-uppercase text-primary small fw-bold mb-3">
                            <i class="bi bi-info-circle me-1"></i> Información Básica
                        </h6>
                        <div class="mb-3">
                            <p class="text-muted small mb-1">Cliente</p>
                            <h5 class="fw-bold"><?= htmlspecialchars($cliente['nombre'] ?? 'N/A') ?></h5>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted small mb-1">RFC</p>
                            <h5 class="mb-0"><?= htmlspecialchars($cliente['rfc'] ?? 'N/A') ?></h5>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted small mb-1">Razón Social</p>
                            <p class="mb-0"><?= htmlspecialchars($cliente['rs'] ?? 'N/A') ?></p>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted small mb-1">Observaciones</p>
                            <p class="mb-0"><?= htmlspecialchars($cliente['obs'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>

                      <!-- Columna derecha - Rediseñada -->
            <div class="col-md-6">
                <div class="card border-1 mb-3 h-100"  style="background-color: var(--color-acento);">
                    <div class="card-body">
                        <h6 class="text-uppercase text-primary small fw-bold mb-3">
                            <i class="bi bi-card-checklist me-1"></i> Detalles Adicionales
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                    <p class="text-muted small mb-1">Tipo</p>
                                    <span class="d-block fw-bold"><?= ($cliente['tpersona'] ?? '') == 'fisica' ? 'Persona Física' : 'Persona Moral' ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded <?= ($cliente['status'] ?? 0) == 1 ? 'bg-success' : 'bg-secondary' ?> text-white h-100">
                                    <p class="small mb-1">Estado</p>
                                    <span class="fw-bold"><?= ($cliente['status'] ?? 0) == 1 ? 'Activo' : 'Inactivo' ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                    <p class="text-muted small mb-1">Tipo de evidencia</p>
                                    <p class="mb-0"><?= ($cliente['fac_rem'] ?? '') == 'FAC' ? 'Factura' : 'Remisión' ?></p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-primary bg-opacity-25 border border-primary h-100">
                                    <p class="text-muted small mb-1">Zona</p>
                                    <span class="d-block fw-bold"><?=$zon1['nom']?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>

            <!-- Sección de bodegas -->

   <div class="card-header border-bottom-0">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Bodegas Registradas</h5>
        <a href="?p=N_direccion&id=<?= $id_cliente ?>" class="btn btn-sm rounded-3 btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nueva Bodega
        </a>
    </div>
</div>
<div class="card-body">
    <?php if (empty($bodegas)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> Este cliente no tiene bodegas registradas.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm" id="miTabla" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Dirección</th>
                        <th>Observación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bodegas as $index => $bodega): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= $bodega['cod_al']?></strong>
                                <?php if ($tipoZonaActual == 'MEO' && !empty($bodega['c_postal'])): ?>
                                <div class="small text-muted">
                                    CP: <?= $bodega['c_postal']?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $bodega['noma']?></td>
                            <td>
                                <div class="small">
                                    <strong>Atención:</strong> <?= $bodega['atencion']?><br>
                                    <strong>Tel:</strong> <?= $bodega['tel']?><br>
                                    <strong>Email:</strong> <?= $bodega['email']?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($bodega['calle']) || !empty($bodega['colonia'])): ?>
                                <div class="small">
                                    <?php if (!empty($bodega['calle'])): ?>
                                        <strong>Calle:</strong> <?= $bodega['calle'] ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($bodega['numext'])): ?>
                                        <strong>No. Ext:</strong> <?= $bodega['numext'] ?>
                                        <?php if (!empty($bodega['numint'])): ?>
                                            <strong>Int:</strong> <?= $bodega['numint'] ?><br>
                                        <?php else: ?><br><?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($bodega['colonia'])): ?>
                                        <strong>Colonia:</strong> <?= $bodega['colonia'] ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($bodega['estado'])): ?>
                                        <strong>Estado:</strong> <?= $bodega['estado'] ?><br>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sin dirección registrada</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $bodega['obs'] ?></td>
                            <td class="text-center">
                                <div class="btn-group justify-content-center"> 
                                    <a href="?p=E_direccion&id=<?= $bodega['id_direc'] ?>" 
                                     class="btn btn-sm btn-outline-primary" title="Editar">
                                     <i class="bi bi-pencil"></i>
                                 </a>
                                 <button <?= $perm['ACT_DES'];?> class="btn btn-sm btn-outline-danger eliminar-direccion" 
                                 data-id="<?= $bodega['id_direc'] ?>" title="Eliminar">
                                 <i class="bi bi-trash"></i>
                             </button>
                         </div>
                     </td>
                 </tr>
             <?php endforeach; ?>
         </tbody>
     </table>
 </div>
<?php endif; ?>
</div>
 </div>

<!-- Modal de confirmación para eliminar bodega -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirmar eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar esta bodega? Esta acción no se puede deshacer.</p>
                <input type="hidden" id="bodegaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
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
<script>
    $(document).ready(function() {
        // Configurar modal para eliminar bodega
        $(document).on('click', '.eliminar-direccion', function() {
            const id = $(this).data('id');
            $('#bodegaId').val(id);
            $('#confirmDeleteModal').modal('show');
        });

        // Confirmar eliminación
        $('#confirmDeleteBtn').click(function() {
            const id = $('#bodegaId').val();

            $.post('mod/eliminar_direccion.php', {
                id: id
            }, function(response) {
                if (response.success) {
                    // Mostrar notificación de éxito
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html('<i class="bi bi-check-circle-fill text-success me-2"></i> Bodega eliminada correctamente');
                    toast.show();
                    
                    // Recargar después de 1.5 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Mostrar notificación de error
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error: ' + response.message);
                    toast.show();
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                $('#toastMessage').html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error en la solicitud: ' + textStatus);
                toast.show();
            });

            $('#confirmDeleteModal').modal('hide');
        });
    });
</script>

<!-- Toast para notificaciones -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-white">
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>