<?php
// V_venta.php - Módulo para ver detalles de una venta (Rediseño ERP compacto)

// Obtener ID de la venta
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_venta <= 0) {
    alert("ID de venta no válido", 0, "ventas_info");
    exit;
}

// Procesar actualización de datos del fletero
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Procesar actualización de factura de venta
    if (isset($_POST['actualizar_factura_venta'])) {
        $factura_venta = trim($_POST['factura_venta'] ?? '');
        $fecha_factura = $_POST['fecha_factura'] ?? null;
        
        // Validar que la factura de venta no se repita en otras ventas activas
        if (!empty($factura_venta)) {
            // Verificar si la factura ya existe en otra venta activa
            $sql_check_factura_venta = "SELECT id_venta, folio
                                       FROM ventas 
                                       WHERE factura_venta = ? 
                                       AND status = 1 
                                       AND id_venta != ?";
            
            $stmt_check_factura = $conn_mysql->prepare($sql_check_factura_venta);
            if ($stmt_check_factura) {
                $stmt_check_factura->bind_param('si', $factura_venta, $id_venta);
                $stmt_check_factura->execute();
                $result_check_factura = $stmt_check_factura->get_result();
                
                if ($result_check_factura->num_rows > 0) {
                    $factura_duplicada_venta = $result_check_factura->fetch_assoc();
                    $error_factura_venta = "La factura de venta '$factura_venta' ya está registrada en la venta activa con folio: " . 
                                          htmlspecialchars($factura_duplicada_venta['folio']) . 
                                          ". Por favor, utiliza un número de factura diferente.";
                }
            }
        }
        
        // Si no hay error de factura duplicada, proceder con la actualización
        if (!isset($error_factura_venta)) {
            // Actualizar datos en ventas
            $sql_update_factura = "UPDATE ventas 
                                 SET factura_venta = ?, 
                                     fecha_factura = ?,
                                     factura_actualizada = NOW()
                                 WHERE id_venta = ?";
            
            $stmt_update_factura = $conn_mysql->prepare($sql_update_factura);
            if ($stmt_update_factura) {
                $fecha_factura_sql = !empty($fecha_factura) ? $fecha_factura : null;
                
                $stmt_update_factura->bind_param('ssi', 
                    $factura_venta, 
                    $fecha_factura_sql,
                    $id_venta
                );
                
                if ($stmt_update_factura->execute()) {
                    // Refrescar la página para mostrar los cambios
                    alert("Factura de venta actualizada con éxito", 1, "V_venta&id=$id_venta");
                    exit;
                } else {
                    alert("Error al actualizar la factura de venta", 0, "V_venta&id=$id_venta");
                }
            }
        }
    }
    
    // Procesar actualización de datos del fletero (mantener la funcionalidad existente)
    if (isset($_POST['actualizar_flete'])) {
        $tipo_camion = $_POST['tipo_camion'] ?? null;
        $nombre_chofer = $_POST['nombre_chofer'] ?? null;
        $placas_unidad = $_POST['placas_unidad'] ?? null;
        $factura_transportista = trim($_POST['factura_transportista'] ?? '');
        
        // Validar que la factura no se repita en otras ventas activas
        if (!empty($factura_transportista)) {
            // Verificar si la factura ya existe en otra venta activa
            $sql_check_factura = "SELECT vf.id_venta, v.folio
                                 FROM venta_flete vf
                                 INNER JOIN ventas v ON vf.id_venta = v.id_venta
                                 WHERE vf.factura_transportista = ? 
                                 AND v.status = 1 
                                 AND vf.id_venta != ?";
            
            $stmt_check = $conn_mysql->prepare($sql_check_factura);
            if ($stmt_check) {
                $stmt_check->bind_param('si', $factura_transportista, $id_venta);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    $factura_duplicada = $result_check->fetch_assoc();
                    $error_flete = "La factura '$factura_transportista' ya está registrada en la venta activa con folio: " . 
                                  htmlspecialchars($factura_duplicada['folio']) . 
                                  ". Por favor, utiliza un número de factura diferente.";
                }
            }
        }
        
        // Si no hay error de factura duplicada, proceder con la actualización
        if (!isset($error_flete)) {
            // Actualizar datos en venta_flete
            $sql_update_flete = "UPDATE venta_flete 
                                 SET tipo_camion = ?, 
                                     nombre_chofer = ?, 
                                     placas_unidad = ?, 
                                     factura_transportista = ?,
                                     fecha_actualizacion = NOW()
                                 WHERE id_venta = ?";
            
            $stmt_update = $conn_mysql->prepare($sql_update_flete);
            if ($stmt_update) {
                $stmt_update->bind_param('ssssi', 
                    $tipo_camion, 
                    $nombre_chofer, 
                    $placas_unidad, 
                    $factura_transportista,
                    $id_venta
                );
                
                if ($stmt_update->execute()) {
                    // Refrescar la página para mostrar los cambios
                    alert("Datos del fletero actualizados con éxito", 1, "V_venta&id=$id_venta");
                    exit;
                } else {
                    alert("Error al actualizar los datos del fletero", 0, "V_venta&id=$id_venta");
                }
            }
        }
    }
}

