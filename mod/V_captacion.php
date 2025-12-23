<?php
// Obtener el ID de la captación desde la URL
$id_captacion = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_captacion <= 0) {
    alert("ID de captación no válido", 0, "captacion");
    exit;
}
// Obtener datos principales de la captación
$sql_captacion = "SELECT 
c.*,
z.cod as cod_zona,
z.PLANTA as nombre_zona,
p.cod as cod_proveedor,
p.rs as nombre_proveedor,
a.cod as cod_almacen,
a.nombre as nombre_almacen,
t.placas as placas_fletero,
t.razon_so as nombre_fletero,
dbp.cod_al as cod_bodega_proveedor,
dbp.noma as nombre_bodega_proveedor,
dba.cod_al as cod_bodega_almacen,
dba.noma as nombre_bodega_almacen,
u.nombre as nombre_usuario
FROM captacion c
LEFT JOIN zonas z ON c.zona = z.id_zone
LEFT JOIN proveedores p ON c.id_prov = p.id_prov
LEFT JOIN almacenes a ON c.id_alma = a.id_alma
LEFT JOIN transportes t ON c.id_transp = t.id_transp
LEFT JOIN direcciones dbp ON c.id_direc_prov = dbp.id_direc
LEFT JOIN direcciones dba ON c.id_direc_alma = dba.id_direc
LEFT JOIN usuarios u ON c.id_user = u.id_user
WHERE c.id_captacion = ? AND c.status = 1";

$stmt_captacion = $conn_mysql->prepare($sql_captacion);
$stmt_captacion->bind_param('i', $id_captacion);
$stmt_captacion->execute();
$result_captacion = $stmt_captacion->get_result();

if (!$result_captacion || $result_captacion->num_rows == 0) {
    alert("Captación no encontrada", 0, "captacion");
    exit;
}

$captacion = $result_captacion->fetch_assoc();

// Obtener el folio completo desde la base de datos
$folio_completo = $captacion['folio'];

if (strlen($folio_completo) == 4 && is_numeric($folio_completo)) {
    $fecha_captacion = $captacion['fecha_captacion'];
    $anio_mes = date('ym', strtotime($fecha_captacion));
    $folio_numero = str_pad($folio_completo, 4, '0', STR_PAD_LEFT);
    $folio_completo = "C-" . $captacion['cod_zona'] . "-" . $anio_mes . $folio_numero;
}

// Obtener productos de la captación con precio por kilo
$sql_productos = "SELECT 
cd.*,
p.cod as cod_producto,
p.nom_pro as nombre_producto,
pc.precio as precio_compra_por_kilo,
cd.numero_ticket,
cd.numero_bascula,
cd.numero_factura
FROM captacion_detalle cd
LEFT JOIN productos p ON cd.id_prod = p.id_prod
LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
WHERE cd.id_captacion = ? AND cd.status = 1";

$stmt_productos = $conn_mysql->prepare($sql_productos);
$stmt_productos->bind_param('i', $id_captacion);
$stmt_productos->execute();
$productos = $stmt_productos->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener información del flete
$sql_flete = "SELECT 
cf.*,
cf.id_capt_flete as id_flete,
pf.precio as precio_flete,
pf.tipo as tipo_flete,
pf.conmin as toneladas_minimas,
cf.numero_factura_flete
FROM captacion_flete cf
LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
WHERE cf.id_captacion = ?";

$stmt_flete = $conn_mysql->prepare($sql_flete);
$stmt_flete->bind_param('i', $id_captacion);
$stmt_flete->execute();
$flete = $stmt_flete->get_result()->fetch_assoc();

// Calcular totales y costos
$total_kilos = 0;
$total_granel = 0;
$total_pacas_kilos = 0;
$total_pacas_cantidad = 0;
$costo_total_productos = 0;
$costo_total_flete = 0;

foreach ($productos as $producto) {
    $total_kilos += $producto['total_kilos'];
    $total_granel += $producto['granel_kilos'];
    $total_pacas_kilos += $producto['pacas_kilos'];
    $total_pacas_cantidad += $producto['pacas_cantidad'];
    
    $costo_producto = $producto['precio_compra_por_kilo'] * $producto['total_kilos'];
    $costo_total_productos += $costo_producto;
}

if ($flete) {
    if ($flete['tipo_flete'] == 'MFT') {
        $toneladas = $total_kilos / 1000;
        $costo_total_flete = $toneladas * $flete['precio_flete'];
    } else {
        $costo_total_flete = $flete['precio_flete'];
    }
}

$costo_total_captacion = $costo_total_productos + $costo_total_flete;

$tiene_granel = $total_granel > 0;
$tiene_pacas = $total_pacas_cantidad > 0;
$tiene_ambos = $tiene_granel && $tiene_pacas;

