<?php
// Obtener el ID del transportista a editar
$id_transp = $_GET['id'] ?? 0;

// Consultar los datos del transportista
$transpQuery = $conn_mysql->prepare("SELECT * FROM transportes WHERE id_transp = ?");
$transpQuery->bind_param('i', $id_transp);
$transpQuery->execute();
$transpData = $transpQuery->get_result()->fetch_assoc();

if (!$transpData) {
    alert("Transportista no encontrado", 0, "transportes");
    exit();
}

// Procesar el formulario de actualización
if (isset($_POST['guardar01'])) {
    try {
        $TransportistaData = [
            'placas' => $_POST['placas'] ?? '',
            'razon_so' => $_POST['razon_so'] ?? '',
            'linea' => $_POST['linea'] ?? '',
            'tipo' => $_POST['tipo'] ?? '',
            'chofer' => $_POST['chofer'] ?? '',
            'placas_caja' => $_POST['placas_caja'] ?? '',
            'correo' => $_POST['correo'] ?? '',
            'id_user' => $idUser,
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Actualizar transportista con MySQLi
        $setClause = implode(' = ?, ', array_keys($TransportistaData)) . ' = ?';
        $sql = "UPDATE transportes SET $setClause WHERE id_transp = ?";
        $stmt = $conn_mysql->prepare($sql);
        
        // Pasar los valores en el orden correcto (datos + id)
        $values = array_values($TransportistaData);
        $values[] = $id_transp;
        
        $types = str_repeat('s', count($TransportistaData)) . 'i'; // 's' para strings, 'i' para id
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Transportista actualizado exitosamente", 1, "transportes");
            logActivity('EDITAR', 'Se actualizo el fletero '. $id_transp);
        } else {
            alert("No se realizaron cambios en el transportista", 1, "transportes");
            logActivity('EDITAR', 'No se realizo cambios en el fletero '. $id_transp);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_transportista&id=$id_transp");
        logActivity('EDITAR', 'Error al intentar actualizar el fletero '. $id_transp);
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Transportista</h5>
            <a href="?p=transportes">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Sección de información básica del transportista -->
                <div class="form-section">
                    <h5 class="section-header">Información del Transportista</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="placas" class="form-label">ID Fletero</label>
                            <input name="placas" type="text" class="form-control" id="placas" 
                                   value="<?= htmlspecialchars($transpData['placas'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="linea" class="form-label">Línea</label>
                            <input name="linea" type="text" class="form-control" id="linea" 
                                   value="<?= htmlspecialchars($transpData['linea'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="razon_so" class="form-label">Razon Social</label>
                            <input name="razon_so" type="text" class="form-control" id="razon_so" value="<?= htmlspecialchars($transpData['razon_so'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="CAMIONETA 3 1/2" <?= ($transpData['tipo'] ?? '') == 'CAMIONETA 3 1/2' ? 'selected' : '' ?>>CAMIONETA 3 1/2</option>
                                <option value="TRAILER" <?= ($transpData['tipo'] ?? '') == 'TRAILER' ? 'selected' : '' ?>>TRAILER</option>
                                <option value="TORTON" <?= ($transpData['tipo'] ?? 'TORTON') == 'TORTON' ? 'selected' : '' ?>>TORTON</option>
                                <option value="CAMIONETA CHICA" <?= ($transpData['tipo'] ?? '') == 'CAMIONETA CHICA' ? 'selected' : '' ?>>CAMIONETA CHICA</option>
                                <option value="OTRO" <?= ($transpData['tipo'] ?? '') == 'OTRO' ? 'selected' : '' ?>>OTRO</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="chofer" class="form-label">Chofer</label>
                            <input name="chofer" type="text" class="form-control" id="chofer" 
                                   value="<?= htmlspecialchars($transpData['chofer'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="correo" class="form-label">Correo del chofer</label>
                            <input name="correo" type="email" class="form-control" id="correo" 
                                   value="<?= htmlspecialchars($transpData['correo'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="placas_caja" class="form-label">Placas</label>
                            <input name="placas_caja" type="text" class="form-control" id="placas_caja"
                                   value="<?= htmlspecialchars($transpData['placas_caja'] ?? '') ?>">
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
                                        <option value="<?=$zona1['id_zone']?>" <?= ($transpData['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
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


                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>