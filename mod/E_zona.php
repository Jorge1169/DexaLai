<?php
requirePermiso('ADMIN', 'zonas');

$id_zone = intval($_GET['id'] ?? 0);
if ($id_zone <= 0) {
    alert('Zona no válida', 0, 'zonas');
    exit;
}

$stmtZona = $conn_mysql->prepare("SELECT * FROM zonas WHERE id_zone = ?");
$stmtZona->bind_param('i', $id_zone);
$stmtZona->execute();
$zona = $stmtZona->get_result()->fetch_assoc();

if (!$zona) {
    alert('Zona no encontrada', 0, 'zonas');
    exit;
}

if (isset($_POST['actualizar_zona'])) {
    $cod = trim($_POST['cod'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $planta = trim($_POST['planta'] ?? '');
    $color = trim($_POST['color'] ?? '#3498db');
    $tipo = strtoupper(trim($_POST['tipo'] ?? 'NOR'));
    $status = ($_POST['status'] ?? '1') === '1' ? '1' : '0';

    if (!in_array($tipo, ['NOR', 'MEO', 'SUR'], true)) {
        $tipo = 'NOR';
    }

    if ($cod === '' || $nom === '' || $planta === '') {
        alert('Código, nombre y planta son obligatorios', 0, 'E_zona&id=' . $id_zone);
        exit;
    }

    $stmtCod = $conn_mysql->prepare("SELECT id_zone FROM zonas WHERE cod = ? AND id_zone != ?");
    $stmtCod->bind_param('si', $cod, $id_zone);
    $stmtCod->execute();
    $resCod = $stmtCod->get_result();

    if ($resCod && $resCod->num_rows > 0) {
        alert('El código de zona ya está en uso', 0, 'E_zona&id=' . $id_zone);
        exit;
    }

    $idUserSession = $_SESSION['id_cliente'] ?? 0;

    $stmtUpdate = $conn_mysql->prepare("UPDATE zonas SET cod = ?, nom = ?, PLANTA = ?, color = ?, tipo = ?, id_user = ?, status = ? WHERE id_zone = ?");
    $stmtUpdate->bind_param('sssssisi', $cod, $nom, $planta, $color, $tipo, $idUserSession, $status, $id_zone);
    $stmtUpdate->execute();

    if ($stmtUpdate->affected_rows >= 0) {
        logActivity('EDITAR', 'Actualizó zona ID: ' . $id_zone . ' a tipo ' . $tipo);
        alert('Zona actualizada exitosamente', 1, 'zonas');
    } else {
        alert('No fue posible actualizar la zona', 0, 'E_zona&id=' . $id_zone);
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Zona</h5>
            <a href="?p=zonas" class="btn btn-sm btn-danger rounded-3">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">ID</label>
                        <input type="text" class="form-control" value="<?= (int)$zona['id_zone'] ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Código *</label>
                        <input type="text" name="cod" class="form-control" maxlength="10" value="<?= htmlspecialchars($zona['cod'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($zona['nom'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Planta *</label>
                        <input type="text" name="planta" class="form-control" value="<?= htmlspecialchars($zona['PLANTA'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="NOR" <?= ($zona['tipo'] ?? '') === 'NOR' ? 'selected' : '' ?>>NOR</option>
                            <option value="MEO" <?= ($zona['tipo'] ?? '') === 'MEO' ? 'selected' : '' ?>>MEO</option>
                            <option value="SUR" <?= ($zona['tipo'] ?? '') === 'SUR' ? 'selected' : '' ?>>SUR</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color *</label>
                        <input type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($zona['color'] ?? '#3498db') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="1" <?= ($zona['status'] ?? '1') === '1' ? 'selected' : '' ?>>Activa</option>
                            <option value="0" <?= ($zona['status'] ?? '1') === '0' ? 'selected' : '' ?>>Inactiva</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" name="actualizar_zona" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