// ============================================
// PROCESAR GUARDADO DE NÚMEROS DE PRODUCTOS
// ============================================
// ============================================
// PROCESAR GUARDADO DE NÚMEROS DE PRODUCTOS
// ============================================
if (isset($_POST['guardar_numeros_productos'])) {
    try {
        $productos_actualizar = $_POST['productos'] ?? [];
        $id_captacion_post = $_POST['id_captacion'] ?? 0;
        
        if ($id_captacion_post != $id_captacion) {
            throw new Exception("ID de captación no coincide");
        }
        
        if (empty($productos_actualizar) || !is_array($productos_actualizar)) {
            throw new Exception("No hay datos de productos para actualizar");
        }
        
        // ============================================
        // VALIDAR DUPLICADOS ANTES DE GUARDAR
        // ============================================
        $duplicados_encontrados = [];
        
        foreach ($productos_actualizar as $index => $producto) {
            $id_detalle = isset($producto['id_detalle']) ? intval($producto['id_detalle']) : 0;
            
            // Obtener y limpiar valores
            $numero_ticket = isset($producto['numero_ticket']) ? trim($producto['numero_ticket']) : '';
            $numero_bascula = isset($producto['numero_bascula']) ? trim($producto['numero_bascula']) : '';
            $numero_factura = isset($producto['numero_factura']) ? trim($producto['numero_factura']) : '';
            
            if ($id_detalle <= 0) continue;
            
            // Validar ticket duplicado
            if (!empty($numero_ticket)) {
                $sql_ticket = "SELECT cd.id_detalle, p.cod, p.nom_pro 
                FROM captacion_detalle cd
                INNER JOIN captacion c ON cd.id_captacion = c.id_captacion
                INNER JOIN productos p ON cd.id_prod = p.id_prod
                WHERE cd.numero_ticket = ? 
                AND cd.id_detalle != ? 
                AND cd.status = 1 
                AND c.id_prov = ?";
                
                $stmt = $conn_mysql->prepare($sql_ticket);
                $stmt->bind_param('sii', $numero_ticket, $id_detalle, $captacion['id_prov']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $duplicado = $result->fetch_assoc();
                    $duplicados_encontrados[] = "Ticket '$numero_ticket' ya existe para el producto " . 
                    $duplicado['cod'] . " - " . $duplicado['nom_pro'];
                }
            }
            
            // Validar báscula duplicada
            if (!empty($numero_bascula)) {
                $sql_bascula = "SELECT cd.id_detalle, p.cod, p.nom_pro 
                FROM captacion_detalle cd
                INNER JOIN captacion c ON cd.id_captacion = c.id_captacion
                INNER JOIN productos p ON cd.id_prod = p.id_prod
                WHERE cd.numero_bascula = ? 
                AND cd.id_detalle != ? 
                AND cd.status = 1 
                AND c.id_prov = ?";
                
                $stmt = $conn_mysql->prepare($sql_bascula);
                $stmt->bind_param('sii', $numero_bascula, $id_detalle, $captacion['id_prov']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $duplicado = $result->fetch_assoc();
                    $duplicados_encontrados[] = "Báscula '$numero_bascula' ya existe para el producto " . 
                    $duplicado['cod'] . " - " . $duplicado['nom_pro'];
                }
            }
            
            // Validar factura duplicada
            if (!empty($numero_factura)) {
                $sql_factura = "SELECT cd.id_detalle, p.cod, p.nom_pro 
                FROM captacion_detalle cd
                INNER JOIN captacion c ON cd.id_captacion = c.id_captacion
                INNER JOIN productos p ON cd.id_prod = p.id_prod
                WHERE cd.numero_factura = ? 
                AND cd.id_detalle != ? 
                AND cd.status = 1 
                AND c.id_prov = ?";
                
                $stmt = $conn_mysql->prepare($sql_factura);
                $stmt->bind_param('sii', $numero_factura, $id_detalle, $captacion['id_prov']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $duplicado = $result->fetch_assoc();
                    $duplicados_encontrados[] = "Factura '$numero_factura' ya existe para el producto " . 
                    $duplicado['cod'] . " - " . $duplicado['nom_pro'];
                }
            }
        }
        
        // Si hay duplicados, mostrar error y no guardar
        if (!empty($duplicados_encontrados)) {
            $mensaje_duplicados = "Se encontraron duplicados: " . implode("<br>", $duplicados_encontrados);
            throw new Exception($mensaje_duplicados);
        }
        
        // ============================================
        // SI NO HAY DUPLICADOS, PROCEDER A GUARDAR
        // ============================================
        $conn_mysql->begin_transaction();
        $actualizados = 0;
        $errores = [];
        
        foreach ($productos_actualizar as $index => $producto) {
            $id_detalle = isset($producto['id_detalle']) ? intval($producto['id_detalle']) : 0;
            
            // Obtener y limpiar valores
            $numero_ticket = isset($producto['numero_ticket']) ? trim($producto['numero_ticket']) : '';
            $numero_bascula = isset($producto['numero_bascula']) ? trim($producto['numero_bascula']) : '';
            $numero_factura = isset($producto['numero_factura']) ? trim($producto['numero_factura']) : '';
            
            if ($id_detalle <= 0) {
                $errores[] = "ID de detalle inválido en índice $index";
                continue;
            }
            
            // Convertir cadenas vacías a NULL
            $ticket_sql = ($numero_ticket === '') ? null : $numero_ticket;
            $bascula_sql = ($numero_bascula === '') ? null : $numero_bascula;
            $factura_sql = ($numero_factura === '') ? null : $numero_factura;
            
            // Actualizar el registro
            $sql = "UPDATE captacion_detalle 
            SET numero_ticket = ?, 
            numero_bascula = ?, 
            numero_factura = ? 
            WHERE id_detalle = ? AND id_captacion = ? AND status = 1";
            
            $stmt = $conn_mysql->prepare($sql);
            if (!$stmt) {
                $errores[] = "Error al preparar consulta para ID $id_detalle: " . $conn_mysql->error;
                continue;
            }
            
            $stmt->bind_param('sssii', $ticket_sql, $bascula_sql, $factura_sql, $id_detalle, $id_captacion);
            
            if (!$stmt->execute()) {
                $errores[] = "Error al ejecutar para ID $id_detalle: " . $stmt->error;
            } else {
                $actualizados++;
            }
            
            $stmt->close();
        }
        
        if (empty($errores)) {
            $conn_mysql->commit();
            alert("Datos actualizados con éxito para $actualizados productos", 1, "V_captacion&id=$id_captacion_post");
        } else {
            $conn_mysql->rollback();
            $mensaje_error = "Errores encontrados: " . implode(', ', $errores);
            alert($mensaje_error, 0, "V_captacion&id=$id_captacion_post");
        }
        
    } catch (Exception $e) {
        if (isset($conn_mysql)) {
            $conn_mysql->rollback();
        }
        alert("Error: " . $e->getMessage(), 0, "V_captacion&id=$id_captacion");
    }
}

