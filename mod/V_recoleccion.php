<style>
/* Mejoras responsivas para alerts */
.alert .btn-sm {
    white-space: nowrap;
    min-width: fit-content;
}

/* Asegurar que el texto se ajuste bien en móviles */
.alert-heading {
    font-size: 0.95rem;
}

/* Mejor espaciado en móviles */
@media (max-width: 768px) {
    .alert .flex-grow-1 {
        margin-right: 0 !important;
    }
    
    .alert .d-flex.flex-column .mt-2 {
        margin-top: 0.75rem !important;
    }
    
    .alert .btn {
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
    }
}

/* Para pantallas muy pequeñas */
@media (max-width: 576px) {
    .alert .d-flex.flex-column .gap-2 {
        gap: 0.5rem !important;
    }
    
    .alert .small {
        font-size: 0.775rem;
    }
}
</style>
<?php
// V_recoleccion.php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ?p=recoleccion");
    exit;
}

$id_recoleccion = $_GET['id'];

$query = "SELECT 
r.*,
r.alias_inv_fle AS alias_fletero,
r.folio_inv_fle AS folio_fletero,
r.alias_inv_pro AS alias_proveedor,
r.folio_inv_pro AS folio_proveedor,
r.Vfac_com AS factura_complemento, -- Nuevo: complemento
r.Vfech_com AS fecha_complemento, -- Nuevo: complemento
r.Vob_com AS observacion_complemento, -- Nuevo: complemento
r.Vcomp_com AS comprobar_complemento, -- Nuevo: complemento
r.ex AS recoleccion_excel,
r.id_prov AS id_proveedor,
r.id_transp AS id_transportista,
r.tipo_fle,  -- NUEVO: Tipo de camión
r.nom_fle,   -- NUEVO: Nombre del chofer
r.placas_fle, -- NUEVO: Placas de la unidad
r.remixtac,  -- NUEVO: Remisión especial de Ixtac
p.rs AS razon_social_proveedor,
p.cod AS cod_proveedor,
dp.noma AS nombre_bodega_proveedor,
dp.cod_al AS cod_bodega_proveedor,
dp.email AS correo_proveedor,
t.razon_so AS razon_social_fletero,
t.placas AS placas_fletero,
t.correo AS correo_fletero,
c.nombre AS nombre_cliente,
c.cod AS cod_cliente,
c.fac_rem AS factura_remision, -- Nuevo: factura o recoleccion
dc.noma AS nombre_bodega_cliente,
dc.cod_al AS cod_bodega_cliente,
pr.nom_pro AS nombre_producto,
pr.cod AS cod_producto,
z.cod AS cod_zona,
z.nom AS nombre_zona,
z.PLANTA AS planta_zona,
pf.precio AS precio_flete,
pf.tipo AS tipo_flete,  -- NUEVO: Tipo de flete (FT o FV)
pf.conmin AS peso_minimo,  -- NUEVO: Peso mínimo en toneladas
pc.precio AS precio_compra,
pv.precio AS precio_venta,
u.nombre AS nombre_usuario
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
    alert("Recolección no encontrada", 2, "recoleccion");
    exit;
}

$recoleccion = $result->fetch_assoc();

// NUEVA FUNCIÓN PARA CALCULAR EL PRECIO REAL DEL FLETE
function calcularPrecioFleteReal($recoleccion) {
    $precio_base = $recoleccion['precio_flete'];
    $tipo_flete = $recoleccion['tipo_flete'];
    $peso_minimo = $recoleccion['peso_minimo'];
    $peso_flete_kg = $recoleccion['peso_fle']; // Peso en kilogramos
    $peso_proveedor_kg = $recoleccion['peso_prov']; // Peso del proveedor en kilogramos
    
    // MODIFICACIÓN: Si el precio del flete es 0, usar el peso del proveedor
    if ($precio_base == 0 && !empty($peso_proveedor_kg) && $peso_proveedor_kg > 0) {
        $peso_flete_kg = $peso_proveedor_kg;
    }
    
    // Si no hay peso de flete registrado, retornar el precio base
    if (empty($peso_flete_kg) || $peso_flete_kg <= 0) {
        return [
            'precio_final' => $precio_base,
            'tipo_calculo' => 'Precio base (sin peso)',
            'peso_utilizado' => 0,
            'es_minimo' => false,
            'peso_usado' => 'flete' // Indica qué peso se usó
        ];
    }
    
    // Convertir peso de kg a toneladas
    $peso_flete_ton = $peso_flete_kg / 1000;
    
    if ($tipo_flete == 'FV') {
        // Por viaje: el precio es fijo independientemente del peso
        return [
            'precio_final' => $precio_base,
            'tipo_calculo' => 'Por viaje (precio fijo)',
            'peso_utilizado' => $peso_flete_ton,
            'es_minimo' => false,
            'peso_usado' => ($precio_base == 0 && !empty($peso_proveedor_kg) && $peso_proveedor_kg > 0) ? 'proveedor' : 'flete'
        ];
    } elseif ($tipo_flete == 'FT') {
        // Por tonelada: aplicar reglas de peso mínimo
        if ($peso_minimo > 0) {
            if ($peso_flete_ton <= $peso_minimo) {
                // Si el peso es menor o igual al mínimo, usar el mínimo
                $precio_final = $precio_base * $peso_minimo;
                return [
                    'precio_final' => $precio_final,
                    'tipo_calculo' => 'Por tonelada (aplicado peso mínimo)',
                    'peso_utilizado' => $peso_minimo,
                    'es_minimo' => true,
                    'peso_usado' => ($precio_base == 0 && !empty($peso_proveedor_kg) && $peso_proveedor_kg > 0) ? 'proveedor' : 'flete'
                ];
            } else {
                // Si el peso es mayor al mínimo, usar el peso real
                $precio_final = $precio_base * $peso_flete_ton;
                return [
                    'precio_final' => $precio_final,
                    'tipo_calculo' => 'Por tonelada (peso real)',
                    'peso_utilizado' => $peso_flete_ton,
                    'es_minimo' => false,
                    'peso_usado' => ($precio_base == 0 && !empty($peso_proveedor_kg) && $peso_proveedor_kg > 0) ? 'proveedor' : 'flete'
                ];
            }
        } else {
            // Si no hay peso mínimo configurado, usar peso real
            $precio_final = $precio_base * $peso_flete_ton;
            return [
                'precio_final' => $precio_final,
                'tipo_calculo' => 'Por tonelada (sin peso mínimo)',
                'peso_utilizado' => $peso_flete_ton,
                'es_minimo' => false,
                'peso_usado' => ($precio_base == 0 && !empty($peso_proveedor_kg) && $peso_proveedor_kg > 0) ? 'proveedor' : 'flete'
            ];
        }
    }
    
    // Por defecto, retornar precio base
    return [
        'precio_final' => $precio_base,
        'tipo_calculo' => 'Tipo desconocido',
        'peso_utilizado' => 0,
        'es_minimo' => false,
        'peso_usado' => 'flete'
    ];
}

// CALCULAR EL PRECIO REAL DEL FLETE
$calculo_flete = calcularPrecioFleteReal($recoleccion);
$precio_flete_real = $calculo_flete['precio_final'];

// NUEVA COMPARACIÓN ENTRE PRECIO CALCULADO Y FACTURA
$diferencia_flete = 0;
$hay_diferencia = false;
$porcentaje_diferencia = 0;

if ($recoleccion['precio_flete'] == 0 && !empty($recoleccion['peso_prov']) && $recoleccion['peso_prov'] > 0) {
    // Si el precio del flete es 0, usar el peso del proveedor para el cálculo de venta
    $peso_para_venta = $recoleccion['peso_prov'];
} else {
    // Caso normal, usar el peso del flete
    $peso_para_venta = $recoleccion['peso_fle'];
}

// NUEVA COMPARACIÓN ENTRE PRECIO CALCULADO Y FACTURA
$diferencia_flete = 0;
$hay_diferencia = false;
$porcentaje_diferencia = 0;

if (!empty($recoleccion['sub_tot_inv']) && $recoleccion['sub_tot_inv'] > 0) {
    $diferencia_flete = abs($precio_flete_real - $recoleccion['sub_tot_inv']);
    $hay_diferencia = $diferencia_flete > 0.01; // Tolerancia de 1 centavo
    
    if ($precio_flete_real > 0) {
        $porcentaje_diferencia = ($diferencia_flete / $precio_flete_real) * 100;
    }
}
//Calcular precio por kilo
$Precio_x_kilos = 0;

if ($recoleccion['peso_fle'] > 0) {
    $Precio_x_kilos = $precio_flete_real / $recoleccion['peso_fle'];
}
// NUEVO: Verificar si existe complemento de factura en base de datos
$complemento_existe = !empty($recoleccion['factura_complemento']);

// OPTIMIZACIÓN: Solo buscar PDF del complemento si existe en base de datos
$existePDFComplemento = false;
$url_complemento = '';

// codigo

$folio_completo = $recoleccion['cod_zona'] . "-" . date('ym', strtotime($recoleccion['fecha_r'])) . str_pad($recoleccion['folio'], 4, '0', STR_PAD_LEFT);
$fecha_recoleccion = date('d/m/Y', strtotime($recoleccion['fecha_r']));
$fecha_factura = date('d/m/Y', strtotime($recoleccion['fecha_v']));
$fecha_complemento = !empty($recoleccion['fecha_complemento']) ? date('d/m/Y', strtotime($recoleccion['fecha_complemento'])) : 'N/A';

