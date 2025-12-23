<?php
if (isset($_POST['guardar01'])) {
    try {
        $CodAlmacen = $_POST['cod'] ?? '';
        $zona_almacen = $_POST['zona'] ?? $zona_seleccionada;
        
        // Verificar si el código del almacén ya existe EN LA MISMA ZONA
        $VerAlma0 = $conn_mysql->query("SELECT * FROM almacenes WHERE cod = '$CodAlmacen' AND zona = '$zona_almacen' AND status = '1'");
        $VerAlma1 = mysqli_fetch_array($VerAlma0);
        if (!empty($VerAlma1['id_alma'])) {
            alert("El código del almacén ya existe en esta zona, favor de ocupar otro", 0, "N_almacen"); 
            exit;
        }
        
        $AlmacenData = [
            'nombre' => $_POST['nombre'] ?? '',
            'cod' => $_POST['cod'] ?? '',
            'rs' => $_POST['rs'] ?? '',
            'rfc' => $_POST['rfc'] ?? '',
            'tpersona' => $_POST['tpersona'] ?? '',
            'obs' => $_POST['obsP'] ?? '',
            'id_user' => $idUser,
            'fac_rem' => $_POST['fac_rem'] ?? 'FAC',
            'zona' => $zona_almacen
        ];

        // Insertar almacén con MySQLi
        $columns = implode(', ', array_keys($AlmacenData));
        $placeholders = str_repeat('?,', count($AlmacenData) - 1) . '?';
        $sql = "INSERT INTO almacenes ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);

        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($AlmacenData)); // 's' para string
        $stmt->bind_param($types, ...array_values($AlmacenData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Obtener ID del almacén recién insertado
            $idAlmacen = $conn_mysql->insert_id;
            
            // Insertar dirección para el almacén
            $DireccionData = [
                'cod_al' => $_POST['cod_al'] ?? '',
                'noma' => $_POST['noma'] ?? '',
                'atencion' => $_POST['atencion'] ?? '',
                'tel' => $_POST['tel'] ?? '',
                'email' => $_POST['email'] ?? '',
                'obs' => $_POST['obsD'] ?? '',
                'id_alma' => $idAlmacen  // Cambiado de id_us a id_alma
            ];

            $columnsD = implode(', ', array_keys($DireccionData));
            $placeholdersD = str_repeat('?,', count($DireccionData) - 1) . '?';
            $sqlD = "INSERT INTO direcciones ($columnsD) VALUES ($placeholdersD)";
            $stmtD = $conn_mysql->prepare($sqlD);

            $typesD = str_repeat('s', count($DireccionData));
            $stmtD->bind_param($typesD, ...array_values($DireccionData));
            $stmtD->execute();

            if ($stmtD->affected_rows > 0) {
                alert("Almacén $CodAlmacen registrado exitosamente", 1, "V_almacen&id=$idAlmacen");
                logActivity('CREAR', 'Dio de alta un nuevo almacén '. $idAlmacen);
            } else {
                alert("Error al registrar la dirección", 0, "N_almacen");
            }
        } else {
            alert("Error al registrar el almacén", 0, "N_almacen");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_almacen");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Almacén</h5>
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
                            <label for="codigo" class="form-label">Código <span id="resulpostal00"></span></label>
                            <input name="cod" type="text" class="form-control" id="codigo" oninput="codigoAlmacen01()" required>
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
                            <select class="form-select" name="fac_rem" id="tipoEvidencia">
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

                <!-- Sección de ubicación del almacén -->
                <div class="form-section">
                    <h5 class="section-header">Ubicación del Almacén</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="codigoAlmacen" class="form-label">Código de Almacén</label>
                            <input name="cod_al" type="text" class="form-control" id="codigoAlmacen">
                        </div>
                        <div class="col-md-6">
                            <label for="nombreAlmacen" class="form-label">Nombre de Almacén</label>
                            <input name="noma" type="text" class="form-control" id="nombreAlmacen">
                        </div>
                    </div>
                </div>

                <!-- Sección de información de contacto -->
                <div class="form-section">
                    <h5 class="section-header">Información de Contacto</h5>
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
                
                <!-- Sección de observaciones generales -->
                <div class="form-section">
                    <h5 class="section-header">Observaciones Generales</h5>
                    <div class="mb-3">
                        <label for="observacionesGenerales" class="form-label">Observaciones</label>
                        <textarea class="form-control" name="obsP" id="observacionesGenerales" rows="3"></textarea>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
                </div>
                <script>
                    $(document).ready(function() {
                        let almacenEditado = {
                            codigo: false,
                            nombre: false
                        };

                        // Detectar edición manual de almacenes
                        $('#codigoAlmacen').on('input', function() {
                            almacenEditado.codigo = true;
                        });
                        
                        $('#nombreAlmacen').on('input', function() {
                            almacenEditado.nombre = true;
                        });

                        // Actualizar almacenes cuando cambian los datos básicos
                        $('#codigo, #razonSocial').on('input', function() {
                            if(!almacenEditado.codigo) {
                                $('#codigoAlmacen').val($('#codigo').val());
                            }
                            if(!almacenEditado.nombre) {
                                $('#nombreAlmacen').val($('#razonSocial').val());
                            }
                        });
                        
                        // Actualizar al enviar el formulario
                        $('form').on('submit', function() {
                            if(!almacenEditado.codigo) {
                                $('#codigoAlmacen').val($('#codigo').val());
                            }
                            if(!almacenEditado.nombre) {
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
    function codigoAlmacen01() {
        var codigo = document.getElementById('codigo').value;
        var zona = document.getElementById('zona') ? document.getElementById('zona').value : '<?php echo $zona_seleccionada; ?>';

        var parametros = {
            "codigo_ALMACEN": codigo,
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