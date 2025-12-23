<?php
if (isset($_POST['guardar01'])) {
   $factura1 = $_POST['fact'] ?? '';
 // Validar formato de fecha
   $fecha_compra = $_POST['fecha'] ?? date('Y-m-d');
   if (!DateTime::createFromFormat('Y-m-d', $fecha_compra)) {
    alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "N_compra");
    exit;
}
$AlertCo02 = $conn_mysql->query("SELECT * FROM ventas WHERE fact = '$factura1' AND status = '1'");
$AlertCo03 = mysqli_fetch_array($AlertCo02);

if (!empty($AlertCo03['id_compra'])) {

    alert("Factura ya ocupada", 2, "N_venta");  
}else {
    try {
        // Iniciar transacción
        $conn_mysql->begin_transaction();

        // 1. Registrar la venta
        $VentaData = [
            'fact' => $_POST['fact'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'id_cli' => $_POST['id_cli'] ?? '',
            'factura' => $_POST['factura'] ?? '',
            'id_direc' => $_POST['id_direc'] ?? '',
            'id_compra' => $_POST['id_compra'] ?? '',
            'id_prod' => $_POST['id_prod'] ?? '',
            'costo_flete' => $_POST['costo_flete'] ?? 0,
            'flete' => $_POST['id_transporte'] ?? '',
            'peso_cliente' => $_POST['peso_cliente'] ?? 0,
            'precio' => $_POST['precio'] ?? 0,
            'id_user' => $idUser,
            'status' => 1,
            'fecha' => $fecha_compra,
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Insertar venta
        $columns = implode(', ', array_keys($VentaData));
        $placeholders = str_repeat('?,', count($VentaData) - 1) . '?';
        $sql = "INSERT INTO ventas ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        $types = str_repeat('s', count($VentaData));
        $stmt->bind_param($types, ...array_values($VentaData));
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            throw new Exception("Error al registrar la venta");
        }

        // Obtener el ID de la venta recién insertada
        $id_venta = $conn_mysql->insert_id;

        // 2. Registrar el movimiento en almacén (salida)
        $AlmacenData = [
            'id_venta' => $id_venta,
            'id_prod' => $_POST['id_prod'] ?? '',
            'salida' => $_POST['peso_cliente'] ?? 0,
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

        alert("Venta registrada exitosamente", 1, "ventas");

    } catch (mysqli_sql_exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "N_venta");
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "N_venta");
    }
}
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Venta</h5>
            <a href="?p=ventas">
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
                        <label for="fact" class="form-label">Remisión <span id="resulpostal00"></span></label>
                        <input name="fact" type="text" class="form-control" id="facturav" oninput="codigo1()" required>
                    </div>
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input name="nombre" type="text" class="form-control" id="nombre" required>
                    </div>
                    <div class="col-md-2">
                        <label for="factura" class="form-label">Factura de venta</label>
                        <input name="factura" type="text" class="form-control" id="factura">
                    </div>
                    <div class="col-md-2">
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
                            <select class="form-select" name="zona" id="zona" onchange="cambiarZona()">
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
                        } else {
                            ?>
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" onchange="cambiarZona()">
                                <?php
                                $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    $selected = ($zona1['id_zone'] == $zona_seleccionada) ? 'selected' : '';
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>" <?=$selected?>> <?=$zona1['nom']?> </option>
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

            <!-- Sección de cliente y dirección -->
            <div class="form-section mt-4">
                <h5 class="section-header">Cliente y Bodega</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="cliente" class="form-label">Cliente</label>
                        <select class="form-select" name="id_cli" id="cliente" required onchange="cargarDirecciones(this.value)">
                            <option selected disabled value="">Seleccione una zona primero...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Bodega de Entrega</label>
                        <select class="form-select" name="id_direc" id="direccion" required disabled>
                            <option selected disabled value="">Primero seleccione un cliente</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sección de compra y producto -->
            <div class="form-section mt-4">
                <h5 class="section-header">Compra y Producto</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="compra" class="form-label">Compra</label>
                        <select class="form-select" name="id_compra" id="compra" required onchange="cargarDatosCompra(this.value)">
                            <option selected disabled value="">Seleccione una zona primero...</option>
                        </select>
                        <div class="form-text">Escribe para buscar entre las opciones disponibles.</div>
                    </div>
                    <script>
                        $(document).ready(function() {
                            $('#compra').select2({
                                placeholder: "Selecciona o busca una opción",
                                allowClear: true,
                                language: "es"
                            });
                        });
                    </script>
                    <div class="col-md-6">
                        <label for="producto" class="form-label">Producto</label>
                        <input type="hidden" name="id_prod" class="form-control" id="producto">
                        <input type="text" class="form-control" id="nompro" readonly>
                    </div>
                </div>
            </div>

            <!-- Sección de flete -->
            <div class="form-section mt-4">
                <h5 class="section-header">Datos del Flete</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="costo_flete" class="form-label">Costo Flete</label>
                        <input name="costo_flete" type="number" step="0.01" class="form-control" id="costo_flete" required>
                    </div>
                    <div class="col-md-6">
                        <label for="flete" class="form-label">Transporte (Flete)</label>
                        <input name="flete" type="text" class="form-control" id="flete" readonly>
                        <input type="hidden" name="id_transporte" id="id_transporte">
                    </div>
                </div>
            </div>

            <!-- Sección de pesos -->
            <div class="form-section mt-4">
                <h5 class="section-header">Pesos</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="tara" class="form-label">Tara</label>
                        <input name="tara" type="number" step="0.01" class="form-control" id="tara" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="bruto" class="form-label">Bruto</label>
                        <input name="bruto" type="number" step="0.01" class="form-control" id="bruto" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="neto" class="form-label">Neto</label>
                        <input name="neto" type="number" step="0.01" class="form-control" id="neto" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="peso_cliente" class="form-label">Peso Cliente</label>
                        <input name="peso_cliente" type="number" step="0.01" class="form-control" id="peso_cliente" required>
                    </div>
                </div>
            </div>

            <!-- Sección de precio -->
            <div class="form-section mt-4">
                <h5 class="section-header">Precio</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="precio" class="form-label">Precio</label>
                        <input name="precio" type="number" step="0.01" class="form-control" id="precio" required>
                    </div>
                </div>
            </div>

            <!-- Campo oculto para el usuario -->
            <input type="hidden" name="id_user" value="<?= $idUser ?>">

            <!-- Botones de acción -->
            <div class="d-flex justify-content-md-end mt-4">
                <button type="submit" name="guardar01" class="btn btn-primary">Guardar Venta</button>
            </div>
        </form>
    </div>
</div>
</div>
<script>

    function cambiarZona() {
        cargarClientes();
        cargarCompras();
        cargarProveedores(); 
        cargarTransportes(); 
        cargarProductos();
    }

    function cargarClientes() {
        const zonaId = $('#zona').val();

        if (zonaId) {
            $.ajax({
                url: 'get_clientes.php',
                type: 'POST',
                data: { zona_id: zonaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const clientes = response.data;
                        let options = '<option selected disabled value="">Seleccionar cliente...</option>';

                        clientes.forEach(function(cli) {
                            options += `<option value="${cli.id_cli}">${cli.cod} - ${cli.rs}</option>`;
                        });

                        $('#cliente').html(options);
                        $('#direccion').html('<option selected disabled value="">Seleccione un cliente primero</option>');
                        $('#direccion').prop('disabled', true);
                    } else {
                        $('#cliente').html('<option selected disabled value="">No hay clientes para esta zona</option>');
                        $('#direccion').html('<option selected disabled value="">Seleccione un cliente primero</option>');
                        $('#direccion').prop('disabled', true);
                    }
                },
                error: function() {
                    $('#cliente').html('<option selected disabled value="">Error al cargar clientes</option>');
                }
            });
        } else {
            $('#cliente').html('<option selected disabled value="">Seleccione una zona primero...</option>');
            $('#direccion').html('<option selected disabled value="">Seleccione un cliente primero</option>');
            $('#direccion').prop('disabled', true);
        }
    }

    function cargarCompras() {
        const zonaId = $('#zona').val();

        if (zonaId) {
            $.ajax({
                url: 'get_compras.php',
                type: 'POST',
                data: { zona_id: zonaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const compras = response.data;
                        let options = '<option selected disabled value="">Seleccionar compra...</option>';

                        compras.forEach(function(comp) {
                            options += `<option value="${comp.id_compra}">${comp.fact} - ${comp.nombre}</option>`;
                        });

                        $('#compra').html(options);

                    // Limpiar campos relacionados con compras
                        $('#tara').val('');
                        $('#bruto').val('');
                        $('#neto').val('');
                        $('#producto').val('');
                        $('#nompro').val('');
                        $('#id_transporte').val('');
                        $('#flete').val('');
                    } else {
                        $('#compra').html('<option selected disabled value="">No hay compras para esta zona</option>');

                    // Limpiar campos relacionados con compras
                        $('#tara').val('');
                        $('#bruto').val('');
                        $('#neto').val('');
                        $('#producto').val('');
                        $('#nompro').val('');
                        $('#id_transporte').val('');
                        $('#flete').val('');
                    }
                },
                error: function() {
                    $('#compra').html('<option selected disabled value="">Error al cargar compras</option>');
                }
            });
        } else {
            $('#compra').html('<option selected disabled value="">Seleccione una zona primero...</option>');
        }
    }

