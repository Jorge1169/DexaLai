<?php
// Verificar si se recibió el ID de la recolección a editar
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ?p=recoleccion");
    exit;
}

$id_recoleccion = $_GET['id'];

$query = "SELECT 
r.*,
p.rs AS razon_social_proveedor,
p.cod AS cod_proveedor,
dp.noma AS nombre_bodega_proveedor,
dp.cod_al AS cod_bodega_proveedor,
t.razon_so AS razon_social_fletero,
t.placas AS placas_fletero,
c.nombre AS nombre_cliente,
c.cod AS cod_cliente,
dc.noma AS nombre_bodega_cliente,
dc.cod_al AS cod_bodega_cliente,
pr.nom_pro AS nombre_producto,
pr.cod AS cod_producto,
z.cod AS cod_zona,
z.nom AS nombre_zona,
pf.precio AS precio_flete,
pc.precio AS precio_compra,
pv.precio AS precio_venta,
prc.id_prod,
prc.id_cprecio_c,
prc.id_cprecio_v
FROM recoleccion r
LEFT JOIN proveedores p ON r.id_prov = p.id_prov
LEFT JOIN direcciones dp ON r.id_direc_prov = dp.id_direc
LEFT JOIN transportes t ON r.id_transp = t.id_transp
LEFT JOIN clientes c ON r.id_cli = c.id_cli
LEFT JOIN direcciones dc ON r.id_direc_cli = dc.id_direc
LEFT JOIN usuarios u ON r.id_user = u.id_user
LEFT JOIN zonas z ON r.zona = z.id_zone
LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
WHERE r.id_recol = ?";

$stmt = $conn_mysql->prepare($query);
$stmt->bind_param("i", $id_recoleccion);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ?p=recoleccion");
    exit;
}

$recoleccion = $result->fetch_assoc();

// Generar el folio completo para mostrar
$folio_completo = $recoleccion['cod_zona'] . "-" . date('ym', strtotime($recoleccion['fecha_r'])) . str_pad($recoleccion['folio'], 4, '0', STR_PAD_LEFT);

