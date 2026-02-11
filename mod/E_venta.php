<?php
// E_venta.php - Módulo para Editar una Venta Existente (LÓGICA CORREGIDA)

// Verificación de permisos - Backend
requirePermiso('VENTAS_EDITAR', 'ventas');

// Obtener ID de la venta a editar
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_venta <= 0) {
    alert("ID de venta no válido", 0, "ventas_info");
    exit;
}

// Obtener información completa de la venta
$sql_venta = "SELECT v.*, 
                     vd.id_prod, vd.id_pre_venta, vd.pacas_cantidad, vd.total_kilos, vd.observaciones,
                     vf.id_fletero, vf.id_pre_flete,
                     p.cod as cod_producto, p.nom_pro as nombre_producto,
                     pr.precio as precio_actual,
                     CONCAT('V-', z.cod, '-', 
                           DATE_FORMAT(v.fecha_venta, '%y%m'), 
                           LPAD(v.folio, 4, '0')) as folio_compuesto,
                     DATE_FORMAT(v.fecha_venta, '%Y-%m-%d') as fecha_venta_form,
                     c.cod as cod_cliente, c.nombre as nombre_cliente,
                     a.cod as cod_almacen, a.nombre as nombre_almacen,
                     z.cod as nombre_zona
              FROM ventas v
              LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta AND vd.status = 1
              LEFT JOIN venta_flete vf ON v.id_venta = vf.id_venta
              LEFT JOIN productos p ON vd.id_prod = p.id_prod
              LEFT JOIN precios pr ON vd.id_pre_venta = pr.id_precio
              LEFT JOIN zonas z ON v.zona = z.id_zone
              LEFT JOIN clientes c ON v.id_cliente = c.id_cli
              LEFT JOIN almacenes a ON v.id_alma = a.id_alma
              WHERE v.id_venta = ? AND v.status = 1";
$stmt_venta = $conn_mysql->prepare($sql_venta);
$stmt_venta->bind_param('i', $id_venta);
$stmt_venta->execute();
$result_venta = $stmt_venta->get_result();

if (!$result_venta || $result_venta->num_rows == 0) {
    alert("Venta no encontrada", 0, "ventas_info");
    exit;
}

$venta = $result_venta->fetch_assoc();

// Obtener información actual del inventario para este producto/bodega
$sql_inventario_actual = "SELECT 
                         id_inventario,
                         pacas_cantidad_disponible,
                         pacas_kilos_disponible,
                         total_kilos_disponible
                         FROM inventario_bodega 
                         WHERE id_prod = ? AND id_bodega = ?";
$stmt_inv_actual = $conn_mysql->prepare($sql_inventario_actual);
$stmt_inv_actual->bind_param('ii', $venta['id_prod'], $venta['id_direc_alma']);
$stmt_inv_actual->execute();
$result_inv_actual = $stmt_inv_actual->get_result();
$inventario_actual = $result_inv_actual->fetch_assoc();

// Obtener stock adicional disponible (solo lo extra que NO está en la venta actual)
$pacas_disponibles_extra = $inventario_actual ? $inventario_actual['pacas_cantidad_disponible'] : 0;
$kilos_disponibles_extra = $inventario_actual ? $inventario_actual['pacas_kilos_disponible'] : 0;

// Obtener precios de venta disponibles para la fecha seleccionada
$fecha_venta_actual = $venta['fecha_venta_form'];