// Llamar a las funciones al cargar la página
    $(document).ready(function() {
    // Si hay una zona seleccionada inicialmente, cargar clientes y compras
        if ($('#zona').val()) {
            cargarClientes();
            cargarCompras();
        }
    });


    function codigo1() {
        var facturav = document.getElementById('facturav').value;

        var parametros = {
            "facturav": facturav,
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
    $(document).ready(function() {
    // Cargar direcciones según cliente seleccionado
        window.cargarDirecciones = function(id_cli) {
            if (id_cli) {
                $('#direccion').prop('disabled', false);

                $.ajax({
                    url: 'mod/get_direcciones_cliente.php',
                    type: 'POST',
                    data: { id_cli: id_cli },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const direcciones = response.data;
                            let options = '<option selected disabled value="">Seleccionar dirección...</option>';

                            direcciones.forEach(function(dir) {
                                options += `<option value="${dir.id_direc}">${dir.cod_al}</option>`;
                            });

                            $('#direccion').html(options);
                        } else {
                            $('#direccion').html('<option selected disabled value="">No hay direcciones para este cliente</option>');
                        }
                    },
                    error: function() {
                        $('#direccion').html('<option selected disabled value="">Error al cargar direcciones</option>');
                    }
                });
            } else {
                $('#direccion').prop('disabled', true).html('<option selected disabled value="">Primero seleccione un cliente</option>');
            }
        };

    // Cargar datos de la compra seleccionada (incluyendo transporte)
        window.cargarDatosCompra = function(id_compra) {
            if (id_compra) {
                $.ajax({
                    url: 'mod/get_datos_compra.php',
                    type: 'POST',
                    data: { id_compra: id_compra },
                    dataType: 'json',
                    success: function(response) {
                        if (response) {
                            $('#tara').val(response.tara);
                            $('#bruto').val(response.bruto);
                            $('#neto').val(response.neto);
                            $('#peso_cliente').val(response.neto);
                            $('#producto').val(response.id_prod);
                            $('#nompro').val(response.nom_prod);
                            $('#id_transporte').val(response.id_transporte);

                    // Mostrar datos del transporte en el campo flete
                            if (response.transporte) {
                                $('#flete').val(response.transporte.placas);
                            }
                        }
                    }
                });
            }
        };

    // Validación antes de enviar el formulario
        $('form').submit(function(e) {
            const pesoCliente = parseFloat($('#peso_cliente').val());
            if (pesoCliente <= 0) {
                alert('El peso cliente debe ser mayor que cero');
                e.preventDefault();
                return false;
            }

            const precio = parseFloat($('#precio').val());
            if (precio <= 0) {
                alert('El precio debe ser mayor que cero');
                e.preventDefault();
                return false;
            }

            return true;
        });
    });
</script>