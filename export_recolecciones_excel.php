<?php
session_start();
require_once 'config/conexiones.php';
logActivity('EXCEL', 'Descargo el EXCEL de recolecciones');
$search = $_GET['search'] ?? '';
$search = $conn_mysql->real_escape_string($search);

// Forzar descarga como CSV
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=Recolecciones_" . date('Y-m-d_H-i-s') . ".csv");

// BOM para UTF-8 (acentos correctamente en Excel)
echo "\xEF\xBB\xBF";

// Función para calcular precio de flete real Y precio por kilo
function calcularPrecioFleteReal($precio_base, $tipo_flete, $peso_minimo, $peso_flete_kg, &$precio_por_kilo = null) {
    if (empty($peso_flete_kg) || $peso_flete_kg <= 0) {
        $precio_por_kilo = 0;
        return floatval($precio_base);
    }

    $peso_flete_ton = floatval($peso_flete_kg) / 1000;
    $precio_base = floatval($precio_base);
    $peso_minimo = floatval($peso_minimo);
    $precio_flete_real = 0;

    if ($tipo_flete == 'FV') {
        // Flete por viaje
        $precio_flete_real = $precio_base;
        $precio_por_kilo = ($peso_flete_kg > 0) ? $precio_flete_real / $peso_flete_kg : 0;
    } elseif ($tipo_flete == 'FT') {
        // Flete por tonelada
        if ($peso_minimo > 0) {
            if ($peso_flete_ton <= $peso_minimo) {
                $precio_flete_real = $precio_base * $peso_minimo;
            } else {
                $precio_flete_real = $precio_base * $peso_flete_ton;
            }
        } else {
            $precio_flete_real = $precio_base * $peso_flete_ton;
        }
        $precio_por_kilo = ($peso_flete_kg > 0) ? $precio_flete_real / $peso_flete_kg : 0;
    } else {
        // Tipo desconocido
        $precio_flete_real = $precio_base;
        $precio_por_kilo = 0;
    }

    return $precio_flete_real;
}

// Filtros GET
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$filtroEstado = $_GET['filtroEstado'] ?? 'todas';
$zona = $_GET['zona'] ?? '0';

$zona = filter_var($zona, FILTER_VALIDATE_INT, [
    'options' => [
        'default' => 0,
        'min_range' => 0
    ]
]);

// DEBUG: Verificar valores recibidos (puedes comentar esto después)
//error_log("Zona recibida: " . $zona);
//error_log("Fecha inicio: " . $fechaInicio);
//error_log("Fecha fin: " . $fechaFin);

// Base query
$baseQuery = "SELECT 
r.*,
r.Vfac_com AS factura_complemento, -- NUEVO: Complemento de factura
r.alias_inv_fle AS alias_fletero,
r.folio_inv_fle AS folio_fletero,
r.alias_inv_pro AS alias_proveedor,
r.folio_inv_pro AS folio_proveedor,
r.remision AS remision_recole,
p.cod AS cod_proveedor,
dp.cod_al AS cod_bodega_proveedor,
t.placas AS cod_fletero,
c.cod AS cod_cliente,
pc.precio AS precio_compra,
pv.precio AS precio_venta,
pf.precio AS precio_flete_base,
pf.tipo AS tipo_flete,
pf.conmin AS peso_minimo,
z.cod AS cod_zona,
pr.nom_pro AS nombre_producto,
CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo
FROM recoleccion r
LEFT JOIN proveedores p ON r.id_prov = p.id_prov
LEFT JOIN direcciones dp ON r.id_direc_prov = dp.id_direc
LEFT JOIN transportes t ON r.id_transp = t.id_transp
LEFT JOIN clientes c ON r.id_cli = c.id_cli
LEFT JOIN zonas z ON r.zona = z.id_zone
LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
WHERE r.status = '1'";

