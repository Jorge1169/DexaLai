<?php
require_once '../config/conexiones.php';

// ============================================
// 1. PROCESAR ACTUALIZACIÓN DE ESTADO (POST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_remision') {
    if (!isset($_POST['id_recoleccion']) || empty($_POST['id_recoleccion'])) {
        die(json_encode(['success' => false, 'message' => 'ID de recolección no válido']));
    }

    $id_recoleccion_post = intval($_POST['id_recoleccion']);

    $sql_estado = "UPDATE recoleccion SET estado_remision = 0 WHERE id_recol = ? AND estado_remision = 1";
    $stmt_estado = $conn_mysql->prepare($sql_estado);
    $stmt_estado->bind_param('i', $id_recoleccion_post);

    if ($stmt_estado->execute()) {
        if ($stmt_estado->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Documento actualizado a copia']);
        } else {
            echo json_encode(['success' => false, 'message' => 'El documento ya era una copia']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }

    $stmt_estado->close();
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Error: No se especificó el ID de la recolección');
}

$id_recoleccion = intval($_GET['id']);

$sql_recoleccion = "SELECT
    r.*,
    CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) AS folio_compuesto,
    p.cod AS cod_proveedor,
    p.rs AS razon_social_proveedor,
    p.nombre AS nombre_proveedor,
    c.cod AS cod_cliente,
    c.nombre AS nombre_cliente,
    c.rfc AS rfc_cliente,
    z.PLANTA AS nombre_zona,
    z.cod AS cod_zona,
    t.razon_so AS nombre_fletero,
    t.placas AS placas_fletero,
    u.nombre AS nombre_usuario,
    DATE_FORMAT(r.fecha_r, '%d/%m/%Y') AS fecha_recoleccion_formateada,
    DATE_FORMAT(r.fecha_v, '%d/%m/%Y') AS fecha_factura_formateada,
    dprov.noma AS nombre_bodega_proveedor,
    dprov.cod_al AS cod_bodega_proveedor,
    dprov.calle AS calle_bodega_proveedor,
    dprov.numext AS numext_bodega_proveedor,
    dprov.numint AS numint_bodega_proveedor,
    dprov.colonia AS colonia_bodega_proveedor,
    dprov.estado AS estado_bodega_proveedor,
    dprov.c_postal AS cp_bodega_proveedor,
    dcli.noma AS nombre_bodega_cliente,
    dcli.cod_al AS cod_bodega_cliente,
    dcli.calle AS calle_bodega_cliente,
    dcli.numext AS numext_bodega_cliente,
    dcli.numint AS numint_bodega_cliente,
    dcli.colonia AS colonia_bodega_cliente,
    dcli.estado AS estado_bodega_cliente,
    dcli.c_postal AS cp_bodega_cliente,
    pf.precio AS precio_flete,
    pf.tipo AS tipo_flete
FROM recoleccion r
LEFT JOIN proveedores p ON r.id_prov = p.id_prov
LEFT JOIN clientes c ON r.id_cli = c.id_cli
LEFT JOIN zonas z ON r.zona = z.id_zone
LEFT JOIN transportes t ON r.id_transp = t.id_transp
LEFT JOIN usuarios u ON r.id_user = u.id_user
LEFT JOIN direcciones dprov ON r.id_direc_prov = dprov.id_direc
LEFT JOIN direcciones dcli ON r.id_direc_cli = dcli.id_direc
LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
WHERE r.id_recol = ? AND r.status = 1";

$stmt = $conn_mysql->prepare($sql_recoleccion);
$stmt->bind_param('i', $id_recoleccion);
$stmt->execute();
$resultado_recoleccion = $stmt->get_result();

if ($resultado_recoleccion->num_rows === 0) {
    die('Error: Recolección no encontrada o cancelada');
}

$recoleccion = $resultado_recoleccion->fetch_assoc();

$sql_detalle = "SELECT
    prc.*,
    p.cod AS cod_producto,
    p.nom_pro AS nombre_producto,
    pc.precio AS precio_compra,
    pv.precio AS precio_venta
