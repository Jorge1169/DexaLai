<?php
// remision.php - Generar documento de remisión para ventas

session_start();

// Incluir conexión a la base de datos (ajusta la ruta según tu estructura)
require_once '../config/conexiones.php';

// Verificar que se haya pasado un ID de venta
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó el ID de la venta");
}

$id_venta = intval($_GET['id']);

// Obtener datos de la venta
$sql_venta = "SELECT 
    v.*,
    CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio, 4, '0')) as folio_compuesto,
    c.cod as cod_cliente, c.nombre as nombre_cliente, c.rfc as rfc_cliente,
    a.cod as cod_almacen, a.nombre as nombre_almacen,
    z.PLANTA as nombre_zona,
    t.placas as placas_fletero, t.razon_so as nombre_fletero,
    u.nombre as nombre_usuario,
    DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_venta_formateada,
    v.factura_venta,
    DATE_FORMAT(v.fecha_factura, '%d/%m/%Y') as fecha_factura_formateada
FROM ventas v
LEFT JOIN clientes c ON v.id_cliente = c.id_cli
LEFT JOIN almacenes a ON v.id_alma = a.id_alma
LEFT JOIN zonas z ON v.zona = z.id_zone
LEFT JOIN transportes t ON v.id_transp = t.id_transp
LEFT JOIN usuarios u ON v.id_user = u.id_user
WHERE v.id_venta = ? AND v.status = 1";

$stmt = $conn_mysql->prepare($sql_venta);
$stmt->bind_param('i', $id_venta);
$stmt->execute();
$result_venta = $stmt->get_result();

if ($result_venta->num_rows === 0) {
    die("Error: Venta no encontrada o ha sido cancelada");
}

$venta = $result_venta->fetch_assoc();

// Obtener detalles de la venta
$sql_detalles = "SELECT 
    vd.*,
    p.cod as cod_producto, p.nom_pro as nombre_producto
FROM venta_detalle vd
LEFT JOIN productos p ON vd.id_prod = p.id_prod
WHERE vd.id_venta = ? AND vd.status = 1";

$stmt_detalles = $conn_mysql->prepare($sql_detalles);
$stmt_detalles->bind_param('i', $id_venta);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result();

// Obtener información del flete
$sql_flete = "SELECT 
    vf.*,
    vf.factura_transportista,
    vf.nombre_chofer,
    vf.placas_unidad,
    vf.tipo_camion
FROM venta_flete vf
WHERE vf.id_venta = ?";

$stmt_flete = $conn_mysql->prepare($sql_flete);
$stmt_flete->bind_param('i', $id_venta);
$stmt_flete->execute();
$flete = $stmt_flete->get_result();
$flete_data = $flete->num_rows > 0 ? $flete->fetch_assoc() : null;

// Obtener dirección del cliente
$dir_cliente = null;
if (!empty($venta['id_direc_cliente'])) {
    $sql_dir = "SELECT * FROM direcciones WHERE id_direc = ?";
    $stmt_dir = $conn_mysql->prepare($sql_dir);
    $stmt_dir->bind_param('i', $venta['id_direc_cliente']);
    $stmt_dir->execute();
    $result_dir = $stmt_dir->get_result();
    if ($result_dir->num_rows > 0) {
        $dir_cliente = $result_dir->fetch_assoc();
    }
}

// Obtener dirección del almacén (bodega)
$dir_almacen = null;
if (!empty($venta['id_direc_alma'])) {
    $sql_dir_almacen = "SELECT * FROM direcciones WHERE id_direc = ?";
    $stmt_dir_almacen = $conn_mysql->prepare($sql_dir_almacen);
    $stmt_dir_almacen->bind_param('i', $venta['id_direc_alma']);
    $stmt_dir_almacen->execute();
    $result_dir_almacen = $stmt_dir_almacen->get_result();
    if ($result_dir_almacen->num_rows > 0) {
        $dir_almacen = $result_dir_almacen->fetch_assoc();
    }
}

// Calcular totales
$total_pacas = 0;
$total_kilos = 0;

while ($detalle = $detalles->fetch_assoc()) {
    $total_pacas += $detalle['pacas_cantidad'];
    $total_kilos += $detalle['total_kilos'];
}

// Reiniciar puntero
mysqli_data_seek($detalles, 0);

// Configurar para PDF
header('Content-Type: text/html; charset=utf-8');

