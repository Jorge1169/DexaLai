<?php
// N_direccion.php - Nueva Bodega
// Verificación de permisos - Backend
requirePermiso('CLIENTES_EDITAR', 'clientes');

$id_cliente = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona

$cliente = [];
if ($id_cliente) {
    $sqlCliente = "SELECT cod, nombre, rs FROM clientes WHERE id_cli = ?";
    $stmtCliente = $conn_mysql->prepare($sqlCliente);
    $stmtCliente->bind_param('i', $id_cliente);
    $stmtCliente->execute();
    $resultCliente = $stmtCliente->get_result();
    $cliente = $resultCliente->fetch_assoc();
}

if (isset($_POST['guardarDireccion'])) {
    $cod_al1 = $_POST['cod_al'] ?? '';
    $AlertCo02 = $conn_mysql->query("SELECT * FROM direcciones WHERE cod_al = '$cod_al1' AND status = '1'");
    $AlertCo03 = mysqli_fetch_array($AlertCo02);

    if (!empty($AlertCo03['id_direc'])) {
        alert("Código ya ocupado", 2, "N_direccion&id=$id_cliente");  
    } else {
        try {
            // Datos básicos de la bodega
            $BodegaData = [
                'cod_al' => $_POST['cod_al'] ?? '',
                'noma' => $_POST['noma'] ?? '',
                'atencion' => $_POST['atencion'] ?? '',
                'tel' => $_POST['tel'] ?? '',
                'email' => $_POST['email'] ?? '',
                'obs' => $_POST['obs'] ?? '',
                'id_us' => $id_cliente
            ];

            // Solo para zonas MEO: agregar datos de dirección física
            if ($tipoZonaActual == 'MEO') {
                $BodegaData['calle'] = $_POST['calle'] ?? '';
                $BodegaData['c_postal'] = $_POST['c_postal'] ?? '';
                $BodegaData['numext'] = $_POST['numext'] ?? '';
                $BodegaData['numint'] = $_POST['numint'] ?? '';
                $BodegaData['pais'] = $_POST['pais'] ?? 'México';
                $BodegaData['estado'] = $_POST['estado'] ?? '';
                $BodegaData['colonia'] = $_POST['colonia'] ?? '';
            }

            // Insertar bodega
            $columns = implode(', ', array_keys($BodegaData));
            $placeholders = str_repeat('?,', count($BodegaData) - 1) . '?';
            $sql = "INSERT INTO direcciones ($columns) VALUES ($placeholders)";
            $stmt = $conn_mysql->prepare($sql);

            $types = str_repeat('s', count($BodegaData));
            $stmt->bind_param($types, ...array_values($BodegaData));
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                alert("Bodega agregada exitosamente", 1, "V_cliente&id=$id_cliente");
                logActivity('CREAR', 'Dio de alta una nueva bodega para el cliente '. $id_cliente);
            } else {
                alert("Error al registrar la bodega", 0, "N_direccion&id=$id_cliente");
            }
        } catch (mysqli_sql_exception $e) {
            alert("Error: " . $e->getMessage(), 0, "N_direccion&id=$id_cliente");
        }
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Bodega para: <?= htmlspecialchars($cliente['nombre'] ?? '') ?></h5>
            <a href="?p=V_cliente&id=<?= $id_cliente ?>">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Información del cliente (solo lectura) -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Código Cliente</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['cod'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Razón Social</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['rs'] ?? '') ?>" readonly>
                    </div>
                </div>

                <!-- Campos de la bodega -->
                <div class="form-section">
                    <h5 class="section-header">Información de la Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cod_al" class="form-label">Código de Bodega <span id="resulpostal00"></span></label>
                            <input name="cod_al" type="text" class="form-control" id="cod_al" oninput="codigo1()">
                        </div>
                        <div class="col-md-6">
                            <label for="noma" class="form-label">Nombre de Bodega</label>
                            <input name="noma" type="text" class="form-control" id="noma">
                        </div>
                        <div class="col-md-6">
                            <label for="atencion" class="form-label">Atención</label>
                            <input name="atencion" type="text" class="form-control" value="N/S" id="atencion">
                        </div>
                        <div class="col-md-3">
                            <label for="tel" class="form-label">Teléfono</label>
                            <input name="tel" type="tel" class="form-control" value="55 5555 5555" id="tel">
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" id="email">
                        </div>
                        <div class="col-12">
                            <label for="obs" class="form-label">Observaciones</label>
                            <textarea name="obs" class="form-control" id="obs" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE DIRECCIÓN FÍSICA (solo para MEO) -->
                <?php if ($tipoZonaActual == 'MEO'): ?>
                <div class="form-section mt-4">
                    <h5 class="section-header text-info">
                        <i class="bi bi-geo-alt me-2"></i> Dirección Física (Opcional)
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="calle" class="form-label">Calle</label>
                            <input name="calle" type="text" class="form-control" id="calle">
                        </div>
                        <div class="col-md-3">
                            <label for="numext" class="form-label">Número Exterior</label>
                            <input name="numext" type="text" class="form-control" id="numext">
                        </div>
                        <div class="col-md-3">
                            <label for="numint" class="form-label">Número Interior</label>
                            <input name="numint" type="text" class="form-control" id="numint">
                        </div>
                        <div class="col-md-4">
                            <label for="colonia" class="form-label">Colonia</label>
                            <input name="colonia" type="text" class="form-control" id="colonia">
                        </div>
                        <div class="col-md-4">
                            <label for="c_postal" class="form-label">Código Postal</label>
                            <input name="c_postal" type="text" class="form-control" id="c_postal" maxlength="5">
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" class="form-select" id="estado">
                                <option value="">Seleccionar estado...</option>
                                <option value="Aguascalientes">Aguascalientes</option>
                                <option value="Baja California">Baja California</option>
                                <option value="Baja California Sur">Baja California Sur</option>
                                <option value="Campeche">Campeche</option>
                                <option value="Chiapas">Chiapas</option>
                                <option value="Chihuahua">Chihuahua</option>
                                <option value="Ciudad de México">Ciudad de México</option>
                                <option value="Coahuila">Coahuila</option>
                                <option value="Colima">Colima</option>
                                <option value="Durango">Durango</option>
                                <option value="Estado de México">Estado de México</option>
                                <option value="Guanajuato">Guanajuato</option>
                                <option value="Guerrero">Guerrero</option>
                                <option value="Hidalgo">Hidalgo</option>
                                <option value="Jalisco">Jalisco</option>
                                <option value="Michoacán">Michoacán</option>
                                <option value="Morelos">Morelos</option>
                                <option value="Nayarit">Nayarit</option>
                                <option value="Nuevo León">Nuevo León</option>
                                <option value="Oaxaca">Oaxaca</option>
                                <option value="Puebla">Puebla</option>
                                <option value="Querétaro">Querétaro</option>
                                <option value="Quintana Roo">Quintana Roo</option>
                                <option value="San Luis Potosí">San Luis Potosí</option>
                                <option value="Sinaloa">Sinaloa</option>
                                <option value="Sonora">Sonora</option>
                                <option value="Tabasco">Tabasco</option>
                                <option value="Tamaulipas">Tamaulipas</option>
                                <option value="Tlaxcala">Tlaxcala</option>
                                <option value="Veracruz">Veracruz</option>
                                <option value="Yucatán">Yucatán</option>
                                <option value="Zacatecas">Zacatecas</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pais" class="form-label">País</label>
                            <input name="pais" type="text" class="form-control" id="pais" value="México" readonly>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardarDireccion" class="btn btn-primary">Guardar Bodega</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function codigo1() {
        var cod_al = document.getElementById('cod_al').value;
        var parametros = {"cod_al": cod_al};
        
        $.ajax({
            data: parametros,
            url: 'cd_php.php',
            type: 'POST',
            beforeSend: function () {
                $('#resulpostal00').html("");
            },
            success: function (mensaje) {
                $('#resulpostal00').html(mensaje);
            }
        });
    }
</script>