<?php

// ajax_recolecciones.php
session_start();
require_once 'config/conexiones.php';

// Parámetros de DataTables
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$order_column = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';

// Mapeo de columnas (debe coincidir con el orden en la tabla)
$columns = [
    0 => 'r.id_recol',
    2 => 'r.folio',
    3 => 'p.rs',
    4 => 'dp.noma',
    5 => 't.razon_so',
    6 => 'c.nombre',
    7 => 'dc.noma',
    8 => 'pr.nom_pro',
    9 => 'r.remision',
    10 => 'r.remixtac',
    11 => 'r.factura_v',
    12 => 'factura_proveedor',
    13 => 'r.folio_inv_pro',
    14 => 'factura_flete',
    15 => 'r.folio_inv_fle',
    16 => 'pf.precio',
    17 => 'pc.precio',
    18 => 'pv.precio',
    19 => 'z.nom',
    20 => 'r.fecha_r',
    21 => 'estado_documentos',
    22 => 'r.status'
];

// Construir consulta base
$query = "SELECT 
r.*,
r.Vfac_com AS factura_complemento, -- NUEVO: Complemento de factura
r.Vfech_com AS fecha_complemento, -- NUEVO: Fecha complemento
r.Vob_com AS observacion_complemento, -- NUEVO: Observación complemento
r.Vcomp_com AS comprobar_complemento, -- NUEVO: Comprobación complemento
r.factura_fle AS factura_flete,
r.alias_inv_fle AS alias_fletero,
r.remixtac AS remision_ixtac,
r.folio_inv_fle AS folio_fletero,
r.alias_inv_pro AS alias_proveedor,
r.folio_inv_pro AS folio_proveedor,
r.factura_pro AS factura_proveedor,
r.doc_pro AS documento_proveedor,
r.doc_fle AS documento_fletero,
r.d_f_p AS evidencia_proveedor,  -- NUEVO: Evidencia proveedor
r.d_f_f AS evidencia_fletero,    -- NUEVO: Evidencia fletero
r.remision AS remision_recoleccion,
r.factura_fle AS Factura_flete,
CASE 
WHEN r.remision IS NOT NULL AND r.factura_fle IS NOT NULL THEN 2
when r.remision IS NOT NULL or r.factura_fle IS NOT null THEN 1
ELSE 0
END AS estado_documentos,
r.factus_v_corr AS factura_comprovada,
p.rs AS razon_social_proveedor,
p.cod AS cod_proveedor,
dp.noma AS nombre_bodega_proveedor,
dp.cod_al AS cod_bodega_proveedor,
t.razon_so AS razon_social_fletero,
t.placas AS placas_fletero,
c.nombre AS nombre_cliente,
c.cod AS cod_cliente,
c.fac_rem AS factura_remision, -- Nuevo: factura o recoleccion
dc.noma AS nombre_bodega_cliente,
dc.cod_al AS cod_bodega_cliente,
pr.nom_pro AS nombre_producto,
pr.cod AS cod_producto,
u.nombre AS nombre_usuario,
z.nom AS nom_zone,
z.cod AS cod_zona,
z.PLANTA AS planta_zona,
pf.precio AS precio_flete,
pc.precio AS precio_compra,
pv.precio AS precio_venta,
COUNT(*) OVER() AS total_count
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
LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio";

// Filtro por zona si está definido
if (isset($_POST['zona']) && $_POST['zona'] != '0') {
    $zona = intval($_POST['zona']);
    $query .= " WHERE r.zona = $zona";
} else {
    $query .= " WHERE 1=1";
}

