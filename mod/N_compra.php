<?php
// Verificación de permisos - Backend
requirePermiso('PROVEEDORES_CREAR', 'compras');

if (isset($_POST['guardar01'])) {

    $factura1 = $_POST['fact'] ?? '';

     // Validar formato de fecha
    $fecha_compra = $_POST['fecha'] ?? date('Y-m-d');
    if (!DateTime::createFromFormat('Y-m-d', $fecha_compra)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "N_compra");
        exit;
    }

    $AlertCo02 = $conn_mysql->query("SELECT * FROM compras WHERE fact = '$factura1' AND status = '1'");
    $AlertCo03 = mysqli_fetch_array($AlertCo02);

    if (!empty($AlertCo03['id_compra'])) {
        alert("Remisión ya ocupada", 2, "N_compra");  
    }else {

        try {
        // Iniciar transacción
            $conn_mysql->begin_transaction();

        // 1. Registrar la compra
            $CompraData = [
                'fact' => $_POST['fact'] ?? '',
                'nombre' => $_POST['nombre'] ?? '',
                'id_prov' => $_POST['id_prov'] ?? '',
                'id_direc' => $_POST['id_direc'] ?? '',
                'id_transp' => $_POST['id_transp'] ?? '',
                'id_prod' => $_POST['id_prod'] ?? '',
                'tara' => $_POST['tara'] ?? 0,
                'bruto' => $_POST['bruto'] ?? 0,
                'neto' => $_POST['neto'] ?? 0,
                'pres' => $_POST['pres'] ?? 0,
                'id_user' => $idUser,
                'status' => 1,
                'fecha' => $fecha_compra,
                'zona' => $_POST['zona'] ?? $zona_seleccionada
            ];

        // Insertar compra
            $columns = implode(', ', array_keys($CompraData));
            $placeholders = str_repeat('?,', count($CompraData) - 1) . '?';
            $sql = "INSERT INTO compras ($columns) VALUES ($placeholders)";
            $stmt = $conn_mysql->prepare($sql);
            $types = str_repeat('s', count($CompraData));
            $stmt->bind_param($types, ...array_values($CompraData));
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                throw new Exception("Error al registrar la compra");
            }

        // Obtener el ID de la compra recién insertada
            $id_compra = $conn_mysql->insert_id;

        // 2. Registrar el movimiento en almacén
            $AlmacenData = [
                'id_compra' => $id_compra,
                'id_prod' => $_POST['id_prod'] ?? '',
                'entrada' => $_POST['neto'] ?? 0,
                'id_user' => $idUser,
                'zona' => $_POST['zona'] ?? $zona_seleccionada
            ];

            $columnsAlm = implode(', ', array_keys($AlmacenData));
            $placeholdersAlm = str_repeat('?,', count($AlmacenData) - 1) . '?';
            $sqlAlm = "INSERT INTO almacen ($columnsAlm) VALUES ($placeholdersAlm)";
            $stmtAlm = $conn_mysql->prepare($sqlAlm);
            $typesAlm = str_repeat('s', count($AlmacenData));
            $stmtAlm->bind_param($typesAlm, ...array_values($AlmacenData));
            $stmtAlm->execute();

            if ($stmtAlm->affected_rows <= 0) {
                throw new Exception("Error al registrar en almacén");
            }

        // Confirmar transacción
            $conn_mysql->commit();

            alert("Compra registrada exitosamente", 1, "compras");
            logActivity('CREAR', 'Dio de alta un nuevo cliente '. $idCliente);

        } catch (mysqli_sql_exception $e) {
            $conn_mysql->rollback();
            alert("Error: " . $e->getMessage(), 0, "N_compra");
        } catch (Exception $e) {
            $conn_mysql->rollback();
            alert("Error: " . $e->getMessage(), 0, "N_compra");
        }
    }
}
?>

