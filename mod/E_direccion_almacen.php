<?php
// Obtener ID de la dirección
$id_direccion = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona

$direccion = [];
$almacen = [];

if ($id_direccion) {
    // Obtener datos de la dirección
    $sqlDireccion = "SELECT * FROM direcciones WHERE id_direc = ?";
    $stmtDireccion = $conn_mysql->prepare($sqlDireccion);
    $stmtDireccion->bind_param('i', $id_direccion);
    $stmtDireccion->execute();
    $resultDireccion = $stmtDireccion->get_result();
    $direccion = $resultDireccion->fetch_assoc();
    
    // Obtener datos del almacén asociado
    if (!empty($direccion['id_alma'])) {
        $sqlAlmacen = "SELECT * FROM almacenes WHERE id_alma = ?";
        $stmtAlmacen = $conn_mysql->prepare($sqlAlmacen);
        $stmtAlmacen->bind_param('i', $direccion['id_alma']);
        $stmtAlmacen->execute();
        $resultAlmacen = $stmtAlmacen->get_result();
        $almacen = $resultAlmacen->fetch_assoc();
    }
}

if (isset($_POST['actualizarDireccion'])) {
    try {
        $DireccionData = [
            'cod_al' => $_POST['cod_al'] ?? '',
            'noma' => $_POST['noma'] ?? '',
            'atencion' => $_POST['atencion'] ?? '',
            'tel' => $_POST['tel'] ?? '',
            'email' => $_POST['email'] ?? '',
            'obs' => $_POST['obs'] ?? ''
        ];

        // Solo para zonas MEO: agregar datos de dirección física
        if ($tipoZonaActual == 'MEO') {
            $DireccionData['calle'] = $_POST['calle'] ?? '';
            $DireccionData['c_postal'] = $_POST['c_postal'] ?? '';
            $DireccionData['numext'] = $_POST['numext'] ?? '';
            $DireccionData['numint'] = $_POST['numint'] ?? '';
            $DireccionData['pais'] = $_POST['pais'] ?? 'México';
            $DireccionData['estado'] = $_POST['estado'] ?? '';
            $DireccionData['colonia'] = $_POST['colonia'] ?? '';
        }

        // Verificar si el código ya existe (excluyendo el actual)
        if ($DireccionData['cod_al'] != $direccion['cod_al']) {
            $verCod = $conn_mysql->query("SELECT * FROM direcciones WHERE cod_al = '".$DireccionData['cod_al']."' AND status = '1' AND id_direc != '$id_direccion'");
            if (mysqli_num_rows($verCod) > 0) {
                alert("El código de dirección ya existe", 0, "E_direccion_almacen&id=$id_direccion");
                exit;
            }
        }

        // Actualizar dirección
        $setParts = [];
        $types = '';
        $values = [];

        foreach ($DireccionData as $key => $value) {
            $setParts[] = "$key = ?";
            $types .= 's';
            $values[] = $value;
        }

        $values[] = $id_direccion; // Para el WHERE
        
        $sql = "UPDATE direcciones SET " . implode(', ', $setParts) . " WHERE id_direc = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param($types . 'i', ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Dirección actualizada exitosamente", 1, "V_almacen&id=" . $direccion['id_alma']);
            logActivity('EDITAR', 'Editó la dirección ' . $id_direccion . ' del almacén ' . $direccion['id_alma']);
        } else {
            alert("No se realizaron cambios en la dirección", 1, "V_almacen&id=" . $direccion['id_alma']);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_direccion_almacen&id=$id_direccion");
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Dirección de Almacén: <?= htmlspecialchars($almacen['nombre'] ?? '') ?></h5>
            <a href="?p=V_almacen&id=<?= $direccion['id_alma'] ?? '' ?>">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Campos básicos de la dirección -->
                <div class="form-section">
                    <h5 class="section-header">Información de la Dirección</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cod_al" class="form-label">Código <span id="resultadoCodigo"></span></label>
                            <input type="text" class="form-control" id="cod_al" name="cod_al" 
                                   value="<?= htmlspecialchars($direccion['cod_al'] ?? '') ?>"
                                   oninput="verificarCodigoDireccionEdit()" required>
                        </div>
                        <div class="col-md-6">
                            <label for="noma" class="form-label">Nombre de Dirección</label>
                            <input type="text" class="form-control" id="noma" name="noma" 
                                   value="<?= htmlspecialchars($direccion['noma'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="atencion" class="form-label">Atención</label>
                            <input type="text" class="form-control" id="atencion" name="atencion" 
                                   value="<?= htmlspecialchars($direccion['atencion'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="tel" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="tel" name="tel" 
                                   value="<?= htmlspecialchars($direccion['tel'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($direccion['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="obs" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="obs" name="obs" rows="3"><?= htmlspecialchars($direccion['obs'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE DIRECCIÓN FÍSICA (solo para MEO) -->
                <?php if ($tipoZonaActual == 'MEO'): ?>
                <div class="form-section mt-4">
                    <h5 class="section-header text-info">
                        <i class="bi bi-geo-alt me-2"></i> Dirección Física
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="calle" class="form-label">Calle</label>
                            <input name="calle" type="text" class="form-control" id="calle" 
                                   value="<?= htmlspecialchars($direccion['calle'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numext" class="form-label">Número Exterior</label>
                            <input name="numext" type="text" class="form-control" id="numext" 
                                   value="<?= htmlspecialchars($direccion['numext'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numint" class="form-label">Número Interior</label>
                            <input name="numint" type="text" class="form-control" id="numint" 
                                   value="<?= htmlspecialchars($direccion['numint'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="colonia" class="form-label">Colonia</label>
                            <input name="colonia" type="text" class="form-control" id="colonia" 
                                   value="<?= htmlspecialchars($direccion['colonia'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="c_postal" class="form-label">Código Postal</label>
                            <input name="c_postal" type="text" class="form-control" id="c_postal" maxlength="5"
                                   value="<?= htmlspecialchars($direccion['c_postal'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" class="form-select" id="estado">
                                <option value="">Seleccionar estado...</option>
                                <option value="Aguascalientes" <?= ($direccion['estado'] ?? '') == 'Aguascalientes' ? 'selected' : '' ?>>Aguascalientes</option>
                                <option value="Baja California" <?= ($direccion['estado'] ?? '') == 'Baja California' ? 'selected' : '' ?>>Baja California</option>
                                <option value="Baja California Sur" <?= ($direccion['estado'] ?? '') == 'Baja California Sur' ? 'selected' : '' ?>>Baja California Sur</option>
                                <option value="Campeche" <?= ($direccion['estado'] ?? '') == 'Campeche' ? 'selected' : '' ?>>Campeche</option>
                                <option value="Chiapas" <?= ($direccion['estado'] ?? '') == 'Chiapas' ? 'selected' : '' ?>>Chiapas</option>
                                <option value="Chihuahua" <?= ($direccion['estado'] ?? '') == 'Chihuahua' ? 'selected' : '' ?>>Chihuahua</option>
                                <option value="Ciudad de México" <?= ($direccion['estado'] ?? '') == 'Ciudad de México' ? 'selected' : '' ?>>Ciudad de México</option>
                                <option value="Coahuila" <?= ($direccion['estado'] ?? '') == 'Coahuila' ? 'selected' : '' ?>>Coahuila</option>
                                <option value="Colima" <?= ($direccion['estado'] ?? '') == 'Colima' ? 'selected' : '' ?>>Colima</option>
                                <option value="Durango" <?= ($direccion['estado'] ?? '') == 'Durango' ? 'selected' : '' ?>>Durango</option>
                                <option value="Estado de México" <?= ($direccion['estado'] ?? '') == 'Estado de México' ? 'selected' : '' ?>>Estado de México</option>
                                <option value="Guanajuato" <?= ($direccion['estado'] ?? '') == 'Guanajuato' ? 'selected' : '' ?>>Guanajuato</option>
                                <option value="Guerrero" <?= ($direccion['estado'] ?? '') == 'Guerrero' ? 'selected' : '' ?>>Guerrero</option>
                                <option value="Hidalgo" <?= ($direccion['estado'] ?? '') == 'Hidalgo' ? 'selected' : '' ?>>Hidalgo</option>
                                <option value="Jalisco" <?= ($direccion['estado'] ?? '') == 'Jalisco' ? 'selected' : '' ?>>Jalisco</option>
                                <option value="Michoacán" <?= ($direccion['estado'] ?? '') == 'Michoacán' ? 'selected' : '' ?>>Michoacán</option>
                                <option value="Morelos" <?= ($direccion['estado'] ?? '') == 'Morelos' ? 'selected' : '' ?>>Morelos</option>
                                <option value="Nayarit" <?= ($direccion['estado'] ?? '') == 'Nayarit' ? 'selected' : '' ?>>Nayarit</option>
                                <option value="Nuevo León" <?= ($direccion['estado'] ?? '') == 'Nuevo León' ? 'selected' : '' ?>>Nuevo León</option>
                                <option value="Oaxaca" <?= ($direccion['estado'] ?? '') == 'Oaxaca' ? 'selected' : '' ?>>Oaxaca</option>
                                <option value="Puebla" <?= ($direccion['estado'] ?? '') == 'Puebla' ? 'selected' : '' ?>>Puebla</option>
                                <option value="Querétaro" <?= ($direccion['estado'] ?? '') == 'Querétaro' ? 'selected' : '' ?>>Querétaro</option>
                                <option value="Quintana Roo" <?= ($direccion['estado'] ?? '') == 'Quintana Roo' ? 'selected' : '' ?>>Quintana Roo</option>
                                <option value="San Luis Potosí" <?= ($direccion['estado'] ?? '') == 'San Luis Potosí' ? 'selected' : '' ?>>San Luis Potosí</option>
                                <option value="Sinaloa" <?= ($direccion['estado'] ?? '') == 'Sinaloa' ? 'selected' : '' ?>>Sinaloa</option>
                                <option value="Sonora" <?= ($direccion['estado'] ?? '') == 'Sonora' ? 'selected' : '' ?>>Sonora</option>
                                <option value="Tabasco" <?= ($direccion['estado'] ?? '') == 'Tabasco' ? 'selected' : '' ?>>Tabasco</option>
                                <option value="Tamaulipas" <?= ($direccion['estado'] ?? '') == 'Tamaulipas' ? 'selected' : '' ?>>Tamaulipas</option>
                                <option value="Tlaxcala" <?= ($direccion['estado'] ?? '') == 'Tlaxcala' ? 'selected' : '' ?>>Tlaxcala</option>
                                <option value="Veracruz" <?= ($direccion['estado'] ?? '') == 'Veracruz' ? 'selected' : '' ?>>Veracruz</option>
                                <option value="Yucatán" <?= ($direccion['estado'] ?? '') == 'Yucatán' ? 'selected' : '' ?>>Yucatán</option>
                                <option value="Zacatecas" <?= ($direccion['estado'] ?? '') == 'Zacatecas' ? 'selected' : '' ?>>Zacatecas</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pais" class="form-label">País</label>
                            <input name="pais" type="text" class="form-control" id="pais" 
                                   value="<?= htmlspecialchars($direccion['pais'] ?? 'México') ?>" readonly>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="actualizarDireccion" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function verificarCodigoDireccionEdit() {
    var codigo = document.getElementById('cod_al').value;
    var id_actual = '<?= $id_direccion ?>';
    
    var parametros = {
        "cod_al_edit": codigo,
        "id_actual": id_actual
    };

    $.ajax({
        data: parametros,
        url: 'cd_php.php',
        type: 'POST',
        beforeSend: function () {
            $('#resultadoCodigo').html("<span class='badge text-bg-secondary'>Verificando...</span>");
        },
        success: function (mensaje) {
            $('#resultadoCodigo').html(mensaje);
        }
    });
}
</script>