FROM producto_recole prc
LEFT JOIN productos p ON prc.id_prod = p.id_prod
LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
WHERE prc.id_recol = ?";

$stmt_detalle = $conn_mysql->prepare($sql_detalle);
$stmt_detalle->bind_param('i', $id_recoleccion);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();

function construirDireccionLinea($calle, $numext, $numint, $colonia, $estado, $cp)
{
    $partes = [];
    if (!empty($calle)) {
        $partes[] = trim($calle);
    }
    if (!empty($numext)) {
        $partes[] = 'No. ' . trim($numext);
    }
    if (!empty($numint)) {
        $partes[] = 'Int. ' . trim($numint);
    }
    if (!empty($colonia)) {
        $partes[] = trim($colonia);
    }
    if (!empty($estado)) {
        $partes[] = trim($estado);
    }
    if (!empty($cp)) {
        $partes[] = 'CP ' . trim($cp);
    }

    return implode(', ', $partes);
}

$direccion_proveedor = construirDireccionLinea(
    $recoleccion['calle_bodega_proveedor'] ?? '',
    $recoleccion['numext_bodega_proveedor'] ?? '',
    $recoleccion['numint_bodega_proveedor'] ?? '',
    $recoleccion['colonia_bodega_proveedor'] ?? '',
    $recoleccion['estado_bodega_proveedor'] ?? '',
    $recoleccion['cp_bodega_proveedor'] ?? ''
);

$direccion_cliente = construirDireccionLinea(
    $recoleccion['calle_bodega_cliente'] ?? '',
    $recoleccion['numext_bodega_cliente'] ?? '',
    $recoleccion['numint_bodega_cliente'] ?? '',
    $recoleccion['colonia_bodega_cliente'] ?? '',
    $recoleccion['estado_bodega_cliente'] ?? '',
    $recoleccion['cp_bodega_cliente'] ?? ''
);

$peso_proveedor = (float)($recoleccion['peso_prov'] ?? 0);
$peso_fletero = (float)($recoleccion['peso_fle'] ?? 0);
$observaciones_doc = $recoleccion['observaciones'] ?? ($recoleccion['observacion'] ?? '');
$tipo_copia = (($recoleccion['estado_remision'] ?? 1) == 1) ? 'ORIGINAL' : 'COPIA';

$total_peso_proveedor = $peso_proveedor;
$total_peso_fletero = $peso_fletero;