// Búsqueda global mejorada - AGREGADO BÚSQUEDA DE COMPLEMENTO
if (!empty($search)) {
    $search = $conn_mysql->real_escape_string($search);
    $query .= " AND (
    r.folio LIKE '%$search%' 
    OR r.fecha_r LIKE '%$search%'
    or r.remision LIKE '%$search%'
    or r.remixtac LIKE '%$search%'
    OR p.rs LIKE '%$search%'
    OR p.cod LIKE '%$search%'
    OR t.placas LIKE '%$search%'
    OR t.razon_so LIKE '%$search%'
    OR c.nombre LIKE '%$search%'
    OR pr.nom_pro LIKE '%$search%'
    OR pr.cod LIKE '%$search%'
    OR r.factura_v LIKE '%$search%'
    OR r.Vfac_com LIKE '%$search%' -- NUEVO: Búsqueda de complemento
    OR r.factura_pro LIKE '%$search%'
    OR r.factura_fle LIKE '%$search%'
    OR pf.precio LIKE '%$search%'
    OR pc.precio LIKE '%$search%'
    OR pv.precio LIKE '%$search%'
    OR dp.noma LIKE '%$search%'
    OR dc.noma LIKE '%$search%'
    OR dc.cod_al LIKE '%$search%'
    OR c.cod LIKE '%$search%'
    OR z.nom LIKE '%$search%'
    OR CONCAT (r.alias_inv_pro,'-',r.folio_inv_pro) LIKE '%$search%'
    OR CONCAT (r.alias_inv_fle,'-',r.folio_inv_fle) LIKE '%$search%'
    OR CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) LIKE '%$search%'
)";
}

// Filtro de estado (activo/inactivo)
if (isset($_POST['mostrarInactivos'])) {
    if ($_POST['mostrarInactivos'] == 'false') {
        $query .= " AND r.status = '1'";
    }
    if ($_POST['mostrarInactivos'] == 'true') {
        $query .= " AND r.status = '0'";
    }
    // Si es true, mostrar ambos (activos e inactivos)
} else {
    // Por defecto, mostrar solo activos
    $query .= " AND r.status = '1'";
}

// Filtro por fechas
if (isset($_POST['fechaInicio']) && !empty($_POST['fechaInicio'])) {
    $fechaInicio = $_POST['fechaInicio'];
    $query .= " AND r.fecha_r >= '$fechaInicio'";
}

if (isset($_POST['fechaFin']) && !empty($_POST['fechaFin'])) {
    $fechaFin = $_POST['fechaFin'];
    $query .= " AND r.fecha_r <= '$fechaFin'";
}

// Ordenamiento
$order_by = $columns[$order_column] ?? 'r.fecha_r';
$query .= " ORDER BY $order_by $order_dir";

// Paginación
$query .= " LIMIT $start, $length";