// Procesar el formulario de actualización
if (isset($_POST['actualizar01'])) {
    // Validaciones y procesamiento similar a N_recoleccion pero con UPDATE
    $fecha_factura = $_POST['FecaFac'] ?? date('Y-m-d');
    
    if (!DateTime::createFromFormat('Y-m-d', $fecha_factura)) {
        alert("Formato de fecha inválido. Use YYYY-MM-DD", 2, "E_recoleccion&id=" . $id_recoleccion);
        exit;
    }

    $PFC = $_POST['id_preFle'] ?? 0;
    $PDC = $_POST['id_prePD'] ?? 0;
    $PDV = $_POST['id_prePDv'] ?? 0;
    $BODprov = $_POST['bodgeProv'] ?? 0;
    $idFle = $_POST['idFletero'] ?? 0;
    // Nuevas validaciones
    $tipo_flete = $_POST['tipo_flete'] ?? '';
    // NUEVAS VALIDACIONES PARA EL NUEVO SISTEMA
    if (empty($tipo_flete)) {
        alert("Debe seleccionar el tipo de flete", 0, "E_recoleccion&id=" . $id_recoleccion);
        exit;
    }
    if ($BODprov <= 0) {
        alert("Debe seleccionar una bodega de proveedor válida", 0, "E_recoleccion&id=" . $id_recoleccion);
        exit;
    }

    // Validaciones
    if ($PFC <= 0 || $PDC <= 0 || $PDV <= 0) {
        alert("Debe seleccionar precios válidos", 0, "E_recoleccion&id=" . $id_recoleccion);
        exit;
    }

    // Validar correos (similar a N_recoleccion)
    $VerBP0 = $conn_mysql->query("SELECT * FROM direcciones WHERE id_direc = '$BODprov' AND email != ''");
    $VerBP1 = mysqli_fetch_array($VerBP0);
    if (empty($VerBP1['id_direc'])) {
        alert("Bodega del Proveedor sin correo", 0, "E_recoleccion&id=" . $id_recoleccion); 
        exit;
    }

    $Verfle0 = $conn_mysql->query("SELECT * FROM transportes WHERE id_transp = '$idFle' AND correo != ''");
    $Verfle1 = mysqli_fetch_array($Verfle0);
    if (empty($Verfle1['id_transp'])) {
        alert("El fletero no cuenta con correo", 0, "E_recoleccion&id=" . $id_recoleccion); 
        exit;
    }
    // Actualizar datos
    try {
        $conn_mysql->begin_transaction();

    // Actualizar tabla recoleccion
        $updateRecoleData = [
            'id_prov' => $_POST['idProveedor'],
            'id_direc_prov' => $BODprov,
            'id_transp' => $idFle,
            'pre_flete' => $PFC,
            'id_cli' => $_POST['idCliente'],
            'id_direc_cli' => $_POST['bodgeCli'],
            'factura_v' => $_POST['FacVen'],
            'fecha_v' => $fecha_factura,
            'factus_v_corr' => 0
        ];

        $setClause = implode(' = ?, ', array_keys($updateRecoleData)) . ' = ?';
        $sql = "UPDATE recoleccion SET $setClause WHERE id_recol = ?";
        $stmt = $conn_mysql->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn_mysql->error);
        }
        
        $values = array_values($updateRecoleData);
    $values[] = $id_recoleccion; // Agregar el ID para el WHERE
    
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    
    if ($stmt->errno) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    // Actualizar tabla producto_recole
    $updateProdData = [
        'id_prod' => $_POST['idProd'],
        'id_cprecio_c' => $PDC,
        'id_cprecio_v' => $PDV
    ];

    $setClauseProd = implode(' = ?, ', array_keys($updateProdData)) . ' = ?';
    $sqlProd = "UPDATE producto_recole SET $setClauseProd WHERE id_recol = ?";
    $stmtProd = $conn_mysql->prepare($sqlProd);
    
    if (!$stmtProd) {
        throw new Exception("Error preparing product statement: " . $conn_mysql->error);
    }
    // SI EL PRECIO TIENE UN COSTO DE 0 PESOS SE DEBE DE ACTUALIZAR LA FACTURA Y EL PRECIO

    $BusPrecio0 = $conn_mysql->query("SELECT * FROM precios WHERE id_precio = '$PFC'");

    $BusPrecio1 = mysqli_fetch_array($BusPrecio0);
    if ($BusPrecio1['precio'] == 0) {
        $conn_mysql->query("UPDATE recoleccion SET factura_fle = 'N/A' WHERE id_recol = '$id_recoleccion'");
    }

    $valuesProd = array_values($updateProdData);
    $valuesProd[] = $id_recoleccion; // Agregar el ID para el WHERE
    
    $typesProd = str_repeat('s', count($valuesProd));
    $stmtProd->bind_param($typesProd, ...$valuesProd);
    $stmtProd->execute();
    
    if ($stmtProd->errno) {
        throw new Exception("Error executing product statement: " . $stmtProd->error);
    }

    $conn_mysql->commit();
    alert("Recolección actualizada exitosamente", 1, "V_recoleccion&id=" .$id_recoleccion);
    logActivity('EDITAR', 'Edito la recoleccion '. $id_recoleccion);
    
} catch (mysqli_sql_exception $e) {
    $conn_mysql->rollback();
    //error_log("Error SQL: " . $e->getMessage());
    alert("Error: " . $e->getMessage(), 0, "E_recoleccion&id=" . $id_recoleccion);
} catch (Exception $e) {
    $conn_mysql->rollback();
    //error_log("Error: " . $e->getMessage());
    alert("Error: " . $e->getMessage(), 0, "E_recoleccion&id=" . $id_recoleccion);
}
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar recolección</h5>
            <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
            <script>
              document.getElementById('btnCerrar').addEventListener('click', function() {
                window.close();
            });
        </script>
    </div>
    <div class="card-body">
        <form class="forms-sample" method="post" action="">
            <div class="form-section shadow-sm">
                <h5 class="section-header">Información Básica</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="zona" class="form-label">Zona</label>
                        <input type="text" class="form-control" value="<?=htmlspecialchars($recoleccion['nombre_zona'])?>" disabled>
                        <input type="hidden" name="zona" value="<?=$recoleccion['zona']?>">
                    </div>
                    <div class="col-md-4">
                        <label for="folio" class="form-label">Folio</label>
                        <input type="text" class="form-control" value="<?=$folio_completo?>" disabled>
                        <input type="hidden" name="folio" value="<?=$recoleccion['folio']?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha de Recolección</label>
                        <input type="text" value="<?=date('Y-m-d', strtotime($recoleccion['fecha_r']))?>" class="form-control" disabled>
                    </div>
                </div>
            </div>


            <div class="form-section shadow-sm">
                <h5 class="section-header">Proveedor y bodega:</h5>
                <div class="row g-3">
                    <div class="col-md-4" id="resulProv">
                        <label for="IdProveedor" class="form-label">Proveedor</label>
                        <select class="form-select" name="idProveedor" id='idProveedor' onchange="idProv1()" required>
                            <option disabled value="">Selecciona un proveedor...</option>
                            <?php
                                // Filtrar proveedores por la zona de la recolección
                            $Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '".$recoleccion['zona']."'");

                            while ($Prov_id1 = mysqli_fetch_array($Prov_id0)) {
                                $selected = ($Prov_id1['id_prov'] == $recoleccion['id_prov']) ? 'selected' : '';
                                ?>
                                <option value="<?=$Prov_id1['id_prov']?>" <?=$selected?>><?=$Prov_id1['cod']." / ".$Prov_id1['rs']?></option>
                                <?php
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="BodePro">
                        <label for="Bodegas" class="form-label">Bodega Proveedor</label>
                        <select class="form-select" name="bodgeProv" id="bodgeProv" required>
                            <option disabled value="">Cargando bodegas...</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Nuevas seleccion -->
            <div class="form-section shadow-sm">
                <h5 class="section-header">Cliente y bodega:</h5>
                <div class="row g-3">
                    <div class="col-md-4" id="resulCli">
                        <label for="Cliente" class="form-label">Cliente</label>
                        <select class="form-select" name="idCliente" id="idCliente" onchange="idcl()" required>
                            <option disabled value="">Selecciona un cliente...</option>
                            <?php
                                // Filtrar clientes por la zona de la recolección
                            $Cli_id0 = $conn_mysql->query("SELECT * FROM clientes where status = '1' AND zona = '".$recoleccion['zona']."'");

                            while ($Cli_id1 = mysqli_fetch_array($Cli_id0)) {
                                $selected = ($Cli_id1['id_cli'] == $recoleccion['id_cli']) ? 'selected' : '';
                                ?>
                                <option value="<?=$Cli_id1['id_cli']?>" <?=$selected?>><?=$Cli_id1['cod']." - ".$Cli_id1['nombre']?></option>
                                <?php
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="CliEntrega">
                        <label for="BodCli" class="form-label">Bodega Cliente</label>
                        <select class="form-select" name="bodgeCli" id="bodgeCli" required>
                            <option disabled value="">Cargando bodegas...</option>
                        </select>
                    </div>
                </div>
            </div>


            <div class="form-section shadow-sm">
                <h5 class="section-header">Fletero y tipo de flete:</h5>
                <div class="row g-3">
                    <div class="col-md-4" id="resulfLE">
                        <label for="Fletero" class="form-label">Fletero</label>
                        <select class="form-select" name="idFletero" id="idFletero" onchange="idFl()" required>
                            <option disabled value="">Selecciona un transportista...</option>
                            <?php
                                // Filtrar fleteros por la zona de la recolección
                            $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '".$recoleccion['zona']."'");

                            while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
                                $verCorF = (empty($Fle_id1['correo'])) ? ' ' : '📧' ;
                                $selected = ($Fle_id1['id_transp'] == $recoleccion['id_transp']) ? 'selected' : '';
                                ?>
                                <option value="<?=$Fle_id1['id_transp']?>" <?=$selected?>><?=$Fle_id1['placas']." - ".$Fle_id1['razon_so']." ".$verCorF?></option>
                                <?php
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="TipoFlete">
                        <label for="tipo_flete" class="form-label">Tipo de Flete</label>
                        <select class="form-select" name="tipo_flete" id="tipo_flete" onchange="idFl()" required>
                            <option disabled value="">Selecciona tipo...</option>
                            <option value="FT" <?=($recoleccion['tipo_flete'] ?? 'FT') == 'FT' ? 'selected' : ''?>>Por tonelada</option>
                            <option value="FV" <?=($recoleccion['tipo_flete'] ?? 'FT') == 'FV' ? 'selected' : ''?>>Por viaje</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="PreFle">
                        <label for="preFl" class="form-label">Precio del flete</label>
                        <select class="form-select" name="id_preFle" id="id_preFle" required>
                            <option disabled value="">Cargando precios...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section shadow-sm">
                <h5 class="section-header">Producto y precios:</h5>
                <div class="row g-3">
                    <div class="col-md-4" id="resulProd">
                        <label for="Fletero" class="form-label">Producto</label>
                        <select class="form-select" name="idProd" id="idProd" onchange="idPd();idPdv()" required>
                            <option disabled value="">Selecciona un producto...</option>
                            <?php
                                // Filtrar productos por la zona de la recolección
                            $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '".$recoleccion['zona']."'");

                            while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
                                $selected = ($Prod_id1['id_prod'] == $recoleccion['id_prod']) ? 'selected' : '';
                                ?>
                                <option value="<?=$Prod_id1['id_prod']?>" <?=$selected?>><?=$Prod_id1['cod']." - ".$Prod_id1['nom_pro']?></option>
                                <?php
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="PrePro">
                        <label for="prePD" class="form-label">Precio de compra</label>
                        <select class="form-select" name="id_prePD" id="id_prePD" required>
                            <option disabled value="">Cargando precios...</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="PreProv">
                        <label for="prePDv" class="form-label">Precio de venta</label>
                        <select class="form-select" name="id_prePDv" id="id_prePDv" required>
                            <option disabled value="">Cargando precios...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section shadow-sm">
                <h5 class="section-header">Información de factura: </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="FacVen" class="form-label">Factura de venta</label>
                        <input type="text" name="FacVen" id="FacVen" class="form-control" value="<?=htmlspecialchars($recoleccion['factura_v'])?>" required>
                    </div>
                    <script>
                        const FacVenInput = document.getElementById('FacVen');
                        // Eliminar espacios en tiempo real
                        FacVenInput.addEventListener('input', function() {
                            this.value = this.value.replace(/\s+/g, '');
                        });
                        
                        // Validar antes de enviar formulario
                        document.querySelector('form').addEventListener('submit', function(e) {
                            const FacVenValue = FacVenInput.value.trim();
                            
                            if (FacVenValue.includes(' ')) {
                                e.preventDefault();
                                alert('El campo remisión no debe contener espacios');
                                FacVenInput.focus();
                            }
                        });
                    </script>
                    <div class="col-md-4">
                        <label for="FecaFac" class="form-label">Fecha de factura</label>
                        <input type="date" name="FecaFac" id="FecaFac" class="form-control" value="<?=date('Y-m-d', strtotime($recoleccion['fecha_v']))?>" required>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-md-end mt-4">
                <button type="submit" name="actualizar01" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div>
<!-- Nuevas seleccion -->
<!-- Scripts para Select2 -->
<script>
    $(document).ready(function() {
        $('#idFletero').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es"
        });
        
        $('#idProd').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es"
        });
        
        $('#idCliente').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es"
        });
        
        $('#idProveedor').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: true,
            language: "es"
        });

        $('#tipo_flete').select2({
            placeholder: "Selecciona tipo de flete",
            allowClear: false,
            language: "es"
        });

        // Obtener el tipo de flete del precio actual (si existe)
        function obtenerTipoFleteActual() {
            var precioFleteId = <?=$recoleccion['pre_flete']?>;
            if (precioFleteId > 0) {
                // Hacer una consulta AJAX para obtener el tipo del precio actual
                $.ajax({
                    url: 'get_recoleccion.php',
                    type: 'POST',
                    data: { 
                        'obtenerTipoFlete': precioFleteId 
                    },
                    success: function(response) {
                        if (response && response !== '') {
                            $('#tipo_flete').val(response).trigger('change');
                        }
                    }
                });
            }
        }
        // Cargar los selects dependientes con los valores actuales
        setTimeout(function() {
            // Primero cargar bodegas del proveedor
            if ($('#idProveedor').val()) {
                idProv1();
            }
            
            // Cargar bodegas del cliente
            if ($('#idCliente').val()) {
                idcl();
            }
            
            // Obtener y establecer el tipo de flete
            obtenerTipoFleteActual();
            
            // Cargar precios del producto
            if ($('#idProd').val()) {
                setTimeout(function() {
                    idPd();
                    idPdv();
                }, 800);
            }
            
            // Cargar precios del fletero (después de cargar bodegas)
            if ($('#idFletero').val()) {
                setTimeout(function() {
                    idFl();
                }, 1000);
            }
        }, 500);
    });

    // Función actualizada para obtener precios de flete (igual que en N_recoleccion)
    function idFl() {
        var idFletero = document.getElementById('idFletero').value;
        var tipoFlete = document.getElementById('tipo_flete').value;
        var bodegaProv = document.getElementById('bodgeProv') ? document.getElementById('bodgeProv').value : 0;
        var bodegaCli = document.getElementById('bodgeCli') ? document.getElementById('bodgeCli').value : 0;
        var recoleccionId = <?=$id_recoleccion?>;
        var fechaRecoleccion = '<?=$recoleccion['fecha_r']?>';

        if (idFletero && tipoFlete && bodegaProv && bodegaCli) {
            var parametros = {
                "idFleteroEdit": idFletero,
                "tipoFleteEdit": tipoFlete,
                "origenEdit": bodegaProv,
                "destinoEdit": bodegaCli,
                "recoleccionId": recoleccionId,
                "fechaRecoleccion": fechaRecoleccion
            };

            $.ajax({
                data: parametros,
                url: 'get_recoleccion.php',
                type: 'POST',
                beforeSend: function () {
                    $('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Buscando precios...</option></select>');
                },
                success: function (mensaje) {
                    $('#PreFle').html(mensaje);
                    setTimeout(function() {
                        if ($('#id_preFle').length) {
                            $('#id_preFle').val(<?=$recoleccion['pre_flete']?>).trigger('change');
                        }
                    }, 300);
                },
                error: function() {
                    $('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Error al cargar precios</option></select>');
                }
            });
        }
    }

    // Actualizar precios de flete cuando cambien las bodegas
    function actualizarPrecioFlete() {
        if (document.getElementById('idFletero') && document.getElementById('tipo_flete')) {
            idFl();
        }
    }
