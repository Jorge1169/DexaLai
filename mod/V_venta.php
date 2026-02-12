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
                    logActivity('VENTA_FACTURA', "Factura de venta actualizada en venta {$id_venta}: {$factura_venta}");
                    // Refrescar la página para mostrar los cambios
                    alert("Factura de venta actualizada con éxito", 1, "V_venta&id=$id_venta");
                    exit;
                } else {
                    logActivity('VENTA_FACTURA_ERROR', "Error al actualizar factura de venta {$id_venta}: " . $stmt_update_factura->error);
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
                    logActivity('VENTA_FLETE', "Datos de flete actualizados en venta {$id_venta} (factura {$factura_transportista})");
                    // Refrescar la página para mostrar los cambios
                    alert("Datos del fletero actualizados con éxito", 1, "V_venta&id=$id_venta");
                    exit;
                } else {
                    logActivity('VENTA_FLETE_ERROR', "Error al actualizar flete en venta {$id_venta}: " . $stmt_update->error);
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
        //error_log("Validación automática de factura para venta ID: $id_venta");
        
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
                    vf.impuestoTraslado_v as impuestoTraslado_flete,
                    vf.impuestoRetenido_v as impuestoRetenido_flete,
                    vf.subtotal_v as subtotal_flete,
                    vf.total_v as total_flete,
                     vf.aliasven as alias_CR_venta,
                     vf.folioven as folio_CR_venta,
                     p.precio as precio_flete,
                     CASE 
                         WHEN p.tipo = 'MFT' THEN 'Por tonelada'
                         WHEN p.tipo = 'MFV' THEN 'Por viaje'
                         ELSE p.tipo
                     END as tipo_flete,
                     DATE_FORMAT(vf.fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion_formateada,
                     DATE_FORMAT(vf.fecha_subida_ticket, '%d/%m/%Y %H:%i') as fecha_subida_ticket_formateada,
                     vf.folio_ticket_bascula,
                     vf.archivo_ticket
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
    
    $current_year = intval(date('Y'));
    $es_ano_pasado = (intval($ano) === $current_year - 1);
    
    $tipo_upper = strtoupper($tipo);
    if ($es_ano_pasado) {
        // Usar dominio OLD para facturas/remisiones del año pasado
        if ($tipo_upper == 'FAC' || $tipo_upper == 'FV') {
            return "https://olddocs.esasacloud.com/olddocs-01/cpu27/{$planta_zona}/FACTURAS/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
        } else {
            return "https://olddocs.esasacloud.com/olddocs-01/cpu27/{$planta_zona}/REMISIONES/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
        }
    } else {
        // Dominio actual
        if ($tipo_upper == 'FAC' || $tipo_upper == 'FV') {
            return "https://glama.esasacloud.com/doctos/{$planta_zona}/FACTURAS/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
        } else {
            return "https://glama.esasacloud.com/doctos/{$planta_zona}/REMISIONES/{$ano}/{$mes_nombre}/SIGN_{$factura_numero}.pdf";
        }
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
        logActivity('VENTA_FACTURA_VALIDACION', "Factura validada correctamente para venta {$id_venta}");
        alert("✅ Factura validada correctamente - PDF encontrado", 1, "V_venta&id=$id_venta");
    } elseif ($resultado === false) {
        logActivity('VENTA_FACTURA_VALIDACION', "Factura no válida (PDF no encontrado) para venta {$id_venta}");
        alert("❌ Factura no válida - PDF no encontrado", 2, "V_venta&id=$id_venta");
    } else {
        logActivity('VENTA_FACTURA_VALIDACION_ERROR', "No se pudo validar la factura para venta {$id_venta}");
        alert("⚠️ No se pudo validar la factura", 2, "V_venta&id=$id_venta");
    }
}
// =============================================
// FUNCIONES SIMPLIFICADAS PARA TICKET DE BÁSCULA
// =============================================

/**
 * Función simplificada para subir ticket
 */
function subirTicketBascula($archivo_temporal, $nombre_original, $id_venta, $folio_ticket) {
    // Obtener la ruta base del proyecto de forma dinámica
    // Usar la misma estructura que en tu otro módulo que funciona
    $directorio_base = __DIR__ . '/../uploads/ticket/';
    
    // Verificar y crear directorios si no existen
    if (!is_dir($directorio_base)) {
        if (!mkdir($directorio_base, 0755, true)) {
            return ['error' => 'No se pudo crear el directorio: ' . $directorio_base];
        }
    }
    
    // Verificar que el directorio sea escribible
    if (!is_writable($directorio_base)) {
        // Intentar cambiar permisos
        if (!chmod($directorio_base, 0755)) {
            return ['error' => 'El directorio no tiene permisos de escritura: ' . $directorio_base];
        }
    }
    
    // Validar que sea un archivo válido
    if (!is_uploaded_file($archivo_temporal)) {
        return ['error' => 'Archivo no válido o no subido correctamente'];
    }
    
    // Obtener extensión
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    
    // Validar extensión
    $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp'];
    if (!in_array($extension, $extensiones_permitidas)) {
        return ['error' => 'Tipo de archivo no permitido. Solo PDF, JPG, PNG, GIF, BMP'];
    }
    
    // Validar tamaño (5MB máximo)
    $tamano = filesize($archivo_temporal);
    if ($tamano > (5 * 1024 * 1024)) {
        return ['error' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Crear nombre seguro para el archivo
    $folio_limpio = preg_replace('/[^a-zA-Z0-9]/', '_', $folio_ticket);
    $nombre_archivo = 'ticket_' . $id_venta . '_' . $folio_limpio . '_' . time() . '.' . $extension;
    $ruta_completa = $directorio_base . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($archivo_temporal, $ruta_completa)) {
        // Para guardar en BD, usar solo el nombre del archivo
        return [
            'success' => true,
            'nombre_archivo' => $nombre_archivo,
            'ruta_completa' => $ruta_completa,
            'url_relativa' => 'uploads/ticket/' . $nombre_archivo
        ];
    } else {
        $error_info = error_get_last();
        return ['error' => 'Error al mover el archivo: ' . ($error_info['message'] ?? 'Error desconocido')];
    }
}
function optimizarImagenParaHosting($tmp_path, $dest_path, $mime_type) {
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($tmp_path);
            if (!$image) throw new Exception('No se pudo procesar la imagen JPEG');
            imagejpeg($image, $dest_path, 85); // Calidad 85%
            imagedestroy($image);
            break;
            
        case 'image/png':
            $image = imagecreatefrompng($tmp_path);
            if (!$image) throw new Exception('No se pudo procesar la imagen PNG');
            imagesavealpha($image, true);
            imagepng($image, $dest_path, 8); // Compresión 8/9
            imagedestroy($image);
            break;
            
        case 'image/webp':
            $image = imagecreatefromwebp($tmp_path);
            if (!$image) throw new Exception('No se pudo procesar la imagen WebP');
            imagewebp($image, $dest_path, 85);
            imagedestroy($image);
            break;
            
        case 'image/gif':
            // Para GIFs, simplemente mover
            move_uploaded_file($tmp_path, $dest_path);
            break;
            
        default:
            throw new Exception('Tipo de imagen no soportado para optimización');
    }
    
    return true;
}
/**
 * Función simplificada para obtener información del ticket
 */
function obtenerInfoTicket($nombre_archivo) {
    if (empty($nombre_archivo)) {
        return null;
    }
    
    // Misma ruta que en subirTicketBascula
    $directorio_base = $_SERVER['DOCUMENT_ROOT'] . '/DexaLai/uploads/ticket/';
    $ruta_archivo = $directorio_base . $nombre_archivo;
    $url_base = '/DexaLai/uploads/ticket/';
    
    if (!file_exists($ruta_archivo)) {
        return [
            'nombre' => $nombre_archivo,
            'existe' => false,
            'error' => 'Archivo no encontrado en: ' . $ruta_archivo
        ];
    }
    
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    $tamano = filesize($ruta_archivo);
    
    return [
        'nombre' => $nombre_archivo,
        'ruta' => $ruta_archivo,
        'url' => $url_base . $nombre_archivo,
        'url_absoluta' => 'http://' . $_SERVER['HTTP_HOST'] . $url_base . $nombre_archivo,
        'extension' => $extension,
        'tamano' => $tamano,
        'tamano_formateado' => formatoTamanoSimple($tamano),
        'es_imagen' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']),
        'es_pdf' => $extension == 'pdf',
        'existe' => true
    ];
}

/**
 * Formatear tamaño de forma simple
 */
function formatoTamanoSimple($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Eliminar archivo de ticket
 */
function eliminarTicketArchivo($nombre_archivo) {
    if (empty($nombre_archivo)) {
        return true;
    }
    
    // Misma ruta consistente
    $directorio_base = __DIR__ . '/../uploads/ticket/';
    $ruta_archivo = $directorio_base . $nombre_archivo;
    
    if (file_exists($ruta_archivo) && is_file($ruta_archivo)) {
        return @unlink($ruta_archivo);
    }
    
    return true;
}
function verificarDirectorioTicket() {
    $directorio = $_SERVER['DOCUMENT_ROOT'] . '/DexaLai/uploads/ticket/';
    
    echo "<div style='background:#f0f0f0; padding:10px; margin:10px 0;'>";
    echo "<strong>Debug de directorio:</strong><br>";
    echo "Ruta: " . $directorio . "<br>";
    echo "Existe: " . (is_dir($directorio) ? 'Sí' : 'No') . "<br>";
    echo "Es escribible: " . (is_writable($directorio) ? 'Sí' : 'No') . "<br>";
    echo "Permisos: " . substr(sprintf('%o', fileperms($directorio)), -4) . "<br>";
    echo "</div>";
}
// Procesar subida de ticket de báscula - VERSIÓN SIMPLIFICADA
if (isset($_POST['subir_ticket'])) {
    $id_venta = intval($_POST['id_venta'] ?? 0);
    $folio_ticket = trim($_POST['folio_ticket'] ?? '');
    
    // Validaciones básicas
    if ($id_venta <= 0) {
        alert("ID de venta no válido", 2, "V_venta&id=$id_venta");
        exit;
    }
    
    if (empty($folio_ticket)) {
        alert("El folio del ticket es obligatorio", 2, "V_venta&id=$id_venta");
        exit;
    }
    
    // Verificar archivo
    if (!isset($_FILES['archivo_ticket']) || $_FILES['archivo_ticket']['error'] !== UPLOAD_ERR_OK) {
        alert("Debe seleccionar un archivo válido", 2, "V_venta&id=$id_venta");
        exit;
    }
    
    $archivo_temporal = $_FILES['archivo_ticket']['tmp_name'];
    $nombre_original = $_FILES['archivo_ticket']['name'];
    $tamano_archivo = $_FILES['archivo_ticket']['size'];
    
    // Determinar tipo MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo_temporal);
    finfo_close($finfo);
    
    // Tipos de archivo permitidos
    $tipos_permitidos = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf'
    ];
    
    if (!array_key_exists($tipo_mime, $tipos_permitidos)) {
        alert("Tipo de archivo no permitido. Solo JPG, PNG, GIF o PDF", 2, "V_venta&id=$id_venta");
        exit;
    }
    
    // Validar tamaño (5MB máximo)
    if ($tamano_archivo > (5 * 1024 * 1024)) {
        alert("El archivo es demasiado grande. Máximo 5MB", 2, "V_venta&id=$id_venta");
        exit;
    }
    
    try {
        // Si hay archivo anterior, eliminarlo
        $sql_anterior = "SELECT archivo_ticket FROM venta_flete WHERE id_venta = ?";
        $stmt_anterior = $conn_mysql->prepare($sql_anterior);
        $stmt_anterior->bind_param('i', $id_venta);
        $stmt_anterior->execute();
        $result_anterior = $stmt_anterior->get_result();
        
        if ($result_anterior->num_rows > 0) {
            $anterior = $result_anterior->fetch_assoc();
            if (!empty($anterior['archivo_ticket'])) {
                eliminarTicketArchivo($anterior['archivo_ticket']);
            }
        }
        
        // Preparar directorio y nombre de archivo
        $directorio_base = __DIR__ . '/../uploads/ticket/';
        if (!is_dir($directorio_base)) {
            mkdir($directorio_base, 0755, true);
        }
        
        // Crear nombre único para el archivo
        $extension = $tipos_permitidos[$tipo_mime];
        $folio_limpio = preg_replace('/[^a-zA-Z0-9]/', '_', $folio_ticket);
        $nombre_archivo = 'ticket_' . $id_venta . '_' . $folio_limpio . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio_base . $nombre_archivo;
        
        // Procesar el archivo según su tipo
        if ($tipo_mime === 'application/pdf') {
            // Para PDFs, simplemente mover
            if (!move_uploaded_file($archivo_temporal, $ruta_completa)) {
                throw new Exception('Error al mover el archivo PDF');
            }
        } else {
            // Para imágenes, optimizar
            optimizarImagenParaHosting($archivo_temporal, $ruta_completa, $tipo_mime);
        }
        
        // Actualizar en base de datos
        $sql_update = "UPDATE venta_flete 
                       SET folio_ticket_bascula = ?,
                           archivo_ticket = ?,
                           fecha_subida_ticket = NOW()
                       WHERE id_venta = ?";
        
        $stmt_update = $conn_mysql->prepare($sql_update);
        if (!$stmt_update) {
            throw new Exception('Error al preparar la consulta SQL');
        }
        
        $stmt_update->bind_param('ssi', 
            $folio_ticket,
            $nombre_archivo,
            $id_venta
        );
        
        if ($stmt_update->execute()) {
            logActivity('VENTA_TICKET', "Subido ticket de báscula {$folio_ticket} para venta {$id_venta}");
            alert("✅ Ticket de báscula guardado correctamente", 1, "V_venta&id=$id_venta");
            exit;
        } else {
            throw new Exception('Error al guardar en la base de datos: ' . $stmt_update->error);
        }
        
    } catch (Exception $e) {
        logActivity('VENTA_TICKET_ERROR', "Error al subir ticket de báscula para venta {$id_venta}: " . $e->getMessage());
        alert("❌ Error al subir el ticket: " . $e->getMessage(), 2, "V_venta&id=$id_venta");
        exit;
    }
}


// Procesar eliminación de ticket - VERSIÓN SIMPLIFICADA
if (isset($_POST['eliminar_ticket'])) {
    $id_venta = intval($_POST['id_venta'] ?? 0);
    
    // Obtener nombre del archivo
    $sql_archivo = "SELECT archivo_ticket FROM venta_flete WHERE id_venta = ?";
    $stmt_archivo = $conn_mysql->prepare($sql_archivo);
    $stmt_archivo->bind_param('i', $id_venta);
    $stmt_archivo->execute();
    $result_archivo = $stmt_archivo->get_result();
    
    if ($result_archivo->num_rows > 0) {
        $archivo_data = $result_archivo->fetch_assoc();
        if (!empty($archivo_data['archivo_ticket'])) {
            eliminarTicketArchivo($archivo_data['archivo_ticket']);
        }
    }
    
    // Actualizar base de datos
    $sql_delete = "UPDATE venta_flete 
                   SET folio_ticket_bascula = NULL,
                       archivo_ticket = NULL,
                       fecha_subida_ticket = NULL
                   WHERE id_venta = ?";
    
    $stmt_delete = $conn_mysql->prepare($sql_delete);
    if ($stmt_delete) {
        $stmt_delete->bind_param('i', $id_venta);
        
        if ($stmt_delete->execute()) {
            logActivity('VENTA_TICKET_ELIMINADO', "Ticket de báscula eliminado en venta {$id_venta}");
            alert("✅ Ticket de báscula eliminado correctamente", 1, "V_venta&id=$id_venta");
            exit;
        } else {
            logActivity('VENTA_TICKET_ERROR', "Error al eliminar ticket de báscula en venta {$id_venta}");
            alert("❌ Error al eliminar el ticket de la base de datos", 2, "V_venta&id=$id_venta");
        }
    }
}

?>

<div class="container-fluid px-3 py-3" style="max-width: 1400px; margin: 0 auto;">
    <!-- Header principal -->
    <div class="row mb-4">
        <div class="col-12">
            <!-- Tarjeta de información principal -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header encabezado-col">
                    <!-- Barra de acciones superior -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <button onclick="window.close()" class="btn btn-warning btn-sm me-3">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <div>
                                <h3 class="mb-1 fw-bold text-light">Detalle de Venta</h3>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFacturaVenta">
                                <i class="bi bi-file-text me-1"></i>
                                <?= $factura_venta_estado == 'sin_factura' ? 'Factura' : 'Editar Factura' ?>
                            </button>
                            <?php if ($flete->num_rows > 0): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFletero">
                                <i class="bi bi-truck me-1"></i>Transporte
                            </button>
                            <?php endif; ?>
                            <?php if ($flete_data['tipo_camion'] != null and $flete_data['placas_unidad'] != null and $flete_data['nombre_chofer'] != null) {
                               ?>
                                <a href="doc/remision.php?id=<?= $id_venta ?>" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="bi bi-file-text me-1"></i>Remisión
                                </a>
                               <?php 
                            }?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="bi bi-cart-check text-primary fs-2"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold">Venta #<?= htmlspecialchars($venta['folio_compuesto']) ?></h4>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?= htmlspecialchars($venta['fecha_formateada']) ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-person me-1"></i>
                                        <?= htmlspecialchars($venta['nombre_usuario']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <div class="border-start border-3 border-primary ps-3">
                                        <small class="text-muted d-block">Zona</small>
                                        <strong class="d-block"><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <div class="border-start border-3 border-success ps-3">
                                        <small class="text-muted d-block">Valor Venta</small>
                                        <strong class="d-block text-success">$<?= number_format($total_venta, 2) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <div class="border-start border-3 border-info ps-3">
                                        <small class="text-muted d-block">Costo Flete</small>
                                        <strong class="d-block text-info">$<?= number_format($total_flete, 2) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-start border-3 border-warning ps-3">
                                        <small class="text-muted d-block">Ganancia Neta</small>
                                        <strong class="d-block text-warning">$<?= number_format($total_general, 2) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barra de estado de factura -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-2 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-receipt me-2 fs-5 text-<?= $factura_venta_clase ?>"></i>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">
                                            Factura de Venta 
                                            <?php if (!empty($venta['factura_venta'])): ?>
                                            <span class="ms-2 fw-normal"><?= htmlspecialchars($venta['factura_venta']) ?></span>
                                            <?php endif; ?>
                                        </h5>
                                        <?php if ($factura_venta_estado == 'con_factura' && !empty($venta['fecha_factura_formateada'])): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= htmlspecialchars($venta['fecha_factura_formateada']) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2">
                                <!-- Estado de validación -->
                                <?php if (!empty($venta['factura_venta'])): ?>
                                    <?php if ($venta['factura_valida'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success">
                                            <i class="bi bi-check-circle me-1"></i>Válida
                                        </span>
                                        <?php if (!empty($venta['url_factura_pdf'])): ?>
                                        <a href="<?= htmlspecialchars($venta['url_factura_pdf']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-success"
                                           title="Ver PDF">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                        </a>
                                        <?php endif; ?>
                                    <?php elseif (!empty($venta['fecha_validacion_formateada'])): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">
                                            <i class="bi bi-x-circle me-1"></i>No válida
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">
                                            <i class="bi bi-clock me-1"></i>Sin validar
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="?p=V_venta&id=<?= $id_venta ?>&validar_factura=1" 
                                    class="btn btn-sm btn-<?= $venta['factura_valida'] == 1 ? 'outline-success' : 'outline-warning' ?>"
                                    title="Validar existencia del PDF">
                                        <i class="bi bi-shield-check me-1"></i>
                                        <?= $venta['factura_valida'] == 1 ? 'Revalidar' : 'Validar' ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($factura_venta_duplicada_info): ?>
                                <span class="badge bg-warning bg-opacity-20 text-warning border border-warning" 
                                      data-bs-toggle="tooltip" title="Factura duplicada">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Duplicada
                                </span>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#modalFacturaVenta">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    <?= $factura_venta_estado == 'sin_factura' ? 'Agregar' : 'Editar' ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Mensajes informativos -->
                        <?php if (!empty($venta['factura_venta']) && !empty($venta['fecha_validacion_formateada']) && $venta['factura_valida'] == 0): ?>
                        <div class="alert alert-danger alert-sm mt-3 py-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Factura no válida:</strong> El PDF no fue encontrado en el servidor.
                            <small class="ms-2">Última validación: <?= htmlspecialchars($venta['fecha_validacion_formateada']) ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($factura_venta_duplicada_info): ?>
                        <div class="alert alert-warning alert-sm mt-2 py-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> Esta factura ya está registrada en otra venta activa.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal en grid 3 columnas -->
    <div class="row g-3">
        <!-- Columna 1: Producto y origen/destino -->
        <div class="col-lg-4">
            <!-- Tarjeta del producto -->
            <div class="card border-0 shadow h-100">
                <div class="card-header bg-transparent border-bottom py-2">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam text-primary me-2"></i>Producto Vendido
                    </h5>
                </div>
                <div class="card-body p-3">
                    <?php if ($producto): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($producto['nombre_producto']) ?></h6>
                                <p class="text-muted small mb-0">Código: <?= htmlspecialchars($producto['cod_producto']) ?></p>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-tag me-1"></i>
                                $<?= number_format($producto['precio_venta'], 2) ?>/kg
                            </span>
                        </div>
                        
                        <?php if (!empty($producto['observaciones'])): ?>
                        <div class="alert alert-info alert-sm py-2 mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <?= htmlspecialchars($producto['observaciones']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="border rounded p-2 text-center bg-body-tertiary h-100">
                                    <small class="text-muted d-block">Pacas</small>
                                    <h4 class="fw-bold mb-0"><?= number_format($producto['pacas_cantidad'], 0) ?></h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center bg-body-tertiary h-100">
                                    <small class="text-muted d-block">Kilos</small>
                                    <h4 class="fw-bold mb-0"><?= number_format($producto['total_kilos'], 1) ?></h4>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-bold fs-5 text-success">
                                    $<?= number_format($producto['total_kilos'] * $producto['precio_venta'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Métricas compactas -->
                    <div class="border rounded p-3 bg-body-tertiary">
                        <h6 class="fw-bold mb-2 text-muted d-flex align-items-center">
                            <i class="bi bi-graph-up me-2"></i>Métricas
                        </h6>
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

        <!-- Columna 2: Origen y Destino -->
        <div class="col-lg-4">
            <?php
            // Obtener direcciones completas de las bodegas (si no están ya en $venta)
            $dir_alm = null;
            $dir_cli = null;

            if (!empty($venta['id_direc_alma'])) {
            $sql_dir_alm = "SELECT calle, numext, numint, colonia, estado, c_postal FROM direcciones WHERE id_direc = ? LIMIT 1";
            $stmt_dir_alm = $conn_mysql->prepare($sql_dir_alm);
            if ($stmt_dir_alm) {
                $stmt_dir_alm->bind_param('i', $venta['id_direc_alma']);
                $stmt_dir_alm->execute();
                $res_dir_alm = $stmt_dir_alm->get_result();
                if ($res_dir_alm && $res_dir_alm->num_rows) {
                $dir_alm = $res_dir_alm->fetch_assoc();
                }
            }
            }

            if (!empty($venta['id_direc_cliente'])) {
            $sql_dir_cli = "SELECT calle, numext, numint, colonia, estado, c_postal FROM direcciones WHERE id_direc = ? LIMIT 1";
            $stmt_dir_cli = $conn_mysql->prepare($sql_dir_cli);
            if ($stmt_dir_cli) {
                $stmt_dir_cli->bind_param('i', $venta['id_direc_cliente']);
                $stmt_dir_cli->execute();
                $res_dir_cli = $stmt_dir_cli->get_result();
                if ($res_dir_cli && $res_dir_cli->num_rows) {
                $dir_cli = $res_dir_cli->fetch_assoc();
                }
            }
            }

            function formato_direccion($d) {
            if (empty($d)) return '<span class="text-muted small">Sin dirección registrada</span>';
            $parts = [];
            if (!empty($d['calle'])) $parts[] = htmlspecialchars($d['calle']);
            $num = '';
            if (!empty($d['numext'])) $num .= 'No. Ext ' . htmlspecialchars($d['numext']);
            if (!empty($d['numint'])) $num .= ($num ? ' / ' : '') . 'Int ' . htmlspecialchars($d['numint']);
            if ($num) $parts[] = $num;
            if (!empty($d['colonia'])) $parts[] = 'Col. ' . htmlspecialchars($d['colonia']);
            if (!empty($d['estado'])) $parts[] = htmlspecialchars($d['estado']);
            if (!empty($d['c_postal'])) $parts[] = 'CP ' . htmlspecialchars($d['c_postal']);
            if (!empty($d['referencia'])) $parts[] = '<small class="text-muted">Ref: ' . htmlspecialchars($d['referencia']) . '</small>';
            return implode('<br>', $parts);
            }
            ?>
            <div class="card border-0 shadow h-100">
            <div class="card-header bg-transparent border-bottom py-2">
                <h5 class="mb-0">
                <i class="bi bi-arrow-left-right text-info me-2"></i>Origen y Destino
                </h5>
            </div>
            <div class="card-body p-3">
                <!-- Origen -->
                <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                    <i class="bi bi-shop text-primary fs-4"></i>
                    </div>
                    <div>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($venta['nombre_almacen']) ?></h6>
                    <p class="text-muted small mb-0">Código: <?= htmlspecialchars($venta['cod_almacen']) ?></p>
                    </div>
                </div>

                <div class="ps-4">
                    <div class="border-start border-3 border-primary ps-3 small">
                    <small class="text-muted d-block">Bodega de Salida</small>
                    <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_almacen']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_almacen']) ?></small>

                    <div class="mt-2 small">
                        <?= formato_direccion($dir_alm) ?>
                    </div>
                    </div>
                </div>
                </div>

                <hr class="my-3">

                <!-- Destino -->
                <div>
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                    <i class="bi bi-person text-success fs-4"></i>
                    </div>
                    <div>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($venta['nombre_cliente']) ?></h6>
                    <p class="text-muted small mb-0">Código: <?= htmlspecialchars($venta['cod_cliente']) ?></p>
                    </div>
                </div>

                <div class="ps-4">
                    <div class="border-start border-3 border-success ps-3 small">
                    <small class="text-muted d-block">Bodega de Destino</small>
                    <div class="fw-semibold"><?= htmlspecialchars($venta['cod_bodega_cliente']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($venta['nombre_bodega_cliente']) ?></small>

                    <div class="mt-2 small">
                        <?= formato_direccion($dir_cli) ?>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Columna 3: Información de flete -->
        <div class="col-lg-4">
            <?php if ($flete->num_rows > 0 && $flete_data): ?>
            <div class="card border-0 shadow h-100">
            <div class="card-header bg-transparent border-bottom py-2">
                <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-truck text-info me-2"></i>Información de Transporte
                </h5>
                <?php if (!empty($flete_data['fecha_actualizacion_formateada'])): ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-clock-history me-1"></i>
                    <?= htmlspecialchars($flete_data['fecha_actualizacion_formateada']) ?>
                </span>
                <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-3">
                <!-- Información básica del fletero -->
                <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                    <i class="bi bi-truck text-info fs-4"></i>
                    </div>
                    <div>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($venta['nombre_fletero']) ?></h6>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-upc-scan me-1"></i>
                        <?= htmlspecialchars($venta['placas_fletero']) ?>
                    </p>
                    </div>
                </div>
                
                <?php if ($factura_transportista_duplicada_info): ?>
                <div class="alert alert-warning alert-sm py-2 mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Factura duplicada:</strong> Ya existe en venta #<?= htmlspecialchars($factura_transportista_duplicada_info['folio']) ?>
                </div>
                <?php endif; ?>
                </div>
                
                <!-- Detalles del transporte -->
                <?php if (!empty($flete_data['tipo_camion']) || !empty($flete_data['nombre_chofer']) || 
                       !empty($flete_data['placas_unidad']) || !empty($flete_data['factura_transportista'])): ?>
                <div class="mb-3">
                <h6 class="fw-bold mb-2 text-info">
                    <i class="bi bi-truck-front me-2"></i>Detalles del Transporte
                </h6>
                <div class="row g-2">
                    <?php if (!empty($flete_data['tipo_camion'])): ?>
                    <div class="col-6">
                    <small class="text-muted d-block">Tipo de unidad</small>
                    <strong><?= htmlspecialchars($flete_data['tipo_camion']) ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($flete_data['nombre_chofer'])): ?>
                    <div class="col-6">
                    <small class="text-muted d-block">Chofer</small>
                    <strong><?= htmlspecialchars($flete_data['nombre_chofer']) ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($flete_data['placas_unidad'])): ?>
                    <div class="col-6">
                    <small class="text-muted d-block">Placas unidad</small>
                    <strong><?= htmlspecialchars($flete_data['placas_unidad']) ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($flete_data['factura_transportista'])): ?>
                    <div class="col-6">
                    <small class="text-muted d-block">Factura transportista</small>
                    <div class="d-flex align-items-center">
                        <?php
                        if (!empty($flete_data['doc_factura_ven']) || !empty($flete_data['com_factura_ven'])){
                        ?>
                        <button type="button" class="btn btn-info btn-sm rounded-4 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-file-earmark-pdf"></i> <?= $flete_data['factura_transportista'] ?>
                            </button>
                            <ul class="dropdown-menu">
                              <?php if (!empty($flete_data['doc_factura_ven'])): ?>
                                <li><a class="dropdown-item" href="<?= $invoiceLK . $flete_data['doc_factura_ven'] ?>.pdf" target="_blank">Ver Factura de Venta</a></li>
                              <?php endif; ?>
                              <?php if (!empty($flete_data['com_factura_ven'])): ?>
                                <li><a class="dropdown-item" href="<?= $invoiceLK . $flete_data['com_factura_ven'] ?>.pdf" target="_blank">Ver Comprobante de Venta</a></li>
                              <?php endif; ?>
                            </ul>
                        <?php
                        }else{
                        ?>
                        <strong><?= htmlspecialchars($flete_data['factura_transportista']) ?></strong>
                        <?php
                        }
                        ?>

                        <?php if ($factura_transportista_duplicada_info): ?>
                        <span class="badge bg-warning bg-opacity-20 text-warning border border-warning ms-2">
                        <i class="bi bi-exclamation-triangle"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($flete_data['folio_CR_venta'])): ?>
                    <div class="col-6">
                    <small class="text-muted d-block">Folio CR Venta</small>
                    <!-- Mostrar el folio completo con formato y link para abrir en otra pagina el cr -->
                    <a href="<?= $link.urlencode($flete_data['alias_CR_venta']).'-' . $flete_data['folio_CR_venta'] ?>" target="_blank" class="btn btn-sm btn-info rounded-5">
                        <i class="bi bi-file-earmark-text me-1"></i>
                        <?= htmlspecialchars($flete_data['alias_CR_venta']) ?> - <?= htmlspecialchars($flete_data['folio_CR_venta']) ?>
                    </a>
                    </div>
                    <?php endif; ?>
                </div>
                </div>
                <?php endif; ?>
                
                <!-- Información del flete -->
                <div class="border rounded p-3 bg-info bg-opacity-10 mb-3">
                <div class="row g-2">
                    <div class="col-6">
                    <small class="text-muted d-block">Tipo de flete</small>
                    <span class="badge bg-info bg-opacity-25 text-info"><?= htmlspecialchars($flete_data['tipo_flete']) ?></span>
                    </div>
                    <div class="col-6">
                    <small class="text-muted d-block">Costo total</small>
                    <strong class="text-info">$<?= number_format($total_flete, 2) ?></strong>
                    </div>
                </div>
                </div>

                <!-- Desglose Fiscal Flete -->
                <?php if (!empty($flete_data['impuestoTraslado_flete'])): ?>
                <div class="border rounded p-3 bg-body-tertiary mb-3">
                <h6 class="text-muted mb-3 d-flex align-items-center">
                    <i class="bi bi-receipt text-secondary me-2"></i> Desglose Fiscal Flete
                </h6>

                <div class="mb-2 pb-2 border-bottom border-opacity-50">
                    <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Subtotal</span>
                    <span class="fw-semibold text-secondary">$<?= number_format($flete_data['subtotal_flete'], 2) ?></span>
                    </div>
                </div>

                <div class="mb-2 pb-2 border-bottom border-opacity-50">
                    <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle text-success fs-6"></i>
                        <span class="text-muted small">IVA Traslado</span>
                    </div>
                    <span class="fw-semibold text-success">+$<?= number_format($flete_data['impuestoTraslado_flete'], 2) ?></span>
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom border-opacity-50">
                    <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-dash-circle text-danger fs-6"></i>
                        <span class="text-muted small">IVA Retenido</span>
                    </div>
                    <span class="fw-semibold text-danger">-$<?= number_format($flete_data['impuestoRetenido_flete'], 2) ?></span>
                    </div>
                </div>

                <div class="p-3 rounded-3 border-2" style="border-color: rgba(52,152,219,0.3); background: rgba(52,152,219,0.08);">
                    <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total Neto Flete</span>
                    <span class="fw-bold text-info fs-5">$<?= number_format($flete_data['total_flete'], 2) ?></span>
                    </div>
                </div>

                <!-- Alerta si el costo total no coincide con el subtotal -->
                <?php 
                $subtotal = $flete_data['subtotal_flete'] ?? 0;
                $total = $total_flete ?? 0;
                
                if (!empty($subtotal) && !empty($total) && abs($subtotal - $total) > 0.01): 
                ?>
                <div class="alert alert-warning alert-sm mt-3 py-2 mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Discrepancia detectada:</strong> El subtotal ($<?= number_format($subtotal, 2) ?>) no coincide con el total ($<?= number_format($total, 2) ?>).
                </div>
                <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Información del Ticket de Báscula -->
                    <?php if (!empty($flete_data['folio_ticket_bascula']) || !empty($flete_data['archivo_ticket'])): 
                    $info_ticket = obtenerInfoTicket($flete_data['archivo_ticket'] ?? '');
                    $tiene_ticket = !empty($info_ticket);
                    ?>
                            <div class="col-12 mt-3 pt-2 border-top">
                    <h6 class="small text-muted mb-2">
                        <i class="bi bi-scale me-1"></i>Ticket de Báscula
                    </h6>
                    
                    <div class="row g-2 align-items-center">
                        <?php if (!empty($flete_data['folio_ticket_bascula'])): ?>
                        <div class="col-6">
                        <small class="text-muted d-block">Folio</small>
                        <strong><?= htmlspecialchars($flete_data['folio_ticket_bascula']) ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($flete_data['archivo_ticket'])): ?>
                        <div class="col-6">
                        <small class="text-muted d-block">Archivo</small>
                        <?php if ($tiene_ticket): 
                        ?>
                        <button type="button" class="btn btn-sm btn-outline-info d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#ModalTicket">
                            Ver Ticket
                        </button>

                        <?php else: ?>
                        <span class="text-danger small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Archivo no encontrado
                        </span>
                        <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($flete_data['fecha_subida_ticket_formateada'])): ?>
                        <div class="col-12">
                        <small class="text-muted d-block">Subido</small>
                        <span class="small"><?= htmlspecialchars($flete_data['fecha_subida_ticket_formateada']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($flete_data['tipo_flete'] == 'Por tonelada'): ?>
                            <div class="mt-2">
                    <small class="text-muted d-block">Precio por tonelada</small>
                    <strong>$<?= number_format($flete_data['precio_flete'], 2) ?></strong>
                    </div>
                    <?php endif; ?>

                    <!-- Botón para editar -->
                            <div class="mt-3 pt-3 border-top text-center">
                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalFletero">
                        <i class="bi bi-pencil-square me-2"></i>Editar Información de Transporte
                    </button>
                    </div>
                </div>
                </div>
            <?php else: ?>
            <div class="card border-0 shadow h-100">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5">
                <div class="bg-info bg-opacity-10 rounded-3 p-4 mb-3">
                <i class="bi bi-truck text-info fs-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Sin Información de Transporte</h5>
                <p class="text-muted mb-4">No hay datos de flete registrados para esta venta.</p>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFletero">
                <i class="bi bi-plus-circle me-2"></i>Agregar Datos de Transporte
                </button>
            </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    

    <!-- Resumen financiero al final -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-calculator text-warning me-2"></i>Resumen Financiero
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- Resumen ejecutivo -->
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <div class="brounded-3 p-3 border border-subtle">
                                <div class="row g-3 text-center">
                                    <div class="col-md-3">
                                        <h6 class="text-muted small mb-1">
                                            <i class="bi bi-arrow-down-short text-danger"></i> Costo Total
                                        </h6>
                                        <h4 class="fw-bold text-danger mb-0">
                                            $<?= number_format($total_flete, 2) ?>
                                        </h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted small mb-1">
                                            <i class="bi bi-arrow-up-short text-success"></i> Ingreso Total
                                        </h6>
                                        <h4 class="fw-bold text-success mb-0">
                                            $<?= number_format($total_venta, 2) ?>
                                        </h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted small mb-1">
                                            <i class="bi bi-dash-circle"></i> Balance
                                        </h6>
                                        <h4 class="fw-bold <?= $total_general >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                            $<?= number_format($total_general, 2) ?>
                                        </h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted small mb-1">
                                            <i class="bi bi-pie-chart"></i> Rentabilidad
                                        </h6>
                                        <h4 class="fw-bold text-primary mb-0">
                                            <?php if ($total_venta > 0): 
                                                echo number_format(($total_general / $total_venta) * 100, 1);
                                            else:
                                                echo '0.0';
                                            endif; ?>%
                                        </h4>
                                    </div>
                                </div>
                            </div>
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
                    <div class="card border-0 bg-body-tertiary mb-3">
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
 <div class="modal fade" id="ModalTicket" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Ticket de báscula <?= $flete_data['folio_ticket_bascula'] ?></h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?php
            $nombre_archivo = $flete_data['archivo_ticket'];
            // Usando pathinfo (más robusto y recomendado)
            $extension_pathinfo = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            if ($extension_pathinfo == 'pdf') {
                ?>
                <iframe src="uploads/ticket/<?= $flete_data['archivo_ticket'] ?>" 
                        style="width:100%; height:500px;" frameborder="0">
                        </iframe>

                <?php
            } else {
                ?>
                <img src="uploads/ticket/<?= $flete_data['archivo_ticket'] ?>" alt="Ticket de Báscula" class="img-fluid">  
                <?php
            }
            ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
        </div>
    </div>
</div>
<!-- Modal para editar datos del fletero -->
<?php if ($flete->num_rows > 0): ?>
<div class="modal fade" id="modalFletero" tabindex="-1" aria-labelledby="modalFleteroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalFleteroLabel">
                    <i class="bi bi-truck me-2"></i>Datos del Fletero
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Pestañas para separar las secciones -->
            <div class="modal-body p-0">
                <ul class="nav nav-tabs" id="fleteroTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" 
                                data-bs-target="#datos" type="button" role="tab">
                            <i class="bi bi-truck me-1"></i> Datos del Transporte
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ticket-tab" data-bs-toggle="tab" 
                                data-bs-target="#ticket" type="button" role="tab">
                            <i class="bi bi-scale me-1"></i> Ticket de Báscula
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content p-3" id="fleteroTabContent">
                    <!-- Pestaña 1: Datos del transporte -->
                    <div class="tab-pane fade show active" id="datos" role="tabpanel">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                            
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
                                        <div class="form-text">Opcional - Número de factura del transportista</div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($flete_data['fecha_actualizacion_formateada'])): ?>
                            <div class="alert alert-secondary">
                                <i class="bi bi-clock-history me-2"></i>
                                Última actualización: <?= htmlspecialchars($flete_data['fecha_actualizacion_formateada']) ?>
                            </div>
                            <?php endif; ?>
                            
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
                    
                    <!-- Pestaña 2: Ticket de báscula -->
                    <div class="tab-pane fade" id="ticket" role="tabpanel">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                            
                            <!-- Información actual del ticket -->
                            <?php if (!empty($flete_data['folio_ticket_bascula']) || !empty($flete_data['archivo_ticket'])): ?>
                            <div class="alert alert-success">
                                <h6 class="alert-heading">
                                    <i class="bi bi-check-circle me-2"></i>Ticket registrado
                                </h6>
                                
                                <?php if (!empty($flete_data['folio_ticket_bascula'])): ?>
                                <p class="mb-1">
                                    <strong>Folio:</strong> <?= htmlspecialchars($flete_data['folio_ticket_bascula']) ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($flete_data['archivo_ticket'])): 
                                    $info_ticket = obtenerInfoTicket($flete_data['archivo_ticket']);
                                ?>
                                <p class="mb-1">
                                    <strong>Archivo:</strong> 
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#ModalTicket">
                                        <i class="bi bi-file-earmark-image me-1"></i>
                                         <?= htmlspecialchars($info_ticket['nombre'] ?? $flete_data['archivo_ticket']) ?>
                                    </button>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($flete_data['fecha_subida_ticket_formateada'])): ?>
                                <p class="mb-0">
                                    <strong>Subido:</strong> <?= htmlspecialchars($flete_data['fecha_subida_ticket_formateada']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No hay ticket de báscula registrado.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Formulario para subir/actualizar ticket -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="folio_ticket" class="form-label">
                                            <i class="bi bi-ticket-detailed me-1"></i>Folio del Ticket *
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="folio_ticket" 
                                               name="folio_ticket" 
                                               value="<?= htmlspecialchars($flete_data['folio_ticket_bascula'] ?? '') ?>"
                                               placeholder="Ej: TKT-001234"
                                               required>
                                        <div class="form-text">Número de folio del ticket de báscula</div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="archivo_ticket" class="form-label">
                                            <i class="bi bi-file-arrow-up me-1"></i>Archivo del Ticket
                                        </label>
                                        <input type="file" 
                                               class="form-control" 
                                               id="archivo_ticket" 
                                               name="archivo_ticket"
                                               accept=".jpg,.jpeg,.png,.gif,.bmp,.tiff">
                                        <div class="form-text">
                                            Sube el ticket fotografía (JPG, PNG, GIF, BMP, TIFF) - Máximo 5MB
                                        </div>
                                        
                                        <!-- Información del archivo actual -->
                                        <?php if (!empty($flete_data['archivo_ticket'])): 
                                            $info_ticket = obtenerInfoTicket($flete_data['archivo_ticket']);
                                        ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Archivo actual: 
                                                <strong><?= $flete_data['archivo_ticket'] ?></strong>
                                                <?php if (!empty($info_ticket)): ?>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Si subes un nuevo archivo, el anterior será reemplazado.
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Importante:</strong> El folio del ticket debe ser único. 
                                Verifica que no esté registrado en otra venta.
                            </div>
                            
                            <div class="modal-footer">
                                <!-- Botón para eliminar ticket (solo si existe) -->
                                <?php if (!empty($flete_data['folio_ticket_bascula']) || !empty($flete_data['archivo_ticket'])): ?>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEliminarTicket">
                                    <i class="bi bi-trash me-2"></i>Eliminar Ticket
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </button>
                                <button type="submit" name="subir_ticket" class="btn btn-success">
                                    <i class="bi bi-cloud-upload me-2"></i>
                                    <?= empty($flete_data['folio_ticket_bascula']) ? 'Subir Ticket' : 'Actualizar Ticket' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar ticket -->
<div class="modal fade" id="modalEliminarTicket" tabindex="-1" aria-labelledby="modalEliminarTicketLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarTicketLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar el ticket de báscula?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-1">
                        <li>El folio del ticket</li>
                        <li>El archivo subido</li>
                        <li>La fecha de subida</li>
                    </ul>
                </div>
                <p class="mb-0"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" name="eliminar_ticket" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
// Script para mejor experiencia de usuario
document.addEventListener('DOMContentLoaded', function() {

    const archivoTicketInput = document.getElementById('archivo_ticket');
    if (archivoTicketInput) {
        archivoTicketInput.addEventListener('change', function() {
            const archivo = this.files[0];
            if (archivo) {
                const tamanoMaximo = 5 * 1024 * 1024; // 5MB
                if (archivo.size > tamanoMaximo) {
                    alert('El archivo es demasiado grande. El tamaño máximo es 5MB.');
                    this.value = '';
                }
                
                // Mostrar nombre del archivo
                const nombreArchivo = archivo.name;
                const extension = nombreArchivo.split('.').pop().toLowerCase();
                const extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'];
                
                if (!extensionesPermitidas.includes(extension)) {
                    alert('Tipo de archivo no permitido. Solo se aceptan: PDF, JPG, PNG, GIF, BMP, TIFF.');
                    this.value = '';
                }
            }
        });
    }
    
    // Validar folio del ticket
    const folioTicketInput = document.getElementById('folio_ticket');
    if (folioTicketInput) {
        folioTicketInput.addEventListener('blur', function() {
            this.value = this.value.trim().toUpperCase();
        });
    }
    
    // Prevenir envío duplicado
    const formularioTicket = document.querySelector('form[name="subir_ticket"]');
    if (formularioTicket) {
        formularioTicket.addEventListener('submit', function(e) {
            const botonSubmit = this.querySelector('button[name="subir_ticket"]');
            if (botonSubmit) {
                botonSubmit.disabled = true;
                botonSubmit.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Subiendo...';
            }
        });
    }
    
    // Auto-focus en la pestaña activa
    const modalFletero = document.getElementById('modalFletero');
    if (modalFletero) {
        modalFletero.addEventListener('shown.bs.modal', function() {
            const pestañaActiva = this.querySelector('.nav-link.active');
            if (pestañaActiva && pestañaActiva.id === 'ticket-tab') {
                const folioInput = document.getElementById('folio_ticket');
                if (folioInput) {
                    setTimeout(() => folioInput.focus(), 100);
                }
            }
        });
    }
    
    // Confirmar antes de eliminar
    const botonEliminarTicket = document.querySelector('button[name="eliminar_ticket"]');
    if (botonEliminarTicket) {
        botonEliminarTicket.addEventListener('click', function(e) {
            if (!confirm('¿Estás completamente seguro de eliminar el ticket? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    }
    
    // Vista previa de imagen (opcional)
    const previsualizarImagen = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            const extension = input.files[0].name.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'].includes(extension)) {
                reader.onload = function(e) {
                    // Crear o actualizar vista previa
                    let preview = document.getElementById('ticket-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'ticket-preview';
                        preview.className = 'ticket-preview mt-2';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                        <div class="mt-1 small text-muted">
                            Vista previa - ${input.files[0].name}
                        </div>
                    `;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    };
    
    // Activar vista previa si se desea
    if (archivoTicketInput) {
        archivoTicketInput.addEventListener('change', function() {
            previsualizarImagen(this);
        });
    }
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