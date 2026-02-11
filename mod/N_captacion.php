<?php
// Verificación de permisos - Backend
requirePermiso('CAPTACION_CREAR', 'captacion');

$folio = '';
$folioM = '';
$fecha_seleccionada = $_POST['fecha_captacion'] ?? date('Y-m-d');
$fecha = date('ym', strtotime($fecha_seleccionada));
$mes_actual = date('m', strtotime($fecha_seleccionada));
$anio_actual = date('Y', strtotime($fecha_seleccionada));
$fecha_actual = $fecha_seleccionada . ' ' . date('H:i:s');

// Inicializar array de productos desde sesión
$productos_agregados = $_SESSION['productos_agregados'] ?? [];

if ($zona_seleccionada == 0) {
    $zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1'");
} else {
    $zona_s0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
} 
$zona_s1 = mysqli_fetch_array($zona_s0);

// Consulta para obtener el último folio del mes de la fecha seleccionada
$query = "SELECT folio FROM captacion WHERE status = '1' 
AND YEAR(fecha_captacion) = '$anio_actual'  
AND MONTH(fecha_captacion) = '$mes_actual' 
AND zona = '".$zona_s1['id_zone']."'
ORDER BY folio DESC 
LIMIT 1";

$Capt00 = $conn_mysql->query($query);

if ($Capt00 && $Capt00->num_rows > 0) {
    $Capt01 = $Capt00->fetch_assoc();
    $ultimo_folio = intval($Capt01['folio']);
    $nuevo_numero = $ultimo_folio + 1;
    
    if ($nuevo_numero > 9999) {
        $folio = 'ERROR: Límite alcanzado';
    } else {
        $folio = str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
    }
} else {
    $folio = '0001';
}

// Formato del folio: C-ZONA-AAMM0001
$folioM = "C-".$zona_s1['cod']."-".$fecha.$folio;