</script>

<!-- Los mismos scripts AJAX que usas en N_recoleccion (sin cambios) -->
<script>
    function idcl() {
        var idCliente = document.getElementById('idCliente').value;
        var recoleccionId = <?=$id_recoleccion?>;
        var parametros = {
            "idCliente": idCliente,
            "recoleccionId": recoleccionId
        };
        
        $.ajax({
            data: parametros,
            url: 'get_recoleccion.php',
            type: 'POST',
            beforeSend: function () {
                $('#CliEntrega').html("<option>Cargando...</option>");
            },
            success: function (mensaje) {
                $('#CliEntrega').html(mensaje);
                // Actualizar precio flete cuando cambie la bodega cliente
                $(document).ready(function() {
                    if ($('#bodgeCli').length) {
                        $('#bodgeCli').select2({
                            placeholder: "Selecciona bodega",
                            allowClear: true,
                            language: "es"
                        }).on('change', actualizarPrecioFlete);
                        
                        // Establecer la bodega actual después de cargar
                        setTimeout(function() {
                            $('#bodgeCli').val(<?=$recoleccion['id_direc_cli']?>).trigger('change');
                        }, 300);
                    }
                });
            }
        });
    }
</script>

<script>
    function idPd() {
        var idProd = document.getElementById('idProd').value;
        var recoleccionId = <?=$id_recoleccion?>;
        var fechaRecoleccion = '<?=$recoleccion['fecha_r']?>';

        var parametros = {
            "idProdEdit": idProd,
            "recoleccionId": recoleccionId,
            "fechaRecoleccion": fechaRecoleccion
        };

        $.ajax({
            data: parametros,
            url: 'get_recoleccion.php',
            type: 'POST',
            beforeSend: function () {
                $('#PrePro').html("<option>Cargando...</option>");
            },
            success: function (mensaje) {
                $('#PrePro').html(mensaje);
                setTimeout(function() {
                    $('#id_prePD').val(<?=$recoleccion['id_cprecio_c']?>).trigger('change');
                }, 300);
            }
        });
    }