<!-- El resto del formulario permanece igual como en la versión anterior -->
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Compra</h5>
            <a href="?p=compras">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">

            <form class="forms-sample" method="post" action="">
                <!-- Sección de información básica -->
                <div class="form-section">
                    <h5 class="section-header">Información Básica </h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="factura" class="form-label">Remisión <span id="resulpostal00"></span></label>
                            <input name="fact" type="text" class="form-control" id="factura" oninput="codigo1()" required>
                            <div></div>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input name="fecha" type="date" class="form-control" id="fecha" 
                            value="<?= date('Y-m-d') ?>" required
                            max="<?= date('Y-m-d') ?>">
                        </div>
                         <div class="col-md-4">
                        <?php
                        if ($zona_seleccionada == 0) {
                            ?>
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" onchange="cargarProveedores(); cargarTransportes(); cargarProductos();">
                                <?php
                                $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>"> <?=$zona1['nom']?> </option>
                                    <?php
                                }
                                ?>
                            </select>

                            <?php
                        }else {
                            ?>
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" onchange="cargarProveedores(); cargarTransportes(); cargarProductos();">
                                <?php
                                $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1 AND id_zone = '".$zona_seleccionada."'");
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>"> <?=$zona1['nom']?> </option>
                                    <?php
                                }
                                ?>
                            </select>
                            <?php
                        }
                        ?>
                    </div>
                    </div>
                </div>

                <!-- Sección de proveedor y dirección -->
                <div class="form-section mt-4">
                    <h5 class="section-header">Proveedor y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="proveedor" class="form-label">Proveedor</label>
                            <select class="form-select" name="id_prov" id="proveedor" required>
                                <option selected disabled value="">Seleccione una zona primero...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Bodega</label>
                            <select class="form-select" name="id_direc" id="direccion" required disabled>
                                <option selected disabled value="">Primero seleccione un proveedor</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sección de transporte y producto -->
                <div class="form-section mt-4">
                    <h5 class="section-header">Transporte y Producto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="transporte" class="form-label">Transporte</label>
                            <select class="form-select" name="id_transp" id="transporte" required>
                                <option selected disabled value="">Seleccione una zona primero...</option>
                            </select>
                            <div class="form-text">Escribe para buscar entre las opciones disponibles.</div>
                        </div>
                        <script>
                        $(document).ready(function() {
                            $('#transporte').select2({
                                placeholder: "Selecciona o busca una opción",
                                allowClear: true,
                                language: "es"
                            });
                        });
                    </script>
                        <div class="col-md-6">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" name="id_prod" id="producto" required>
                                <option selected disabled value="">Seleccione una zona primero...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sección de pesos y precios -->
                <div class="form-section mt-4">
                    <h5 class="section-header">Pesos y Precios</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="tara" class="form-label">Tara</label>
                            <input name="tara" type="number" step="0.01" class="form-control" value="0" id="tara" required>
                        </div>
                        <div class="col-md-3">
                            <label for="bruto" class="form-label">Bruto</label>
                            <input name="bruto" type="number" step="0.01" class="form-control" id="bruto" required>
                        </div>
                        <div class="col-md-3">
                            <label for="neto" class="form-label">Neto</label>
                            <input name="neto" type="number" step="0.01" class="form-control" id="neto" required readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="precio" class="form-label">Precio Unitario</label>
                            <input name="pres" type="number" step="0.01" class="form-control" id="precio" required>
                        </div>
                    </div>
                </div>

                <!-- Campo oculto para el usuario -->
                <input type="hidden" name="id_user" value="<?= $idUser ?>">

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar Compra</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function codigo1() {
        var factura = document.getElementById('factura').value;

        var parametros = {
            "factura": factura,
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
<script>
    function cargarTransportes() {
        const zonaId = $('#zona').val();

        if (zonaId) {
            $.ajax({
                url: 'get_transportes.php',
                type: 'POST',
                data: { zona_id: zonaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const transportes = response.data;
                        let options = '<option selected disabled value="">Seleccionar transporte...</option>';

                        transportes.forEach(function(trans) {
                            options += `<option value="${trans.id_transp}">${trans.placas}</option>`;
                        });

                        $('#transporte').html(options);
                    } else {
                        $('#transporte').html('<option selected disabled value="">No hay transportes para esta zona</option>');
                    }
                },
                error: function() {
                    $('#transporte').html('<option selected disabled value="">Error al cargar transportes</option>');
                }
            });
        } else {
            $('#transporte').html('<option selected disabled value="">Seleccione una zona primero...</option>');
        }
    }
    function cargarProductos() {
        const zonaId = $('#zona').val();

        if (zonaId) {
            $.ajax({
                url: 'get_productos.php',
                type: 'POST',
                data: { zona_id: zonaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const productos = response.data;
                        let options = '<option selected disabled value="">Seleccionar producto...</option>';

                        productos.forEach(function(prod) {
                            options += `<option value="${prod.id_prod}">${prod.nom_pro}</option>`;
                        });

                        $('#producto').html(options);
                    } else {
                        $('#producto').html('<option selected disabled value="">No hay productos para esta zona</option>');
                    }
                },
                error: function() {
                    $('#producto').html('<option selected disabled value="">Error al cargar productos</option>');
                }
            });
        } else {
            $('#producto').html('<option selected disabled value="">Seleccione una zona primero...</option>');
        }
    }
    function cargarProveedores() {
        const zonaId = $('#zona').val();

        if (zonaId) {
            $.ajax({
                url: 'get_proveedores.php',
                type: 'POST',
                data: { zona_id: zonaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const proveedores = response.data;
                        let options = '<option selected disabled value="">Seleccionar proveedor...</option>';

                        proveedores.forEach(function(prov) {
                            options += `<option value="${prov.id_prov}">${prov.cod} - ${prov.rs}</option>`;
                        });

                        $('#proveedor').html(options);
                    } else {
                        $('#proveedor').html('<option selected disabled value="">No hay proveedores para esta zona</option>');
                    }
                },
                error: function() {
                    $('#proveedor').html('<option selected disabled value="">Error al cargar proveedores</option>');
                }
            });
        } else {
            $('#proveedor').html('<option selected disabled value="">Seleccione una zona primero...</option>');
        }
    }

