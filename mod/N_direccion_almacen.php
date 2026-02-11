<?php
// Verificación de permisos - Backend
requirePermiso('ALMACENES_CREAR', 'almacenes');

// Obtener ID del Almacén
$id_almacen = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona

// Verificar que el almacén existe
if ($id_almacen) {
    $checkAlmacen = $conn_mysql->query("SELECT * FROM almacenes WHERE id_alma = '$id_almacen' AND status = '1'");
    if (mysqli_num_rows($checkAlmacen) == 0) {
        alert("Almacén no encontrado", 0, "almacenes");
        exit;
    }
    $almacenData = mysqli_fetch_array($checkAlmacen);
}

if (isset($_POST['guardarDireccion'])) {
    try {
        $DireccionData = [
            'cod_al' => $_POST['cod_al'] ?? '',
            'noma' => $_POST['noma'] ?? '',
            'atencion' => $_POST['atencion'] ?? '',
            'tel' => $_POST['tel'] ?? '',
            'email' => $_POST['email'] ?? '',
            'obs' => $_POST['obs'] ?? '',
            'id_alma' => $id_almacen
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

        // Verificar si el código ya existe
        $verCod = $conn_mysql->query("SELECT * FROM direcciones WHERE cod_al = '".$DireccionData['cod_al']."' AND status = '1'");
        if (mysqli_num_rows($verCod) > 0) {
            alert("El código de dirección ya existe", 0, "N_direccion_almacen&id=$id_almacen");
            exit;
        }

        // Insertar dirección
        $columns = implode(', ', array_keys($DireccionData));
        $placeholders = str_repeat('?,', count($DireccionData) - 1) . '?';
        $sql = "INSERT INTO direcciones ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);

        $types = str_repeat('s', count($DireccionData));
        $stmt->bind_param($types, ...array_values($DireccionData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Dirección registrada exitosamente", 1, "V_almacen&id=$id_almacen");
            logActivity('CREAR', 'Agregó nueva dirección al almacén '. $id_almacen);
        } else {
            alert("Error al registrar la dirección", 0, "N_direccion_almacen&id=$id_almacen");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_direccion_almacen&id=$id_almacen");
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Dirección para Almacén: <?= htmlspecialchars($almacenData['nombre'] ?? '') ?></h5>
            <a href="?p=V_almacen&id=<?= $id_almacen ?>">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="cod_al" class="form-label">Código <span id="resultadoCodigo"></span></label>
                        <input type="text" class="form-control" id="cod_al" name="cod_al" 
                               oninput="verificarCodigoDireccion()" required>
                    </div>
                    <div class="col-md-6">
                        <label for="noma" class="form-label">Nombre de Dirección</label>
                        <input type="text" class="form-control" id="noma" name="noma" required>
                    </div>
                    <div class="col-md-6">
                        <label for="atencion" class="form-label">Atención</label>
                        <input type="text" class="form-control" id="atencion" name="atencion" value="N/S">
                    </div>
                    <div class="col-md-3">
                        <label for="tel" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="tel" name="tel" value="55 5555 5555">
                    </div>
                    <div class="col-md-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="col-12">
                        <label for="obs" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="obs" name="obs" rows="3"></textarea>
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
                                <!-- Lista completa de estados de México -->
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

                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardarDireccion" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function verificarCodigoDireccion() {
    var codigo = document.getElementById('cod_al').value;
    
    var parametros = {
        "cod_al": codigo
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