if (!empty($search)) {
    $baseQuery .= " AND (
    r.folio LIKE '%$search%' 
    OR p.cod LIKE '%$search%' 
    OR p.rs LIKE '%$search%' 
    OR dp.cod_al LIKE '%$search%' 
    OR t.placas LIKE '%$search%' 
    OR c.cod LIKE '%$search%' 
    OR r.factura_pro LIKE '%$search%' 
    OR r.peso_prov LIKE '%$search%' 
    OR r.peso_fle LIKE '%$search%' 
    OR r.factura_fle LIKE '%$search%' 
    OR r.factura_v LIKE '%$search%'
    OR r.Vfac_com LIKE '%$search%' -- NUEVO: Búsqueda de complemento
    OR r.remision LIKE '%$search%' 
    OR pv.precio LIKE '%$search%' 
    OR pc.precio LIKE '%$search%' 
    OR r.fecha_r LIKE '%$search%' 
    OR CONCAT(r.alias_inv_pro,'-',r.folio_inv_pro) LIKE '%$search%' 
    OR CONCAT(r.alias_inv_fle,'-',r.folio_inv_fle) LIKE '%$search%' 
    OR CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio,4,'0')) LIKE '%$search%'
)";
}

// Filtros de fecha
if (!empty($fechaInicio)) $baseQuery .= " AND r.fecha_r >= '$fechaInicio'";
if (!empty($fechaFin)) $baseQuery .= " AND r.fecha_r <= '$fechaFin 23:59:59'";

// FILTRO ZONA MEJORADO
if ($zona > 0) {
    $baseQuery .= " AND r.zona = " . intval($zona);
    //error_log("Aplicando filtro zona: " . $zona); // DEBUG
}

if ($filtroEstado != 'todas') {
    switch ($filtroEstado) {
        case 'completas':
            // Ambos contrarecibos presentes (incluyendo N/A para flete)
        $baseQuery .= " AND (r.folio_inv_pro IS NOT NULL AND r.folio_inv_pro != '') 
        AND (r.folio_inv_fle IS NOT NULL OR r.factura_fle = 'N/A')";
        break;

        case 'solo-flete':
            // Solo tiene contra recibo de flete (o N/A) pero NO tiene de compra
        $baseQuery .= " AND ((r.folio_inv_fle IS NOT NULL AND r.folio_inv_fle != '') 
        OR (pf.precio = 0)) 
        AND (r.folio_inv_pro IS NULL OR r.folio_inv_pro = '')";
        break;

        case 'solo-compra':
            // Solo tiene contra recibo de compra pero NO tiene de flete (ni N/A)
        $baseQuery .= " AND (r.folio_inv_pro IS NOT NULL AND r.folio_inv_pro != '') 
        AND (r.folio_inv_fle IS NULL OR r.folio_inv_fle = '') 
        AND pf.precio != 0";
        break;

        case 'pendientes':
            // No tiene ningún contra recibo (ni N/A para flete)
        $baseQuery .= " AND (r.folio_inv_pro IS NULL OR r.folio_inv_pro = '') 
        AND (r.folio_inv_fle IS NULL OR r.folio_inv_fle = '') 
        AND pf.precio != 0";
        break;
    }
}

$baseQuery .= " ORDER BY r.fecha_r DESC";
$result = $conn_mysql->query($baseQuery);
if (!$result) die("Error en la consulta: " . $conn_mysql->error);

// Encabezados CSV - AGREGADA NUEVA COLUMNA "Precio Flete/Kg"
echo "Folio,Proveedor,Bodega Prov,Fletero,Cliente,Peso Compra (kg),Precio Compra,Total Compra,Remison,Factura Compra,C.R Compra,Peso Flete (kg),Precio Flete,Tipo de flete,Precio Flete/Kg,Total Flete,Factura Flete,C.R Flete,Precio Venta,Total Venta,Factura Venta,Complemento Factura,Utilidad,Fecha,Observacion,Tipo de transporte,Chofer,Placas\n";

