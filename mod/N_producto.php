<?php
if (isset($_POST['guardar01'])) {
    try {
        $ProductoData = [
            'nom_pro' => $_POST['nom_pro'] ?? '',
            'cod' => $_POST['cod'] ?? '',
            'lin' => $_POST['lin'] ?? '',
            'fecha' => date('Y-m-d H:i:s'), // Fecha actual automática
            'id_user' => $idUser,
            'status' => 1,
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Insertar producto con MySQLi
        $columns = implode(', ', array_keys($ProductoData));
        $placeholders = str_repeat('?,', count($ProductoData) - 1) . '?';
        $sql = "INSERT INTO productos ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($ProductoData)); // 's' para string
        $stmt->bind_param($types, ...array_values($ProductoData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener ID del producto recién insertado
            $idProducto = $stmt->insert_id;
            alert("Producto registrado exitosamente", 1, "productos");
            logActivity('CREAR', 'Dio de alta una nuevo producto');
        } else {
            alert("Error al registrar el producto", 0, "N_producto");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_producto");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Producto</h5>
            <a href="?p=productos">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Sección de información básica del producto -->
                <div class="form-section">
                    <h5 class="section-header">Información del Producto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cod" class="form-label">Código <span id="resulpostal00"></span></label>
                            <input name="cod" type="text" class="form-control" id="cod" oninput="codigo1()" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nom_pro" class="form-label">Nombre del Producto</label>
                            <input name="nom_pro" type="text" class="form-control" id="nom_pro" required>
                        </div>
                        <div class="col-md-8">
                            <label for="lin" class="form-label">Línea</label>
                            <input name="lin" type="text" class="form-control" id="lin" required>
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
        var cod = document.getElementById('cod').value;

        var parametros = {
            "codProd": cod,
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