// Procesar actualización de venta
if (isset($_POST['actualizar_venta'])) {
    $nuevo_precio_id = $_POST['id_precio_venta'] ?? $venta['id_pre_venta'];
    $nuevas_pacas = intval($_POST['cantidad_pacas'] ?? $venta['pacas_cantidad']);
    $nuevos_kilos = floatval($_POST['kilos_venta'] ?? $venta['total_kilos']);
    $nueva_fecha = $_POST['fecha_venta'] ?? $venta['fecha_venta_form'];
    $nuevas_observaciones = $_POST['observaciones_venta'] ?? $venta['observaciones'];
    
    // Calcular diferencias
    $diferencia_pacas = $nuevas_pacas - $venta['pacas_cantidad'];
    $diferencia_kilos = $nuevos_kilos - $venta['total_kilos'];
    
    // Validaciones
    $errores = [];
    
    // Validar que haya cambios
    if ($nuevo_precio_id == $venta['id_pre_venta'] && 
        $nuevas_pacas == $venta['pacas_cantidad'] && 
        $nuevos_kilos == $venta['total_kilos'] &&
        $nueva_fecha == $venta['fecha_venta_form'] &&
        $nuevas_observaciones == $venta['observaciones']) {
        $errores[] = "No se realizaron cambios";
    }
    
    // Validar si se quieren sacar MÁS pacas/kilos (diferencia positiva)
    if ($diferencia_pacas > 0 && $diferencia_pacas > $pacas_disponibles_extra) {
        $errores[] = "No hay suficientes pacas disponibles. Disponibles extra: " . $pacas_disponibles_extra;
    }
    
    if ($diferencia_kilos > 0 && $diferencia_kilos > $kilos_disponibles_extra) {
        $errores[] = "No hay suficientes kilos disponibles. Disponibles extra: " . number_format($kilos_disponibles_extra, 2) . " kg";
    }
    
    if (!empty($errores)) {
        alert(implode("<br>", $errores), 2, "E_venta&id=" . $id_venta);
        exit;
    }
    
    try {
        $conn_mysql->begin_transaction();
        
        // 1. Obtener movimiento de inventario original de esta venta
        $sql_movimiento_original = "SELECT * FROM movimiento_inventario 
                                   WHERE id_venta = ? 
                                   AND tipo_movimiento = 'salida'
                                   ORDER BY created_at DESC LIMIT 1";
        $stmt_mov_original = $conn_mysql->prepare($sql_movimiento_original);
        $stmt_mov_original->bind_param('i', $id_venta);
        $stmt_mov_original->execute();
        $result_mov_original = $stmt_mov_original->get_result();
        $movimiento_original = $result_mov_original->fetch_assoc();
        
        // 2. Actualizar inventario_bodega con las diferencias
        if ($inventario_actual) {
            // Calcular nuevos valores basados en las diferencias
            $nuevas_pacas_inventario = $inventario_actual['pacas_cantidad_disponible'] - $diferencia_pacas;
            $nuevos_kilos_inventario = $inventario_actual['pacas_kilos_disponible'] - $diferencia_kilos;
            $nuevo_total_kilos = $inventario_actual['total_kilos_disponible'] - $diferencia_kilos;
            
            // Actualizar inventario
            $sql_update_inventario = "UPDATE inventario_bodega SET 
                                     pacas_cantidad_disponible = ?,
                                     pacas_kilos_disponible = ?,
                                     total_kilos_disponible = ?,
                                     ultima_salida = NOW(),
                                     updated_at = NOW(),
                                     id_user = ?
                                     WHERE id_inventario = ?";
            
            $stmt_update_inv = $conn_mysql->prepare($sql_update_inventario);
            $stmt_update_inv->bind_param('dddii', 
                $nuevas_pacas_inventario,
                $nuevos_kilos_inventario,
                $nuevo_total_kilos,
                $idUser,
                $inventario_actual['id_inventario']
            );
            $stmt_update_inv->execute();
            
            // 3. Actualizar movimiento de inventario si existe
            if ($movimiento_original) {
                // Calcular nuevos valores para el movimiento
                $nueva_cantidad_movimiento = $movimiento_original['pacas_cantidad_movimiento'] + $diferencia_pacas;
                $nuevos_kilos_movimiento = $movimiento_original['pacas_kilos_movimiento'] + $diferencia_kilos;
                
                $sql_update_movimiento = "UPDATE movimiento_inventario SET 
                                         pacas_cantidad_movimiento = ?,
                                         pacas_kilos_movimiento = ?,
                                         pacas_cantidad_nuevo = ?,
                                         pacas_kilos_nuevo = ?,
                                         observaciones = CONCAT(observaciones, ' - Editado: ', NOW())
                                         WHERE id_movimiento = ?";
                
                $stmt_update_mov = $conn_mysql->prepare($sql_update_movimiento);
                $stmt_update_mov->bind_param('iiddi',
                    $nueva_cantidad_movimiento,
                    $nuevos_kilos_movimiento,
                    $nuevas_pacas_inventario,
                    $nuevos_kilos_inventario,
                    $movimiento_original['id_movimiento']
                );
                $stmt_update_mov->execute();
            } else {
                // Crear nuevo movimiento si no existe
                $sql_nuevo_movimiento = "INSERT INTO movimiento_inventario 
                                        (id_inventario, id_venta, tipo_movimiento,
                                         pacas_cantidad_movimiento, pacas_kilos_movimiento,
                                         pacas_cantidad_anterior, pacas_cantidad_nuevo,
                                         pacas_kilos_anterior, pacas_kilos_nuevo,
                                         observaciones, id_user, created_at)
                                        VALUES (?, ?, 'salida_edicion', 
                                                ?, ?, 
                                                ?, ?,
                                                ?, ?,
                                                ?, ?, NOW())";
                
                $observacion = 'Venta editada #' . $id_venta . ' - Dif: ' . $diferencia_pacas . ' pacas, ' . $diferencia_kilos . ' kg';
                
                $stmt_nuevo_mov = $conn_mysql->prepare($sql_nuevo_movimiento);
                $stmt_nuevo_mov->bind_param('iiidddiidss',
                    $inventario_actual['id_inventario'],
                    $id_venta,
                    $diferencia_pacas,
                    $diferencia_kilos,
                    $inventario_actual['pacas_cantidad_disponible'],
                    $nuevas_pacas_inventario,
                    $inventario_actual['pacas_kilos_disponible'],
                    $nuevos_kilos_inventario,
                    $observacion,
                    $idUser
                );
                $stmt_nuevo_mov->execute();
            }
        }
        
        // 4. Actualizar detalles de la venta
        $sql_update_detalle = "UPDATE venta_detalle SET 
                              id_pre_venta = ?,
                              pacas_cantidad = ?,
                              total_kilos = ?,
                              observaciones = ?,
                              updated_at = NOW(),
                              id_user = ?
                              WHERE id_venta = ? AND status = 1";
        
        $stmt_update = $conn_mysql->prepare($sql_update_detalle);
        $stmt_update->bind_param('iiddsi', 
            $nuevo_precio_id,
            $nuevas_pacas,
            $nuevos_kilos,
            $nuevas_observaciones,
            $idUser,
            $id_venta
        );
        $stmt_update->execute();
        
        // 5. Actualizar fecha de venta si cambió
        if ($nueva_fecha != $venta['fecha_venta_form']) {
            $sql_update_fecha = "UPDATE ventas SET 
                                fecha_venta = ?,
                                updated_at = NOW(),
                                id_user = ?
                                WHERE id_venta = ?";
            
            $stmt_fecha = $conn_mysql->prepare($sql_update_fecha);
            $stmt_fecha->bind_param('ssi', $nueva_fecha, $idUser, $id_venta);
            $stmt_fecha->execute();
        }
        
        $conn_mysql->commit();
        
        alert("Venta actualizada exitosamente", 1, "V_venta&id=" . $id_venta);
        logActivity('ACTUALIZAR', 'Editó la venta '. $id_venta);
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error al actualizar venta: " . $e->getMessage(), 2, "E_venta&id=" . $id_venta);
    }
}