// Procesar guardar captación completa
if (isset($_POST['guardar_captacion'])) {
    // Validaciones básicas
    $tipo_flete = $_POST['tipo_flete'] ?? '';
    $bodega_proveedor = $_POST['bodgeProv'] ?? 0;
    $bodega_almacen = $_POST['bodgeAlm'] ?? 0;
    $precio_flete = $_POST['id_preFle'] ?? 0;
    $idFle = $_POST['idFletero'] ?? 0;
    
    // Verificar que hay al menos un producto
    if (empty($productos_agregados)) {
        alert("Debe agregar al menos un producto", 0, "N_captacion");
        exit;
    }
    
    // Validaciones
    if (empty($tipo_flete)) {
        alert("Debe seleccionar el tipo de flete", 0, "N_captacion");
        exit;
    }
    if ($bodega_proveedor <= 0) {
        alert("Debe seleccionar una bodega de proveedor válida", 0, "N_captacion");
        exit;
    }
    if ($bodega_almacen <= 0) {
        alert("Debe seleccionar una bodega de almacén válida", 0, "N_captacion");
        exit;
    }
    if ($precio_flete <= 0) {
        alert("Debe seleccionar un precio de flete válido", 0, "N_captacion");
        exit;
    }
    
    // Verificar correos
    $VerBP0 = $conn_mysql->query("SELECT * FROM direcciones WHERE id_direc = '$bodega_proveedor' AND email != ''");
    $VerBP1 = mysqli_fetch_array($VerBP0);
    if (empty($VerBP1['id_direc'])) {
        alert("Bodega del Proveedor sin correo", 0, "N_captacion"); 
        exit;
    }
    
    if ($idFle > 0) {
        $Verfle0 = $conn_mysql->query("SELECT * FROM transportes WHERE id_transp = '$idFle' AND correo != ''");
        $Verfle1 = mysqli_fetch_array($Verfle0);
        if (empty($Verfle1['id_transp'])) {
            alert("El fletero no cuenta con correo", 0, "N_captacion"); 
            exit;
        }
    }
    
    // Insertar transacción
    try {
        $conn_mysql->begin_transaction();
        
        // 1. Insertar en tabla captacion
        $CaptacionData = [
            'folio' => $folio,
            'fecha_captacion' => $_POST['fecha_captacion'],
            'zona' => $_POST['zona'],
            'id_prov' => $_POST['idProveedor'],
            'id_direc_prov' => $bodega_proveedor,
            'id_alma' => $_POST['idAlmacen'],
            'id_direc_alma' => $bodega_almacen,
            'id_transp' => $idFle,
            'id_user' => $idUser,
            'status' => 1
        ];
        
        $columns = implode(', ', array_keys($CaptacionData));
        $placeholders = implode(', ', array_fill(0, count($CaptacionData), '?'));
        $sql = "INSERT INTO captacion ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        $types = str_repeat('s', count($CaptacionData));
        $stmt->bind_param($types, ...array_values($CaptacionData));
        $stmt->execute();
        
        $id_captacion = $conn_mysql->insert_id;
        
        // 2. Insertar cada producto en captacion_detalle CON CAMPOS CORRECTOS
        foreach ($productos_agregados as $producto) {
            // Determinar tipo de almacenamiento para BD
            $tipo_almacen_bd = '';
            if ($producto['granel_kilos'] > 0 && $producto['pacas_cantidad'] == 0) {
                $tipo_almacen_bd = 'granel';
            } elseif ($producto['granel_kilos'] == 0 && $producto['pacas_cantidad'] > 0) {
                $tipo_almacen_bd = 'pacas';
            } else {
                $tipo_almacen_bd = 'mixto';
            }
            
            $DetalleData = [
                'id_captacion' => $id_captacion,
                'id_prod' => $producto['id_producto'],
                'id_pre_compra' => $producto['id_precio_compra'],
                'granel_kilos' => $producto['granel_kilos'],
                'pacas_cantidad' => $producto['pacas_cantidad'],
                'pacas_kilos' => $producto['pacas_kilos'],
                'pacas_peso_promedio' => $producto['peso_promedio'],
                'total_kilos' => $producto['total_kilos'],
                'tipo_almacen' => $tipo_almacen_bd,
                'observaciones' => $producto['observaciones'],
                'id_user' => $idUser,
                'status' => 1
            ];
            
            $columnsDet = implode(', ', array_keys($DetalleData));
            $placeholdersDet = implode(', ', array_fill(0, count($DetalleData), '?'));
            $sqlDet = "INSERT INTO captacion_detalle ($columnsDet) VALUES ($placeholdersDet)";
            $stmtDet = $conn_mysql->prepare($sqlDet);
            
            $typesDet = str_repeat('s', count($DetalleData));
            $stmtDet->bind_param($typesDet, ...array_values($DetalleData));
            $stmtDet->execute();
        }
        
        // 3. Insertar relación de flete
        $FleteData = [
            'id_captacion' => $id_captacion,
            'id_fletero' => $idFle,
            'id_pre_flete' => $precio_flete
        ];
        
        $columnsFlete = implode(', ', array_keys($FleteData));
        $placeholdersFlete = implode(', ', array_fill(0, count($FleteData), '?'));
        $sqlFlete = "INSERT INTO captacion_flete ($columnsFlete) VALUES ($placeholdersFlete)";
        $stmtFlete = $conn_mysql->prepare($sqlFlete);
        
        $typesFlete = str_repeat('s', count($FleteData));
        $stmtFlete->bind_param($typesFlete, ...array_values($FleteData));
        $stmtFlete->execute();
        
        // 4. Actualizar inventario
        require_once 'config/conexiones.php';
        require_once 'get_captacion.php'; // Asegúrate de que la función esté disponible
        if (function_exists('actualizarInventarioCaptacion')) {
            actualizarInventarioCaptacion($id_captacion, $conn_mysql, $idUser);
        }
        
        $conn_mysql->commit();
        
        // Limpiar productos de la sesión después de guardar exitosamente
        unset($_SESSION['productos_agregados']);
        
        alert("Captación registrada exitosamente", 1, "V_captacion&id=" . $id_captacion);
        logActivity('CREAR', 'Dio de alta una nueva captación '. $id_captacion);
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error: " . $e->getMessage(), 0, "N_captacion");
    }
}

