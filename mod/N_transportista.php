<?php
if (isset($_POST['guardar01'])) {
    try {

        $ID_FLETERO = $_POST['placas'];
        $zona_transportista = $_POST['zona'] ?? $zona_seleccionada;

        $Verpla0 = $conn_mysql->query("SELECT * FROM transportes where placas = '$ID_FLETERO' AND zona = '$zona_transportista' AND status = '1'");
        $Verpla1 = mysqli_fetch_array($Verpla0);
        if (!empty($Verpla1['id_transp'])) {
            alert("El id del fletero ya existe en esta zona, favor de ocupar otro", 0, "N_transportista"); 
            exit;
        }
        
        $TransportistaData = [
         'placas' => $_POST['placas'] ?? '',
         'razon_so' => $_POST['razon_so'] ?? '',
         'linea' => $_POST['linea'] ?? '',
         'tipo' => $_POST['tipo'] ?? '',
         'chofer' => $_POST['chofer'] ?? '',
         'placas_caja' => $_POST['placas_caja'] ?? '',
         'correo' => $_POST['correo'] ?? '',
         'id_user' => $idUser,
         'status' => 1,
         'zona' => $_POST['zona'] ?? $zona_seleccionada 
     ];

        // Insertar transportista con MySQLi
     $columns = implode(', ', array_keys($TransportistaData));
     $placeholders = str_repeat('?,', count($TransportistaData) - 1) . '?';
     $sql = "INSERT INTO transportes ($columns) VALUES ($placeholders)";
     $stmt = $conn_mysql->prepare($sql);

        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($TransportistaData)); // 's' para string
        $stmt->bind_param($types, ...array_values($TransportistaData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener ID del transportista recién insertado
            $idTransportista = $stmt->insert_id;
            alert("Transportista registrado exitosamente", 1, "transportes");
            logActivity('CREAR', 'Dio de alta un nuevo fletero');
        } else {
            alert("Error al registrar el transportista", 0, "N_transportista");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_transportista");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Transportista</h5>
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
                            <label for="placas" class="form-label">ID flete <span id="resulpostal00"></span></label>
                            <input name="placas" type="text" class="form-control" id="placas" oninput="codigo1()" required>
                        </div>
                        <div class="col-md-4">
                            <label for="linea" class="form-label">Línea</label>
                            <input name="linea" type="text" class="form-control" id="linea" required>
                        </div>
                        <div class="col-md-4">
                            <label for="razon_so" class="form-label">Razon Social</label>
                            <input name="razon_so" type="text" class="form-control" id="razon_so" required>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                               <option value="CAMIONETA 3 1/2">CAMIONETA 3 1/2</option>
                               <option value="TRAILER">TRAILER</option>
                               <option value="TORTON">TORTON</option>
                               <option value="CAMIONETA CHICA">CAMIONETA CHICA</option>
                               <option value="OTRO">OTRO</option>
                           </select>
                       </div>
                       <div class="col-md-4">
                        <label for="chofer" class="form-label">Chofer</label>
                        <input name="chofer" type="text" class="form-control" id="chofer" value="N/S">
                    </div>
                    <div class="col-md-4">
                        <label for="correo" class="form-label">Correo del chofer</label>
                        <input name="correo" type="email" class="form-control" id="correo">
                    </div>
                    <div class="col-md-4">
                        <label for="placas_caja" class="form-label">Placas</label>
                        <input name="placas_caja" type="text" class="form-control" id="placas_caja" value="N/S">
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
                            <option value="<?=$zona1['id_zone']?>" <?= ($zona_seleccionada ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
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
        <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
    </div>
</form>
</div>
</div>
</div>
<script>
function codigo1() {
    var placas = document.getElementById('placas').value;
    var zona = document.getElementById('zona') ? document.getElementById('zona').value : '<?php echo $zona_seleccionada; ?>';

    var parametros = {
        "placas": placas,
        "zona": zona
    };

    console.log(parametros);

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