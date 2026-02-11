<?php
// Verificación de permisos - Backend
requirePermiso('COMPRAS_EDITAR', 'compras');

// Obtener el ID de la compra a editar
$id_compra = $_GET['id'] ?? 0;

// Consultar los datos de la compra
$compraQuery = $conn_mysql->prepare("
    SELECT c.*, 
    p.cod AS cod_proveedor,
    d.cod_al AS cod_direccion,
    t.placas AS placas_transporte,
    pr.nom_pro AS nombre_producto
    FROM compras c
    LEFT JOIN proveedores p ON c.id_prov = p.id_prov
    LEFT JOIN direcciones d ON c.id_direc = d.id_direc
    LEFT JOIN transportes t ON c.id_transp = t.id_transp
    LEFT JOIN productos pr ON c.id_prod = pr.id_prod
    WHERE c.id_compra = ?
    ");
$compraQuery->bind_param('i', $id_compra);
$compraQuery->execute();
$compraData = $compraQuery->get_result()->fetch_assoc();

if (!$compraData) {
    alert("Compra no encontrada", 0, "compras");
    exit();
}
if ($compraData['acciones'] == 1) {
    alert("No es posible editar esta compra", 2, "ventas");
    exit();
}

// Consultar el registro en almacén relacionado
$almacenQuery = $conn_mysql->prepare("SELECT * FROM almacen WHERE id_compra = ?");
$almacenQuery->bind_param('i', $id_compra);
$almacenQuery->execute();
$almacenData = $almacenQuery->get_result()->fetch_assoc();

// Procesar el formulario de actualización
if (isset($_POST['guardar01'])) {
    try {
        // Iniciar transacción
        $conn_mysql->begin_transaction();

        // 1. Actualizar la compra
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
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'), // Añadir fecha a los datos
            'id_user' => $idUser
        ];

        $setClause = implode(' = ?, ', array_keys($CompraData)) . ' = ?';
        $sql = "UPDATE compras SET $setClause WHERE id_compra = ?";
        $stmt = $conn_mysql->prepare($sql);
        
        $values = array_values($CompraData);
        $values[] = $id_compra;
        
        $types = str_repeat('s', count($CompraData)) . 'i';
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        // 2. Verificar si hubo cambios en producto o peso neto
        $productoCambiado = ($compraData['id_prod'] != $_POST['id_prod']);
        $pesoCambiado = ($compraData['neto'] != $_POST['neto']);

        if ($productoCambiado || $pesoCambiado) {
            // Actualizar registro en almacén
            $AlmacenData = [
                'id_prod' => $_POST['id_prod'] ?? '',
                'entrada' => $_POST['neto'] ?? 0,
                'id_user' => $idUser
            ];

            $setClauseAlm = implode(' = ?, ', array_keys($AlmacenData)) . ' = ?';
            $sqlAlm = "UPDATE almacen SET $setClauseAlm WHERE id_compra = ?";
            $stmtAlm = $conn_mysql->prepare($sqlAlm);
            
            $valuesAlm = array_values($AlmacenData);
            $valuesAlm[] = $id_compra;
            
            $typesAlm = str_repeat('s', count($AlmacenData)) . 'i';
            $stmtAlm->bind_param($typesAlm, ...$valuesAlm);
            $stmtAlm->execute();
        }

        // Confirmar transacción
        $conn_mysql->commit();

        alert("Compra actualizada exitosamente", 1, "V_compra&id=$id_compra");

    } catch (mysqli_sql_exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "E_compra&id=$id_compra");
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "E_compra&id=$id_compra");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Compra</h5>
            <a href="?p=compras">
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
                            <label for="factura" class="form-label">Remisión</label>
                            <input name="fact" type="text" class="form-control" id="factura" 
                            value="<?= htmlspecialchars($compraData['fact'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" 
                            value="<?= htmlspecialchars($compraData['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input name="fecha" type="date" class="form-control" id="fecha" 
                            value="<?= date('Y-m-d', strtotime($compraData['fecha'])) ?>" required>
                        </div>
                        <!-- En la sección de zona -->
                        <div class="col-md-4">
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" disabled>
                                <?php
                                $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>" <?= ($compraData['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
                                    <?php
                                }
                                ?>
                            </select>
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
                                <option selected disabled value="">Seleccionar proveedor...</option>
                                <?php
                                $proveedores = $conn_mysql->query("SELECT id_prov, cod, nombre FROM proveedores WHERE status = 1 AND zona = '".$compraData['zona']."' ORDER BY nombre");
                                while ($prov = mysqli_fetch_array($proveedores)) {
                                    $selected = ($prov['id_prov'] == $compraData['id_prov']) ? 'selected' : '';
                                    echo "<option value='{$prov['id_prov']}' $selected>{$prov['cod']} - {$prov['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Bodega</label>
                            <select class="form-select" name="id_direc" id="direccion" required>
                                <option selected disabled value="">Cargando direcciones...</option>
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
                                <option selected disabled value="">Seleccionar transporte...</option>
                                <?php
                                $transportes = $conn_mysql->query("SELECT id_transp, placas FROM transportes WHERE status = 1 AND zona = '".$compraData['zona']."' ORDER BY placas");
                                while ($trans = mysqli_fetch_array($transportes)) {
                                    $selected = ($trans['id_transp'] == $compraData['id_transp']) ? 'selected' : '';
                                    echo "<option value='{$trans['id_transp']}' $selected>{$trans['placas']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" name="id_prod" id="producto" required>
                                <option selected disabled value="">Seleccionar producto...</option>
                                <?php
                                $productos = $conn_mysql->query("SELECT id_prod, nom_pro FROM productos WHERE status = 1 AND zona = '".$compraData['zona']."' ORDER BY nom_pro");
                                while ($prod = mysqli_fetch_array($productos)) {
                                    $selected = ($prod['id_prod'] == $compraData['id_prod']) ? 'selected' : '';
                                    echo "<option value='{$prod['id_prod']}' $selected>{$prod['nom_pro']}</option>";
                                }
                                ?>
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
                            <input name="tara" type="number" step="0.01" class="form-control" id="tara" 
                            value="<?= htmlspecialchars($compraData['tara'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="bruto" class="form-label">Bruto</label>
                            <input name="bruto" type="number" step="0.01" class="form-control" id="bruto" 
                            value="<?= htmlspecialchars($compraData['bruto'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="neto" class="form-label">Neto</label>
                            <input name="neto" type="number" step="0.01" class="form-control" id="neto" 
                            value="<?= htmlspecialchars($compraData['neto'] ?? 0) ?>" required readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="precio" class="form-label">Precio Unitario</label>
                            <input name="pres" type="number" step="0.01" class="form-control" id="precio" 
                            value="<?= htmlspecialchars($compraData['pres'] ?? 0) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Campo oculto para el usuario -->
                <input type="hidden" name="id_user" value="<?= $idUser ?>">

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
    // Cargar direcciones del proveedor seleccionado al inicio
        const idProv = $('#proveedor').val();
        if (idProv) {
            cargarDirecciones(idProv, <?= $compraData['id_direc'] ?? 'null' ?>);
        }

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
                cargarDirecciones(idProv);
            } else {
                $('#direccion').prop('disabled', true).html('<option selected disabled value="">Primero seleccione un proveedor</option>');
            }
        });

    // Función para cargar direcciones
        function cargarDirecciones(idProv, idDireccionSeleccionada = null) {
            $('#direccion').prop('disabled', false).html('<option selected disabled value="">Cargando direcciones...</option>');

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
                            const selected = (idDireccionSeleccionada && dir.id_direc == idDireccionSeleccionada) ? 'selected' : '';
                            options += `<option value="${dir.id_direc}" ${selected}>${dir.cod_al} - ${dir.noma}</option>`;
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
        }

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