// Ejecutar consulta
$result = $conn_mysql->query($query);
$data = [];
$total_count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_count = $row['total_count']; // Obtenemos el conteo total

    $correos = '';

    if ($row['remision_recoleccion'] != '') {
        $correo_pro = '<i class="bi bi-envelope-check-fill text-success fs-55"  title="Correo de proveedor contestado"></i>';
    }else {
        $correo_pro = '<i class="bi bi-envelope-x-fill text-danger fs-55" title="Correo de proveedor no contestado"></i>';
    }
    if ($row['Factura_flete'] != '') {
        $correo_fle = '<i class="bi bi-envelope-check-fill text-success fs-55" title="Correo de fletero contestado"></i>';
    }else {
        $correo_fle = '<i class="bi bi-envelope-x-fill text-danger fs-55" title="Correo de fletero no contestado"></i>';
    }
    
    // =============================================
    // NUEVA SECCIÓN: FACTURA DE VENTA + COMPLEMENTO
    // =============================================
    $factura_venta = '';
    $complemento_existe = !empty($row['factura_complemento']);
    
    // Factura principal (igual que antes)
    if ($row['factura_comprovada'] == 1) {
        $mesesA = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
        $numero_mesA = date('m', strtotime($row['fecha_v'])) - 1;
        $anoA = date('Y', strtotime($row['fecha_v']));

        if ($row['factura_remision'] == 'FAC') {

        $url_principal = 'https://glama.esasacloud.com/doctos/'.$row['planta_zona'].'/FACTURAS/'.$anoA.'/'.$mesesA[$numero_mesA].'/SIGN_'.$row['factura_v'].'.pdf';
        }else{
            $url_principal = 'https://glama.esasacloud.com/doctos/'.$row['planta_zona'].'/REMISIONES/'.$anoA.'/'.$mesesA[$numero_mesA].'/SIGN_'.$row['factura_v'].'.pdf';
        }

        $factura_principal = '<a href="'.$url_principal.'" target="_blank" class="link-underline link-underline-opacity-0 text-success fw-bold" title="Factura de venta">'.$row['factura_v'].' <i class="bi bi-file-earmark-pdf-fill"></i></a>';
    } else {
        $factura_principal = '<span class="text-danger fw-bold">'.$row['factura_v'].'</span>';
    }

    // Complemento de factura (OPTIMIZADO - usa Vcomp_com en lugar de verificar PDF)
    $complemento_html = '';
    if ($complemento_existe) {
        // Generar URL del complemento
        $mesesA_complemento = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
        $numero_mesA_complemento = date('m', strtotime($row['fecha_complemento'])) - 1;
        $anoA_complemento = date('Y', strtotime($row['fecha_complemento']));
        $url_complemento = 'https://glama.esasacloud.com/doctos/'.$row['planta_zona'].'/FACTURAS/'.$anoA_complemento.'/'.$mesesA_complemento[$numero_mesA_complemento].'/SIGN_'.$row['factura_complemento'].'.pdf';
        
        // OPTIMIZACIÓN: Usar Vcomp_com en lugar de verificar PDF
        if ($row['comprobar_complemento'] == 1) {
            $complemento_html = '
            <div class="mt-1">
                <a href="'.$url_complemento.'" target="_blank" class="link-underline link-underline-opacity-0 text-primary fw-bold" title="Complemento de factura">
                    '.$row['factura_complemento'].' <i class="bi bi-file-earmark-plus-fill me-1"></i>
                </a>
            </div>';
        } else {
            $complemento_html = '
            <div class="mt-1">
                <span class="text-danger fw-bold">'.$row['factura_complemento'].'</span>
            </div>';
        }
    }

    // Combinar factura principal y complemento
    $factura_venta = '
    <div class="factura-venta-container">
        <div class="factura-principal">
            '.$factura_principal.'
        </div>
        '.$complemento_html.'
    </div>';
    // =============================================
    // FIN NUEVA SECCIÓN
    // =============================================

    $correos = $correo_pro." ".$correo_fle;
    
    // NUEVO: Factura de Proveedor con dropdown para dos documentos
    if (!empty($row['documento_proveedor'])) {
        $facturaPro = (empty($row['documento_proveedor'])) ? '' : $invoiceLK.$row['documento_proveedor'].".pdf";
        $evidenciaPro = (empty($row['evidencia_proveedor'])) ? '' : $invoiceLK.$row['evidencia_proveedor'].".pdf";
        
        $doc_provee = '
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle btn-sm rounded-3 py-0" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> '.$row['factura_proveedor'].'
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item small" href="'.$facturaPro.'" target="_blank"><i class="bi bi-file-text me-2"></i>Factura</a></li>
                '.(!empty($row['evidencia_proveedor']) ? 
                '<li><a class="dropdown-item small" href="'.$evidenciaPro.'" target="_blank"><i class="bi bi-file-check me-2"></i>Evidencia</a></li>' 
                : '').'
            </ul>   
        </div>';
    } else {
        $doc_provee = '<p class="text-danger fw-bold">'.$row['factura_proveedor'].'</p>';
    }

    // NUEVO: Factura de Flete con dropdown para dos documentos
    if (!empty($row['documento_fletero'])) {
        $facturaFle = (empty($row['documento_fletero'])) ? '' : $invoiceLK.$row['documento_fletero'].".pdf";
        $evidenciaFle = (empty($row['evidencia_fletero'])) ? '' : $invoiceLK.$row['evidencia_fletero'].".pdf";
        
        $doc_fleter = '
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle btn-sm rounded-3 py-0" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> '.$row['factura_flete'].'
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item small" href="'.$facturaFle.'" target="_blank"><i class="bi bi-file-text me-2"></i>Factura</a></li>
                '.(!empty($row['evidencia_fletero']) ? 
                '<li><a class="dropdown-item small" href="'.$evidenciaFle.'" target="_blank"><i class="bi bi-file-check me-2"></i>Evidencia</a></li>' 
                : '').'
            </ul>   
        </div>';
    } else {
        // si el costo del flete es 0 entonces no va a tener factura
        $ColorFlete = ($row['precio_flete'] == 0) ? 'success' : 'danger';
        $doc_fleter = '<p class="text-'.$ColorFlete.' fw-bold">'.$row['factura_flete'].'</p>';
    }

    // contra
    $contra_pro = "";
    $tiene_contra_pro = false;
    if (!empty($row['folio_proveedor'])) {
        $contra_pro = "<a href='".$link.$row['alias_proveedor']."-".$row['folio_proveedor']."' target='_blank' class='link-underline link-underline-opacity-0'><p class='text-success fw-bold'>".$row['alias_proveedor']."-".$row['folio_proveedor']."</p></a>";
        $tiene_contra_pro = true;
    } else {
        $contra_pro = "<p class='text-danger fw-bold'></p>";
    }

    $contra_fle = "";
    $tiene_contra_fle = false;

    if (!empty($row['folio_fletero'])) {
        $contra_fle = "<a href='".$link.$row['alias_fletero']."-".$row['folio_fletero']."' target='_blank' class='link-underline link-underline-opacity-0'><p class='text-success fw-bold'>".$row['alias_fletero']."-".$row['folio_fletero']."</p></a>";
        $tiene_contra_fle = true;
    } 
    elseif ($row['precio_flete'] == '0') {
        $contra_fle = "<p class='text-success fw-bold'>N/A</p>";
        $tiene_contra_fle = true;
    } 
    else {
        $contra_fle = "<p class='text-danger fw-bold'></p>";
    }

    // Agregar clases específicas para mejor detección
    if ($tiene_contra_pro && $tiene_contra_fle) {
        $clase_contra = "completo";
    } elseif ($tiene_contra_pro) {
        $clase_contra = "solo-compra";
    } elseif ($tiene_contra_fle) {
        $clase_contra = "solo-flete";
    } else {
        $clase_contra = "sin-contra";
    }

    $contra_pro = "<span class='contra-recibo {$clase_contra}'>" . $contra_pro . "</span>";
    $contra_fle = "<span class='contra-recibo {$clase_contra}'>" . $contra_fle . "</span>";

    // Formatear fechas
    $fecha_recoleccion = date('Y-m-d', strtotime($row['fecha_r']));
    $fecha_factura = date('Y-m-d', strtotime($row['fecha_v']));
    $status = $row['status'] == '1' ? 'Activo' : 'Inactivo';
    $badgeClass = $row['status'] == '1' ? 'bg-success' : 'bg-danger';
    
    // Generar folio completo
    $folio_completo = $row['cod_zona'] . "-" . date('ym', strtotime($row['fecha_r'])) . str_pad($row['folio'], 4, '0', STR_PAD_LEFT);

    $remision0 = $row['remision_recoleccion'];
    
    // Preparar datos para DataTables
    $data[] = [
        '', // Columna # (se llena en el frontend)
        '', // Columna Acciones (se genera en el frontend)
        '<a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover fw-bold" href="?p=V_recoleccion&id='.$row['id_recol'].'" target="_blank">'.htmlspecialchars($folio_completo).'</a>',
        htmlspecialchars($row['cod_proveedor']),
        htmlspecialchars($row['nombre_bodega_proveedor']),
        htmlspecialchars($row['placas_fletero']),
        htmlspecialchars($row['cod_cliente'] . " - " . $row['nombre_cliente']),
        htmlspecialchars($row['cod_bodega_cliente']),
        htmlspecialchars($row['cod_producto'] . " - " . $row['nombre_producto']),
        $remision0,
        $row['remision_ixtac'],
        $factura_venta, // Ahora incluye factura principal + complemento
        $doc_provee, // Ahora muestra dropdown con factura y evidencia
        $contra_pro,
        $doc_fleter, // Ahora muestra dropdown con factura y evidencia
        $contra_fle,
        '$'.number_format($row['precio_flete'], 2),
        '$'.number_format($row['precio_compra'], 2),
        '$'.number_format($row['precio_venta'], 2),
        htmlspecialchars($row['nom_zone']),
        $fecha_recoleccion,
        $correos,
        '<span class="badge '.$badgeClass.'">'.$status.'</span>',
        $row['status'], // Columna oculta para filtrado
        $row['id_recol'] // ID para las acciones
    ];
}

// Obtener el total de registros sin filtrar
$total_query = "SELECT COUNT(*) as total FROM recoleccion";
$total_result = $conn_mysql->query($total_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];

// Respuesta para DataTables
$response = [
    "draw" => intval($_POST['draw'] ?? 1),
    "recordsTotal" => $total_records,
    "recordsFiltered" => $total_count,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);