// ============================================
// PROCESAR GUARDADO DE FACTURA DE FLETE
// ============================================
if (isset($_POST['guardar_factura_flete'])) {
    try {
        $id_captacion_post = $_POST['id_captacion'] ?? 0;
        $numero_factura_flete = trim($_POST['numero_factura_flete'] ?? '');
        $validar_duplicado = isset($_POST['validar_duplicado']) ? (int)$_POST['validar_duplicado'] : 0;
        
        if ($id_captacion_post != $id_captacion) {
            throw new Exception("ID de captación no coincide");
        }
        
        if (empty($numero_factura_flete)) {
            throw new Exception("El número de factura es requerido");
        }
        
        // Validar duplicado si se solicita
        if ($validar_duplicado) {
            // Obtener ID del fletero
            $sql_fletero = "SELECT id_transp FROM captacion WHERE id_captacion = ?";
            $stmt = $conn_mysql->prepare($sql_fletero);
            $stmt->bind_param('i', $id_captacion);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $id_fletero = $row['id_transp'] ?? 0;
                
                // Verificar duplicado
                $sql_duplicado = "SELECT cf.id_captacion 
                FROM captacion_flete cf
                INNER JOIN captacion c ON cf.id_captacion = c.id_captacion
                WHERE cf.numero_factura_flete = ? 
                AND cf.id_captacion != ? 
                AND c.id_transp = ? 
                AND cf.numero_factura_flete IS NOT NULL 
                AND cf.numero_factura_flete != ''";
                
                $stmt = $conn_mysql->prepare($sql_duplicado);
                $stmt->bind_param('sii', $numero_factura_flete, $id_captacion, $id_fletero);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
    // Obtener información de la captación duplicada
                    $sql_info_duplicado = "SELECT c.folio, DATE_FORMAT(c.fecha_captacion, '%d/%m/%Y') as fecha
                    FROM captacion_flete cf
                    INNER JOIN captacion c ON cf.id_captacion = c.id_captacion
                    WHERE cf.numero_factura_flete = ? 
                    AND cf.id_captacion != ? 
                    AND c.id_transp = ?";

                    $stmt_info = $conn_mysql->prepare($sql_info_duplicado);
                    $stmt_info->bind_param('sii', $numero_factura_flete, $id_captacion, $id_fletero);
                    $stmt_info->execute();
                    $result_info = $stmt_info->get_result();

                    if ($duplicado_info = $result_info->fetch_assoc()) {
                        throw new Exception("El número de factura '$numero_factura_flete' ya existe para este fletero.<br>
                         Folio: {$duplicado_info['folio']}<br>
                         Fecha: {$duplicado_info['fecha']}");
                    } else {
                        throw new Exception("El número de factura '$numero_factura_flete' ya existe para este fletero.");
                    }
                }
            }
        }
        
        // Verificar si ya existe registro en captacion_flete
        $sql_check = "SELECT id_captacion FROM captacion_flete WHERE id_captacion = ?";
        $stmt_check = $conn_mysql->prepare($sql_check);
        $stmt_check->bind_param('i', $id_captacion);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows == 0) {
            // Insertar nuevo registro
            $sql_insert = "INSERT INTO captacion_flete (id_captacion, numero_factura_flete) VALUES (?, ?)";
            $stmt_insert = $conn_mysql->prepare($sql_insert);
            $stmt_insert->bind_param('is', $id_captacion, $numero_factura_flete);
            
            if ($stmt_insert->execute()) {
                alert("Factura de flete creada correctamente", 1, "V_captacion&id=$id_captacion_post");
            } else {
                throw new Exception("Error al crear factura: " . $stmt_insert->error);
            }
        } else {
            // Actualizar registro existente
            $sql_update = "UPDATE captacion_flete 
            SET numero_factura_flete = ? 
            WHERE id_captacion = ?";
            
            $stmt_update = $conn_mysql->prepare($sql_update);
            $stmt_update->bind_param('si', $numero_factura_flete, $id_captacion);
            
            if ($stmt_update->execute()) {
                alert("Factura de flete actualizada correctamente", 1, "V_captacion&id=$id_captacion_post");
            } else {
                throw new Exception("Error al actualizar: " . $stmt_update->error);
            }
        }
        
    } catch (Exception $e) {

        alert("Error al dar de alta la factura del flete", 0, "V_captacion&id=$id_captacion_post");
    }
}