$Primera_zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
$Primera_zona1 = mysqli_fetch_array($Primera_zona0);
$Primer_zona_select = $Primera_zona1['id_zone'];
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Captación</h5>
            <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="" id="formPrincipal">

                <!-- SECCIÓN 1: Información Básica -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="zona" class="form-label">Zona</label>
                            <select class="form-select" name="zona" id="zona" onchange="cambiarZona()">
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' ORDER BY id_zone");
                                } else {
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas where status = '1' AND id_zone = '$zona_seleccionada'");
                                }
                                while ($zona1 = mysqli_fetch_array($zona0)) {
                                    ?>
                                    <option value="<?=$zona1['id_zone']?>"><?=$zona1['PLANTA']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="resulFolio">
                            <label for="folio" class="form-label">Folio</label>
                            <input type="text" id="folio01" class="form-control" value="<?=$folioM?>" disabled>
                            <input type="hidden" name="folio" value="<?=$folio?>">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_captacion" class="form-label">Fecha de Captación</label>
                            <input type="date" name="fecha_captacion" id="fecha_captacion" class="form-control" 
                            value="<?=$fecha_seleccionada?>" max="<?=date('Y-m-d')?>" 
                            onchange="actualizarFolioYPrecios()" required>
                            <small class="text-muted">Puede seleccionar fechas anteriores</small>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: Proveedor y Bodega -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Proveedor y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulProv">
                            <label for="idProveedor" class="form-label">Proveedor</label>
                            <select class="form-select" name="idProveedor" id='idProveedor' onchange="cargarBodegasProveedor()" required>
                                <option selected disabled value="">Selecciona un proveedor...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Prov_id0 = $conn_mysql->query("SELECT * FROM proveedores where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Prov_id1 = mysqli_fetch_array($Prov_id0)) {
                                    ?>
                                    <option value="<?=$Prov_id1['id_prov']?>"><?=$Prov_id1['cod']." / ".$Prov_id1['rs']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="BodePro">
                            <label for="bodgeProv" class="form-label">Bodega del Proveedor</label>
                            <select class="form-select" disabled>
                                <option>Selecciona un proveedor</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 3: Almacén y Bodega -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Almacén y Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulAlm">
                            <label for="idAlmacen" class="form-label">Almacén</label>
                            <select class="form-select" name="idAlmacen" id="idAlmacen" onchange="cargarBodegasAlmacen()" required>
                                <option selected disabled value="">Selecciona un almacén...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Alm_id0 = $conn_mysql->query("SELECT * FROM almacenes where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Alm_id1 = mysqli_fetch_array($Alm_id0)) {
                                    ?>
                                    <option value="<?=$Alm_id1['id_alma']?>"><?=$Alm_id1['cod']." - ".$Alm_id1['nombre']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="BodeAlm">
                            <label for="bodgeAlm" class="form-label">Bodega del Almacén</label>
                            <select class="form-select" disabled>
                                <option>Selecciona un almacén</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 4: Fletero y Tipo de Flete -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Fletero y Tipo de Flete</h5>
                    <div class="row g-3">
                        <div class="col-md-4" id="resulfLE">
                            <label for="idFletero" class="form-label">Fletero</label>
                            <select class="form-select" name="idFletero" id="idFletero" onchange="cargarPrecioFlete()" required>
                                <option selected disabled value="">Selecciona un transportista...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Fle_id0 = $conn_mysql->query("SELECT * FROM transportes where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Fle_id1 = mysqli_fetch_array($Fle_id0)) {
                                    $verCorF = (empty($Fle_id1['correo'])) ? ' ' : '📧' ;
                                    ?>
                                    <option value="<?=$Fle_id1['id_transp']?>"><?=$Fle_id1['placas']." - ".$Fle_id1['razon_so']." ".$verCorF?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="TipoFlete">
                            <label for="tipo_flete" class="form-label">Tipo de Flete</label>
                            <select class="form-select" name="tipo_flete" id="tipo_flete" onchange="cargarPrecioFlete()" required>
                                <option selected disabled value="">Selecciona tipo...</option>
                                <option value="MFT">Por tonelada (MEO)</option>
                                <option value="MFV">Por viaje (MEO)</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="PreFle">
                            <label for="preFl" class="form-label">Precio del flete</label>
                            <select class="form-select" disabled>
                                <option>Selecciona fletero y tipo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 5: Agregar Productos -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Agregar Productos</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-4" id="resulProd">
                            <label for="idProd" class="form-label">Producto</label>
                            <select class="form-select" name="idProd" id="idProd" onchange="cargarPrecioCompra()" required>
                                <option selected disabled value="">Selecciona un producto...</option>
                                <?php
                                if ($zona_seleccionada == 0) {
                                    $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$Primer_zona_select'");    
                                } else {
                                    $Prod_id0 = $conn_mysql->query("SELECT * FROM productos where status = '1' AND zona = '$zona_seleccionada'");
                                }
                                while ($Prod_id1 = mysqli_fetch_array($Prod_id0)) {
                                    ?>
                                    <option value="<?=$Prod_id1['id_prod']?>"><?=$Prod_id1['cod']." - ".$Prod_id1['nom_pro']?></option>
                                    <?php
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="PrePro">
                            <label for="prePD" class="form-label">Precio de compra</label>
                            <select class="form-select" name="id_prePD" id="id_prePD" required disabled>
                                <option>Selecciona un producto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo_almacen" class="form-label">Tipo de Almacenamiento</label>
                            <select class="form-select" name="tipo_almacen" id="tipo_almacen" onchange="cambiarTipoAlmacen(this.value)" required>
                                <option value="granel">Granel (solo kilos)</option>
                                <option value="pacas">Pacas (cantidad y peso)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos para GRANEL -->
                    <div class="row g-3 mt-2" id="campos_granel">
                        <div class="col-md-4">
                            <label for="granel_kilos" class="form-label">Peso en Granel (kilos)</label>
                            <div class="input-group">
                                <input type="number" name="granel_kilos" id="granel_kilos" class="form-control" step="0.01" min="0.01" value="">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- Espacio vacío para mantener layout -->
                        </div>
                        <div class="col-md-4">
                            <!-- Espacio vacío para mantener layout -->
                        </div>
                    </div>

                    <!-- Campos para PACAS (oculto por defecto) -->
                    <div class="row g-3 mt-2" id="campos_pacas" style="display: none;">
                        <div class="col-md-4">
                            <label for="pacas_cantidad" class="form-label">Cantidad de Pacas</label>
                            <input type="number" name="pacas_cantidad" id="pacas_cantidad" class="form-control" min="1" value="">
                        </div>
                        <div class="col-md-4">
                            <label for="pacas_kilos" class="form-label">Peso Total de Pacas (kilos)</label>
                            <div class="input-group">
                                <input type="number" name="pacas_kilos" id="pacas_kilos" class="form-control" step="0.01" min="0.01" value="">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Peso Promedio por Paca</label>
                            <div class="form-control" id="peso_promedio_calc">0.00 kg</div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-10">
                            <label for="observaciones_prod" class="form-label">Observaciones (opcional)</label>
                            <input type="text" name="observaciones_prod" id="observaciones_prod" class="form-control" placeholder="Ej: Producto húmedo, pacas irregulares, etc.">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" onclick="agregarProductoConAjax()" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg me-1"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <div id="error-producto" class="alert alert-danger mt-2" style="display: none;"></div>
                </div>
                
                <!-- SECCIÓN 6: Productos Agregados (Tabla) - Cargada dinámicamente -->
                <div id="tabla-productos-container">
                    <?php include 'generar_tabla_productos.php'; ?>
                </div>
                
                <!-- Botón para guardar toda la captación -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar_captacion" class="btn btn-primary" <?= empty($productos_agregados) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle me-1"></i> Guardar Captación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('#zona, #idProveedor, #idAlmacen, #idFletero, #idProd').select2({
            placeholder: "Selecciona o busca una opción",
            allowClear: false,
            language: "es",
            width: '100%'
        });

        $('#tipo_flete').select2({
            placeholder: "Selecciona tipo de flete",
            allowClear: false,
            language: "es",
            width: '100%'
        });
        
        // Inicializar campos de tipo de almacenamiento
        cambiarTipoAlmacen('granel');
        
        // Event listeners para cálculos automáticos
        $('#pacas_cantidad, #pacas_kilos').on('input', calcularPesoPromedio);
    });

    // Función para agregar producto usando AJAX
    function agregarProductoConAjax() {
        // Ocultar mensaje de error anterior
        $('#error-producto').hide().empty();
        
        // Recolectar datos del formulario
        var datosFormulario = {
            accion: 'agregar_producto',
            idProd: $('#idProd').val(),
            id_prePD: $('#id_prePD').val(),
            tipo_almacen: $('#tipo_almacen').val(),
            granel_kilos: $('#granel_kilos').val() || 0,
            pacas_cantidad: $('#pacas_cantidad').val() || 0,
            pacas_kilos: $('#pacas_kilos').val() || 0,
            observaciones_prod: $('#observaciones_prod').val()
        };
        
        // Validación básica
        if (!datosFormulario.idProd || datosFormulario.idProd <= 0) {
            mostrarError('Seleccione un producto válido');
            return;
        }
        
        // Validación según tipo de almacenamiento
        if (datosFormulario.tipo_almacen == 'granel') {
            if (!datosFormulario.granel_kilos || parseFloat(datosFormulario.granel_kilos) <= 0) {
                mostrarError('Ingrese un peso en granel válido');
                return;
            }
        } else { // pacas
            if (!datosFormulario.pacas_cantidad || parseInt(datosFormulario.pacas_cantidad) <= 0) {
                mostrarError('Ingrese una cantidad de pacas válida');
                return;
            }
            if (!datosFormulario.pacas_kilos || parseFloat(datosFormulario.pacas_kilos) <= 0) {
                mostrarError('Ingrese un peso total de pacas válido');
                return;
            }
        }
        
        // Enviar datos vía AJAX 
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: datosFormulario,
            beforeSend: function() {
                // Mostrar indicador de carga
                $('#tabla-productos-container').html('<div class="text-center py-3"><i class="bi bi-arrow-clockwise bi-spin me-2"></i>Agregando producto...</div>');
            },
            success: function(respuesta) {
                // La respuesta será el HTML de la tabla de productos actualizada
                $('#tabla-productos-container').html(respuesta);
                
                // Limpiar campos del formulario
                $('#granel_kilos, #pacas_cantidad, #pacas_kilos, #observaciones_prod').val('');
                $('#peso_promedio_calc').text('0.00 kg');
                
                // Volver a habilitar/deshabilitar botón de guardar
                var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
                $('button[name="guardar_captacion"]').prop('disabled', !tieneProductos);
            },
            error: function(xhr, status, error) {
                var errorMsg = xhr.responseText || 'Error al agregar producto: ' + error;
                mostrarError(errorMsg);
                console.error('Error AJAX:', error);

                // Recargar la tabla de productos en caso de error
                cargarTablaProductos();
            }
        });
    }

    // Función para cargar la tabla de productos (usada después de eliminar)
    function cargarTablaProductos() {
        $.ajax({
            url: 'generar_tabla_productos.php',
            type: 'GET',
            success: function(respuesta) {
                $('#tabla-productos-container').html(respuesta);
                
                // Volver a habilitar/deshabilitar botón de guardar
                var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
                $('button[name="guardar_captacion"]').prop('disabled', !tieneProductos);
            }
        });
    }

    // Función para eliminar producto via AJAX
    function eliminarProducto(index) {
        if (!confirm('¿Está seguro de eliminar este producto?')) {
            return;
        }
        
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: {
                accion: 'eliminar_producto',
                indice_producto: index
            },
            beforeSend: function() {
                $('#tabla-productos-container').html('<div class="text-center py-3"><i class="bi bi-arrow-clockwise bi-spin me-2"></i>Eliminando producto...</div>');
            },
            success: function(respuesta) {
                // La respuesta será el HTML de la tabla de productos actualizada
                $('#tabla-productos-container').html(respuesta);

                // Volver a habilitar/deshabilitar botón de guardar
                var tieneProductos = $('#tabla-productos-container').find('table').length > 0;
                $('button[name="guardar_captacion"]').prop('disabled', !tieneProductos);
            },
            error: function(xhr, status, error) {
                alert('Error al eliminar producto: ' + error);
                console.error(error);
                cargarTablaProductos();
            }
        });
    }

    // Función para mostrar errores
    function mostrarError(mensaje) {
        $('#error-producto').html(mensaje).show();
        $('#error-producto').get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Función para cambiar zona
    function cambiarZona() {
        var zonaId = $('#zona').val();
        var fecha = $('#fecha_captacion').val();

        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: {
                zona: zonaId,
                fecha_captacion: fecha,
                accion: 'folio'
            },
            success: function(response) {
                $('#resulFolio').html(response);
            // Recargar selects basados en zona
                cargarProveedores(zonaId);
                cargarAlmacenes(zonaId);
                cargarFleteros(zonaId);
                cargarProductos(zonaId);
            }
        });
    }

    // Función para cargar proveedores por zona
    function cargarProveedores(zonaId) {
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                zonaProveedor: zonaId,
                accion: 'proveedores'
            },
            success: function(response) {
                $('#resulProv').html(response);
                $('#idProveedor').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar almacenes por zona
    function cargarAlmacenes(zonaId) {
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                zonaAlmacen: zonaId,
                accion: 'almacenes'
            },
            success: function(response) {
                $('#resulAlm').html(response);
                $('#idAlmacen').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar fleteros por zona
    function cargarFleteros(zonaId) {
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                zonaFletero: zonaId,
                accion: 'fleteros'
            },
            success: function(response) {
                $('#resulfLE').html(response);
                $('#idFletero').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar productos por zona
    function cargarProductos(zonaId) {
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                zonaProducto: zonaId,
                accion: 'productos'
            },
            success: function(response) {
                $('#resulProd').html(response);
                $('#idProd').select2({
                    placeholder: "Selecciona o busca una opción",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                });
            }
        });
    }

    // Función para cargar bodegas del proveedor
    function cargarBodegasProveedor() {
        var idProveedor = $('#idProveedor').val();

        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                idProveedor: idProveedor,
                accion: 'bodegas_proveedor'
            },
            beforeSend: function() {
                $('#BodePro').html('<label class="form-label">Bodega del Proveedor</label><div class="form-control">Cargando...</div>');
            },
            success: function(response) {
                $('#BodePro').html(response);
                $('#bodgeProv').select2({
                    placeholder: "Selecciona bodega",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                }).on('change', cargarPrecioFlete);
            }
        });
    }

    // Función para cargar bodegas del almacén
    function cargarBodegasAlmacen() {
        var idAlmacen = $('#idAlmacen').val();

        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: { 
                idAlmacen: idAlmacen,
                accion: 'bodegas_almacen'
            },
            beforeSend: function() {
                $('#BodeAlm').html('<label class="form-label">Bodega del Almacén</label><div class="form-control">Cargando...</div>');
            },
            success: function(response) {
                $('#BodeAlm').html(response);
                $('#bodgeAlm').select2({
                    placeholder: "Selecciona bodega",
                    allowClear: false,
                    language: "es",
                    width: '100%'
                }).on('change', cargarPrecioFlete);
            }
        });
    }

    // Función para cargar precio de flete
    function cargarPrecioFlete() {
        var idFletero = $('#idFletero').val();
        var tipoFlete = $('#tipo_flete').val();
        var bodgeProv = $('#bodgeProv').val();
        var bodgeAlm = $('#bodgeAlm').val();
        var fechaCaptacion = $('#fecha_captacion').val();

        if (!idFletero || !tipoFlete || !bodgeProv || !bodgeAlm || !fechaCaptacion) {
            $('#PreFle').html('<label class="form-label">Precio del flete</label><select class="form-select" disabled><option>Complete todos los campos</option></select>');
            return;
        }

        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: {
                idFletero: idFletero,
                tipoFlete: tipoFlete,
                origen: bodgeProv,
                destino: bodgeAlm,
                fechaCaptacion: fechaCaptacion,
                accion: 'precio_flete'
            },
            beforeSend: function() {
                $('#PreFle').html('<label class="form-label">Precio del flete</label><div class="form-control">Buscando precios...</div>');
            },
            success: function(response) {
                $('#PreFle').html(response);
            }
        });
    }

    // Función para cargar precio de compra
    function cargarPrecioCompra() {
        var idProd = $('#idProd').val();
        var fechaCaptacion = $('#fecha_captacion').val();

        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: {
                idProd: idProd,
                fechaCaptacion: fechaCaptacion,
                accion: 'precio_compra'
            },
            beforeSend: function() {
                $('#PrePro').html('<label class="form-label">Precio de compra</label><div class="form-control">Buscando precios...</div>');
            },
            success: function(response) {
                $('#PrePro').html(response);
            }
        });
    }

    // Función para actualizar folio y precios cuando cambia la fecha
    function actualizarFolioYPrecios() {
        var fechaSeleccionada = $('#fecha_captacion').val();
        var zonaId = $('#zona').val();

        if (!fechaSeleccionada || !zonaId) return;

        // Actualizar folio
        $.ajax({
            url: 'get_captacion.php',
            type: 'POST',
            data: {
                zona: zonaId,
                fecha_captacion: fechaSeleccionada,
                accion: 'folio'
            },
            beforeSend: function() {
                $('#resulFolio').html('<label class="form-label">Folio</label><div class="form-control">Actualizando...</div>');
            },
            success: function(response) {
                $('#resulFolio').html(response);

                // Actualizar precios si ya están seleccionados
                if ($('#idProd').val()) cargarPrecioCompra();
                if ($('#idFletero').val() && $('#tipo_flete').val()) cargarPrecioFlete();
            }
        });
    }

    // Cerrar ventana
    $('#btnCerrar').click(function() {
        window.close();
    });

    // Función para manejar tipos de almacenamiento
    function cambiarTipoAlmacen(tipo) {
        // Ocultar todos los campos
        $('#campos_granel, #campos_pacas').hide();

         // Mostrar los campos correspondientes
    if (tipo === 'granel') {
        $('#campos_granel').show();
        $('#granel_kilos').prop('required', false);
        $('#pacas_cantidad, #pacas_kilos').prop('required', false);
    } else { // pacas
        $('#campos_pacas').show();
        $('#granel_kilos').prop('required', false);
        $('#pacas_cantidad, #pacas_kilos').prop('required', false);
    }

        // Actualizar cálculos
        calcularPesoPromedio();
    }

    // Calcular peso promedio por paca
    function calcularPesoPromedio() {
        var cantidad = parseFloat($('#pacas_cantidad').val()) || 0;
        var kilos = parseFloat($('#pacas_kilos').val()) || 0;
    
        if (cantidad > 0 && kilos > 0) {
            var promedio = kilos / cantidad;
            $('#peso_promedio_calc').text(promedio.toFixed(2) + ' kg');
        } else {
            $('#peso_promedio_calc').text('0.00 kg');
        }
    }
</script>