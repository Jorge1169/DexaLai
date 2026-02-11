   <?php
   // Verificación de permisos - Backend
   requirePermiso('CLIENTES_CREAR', 'clientes');

   if (isset($_POST['guardar01'])) {
    try {
      $CodCliente = $_POST['cod'] ?? '';
      $zona_cliente = $_POST['zona'] ?? $zona_seleccionada;
      
 // Verificar si el código del cliente ya existe EN LA MISMA ZONA
      $VerCli0 = $conn_mysql->query("SELECT * FROM clientes WHERE cod = '$CodCliente' AND zona = '$zona_cliente' AND status = '1'");
      $VerCli1 = mysqli_fetch_array($VerCli0);
      if (!empty($VerCli1['id_cli'])) {
        alert("El código del cliente ya existe en esta zona, favor de ocupar otro", 0, "N_cliente"); 
        exit;
      }
      
      $ClienteData = [
        'nombre' => $_POST['nombre'] ?? '',
        'cod' => $_POST['cod'] ?? '',
        'rs' => $_POST['rs'] ?? '',
        'rfc' => $_POST['rfc'] ?? '',
        'tpersona' => $_POST['tpersona'] ?? '',
        'obs' => $_POST['obsP'] ?? '',
        'id_user' => $idUser,
        'fac_rem' => $fac_rem,
        'zona' => $zona_cliente
      ];

        // Insertar cliente con MySQLi
      $columns = implode(', ', array_keys($ClienteData));
      $placeholders = str_repeat('?,', count($ClienteData) - 1) . '?';
      $sql = "INSERT INTO clientes ($columns) VALUES ($placeholders)";
      $stmt = $conn_mysql->prepare($sql);

        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($ClienteData)); // 's' para string
        $stmt->bind_param($types, ...array_values($ClienteData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener ID del cliente recién insertado
          $idCliente = $conn_mysql->insert_id;
            // Insertar dirección
          $DireccionData = [
            'cod_al' => $_POST['cod_al'] ?? '',
            'noma' => $_POST['noma'] ?? '',
            'atencion' => $_POST['atencion'] ?? '',
            'tel' => $_POST['tel'] ?? '',
            'email' => $_POST['email'] ?? '',
            'obs' => $_POST['obsD'] ?? '',
            'id_us' => $idCliente
          ];

          $columnsD = implode(', ', array_keys($DireccionData));
          $placeholdersD = str_repeat('?,', count($DireccionData) - 1) . '?';
          $sqlD = "INSERT INTO direcciones ($columnsD) VALUES ($placeholdersD)";
          $stmtD = $conn_mysql->prepare($sqlD);

          $typesD = str_repeat('s', count($DireccionData));
          $stmtD->bind_param($typesD, ...array_values($DireccionData));
          $stmtD->execute();

          if ($stmtD->affected_rows > 0) {
            alert("Cliente $CodCliente registrado exitosamente", 1, "V_cliente&id=$idCliente");
            logActivity('CREAR', 'Dio de alta un nuevo cliente '. $idCliente);
          } else {
            alert("Error al registrar la dirección", 0, "N_cliente");
          }
        } else {
          alert("Error al registrar el cliente", 0, "N_cliente");
        }
      } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_cliente");
      }
    }
    ?>
    <div class="container mt-2">
      <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Nuevo Cliente</h5>
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
                  <label for="codigo" class="form-label">Código <span id="resulpostal00"></span></label>
                  <input name="cod" type="text" class="form-control" id="codigo" oninput="codigo1()" required>
                </div>
                <div class="col-md-4">
                  <label for="razonSocial" class="form-label">Razón Social</label>
                  <input name="rs" type="text" class="form-control" id="razonSocial" required>
                </div>
                <div class="col-md-4">
                  <label for="nombre" class="form-label">Nombre</label>
                  <input name="nombre" type="text" class="form-control" id="nombre" required>
                </div>
                <div class="col-md-4">
                  <label for="rfc" class="form-label">RFC</label>
                  <input name="rfc" type="text" class="form-control" id="rfc">
                </div>
              <!-- 
              <div class="col-md-4">
                <label for="cuentaContable" class="form-label">Cuenta Contable</label>
                <input name="c_contable" type="text" class="form-control" id="cuentaContable">
              </div>
                -->
                <div class="col-md-4">
                  <label for="tipoPersona" class="form-label">Tipo de Persona</label>
                  <select class="form-select" name="tpersona" id="tipoPersona">
                    <option selected disabled>Seleccionar...</option>
                    <option value="fisica">Física</option>
                    <option value="moral">Moral</option>
                  </select>
                </div>
                <div class="col-md-4">
                            <label for="TipoEvidencia" class="form-label">Tipo de evidencia</label>
                            <select class="form-select" name="fac_rem" id="tipoPersona">
                                <option value="FAC">Factura</option>
                                <option value="REM">Remisión</option>
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

            <!-- Sección de almacén -->
            <div class="form-section">
              <h5 class="section-header">Bodega</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="codigoAlmacen" class="form-label">Código de Bodega</label>
                  <input name="cod_al" type="text" class="form-control" id="codigoAlmacen">
                </div>
                <div class="col-md-6">
                  <label for="nombreAlmacen" class="form-label">Nombre de Bodega</label>
                  <input name="noma" type="text" class="form-control" id="nombreAlmacen">
                </div>
              </div>
            </div>

            <!-- Sección de dirección -->
            <div class="form-section">
              <h5 class="section-header">Información de Bodega</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="atencion" class="form-label">Atención</label>
                  <input name="atencion" type="text" class="form-control" id="atencion" value="N/S">
                </div>
                <div class="col-md-3">
                  <label for="telefono" class="form-label">Teléfono</label>
                  <input name="tel" type="tel" class="form-control" id="telefono" value="55 5555 5555">
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
                $('#codigoAlmacen').on('input', function() {
                  bodegaEditada.codigo = true;
                });
                
                $('#nombreAlmacen').on('input', function() {
                  bodegaEditada.nombre = true;
                });

  // Actualizar bodegas cuando cambian los datos del cliente
                $('#codigo, #razonSocial').on('input', function() {
                  if(!bodegaEditada.codigo) {
                    $('#codigoAlmacen').val($('#codigo').val());
                  }
                  if(!bodegaEditada.nombre) {
                    $('#nombreAlmacen').val($('#razonSocial').val());
                  }
                });
                
  // Actualizar al enviar el formulario
                $('form').on('submit', function() {
                  if(!bodegaEditada.codigo) {
                    $('#codigoAlmacen').val($('#codigo').val());
                  }
                  if(!bodegaEditada.nombre) {
                    $('#nombreAlmacen').val($('#razonSocial').val());
                  }
                  return true;
                });
                
  // Inicializar valores al cargar
                $('#codigoAlmacen').val($('#codigo').val());
                $('#nombreAlmacen').val($('#razonSocial').val());
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
          "codigo_CLIENTE": codigo,
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