// ============================================
// CONTINÚA CON EL CÓDIGO ORIGINAL
// ============================================

?>

<style>
    :root {
        --primary-color: #4e73df;
        --success-color: #1cc88a;
        --warning-color: #f6c23e;
        --info-color: #36b9cc;
        --dark-color: #5a5c69;
        --light-color: #f8f9fc;
    }

    
    .info-card {
        border-left: 4px solid var(--primary-color);
        transition: transform 0.2s;
        height: 100%;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
    }
    
    .finance-card {
        border-left: 4px solid var(--success-color);
        background: linear-gradient(135deg, rgba(28, 200, 138, 0.05) 0%, rgba(255, 255, 255, 0.1) 100%);
    }
    
    [data-bs-theme="dark"] .finance-card {
        background: linear-gradient(135deg, rgba(28, 200, 138, 0.1) 0%, rgba(33, 37, 41, 0.3) 100%);
        border-color: var(--success-color);
    }
    
    .stat-card {
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .stat-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .product-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
    }
    
    .table-custom th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border-bottom-width: 2px;
    }
    
    .table-custom tbody tr {
        transition: background-color 0.15s;
    }
    
    .table-custom tbody tr:hover {
        background-color: rgba(var(--primary-color-rgb), 0.05);
    }
    
    [data-bs-theme="dark"] .table-custom tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    .cost-display {
        font-family: 'SF Mono', 'Roboto Mono', monospace;
        font-weight: 600;
    }
    
    .section-title {
        position: relative;
        padding-bottom: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 3rem;
        height: 3px;
        background: var(--primary-color);
        border-radius: 2px;
    }
    
    .folio-badge {
        font-family: 'SF Mono', 'Roboto Mono', monospace;
        letter-spacing: 1px;
        font-weight: 600;
    }
    
    .status-badge {
        padding: 0.35rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .card-header-custom {
        background-color: rgba(var(--primary-color-rgb), 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    [data-bs-theme="dark"] .card-header-custom {
        background-color: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .quantity-badge {
        background-color: rgba(var(--primary-color-rgb), 0.1);
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 600;
    }
    
    [data-bs-theme="dark"] .quantity-badge {
        background-color: rgba(var(--primary-color-rgb), 0.2);
    }
    /* Estilos para los modales y validaciones */
    .numero-input {
        min-width: 120px;
    }

    .numero-input.duplicado {
        border-color: #dc3545;
        background-color: rgba(220, 53, 69, 0.05);
    }

    .numero-input.valido {
        border-color: #198754;
        background-color: rgba(25, 135, 84, 0.05);
    }

    .validacion-icon .bi-check-circle {
        color: #198754;
    }

    .validacion-icon .bi-x-circle {
        color: #dc3545;
    }

    .validacion-icon .bi-question-circle {
        color: #6c757d;
    }

/* Estilo para mostrar números en la tabla principal */
.numero-badge {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    margin: 0.1rem;
    display: inline-block;
    background-color: rgba(var(--primary-color-rgb), 0.1);
    border-radius: 0.25rem;
}
</style>

<div class="container py-3 px-lg-4">
    <!-- Header Principal -->
    <div class="card border-0 shadow-lg mb-4 overflow-hidden">
        <div class="encabezado-col text-white p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div>
                            <h1 class="h3 mb-2 fw-bold">Reporte de Captación</h1>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-white bg-opacity-25 text-white folio-badge">
                                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($folio_completo) ?>
                                </span>
                                <span class="badge bg-white bg-opacity-25 text-white">
                                    <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($captacion['fecha_captacion'])) ?>
                                </span>
                                <span class="badge bg-white bg-opacity-25 text-white">
                                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($captacion['nombre_usuario'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-end gap-2">
                        <!-- Botón para actualizar números de productos -->
                        <?php if (!empty($productos)): ?>
                            <button class="btn btn-sm rounded-3 btn-primary align-items-center" data-bs-toggle="modal" data-bs-target="#modalNumerosProductos">
                                <i class="bi bi-card-checklist"></i> Actualizar Números
                            </button>
                        <?php endif; ?>

                        <!-- Botón para actualizar factura de flete -->
                        <?php if ($flete): ?>
                            <button class="btn btn-sm rounded-3 btn-warning align-items-center" data-bs-toggle="modal" data-bs-target="#modalFacturaFlete">
                                <i class="bi bi-receipt"></i> Factura Flete
                            </button>
                        <?php endif; ?>

                        <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger align-items-center">
                            <i class="bi bi-x-circle"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary border-0 shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Productos
                            </div>
                            <div class="h5 mb-0 fw-bold"><?= count($productos) ?></div>
                            <div class="text-muted small">Productos registrados</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success border-0 shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Total Kilos
                            </div>
                            <div class="h5 mb-0 fw-bold"><?= number_format($total_kilos, 2) ?></div>
                            <div class="text-muted small">Kilogramos totales</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-speedometer2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning border-0 shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-indigo text-uppercase mb-1">
                                Costo Productos
                            </div>
                            <div class="h5 mb-0 fw-bold">$<?= number_format($costo_total_productos, 2) ?></div>
                            <div class="text-muted small">Compra de materiales</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-indigo bg-opacity-10 text-indigo">
                                <i class="bi bi-cart-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Información Principal -->
    <?php
    $provName = htmlspecialchars($captacion['nombre_proveedor'] ?? 'N/A');
    $provCode = htmlspecialchars($captacion['cod_proveedor'] ?? '');
    $provBodega = htmlspecialchars($captacion['nombre_bodega_proveedor'] ?? '-');

    $almacenName = htmlspecialchars($captacion['nombre_almacen'] ?? 'N/A');
    $almacenCode = htmlspecialchars($captacion['cod_almacen'] ?? '');
    $almacenBodega = htmlspecialchars($captacion['nombre_bodega_almacen'] ?? '-');

    $zonaCode = htmlspecialchars($captacion['cod_zona'] ?? '-');
    $zonaName = htmlspecialchars($captacion['nombre_zona'] ?? '-');
    $fechaCapt = !empty($captacion['fecha_captacion']) ? date('d/m/Y', strtotime($captacion['fecha_captacion'])) : '-';

    $fleteTipo = $flete['tipo_flete'] ?? null;
    $fletePrecio = isset($flete['precio_flete']) ? number_format($flete['precio_flete'], 2) : '0.00';
    $fleteLabel = $fleteTipo === 'MFT' ? 'Por Tonelada' : 'Por Viaje';
    $fleteNumero = htmlspecialchars($flete['numero_factura_flete'] ?? '');
    $fletePlacas = htmlspecialchars($captacion['placas_fletero'] ?? '');
    $fleteNombre = htmlspecialchars($captacion['nombre_fletero'] ?? '-');

    $costoProdFmt = number_format($costo_total_productos, 2);
    $costoFleteFmt = number_format($costo_total_flete, 2);
    $totalKilosFmt = number_format($total_kilos, 2);
    $costoCaptacionFmt = number_format($costo_total_captacion, 2);
    $promedioKilo = $total_kilos > 0 ? number_format($costo_total_captacion / $total_kilos, 4) : '0.0000';
    ?>
    <div class="row mb-4">
        <!-- Información General -->
        <div class="col-lg-8">
            <div class="row gy-4">
                <!-- Proveedor -->
                <div class="col-md-6">
                    <div class="card info-card border-0 shadow h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-truck text-primary me-2" aria-hidden="true"></i> Proveedor
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3" aria-hidden="true">
                                    <i class="bi bi-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= $provName ?></div>
                                    <small class="text-muted">Código: <?= $provCode ?></small>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <small class="text-muted d-block mb-1">Bodega Proveedor</small>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt text-muted me-2" aria-hidden="true"></i>
                                    <span><?= $provBodega ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Almacén -->
                <div class="col-md-6">
                    <div class="card info-card border-0 shadow h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-building text-success me-2" aria-hidden="true"></i> Almacén Destino
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 p-2 rounded-circle me-3" aria-hidden="true">
                                    <i class="bi bi-house-door text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= $almacenName ?></div>
                                    <small class="text-muted">Código: <?= $almacenCode ?></small>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <small class="text-muted d-block mb-1">Bodega Almacén</small>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt-fill text-muted me-2" aria-hidden="true"></i>
                                    <span><?= $almacenBodega ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zona y Fecha -->
                <div class="col-md-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block mb-1">Zona</small>
                                    <div class="d-flex align-items-center">
                                        <div class="quantity-badge me-2"><?= $zonaCode ?></div>
                                        <span class="fw-semibold"><?= $zonaName ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block mb-1">Fecha Captación</small>
                                    <div class="fw-semibold">
                                        <i class="bi bi-calendar3 text-primary me-1" aria-hidden="true"></i>
                                        <?= $fechaCapt ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flete (si aplica) -->
                <?php if ($flete): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header card-header-custom">
                                <h6 class="mb-0 d-flex align-items-center">
                                    <i class="bi bi-truck-flatbed text-warning me-2" aria-hidden="true"></i> Servicio de Flete
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Fletero</small>
                                    <div class="fw-semibold">
                                        <i class="bi bi-truck text-warning me-1" aria-hidden="true"></i>
                                        <?= $fletePlacas ?> <?= $fletePlacas && $fleteNombre ? '-' : '' ?> <?= $fleteNombre ?>
                                    </div>
                                </div>

                                <?php if (!empty($fleteNumero)): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Facturación Flete</small>
                                        <div class="fw-semibold">
                                            <i class="bi bi-receipt text-warning me-1" aria-hidden="true"></i>
                                            <?= $fleteNumero ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block mb-1">Tipo de Flete</small>
                                        <span class="badge bg-warning bg-opacity-25 text-dark"><?= $fleteLabel ?></span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block mb-1">Precio</small>
                                        <span class="fw-bold text-success">$<?= $fletePrecio ?><?= $fleteTipo === 'MFT' ? ' / ton' : '' ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen Financiero -->
        <div class="col-lg-4">
            <div class="card finance-card border-0 shadow h-100">
                <div class="card-header card-header-custom">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-calculator text-success me-2" aria-hidden="true"></i> Resumen Financiero
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Costo de Productos</span>
                            <span class="cost-display text-primary">$<?= $costoProdFmt ?></span>
                        </div>
                        <small class="text-muted"><?= $totalKilosFmt ?> kg comprados</small>
                    </div>

                    <?php if ($flete): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">Costo de Flete</span>
                                <span class="cost-display text-indigo">$<?= $costoFleteFmt ?></span>
                            </div>
                            <small class="text-muted">
                                <?= $fleteTipo === 'MFT' ? number_format($total_kilos / 1000, 2) . ' ton transportadas' : 'Viaje completo' ?>
                            </small>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Total Captación</h5>
                            <h4 class="fw-bold text-success mb-0">$<?= $costoCaptacionFmt ?></h4>
                        </div>
                        <div class="text-center p-3 rounded" style="background: rgba(28,200,138,0.06);">
                            <small class="text-muted d-block mb-1">Costo promedio por kilo</small>
                            <div class="h5 fw-bold text-success">
                                $<?= $promedioKilo ?><small class="text-muted fs-6"> /kg</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- /.col-lg-4 -->
    </div>

    <!-- Detalle de Productos -->
    <div class="card border-0 shadow mb-4">
        <div class="card-header card-header-custom d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <i class="bi bi-clipboard-data text-primary me-2"></i>
                Detalle de Productos
            </h5>
            <div class="d-flex gap-2">
                <span class="badge bg-primary"><?= count($productos) ?> productos</span>
                <span class="badge bg-success"><?= number_format($total_kilos, 2) ?> kg</span>
                <span class="badge bg-warning">$<?= number_format($costo_total_productos, 2) ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($productos)): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr class="table-light">
                                <th width="5%">#</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <?php if ($tiene_granel || $tiene_ambos): ?>
                                    <th class="text-end">Kilos Granel</th>
                                <?php endif; ?>
                                
                                <?php if ($tiene_pacas || $tiene_ambos): ?>
                                    <th class="text-end">Cant. Pacas</th>
                                    <th class="text-end">Kilos Pacas</th>
                                    <th class="text-end">Promedio</th>
                                <?php endif; ?>
                                
                                <th class="text-end">Total Kilos</th>
                                <th class="text-end">Precio/kg</th>
                                <th class="text-end">Subtotal</th>
                                <th>N° Ticket</th>
                                <th>N° Báscula</th>
                                <th>N° Factura</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = 1; ?>
                            <?php foreach ($productos as $producto): ?>
                                <?php
                                $tipo_almacen = $producto['tipo_almacen'];
                                $subtotal_producto = $producto['precio_compra_por_kilo'] * $producto['total_kilos'];
                                
                                $type_colors = [
                                    'granel' => ['bg' => 'bg-warning bg-opacity-25', 'text' => 'text-warning'],
                                    'pacas' => ['bg' => 'bg-indigo bg-opacity-25', 'text' => 'text-indigo']
                                ];
                                $color = $type_colors[$tipo_almacen] ?? ['bg' => 'bg-secondary bg-opacity-25', 'text' => 'text-secondary'];
                                ?>
                                <tr>
                                    <td class="fw-semibold text-muted"><?= $contador++ ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($producto['cod_producto']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($producto['nombre_producto']) ?></small>
                                    </td>
                                    <td>
                                        <span class="product-type-badge <?= $color['bg'] ?> <?= $color['text'] ?>">
                                            <?= ucfirst($tipo_almacen) ?>
                                        </span>
                                    </td>
                                    
                                    <?php if ($tiene_granel || $tiene_ambos): ?>
                                        <td class="text-end">
                                            <?php if ($producto['granel_kilos'] > 0): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-dark">
                                                    <?= number_format($producto['granel_kilos'], 2) ?> kg
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <?php if ($tiene_pacas || $tiene_ambos): ?>
                                        <td class="text-end">
                                            <?php if ($producto['pacas_cantidad'] > 0): ?>
                                                <span class="badge bg-indigo bg-opacity-50 text-dark">
                                                    <?= number_format($producto['pacas_cantidad']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($producto['pacas_kilos'] > 0): ?>
                                                <?= number_format($producto['pacas_kilos'], 2) ?> kg
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($producto['pacas_peso_promedio'] > 0): ?>
                                                <?= number_format($producto['pacas_peso_promedio'], 2) ?> kg
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td class="text-end fw-bold">
                                        <span><?= number_format($producto['total_kilos'], 2) ?> kg</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="cost-display text-primary">$<?= number_format($producto['precio_compra_por_kilo'], 4) ?></div>
                                        <div class="text-muted small">/kg</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="cost-display text-success">$<?= number_format($subtotal_producto, 2) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['numero_ticket'])): ?>
                                            <span class="numero-badge" data-bs-toggle="tooltip" title="Ticket">
                                                <i class="bi bi-ticket-detailed me-1"></i><?= htmlspecialchars($producto['numero_ticket']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['numero_bascula'])): ?>
                                            <span class="numero-badge" data-bs-toggle="tooltip" title="Báscula">
                                                <i class="bi bi-speedometer me-1"></i><?= htmlspecialchars($producto['numero_bascula']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['numero_factura'])): ?>
                                            <span class="numero-badge" data-bs-toggle="tooltip" title="Factura">
                                                <i class="bi bi-receipt me-1"></i><?= htmlspecialchars($producto['numero_factura']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['observaciones'])): ?>
                                            <small class="d-block text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($producto['observaciones']) ?>">
                                                <i class="bi bi-chat-left-text me-1"></i>
                                                <?= strlen($producto['observaciones']) > 30 ? substr($producto['observaciones'], 0, 30) . '...' : $producto['observaciones'] ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Fila de Totales -->
                            <tr class="table-active fw-bold">
                                <td colspan="2" class="text-end">TOTALES:</td>
                                <td></td>
                                
                                <?php if ($tiene_granel || $tiene_ambos): ?>
                                    <td class="text-end">
                                        <?php if ($total_granel > 0): ?>
                                            <span class="text-warning"><?= number_format($total_granel, 2) ?> kg</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                
                                <?php if ($tiene_pacas || $tiene_ambos): ?>
                                    <td class="text-end">
                                        <?php if ($total_pacas_cantidad > 0): ?>
                                            <span class="text-info"><?= number_format($total_pacas_cantidad) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($total_pacas_kilos > 0): ?>
                                            <span class="text-info"><?= number_format($total_pacas_kilos, 2) ?> kg</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">-</td>
                                <?php endif; ?>
                                
                                <td class="text-end">
                                    <span class="text-success"><?= number_format($total_kilos, 2) ?> kg</span>
                                </td>
                                <td class="text-end">-</td>
                                <td class="text-end">
                                    <span class="text-success">$<?= number_format($costo_total_productos, 2) ?></span>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-inboxes fs-1 text-muted opacity-50"></i>
                    </div>
                    <h5 class="text-muted mb-2">No hay productos registrados</h5>
                    <p class="text-muted small">Esta captación no contiene productos</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pie de Página -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <small class="text-muted">
                        <i class="bi bi-clock-history me-1"></i>
                        Reporte generado el <?= date('d/m/Y H:i:s') ?> | 
                        Captación ID: <?= $id_captacion ?> | 
                        Folio: <?= htmlspecialchars($folio_completo) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal para Números de Productos -->
<!-- Modal para Números de Productos -->
<div class="modal fade" id="modalNumerosProductos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-card-checklist me-2"></i>
                    Actualizar Números - Productos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Los números no se pueden repetir con el mismo proveedor para productos activos (status = 1)
                    </div>
                    
                    <input type="hidden" name="id_captacion" value="<?= $id_captacion ?>">
                    <input type="hidden" name="guardar_numeros_productos" value="1">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>N° Ticket</th>
                                    <th>N° Báscula</th>
                                    <th>N° Factura</th>
                                    <th>Validación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $index => $producto): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($producto['cod_producto']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($producto['nombre_producto']) ?></small>
                                            <input type="hidden" name="productos[<?= $index ?>][id_detalle]" value="<?= $producto['id_detalle'] ?>">
                                        </td>
                                        <td>
                                            <input type="text" 
                                            class="form-control form-control-sm numero-input" 
                                            name="productos[<?= $index ?>][numero_ticket]" 
                                            value="<?= htmlspecialchars($producto['numero_ticket'] ?? '') ?>"
                                            data-id-detalle="<?= $producto['id_detalle'] ?>"
                                            data-tipo="ticket"
                                            onblur="validarNumero(this)">
                                        </td>
                                        <td>
                                            <input type="text" 
                                            class="form-control form-control-sm numero-input" 
                                            name="productos[<?= $index ?>][numero_bascula]" 
                                            value="<?= htmlspecialchars($producto['numero_bascula'] ?? '') ?>"
                                            data-id-detalle="<?= $producto['id_detalle'] ?>"
                                            data-tipo="bascula"
                                            onblur="validarNumero(this)">
                                        </td>
                                        <td>
                                            <input type="text" 
                                            class="form-control form-control-sm numero-input" 
                                            name="productos[<?= $index ?>][numero_factura]" 
                                            value="<?= htmlspecialchars($producto['numero_factura'] ?? '') ?>"
                                            data-id-detalle="<?= $producto['id_detalle'] ?>"
                                            data-tipo="factura"
                                            onblur="validarNumero(this)">
                                        </td>
                                        <td class="text-center">
                                            <div class="validacion-icon" id="validacion_<?= $producto['id_detalle'] ?>">
                                                <i class="bi bi-question-circle text-muted" title="Sin validar"></i>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal para Factura de Flete -->
<?php if ($flete): ?>
    <div class="modal fade" id="modalFacturaFlete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>
                        Factura de Flete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            El número de factura no se puede repetir con el mismo fletero
                        </div>

                        <input type="hidden" name="id_captacion" value="<?= $id_captacion ?>">
                        <input type="hidden" name="guardar_factura_flete" value="1">

                        <div class="mb-3">
                            <label class="form-label">Fletero</label>
                            <div class="form-control">
                                <strong><?= htmlspecialchars($captacion['nombre_fletero']) ?></strong>
                                <br>
                                <small class="text-muted">Placas: <?= htmlspecialchars($captacion['placas_fletero']) ?></small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Número de Factura de Flete *</label>
                            <input type="text" 
                            class="form-control" 
                            name="numero_factura_flete" 
                            id="numero_factura_flete"
                            value="<?= htmlspecialchars($flete['numero_factura_flete'] ?? '') ?>"
                            required>
                            <div class="form-text">Número único para este fletero</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="hidden" name="validar_duplicado" id="validar_duplicado" value="1" checked>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save me-1"></i> Guardar Factura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
<script>
// Función para validar números en tiempo real
    function validarNumero(input) {
        var valor = input.value.trim();
        var idDetalle = input.getAttribute('data-id-detalle');
        var tipo = input.getAttribute('data-tipo');
        var idCaptacion = <?= $id_captacion ?>;
        var idProveedor = <?= $captacion['id_prov'] ?? 0 ?>;

        if (valor === '') {
            input.classList.remove('duplicado', 'valido');
            document.getElementById('validacion_' + idDetalle).innerHTML = '<i class="bi bi-question-circle text-muted" title="Sin validar"></i>';
            return;
        }

    // Mostrar loading
        document.getElementById('validacion_' + idDetalle).innerHTML = '<i class="bi bi-hourglass text-warning" title="Validando..."></i>';

    // Crear objeto FormData
        var formData = new FormData();
        formData.append('id_captacion', idCaptacion);
        formData.append('id_detalle', idDetalle);
        formData.append('tipo', tipo);
        formData.append('numero', valor);
        formData.append('id_proveedor', idProveedor);

    // Enviar validación vía AJAX
        fetch('AJAX/validar_numero.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                input.classList.add('duplicado');
                input.classList.remove('valido');
                document.getElementById('validacion_' + idDetalle).innerHTML = '<i class="bi bi-x-circle text-danger" title="Número duplicado"></i>';
            } else {
                input.classList.add('valido');
                input.classList.remove('duplicado');
                document.getElementById('validacion_' + idDetalle).innerHTML = '<i class="bi bi-check-circle text-success" title="Válido"></i>';
            }
        })
        .catch(error => {
            console.error('Error en validación:', error);
            input.classList.remove('duplicado', 'valido');
            document.getElementById('validacion_' + idDetalle).innerHTML = '<i class="bi bi-exclamation-triangle text-warning" title="Error en validación"></i>';
        });
    }