// Obtener información de la venta (incluyendo los nuevos campos)
$sql_venta = "SELECT v.*, 
                     CONCAT('V-', z.cod, '-', 
                           DATE_FORMAT(v.fecha_venta, '%y%m'), 
                           LPAD(v.folio, 4, '0')) as folio_compuesto,
                     v.folio as folio_simple,
                     c.cod as cod_cliente, c.nombre as nombre_cliente,
                     a.cod as cod_almacen, a.nombre as nombre_almacen,
                     z.PLANTA as nombre_zona, z.cod as cod_zona,
                     t.placas as placas_fletero, t.razon_so as nombre_fletero,
                     d_alm.cod_al as cod_bodega_almacen, d_alm.noma as nombre_bodega_almacen,
                     d_cli.cod_al as cod_bodega_cliente, d_cli.noma as nombre_bodega_cliente,
                     u.nombre as nombre_usuario,
                     DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_formateada,
                     DATE_FORMAT(v.created_at, '%d/%m/%Y %H:%i') as fecha_creacion_formateada,
                     DATE_FORMAT(v.fecha_factura, '%d/%m/%Y') as fecha_factura_formateada,
                     DATE_FORMAT(v.factura_actualizada, '%d/%m/%Y %H:%i') as factura_actualizada_formateada,
                     DATE_FORMAT(v.fecha_validacion_factura, '%d/%m/%Y %H:%i') as fecha_validacion_formateada,
                     v.factura_valida,
                     v.url_factura_pdf,
                     CASE 
                         WHEN v.factura_venta IS NULL THEN 'Pendiente'
                         WHEN v.factura_venta = '' THEN 'Pendiente'
                         ELSE v.factura_venta
                     END as factura_venta_display
              FROM ventas v
              LEFT JOIN clientes c ON v.id_cliente = c.id_cli
              LEFT JOIN almacenes a ON v.id_alma = a.id_alma
              LEFT JOIN zonas z ON v.zona = z.id_zone
              LEFT JOIN transportes t ON v.id_transp = t.id_transp
              LEFT JOIN direcciones d_alm ON v.id_direc_alma = d_alm.id_direc
              LEFT JOIN direcciones d_cli ON v.id_direc_cliente = d_cli.id_direc
              LEFT JOIN usuarios u ON v.id_user = u.id_user
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

// Verificar si la factura existe y necesita ser validada automáticamente
if (!empty($venta['factura_venta'])) {
    // Condiciones para validación automática (igual que en recolección):
    // 1. Tiene factura_venta
    // 2. factura_valida es 0 (no validada) O está vacía/null
    // 3. No se acaba de intentar validar manualmente (para evitar loops)
    
    $debe_validar_auto = false;
    
    if ($venta['factura_valida'] == 0 || $venta['factura_valida'] === null) {
        // Si nunca se ha validado, validar automáticamente
        $debe_validar_auto = true;
    } elseif (!empty($venta['fecha_validacion_factura'])) {
        // Si ya se validó pero hace más de 1 día, revalidar
        $fecha_validacion = strtotime($venta['fecha_validacion_factura']);
        $hace_un_dia = strtotime('-1 day');
        
        if ($fecha_validacion < $hace_un_dia) {
            $debe_validar_auto = true;
        }
    }
    
    // No validar si el usuario acaba de intentar validar manualmente
    if (isset($_GET['validar_factura'])) {
        $debe_validar_auto = false;
    }
    
    // Ejecutar validación automática si corresponde
    if ($debe_validar_auto) {
        // Solo mostrar mensaje en consola/registro, no al usuario
        error_log("Validación automática de factura para venta ID: $id_venta");
        
        // Ejecutar validación (en segundo plano)
        $resultado_auto = validarFacturaVenta($id_venta, $conn_mysql);
        
        // Actualizar los datos de la venta con el resultado
        if ($resultado_auto === true) {
            $venta['factura_valida'] = 1;
            $venta['fecha_validacion_factura'] = date('Y-m-d H:i:s');
        } elseif ($resultado_auto === false) {
            $venta['factura_valida'] = 0;
            $venta['fecha_validacion_factura'] = date('Y-m-d H:i:s');
        }
        
        // Recalcular fecha formateada si se actualizó
        if (!empty($venta['fecha_validacion_factura'])) {
            $venta['fecha_validacion_formateada'] = date('d/m/Y H:i', strtotime($venta['fecha_validacion_factura']));
        }
    }
}


// Obtener detalles del producto vendido
$sql_detalle = "SELECT vd.*, 
                       p.cod as cod_producto, p.nom_pro as nombre_producto,
                       pr.precio as precio_venta
                FROM venta_detalle vd
                LEFT JOIN productos p ON vd.id_prod = p.id_prod
                LEFT JOIN precios pr ON vd.id_pre_venta = pr.id_precio
                WHERE vd.id_venta = ? AND vd.status = 1";
$stmt_detalle = $conn_mysql->prepare($sql_detalle);
$stmt_detalle->bind_param('i', $id_venta);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();