// Obtener precios disponibles para edición
$sql_precios = "SELECT p.*, 
                       CASE 
                           WHEN p.destino = ? THEN 'Precio específico'
                           WHEN p.destino = 0 THEN 'Precio general'
                           ELSE 'Otro precio'
                       END as tipo_precio
                FROM precios p 
                WHERE p.id_prod = ? 
                AND p.tipo = 'v'
                AND p.status = '1'
                AND p.fecha_ini <= ? 
                AND p.fecha_fin >= ?
                ORDER BY p.destino DESC, p.fecha_ini DESC";
$stmt_precios = $conn_mysql->prepare($sql_precios);
$stmt_precios->bind_param('iiss', 
    $venta['id_direc_cliente'], 
    $venta['id_prod'], 
    $fecha_venta_actual, 
    $fecha_venta_actual
);
$stmt_precios->execute();
$precios_disponibles = $stmt_precios->get_result();
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Venta: <?= htmlspecialchars($venta['folio_compuesto']) ?></h5>
            <button id="btnCerrar" type="button" class="btn btn-sm rounded-3 btn-danger"
                onclick="(function(){ try{ window.open('','_self'); window.close(); }catch(e){ console.error(e); } })();">
                <i class="bi bi-x-circle"></i> Cerrar
            </button>
        </div>
        
        <div class="card-body">
            <form class="forms-sample" method="post" action="" id="formEditarVenta">
                
                <!-- Información General (solo lectura) -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Información General</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Folio</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($venta['folio_compuesto']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_venta" class="form-label">Fecha de Venta *</label>
                            <input type="date" name="fecha_venta" id="fecha_venta" class="form-control" 
                                   value="<?= $venta['fecha_venta_form'] ?>" max="<?= date('Y-m-d') ?>" 
                                   onchange="actualizarPreciosEdicion()" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Zona</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(($venta['nombre_zona'] ?? '')) ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Partes (solo lectura) -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Partes Involucradas</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($venta['cod_cliente'] . ' - ' . $venta['nombre_cliente']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Almacén</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($venta['cod_almacen'] . ' - ' . $venta['nombre_almacen']) ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Producto y Precios (editables) -->
                <div class="form-section shadow-sm mb-4">
                    <h5 class="section-header">Producto y Precios</h5>
                    
                    <!-- Producto (solo lectura) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($venta['cod_producto'] . ' - ' . $venta['nombre_producto']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio Actual</label>
                            <input type="text" class="form-control" 
                                   value="$<?= number_format($venta['precio_actual'], 2) ?>" readonly>
                        </div>
                    </div>
                    
                    <!-- Precio de Venta (editable) -->
                    <div class="row g-3">
                        <div class="col-md-6" id="PreciosEdicion">
                            <label for="id_precio_venta" class="form-label">Seleccionar Nuevo Precio *</label>
                            <select class="form-select" name="id_precio_venta" id="id_precio_venta" required>
                                <?php
                                if ($precios_disponibles->num_rows > 0) {
                                    while ($precio = $precios_disponibles->fetch_assoc()) {
                                        $selected = ($precio['id_precio'] == $venta['id_pre_venta']) ? 'selected' : '';
                                        $fecha_fin = ($precio['fecha_fin'] && $precio['fecha_fin'] != '0000-00-00 00:00:00') 
                                            ? date('d/m/Y', strtotime($precio['fecha_fin'])) 
                                            : 'Indefinido';
                                        ?>
                                        <option value="<?= $precio['id_precio'] ?>" <?= $selected ?>>
                                            $<?= number_format($precio['precio'], 2) ?> 
                                            (<?= $precio['tipo_precio'] ?> - Vigente hasta: <?= $fecha_fin ?>)
                                        </option>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <option value="<?= $venta['id_pre_venta'] ?>" selected>
                                        $<?= number_format($venta['precio_actual'], 2) ?> (Precio actual - Sin alternativas)
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Stock Disponible -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Inventario Actual y Cambios:</strong><br>
                                • Inventario actual: <strong><?= $pacas_disponibles_extra ?></strong> pacas / 
                                <strong><?= number_format($kilos_disponibles_extra, 2) ?> kg</strong><br>
                                • Venta original: <strong><?= $venta['pacas_cantidad'] ?></strong> pacas / 
                                <strong><?= number_format($venta['total_kilos'], 2) ?> kg</strong><br>
                                <small class="text-muted">Si aumenta la cantidad, se restará del inventario. Si disminuye, se sumará al inventario.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cantidades (editables) -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="cantidad_pacas" class="form-label">Cantidad de Pacas *</label>
                            <div class="input-group">
                                <input type="number" name="cantidad_pacas" id="cantidad_pacas" class="form-control" 
                                       min="1" step="1" value="<?= $venta['pacas_cantidad'] ?>" 
                                       onchange="validarCantidadesEdicion()" required>
                                <span class="input-group-text">pacas</span>
                            </div>
                            <small class="text-muted">Original: <?= $venta['pacas_cantidad'] ?> pacas</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="kilos_venta" class="form-label">Peso Total (kilos) *</label>
                            <div class="input-group">
                                <input type="number" name="kilos_venta" id="kilos_venta" class="form-control" 
                                       step="0.01" min="0.01" value="<?= number_format($venta['total_kilos'], 2) ?>" 
                                       onchange="validarCantidadesEdicion()" required>
                                <span class="input-group-text">kg</span>
                            </div>
                            <small class="text-muted">Original: <?= number_format($venta['total_kilos'], 2) ?> kg</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="observaciones_venta" class="form-label">Observaciones</label>
                            <input type="text" name="observaciones_venta" id="observaciones_venta" class="form-control" 
                                   value="<?= htmlspecialchars($venta['observaciones'] ?? '') ?>"
                                   placeholder="Observaciones de la venta...">
                        </div>
                    </div>
                    
                    <!-- Resumen de Cambios -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-warning" id="resumenCambios">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Los cambios se reflejarán en el inventario automáticamente
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="?p=V_venta&id=<?= $id_venta ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver a Vista
                    </a>
                    <div>
                        <button type="button" class="btn btn-warning" onclick="mostrarConfirmacion()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Restaurar Original
                        </button>
                        <button type="submit" name="actualizar_venta" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Actualizar Venta
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restaurar Valores Originales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de restaurar todos los valores a los originales?</p>
                <p><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="restaurarOriginal()">Restaurar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript para edición -->
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#id_precio_venta').select2({
        placeholder: "Selecciona un precio",
        allowClear: false,
        language: "es",
        width: '100%'
    });
    
    // Cerrar ventana
    $('#btnCerrar').click(function() {
        window.close();
    });
    
    // Calcular diferencias iniciales
    validarCantidadesEdicion();
});

// Función para actualizar precios cuando cambia la fecha
function actualizarPreciosEdicion() {
    var fechaSeleccionada = $('#fecha_venta').val();
    var idProducto = <?= $venta['id_prod'] ?>;
    var idBodegaCliente = <?= $venta['id_direc_cliente'] ?>;
    
    if (!fechaSeleccionada) return;
    
    $.ajax({
        url: 'get_venta.php',
        type: 'POST',
        data: {
            idProd: idProducto,
            fechaVenta: fechaSeleccionada,
            idCliente: <?= $venta['id_cliente'] ?>,
            idBodegaCliente: idBodegaCliente,
            accion: 'precio_venta'
        },
        beforeSend: function() {
            $('#PreciosEdicion').html('<div class="form-control">Buscando precios...</div>');
        },
        success: function(response) {
            $('#PreciosEdicion').html(response);
            $('#id_precio_venta').select2({
                placeholder: "Selecciona un precio",
                allowClear: false,
                language: "es",
                width: '100%'
            });
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $('#PreciosEdicion').html('<div class="alert alert-danger">Error al cargar precios</div>');
        }
    });
}

// Función para validar cantidades durante edición
function validarCantidadesEdicion() {
    var pacasOriginal = <?= $venta['pacas_cantidad'] ?>;
    var kilosOriginal = <?= $venta['total_kilos'] ?>;
    var pacasInventario = <?= $pacas_disponibles_extra ?>;
    var kilosInventario = <?= $kilos_disponibles_extra ?>;
    
    var pacasNuevas = parseInt($('#cantidad_pacas').val()) || 0;
    var kilosNuevos = parseFloat($('#kilos_venta').val()) || 0;
    
    // Calcular diferencias
    var diffPacas = pacasNuevas - pacasOriginal;
    var diffKilos = kilosNuevos - kilosOriginal;
    
    var errores = [];
    var mensajes = [];
    
    // Validar solo si se quiere SACAR MÁS (diferencia positiva)
    if (diffPacas > 0 && diffPacas > pacasInventario) {
        errores.push('No hay suficientes pacas disponibles. Disponibles: ' + pacasInventario);
        $('#cantidad_pacas').addClass('is-invalid');
    } else {
        $('#cantidad_pacas').removeClass('is-invalid');
    }
    
    if (diffKilos > 0 && diffKilos > kilosInventario) {
        errores.push('No hay suficientes kilos disponibles. Disponibles: ' + kilosInventario.toFixed(2) + ' kg');
        $('#kilos_venta').addClass('is-invalid');
    } else {
        $('#kilos_venta').removeClass('is-invalid');
    }
    
    // Determinar el efecto en el inventario
    var efectoInventario = '';
    if (diffPacas !== 0 || diffKilos !== 0) {
        efectoInventario = '<strong>Efecto en inventario:</strong><br>';
        
        if (diffPacas > 0) {
            efectoInventario += '• Se RESTARÁN ' + diffPacas + ' pacas del inventario<br>';
        } else if (diffPacas < 0) {
            efectoInventario += '• Se SUMARÁN ' + Math.abs(diffPacas) + ' pacas al inventario<br>';
        }
        
        if (diffKilos > 0) {
            efectoInventario += '• Se RESTARÁN ' + diffKilos.toFixed(2) + ' kg del inventario<br>';
        } else if (diffKilos < 0) {
            efectoInventario += '• Se SUMARÁN ' + Math.abs(diffKilos).toFixed(2) + ' kg al inventario<br>';
        }
        
        efectoInventario += '<small class="text-muted">Movimientos de inventario se actualizarán automáticamente</small>';
        
        $('#resumenCambios').removeClass('alert-warning').addClass('alert-info').html('<i class="bi bi-info-circle me-2"></i>' + efectoInventario);
    } else {
        $('#resumenCambios').removeClass('alert-info').addClass('alert-warning').html('<i class="bi bi-exclamation-triangle me-2"></i>No hay cambios en las cantidades');
    }
    
    // Mostrar errores si los hay
    if (errores.length > 0) {
        $('#resumenCambios').removeClass('alert-info alert-warning').addClass('alert-danger').html('<i class="bi bi-exclamation-triangle me-2"></i>' + errores.join('<br>'));
    }
    
    return errores.length === 0;
}

// Función para mostrar modal de confirmación
function mostrarConfirmacion() {
    $('#modalConfirmacion').modal('show');
}

// Función para restaurar valores originales
function restaurarOriginal() {
    $('#cantidad_pacas').val(<?= $venta['pacas_cantidad'] ?>);
    $('#kilos_venta').val(<?= number_format($venta['total_kilos'], 2) ?>);
    $('#observaciones_venta').val("<?= addslashes($venta['observaciones'] ?? '') ?>");
    
    // Seleccionar precio original
    $('#id_precio_venta').val("<?= $venta['id_pre_venta'] ?>").trigger('change');
    
    // Cerrar modal
    $('#modalConfirmacion').modal('hide');
    
    // Actualizar validación
    validarCantidadesEdicion();
    
    // Mostrar mensaje
    alert('Valores restaurados a los originales');
}

// Validar formulario antes de enviar
$('#formEditarVenta').on('submit', function(e) {
    if (!validarCantidadesEdicion()) {
        e.preventDefault();
        alert('Por favor corrija los errores antes de enviar');
        return false;
    }
    
    // Confirmar cambios
    if (!confirm('¿Está seguro de actualizar la venta? Esta acción modificará el inventario.')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>