// Verificar si los datos están completos
$remision_completa = !empty($recoleccion['remision']);
$remixtac_completa = !empty($recoleccion['remixtac']);
$peso_proveedor_completo = !empty($recoleccion['peso_prov']);
$factura_flete_completa = !empty($recoleccion['factura_fle']);
$peso_flete_completo = !empty($recoleccion['peso_fle']);

// Verificar si al menos una remisión está completa
$alguna_remision_completa = $remision_completa || $remixtac_completa;

// link para avance
$mesesA = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
$numero_mesA = date('m', strtotime($recoleccion['fecha_v'])) - 1;
$anoA = date('Y', strtotime($recoleccion['fecha_v']));

// Seleccionar base de URL según si la factura pertenece al año actual o es anterior
$base_actual = 'https://glama.esasacloud.com/doctos/';
$base_antiguo = 'https://olddocs.esasacloud.com/olddocs-01/cpu27/';
$base = ($anoA != date('Y')) ? $base_antiguo : $base_actual;

if ($recoleccion['factura_remision'] == 'FAC') {
    $url = $base . $recoleccion['planta_zona'] . '/FACTURAS/' . $anoA . '/' . $mesesA[$numero_mesA] . '/SIGN_' . $recoleccion['factura_v'] . '.pdf';
} else {
    $url = $base . $recoleccion['planta_zona'] . '/REMISIONES/' . $anoA . '/' . $mesesA[$numero_mesA] . '/SIGN_' . $recoleccion['factura_v'] . '.pdf';
}

// Función para validar si existe el archivo remoto
function urlExists($url) {
    if (empty($url)) return false;
    $ch = curl_init($url);
    
    // Configurar cURL para ser más rápido
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,          // Solo HEAD request, más rápido
        CURLOPT_FOLLOWLOCATION => true,  // Seguir redirecciones
        CURLOPT_TIMEOUT => 5,           // Timeout de 5 segundos
        CURLOPT_CONNECTTIMEOUT => 3,    // Timeout de conexión de 3 segundos
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FacturaChecker/1.0)',
        CURLOPT_SSL_VERIFYPEER => false, // Para evitar problemas SSL
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode === 200);
}

$existePDF = urlExists($url);

if ($recoleccion['factus_v_corr'] == '0') {
    if ($existePDF) {
        $conn_mysql->query("UPDATE recoleccion SET factus_v_corr = '1' WHERE id_recol = '$id_recoleccion'");
    }
}