// Obtener información del flete (incluyendo nuevos campos)
$sql_flete = "SELECT vf.*, 
                     p.precio as precio_flete,
                     CASE 
                         WHEN p.tipo = 'MFT' THEN 'Por tonelada'
                         WHEN p.tipo = 'MFV' THEN 'Por viaje'
                         ELSE p.tipo
                     END as tipo_flete,
                     DATE_FORMAT(vf.fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion_formateada
              FROM venta_flete vf
              LEFT JOIN precios p ON vf.id_pre_flete = p.id_precio
              WHERE vf.id_venta = ?";
$stmt_flete = $conn_mysql->prepare($sql_flete);
$stmt_flete->bind_param('i', $id_venta);
$stmt_flete->execute();
$flete = $stmt_flete->get_result();

// Obtener información de facturas duplicadas para esta venta (transportista)
$factura_transportista_duplicada_info = null;
if ($flete->num_rows > 0) {
    mysqli_data_seek($flete, 0);
    $flete_data = $flete->fetch_assoc();
    
    // Si ya tiene una factura asignada, verificar si está duplicada
    if (!empty($flete_data['factura_transportista'])) {
        $sql_check_duplicada = "SELECT v.id_venta, v.folio, v.status
                                FROM venta_flete vf
                                INNER JOIN ventas v ON vf.id_venta = v.id_venta
                                WHERE vf.factura_transportista = ? 
                                AND v.status = 1 
                                AND vf.id_venta != ?
                                ORDER BY v.fecha_venta DESC
                                LIMIT 1";
        
        $stmt_duplicada = $conn_mysql->prepare($sql_check_duplicada);
        if ($stmt_duplicada) {
            $stmt_duplicada->bind_param('si', $flete_data['factura_transportista'], $id_venta);
            $stmt_duplicada->execute();
            $result_duplicada = $stmt_duplicada->get_result();
            
            if ($result_duplicada->num_rows > 0) {
                $factura_transportista_duplicada_info = $result_duplicada->fetch_assoc();
            }
        }
    }
}

// Obtener información de facturas de venta duplicadas
$factura_venta_duplicada_info = null;
if (!empty($venta['factura_venta'])) {
    $sql_check_factura_venta_duplicada = "SELECT id_venta, folio, status
                                         FROM ventas 
                                         WHERE factura_venta = ? 
                                         AND status = 1 
                                         AND id_venta != ?
                                         ORDER BY fecha_venta DESC
                                         LIMIT 1";
    
    $stmt_factura_venta_duplicada = $conn_mysql->prepare($sql_check_factura_venta_duplicada);
    if ($stmt_factura_venta_duplicada) {
        $stmt_factura_venta_duplicada->bind_param('si', $venta['factura_venta'], $id_venta);
        $stmt_factura_venta_duplicada->execute();
        $result_factura_venta_duplicada = $stmt_factura_venta_duplicada->get_result();
        
        if ($result_factura_venta_duplicada->num_rows > 0) {
            $factura_venta_duplicada_info = $result_factura_venta_duplicada->fetch_assoc();
        }
    }
}

// Verificar si existe la factura en recolecciones
$factura_venta_existe_en_recoleccion = false;
$recoleccion_con_factura = null;
if (!empty($venta['factura_venta'])) {
    $sql_check_recoleccion = "SELECT r.id_recol, 
                                     CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_recoleccion,
                                     r.factura_v,
                                     r.status,
                                     p.rs as proveedor_nombre
                              FROM recoleccion r
                              LEFT JOIN zonas z ON r.zona = z.id_zone
                              LEFT JOIN proveedores p ON r.id_prov = p.id_prov
                              WHERE r.factura_v = ? 
                              AND r.status = 1
                              LIMIT 1";
    
    $stmt_recoleccion = $conn_mysql->prepare($sql_check_recoleccion);
    if ($stmt_recoleccion) {
        $stmt_recoleccion->bind_param('s', $venta['factura_venta']);
        $stmt_recoleccion->execute();
        $result_recoleccion = $stmt_recoleccion->get_result();
        
        if ($result_recoleccion->num_rows > 0) {
            $factura_venta_existe_en_recoleccion = true;
            $recoleccion_con_factura = $result_recoleccion->fetch_assoc();
        }
    }
}

// Calcular totales
$total_pacas = 0;
$total_kilos = 0;
$total_venta = 0;
$total_flete = 0;

while ($detalle = $detalles->fetch_assoc()) {
    $total_pacas += $detalle['pacas_cantidad'];
    $total_kilos += $detalle['total_kilos'];
    $total_venta += ($detalle['total_kilos'] * $detalle['precio_venta']);
}

// Reiniciar punteros
mysqli_data_seek($detalles, 0);
if ($flete->num_rows > 0) {
    mysqli_data_seek($flete, 0);
}

// Obtener flete
if ($flete_data = $flete->fetch_assoc()) {
    $total_flete = $flete_data['precio_flete'];
    if ($flete_data['tipo_flete'] == 'Por tonelada') {
        $total_flete = $total_flete * ($total_kilos / 1000);
    }
}
$total_general = $total_venta - $total_flete;

// Obtener el primer producto (ya que solo se vende uno)
$producto = $detalles->fetch_assoc();

// Determinar el estado de la factura de venta
$factura_venta_estado = 'sin_factura';
$factura_venta_clase = 'secondary';
$factura_venta_texto = 'Pendiente';

if (!empty($venta['factura_venta'])) {
    $factura_venta_estado = 'con_factura';
    $factura_venta_clase = 'success';
    $factura_venta_texto = $venta['factura_venta'];
    
    if ($factura_venta_duplicada_info) {
        $factura_venta_estado = 'duplicada';
        $factura_venta_clase = 'warning';
    }
}

// Mensajes de éxito
if (isset($_GET['success_factura']) && $_GET['success_factura'] == 1) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Factura de venta actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['success_flete']) && $_GET['success_flete'] == 1) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Datos del fletero actualizados correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}


// =============================================
// FUNCIONES DE VALIDACIÓN DE FACTURA (SIMPLIFICADAS)
// =============================================
function urlExists($url) {
    if (empty($url) || strpos($url, 'SIGN_') === false) {
        return false;
    }
    
    $ch = curl_init($url);
    
    // Configurar cURL para ser más rápido
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FacturaChecker/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode === 200);
}
/**
 * Construir URL para factura de venta (igual que en recolección)
 */
