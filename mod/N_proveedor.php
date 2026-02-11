<?php
// Verificación de permisos - Backend
requirePermiso('PROVEEDORES_CREAR', 'proveedores');

if (isset($_POST['guardar01'])) {
    try {
        $CodProveedor = $_POST['cod'] ?? '';
        $zona_proveedor = $_POST['zona'] ?? $zona_seleccionada;

        // Verificar si el código del proveedor ya existe EN LA MISMA ZONA
        $VerProv0 = $conn_mysql->query("SELECT * FROM proveedores WHERE cod = '$CodProveedor' AND zona = '$zona_proveedor' AND status = '1'");
        $VerProv1 = mysqli_fetch_array($VerProv0);
        if (!empty($VerProv1['id_prov'])) {
            alert("El código del proveedor ya existe en esta zona, favor de ocupar otro", 0, "N_proveedor"); 
            exit;
        }
        $ProveedorData = [
            'nombre' => $_POST['nombre'] ?? '',
            'cod' => $_POST['cod'] ?? '',
            'rs' => $_POST['rs'] ?? '',
            'rfc' => $_POST['rfc'] ?? '',
            'tpersona' => $_POST['tpersona'] ?? '',
            'obs' => $_POST['obsP'] ?? '',
            'id_user' => $idUser,
            'zona' => $zona_proveedor
        ];

        // Insertar proveedor con MySQLi
        $columns = implode(', ', array_keys($ProveedorData));
        $placeholders = str_repeat('?,', count($ProveedorData) - 1) . '?';
        $sql = "INSERT INTO proveedores ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($ProveedorData)); // 's' para string
        $stmt->bind_param($types, ...array_values($ProveedorData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener ID del proveedor recién insertado
            $sqlSelect = "SELECT id_prov FROM proveedores WHERE nombre = ? AND cod = ?";
            $stmtSelect = $conn_mysql->prepare($sqlSelect);
            $stmtSelect->bind_param('ss', $_POST['nombre'], $_POST['cod']);
            $stmtSelect->execute();
            $result = $stmtSelect->get_result();
            $ProvN0 = $result->fetch_assoc();
            $idProveedor = $ProvN0['id_prov'];

            // Insertar bodega
            $BodegaData = [
                'cod_al' => $_POST['cod_al'] ?? '',
                'noma' => $_POST['noma'] ?? '',
                'atencion' => $_POST['atencion'] ?? '',
                'tel' => $_POST['tel'] ?? '',
                'email' => $_POST['email'] ?? '',
                'obs' => $_POST['obsD'] ?? '',
                'id_prov' => $idProveedor
            ];

            $columnsD = implode(', ', array_keys($BodegaData));
            $placeholdersD = str_repeat('?,', count($BodegaData) - 1) . '?';
            $sqlD = "INSERT INTO direcciones ($columnsD) VALUES ($placeholdersD)";
            $stmtD = $conn_mysql->prepare($sqlD);
            
            $typesD = str_repeat('s', count($BodegaData));
            $stmtD->bind_param($typesD, ...array_values($BodegaData));
            $stmtD->execute();

            if ($stmtD->affected_rows > 0) {
                alert("Proveedor $CodProveedor registrado exitosamente", 1, "proveedores");
                logActivity('CREAR', 'Dio de alta una nuevo proveedor '. $CodProveedor);
            } else {
                alert("Error al registrar la bodega", 0, "N_proveedor");
            }
        } else {
            alert("Error al registrar el proveedor", 0, "N_proveedor");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_proveedor");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Proveedor</h5>
            <a href="?p=proveedores">
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
                            <label for="codigo" class="form-label">Código <span id="resulpostal00"></span></label>
                            <input name="cod" type="text" class="form-control" id="codigo" oninput="codigo1()">
                        </div>
                        <div class="col-md-4">
                            <label for="razonSocial" class="form-label">Razón Social</label>
                            <input name="rs" type="text" class="form-control" id="razonSocial">
                        </div>
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="rfc" class="form-label">RFC</label>
                            <input name="rfc" type="text" class="form-control" id="rfc">
                        </div>
                        <div class="col-md-4">
                            <label for="tipoPersona" class="form-label">Tipo de Persona</label>
                            <select class="form-select" name="tpersona" id="tipoPersona">
                                <option selected disabled>Seleccionar...</option>
                                <option value="fisica">Física</option>
                                <option value="moral">Moral</option>
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

        <!-- Sección de bodega -->
        <div class="form-section">
            <h5 class="section-header">Información de Bodega</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="codigoBodega" class="form-label">Código de Bodega</label>
                    <input name="cod_al" type="text" class="form-control" id="codigoBodega">
                </div>
                <div class="col-md-6">
                    <label for="nombreBodega" class="form-label">Nombre de Bodega</label>
                    <input name="noma" type="text" class="form-control" id="nombreBodega">
                </div>
            </div>
        </div>

        <!-- Sección de contacto -->
        <div class="form-section">
            <h5 class="section-header">Contacto</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="atencion" class="form-label">Atención</label>
                    <input name="atencion" type="text" class="form-control" value="N/S" id="atencion">
                </div>
                <div class="col-md-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input name="tel" type="tel" class="form-control" value="55 5555 5555" id="telefono">
                </div>
                <div class="col-md-3">
                    <label for="email" class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" id="email">
                </div>
                <div class="col-md-6">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" name="obsD" id="observaciones" rows="3"></textarea>
                </div>
            </div>
        </div>

        <!-- Sección de observaciones -->
        <div class="form-section">
            <h5 class="section-header">Observaciones</h5>
            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control" name="obsP" id="observaciones" rows="3"></textarea>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex justify-content-md-end mt-4">
            <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
        </div>
        <script>
          $(document).ready(function() {
            let bodegaEditada = {
              codigo: false,
              nombre: false
          };

  // Detectar edición manual de bodegas
          $('#codigoBodega').on('input', function() {
              bodegaEditada.codigo = true;
          });

          $('#nombreBodega').on('input', function() {
              bodegaEditada.nombre = true;
          });

  // Actualizar bodegas cuando cambian los datos del cliente
          $('#codigo, #razonSocial').on('input', function() {
              if(!bodegaEditada.codigo) {
                $('#codigoBodega').val($('#codigo').val());
            }
            if(!bodegaEditada.nombre) {
                $('#nombreBodega').val($('#razonSocial').val());
            }
        });

  // Actualizar al enviar el formulario
          $('form').on('submit', function() {
              if(!bodegaEditada.codigo) {
                $('#codigoBodega').val($('#codigo').val());
            }
            if(!bodegaEditada.nombre) {
                $('#nombreBodega').val($('#razonSocial').val());
            }
            return true;
        });

  // Inicializar valores al cargar
          $('#codigoBodega').val($('#codigo').val());
          $('#nombreBodega').val($('#razonSocial').val());
      });
  </script>
</form>
</div>
</div>
</div>
<script>
    function codigo1() {
        var codigo = document.getElementById('codigo').value;
        var zona = document.getElementById('zona') ? document.getElementById('zona').value : '<?php echo $zona_seleccionada; ?>';

        var parametros = {
            "codigo_PROVEEDOR": codigo,
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