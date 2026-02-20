<?php
requirePermiso('ADMIN', 'zonas');

if (isset($_POST['guardar_zona'])) {
    $cod = trim($_POST['cod'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $planta = trim($_POST['planta'] ?? '');
    $color = trim($_POST['color'] ?? '#3498db');
    $tipo = strtoupper(trim($_POST['tipo'] ?? 'NOR'));

    if (!in_array($tipo, ['NOR', 'MEO', 'SUR'], true)) {
        $tipo = 'NOR';
    }

    if ($cod === '' || $nom === '' || $planta === '') {
        alert('Código, nombre y planta son obligatorios', 0, 'N_zona');
        exit;
    }

    $stmtCod = $conn_mysql->prepare("SELECT id_zone FROM zonas WHERE cod = ?");
    $stmtCod->bind_param('s', $cod);
    $stmtCod->execute();
    $resCod = $stmtCod->get_result();

    if ($resCod && $resCod->num_rows > 0) {
        alert('El código de zona ya existe', 0, 'N_zona');
        exit;
    }

    $nextId = 1;
    $resMax = $conn_mysql->query("SELECT IFNULL(MAX(id_zone), 0) + 1 AS next_id FROM zonas");
    if ($resMax && $rowMax = $resMax->fetch_assoc()) {
        $nextId = (int)$rowMax['next_id'];
    }

    $idUserSession = $_SESSION['id_cliente'] ?? 0;
    $status = '1';

    $stmt = $conn_mysql->prepare("INSERT INTO zonas (id_zone, cod, nom, PLANTA, color, tipo, id_user, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssis', $nextId, $cod, $nom, $planta, $color, $tipo, $idUserSession, $status);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        logActivity('CREAR', 'Creó nueva zona: ' . $cod . ' (' . $tipo . ')');
        alert('Zona creada exitosamente', 1, 'zonas');
    } else {
        alert('No fue posible crear la zona', 0, 'N_zona');
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva Zona</h5>
            <a href="?p=zonas" class="btn btn-sm btn-danger rounded-3">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Código *</label>
                        <input type="text" name="cod" class="form-control" maxlength="10" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Planta *</label>
                        <input type="text" name="planta" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="NOR">NOR</option>
                            <option value="MEO">MEO</option>
                            <option value="SUR">SUR</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color *</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#3498db" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" name="guardar_zona" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar Zona
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