function construirURLFactura($factura_numero, $fecha_factura, $planta_zona, $tipo = 'FAC') {
    $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO",
                   "JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
    
    $fecha = DateTime::createFromFormat('Y-m-d', $fecha_factura);
    if (!$fecha) {
        $fecha = date_create($fecha_factura);
        if (!$fecha) {
            return null;
        }
    }
    
    $numero_mes = intval($fecha->format('m')) - 1;
    $ano = $fecha->format('Y');
    
    if (!isset($meses[$numero_mes])) {
        return null;
    }
    
    $mes_nombre = $meses[$numero_mes];
    
    if ($tipo == 'FAC' || $tipo == 'FV') {
        return "https://glama.esasacloud.com/doctos/{$planta_zona}/FACTURAS/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
    } else {
        return "https://glama.esasacloud.com/doctos/{$planta_zona}/REMISIONES/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
    }
}
function validarFacturaVenta($id_venta, $conn_mysql) {
    // Obtener datos básicos de la venta
    $sql = "SELECT v.factura_venta, v.fecha_factura, v.factura_valida,
                   v.url_factura_pdf, z.PLANTA as planta_zona
            FROM ventas v
            LEFT JOIN zonas z ON v.zona = z.id_zone
            WHERE v.id_venta = ?";
    
    $stmt = $conn_mysql->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param('i', $id_venta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) return false;
    $venta = $result->fetch_assoc();
    
    // Si no hay factura, no validar
    if (empty($venta['factura_venta']) || empty($venta['fecha_factura'])) {
        return false;
    }
    
    // Construir URL
    $url = construirURLFactura(
        $venta['factura_venta'],
        $venta['fecha_factura'],
        $venta['planta_zona'],
        'FAC' // Por defecto factura, no remisión
    );
    
    if (empty($url)) return false;
    
    // Validar existencia del PDF
    $existe_pdf = urlExists($url);
    
    // Actualizar BD (simple, sin campos extras)
    if ($existe_pdf) {
        $sql_update = "UPDATE ventas SET 
                      factura_valida = 1,
                      fecha_validacion_factura = NOW(),
                      url_factura_pdf = ?
                      WHERE id_venta = ?";
    } else {
        $sql_update = "UPDATE ventas SET 
                      factura_valida = 0,
                      fecha_validacion_factura = NOW(),
                      url_factura_pdf = ?
                      WHERE id_venta = ?";
    }
    
    $stmt_update = $conn_mysql->prepare($sql_update);
    $stmt_update->bind_param('si', $url, $id_venta);
    $stmt_update->execute();
    
    return $existe_pdf;
}

// Obtener ID de la venta
$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_venta <= 0) {
    alert("ID de venta no válido", 0, "ventas_info");
    exit;
}

// =============================================
// PROCESAR VALIDACIÓN MANUAL
// =============================================

if (isset($_GET['validar_factura']) && $_GET['validar_factura'] == 1) {
    $resultado = validarFacturaVenta($id_venta, $conn_mysql);
    
    if ($resultado === true) {
        alert("✅ Factura validada correctamente - PDF encontrado", 1, "V_venta&id=$id_venta");
    } elseif ($resultado === false) {
        alert("❌ Factura no válida - PDF no encontrado", 2, "V_venta&id=$id_venta");
    } else {
        alert("⚠️ No se pudo validar la factura", 2, "V_venta&id=$id_venta");
    }
}
?>