// Llamar a la función al cargar la página para cargar proveedores de la zona por defecto
    // Llamar a las funciones al cargar la página
    $(document).ready(function() {
        cargarProveedores();
        cargarTransportes();
        cargarProductos();
    });
    $(document).ready(function() {
    // Calcular automáticamente el neto cuando se ingresen tara y bruto
        $('#tara, #bruto').on('input', function() {
            const tara = parseFloat($('#tara').val()) || 0;
            const bruto = parseFloat($('#bruto').val()) || 0;
            const neto = bruto - tara;
            $('#neto').val(neto.toFixed(2));
        });

    // Cargar direcciones según proveedor seleccionado
        $('#proveedor').change(function() {
            const idProv = $(this).val();
            if (idProv) {
                $('#direccion').prop('disabled', false);

                $.ajax({
                    url: 'get_direcciones.php',
                    type: 'POST',
                    data: { id_prov: idProv },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const direcciones = response.data;
                            let options = '<option selected disabled value="">Seleccionar dirección...</option>';

                            direcciones.forEach(function(dir) {
                                options += `<option value="${dir.id_direc}">${dir.cod_al} - ${dir.noma}</option>`;
                            });

                            $('#direccion').html(options);
                        } else {
                            $('#direccion').html('<option selected disabled value="">No hay direcciones para este proveedor</option>');
                        }
                    },
                    error: function() {
                        $('#direccion').html('<option selected disabled value="">Error al cargar direcciones</option>');
                    }
                });
            } else {
                $('#direccion').prop('disabled', true).html('<option selected disabled value="">Primero seleccione un proveedor</option>');
            }
        });

    // Validación antes de enviar el formulario
        $('form').submit(function(e) {
            const neto = parseFloat($('#neto').val());
            if (neto <= 0) {
                alert('El peso neto debe ser mayor que cero');
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
</script>