// Recorrer resultados
while ($row = $result->fetch_assoc()) {
    $peso_prov = floatval($row['peso_prov'] ?? 0);
    $precio_compra = floatval($row['precio_compra'] ?? 0);
    $peso_fle = floatval($row['peso_fle'] ?? 0);
    $precio_venta = floatval($row['precio_venta'] ?? 0);
    
    // Calcular precio de flete real Y precio por kilo
    $precio_por_kilo = 0;
    $precio_flete_real = calcularPrecioFleteReal(
        $row['precio_flete_base'] ?? 0,
        $row['tipo_flete'] ?? '',
        $row['peso_minimo'] ?? 0,
        $peso_fle,
        $precio_por_kilo // Pasar por referencia
    );

    $flete_gratis = (floatval($row['precio_flete_base'] ?? 0) == 0 && $precio_flete_real == 0);

    $total_venta = $total_compra = $utilidad_estimada = 0;
    $observacion = "";

    if ($flete_gratis) {
        $total_venta = $peso_prov * $precio_venta;
        $total_compra = $peso_prov * $precio_compra;
        $utilidad_estimada = $total_venta - $total_compra;
        $observacion = "No cubrimos flete";
        // Si es gratis, el precio por kilo es 0
        $precio_por_kilo = 0;
    } else {
        $total_venta = $peso_fle * $precio_venta;
        $total_compra = $peso_prov * $precio_compra;

        if ($peso_prov > 0 && $precio_compra > 0 && $peso_fle > 0 && $precio_venta > 0) {
            $utilidad_estimada = $total_venta - $total_compra - $precio_flete_real;
            $observacion = "OK";
        } else {
            $utilidad_estimada = 0;
            if ($peso_fle <= 0) $observacion = "Sin peso flete";
            elseif ($peso_prov <= 0) $observacion = "Sin peso proveedor";
            elseif ($precio_compra <= 0 || $precio_venta <= 0) $observacion = "Precio faltante";
            else $observacion = "Datos incompletos";
        }
    }
    
    $cr_pro = "";
    if (!empty($row['folio_proveedor'])) {
        $cr_pro = $row['alias_proveedor']."-".$row['folio_proveedor'];
    }
    $cr_fle = "";
    if (!empty($row['folio_fletero'])) {
        $cr_fle = $row['alias_fletero']."-".$row['folio_fletero'];
    } elseif ($row['factura_fle'] == 'N/A') {
        $cr_fle = "N/A";
    }

    $fecha = !empty($row['fecha_r']) ? date('Y-m-d', strtotime($row['fecha_r'])) : '';

    $TipoFleAler = '';
    if ($row['tipo_flete'] == 'FV') {
        $TipoFleAler = 'Viaje';
    } elseif ($row['tipo_flete'] == 'FT') {
        $TipoFleAler = 'Tonelada';
    }

    // Escapar comas y comillas en los datos
    $campos = [
        $row['folio_completo'] ?? '',
        $row['cod_proveedor'] ?? '',
        $row['cod_bodega_proveedor'] ?? '',
        $row['cod_fletero'] ?? '',
        $row['cod_cliente'] ?? '',
        number_format($peso_prov, 2),
        number_format($precio_compra, 2),
        number_format($total_compra, 2),
        $row['remision_recole'],
        $row['factura_pro'] ?? '',
        $cr_pro ?? '',
        number_format($peso_fle, 2),
        number_format($row['precio_flete_base'] ?? 0, 2),
        $TipoFleAler ?? '',
        number_format($precio_por_kilo, 4), // NUEVA COLUMNA: Precio Flete por Kilo (4 decimales)
        number_format($precio_flete_real, 2),
        $row['factura_fle'] ?? '',
        $cr_fle ?? '',
        number_format($precio_venta, 2),
        number_format($total_venta, 2),
        $row['factura_v'] ?? '',
        $row['factura_complemento'] ?? '',
        number_format($utilidad_estimada, 2),
        $fecha,
        $observacion,
        $row['tipo_fle'],
        $row['nom_fle'],
        $row['placas_fle']
    ];

    // Encerrar cada campo en comillas dobles
    echo '"' . implode('","', $campos) . "\"\n";
}

$conn_mysql->close();
?>