</script>

<script>
    function idPdv() {
        var idProd = document.getElementById('idProd').value;
        var bodgeCli = document.getElementById('bodgeCli').value;
        var recoleccionId = <?=$id_recoleccion?>;
        var fechaRecoleccion = '<?=$recoleccion['fecha_r']?>';

        var parametros = {
            "idProdVEdit": idProd,
            "bodgeCli": bodgeCli,
            "recoleccionId": recoleccionId,
            "fechaRecoleccion": fechaRecoleccion
        };

        $.ajax({
            data: parametros,
            url: 'get_recoleccion.php',
            type: 'POST',
            beforeSend: function () {
                $('#PreProv').html("<option>Cargando...</option>");
            },
            success: function (mensaje) {
                $('#PreProv').html(mensaje);
                setTimeout(function() {
                    $('#id_prePDv').val(<?=$recoleccion['id_cprecio_v']?>).trigger('change');
                }, 300);
            }
        });
    }
</script>

<script>
    function idProv1() {
        var idProveedor = document.getElementById('idProveedor').value;
        var recoleccionId = <?=$id_recoleccion?>;
        var parametros = {
            "idProveedor": idProveedor,
            "recoleccionId": recoleccionId
        };
        
        $.ajax({
            data: parametros,
            url: 'get_recoleccion.php',
            type: 'POST',
            beforeSend: function () {
                $('#BodePro').html("<option>Cargando...</option>");
            },
            success: function (mensaje) {
                $('#BodePro').html(mensaje);
                // Inicializar Select2 y actualizar precio flete
                $(document).ready(function() {
                    if ($('#bodgeProv').length) {
                        $('#bodgeProv').select2({
                            placeholder: "Selecciona bodega",
                            allowClear: true,
                            language: "es"
                        }).on('change', actualizarPrecioFlete);
                        
                        // Establecer la bodega actual después de cargar
                        setTimeout(function() {
                            $('#bodgeProv').val(<?=$recoleccion['id_direc_prov']?>).trigger('change');
                        }, 300);
                    }
                });
            }
        });
    }
    $(document).ajaxStart(function(){
        $('.card-body').addClass('loading-ajax');
    }).ajaxStop(function(){
        $('.card-body').removeClass('loading-ajax');
    });

// Mejora: Validación en tiempo real
    function validarFormularioCompleto() {
        var camposRequeridos = [
            '#idProveedor', '#bodgeProv', '#idCliente', '#bodgeCli',
            '#idFletero', '#tipo_flete', '#id_preFle', '#idProd',
            '#id_prePD', '#id_prePDv', '#FacVen', '#FecaFac'
        ];

        var completo = true;
        camposRequeridos.forEach(function(campo) {
            var elemento = $(campo);
            if (elemento.length && (!elemento.val() || elemento.val() === '')) {
                completo = false;
            }
        });

        $('button[name="actualizar01"]').prop('disabled', !completo);
    }

// Ejecutar validación cuando cambien los campos
    $('select, input').on('change', function() {
        setTimeout(validarFormularioCompleto, 100);
    });
</script>