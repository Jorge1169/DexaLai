<?php
// Verificación de permisos - Backend
requirePermiso('CLIENTES_EDITAR', 'clientes');

// Obtener ID del Cliente
$id = clear($_GET['id'] ?? '');

// Obtener datos del cliente
$cliente = [];
$direccion = [];
if ($id) {
    // Obtener datos del cliente
    $sqlCliente = "SELECT * FROM clientes WHERE id_cli = ?";
    $stmtCliente = $conn_mysql->prepare($sqlCliente);
    $stmtCliente->bind_param('i', $id);
    $stmtCliente->execute();
    $resultCliente = $stmtCliente->get_result();
    $cliente = $resultCliente->fetch_assoc();

    // Obtener datos de dirección (solo para visualización)
    $sqlDireccion = "SELECT * FROM direcciones WHERE id_us = ?";
    $stmtDireccion = $conn_mysql->prepare($sqlDireccion);
    $stmtDireccion->bind_param('i', $id);
    $stmtDireccion->execute();
    $resultDireccion = $stmtDireccion->get_result();
    $direccion = $resultDireccion->fetch_assoc();
}

if (isset($_POST['guardarEdicion'])) {
    try {
        $ClienteData = [
            'rs' => $_POST['rs'] ?? '',
            'rfc' => $_POST['rfc'] ?? '',
            'tpersona' => $_POST['tpersona'] ?? '',
            'obs' => $_POST['obsP'] ?? '',
            'id_user' => $idUser,
            'id_cli' => $id,
            'fac_rem' => $fac_rem,
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Actualizar cliente
        $setParts = [];
        $types = '';
        $values = [];

        foreach ($ClienteData as $key => $value) {
            if ($key !== 'id_cli') {
                $setParts[] = "$key = ?";
                $types .= 's';
                $values[] = $value;
            }
        }

        $values[] = $id; // Para el WHERE
        
        $sql = "UPDATE clientes SET " . implode(', ', $setParts) . " WHERE id_cli = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param($types . 'i', ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Cliente actualizado exitosamente", 1, "V_cliente&id=$id");
            logActivity('EDITAR', 'Edito el cliente ' . $id);
        } else {
            alert("No se realizaron cambios en el cliente", 1, "V_cliente&id=$id");
            logActivity('EDITAR', 'No hizo cambios en el cliente ' . $id);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_cliente&id=$id");
        logActivity('EDITAR', 'No pudo editar el cliente ' . $id);
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Cliente: <?= htmlspecialchars($cliente['nombre'] ?? '') ?></h5>
            <a href="?p=clientes">
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
                            value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input name="cod" type="text" class="form-control" id="codigo" 
                            value="<?= htmlspecialchars($cliente['cod'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="razonSocial" class="form-label">Razón Social</label>
                            <input name="rs" type="text" class="form-control" id="razonSocial"
                            value="<?= htmlspecialchars($cliente['rs'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="rfc" class="form-label">RFC</label>
                            <input name="rfc" type="text" class="form-control" id="rfc"
                            value="<?= htmlspecialchars($cliente['rfc'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="tipoPersona" class="form-label">Tipo de Persona</label>
                            <select class="form-select" name="tpersona" id="tipoPersona">
                                <option value="fisica" <?= ($cliente['tpersona'] ?? '') == 'fisica' ? 'selected' : '' ?>>Física</option>
                                <option value="moral" <?= ($cliente['tpersona'] ?? '') == 'moral' ? 'selected' : '' ?>>Moral</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="TipoEvidencia" class="form-label">Tipo de evidencia</label>
                            <select class="form-select" name="fac_rem" id="tipoPersona">
                                <option value="FAC" <?= ($cliente['fac_rem'] ?? '') == 'FAC' ? 'selected' : '' ?>>Factura</option>
                                <option value="REM" <?= ($cliente['fac_rem'] ?? '') == 'REM' ? 'selected' : '' ?>>Remisión</option>
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
                                        <option value="<?=$zona1['id_zone']?>" <?= ($cliente['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
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
                        <textarea class="form-control" name="obsP" id="observaciones" rows="3"><?= htmlspecialchars($cliente['obs'] ?? '') ?></textarea>
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