header('Content-Type: text/html; charset=utf-8');
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisión <?= htmlspecialchars($recoleccion['folio_compuesto']) ?></title>
    <link rel="shortcut icon" href="../img/logos/logo.png"/>
    <style>
        @media print {
            @page {
                size: letter;
                margin: 0.5cm;
            }

            body {
                font-family: 'Arial', sans-serif;
                font-size: 8pt;
                line-height: 1.2;
                margin: 0;
                padding: 0;
                color: #000;
            }

            body:after {
                content: "<?= $tipo_copia ?>";
                font-size: 10em;
                color: rgba(52, 166, 214, 0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                transform: rotate(-60deg);
                pointer-events: none;
            }

            .no-print {
                display: none !important;
            }

            .container {
                width: 100%;
                max-width: 21cm;
                margin: 0 auto;
                padding: 0.3cm;
            }

            table {
                page-break-inside: avoid;
                font-size: 8pt;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        @media screen {
            body {
                font-family: 'Arial', sans-serif;
                font-size: 10pt;
                line-height: 1.3;
                margin: 10px;
                padding: 10px;
                background-color: #f5f5f5;
            }

            .container {
                width: 21cm;
                min-height: 29.7cm;
                margin: 0 auto;
                padding: 0.5cm;
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            .print-button {
                text-align: center;
                margin: 10px 0;
            }

            button {
                padding: 8px 16px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
            }

            button:hover {
                background: #0056b3;
            }
        }

        * {
            box-sizing: border-box;
        }

        .header {
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .logo {
            float: left;
            width: 120px;
        }

        .logo img {
            max-width: 100%;
            max-height: 60px;
            height: auto;
        }

        .company-info {
            float: right;
            text-align: right;
            width: calc(100% - 130px);
        }

        .company-info h1 {
            margin: 0;
            font-size: 14pt;
            color: #333;
        }

        .company-info p {
            margin: 1px 0;
            font-size: 7pt;
            color: #666;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .document-title {
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background: #f0f0f0;
            border-radius: 3px;
        }

        .document-title h2 {
            margin: 0;
            font-size: 12pt;
            color: #333;
        }

        .document-title .folio {
            font-size: 10pt;
            color: #007bff;
            font-weight: bold;
            margin-top: 3px;
        }

        .document-title div {
            font-size: 8pt;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 6px;
            border-radius: 3px;
            background: #f9f9f9;
        }

        .info-box h3 {
            margin: 0 0 5px 0;
            font-size: 9pt;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }

        .info-box p {
            margin: 2px 0;
            font-size: 8pt;
            line-height: 1.1;
        }

        .info-box .label {
            font-weight: bold;
            color: #555;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8pt;
        }

        .products-table th {
            background: #333;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 8pt;
            border: 1px solid #ddd;
        }

        .products-table td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 8pt;
        }

        .products-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .totals {
            float: right;
            width: 260px;
            margin-top: 10px;
            font-size: 8pt;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
            font-size: 8pt;
        }

        .total-row.total-final {
            font-weight: bold;
            font-size: 9pt;
            color: #333;
            border-top: 2px solid #333;
            margin-top: 3px;
            padding-top: 5px;
        }

        .observations {
            margin-top: 15px;
            clear: both;
        }

        .observations h3 {
            margin-bottom: 5px;
            font-size: 9pt;
            color: #333;
        }

        .observations-box {
            border: 1px solid #ddd;
            padding: 8px;
            min-height: 60px;
            border-radius: 3px;
            background: #f9f9f9;
            font-size: 8pt;
            line-height: 1.2;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #333;
            font-size: 8pt;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            width: 80%;
            height: 1px;
            background: #333;
            margin: 20px auto 5px;
        }

        .signature-label {
            font-weight: bold;
            font-size: 8pt;
            margin-top: 3px;
        }

        .footer {
            margin-top: 25px;
            padding-top: 5px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        .small-text {
            font-size: 7pt !important;
        }
    </style>
</head>
<body>
    <div class="print-button no-print">
        <button onclick="window.print()">Imprimir Remisión</button>
        <button onclick="window.close()" style="background: #6c757d; margin-left: 10px;">Cerrar</button>
        <h1>Estado del documento: <?= $tipo_copia ?></h1>
    </div>

    <div class="container">
        <div class="header clearfix">
            <div class="logo">
                <img src="../img/logos/LAISA_logo.png" alt="Logo LAISA" onerror="this.onerror=null; this.style.display='none';">
            </div>
            <div class="company-info">
                <h1>LAMINAS ACANALADAS INFINITAS</h1>
                <?php if (!empty($direccion_proveedor)): ?>
                    <p><strong>Dirección:</strong> Plásticos 27, Santa clara Coatitla, Ecatepec de Morelos, EDOMEX, México. C.P. 55540</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="document-title">
            <h2>REMISIÓN</h2>
            <div class="folio">Folio: <?= htmlspecialchars($recoleccion['folio_compuesto']) ?></div>
            <div>Fecha de recolección: <?= htmlspecialchars($recoleccion['fecha_recoleccion_formateada'] ?? 'N/D') ?></div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>DATOS DEL PROVEEDOR</h3>
                <p><span class="label">Proveedor:</span>LAMINAS ACANALADAS INFINITAS</p>
                <p><span class="label">Origen:</span><?= htmlspecialchars($recoleccion['nombre_bodega_proveedor'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Dirección:</span> <?= !empty($direccion_proveedor) ? htmlspecialchars($direccion_proveedor) : 'N/D' ?></p>
            </div>

            <div class="info-box">
                <h3>DATOS DEL CLIENTE</h3>
                <p><span class="label">Cliente:</span> <?= htmlspecialchars($recoleccion['nombre_cliente'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">RFC:</span> <?= htmlspecialchars($recoleccion['rfc_cliente'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Dirección:</span> <?= !empty($direccion_cliente) ? htmlspecialchars($direccion_cliente) : 'N/D' ?></p>
            </div>

            <div class="info-box">
                <h3>DATOS DEL TRANSPORTE</h3>
                <p><span class="label">Fletero:</span> <?= htmlspecialchars($recoleccion['nombre_fletero'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Chofer:</span> <?= htmlspecialchars($recoleccion['nom_fle'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Unidad:</span> <?= htmlspecialchars($recoleccion['tipo_fle'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Placas:</span> <?= htmlspecialchars($recoleccion['placas_fle'] ?? $recoleccion['placas_fletero'] ?? 'N/D') ?></p>
            </div>

            <div class="info-box">
                <h3>DATOS DE LA RECOLECCIÓN</h3>
                <p><span class="label">Fecha recolección:</span> <?= htmlspecialchars($recoleccion['fecha_recoleccion_formateada'] ?? 'N/D') ?></p>
                <p class="small-text"><span class="label">Tipo de entrega:</span> Entrega directa</p>
                <p class="small-text"><span class="label">Responsable recepción:</span> __________________</p>
            </div>
        </div>

        <h3>DETALLE DE PRODUCTOS</h3>
        <table class="products-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Código</th>
                    <th width="35%">Descripción</th>
                    <th width="15%">Peso</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $contador = 1;
                if ($detalles->num_rows > 0):
                    while ($detalle = $detalles->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $contador++ ?></td>
                    <td><?= htmlspecialchars($detalle['cod_producto'] ?? 'N/D') ?></td>
                    <td><?= htmlspecialchars($detalle['nombre_producto'] ?? 'N/D') ?></td>
                    <td style="text-align: right;"><?= number_format($peso_proveedor, 2) ?></td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td>1</td>
                    <td>N/D</td>
                    <td>Sin detalle de productos</td>
                    <td style="text-align: right;"><?= number_format($peso_proveedor, 2) ?></td>
                    <td style="text-align: right;">-</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row total-final">
                <span>Peso Neto:</span>
                <span><?= number_format($total_peso_proveedor, 2) ?> kg</span>
            </div>
        </div>

        <div class="observations">
            <h3>OBSERVACIONES</h3>
            <div class="observations-box">
                <?php if (!empty($observaciones_doc)): ?>
                    <?= nl2br(htmlspecialchars($observaciones_doc)) ?>
                <?php else: ?>
                    <p class="mb-1"><?= htmlspecialchars($recoleccion['remision'] ?? 'N/D') ?></p>
                    <p class="mb-1">Peso verificado en báscula certificada.</p>
                    <p class="mb-1">El cliente deberá verificar cantidad y estado del producto al momento de la recepción.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">ENTREGADO POR</div>
                <p class="small-text">Nombre: __________________</p>
                <p class="small-text">Firma: __________________</p>
            </div>

            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">TRANSPORTISTA</div>
                <p class="small-text">Nombre: <?= htmlspecialchars($recoleccion['nom_fle'] ?? '__________________') ?></p>
                <p class="small-text">Firma: __________________</p>
            </div>

            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">RECIBIDO POR (CLIENTE)</div>
                <p class="small-text">Nombre: __________________</p>
                <p class="small-text">Firma y sello: __________________</p>
            </div>
        </div>
    </div>

    <script>
    window.onbeforeprint = function() {
        <?php if (($recoleccion['estado_remision'] ?? 1) == 1): ?>
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=actualizar_remision&id_recoleccion=<?= $id_recoleccion ?>',
            keepalive: true
        });
        <?php endif; ?>
    };
    </script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;
?>