// Iniciar buffer de salida
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisión <?= $venta['folio_compuesto'] ?></title>
    <link rel="shortcut icon" href="../img/logos/logo.png"/>
    <style>
        /* Estilos para impresión */
        @media print {
            @page {
                size: letter;
                margin: 0.5cm;
            }
            
            body {
                font-family: 'Arial', sans-serif;
                font-size: 8pt; /* Reducido de 10pt a 8pt */
                line-height: 1.2; /* Reducido de 1.3 a 1.2 */
                margin: 0;
                padding: 0;
                color: #000;
            }
            
            .no-print {
                display: none !important;
            }
            
            .container {
                width: 100%;
                max-width: 21cm;
                margin: 0 auto;
                padding: 0.3cm; /* Reducido padding */
            }
            
            /* Para evitar cortes en tablas */
            table {
                page-break-inside: avoid;
                font-size: 8pt; /* Tamaño reducido para tablas */
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        
        /* Estilos para pantalla */
        @media screen {
            body {
                font-family: 'Arial', sans-serif;
                font-size: 10pt; /* Reducido de 12pt a 10pt */
                line-height: 1.3;
                margin: 10px;
                padding: 10px;
                background-color: #f5f5f5;
            }
            
            .container {
                width: 21cm;
                min-height: 29.7cm;
                margin: 0 auto;
                padding: 0.5cm; /* Reducido de 1cm a 0.5cm */
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
        
        /* Estilos comunes */
        * {
            box-sizing: border-box;
        }
        
        .header {
            border-bottom: 1px solid #333; /* Reducido de 2px a 1px */
            padding-bottom: 5px; /* Reducido de 10px a 5px */
            margin-bottom: 10px; /* Reducido de 20px a 10px */
        }
        
        .logo {
            float: left;
            width: 120px; /* Reducido de 150px a 120px */
        }
        
        .logo img {
            max-width: 100%;
            max-height: 60px; /* Altura máxima reducida */
            height: auto;
        }
        
        .company-info {
            float: right;
            text-align: right;
            width: calc(100% - 130px); /* Ajustado por el logo reducido */
        }
        
        .company-info h1 {
            margin: 0;
            font-size: 14pt; /* Reducido de 18pt a 14pt */
            color: #333;
        }
        
        .company-info p {
            margin: 1px 0; /* Reducido de 2px a 1px */
            font-size: 7pt; /* Reducido de 9pt a 7pt */
            color: #666;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .document-title {
            text-align: center;
            margin: 15px 0; /* Reducido de 30px a 15px */
            padding: 8px; /* Reducido de 10px a 8px */
            background: #f0f0f0;
            border-radius: 3px; /* Reducido de 4px a 3px */
        }
        
        .document-title h2 {
            margin: 0;
            font-size: 12pt; /* Reducido de 16pt a 12pt */
            color: #333;
        }
        
        .document-title .folio {
            font-size: 10pt; /* Reducido de 14pt a 10pt */
            color: #007bff;
            font-weight: bold;
            margin-top: 3px; /* Reducido de 5px a 3px */
        }
        
        .document-title div {
            font-size: 8pt; /* Reducido */
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px; /* Reducido de 15px a 8px */
            margin-bottom: 10px; /* Reducido de 20px a 10px */
        }
        
        .info-box {
            border: 1px solid #ddd;
            padding: 6px; /* Reducido de 10px a 6px */
            border-radius: 3px; /* Reducido de 4px a 3px */
            background: #f9f9f9;
        }
        
        .info-box h3 {
            margin: 0 0 5px 0; /* Reducido de 8px a 5px */
            font-size: 9pt; /* Reducido de 11pt a 9pt */
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px; /* Reducido de 5px a 3px */
        }
        
        .info-box p {
            margin: 2px 0; /* Reducido de 3px a 2px */
            font-size: 8pt; /* Reducido de 10pt a 8pt */
            line-height: 1.1;
        }
        
        .info-box .label {
            font-weight: bold;
            color: #555;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0; /* Reducido de 20px a 10px */
            font-size: 8pt; /* Tamaño reducido */
        }
        
        .products-table th {
            background: #333;
            color: white;
            padding: 6px; /* Reducido de 8px a 6px */
            text-align: left;
            font-size: 8pt; /* Reducido de 9pt a 8pt */
            border: 1px solid #ddd;
        }
        
        .products-table td {
            padding: 6px; /* Reducido de 8px a 6px */
            border: 1px solid #ddd;
            font-size: 8pt; /* Reducido de 9pt a 8pt */
        }
        
        .products-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .totals {
            float: right;
            width: 250px; /* Reducido de 300px a 250px */
            margin-top: 10px; /* Reducido de 20px a 10px */
            font-size: 8pt; /* Tamaño reducido */
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0; /* Reducido de 8px a 4px */
            border-bottom: 1px solid #ddd;
            font-size: 8pt; /* Tamaño reducido */
        }
        
        .total-row.total-final {
            font-weight: bold;
            font-size: 9pt; /* Reducido de 11pt a 9pt */
            color: #333;
            border-top: 2px solid #333;
            margin-top: 3px; /* Reducido de 5px a 3px */
            padding-top: 5px; /* Reducido de 10px a 5px */
        }
        
        .observations {
            margin-top: 15px; /* Reducido de 30px a 15px */
            clear: both;
        }
        
        .observations h3 {
            margin-bottom: 5px; /* Reducido de 10px a 5px */
            font-size: 9pt; /* Reducido de 11pt a 9pt */
            color: #333;
        }
        
        .observations-box {
            border: 1px solid #ddd;
            padding: 8px; /* Reducido de 15px a 8px */
            min-height: 60px; /* Reducido de 100px a 60px */
            border-radius: 3px; /* Reducido de 4px a 3px */
            background: #f9f9f9;
            font-size: 8pt; /* Tamaño reducido */
            line-height: 1.1;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px; /* Reducido de 20px a 15px */
            margin-top: 25px; /* Reducido de 50px a 25px */
            padding-top: 10px; /* Reducido de 20px a 10px */
            border-top: 1px solid #333;
            font-size: 8pt; /* Tamaño reducido */
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            width: 80%;
            height: 1px;
            background: #333;
            margin: 20px auto 5px; /* Reducido de 40px auto 10px */
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 8pt; /* Reducido de 9pt a 8pt */
            margin-top: 3px; /* Reducido de 5px a 3px */
        }
        
        .footer {
            margin-top: 25px; /* Reducido de 50px a 25px */
            padding-top: 5px; /* Reducido de 10px a 5px */
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7pt; /* Reducido de 8pt a 7pt */
            color: #666;
        }
        
        h3 {
            font-size: 9pt; /* Reducido para todos los h3 */
            margin-top: 8px; /* Margen reducido */
        }
        
        /* Espaciados reducidos */
        .mb-1 { margin-bottom: 0.1rem !important; }
        .mb-2 { margin-bottom: 0.2rem !important; }
        .mb-3 { margin-bottom: 0.3rem !important; }
        .mt-1 { margin-top: 0.1rem !important; }
        .mt-2 { margin-top: 0.2rem !important; }
        .mt-3 { margin-top: 0.3rem !important; }
        
        /* Ajustes específicos para elementos pequeños */
        .small-text {
            font-size: 7pt !important;
        }
        
        .compact {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
</head>
<body>
    <!-- Botón para imprimir (solo en pantalla) -->
    <div class="print-button no-print">
        <button onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir Remisión
        </button>
        <button onclick="window.close()" style="background: #6c757d; margin-left: 10px;">
            <i class="bi bi-x-circle"></i> Cerrar
        </button>
    </div>  
    <div class="container">
        <!-- Encabezado con logo -->
        <div class="header clearfix">
            <div class="logo">
                <img src="../img/logos/DEXalapa_logo.png" alt="Logo DISTRIBUIDORA DE EMPAQUES" 
                     onerror="this.onerror=null; this.style.display='none';">
            </div>
            <div class="company-info">
                <h1>DISTRIBUIDORA DE EMPAQUES</h1>
                <?php if($dir_almacen): ?>
                <p><strong>Dirección:</strong> 
                    <?= htmlspecialchars($dir_almacen['calle'] ?? '') ?> 
                    <?= htmlspecialchars($dir_almacen['numext'] ?? '') ?>
                    <?= !empty($dir_almacen['numint']) ? 'Int. ' . htmlspecialchars($dir_almacen['numint']) : '' ?>,
                    <?= htmlspecialchars($dir_almacen['colonia'] ?? '') ?>,
                    <?= htmlspecialchars($dir_almacen['estado'] ?? '') ?>,
                    CP <?= htmlspecialchars($dir_almacen['c_postal'] ?? '') ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Título del documento -->
        <div class="document-title">
            <h2>REMISIÓN DE VENTA</h2>
            <div class="folio">Folio: <?= htmlspecialchars($venta['folio_compuesto']) ?></div>
            <div>Fecha de remisión: <?= date('d/m/Y') ?></div>
        </div>
        
        <!-- Información de la venta -->
        <div class="info-grid">
            <!-- Información del cliente -->
            <div class="info-box">
                <h3>DATOS DEL CLIENTE</h3>
                <p><span class="label">Cliente:</span> <?= htmlspecialchars($venta['nombre_cliente']) ?></p>
                <p class="small-text"><span class="label">RFC:</span> <?= htmlspecialchars($venta['rfc_cliente'] ?? 'N/A') ?></p>
                <?php if($dir_cliente): ?>
                <p class="small-text"><span class="label">Dirección:</span> 
                    <?= htmlspecialchars($dir_cliente['calle'] ?? '') ?> 
                    <?= htmlspecialchars($dir_cliente['numext'] ?? '') ?>
                    <?= !empty($dir_cliente['numint']) ? 'Int. ' . htmlspecialchars($dir_cliente['numint']) : '' ?>,
                    <?= htmlspecialchars($dir_cliente['colonia'] ?? '') ?>,
                    <?= htmlspecialchars($dir_cliente['estado'] ?? '') ?>,
                    CP <?= htmlspecialchars($dir_cliente['c_postal'] ?? '') ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- Información de la venta -->
            <div class="info-box">
                <h3>DATOS DE LA VENTA</h3>
                <p><span class="label">Fecha venta:</span> <?= htmlspecialchars($venta['fecha_venta_formateada']) ?></p>
                <p><span class="label">Almacén origen:</span> <?= htmlspecialchars($venta['nombre_almacen']) ?></p>
                <?php if($dir_almacen): ?>
                <p class="small-text"><span class="label">Dirección almacén:</span> 
                    <?= htmlspecialchars($dir_almacen['calle'] ?? '') ?> 
                    <?= htmlspecialchars($dir_almacen['numext'] ?? '') ?>
                    <?= !empty($dir_almacen['numint']) ? 'Int. ' . htmlspecialchars($dir_almacen['numint']) : '' ?>,
                    <?= htmlspecialchars($dir_almacen['colonia'] ?? '') ?>,
                    <?= htmlspecialchars($dir_almacen['estado'] ?? '') ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- Información del transporte -->
            <div class="info-box">
                <h3>DATOS DEL TRANSPORTE</h3>
                <p><span class="label">Fletero:</span> <?= htmlspecialchars($venta['nombre_fletero'] ?? 'No asignado') ?></p>
                <?php if($flete_data): ?>
                <p class="small-text"><span class="label">Chofer:</span> <?= htmlspecialchars($flete_data['nombre_chofer'] ?? '') ?></p>
                <p class="small-text"><span class="label">Unidad:</span> <?= htmlspecialchars($flete_data['tipo_camion'] ?? '') ?></p>
                <p class="small-text"><span class="label">Placas unidad:</span> <?= htmlspecialchars($flete_data['placas_unidad'] ?? '') ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Información de entrega -->
            <div class="info-box">
                <h3>INFORMACIÓN DE ENTREGA</h3>
                <p><span class="label">Fecha de embarque:</span> <?= date('d/m/Y') ?></p>
                <p class="small-text"><span class="label">Tipo de entrega:</span> Entrega directa</p>
                <p class="small-text"><span class="label">Responsable recepción:</span> __________________</p>
            </div>
        </div>
        
        <!-- Productos/Servicios -->
        <h3>DETALLE DE PRODUCTOS</h3>
        <table class="products-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Código</th>
                    <th width="35%">Descripción</th>
                    <th width="15%">Cantidad (Pacas)</th>
                    <th width="15%">Peso (Kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador = 1;
                while($detalle = $detalles->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $contador++ ?></td>
                    <td><?= htmlspecialchars($detalle['cod_producto']) ?></td>
                    <td><?= htmlspecialchars($detalle['nombre_producto']) ?></td>
                    <td style="text-align: center;"><?= number_format($detalle['pacas_cantidad'], 0) ?></td>
                    <td style="text-align: right;"><?= number_format($detalle['total_kilos'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Totales compactos -->
        <div class="totals">
            <div class="total-row">
                <span>Total pacas:</span>
                <span><?= number_format($total_pacas, 0) ?></span>
            </div>
            <div class="total-row">
                <span>Total kilos:</span>
                <span><?= number_format($total_kilos, 2) ?> kg</span>
            </div>
            <div class="total-row total-final">
                <span>Total pacas:</span>
                <span><?= number_format($total_pacas, 0) ?> pacas</span>
            </div>
        </div>
        
        <!-- Observaciones compactas -->
        <div class="observations">
            <h3>OBSERVACIONES</h3>
            <div class="observations-box">
                <p class="mb-1">Producto certificado según normas de calidad. Peso verificado en báscula certificada.</p>
                <p class="mb-1">El cliente deberá verificar cantidad y estado del producto al momento de la recepción.</p>
            </div>
        </div>
        
        <!-- Firmas compactas -->
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
                <p class="small-text">Nombre: <?= htmlspecialchars($flete_data['nombre_chofer'] ?? '__________________') ?></p>
                <p class="small-text">Firma: __________________</p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">RECIBIDO POR (CLIENTE)</div>
                <p class="small-text">Nombre: __________________</p>
                <p class="small-text">Firma y Sello: __________________</p>
            </div>
        </div>
        
        <!-- Salto de página para múltiples copias -->
    </div>

    <script>
    // Auto-imprimir al cargar (opcional)
    window.onload = function() {
        // Descomentar si quieres que se imprima automáticamente
        // setTimeout(function() {
        //     window.print();
        // }, 1000);
    };
    
    // Después de imprimir, cerrar ventana (opcional)
    window.onafterprint = function() {
        // Descomentar si quieres cerrar después de imprimir
        // setTimeout(function() {
        //     window.close();
        // }, 500);
    };
    </script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;
?>