// Validar formulario antes de enviar
    document.addEventListener('DOMContentLoaded', function() {
        var formNumerosProductos = document.querySelector('form[action=""]');

        if (formNumerosProductos && formNumerosProductos.querySelector('input[name="guardar_numeros_productos"]')) {
            formNumerosProductos.addEventListener('submit', function(e) {
                var tieneDuplicados = false;
                var mensajesError = [];

            // Verificar si hay campos con clase 'duplicado'
                var inputsDuplicados = formNumerosProductos.querySelectorAll('.numero-input.duplicado');

                if (inputsDuplicados.length > 0) {
                    tieneDuplicados = true;
                    mensajesError.push("Hay números duplicados marcados en rojo.");
                }

            // Verificar también que no haya campos vacíos que sean requeridos (si aplica)
            // Puedes agregar más validaciones aquí

                if (tieneDuplicados) {
                    e.preventDefault();

                // Crear mensaje de error
                    var mensaje = "No se puede guardar porque hay números duplicados:<br><br>";

                    inputsDuplicados.forEach(function(input) {
                        var tipo = input.getAttribute('data-tipo');
                        var valor = input.value;
                        var nombreTipo = '';

                        switch(tipo) {
                        case 'ticket': nombreTipo = 'Ticket'; break;
                        case 'bascula': nombreTipo = 'Báscula'; break;
                        case 'factura': nombreTipo = 'Factura'; break;
                        }

                        mensaje += "• " + nombreTipo + ": " + valor + "<br>";
                    });

                // Mostrar alerta personalizada
                    alert('error', mensaje);

                // También puedes usar SweetAlert si lo tienes
                // Swal.fire({
                //     icon: 'error',
                //     title: 'Números duplicados',
                //     html: mensaje,
                //     confirmButtonText: 'Entendido'
                // });

                    return false;
                }

            // Si todo está bien, mostrar loading
                var btnSubmit = formNumerosProductos.querySelector('button[type="submit"]');
                if (btnSubmit) {
                    var originalText = btnSubmit.innerHTML;
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="bi bi-hourglass me-1"></i> Guardando...';

                // Restaurar botón si hay error de validación del servidor
                    setTimeout(function() {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalText;
                    }, 5000);
                }

                return true;
            });
        }

    // Validar formulario de flete
        var formFacturaFlete = document.querySelector('form[action=""] input[name="guardar_factura_flete"]');
        if (formFacturaFlete) {
            formFacturaFlete = formFacturaFlete.closest('form');
            formFacturaFlete.addEventListener('submit', function(e) {
                var btnSubmit = this.querySelector('button[type="submit"]');
                if (btnSubmit) {
                    var originalText = btnSubmit.innerHTML;
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="bi bi-hourglass me-1"></i> Guardando...';

                // Restaurar botón si hay error
                    setTimeout(function() {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalText;
                    }, 5000);
                }

                return true;
            });
        }
    });
// Función para mostrar alertas
function alert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
    
    var alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            <i class="bi ${icon} me-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHTML);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

$(document).ready(function() {
        // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

        // Cerrar ventana
    $('#btnCerrar').click(function() {
        window.close();
    });


        // Atajos de teclado
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.close();
        }
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });

        // Resaltar hover en tablas
    $('.table-custom tbody tr').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
        );
});
</script>