// NUEVO: Procesar el formulario del complemento de factura
if (isset($_POST['guardar_complemento'])) {
    $id_recoleccion = $_POST['id_recoleccion'];
    $factura_complemento = $_POST['factura_complemento'];
    $fecha_complemento = $_POST['fecha_complemento'];
    $observacion_complemento = $_POST['observacion_complemento'];

    // Validar que la factura esté presente
    if (empty($factura_complemento)) {
        alert("Debe ingresar el número de factura complemento", 2, "V_recoleccion&id=".$id_recoleccion);
        exit;
    }

    // Actualizar los datos del complemento
    $conn_mysql->query("UPDATE recoleccion SET 
        Vfac_com = '$factura_complemento', 
        Vfech_com = '$fecha_complemento', 
        Vob_com = '$observacion_complemento',
        Vcomp_com = '0' 
        WHERE id_recol = '$id_recoleccion'");
    
    alert("Complemento de factura agregado con éxito", 1, "V_recoleccion&id=".$id_recoleccion);
    logActivity('REC', 'Agregó complemento de factura a la recolección '. $id_recoleccion);
}

// NUEVO: Procesar la eliminación del complemento de factura
if (isset($_POST['eliminar_complemento'])) {
    $id_recoleccion = $_POST['id_recoleccion'];
    
    // Eliminar el complemento de factura
    $conn_mysql->query("UPDATE recoleccion SET 
        Vfac_com = NULL, 
        Vfech_com = NULL, 
        Vob_com = NULL,
        Vcomp_com = NULL 
        WHERE id_recol = '$id_recoleccion'");
    
    alert("Complemento de factura eliminado con éxito", 1, "V_recoleccion&id=".$id_recoleccion);
    logActivity('REC', 'Eliminó complemento de factura de la recolección '. $id_recoleccion);
}

// actualizar remisiones proveedor - VERSIÓN MEJORADA CON EDICIÓN Y REMIXTAC
if (isset($_POST['guardar_prov'])) {
    $id_recoleccion = $_POST['id_recoleccion'];
    $remision = $_POST['remision'];
    $remixtac = $_POST['remixtac'];
    $peso_proveedor = $_POST['peso_proveedor'];

    // Detectar si la remisión cambió para limpiar vínculos previos
    $remision_cambiada = ($remision !== $recoleccion['remision']);

    // Validar que al menos una remisión esté presente
    if (empty($remision) && empty($remixtac)) {
        alert("Debe ingresar al menos una remisión (normal o Ixtac)", 2, "V_recoleccion&id=".$id_recoleccion);
        exit;
    }

    // Verificar si la remisión normal ya existe en OTRA recolección (solo si es nueva o está cambiando)
    if (!empty($remision) && $remision != $recoleccion['remision']) {
        $BusReM0 = $conn_mysql->query("SELECT * FROM recoleccion WHERE remision = '$remision' AND id_prov = '".$recoleccion['id_proveedor']."' AND id_recol != '$id_recoleccion'");
        $BusReM1 = mysqli_fetch_array($BusReM0);
        if (!empty($BusReM1['id_recol'])) {
            alert("Número de remisión ".$remision." ya está ocupada en otra orden de recolección",2,"V_recoleccion&id=".$id_recoleccion);
            logActivity('REC', 'Intentó usar una remisión existente en el sistema en la recolección '. $id_recoleccion);
            exit;
        }
    }

    // Verificar si la remisión ixtac ya existe en OTRA recolección (solo si es nueva o está cambiando)
    if (!empty($remixtac) && $remixtac != $recoleccion['remixtac']) {
        $BusReM0 = $conn_mysql->query("SELECT * FROM recoleccion WHERE remixtac = '$remixtac' AND id_prov = '".$recoleccion['id_proveedor']."' AND id_recol != '$id_recoleccion'");
        $BusReM1 = mysqli_fetch_array($BusReM0);
        if (!empty($BusReM1['id_recol'])) {
            alert("Número de remisión Ixtac ".$remixtac." ya está ocupada en otra orden de recolección",2,"V_recoleccion&id=".$id_recoleccion);
            logActivity('REC', 'Intentó usar una remisión Ixtac existente en el sistema en la recolección '. $id_recoleccion);
            exit;
        }
    }

    // Actualizar los datos; si la remisión cambió, limpiar dependencias ligadas a la remisión anterior
    $campos_reset = '';
    if ($remision_cambiada) {
        $campos_reset = ", peso_conpro = NULL, doc_pro = NULL, d_f_p = NULL, factura_pro = NULL, alias_inv_pro = NULL, folio_inv_pro = NULL";
    }

    $conn_mysql->query("UPDATE recoleccion SET remision = '$remision', remixtac = '$remixtac', peso_prov = '$peso_proveedor'$campos_reset WHERE id_recol = '$id_recoleccion'");
    
    $conn_mysql->query("UPDATE recoleccion SET remi_compro = '0' WHERE id_recol = '$id_recoleccion'");
    
    $mensaje = "Datos del proveedor actualizados con éxito";
    if (!$alguna_remision_completa) {
        $mensaje = "Datos del proveedor agregados con éxito a la orden de recolección";
    }
    
    alert($mensaje, 1, "V_recoleccion&id=".$id_recoleccion);
    logActivity('REC', 'Actualizó las remisiones y el peso de la recolección '. $id_recoleccion);
}
// actualizar factura fletero - VERSIÓN COMPLETA CON TODAS LAS REGLAS
if (isset($_POST['guardar_fle'])) {
    $id_recoleccion = $_POST['id_recoleccion'];
    $factura_flete = trim($_POST['factura_flete']);
    $peso_flete = trim($_POST['peso_flete']);
    $tipo_camion = $_POST['tipo_camion'] ?? '';
    $nombre_chofer = $_POST['nombre_chofer'] ?? '';
    $placas_unidad = $_POST['placas_unidad'] ?? '';
    
    // Validaciones básicas
    if (empty($factura_flete)) {
        alert("La factura del flete es obligatoria", 2, "V_recoleccion&id=".$id_recoleccion);
        exit;
    }
    
    if (empty($peso_flete) || $peso_flete <= 0) {
        alert("El peso del flete es obligatorio y debe ser mayor a 0", 2, "V_recoleccion&id=".$id_recoleccion);
        exit;
    }
    
    // Obtener los datos actuales COMPLETOS
    $query_actual = "SELECT 
    r.factura_fle, 
    r.doc_fle, 
    r.d_f_f, 
    r.folio_inv_fle, 
    r.alias_inv_fle,
    r.peso_fle,
    r.tipo_fle,
    r.nom_fle,
    r.placas_fle,
    r.im_tras_inv,
    r.im_rete_inv,
    r.sub_tot_inv,
    r.total_inv,
    pf.precio AS precio_flete
    FROM recoleccion r 
    LEFT JOIN precios pf ON r.pre_flete = pf.id_precio 
    WHERE r.id_recol = ?";
    
    $stmt_actual = $conn_mysql->prepare($query_actual);
    $stmt_actual->bind_param("i", $id_recoleccion);
    $stmt_actual->execute();
    $result_actual = $stmt_actual->get_result();
    $datos_actuales = $result_actual->fetch_assoc();
    
    $factura_actual = $datos_actuales['factura_fle'];
    $peso_actual = $datos_actuales['peso_fle'];
    $folio_actual = $datos_actuales['folio_inv_fle'];
    $alias_actual = $datos_actuales['alias_inv_fle'];
    $doc_fle_actual = $datos_actuales['doc_fle'];
    $d_f_f_actual = $datos_actuales['d_f_f'];
    
    // Verificar si la factura nueva ya existe en OTRA recolección (solo si es diferente)
    if ($factura_flete != $factura_actual) {
        $BusFac0 = $conn_mysql->query("SELECT * FROM recoleccion WHERE factura_fle = '$factura_flete' AND id_transp = '".$recoleccion['id_transportista']."' AND id_recol != '$id_recoleccion'");
        $BusFac1 = mysqli_fetch_array($BusFac0);
        if (!empty($BusFac1['id_recol'])) {
            alert("La factura $factura_flete ya está registrada en otra orden de recolección", 2, "V_recoleccion&id=".$id_recoleccion);
            logActivity('REC', 'Intento usar una factura existente en otra recolección '. $id_recoleccion);
            exit;
        }
    }
    
    // Preparar la consulta UPDATE
    $update_fields = [];
    $update_fields[] = "factura_fle = '$factura_flete'";
    $update_fields[] = "peso_fle = '$peso_flete'";
    
    // Determinar si debemos resetear documentos
    $resetear_documentos = false;
    $resetear_contrarecibo = false;
    $resetear_facturacion = false;
    $razon_reset = '';
    
    // REGLA 1: Si la factura cambia, siempre resetear documentos de factura
    if ($factura_flete != $factura_actual) {
        $update_fields[] = "doc_fle = NULL";
        $update_fields[] = "d_f_f = NULL";
        $resetear_documentos = true;
        $resetear_facturacion = true; // Cambio de factura = resetear facturación
        $razon_reset .= 'Cambio de factura';
    }
    
    // REGLA 2: Si el peso cambia, también resetear documentos
    if ($peso_flete != $peso_actual) {
        if (!$resetear_documentos) {
            $update_fields[] = "doc_fle = NULL";
            $update_fields[] = "d_f_f = NULL";
            $resetear_documentos = true;
        }
        if (!$resetear_facturacion) {
            $resetear_facturacion = true; // Cambio de peso también resetea facturación
        }
        $razon_reset .= $razon_reset ? ', Cambio de peso' : 'Cambio de peso';
    }
    
    // REGLA 3: Si se resetean documentos O cambia la factura, resetear contra recibo (excepto si es N/A)
    if ($resetear_documentos || $factura_flete != $factura_actual) {
        // Solo resetear contra recibo si NO es N/A (precio_flete != 0)
        if ($datos_actuales['precio_flete'] != 0) {
            $update_fields[] = "folio_inv_fle = NULL";
            $update_fields[] = "alias_inv_fle = NULL";
            $resetear_contrarecibo = true;
        }
    }
    
    // REGLA 4: Resetear campos de facturación si hay cambios
    if ($resetear_facturacion) {
        $update_fields[] = "im_tras_inv = NULL";
        $update_fields[] = "im_rete_inv = NULL";
        $update_fields[] = "sub_tot_inv = NULL";
        $update_fields[] = "total_inv = NULL";
    }
    
    // Campos opcionales - actualizar solo si se proporcionaron
    if (!empty($tipo_camion)) {
        $update_fields[] = "tipo_fle = '$tipo_camion'";
    } elseif (!empty($datos_actuales['tipo_fle']) && $resetear_documentos) {
        // Si ya tenía tipo y se resetearon documentos, mantenerlo
        $update_fields[] = "tipo_fle = '" . $datos_actuales['tipo_fle'] . "'";
    }
    
    if (!empty($nombre_chofer)) {
        $update_fields[] = "nom_fle = '$nombre_chofer'";
    } elseif (!empty($datos_actuales['nom_fle']) && $resetear_documentos) {
        $update_fields[] = "nom_fle = '" . $datos_actuales['nom_fle'] . "'";
    }
    
    if (!empty($placas_unidad)) {
        $update_fields[] = "placas_fle = '$placas_unidad'";
    } elseif (!empty($datos_actuales['placas_fle']) && $resetear_documentos) {
        $update_fields[] = "placas_fle = '" . $datos_actuales['placas_fle'] . "'";
    }
    
    // Ejecutar actualización
    if (!empty($update_fields)) {
        $update_query = "UPDATE recoleccion SET " . implode(', ', $update_fields) . " WHERE id_recol = '$id_recoleccion'";
        
        if ($conn_mysql->query($update_query)) {
            // Construir mensaje según lo que pasó
            $mensaje = "Datos del fletero ";
            
            if (empty($factura_actual)) {
                // Caso 1: Primera vez que se ingresa
                $mensaje .= "guardados correctamente";
                logActivity('REC', 'Agregó factura de flete '.$factura_flete.' en recolección '.$id_recoleccion);
            } else {
                // Caso 2: Edición
                $mensaje .= "actualizados correctamente";
                
                if ($resetear_documentos) {
                    $mensaje .= ". Documentos de factura reseteados";
                    logActivity('REC', 'Editó factura flete en recolección '.$id_recoleccion.' - '.$razon_reset);
                    
                    if ($resetear_contrarecibo) {
                        $mensaje .= " y contra recibo eliminado";
                        if (!empty($folio_actual)) {
                            $mensaje .= " ($alias_actual-$folio_actual)";
                        }
                    }
                    
                    if ($resetear_facturacion) {
                        $mensaje .= ". Datos de facturación reseteados";
                    }
                }
            }
            
            alert($mensaje, 1, "V_recoleccion&id=".$id_recoleccion);
            
        } else {
            alert("Error al actualizar los datos: " . $conn_mysql->error, 2, "V_recoleccion&id=".$id_recoleccion);
        }
    }
}
// Después de obtener los datos de la recolección, agrega estas verificaciones:
$factura_flete_completa = !empty($recoleccion['factura_fle']);
$peso_flete_completo = !empty($recoleccion['peso_fle']);
$tipo_camion_completo = !empty($recoleccion['tipo_fle']);
$nombre_chofer_completo = !empty($recoleccion['nom_fle']);
$placas_unidad_completas = !empty($recoleccion['placas_fle']);

$datos_fletero_completos = $factura_flete_completa && $peso_flete_completo && $tipo_camion_completo && $nombre_chofer_completo && $placas_unidad_completas;

$datos_obligatorios_fletero = $factura_flete_completa;
$datos_opcionales_fletero = $tipo_camion_completo && $nombre_chofer_completo && $placas_unidad_completas && $peso_flete_completo;

// verificar si es una recoleccion de excel
$ExRecole = ($recoleccion['recoleccion_excel'] == 1) ? '<span class="badge text-bg-success" title="Recolección ingresada por excel"><i class="bi bi-filetype-csv"></i></span>' : '' ;

// =============================================
// CONFIGURACIÓN DE NOTIFICACIONES - ACTIVAR/DESACTIVAR
// =============================================
$MOSTRAR_NOTIFICACION_FACTURA_FLETE = false; // Cambiar a false para desactivar la notificación

// =============================================
// LÓGICA DE LA NOTIFICACIÓN - CORREGIDA
// =============================================
$mostrar_notificacion = false;
$mensaje_notificacion = "";
$tipo_notificacion = "warning";

if ($MOSTRAR_NOTIFICACION_FACTURA_FLETE) {
    // CORRECCIÓN: Verificar si falta la factura del flete O el peso del flete
    $faltan_datos_obligatorios = empty($recoleccion['factura_fle']) || empty($recoleccion['peso_fle']);
    $tiene_datos_obligatorios = !empty($recoleccion['factura_fle']) && !empty($recoleccion['peso_fle']);
    $faltan_datos_opcionales = !$tipo_camion_completo || !$nombre_chofer_completo || !$placas_unidad_completas;
    
    if ($faltan_datos_obligatorios || ($tiene_datos_obligatorios && $faltan_datos_opcionales)) {
        $mostrar_notificacion = true;
        
        if ($faltan_datos_obligatorios) {
            // Faltan datos obligatorios (factura y/o peso)
            $mensaje_notificacion = "No olvides agregar la factura de tu flete y el peso, esto para que tu recolección este completa. ";
            
            if (empty($recoleccion['factura_fle']) && empty($recoleccion['peso_fle'])) {
                $mensaje_notificacion .= "Faltan ambos datos obligatorios.";
            } elseif (empty($recoleccion['factura_fle'])) {
                $mensaje_notificacion .= "Falta la factura del flete.";
            } else {
                $mensaje_notificacion .= "Falta el peso del flete.";
            }
            
            $mensaje_notificacion .= " Haz clic en el botón <strong>'Agregar'</strong> en la sección de <strong>Factura Flete</strong> para completar esta información.";
            $tipo_notificacion = "warning";
            
        } elseif ($tiene_datos_obligatorios && $faltan_datos_opcionales) {
            // Tiene datos obligatorios pero faltan opcionales
            $mensaje_notificacion = "Ya tienes los datos principales del fletero (factura y peso), pero faltan los datos adicionales del transporte para completar toda la información.";
            $tipo_notificacion = "info";
        }
    }
}
// Verificar si debe mostrar la alerta de factura rechazada
$mostrar_alerta_rechazada = false;
$contador_rechazadas = 0;
$mensaje_rechazada = "";

// Condiciones para mostrar la alerta:
// 1. No hay factura de fletero actualmente
// 2. El contador FacFexis es mayor a 0
// 3. Solo aplica para factura del fletero
if (empty($recoleccion['factura_fle']) && 
    !empty($recoleccion['FacFexis']) && 
    $recoleccion['FacFexis'] > 0) {

    $mostrar_alerta_rechazada = true;
$contador_rechazadas = $recoleccion['FacFexis'];

    // Determinar el mensaje según el número de rechazos
if ($contador_rechazadas == 1) {
    $mensaje_rechazada = "La factura de flete anterior fue <strong>rechazada en el sistema INVOICE</strong> y ha sido eliminada automáticamente.";
} else {
    $mensaje_rechazada = "La factura de flete ha sido <strong>rechazada {$contador_rechazadas} veces en el sistema INVOICE</strong> y ha sido eliminada automáticamente.";
}

$mensaje_rechazada .= " Por favor, ingresa la factura correcta en la sección de <strong>Factura Flete</strong>.";
}


// =============================================
// DETECCIÓN DEL ESTADO DEL FLETERO
// =============================================

// Verificar si tiene factura de flete
$tiene_factura_flete = !empty($recoleccion['factura_fle']);

// Verificar si tiene peso de flete
$tiene_peso_flete = !empty($recoleccion['peso_fle']) && $recoleccion['peso_fle'] > 0;

// Verificar si tiene todos los datos adicionales
$tiene_datos_adicionales = !empty($recoleccion['tipo_fle']) && 
!empty($recoleccion['nom_fle']) && 
!empty($recoleccion['placas_fle']);

// Verificar si tiene documentos PDF
$tiene_documentos_flete = !empty($recoleccion['doc_fle']);

// Verificar si tiene contra recibo
$tiene_contra_recibo = !empty($recoleccion['folio_fletero']);

// Determinar el estado actual para mostrar el botón correcto
$estado_fletero = 'sin_datos';
if ($tiene_factura_flete && $tiene_peso_flete) {
    if ($tiene_datos_adicionales) {
        $estado_fletero = 'completo';
    } else {
        $estado_fletero = 'parcial';
    }
}

// Verificar si es N/A (factura vacía pero precio_flete = 0)
$es_na = (!$tiene_factura_flete && $recoleccion['precio_flete'] == 0);

$url_comprobacion_remision = '';
if ($recoleccion['remi_compro'] == '1') {
    $url_comprobacion_remision = "https://globaltycloud.com.mx:4013/externo/".$recoleccion['planta_zona']."/bascula/ticket.aspx?&rem=" . urlencode($recoleccion['remision']);
} else {
    $url_comprobacion_remision = '';
}
?>

<div class="container mt-2">
    <!-- ============================================= -->
    <!-- NOTIFICACIÓN DE FACTURA FLETE FALTANTE -->
    <!-- ============================================= -->
    <?php if ($mostrar_notificacion): ?>
        <div class="shadow-lg alert alert-<?= $tipo_notificacion ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <!-- Icono y contenido principal -->
                <div class="d-flex align-items-start w-100">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    </div>
                    <div class="flex-grow-1 me-2 me-md-3">
                        <h6 class="alert-heading mb-1">¡Atención!</h6>
                        <p class="mb-2 mb-md-0 small"><?= $mensaje_notificacion ?></p>
                    </div>
                </div>

                <!-- Botones - se reorganizan en móvil -->
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mt-2 mt-md-0 ms-md-auto">
                    <button type="button" class="btn btn-sm btn-<?= $tipo_notificacion ?> order-2 order-sm-1" 
                        data-bs-toggle="modal" data-bs-target="#modalFletero" <?= $perm['Recole_Editar'];?>>
                        <i class="bi bi-plus-circle me-1 d-none d-sm-inline"></i> 
                        <span class="d-inline d-sm-none">Agregar</span>
                        <span class="d-none d-sm-inline">Agregar ahora</span>
                    </button>
                    <button type="button" class="btn-close order-1 order-sm-2 align-self-start align-self-sm-center" 
                    data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mostrar_alerta_rechazada): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <!-- Icono y contenido principal -->
                <div class="d-flex align-items-start w-100">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-shield-exclamation text-danger me-2 fs-5"></i>
                    </div>
                    <div class="flex-grow-1 me-2 me-md-3">
                        <h6 class="alert-heading mb-1 text-danger">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                            Factura Rechazada
                        </h6>
                        <p class="mb-1 small"><?= $mensaje_rechazada ?></p>
                        <div class="mt-1">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Detección automática del sistema
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Botones - se reorganizan en móvil -->
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mt-2 mt-md-0 ms-md-auto">
                    <button type="button" class="btn btn-sm btn-danger order-2 order-sm-1" 
                    data-bs-toggle="modal" data-bs-target="#modalFletero" <?= $perm['Recole_Editar'];?>>
                    <i class="bi bi-pencil-square me-1 d-none d-sm-inline"></i> 
                    <span class="d-inline d-sm-none">Ingresar</span>
                    <span class="d-none d-sm-inline">Ingresar Factura</span>
                </button>
                <button type="button" class="btn-close order-1 order-sm-2 align-self-start align-self-sm-center" 
                data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="card border-1 shadow-lg">
    <div class="card-header encabezado-col text-white">
        <div class="d-flex justify-content-between align-items-center row">
            <div class="col-12 col-md-6">
                <h4 class="mb-1 fw-light">ORDEN DE RECOLECCIÓN</h4>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-white text-dark fs-6 py-2 px-3">
                        <i class="bi bi-clipboard2-data"></i> <?= $folio_completo ?>
                    </span>
                    <span class="small">
                        <i class="bi bi-calendar-event me-1"></i> <?= $fecha_recoleccion ?>
                    </span>
                </div>
            </div>
            <div class="d-flex gap-1 col-12 col-md-6 d-flex justify-content-end">
                <!-- Estado -->
                <span class="badge bg-<?= $recoleccion['status'] == 1 ? 'success' : 'danger' ?> py-2 px-3 fs-6 rounded-3">
                    <?= $recoleccion['status'] == 1 ? 'Activa' : 'Inactiva' ?>
                </span>

                <!-- Dropdown para acciones de recolección -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-teal text-white dropdown-toggle rounded-3" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-clipboard-data me-1"></i> Acciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="?p=recoleccion">
                                <i class="bi bi-list-ul me-2"></i>Ver Todas las Recolecciones
                            </a>
                        </li>
                        <li>
                            <a <?= $perm['Recole_Crear'];?> class="dropdown-item" href="?p=N_recoleccion">
                                <i class="bi bi-plus-circle me-2"></i>Nueva Recolección
                            </a>
                        </li>
                        <li>
                            <a <?= $perm['Recole_Editar'];?> class="dropdown-item" href="?p=E_recoleccion&id=<?= $id_recoleccion ?>">
                                <i class="bi bi-pencil me-2"></i>Editar
                            </a>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#complementoVenta">
                              <i class="bi bi-file-earmark-plus me-2"></i>Complemento factura
                          </button>
                      </li>
                  </ul>
              </div>
              <!-- Button trigger modal -->
              <?php if ($recoleccion['remision'] == '' OR $recoleccion['factura_fle'] == ''): ?>
                <button type="button" class="btn btn-warning btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#MandarCorreo" <?= $perm['en_correo'];?>>
                    <i class="bi bi-envelope-arrow-up"></i> Enviar correo
                </button>
            <?php endif; ?>

            <!-- Botón Cerrar -->
            <button id="btnCerrar" class="btn btn-sm rounded-3 btn-danger"><i class="bi bi-x-circle"></i> Cerrar</button>
            <script>
              document.getElementById('btnCerrar').addEventListener('click', function() {
                window.close();
            });
        </script>
    </div>
</div>
</div>
<div class="card-body p-4">
    <div class="row g-4">
        <!-- Columna izquierda: Información principal - OPTIMIZADA -->
        <div class="col-lg-8">
            <!-- Tarjetas de Proveedor y Cliente - COMPACTAS -->
            <div class="row g-3">
                <!-- Tarjeta de Proveedor - OPTIMIZADA -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-1">
                        <div class="card-header py-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building me-2 text-primary"></i>
                                <h6 class="card-title mb-0 fw-semibold">Proveedor <?=$ExRecole?></h6>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <!-- Información compacta en lista -->
                            <div class="list-group list-group-flush small">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span class="text-muted">Código:</span>
                                    <strong><?= htmlspecialchars($recoleccion['cod_proveedor']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span class="text-muted">Bodega:</span>
                                    <strong><?= htmlspecialchars($recoleccion['cod_bodega_proveedor']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span class="text-muted">Nombre:</span>
                                    <strong class="text-end"><?= htmlspecialchars($recoleccion['nombre_bodega_proveedor']) ?></strong>
                                </div>

                                <!-- Factura compra compacta -->
                                <div class="list-group-item px-0 py-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Factura compra:</span>
                                        <?php if(!empty($recoleccion['factura_pro'])): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-success dropdown-toggle py-0" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-file-earmark-pdf"></i> <?= $recoleccion['factura_pro'] ?>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if(!empty($recoleccion['doc_pro'])): ?>
                                                        <li><a class="dropdown-item small" href="<?=$invoiceLK.$recoleccion['doc_pro']?>.pdf" target="_blank">
                                                            <i class="bi bi-file-text me-2"></i>Factura
                                                        </a></li>
                                                    <?php endif; ?>
                                                    <?php if(!empty($recoleccion['d_f_p'])): ?>
                                                        <li><a class="dropdown-item small" href="<?=$invoiceLK.$recoleccion['d_f_p']?>.pdf" target="_blank">
                                                            <i class="bi bi-file-check me-2"></i>Evidencia
                                                        </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- C.R Compra compacto -->
                                <div class="list-group-item px-0 py-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">C.R Compra:</span>
                                        <?php if(!empty($recoleccion['folio_proveedor'])): ?>
                                            <a href="<?=$link.$recoleccion['alias_proveedor']."-".$recoleccion['folio_proveedor']?>" target="_blank" class="badge bg-success text-decoration-none">
                                                <?= htmlspecialchars($recoleccion['alias_proveedor']) ?>-<?= htmlspecialchars($recoleccion['folio_proveedor']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pendiente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if($recoleccion['remi_compro'] == 1):?>
                                    <div class="list-group-item px-0 py-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted">Ticket de compro</span>
                                            <a href="<?=$url_comprobacion_remision?>" target="_blank" class="btn btn-sm btn-success py-0">
                                                <i class="bi bi-ticket"></i> Ver Ticket
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Remisiones y peso compactos - VERSIÓN CON REMIXTAC -->
                                <div class="list-group-item px-0 py-1">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <span class="text-muted">Remisión Normal:</span>
                                            <div>
                                                <?php if($remision_completa): ?>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="badge bg-teal"><?= htmlspecialchars($recoleccion['remision']) ?></span>
                                                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1" 
                                                        data-bs-toggle="modal" data-bs-target="#modalProveedor" 
                                                        title="Editar remisiones" <?= $perm['Recole_Editar'];?>>
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted">Remisión Ixtac:</span>
                                        <div>
                                            <?php if($remixtac_completa): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-indigo"><?= htmlspecialchars($recoleccion['remixtac']) ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1" 
                                                    data-bs-toggle="modal" data-bs-target="#modalProveedor" 
                                                    title="Editar remisiones" <?= $perm['Recole_Editar'];?>>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Peso y botón de agregar/editar -->
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <span class="text-muted">Peso:</span>
                                    <div>
                                        <strong class="small">
                                            <?= $peso_proveedor_completo ? htmlspecialchars($recoleccion['peso_prov']) . ' kg' : '-' ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-end">
                                        <?php if(!$alguna_remision_completa): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0" 
                                            data-bs-toggle="modal" data-bs-target="#modalProveedor" <?= $perm['Recole_Editar'];?>>
                                            <i class="bi bi-plus"></i> Agregar Remisiones
                                        </button>
                                    <?php else: ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Tarjeta de Cliente - OPTIMIZADA -->
<div class="col-md-6">
    <div class="card h-100 shadow-sm border-1">
        <div class="card-header py-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-person me-2 text-success"></i>
                <h6 class="card-title mb-0 fw-semibold">Cliente</h6>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Código:</span>
                    <strong><?= htmlspecialchars($recoleccion['cod_cliente']) ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Bodega:</span>
                    <strong><?= htmlspecialchars($recoleccion['cod_bodega_cliente']) ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Nombre:</span>
                    <strong class="text-end"><?= htmlspecialchars($recoleccion['nombre_bodega_cliente']) ?></strong>
                </div>

                <!-- Factura venta compacta -->
                <div class="list-group-item px-0 py-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Factura venta:</span>
                        <?php if($existePDF): ?>
                            <a href="<?=$url?>" target="_blank" class="btn btn-sm btn-success py-0">
                                <i class="bi bi-file-earmark-pdf"></i> <?= $recoleccion['factura_v'] ?>
                            </a>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <?= $recoleccion['factura_v'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Fecha factura:</span>
                    <strong><?= $fecha_factura ?></strong>
                </div>
                
                <!-- NUEVA SECCIÓN: Factura complemento - SOLO SE MUESTRA SI EXISTE -->
                <?php if ($complemento_existe): ?>
                    <div class="list-group-item px-0 py-1 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Factura Complemento:</span>
                            <?php if($existePDFComplemento): ?>
                                <a href="<?=$url_complemento?>" target="_blank" class="btn btn-sm btn-primary py-0">
                                    <i class="bi bi-file-earmark-pdf"></i> <?= $recoleccion['factura_complemento'] ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <?= $recoleccion['factura_complemento'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span class="text-muted">Fecha complemento:</span>
                        <strong><?= $fecha_complemento ?></strong>
                    </div>

                    <?php if(!empty($recoleccion['observacion_complemento'])): ?>
                        <div class="list-group-item px-0 py-1">
                            <div class="mb-1">
                                <strong class="small">Observación del complemento:</strong>
                            </div>
                            <span class="text-muted small"><?=$recoleccion['observacion_complemento']?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <!-- FIN NUEVA SECCIÓN FACTURA COMPLEMENTO -->
            </div>
        </div>
    </div>
</div>
</div>
<!-- Modal para Complemento de Factura -->
<div class="modal fade" id="complementoVenta" tabindex="-1" aria-labelledby="complementoVentaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header encabezado-col text-white">
        <h1 class="modal-title fs-5" id="complementoVentaLabel">
            <?= $complemento_existe ? 'Editar Complemento de Factura' : 'Agregar Complemento de Factura' ?>
        </h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form class="forms-sample" method="post" action="">
        <div class="modal-body">
            <input type="hidden" name="id_recoleccion" value="<?= $id_recoleccion ?>">
            
            <div class="alert alert-info py-2 small" role="alert">
                <i class="bi bi-info-circle me-1"></i>
                El complemento de factura es opcional. Solo complete este formulario si necesita agregar una factura complementaria.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="factura_complemento" class="form-label">Factura Complemento *</label>
                        <input type="text" class="form-control" id="factura_complemento" name="factura_complemento" 
                        value="<?= $recoleccion['factura_complemento'] ?>" 
                        placeholder="Ingrese número de factura complemento" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fecha_complemento" class="form-label">Fecha Complemento</label>
                        <input type="date" class="form-control" id="fecha_complemento" name="fecha_complemento" 
                        value="<?= date('Y-m-d', strtotime($recoleccion['fecha_complemento'])) ?>" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="observacion_complemento" class="form-label">Observación</label>
                        <textarea class="form-control" id="observacion_complemento" name="observacion_complemento" 
                        rows="3" placeholder="Observaciones adicionales sobre el complemento" required><?= $recoleccion['observacion_complemento'] ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Información adicional para contexto -->
            <div class="bg-body-tertiary p-2 rounded small">
                <div class="row">
                    <div class="col-6">
                        <strong>Recolección:</strong><br>
                        <?= $folio_completo ?>
                    </div>
                    <div class="col-6">
                        <strong>Factura Principal:</strong><br>
                        <?= $recoleccion['factura_v'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <?php if ($complemento_existe): ?>
                <!-- Formulario para eliminar complemento -->
                <form method="post" action="" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea eliminar el complemento de factura? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="id_recoleccion" value="<?= $id_recoleccion ?>">
                    <button type="submit" class="btn btn-danger btn-sm rounded-3" name="eliminar_complemento">
                        <i class="bi bi-trash me-1"></i>Eliminar Complemento
                    </button>
                </form>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary btn-sm rounded-3" name="guardar_complemento">
                <?= $complemento_existe ? 'Actualizar' : 'Guardar' ?>
            </button>
        </div>
    </form>
</div>
</div>
</div>

<!-- Tarjeta de Fletero y Producto - OPTIMIZADA -->
<div class="card mt-3 shadow-sm border-1">
    <div class="card-header py-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-truck me-2 text-warning"></i>
            <h6 class="card-title mb-0 fw-semibold">Transporte y Producto</h6>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <!-- Fletero compacto -->
            <div class="col-md-3">
                <div class="text-center p-2 border rounded h-100">
                    <i class="bi bi-truck text-primary fs-5"></i>
                    <div class="mt-1">
                        <small class="text-muted d-block">Fletero</small>
                        <strong class="small"><?= htmlspecialchars($recoleccion['placas_fletero']) ?></strong>
                        <div class="text-muted small"><?= htmlspecialchars($recoleccion['razon_social_fletero']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Precio flete compacto -->
            <div class="col-md-2">
                <div class="text-center p-2 border rounded h-100">
                    <i class="bi bi-currency-dollar text-success fs-5"></i>
                    <div class="mt-1">
                        <small class="text-muted d-block">Precio Flete</small>
                        <strong class="small text-success">$<?= number_format($recoleccion['precio_flete'], 2) ?></strong>
                        <div class="mt-1">
                            <span class="badge bg-<?= $recoleccion['tipo_flete'] == 'FT' ? 'primary' : 'indigo' ?> small">
                                <?= $recoleccion['tipo_flete'] == 'FT' ? 'Por Ton.' : 'Por Viaje' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Producto compacto -->
            <div class="col-md-3">
                <div class="text-center p-2 border rounded h-100">
                    <i class="bi bi-box-seam text-warning fs-5"></i>
                    <div class="mt-1">
                        <small class="text-muted d-block">Producto</small>
                        <strong class="small"><?= htmlspecialchars($recoleccion['cod_producto']) ?></strong>
                        <div class="text-muted small text-truncate"><?= htmlspecialchars($recoleccion['nombre_producto']) ?></div>
                    </div>
                </div>
            </div>


<!-- Factura flete compacta -->
<div class="col-md-4">
    <div class="text-center p-2 border rounded h-100">
        <i class="bi bi-receipt text-info fs-5"></i>
        <div class="mt-1">
            <small class="text-muted d-block">Factura Flete</small>

            <?php if (!empty($recoleccion['doc_fle'])): ?>
                <!-- Con PDF -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-success dropdown-toggle py-0" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-pdf"></i> <?=$recoleccion['factura_fle']?>
                    </button>
                    <ul class="dropdown-menu">
                        <?php if(!empty($recoleccion['doc_fle'])): ?>
                            <li><a class="dropdown-item small" href="<?=$invoiceLK.$recoleccion['doc_fle']?>.pdf" target="_blank">
                                <i class="bi bi-file-text me-2"></i>Factura
                            </a></li>
                        <?php endif; ?>
                        <?php if(!empty($recoleccion['d_f_f'])): ?>
                            <li><a class="dropdown-item small" href="<?=$invoiceLK.$recoleccion['d_f_f']?>.pdf" target="_blank">
                                <i class="bi bi-file-check me-2"></i>Evidencia
                            </a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Botón de editar (ya tiene documentos) -->
                <div class="mt-1">
                    <button type="button" class="btn btn-sm btn-outline-warning py-0" 
                    data-bs-toggle="modal" data-bs-target="#modalFletero" 
                    title="Editar factura y peso" <?= $perm['Recole_Editar'];?>>
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </button>
            </div>
            
        <?php elseif(!empty($recoleccion['factura_fle'])): ?>
            <!-- Sin PDF pero con factura -->
            <div class="d-flex flex-column align-items-center">
                <div class="d-flex align-items-center gap-1 mb-1">
                    <span class="badge bg-teal"><?=$recoleccion['factura_fle']?></span>
                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1" 
                    data-bs-toggle="modal" data-bs-target="#modalFletero" 
                    title="Editar factura y peso" <?= $perm['Recole_Editar'];?>>
                    <i class="bi bi-pencil-square"></i>
                </button>
            </div>
            
            <!-- Determinar qué botón mostrar -->
            <?php 
            $tiene_datos_adicionales = !empty($recoleccion['tipo_fle']) && 
            !empty($recoleccion['nom_fle']) && 
            !empty($recoleccion['placas_fle']);
            ?>
            
            <?php if (!$tiene_datos_adicionales): ?>
                <!-- Botón "Completar" si faltan datos adicionales -->
                <button type="button" class="btn btn-sm btn-warning py-0 mt-1" 
                data-bs-toggle="modal" data-bs-target="#modalFletero" <?= $perm['Recole_Editar'];?>>
                <i class="bi bi-clipboard-plus me-1"></i> Completar datos
            </button>
        <?php else: ?>
            <!-- Ya está completo, solo mostrar que es editable -->
            <small class="text-muted mt-1">
                <i class="bi bi-pencil-square me-1"></i> Editable
            </small>
        <?php endif; ?>
        
        <!-- Advertencia si no hay documentos -->
        <?php if(empty($recoleccion['doc_fle']) && !empty($recoleccion['factura_fle'])): ?>
        <div class="mt-1">
            <small class="text-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Sin documentos
            </small>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>
    <!-- Sin datos - mostrar botón Agregar -->
    <button type="button" class="btn btn-sm btn-outline-primary py-0" data-bs-toggle="modal" data-bs-target="#modalFletero" <?= $perm['Recole_Editar'];?>>
        <i class="bi bi-plus-circle me-1"></i> Agregar
    </button>
<?php endif; ?>

<!-- C.R Flete compacto -->
<div class="mt-2">
    <small class="text-muted">C.R Flete:</small>
    <div>
        <?php if(!empty($recoleccion['folio_fletero'])): ?>
            <a href="<?=$link.$recoleccion['alias_fletero']."-".$recoleccion['folio_fletero']?>" target="_blank" class="badge bg-success text-decoration-none small">
                <?= htmlspecialchars($recoleccion['alias_fletero']) ?>-<?= htmlspecialchars($recoleccion['folio_fletero']) ?>
            </a>
        <?php elseif($recoleccion['precio_flete'] == 0 && empty($recoleccion['factura_fle'])): ?>
            <!-- Caso N/A (no aplica flete) -->
            <span class="badge bg-teal small">
                N/A
            </span>
        <?php else: ?>
            <!-- Pendiente o fue reseteado -->
            <span class="badge bg-<?= empty($recoleccion['factura_fle']) ? 'secondary' : 'warning' ?> small">
                <?= empty($recoleccion['factura_fle']) ? 'Pendiente' : 'Por agregar' ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Información de edición (solo si tiene factura) -->
<?php if(!empty($recoleccion['factura_fle'])): ?>
    <div class="mt-1">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Factura y peso editables
        </small>
    </div>
<?php endif; ?>
</div>
</div>
</div>
</div>

<!-- Detalles del transporte - SOLO SI EXISTEN -->
<?php if(!empty($recoleccion['tipo_fle']) || !empty($recoleccion['nom_fle']) || !empty($recoleccion['placas_fle'])): ?>
<div class="mt-3 pt-3 border-top">
    <h6 class="small text-muted mb-2">
        <i class="bi bi-truck me-1"></i>Detalles del Transporte
    </h6>
    <div class="row g-2 small">
        <?php if(!empty($recoleccion['tipo_fle'])): ?>
            <div class="col-4">
                <span class="text-muted">Tipo:</span>
                <div class="fw-bold text-primary"><?= htmlspecialchars($recoleccion['tipo_fle']) ?></div>
            </div>
        <?php endif; ?>
        <?php if(!empty($recoleccion['nom_fle'])): ?>
            <div class="col-4">
                <span class="text-muted">Chofer:</span>
                <div class="fw-bold"><?= htmlspecialchars($recoleccion['nom_fle']) ?></div>
            </div>
        <?php endif; ?>
        <?php if(!empty($recoleccion['placas_fle'])): ?>
            <div class="col-4">
                <span class="text-muted">Placas:</span>
                <div class="fw-bold"><?= htmlspecialchars($recoleccion['placas_fle']) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>
</div>
</div>
<!-- Columna derecha: Detalles financieros - CON DETALLES COMPLETOS DEL FLETE -->
<div class="col-lg-4">
    <div class="card h-100 shadow-sm border-1">
        <div class="card-header py-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-graph-up me-2 text-danger"></i>
                <h6 class="card-title mb-0 fw-semibold">Resumen Financiero</h6>
            </div>
        </div>
        <div class="card-body p-3">
            <!-- Información básica compacta -->
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Tipo Flete:</span>
                    <span class="badge bg-<?= $recoleccion['tipo_flete'] == 'FT' ? 'primary' : 'indigo' ?>">
                        <?= $recoleccion['tipo_flete'] == 'FT' ? 'Por Tonelada' : 'Por Viaje' ?>
                    </span>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Precio Compra:</span>
                    <strong>$<?= number_format($recoleccion['precio_compra'], 2) ?></strong>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Peso Proveedor:</span>
                    <strong><?= $peso_proveedor_completo ? number_format($recoleccion['peso_prov'], 2) . ' kg' : '-' ?></strong>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-bottom">
                    <span class="text-muted">Total Compra:</span>
                    <strong class="text-danger">
                        <?php
                        $total_compra = ($peso_proveedor_completo && !empty($recoleccion['precio_compra'])) ? 
                        $recoleccion['peso_prov'] * $recoleccion['precio_compra'] : 0;
                        echo $total_compra > 0 ? '$' . number_format($total_compra, 2) : '-';
                        ?>
                    </strong>
                </div>
            </div>

            <!-- Detalles del flete COMPLETOS -->
            <div class="bg-body-tertiary p-2 rounded mt-2 mb-3">
                <h6 class="small text-center text-muted mb-2">Detalles del Flete</h6>

                <!-- Indicador del peso utilizado -->
                <?php if ($recoleccion['precio_flete'] == 0 && $calculo_flete['peso_usado'] == 'proveedor'): ?>
                    <div class="alert alert-indigo py-1 mb-2 small" role="alert">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>Usando peso del proveedor</strong> (flete $0)
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Precio base</span>
                    <strong class="text-muted small">$<?= number_format($recoleccion['precio_flete'], 2) ?></strong>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Peso flete</span>
                    <strong class="text-muted small">
                        <?= $peso_flete_completo ? number_format($recoleccion['peso_fle'], 2) . ' kg' : '-' ?>
                    </strong>
                </div>

                <!-- Mostrar peso del proveedor si es relevante -->
                <?php if ($recoleccion['precio_flete'] == 0 && $peso_proveedor_completo): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Peso proveedor</span>
                        <strong class="text-indigo small"><?= number_format($recoleccion['peso_prov'], 2) ?> kg</strong>
                    </div>
                <?php endif; ?>

                <?php if ($recoleccion['tipo_flete'] == 'FT' && $recoleccion['peso_minimo'] > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Peso mínimo</span>
                        <strong class="text-muted small"><?= number_format($recoleccion['peso_minimo'], 3) ?> ton</strong>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Peso utilizado</span>
                    <strong class="text-muted small">
                        <?= number_format($calculo_flete['peso_utilizado'], 3) ?> ton
                        <?php if ($calculo_flete['es_minimo']): ?>
                            <span class="badge bg-warning small ms-1">Mínimo</span>
                        <?php endif; ?>
                        <?php if ($calculo_flete['peso_usado'] == 'proveedor'): ?>
                            <span class="badge bg-indigo small ms-1">De proveedor</span>
                        <?php endif; ?>
                    </strong>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-1 pt-1 border-top">
                    <span class="text-muted">Precio flete real</span>
                    <strong class="text-danger">$<?= number_format($precio_flete_real, 2) ?></strong>
                </div>

                <?php if ($recoleccion['precio_flete'] != 0 && $calculo_flete['peso_usado'] != 'proveedor'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1 pt-1 border-top">
                        <span class="text-muted">Precio por kilo</span>
                        <strong class="text-danger">$<?= number_format($Precio_x_kilos, 4) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="text-center">
                    <small class="text-muted"><?= $calculo_flete['tipo_calculo'] ?></small>
                </div>
            </div>

            <!-- Ventas y utilidad compactas -->
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                    <span class="text-muted">Precio Venta:</span>
                    <strong>$<?= number_format($recoleccion['precio_venta'], 2) ?></strong>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-bottom">
                    <span class="text-muted">Total Venta:</span>
                    <strong class="text-success">
                        <?php
                        if ($recoleccion['precio_flete'] == 0 && !empty($recoleccion['peso_prov']) && $recoleccion['peso_prov'] > 0) {
                            $total_venta = $recoleccion['peso_prov'] * $recoleccion['precio_venta'];
                        } else {
                            $total_venta = ($peso_flete_completo && !empty($recoleccion['precio_venta'])) ? $recoleccion['peso_fle'] * $recoleccion['precio_venta'] : 0;
                        }
                        echo $total_venta > 0 ? '$' . number_format($total_venta, 2) : '-';
                        ?>
                    </strong>
                </div>
            </div>

            <!-- Utilidad compacta -->
            <?php
            $utilidad_estimada = 0;
            $mostrar_utilidad = false;
            if ($total_compra > 0 && $total_venta > 0) {
                $utilidad_estimada = $total_venta - $total_compra - $precio_flete_real;
                $mostrar_utilidad = true;
                $porcentaje_utilidad = $total_compra > 0 ? ($utilidad_estimada / ($total_compra + $precio_flete_real)) * 100 : 0;
            }
            ?>

            <div class="mt-3 p-2 rounded bg-<?= $utilidad_estimada >= 0 ? 'success' : 'danger' ?> bg-opacity-10 border border-<?= $utilidad_estimada >= 0 ? 'success' : 'danger' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small">Utilidad Estimada:</span>
                    <strong class="text-<?= $utilidad_estimada >= 0 ? 'success' : 'danger' ?> small">
                        $<?= $mostrar_utilidad ? number_format($utilidad_estimada, 2) : '0.00' ?>
                        <?php if ($mostrar_utilidad && $total_compra > 0): ?>
                            <br>
                            <small class="text-muted">(<?= number_format($porcentaje_utilidad, 1) ?>%)</small>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>

            <!-- Facturación compacta -->
            <?php if (!empty($recoleccion['im_tras_inv'])): ?>
                <div class="mt-3 pt-2 border-top">
                    <h6 class="small text-center text-muted mb-2">Facturación</h6>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <span>Costo flete:</span>
                            <span>$<?= number_format($recoleccion['sub_tot_inv'], 2) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <span>Imp. Traslados:</span>
                            <span>$<?= number_format($recoleccion['im_tras_inv'], 2) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <span>Imp. Retenidos:</span>
                            <span class="text-danger">- $<?= number_format($recoleccion['im_rete_inv'], 2) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-top">
                            <span class="fw-semibold">Total:</span>
                            <strong class="text-danger">$<?= number_format($recoleccion['total_inv'], 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($hay_diferencia): ?>
                        <div class="alert alert-warning py-1 mt-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Diferencia: $<?= number_format($diferencia_flete, 2) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success py-1 mt-2 small" role="alert">
                            <i class="bi bi-check-circle me-1"></i>
                            Precios coinciden
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>
<!-- Modal para Proveedor - VERSIÓN CON REMIXTAC -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header encabezado-col text-white">
                <h5 class="modal-title" id="modalProveedorLabel">
                    <?= $alguna_remision_completa ? 'Editar Datos del Proveedor' : 'Agregar Datos del Proveedor' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="forms-sample" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_recoleccion" value="<?= $id_recoleccion ?>">
                    <input type="hidden" name="tipo" value="proveedor">
                    
                    <div class="alert alert-info py-2 small" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Ingresa al menos una remisión:</strong> Puedes ingresar la remisión normal, la remisión especial de Ixtac, o ambas.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="remision" class="form-label">Remisión Normal</label>
                                <input type="text" class="form-control" id="remision" name="remision" 
                                value="<?= $recoleccion['remision'] ?>"  
                                placeholder="Ingrese remisión normal">
                                <div class="form-text">Remisión estándar del proveedor</div>
                            </div>
                            <script>
                                const remisionInput = document.getElementById('remision');
                                // Eliminar espacios en tiempo real
                                remisionInput.addEventListener('input', function() {
                                    this.value = this.value.replace(/\s+/g, '');
                                });
                                
                                // Validar antes de enviar formulario
                                document.querySelector('form').addEventListener('submit', function(e) {
                                    const remisionValue = remisionInput.value.trim();
                                    
                                    if (remisionValue.includes(' ')) {
                                        e.preventDefault();
                                        alert('El campo remisión no debe contener espacios');
                                        remisionInput.focus();
                                    }
                                });
                            </script>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="remixtac" class="form-label">Remisión Ixtac</label>
                                <input type="text" class="form-control" id="remixtac" name="remixtac" 
                                value="<?= $recoleccion['remixtac'] ?>" 
                                placeholder="Ingrese remisión Ixtac">
                                <div class="form-text">Remisión especial de Ixtac</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="peso_proveedor" class="form-label">Peso del Proveedor (kg) *</label>
                        <input type="number" step="0.01" class="form-control" id="peso_proveedor" name="peso_proveedor" 
                        value="<?= $recoleccion['peso_prov'] ?>" 
                        placeholder="Ingrese el peso en kilogramos" required>
                    </div>

                    <!-- Información adicional para contexto -->
                    <div class="bg-body-tertiary p-2 rounded small">
                        <div class="row">
                            <div class="col-6">
                                <strong>Proveedor:</strong><br>
                                <?= $recoleccion['cod_proveedor'] ?> - 
                                <?= $recoleccion['nombre_bodega_proveedor'] ?>
                            </div>
                            <div class="col-6">
                                <strong>Recolección:</strong><br>
                                <?= $folio_completo ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3" name="guardar_prov" id="btnGuardarProv">
                        <?= $alguna_remision_completa ? 'Actualizar' : 'Guardar' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Fletero - VERSIÓN CON EDICIÓN Y RESET DE DOCUMENTOS -->
<div class="modal fade" id="modalFletero" tabindex="-1" aria-labelledby="modalFleteroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header encabezado-col text-white">
                <h5 class="modal-title" id="modalFleteroLabel">
                    <?= $datos_obligatorios_fletero ? 'Editar Datos del Fletero' : 'Agregar Datos del Fletero' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="forms-sample" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_recoleccion" value="<?= $id_recoleccion ?>">
                    <input type="hidden" name="tipo" value="fletero">
                    
                    <!-- Alerta informativa sobre edición -->
                    <?php if (!empty($recoleccion['factura_fle'])): ?>
                        <div class="alert alert-warning py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>¡Atención!</strong> Al editar la factura o el peso:
                            <ul class="mb-0 mt-1 ps-3">
                                <li>Los documentos de la factura (PDF y evidencia) se eliminarán</li>
                                <?php if ($recoleccion['precio_flete'] != 0): ?>
                                    <li>El contra recibo también se eliminará</li>
                                <?php else: ?>
                                    <li>El contra recibo permanecerá como <strong>N/A</strong></li>
                                <?php endif; ?>
                                <li>Los datos adicionales (tipo, chofer, placas) se conservarán</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Factura del Flete - SIEMPRE EDITABLE -->
                    <div class="mb-3">
                        <label for="factura_flete" class="form-label">Factura del Flete *</label>
                        <input type="text" class="form-control" id="factura_flete" name="factura_flete" 
                        value="<?= $recoleccion['factura_fle'] ?>" 
                        placeholder="Ingrese el número de factura del flete" required>
                        <?php if ($datos_obligatorios_fletero): ?>
                            <div class="form-text text-warning">
                                <i class="bi bi-info-circle me-1"></i>
                                Cambiar este valor reseteará los documentos relacionados
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Peso del Flete - SIEMPRE EDITABLE -->
                    <div class="mb-3">
                        <label for="peso_flete" class="form-label">Peso del Flete (kg) *</label>
                        <input type="number" step="0.01" class="form-control" id="peso_flete" name="peso_flete" 
                        value="<?= $recoleccion['peso_fle'] ?>" 
                        placeholder="Ingrese el peso del flete en kilogramos" required>
                    </div>
                    
                    <!-- Accordion para datos adicionales -->
                    <div class="accordion" id="accordionDatosAdicionales">
                        <div class="accordion-item border-1">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-body-tertiary py-2" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapseDatosAdicionales" 
                                aria-expanded="false" aria-controls="collapseDatosAdicionales">
                                <i class="bi bi-plus-circle me-2 text-primary"></i>
                                <span class="fw-normal">Datos adicionales del transporte</span>
                            </button>
                        </h2>
                        <div id="collapseDatosAdicionales" class="accordion-collapse collapse" 
                        data-bs-parent="#accordionDatosAdicionales">
                        <div class="accordion-body pt-3">
                            <div class="mb-3">
                                <label for="tipo_camion" class="form-label">Tipo de Camión</label>
                                <select class="form-select" id="tipo_camion" name="tipo_camion">
                                    <option value="">Seleccione un tipo</option>
                                    <option value="CAMIONETA 3 1/2" <?= $recoleccion['tipo_fle'] == 'CAMIONETA 3 1/2' ? 'selected' : '' ?>>CAMIONETA 3 1/2</option>
                                    <option value="TRAILER" <?= $recoleccion['tipo_fle'] == 'TRAILER' ? 'selected' : '' ?>>TRAILER</option>
                                    <option value="TORTON" <?= $recoleccion['tipo_fle'] == 'TORTON' ? 'selected' : '' ?>>TORTON</option>
                                    <option value="CAMIONETA CHICA" <?= $recoleccion['tipo_fle'] == 'CAMIONETA CHICA' ? 'selected' : '' ?>>CAMIONETA CHICA</option>
                                    <option value="OTRO" <?= $recoleccion['tipo_fle'] == 'OTRO' ? 'selected' : '' ?>>OTRO</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_chofer" class="form-label">Nombre del Chofer</label>
                                <input type="text" class="form-control" id="nombre_chofer" name="nombre_chofer" 
                                value="<?= $recoleccion['nom_fle'] ?>" 
                                placeholder="Ingrese el nombre del chofer">
                            </div>

                            <div class="mb-3">
                                <label for="placas_unidad" class="form-label">Placas de la Unidad</label>
                                <input type="text" class="form-control" id="placas_unidad" name="placas_unidad" 
                                value="<?= $recoleccion['placas_fle'] ?>" 
                                placeholder="Ingrese las placas de la unidad">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="bg-body-tertiary p-2 rounded small mt-3">
                <div class="row">
                    <div class="col-6">
                        <strong>Fletero:</strong><br>
                        <?= htmlspecialchars($recoleccion['razon_social_fletero']) ?>
                    </div>
                    <div class="col-6">
                        <strong>Recolección:</strong><br>
                        <?= $folio_completo ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary btn-sm rounded-3" name="guardar_fle">
                <?= $datos_obligatorios_fletero ? 'Actualizar' : 'Guardar' ?>
            </button>
        </div>
    </form>
</div>
</div>
</div>
<!-- Modal -->
<div class="modal fade" id="MandarCorreo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-muted">
                <h1 class="modal-title fs-5" id="exampleModalLabel"><i class="bi bi-envelope-arrow-up"></i> Enviar correo a Proveedor y Fletero</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Proveedor:<?= htmlspecialchars($recoleccion['cod_proveedor']) ?>
                        <span class="text-muted"><?=$recoleccion['correo_proveedor']?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Fletero: <?= htmlspecialchars($recoleccion['placas_fletero']) ?> 
                        <span class="text-muted"><?=$recoleccion['correo_fletero']?></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <form id="form" action="correoPF.php" method="post" data-validate="parsley" data-trigger="change">
                    <input type="hidden" name="id_rec" value="<?=$id_recoleccion?>">
                    <input type="hidden" name="id_pro" value="<?=$recoleccion['cod_proveedor']?>">
                    <input type="hidden" name="m_pro" value="<?=$recoleccion['correo_proveedor']?>">
                    <input type="hidden" name="id_fle" value="<?=$recoleccion['placas_fletero']?>">
                    <input type="hidden" name="m_fle" value="<?=$recoleccion['correo_fletero']?>">
                    <input type="hidden" name="folio" value="<?= $folio_completo ?>">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cerrar</button>
                    <input type="submit" class="btn btn-warning btn-sm rounded-3" value="Enviar mensaje" name="enviar">
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalFletero = document.getElementById('modalFletero');
        if (modalFletero) {
            modalFletero.addEventListener('show.bs.modal', function() {
            // Auto-expandir accordion si hay datos en campos opcionales
                const tieneDatosParciales = <?= 
                (!empty($recoleccion['factura_fle']) AND 
                   !empty($recoleccion['peso_fle'])) ? 'true' : 'false' 
                ?>;

                if (tieneDatosParciales) {
                    const collapseElement = document.getElementById('collapseDatosAdicionales');
                    if (collapseElement) {
                        const bsCollapse = new bootstrap.Collapse(collapseElement, {
                            toggle: true
                        });
                    }
                }
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formProveedor = document.querySelector('form[action*="guardar_prov"]');
        const btnGuardarProv = document.getElementById('btnGuardarProv');
        const remisionInput = document.getElementById('remision');
        const remixtacInput = document.getElementById('remixtac');
        const pesoInput = document.getElementById('peso_proveedor');

        if (formProveedor) {
            formProveedor.addEventListener('submit', function(e) {
            // Validar que al menos una remisión esté llena
                const remisionVal = remisionInput.value.trim();
                const remixtacVal = remixtacInput.value.trim();

                if (remisionVal === '' && remixtacVal === '') {
                    e.preventDefault();
                    alert('Error: Debe ingresar al menos una remisión (normal o Ixtac)');
                    remisionInput.focus();
                    return false;
                }

            // Validar que el peso esté lleno
                if (pesoInput.value === '' || parseFloat(pesoInput.value) <= 0) {
                    e.preventDefault();
                    alert('Error: El peso del proveedor es obligatorio y debe ser mayor a 0');
                    pesoInput.focus();
                    return false;
                }

                return true;
            });
        }

    // Opcional: Mostrar/ocultar validación en tiempo real
        function validarRemisiones() {
            const remisionVal = remisionInput.value.trim();
            const remixtacVal = remixtacInput.value.trim();
            const pesoVal = pesoInput.value;

            const remisionesOk = remisionVal !== '' || remixtacVal !== '';
            const pesoOk = pesoVal !== '' && parseFloat(pesoVal) > 0;

            if (btnGuardarProv) {
                btnGuardarProv.disabled = !(remisionesOk && pesoOk);
            }
        }

        if (remisionInput && remixtacInput && pesoInput) {
            remisionInput.addEventListener('input', validarRemisiones);
            remixtacInput.addEventListener('input', validarRemisiones);
            pesoInput.addEventListener('input', validarRemisiones);

        // Validación inicial
            validarRemisiones();
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const facturaFleteInput = document.getElementById('factura_flete');
        
        if (facturaFleteInput) {
            // Eliminar espacios en tiempo real
            facturaFleteInput.addEventListener('input', function() {
                this.value = this.value.replace(/\s+/g, '');
            });
            
            // Validar antes de enviar formulario
            const formFletero = document.querySelector('form[name="guardar_fle"]');
            if (formFletero) {
                formFletero.addEventListener('submit', function(e) {
                    const facturaValue = facturaFleteInput.value.trim();
                    
                    if (facturaValue.includes(' ')) {
                        e.preventDefault();
                        alert('El campo factura no debe contener espacios');
                        facturaFleteInput.focus();
                    }
                    
                    // Validar que la factura no esté vacía
                    if (facturaValue === '') {
                        e.preventDefault();
                        alert('El campo factura del flete es obligatorio');
                        facturaFleteInput.focus();
                    }
                });
            }
        }
        
        // Auto-expandir accordion si hay datos
        const modalFletero = document.getElementById('modalFletero');
        if (modalFletero) {
            modalFletero.addEventListener('show.bs.modal', function() {
                const tieneDatosAdicionales = <?= 
                (!empty($recoleccion['tipo_fle']) || 
                 !empty($recoleccion['nom_fle']) || 
                 !empty($recoleccion['placas_fle'])) ? 'true' : 'false' 
                ?>;
                
                if (tieneDatosAdicionales) {
                    const collapseElement = document.getElementById('collapseDatosAdicionales');
                    if (collapseElement) {
                        const bsCollapse = new bootstrap.Collapse(collapseElement, {
                            toggle: true
                        });
                    }
                }
            });
        }
    });
</script>