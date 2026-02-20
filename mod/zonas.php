<?php
requirePermiso('ADMIN', 'inicio');

if (isset($_POST['cambiar_status']) && isset($_POST['id_zone'])) {
    $id_zone = intval($_POST['id_zone']);
    $nuevo_status = ($_POST['nuevo_status'] ?? '1') === '1' ? '1' : '0';

    $stmt = $conn_mysql->prepare("UPDATE zonas SET status = ?, id_user = ? WHERE id_zone = ?");
    $idUserSession = $_SESSION['id_cliente'] ?? 0;
    $stmt->bind_param('sii', $nuevo_status, $idUserSession, $id_zone);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $accion = $nuevo_status === '1' ? 'ACTIVAR' : 'DESACTIVAR';
        logActivity($accion, 'Actualizó status de zona ID: ' . $id_zone);
        alert('Status de zona actualizado correctamente', 1, 'zonas');
    } else {
        alert('No fue posible actualizar el status de la zona', 0, 'zonas');
    }
}

$zonas = $conn_mysql->query("SELECT * FROM zonas ORDER BY id_zone ASC");
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Zonas</h5>
            <a href="?p=N_zona" class="btn btn-sm btn-light rounded-3">
                <i class="bi bi-plus-circle me-1"></i>Nueva Zona
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover table-sm" id="tablaZonas" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Planta</th>
                            <th>Tipo</th>
                            <th>Color</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($zona = mysqli_fetch_assoc($zonas)) {
                            $activo = $zona['status'] === '1';
                            $badgeStatus = $activo ? 'bg-success' : 'bg-danger';
                            $textoStatus = $activo ? 'Activa' : 'Inactiva';
                            $tipo = strtoupper($zona['tipo'] ?? 'NOR');
                            ?>
                            <tr>
                                <td><?= (int)$zona['id_zone'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <a href="?p=E_zona&id=<?= (int)$zona['id_zone'] ?>" class="btn btn-info btn-sm rounded-3">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="id_zone" value="<?= (int)$zona['id_zone'] ?>">
                                            <input type="hidden" name="nuevo_status" value="<?= $activo ? '0' : '1' ?>">
                                            <button type="submit" name="cambiar_status" class="btn btn-<?= $activo ? 'warning' : 'success' ?> btn-sm rounded-3">
                                                <i class="bi bi-<?= $activo ? 'x-circle' : 'check-circle' ?>"></i>
                                                <?= $activo ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td><strong><?= htmlspecialchars($zona['cod'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($zona['nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($zona['PLANTA'] ?? '') ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($tipo) ?></span></td>
                                <td>
                                    <span class="badge" style="background-color: <?= htmlspecialchars($zona['color'] ?? '#3498db') ?>; color:#fff;">
                                        <?= htmlspecialchars($zona['color'] ?? '#3498db') ?>
                                    </span>
                                </td>
                                <td><span class="badge <?= $badgeStatus ?>"><?= $textoStatus ?></span></td>
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

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tablaZonas').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json'
            },
            responsive: true,
            order: [[0, 'asc']]
        });
    }
});
</script>
