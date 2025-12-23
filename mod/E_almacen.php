<?php
// Obtener ID del Almacén
$id = clear($_GET['id'] ?? '');

// Obtener datos del almacén
$almacen = [];

if ($id) {
    // Obtener datos del almacén
    $sqlAlmacen = "SELECT * FROM almacenes WHERE id_alma = ?";
    $stmtAlmacen = $conn_mysql->prepare($sqlAlmacen);
    $stmtAlmacen->bind_param('i', $id);
    $stmtAlmacen->execute();
    $resultAlmacen = $stmtAlmacen->get_result();
    $almacen = $resultAlmacen->fetch_assoc();
}

if (isset($_POST['guardarEdicion'])) {
    try {
        $AlmacenData = [
            'rs' => $_POST['rs'] ?? '',
            'rfc' => $_POST['rfc'] ?? '',
            'tpersona' => $_POST['tpersona'] ?? '',
            'obs' => $_POST['obsP'] ?? '',
            'id_user' => $idUser,
            'fac_rem' => $_POST['fac_rem'] ?? 'FAC',
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Actualizar almacén
        $setParts = [];
        $types = '';
        $values = [];

        foreach ($AlmacenData as $key => $value) {
            $setParts[] = "$key = ?";
            $types .= 's';
            $values[] = $value;
        }

        $values[] = $id; // Para el WHERE
        
        $sql = "UPDATE almacenes SET " . implode(', ', $setParts) . " WHERE id_alma = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param($types . 'i', ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Almacén actualizado exitosamente", 1, "V_almacen&id=$id");
            logActivity('EDITAR', 'Editó el almacén ' . $id);
        } else {
            alert("No se realizaron cambios en el almacén", 1, "V_almacen&id=$id");
            logActivity('EDITAR', 'No hizo cambios en el almacén ' . $id);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_almacen&id=$id");
        logActivity('EDITAR', 'No pudo editar el almacén ' . $id);
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Almacén: <?= htmlspecialchars($almacen['nombre'] ?? '') ?></h5>
            <a href="?p=almacenes">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Sección de información básica -->
                <div class="form-section">
                    <h5 class="section-header">Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" 
                                   value="<?= htmlspecialchars($almacen['nombre'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input name="cod" type="text" class="form-control" id="codigo" 
                                   value="<?= htmlspecialchars($almacen['cod'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="razonSocial" class="form-label">Razón Social</label>
                            <input name="rs" type="text" class="form-control" id="razonSocial"
                                   value="<?= htmlspecialchars($almacen['rs'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="rfc" class="form-label">RFC</label>
                            <input name="rfc" type="text" class="form-control" id="rfc"
                                   value="<?= htmlspecialchars($almacen['rfc'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="tipoPersona" class="form-label">Tipo de Persona</label>
                            <select class="form-select" name="tpersona" id="tipoPersona">
                                <option value="fisica" <?= ($almacen['tpersona'] ?? '') == 'fisica' ? 'selected' : '' ?>>Física</option>
                                <option value="moral" <?= ($almacen['tpersona'] ?? '') == 'moral' ? 'selected' : '' ?>>Moral</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="TipoEvidencia" class="form-label">Tipo de evidencia</label>
                            <select class="form-select" name="fac_rem" id="tipoEvidencia">
                                <option value="FAC" <?= ($almacen['fac_rem'] ?? '') == 'FAC' ? 'selected' : '' ?>>Factura</option>
                                <option value="REM" <?= ($almacen['fac_rem'] ?? '') == 'REM' ? 'selected' : '' ?>>Remisión</option>
                            </select>
                        </div>
                        
                        <?php
                        if ($zona_seleccionada == '0') {
                            ?>
                            <div class="col-md-4">
                                <label for="zona" class="form-label">Zona</label>
                                <select class="form-select" name="zona" id="zona">
                                    <?php
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                    while ($zona1 = mysqli_fetch_array($zona0)) {
                                        ?>
                                        <option value="<?=$zona1['id_zone']?>" <?= ($almacen['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>>
                                            <?=$zona1['nom']?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Sección de observaciones -->
                <div class="form-section">
                    <h5 class="section-header">Observaciones</h5>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" name="obsP" id="observaciones" rows="3"><?= htmlspecialchars($almacen['obs'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardarEdicion" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>