<div class="container-fluid px-3 py-3" style="max-width: 1400px; margin: 0 auto;">
    <!-- Header compacto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <button onclick="window.close()" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <h3 class="mb-1 fw-bold">Detalle de Venta</h3>
                        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                        </nav>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFacturaVenta">
                        <i class="bi bi-file-text me-2"></i>
                        <?= $factura_venta_estado == 'sin_factura' ? 'Agregar Factura' : 'Editar Factura' ?>
                    </button>
                    <?php if ($flete->num_rows > 0): ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFletero">
                        <i class="bi bi-truck me-2"></i>Datos Fletero
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-printer me-2"></i>Imprimir
                    </button>
                    <button class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Exportar
                    </button>
                </div>
            </div>
            
            <!-- Tarjeta de estado -->
            <div class="card border-0 shadow mb-3">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="bi bi-file-text text-primary fs-2"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?= htmlspecialchars($venta['folio_compuesto']) ?></h4>
                                    <small class="text-muted">Folio del documento</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-4">
                                    <small class="text-muted d-block">Fecha Venta</small>
                                    <strong><?= htmlspecialchars($venta['fecha_formateada']) ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Zona</small>
                                    <strong><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Responsable</small>
                                    <strong><?= htmlspecialchars($venta['nombre_usuario']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-end">
                                <div class="me-3">
                                    <small class="text-muted d-block">Estado</small>
                                    <span class="badge bg-success">Completada</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Creado</small>
                                    <small><?= htmlspecialchars($venta['fecha_creacion_formateada']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de factura de venta - SIMPLE Y DIRECTA -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="bg-<?= $factura_venta_clase ?> bg-opacity-25 border border-<?= $factura_venta_clase ?> rounded-2 p-2 d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <i class="bi bi-receipt me-2"></i>
                                    <strong>Factura de Venta:</strong> 
                                    <span class="ms-2"><?= htmlspecialchars($factura_venta_texto) ?></span>
                                    
                                    <?php if ($factura_venta_estado == 'con_factura' && !empty($venta['fecha_factura_formateada'])): ?>
                                        <span class="ms-3">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= htmlspecialchars($venta['fecha_factura_formateada']) ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Estado de validación (sí/no) -->
                                    <?php if (!empty($venta['factura_venta'])): ?>
                                        <span class="ms-3">
                                            <?php if ($venta['factura_valida'] == 1): ?>
                                                <span class="badge bg-success" title="PDF encontrado en el servidor">
                                                    <i class="bi bi-check-circle me-1"></i>Válida
                                                </span>
                                            <?php elseif (!empty($venta['fecha_validacion_formateada'])): ?>
                                                <span class="badge bg-danger" title="PDF no encontrado">
                                                    <i class="bi bi-x-circle me-1"></i>No válida
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" title="Sin validar">
                                                    <i class="bi bi-clock me-1"></i>Sin validar
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($factura_venta_duplicada_info): ?>
                                        <span class="badge bg-warning ms-2" title="Factura duplicada">
                                            <i class="bi bi-exclamation-triangle"></i> Duplicada
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Botón para validar factura (si existe factura) -->
                                    <?php if (!empty($venta['factura_venta'])): ?>
                                        <a href="?p=V_venta&id=<?= $id_venta ?>&validar_factura=1" 
                                        class="btn btn-sm btn-<?= $venta['factura_valida'] == 1 ? 'success' : 'warning' ?>"
                                        title="Validar existencia del PDF">
                                            <i class="bi bi-shield-check me-1"></i>
                                            <?= $venta['factura_valida'] == 1 ? 'Re-validar' : 'Validar' ?>
                                        </a>
                                        
                                        <!-- Enlace al PDF si está validado -->
                                        <?php if ($venta['factura_valida'] == 1 && !empty($venta['url_factura_pdf'])): ?>
                                            <a href="<?= htmlspecialchars($venta['url_factura_pdf']) ?>" 
                                            target="_blank" 
                                            class="btn btn-sm btn-outline-success"
                                            title="Ver PDF">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-<?= $factura_venta_clase == 'secondary' ? 'outline-primary' : 'outline-' . $factura_venta_clase ?>" 
                                            data-bs-toggle="modal" data-bs-target="#modalFacturaVenta">
                                        <i class="bi bi-pencil-square me-1"></i>
                                        <?= $factura_venta_estado == 'sin_factura' ? 'Agregar' : 'Editar' ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Mensaje simple si la factura no es válida -->
                            <?php if (!empty($venta['factura_venta']) && !empty($venta['fecha_validacion_formateada']) && $venta['factura_valida'] == 0): ?>
                                <div class="alert alert-danger mt-2 py-2" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Factura no válida:</strong> El PDF no fue encontrado en el servidor.
                                    Última validación: <?= htmlspecialchars($venta['fecha_validacion_formateada']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($factura_venta_duplicada_info): ?>
                                <div class="alert alert-warning mt-2 py-2" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Advertencia:</strong> Esta factura ya está registrada en otra venta.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Columna principal - Información del producto -->
        <div class="col-lg-8">
            <!-- Tarjeta principal del producto -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header py-2 px-3 border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam text-primary me-2"></i>Detalles del Producto
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">#</th>
                                    <th>Producto</th>
                                    <th class="text-center">Precio Unitario</th>
                                    <th class="text-center">Pacas</th>
                                    <th class="text-center">Kilos</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($producto): ?>
                                <tr>
                                    <td class="text-center align-middle">1</td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($producto['cod_producto']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($producto['nombre_producto']) ?></div>
                                        <?php if (!empty($producto['observaciones'])): ?>
                                        <div class="mt-1">
                                            <small class="text-info">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <?= htmlspecialchars($producto['observaciones']) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark fs-6">
                                            $<?= number_format($producto['precio_venta'], 2) ?>
                                        </span>
                                        <div class="text-muted small mt-1">por kg</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="fw-bold fs-5"><?= number_format($producto['pacas_cantidad'], 0) ?></div>
                                        <div class="text-muted small">unidades</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="fw-bold fs-5"><?= number_format($producto['total_kilos'], 2) ?></div>
                                        <div class="text-muted small">kilogramos</div>
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="fw-bold fs-5 text-success">
                                            $<?= number_format($producto['total_kilos'] * $producto['precio_venta'], 2) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Totales:</td>
                                    <td class="text-center fw-bold">
                                        <?= number_format($total_kilos, 2) ?> kg
                                    </td>
                                    <td class="text-end fw-bold fs-5 text-success">
                                        $<?= number_format($total_venta, 2) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Información de origen y destino en tarjetas compactas -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-header py-2 px-3 border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-shop text-primary me-2"></i>Origen
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($venta['nombre_almacen']) ?></div>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_almacen']) ?></small>
                                </div>
                            </div>
                            <div class="border-start border-3 border-primary ps-3 mt-3">
                                <small class="text-muted d-block">Bodega de Salida</small>
                                <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-header py-2 px-3 border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-person-badge text-success me-2"></i>Destino
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-person text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($venta['nombre_cliente']) ?></div>
                                    <small class="text-muted">Código: <?= htmlspecialchars($venta['cod_cliente']) ?></small>
                                </div>
                            </div>
                            <div class="border-start border-3 border-success ps-3 mt-3">
                                <small class="text-muted d-block">Bodega de Destino</small>
                                <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral - Resumen y flete -->
        <div class="col-lg-4">
            <!-- Resumen financiero compacto -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header py-2 px-3 border-bottom">
                    <h6 class="mb-0">
                        <i class="bi bi-calculator text-warning me-2"></i>Resumen Financiero
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Valor de venta:</span>
                                <span class="fw-semibold">$<?= number_format($total_venta, 2) ?></span>
                            </div>
                            <?php if ($total_flete > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Costo de flete:</span>
                                <span class="text-danger">-$<?= number_format($total_flete, 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Ganancia neta:</span>
                                <span class="fw-bold fs-5 <?= $total_general >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($total_general, 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Métricas rápidas -->
                        <div class="border rounded p-2 bg-body-tertiary">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Precio promedio</small>
                                    <strong>$<?= number_format($total_kilos > 0 ? $total_venta / $total_kilos : 0, 2) ?>/kg</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Peso por paca</small>
                                    <strong><?= number_format($total_pacas > 0 ? $total_kilos / $total_pacas : 0, 2) ?> kg</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de flete (si aplica) -->
            <?php if ($flete->num_rows > 0): 
                if ($flete_data) {
                    // Mostrar alerta si la factura está duplicada
                    if ($factura_transportista_duplicada_info): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> La factura del transportista ya está registrada en la venta activa con folio: 
                        <strong><?= htmlspecialchars($factura_transportista_duplicada_info['folio']) ?></strong>
                        <br><small>Se recomienda utilizar un número de factura único.</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card border-0 shadow mb-3">
                        <div class="card-header py-2 px-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-truck text-info me-2"></i>Información de Flete
                                </h6>
                                <?php if (!empty($flete_data['fecha_actualizacion_formateada'])): ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-clock-history me-1"></i>
                                    <?= htmlspecialchars($flete_data['fecha_actualizacion_formateada']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-truck text-info"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($venta['nombre_fletero']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-upc-scan me-1"></i>
                                        <?= htmlspecialchars($venta['placas_fletero']) ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Nuevos campos del fletero -->
                            <?php if (!empty($flete_data['tipo_camion']) || !empty($flete_data['nombre_chofer']) || 
                                       !empty($flete_data['placas_unidad']) || !empty($flete_data['factura_transportista'])): ?>
                            <div class="border rounded p-3 mb-3 bg-info bg-opacity-5">
                                <h6 class="fw-bold mb-2 text-info">
                                    <i class="bi bi-truck-front me-2"></i>Detalles del Transporte
                                </h6>
                                <div class="row g-2">
                                    <?php if (!empty($flete_data['tipo_camion'])): ?>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Tipo de unidad</small>
                                        <strong><?= htmlspecialchars($flete_data['tipo_camion']) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($flete_data['nombre_chofer'])): ?>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Nombre del chofer</small>
                                        <strong><?= htmlspecialchars($flete_data['nombre_chofer']) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($flete_data['placas_unidad'])): ?>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Placas de la unidad</small>
                                        <strong><?= htmlspecialchars($flete_data['placas_unidad']) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($flete_data['factura_transportista'])): ?>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Factura transportista</small>
                                        <div class="d-flex align-items-center">
                                            <strong><?= htmlspecialchars($flete_data['factura_transportista']) ?></strong>
                                            <?php if ($factura_transportista_duplicada_info): ?>
                                            <span class="badge bg-warning ms-2" data-bs-toggle="tooltip" 
                                                  title="Esta factura está duplicada en otra venta activa">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                No hay información adicional del transporte.
                                <small>Haz clic en "Datos Fletero" para agregarla.</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="border rounded p-2 bg-body-tertiary">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Tipo</small>
                                        <span class="badge bg-info"><?= htmlspecialchars($flete_data['tipo_flete']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Costo</small>
                                        <strong>$<?= number_format($total_flete, 2) ?></strong>
                                    </div>
                                    <?php if ($flete_data['tipo_flete'] == 'Por tonelada'): ?>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Precio por tonelada</small>
                                        <strong>$<?= number_format($flete_data['precio_flete'], 2) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php }
            endif; ?>

            <!-- Información adicional -->
            <div class="card border-0 shadow">
                <div class="card-header py-2 px-3 border-bottom">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle text-secondary me-2"></i>Información Adicional
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Zona</small>
                            <strong><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Folio Simple</small>
                            <strong>#<?= htmlspecialchars($venta['folio_simple']) ?></strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Creado</small>
                            <strong><?= htmlspecialchars($venta['fecha_creacion_formateada']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar factura de venta -->
<div class="modal fade" id="modalFacturaVenta" tabindex="-1" aria-labelledby="modalFacturaVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalFacturaVentaLabel">
                        <i class="bi bi-file-text me-2"></i>Factura de Venta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error_factura_venta)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error_factura_venta) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="factura_venta" class="form-label">
                                    <i class="bi bi-receipt me-1"></i>Número de Factura
                                </label>
                                <input type="text" 
                                       class="form-control <?= isset($error_factura_venta) ? 'is-invalid' : '' ?>" 
                                       id="factura_venta" 
                                       name="factura_venta" 
                                       value="<?= htmlspecialchars($venta['factura_venta'] ?? '') ?>"
                                       placeholder="Ej: FV-2024-001234">
                                <?php if (isset($error_factura_venta)): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($error_factura_venta) ?>
                                </div>
                                <?php endif; ?>
                                <div class="form-text">Número de factura de venta (debe ser único entre ventas activas)</div>
                                
                                <!-- Información de facturas duplicadas existentes -->
                                <?php if ($factura_venta_duplicada_info): ?>
                                <div class="alert alert-warning mt-2 p-2">
                                    <small>
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Esta factura ya está registrada en la venta con folio: 
                                        <strong><?= htmlspecialchars($factura_venta_duplicada_info['folio']) ?></strong>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_factura" class="form-label">
                                    <i class="bi bi-calendar me-1"></i>Fecha de Factura
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_factura" 
                                       name="fecha_factura" 
                                       value="<?= htmlspecialchars($venta['fecha_factura'] ?? '') ?>"
                                       max="<?= date('Y-m-d') ?>">
                                <div class="form-text">Fecha de emisión de la factura</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de la venta -->
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Folio de Venta</small>
                                    <strong><?= htmlspecialchars($venta['folio_compuesto']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Cliente</small>
                                    <strong><?= htmlspecialchars($venta['nombre_cliente']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Fecha de Venta</small>
                                    <strong><?= htmlspecialchars($venta['fecha_formateada']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Total Venta</small>
                                    <strong>$<?= number_format($total_venta, 2) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Importante:</strong> Los números de factura de venta deben ser únicos entre todas las ventas activas.
                        Esta validación evita duplicados en el sistema contable.
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        <strong>Validación automática:</strong> El sistema verificará que la factura no esté registrada en otra venta activa antes de guardar.
                    </div>
                    
                    <?php if (!empty($venta['factura_actualizada_formateada'])): ?>
                    <div class="alert alert-secondary">
                        <i class="bi bi-clock-history me-2"></i>
                        Última actualización: <?= htmlspecialchars($venta['factura_actualizada_formateada']) ?>
                    </div>
                    <?php endif; ?>
                    <!-- En el modal #modalFacturaVenta, antes del cierre del modal-body -->
                    <?php if (!empty($venta['factura_venta'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="small text-muted">
                            <i class="bi bi-shield-check me-2"></i>Validación de Factura
                        </h6>
                        
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted">Estado:</span>
                                <div>
                                    <?php if ($venta['factura_valida'] == 1): ?>
                                        <span class="badge bg-success">Válida</span>
                                    <?php elseif (!empty($venta['fecha_validacion_formateada'])): ?>
                                        <span class="badge bg-danger">No válida</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sin validar</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <span class="text-muted">Última validación:</span>
                                <div>
                                    <?= !empty($venta['fecha_validacion_formateada']) ? 
                                        htmlspecialchars($venta['fecha_validacion_formateada']) : 
                                        '<span class="text-muted">Nunca</span>' ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="?p=V_venta&id=<?= $id_venta ?>&validar_factura=1" 
                            class="btn btn-sm btn-outline-info">
                                <i class="bi bi-arrow-clockwise me-1"></i>Validar ahora
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" name="actualizar_factura_venta" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Factura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar datos del fletero -->
<?php if ($flete->num_rows > 0): ?>
<div class="modal fade" id="modalFletero" tabindex="-1" aria-labelledby="modalFleteroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalFleteroLabel">
                        <i class="bi bi-truck me-2"></i>Datos del Fletero
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error_flete)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error_flete) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_camion" class="form-label">
                                    <i class="bi bi-truck me-1"></i>Tipo de Unidad
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="tipo_camion" 
                                       name="tipo_camion" 
                                       value="<?= htmlspecialchars($flete_data['tipo_camion'] ?? '') ?>"
                                       placeholder="Ej: Camión de 20 toneladas, Trailer, etc.">
                                <div class="form-text">Opcional - Describe el tipo de vehículo utilizado</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_chofer" class="form-label">
                                    <i class="bi bi-person-badge me-1"></i>Nombre del Chofer
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre_chofer" 
                                       name="nombre_chofer" 
                                       value="<?= htmlspecialchars($flete_data['nombre_chofer'] ?? '') ?>"
                                       placeholder="Ej: Juan Pérez Rodríguez">
                                <div class="form-text">Opcional - Nombre completo del conductor</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="placas_unidad" class="form-label">
                                    <i class="bi bi-upc-scan me-1"></i>Placas de la Unidad
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="placas_unidad" 
                                       name="placas_unidad" 
                                       value="<?= htmlspecialchars($flete_data['placas_unidad'] ?? '') ?>"
                                       placeholder="Ej: ABC-123-DEF">
                                <div class="form-text">Opcional - Número de placas del vehículo</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="factura_transportista" class="form-label">
                                    <i class="bi bi-receipt me-1"></i>Factura Transportista
                                </label>
                                <input type="text" 
                                       class="form-control <?= isset($error_flete) ? 'is-invalid' : '' ?>" 
                                       id="factura_transportista" 
                                       name="factura_transportista" 
                                       value="<?= htmlspecialchars($flete_data['factura_transportista'] ?? '') ?>"
                                       placeholder="Ej: FT-2024-001234">
                                <?php if (isset($error_flete)): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($error_flete) ?>
                                </div>
                                <?php endif; ?>
                                <div class="form-text">Opcional - Número de factura del transportista (debe ser único entre ventas activas)</div>
                                
                                <!-- Información de facturas duplicadas existentes -->
                                <?php if ($factura_transportista_duplicada_info): ?>
                                <div class="alert alert-warning mt-2 p-2">
                                    <small>
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Esta factura ya está registrada en la venta con folio: 
                                        <strong><?= htmlspecialchars($factura_transportista_duplicada_info['folio']) ?></strong>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Nota:</strong> Los números de factura del transportista deben ser únicos entre todas las ventas activas.
                        Si una venta está inactiva, puede tener facturas duplicadas.
                    </div>
                    
                    <?php if (!empty($flete_data['fecha_actualizacion_formateada'])): ?>
                    <div class="alert alert-secondary">
                        <i class="bi bi-clock-history me-2"></i>
                        Última actualización: <?= htmlspecialchars($flete_data['fecha_actualizacion_formateada']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" name="actualizar_flete" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Estilos específicos para ERP compacto -->
<style>
:root {
    --erp-primary: #2c3e50;
    --erp-secondary: #f8f9fa;
    --erp-border: #dee2e6;
    --erp-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background-color: #f8f9fa;
    padding: 10px 12px;
}

.table td {
    padding: 12px;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.03);
    transition: background-color 0.2s ease;
}

/* Estilos para métricas */
.border-start {
    border-left-width: 3px !important;
}

.text-muted {
    color: #6c757d !important;
    font-size: 0.85rem;
}

/* Mejoras responsivas */
@media (max-width: 768px) {
    .container-fluid {
        padding: 10px !important;
    }
    
    .card-body {
        padding: 12px !important;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .modal-dialog {
        margin: 10px;
    }
}

/* Estilos para números y montos */
.fw-bold {
    font-weight: 600 !important;
}

.text-success {
    color: #198754 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}

/* Scroll suave */
.table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
}

.table-responsive::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Estilos específicos para la información del fletero */
.bg-info.bg-opacity-5 {
    background-color: rgba(13, 202, 240, 0.05) !important;
}

.bg-info.bg-opacity-10 {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

/* Mejoras para el modal */
.modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-radius: 10px 10px 0 0;
}

.form-label i {
    font-size: 0.9rem;
}
</style>

<script>
// Script para mejor experiencia de usuario
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar ventana con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.close();
        }
    });
    
    // Agregar tooltips si se necesitan
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mejorar la experiencia de impresión
    document.querySelectorAll('[class*="btn-outline-primary"]').forEach(button => {
        if (button.textContent.includes('Imprimir')) {
            button.addEventListener('click', function() {
                window.print();
            });
        }
    });
    
    // Auto-focus en los campos del modal cuando se abren
    const modalFacturaVenta = document.getElementById('modalFacturaVenta');
    if (modalFacturaVenta) {
        modalFacturaVenta.addEventListener('shown.bs.modal', function () {
            const facturaInput = modalFacturaVenta.querySelector('#factura_venta');
            const errorAlert = modalFacturaVenta.querySelector('.alert-danger');
            
            if (errorAlert && facturaInput) {
                facturaInput.focus();
                facturaInput.select();
            } else {
                if (facturaInput) {
                    facturaInput.focus();
                }
            }
        });
    }
    
    const modalFletero = document.getElementById('modalFletero');
    if (modalFletero) {
        modalFletero.addEventListener('shown.bs.modal', function () {
            const facturaInput = modalFletero.querySelector('#factura_transportista');
            const errorAlert = modalFletero.querySelector('.alert-danger');
            
            if (errorAlert && facturaInput) {
                facturaInput.focus();
                facturaInput.select();
            } else {
                const firstInput = modalFletero.querySelector('input[type="text"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    }
    
    // Mostrar notificación de éxito
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Validación en tiempo real para los campos de factura
    const facturaVentaInput = document.getElementById('factura_venta');
    if (facturaVentaInput) {
        facturaVentaInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const invalidFeedback = this.nextElementSibling;
                if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                    invalidFeedback.style.display = 'none';
                }
            }
        });
    }
    
    const facturaTransportistaInput = document.getElementById('factura_transportista');
    if (facturaTransportistaInput) {
        facturaTransportistaInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const invalidFeedback = this.nextElementSibling;
                if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                    invalidFeedback.style.display = 'none';
                }
            }
        });
    }
    
    // Confirmación antes de enviar si hay factura duplicada
    const submitButtons = document.querySelectorAll('button[name="actualizar_flete"], button[name="actualizar_factura_venta"]');
    submitButtons.forEach(submitButton => {
        const form = submitButton.closest('form');
        form.addEventListener('submit', function(e) {
            let facturaInput;
            let warningAlert;
            
            if (submitButton.name === 'actualizar_flete') {
                facturaInput = document.getElementById('factura_transportista');
                warningAlert = form.querySelector('.alert-warning.mt-2');
            } else if (submitButton.name === 'actualizar_factura_venta') {
                facturaInput = document.getElementById('factura_venta');
                warningAlert = form.querySelector('.alert-warning.mt-2');
            }
            
            if (facturaInput) {
                const facturaValue = facturaInput.value.trim();
                
                if (facturaValue && warningAlert) {
                    if (!confirm('⚠️ Esta factura ya está registrada en otra venta activa. ¿Estás seguro de que deseas continuar?')) {
                        e.preventDefault();
                        facturaInput.focus();
                    }
                }
            }
        });
    });
    
    // Establecer fecha máxima para el campo de fecha de factura
    const fechaFacturaInput = document.getElementById('fecha_factura');
    if (fechaFacturaInput && !fechaFacturaInput.value) {
        fechaFacturaInput.value = new Date().toISOString().split('T')[0];
    }
});
// Solo lo esencial
document.addEventListener('DOMContentLoaded', function() {
    // Auto-validar si se guardó una factura nueva
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success_factura') && urlParams.get('success_factura') == '1') {
        // Opcional: auto-validar después de 2 segundos
        setTimeout(() => {
            if (confirm('¿Deseas validar la factura automáticamente?')) {
                window.location.href = window.location.pathname + '?p=V_venta&id=<?= $id_venta ?>&validar_factura=1';
            }
        }, 2000);
    }
    
    // Validar formato de factura (eliminar espacios)
    const facturaInput = document.getElementById('factura_venta');
    if (facturaInput) {
        facturaInput.addEventListener('blur', function() {
            this.value = this.value.replace(/\s+/g, '');
        });
    }
});
</script>