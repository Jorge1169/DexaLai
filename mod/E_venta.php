<?php
// Obtener ID de la venta a editar
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos de la venta existente
$venta = [];
if ($id_venta > 0) {
    $query = "SELECT * FROM ventas WHERE id_venta = ?";
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
}

if (!$venta) {
    alert("Venta no encontrada", 0, "ventas");
    exit();
}
if ($venta['acciones'] == 1) {
    alert("No es posible editar esta venta", 2, "ventas");
    exit();
}

// Procesar actualización
if (isset($_POST['guardar01'])) {
    try {
        // Iniciar transacción
        $conn_mysql->begin_transaction();

        // 1. Actualizar la venta
        $VentaData = [
            'fact' => $_POST['fact'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'factura' => $_POST['factura'] ?? '',
            'id_cli' => $_POST['id_cli'] ?? '',
            'id_direc' => $_POST['id_direc'] ?? '',
            'id_compra' => $_POST['id_compra'] ?? '',
            'id_prod' => $_POST['id_prod'] ?? '',
            'costo_flete' => $_POST['costo_flete'] ?? 0,
            'flete' => $_POST['id_transporte'] ?? '',
            'peso_cliente' => $_POST['peso_cliente'] ?? 0,
            'precio' => $_POST['precio'] ?? 0,
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'), // Añadir fecha a los datos
            'id_user' => $idUser
        ];

        // Construir consulta de actualización
        $updates = [];
        foreach ($VentaData as $key => $value) {
            $updates[] = "$key = ?";
        }
        $setClause = implode(', ', $updates);

        $sql = "UPDATE ventas SET $setClause WHERE id_venta = ?";
        $stmt = $conn_mysql->prepare($sql);
        
        // Tipos de parámetros (todos strings) + id_venta (i)
        $types = str_repeat('s', count($VentaData)) . 'i';
        $params = array_values($VentaData);
        $params[] = $id_venta;
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        if ($stmt->affected_rows < 0) {
            throw new Exception("Error al actualizar la venta");
        }

        // 2. Actualizar el movimiento en almacén
        $AlmacenData = [
            'id_prod' => $_POST['id_prod'] ?? '',
            'salida' => $_POST['peso_cliente'] ?? 0,
            'id_user' => $idUser
        ];

        $updatesAlm = [];
        foreach ($AlmacenData as $key => $value) {
            $updatesAlm[] = "$key = ?";
        }
        $setClauseAlm = implode(', ', $updatesAlm);

        $sqlAlm = "UPDATE almacen SET $setClauseAlm WHERE id_venta = ?";
        $stmtAlm = $conn_mysql->prepare($sqlAlm);
        
        $typesAlm = str_repeat('s', count($AlmacenData)) . 'i';
        $paramsAlm = array_values($AlmacenData);
        $paramsAlm[] = $id_venta;
        
        $stmtAlm->bind_param($typesAlm, ...$paramsAlm);
        $stmtAlm->execute();

        if ($stmtAlm->affected_rows < 0) {
            throw new Exception("Error al actualizar en almacén");
        }

        // Confirmar transacción
        $conn_mysql->commit();

        alert("Venta actualizada exitosamente", 1, "V_venta&id=".$id_venta);

    } catch (mysqli_sql_exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "E_venta&id=".$id_venta);
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "E_venta&id=".$id_venta);
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Venta #<?= $id_venta ?></h5>
            <a href="?p=V_venta&id=<?=$id_venta?>">
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
                            <input name="fact" type="text" class="form-control" id="factura" value="<?= htmlspecialchars($venta['fact'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" value="<?= htmlspecialchars($venta['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input name="fecha" type="date" class="form-control" id="fecha" 
                            value="<?= date('Y-m-d', strtotime($venta['fecha'])) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="factura" class="form-label">Factura de venta</label>
                            <input name="factura" type="text" class="form-control" id="factura" value="<?= htmlspecialchars($venta['factura'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" disabled>
                                <?php
                                $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>" <?= ($venta['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
                                    <?php
                                }
                                ?>
                            </select>
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
                                <option disabled value="">Seleccionar cliente...</option>
                                <?php
                                $clientes = $conn_mysql->query("SELECT id_cli, cod, rs FROM clientes WHERE status = 1 ORDER BY rs");
                                while ($cli = mysqli_fetch_array($clientes)) {
                                    $selected = ($cli['id_cli'] == $venta['id_cli']) ? 'selected' : '';
                                    echo "<option value='{$cli['id_cli']}' $selected>{$cli['cod']} - {$cli['rs']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección de Bodega</label>
                            <select class="form-select" name="id_direc" id="direccion" required>
                                <option disabled value="">Cargando direcciones...</option>
                                <?php
                                // Cargar direcciones del cliente actual
                                if ($venta['id_cli']) {
                                    $direcciones = $conn_mysql->query("
                                        SELECT id_direc, cod_al 
                                        FROM direcciones 
                                        WHERE id_us = {$venta['id_cli']} AND status = 1
                                    ");
                                    while ($dir = mysqli_fetch_array($direcciones)) {
                                        $selected = ($dir['id_direc'] == $venta['id_direc']) ? 'selected' : '';
                                        echo "<option value='{$dir['id_direc']}' $selected>{$dir['cod_al']}</option>";
                                    }
                                }
                                ?>
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
                                <option disabled value="">Seleccionar compra...</option>
                                <?php
                                // Mostrar compras (incluyendo la actualmente seleccionada aunque esté ligada)
                                $compras = $conn_mysql->query("SELECT id_compra, fact, nombre FROM compras WHERE status = '1' AND id_compra = '".$venta['id_compra']."'");
                                while ($comp = mysqli_fetch_array($compras)) {
                                    $selected = ($comp['id_compra'] == $venta['id_compra']) ? 'selected' : '';
                                    echo "<option value='{$comp['id_compra']}' $selected>{$comp['fact']} - {$comp['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" name="id_prod" id="producto" required>
                                <option disabled value="">Seleccionar producto...</option>
                                <?php
                                $productos = $conn_mysql->query("SELECT id_prod, cod, nom_pro FROM productos WHERE status = 1 ORDER BY nom_pro");
                                while ($prod = mysqli_fetch_array($productos)) {
                                    $selected = ($prod['id_prod'] == $venta['id_prod']) ? 'selected' : '';
                                    echo "<option value='{$prod['id_prod']}' $selected>{$prod['cod']} - {$prod['nom_pro']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sección de flete -->
                <div class="form-section mt-4">
                    <h5 class="section-header">Datos del Flete</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="costo_flete" class="form-label">Costo Flete</label>
                            <input name="costo_flete" type="number" step="0.01" class="form-control" id="costo_flete" value="<?= htmlspecialchars($venta['costo_flete'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="flete" class="form-label">Transporte (Flete)</label>
                            <input name="flete" type="text" class="form-control" id="flete" value="<?= htmlspecialchars($venta['flete'] ?? '') ?>" readonly>
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
                            <input name="tara" type="number" step="0.01" class="form-control" id="tara" value="<?= htmlspecialchars($venta['tara'] ?? 0) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="bruto" class="form-label">Bruto</label>
                            <input name="bruto" type="number" step="0.01" class="form-control" id="bruto" value="<?= htmlspecialchars($venta['bruto'] ?? 0) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="neto" class="form-label">Neto</label>
                            <input name="neto" type="number" step="0.01" class="form-control" id="neto" value="<?= htmlspecialchars($venta['neto'] ?? 0) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="peso_cliente" class="form-label">Peso Cliente</label>
                            <input name="peso_cliente" type="number" step="0.01" class="form-control" id="peso_cliente" value="<?= htmlspecialchars($venta['peso_cliente'] ?? 0) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Sección de precio -->
                <div class="form-section mt-4">
                    <h5 class="section-header">Precio</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio</label>
                            <input name="precio" type="number" step="0.01" class="form-control" id="precio" value="<?= htmlspecialchars($venta['precio'] ?? 0) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Campo oculto para el usuario -->
                <input type="hidden" name="id_user" value="<?= $idUser ?>">

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Actualizar Venta</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                        $('#producto').val(response.id_prod);
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
    
    // Cargar datos iniciales si hay una compra seleccionada
    <?php if ($venta['id_compra']): ?>
        cargarDatosCompra(<?= $venta['id_compra'] ?>);
    <?php endif; ?>
});
</script>