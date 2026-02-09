<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contra Recibo</title>
    <link rel="shortcut icon" href="../img/logos/lai_esfera_BN.png"/>
    <style>
        .danger-text {
            color: #dc3545;
        }
        .pagada-text {
            color: #198754;
        }
        /* Estilos optimizados para impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: 'Arial', sans-serif;
                font-size: 12px;
                color: #000;
                background: white;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            table {
                page-break-inside: avoid;
            }
        }
        
        /* Estilos para pantalla */
        @media screen {
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 20px;
                background-color: #f5f5f5;
            }
            
            .container {
                margin: 0 auto;
                background: white;
                padding: 20px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
        
        /* Estilos comunes */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .contra-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background: #343a40;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .table td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        
        .table-striped tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .totals {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .signature-area {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 20px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
        
        /* BOTONES MÁS DISCRETOS Y ELEGANTES */
        .btn-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .btn-discreto {
            background: transparent;
            color: #495057;
            border: 1px solid #6c757d;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-discreto:hover {
            background: #6c757d;
            color: white;
            border-color: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-discreto.primary {
            border-color: #007bff;
            color: #007bff;
        }
        
        .btn-discreto.primary:hover {
            background: #007bff;
            color: white;
        }
        
        .btn-discreto.success {
            border-color: #28a745;
            color: #28a745;
        }
        
        .btn-discreto.success:hover {
            background: #28a745;
            color: white;
        }
        
        .btn-discreto i {
            font-size: 14px;
        }
    </style>
    <!-- Iconos de Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php
    require_once '../config/conexiones.php';
    require_once '../config/conexion_invoice.php';// Conexion a base externa
    
    $ContraRecibo = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$ContraRecibo) {
        die('<div class="container"><div class="alert alert-danger">No se especificó el contra recibo</div></div>');
    }
    
    $separar = explode("-", $ContraRecibo);
    if (count($separar) !== 2) {
        die('<div class="container"><div class="alert alert-danger">Formato de contra recibo inválido</div></div>');
    }
    
    $Alias = $separar[0];
    $Folio = $separar[1];
    
    // Primero intentar detectar si el contra recibo pertenece a una VENTA
    $tipo = '';
    $entidad = '';
    $codigo = '';
    $contraInfo = null;

    $sqlVenta = "SELECT v.id_venta, v.folio AS folio_venta, v.fecha_venta, c.cod AS cod_cliente, c.nombre AS nombre_cliente, tv.placas AS placas_fletero, vf.folioven AS folio_cr, vf.aliasven AS alias_cr
                 FROM venta_flete vf
                 INNER JOIN ventas v ON vf.id_venta = v.id_venta
                 LEFT JOIN clientes c ON v.id_cliente = c.id_cli
                 LEFT JOIN transportes tv ON vf.id_fletero = tv.id_transp
                 WHERE vf.aliasven = ? AND vf.folioven = ?";

    $stmtV = $conn_mysql->prepare($sqlVenta);
    if ($stmtV) {
        $stmtV->bind_param('ss', $Alias, $Folio);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        if ($resV && $resV->num_rows > 0) {
            $contraInfo = $resV->fetch_assoc();
            $tipo = 'VENTA';
            $entidad = $contraInfo['nombre_cliente'] ?? '';
            $codigo = $contraInfo['cod_cliente'] ?? '';
        }
    }

    // Si no fue venta, intentar detectar CAPTACIÓN (detalle o flete)
    if (empty($tipo)) {
        $sqlCapDet = "SELECT ca.id_captacion, cd.id_detalle, ca.folio AS folio_captacion, ca.fecha_captacion, p.cod AS cod_proveedor, p.rs AS nombre_proveedor, cd.foliocap AS folio_cr, cd.aliascap AS alias_cr
                      FROM captacion ca
                      LEFT JOIN captacion_detalle cd ON ca.id_captacion = cd.id_captacion
                      LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                      WHERE cd.aliascap = ? AND cd.foliocap = ? LIMIT 1";

        $stmtC = $conn_mysql->prepare($sqlCapDet);
        if ($stmtC) {
            $stmtC->bind_param('ss', $Alias, $Folio);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            if ($resC && $resC->num_rows > 0) {
                $contraInfo = $resC->fetch_assoc();
                $tipo = 'CAPTACION';
                $entidad = $contraInfo['nombre_proveedor'] ?? '';
                $codigo = $contraInfo['cod_proveedor'] ?? '';
            }
        }

        // si no se encontró en detalle, revisar captacion_flete
        if (empty($tipo)) {
            $sqlCapFlete = "SELECT ca.id_captacion, cf.id_capt_flete, ca.folio AS folio_captacion, ca.fecha_captacion, p.cod AS cod_proveedor, p.rs AS nombre_proveedor, cf.foliocap_flete AS folio_cr, cf.aliascap_flete AS alias_cr
                      FROM captacion ca
                      LEFT JOIN captacion_flete cf ON ca.id_captacion = cf.id_captacion
                      LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                      WHERE cf.aliascap_flete = ? AND cf.foliocap_flete = ?";

            $stmtCF = $conn_mysql->prepare($sqlCapFlete);
            if ($stmtCF) {
                $stmtCF->bind_param('ss', $Alias, $Folio);
                $stmtCF->execute();
                $resCF = $stmtCF->get_result();
                if ($resCF && $resCF->num_rows > 0) {
                    $contraInfo = $resCF->fetch_assoc();
                    $tipo = 'CAPTACION_FLETE';
                    $entidad = $contraInfo['nombre_proveedor'] ?? '';
                    $codigo = $contraInfo['cod_proveedor'] ?? '';
                }
            }
        }
    }

    // Si no es venta ni captación, usar la consulta original sobre recolección
    if (empty($tipo)) {
        $query = "SELECT 
        r.alias_inv_pro as alias_compra,
        r.folio_inv_pro as folio_compra,
        r.alias_inv_fle as alias_flete,
        r.folio_inv_fle as folio_flete,
        p.rs as proveedor,
        p.cod as cod_proveedor,
        t.placas as cod_fletero,
        t.razon_so as fletero
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        WHERE (r.alias_inv_pro = ? AND r.folio_inv_pro = ?) 
        OR (r.alias_inv_fle = ? AND r.folio_inv_fle = ?)
        GROUP BY r.alias_inv_pro, r.folio_inv_pro, r.alias_inv_fle, r.folio_inv_fle, p.rs, t.razon_so";
        
        $stmt = $conn_mysql->prepare($query);
        $stmt->bind_param("ssss", $Alias, $Folio, $Alias, $Folio);
        $stmt->execute();
        $result = $stmt->get_result();
        $contraInfo = $result->fetch_assoc();
        
        if (!$contraInfo) {
            die('<div class="container"><div class="alert alert-danger">Contra recibo no encontrado</div></div>');
        }

        // Determinar si es de compra o flete
        if ($contraInfo['alias_compra'] == $Alias && $contraInfo['folio_compra'] == $Folio) {
            $tipo = 'COMPRA';
            $entidad = $contraInfo['proveedor'];
            $codigo = $contraInfo['cod_proveedor'];
        } else {
            $tipo = 'FLETE';
            $entidad = $contraInfo['fletero'];
            $codigo = $contraInfo['cod_fletero'];
        }
    }
    
    // Dependiendo del tipo detectado, consultar las filas correspondientes
    if ($tipo === 'VENTA') {
        $queryVentasRows = "SELECT v.id_venta, CONCAT('V-', z.cod, '-', DATE_FORMAT(v.fecha_venta, '%y%m'), LPAD(v.folio,4,'0')) AS folio_completo,
                                  v.fecha_venta, c.cod AS cod_cliente, c.nombre AS nombre_cliente,
                                  pr.nom_pro AS producto, vd.total_kilos, pv.precio AS precio_venta,
                                  vf.folioven AS folio_cr, vf.aliasven AS alias_cr, tv.placas AS placas_fletero,
                                  vf.factura_transportista AS factura_transportista
                           FROM venta_flete vf
                           INNER JOIN ventas v ON vf.id_venta = v.id_venta
                           LEFT JOIN venta_detalle vd ON v.id_venta = vd.id_venta
                           LEFT JOIN productos pr ON vd.id_prod = pr.id_prod
                           LEFT JOIN precios pv ON vd.id_pre_venta = pv.id_precio
                           LEFT JOIN clientes c ON v.id_cliente = c.id_cli
                           LEFT JOIN zonas z ON v.zona = z.id_zone
                           LEFT JOIN transportes tv ON vf.id_fletero = tv.id_transp
                           WHERE vf.aliasven = ? AND vf.folioven = ?
                           ORDER BY v.fecha_venta, folio_completo";

        $stmtRecolecciones = $conn_mysql->prepare($queryVentasRows);
        $stmtRecolecciones->bind_param("ss", $Alias, $Folio);
        $stmtRecolecciones->execute();
        $recolecciones = $stmtRecolecciones->get_result();

    } elseif ($tipo === 'CAPTACION' || $tipo === 'CAPTACION_FLETE') {
    // Separamos las consultas según el tipo
    if ($tipo === 'CAPTACION') {
        // Solo captaciones normales (detalle)
         $queryCapt = "SELECT ca.id_captacion, 
                     CONCAT('C-', z.cod, '-', DATE_FORMAT(ca.fecha_captacion, '%y%m'), LPAD(ca.folio,4,'0')) AS folio_completo,
                     ca.fecha_captacion, 
                     p.cod AS cod_proveedor, 
                     p.rs AS nombre_proveedor,
                     pr.nom_pro AS producto, 
                     cd.total_kilos, 
                     cd.pacas_kilos,
                     cd.pacas_cantidad, 
                     cd.numero_factura, 
                     cd.foliocap AS folio_cr, 
                     cd.aliascap AS alias_cr,
                     pc.precio AS precio_compra_por_kilo,
                     NULL AS folio_cr_flete,
                     NULL AS alias_cr_flete,
                     NULL AS placas_fletero
                 FROM captacion ca
                 LEFT JOIN captacion_detalle cd ON ca.id_captacion = cd.id_captacion
                 LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                 LEFT JOIN productos pr ON cd.id_prod = pr.id_prod
                 LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
                 LEFT JOIN zonas z ON ca.zona = z.id_zone
                 WHERE cd.aliascap = ? AND cd.foliocap = ?
                 ORDER BY ca.fecha_captacion, folio_completo";

        $stmtRecolecciones = $conn_mysql->prepare($queryCapt);
        $stmtRecolecciones->bind_param("ss", $Alias, $Folio);
        
    } elseif ($tipo === 'CAPTACION_FLETE') {
        // Solo fletes de captación
        $queryCapt = "SELECT ca.id_captacion, 
                             CONCAT('C-', z.cod, '-', DATE_FORMAT(ca.fecha_captacion, '%y%m'), LPAD(ca.folio,4,'0')) AS folio_completo,
                             ca.fecha_captacion, 
                             p.cod AS cod_proveedor, 
                             p.rs AS nombre_proveedor,
                             NULL AS producto,
                             NULL AS total_kilos,
                             NULL AS pacas_cantidad,
                             NULL AS numero_factura,
                             NULL AS folio_cr,
                             NULL AS alias_cr,
                             cf.foliocap_flete AS folio_cr_flete,
                             cf.aliascap_flete AS alias_cr_flete,
                             t.placas AS placas_fletero
                      FROM captacion ca
                      LEFT JOIN captacion_flete cf ON ca.id_captacion = cf.id_captacion
                      LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                      LEFT JOIN transportes t ON cf.id_fletero = t.id_transp
                      LEFT JOIN zonas z ON ca.zona = z.id_zone
                      WHERE cf.aliascap_flete = ? AND cf.foliocap_flete = ?
                      ORDER BY ca.fecha_captacion, folio_completo";

        $stmtRecolecciones = $conn_mysql->prepare($queryCapt);
        $stmtRecolecciones->bind_param("ss", $Alias, $Folio);
    }
    
    $stmtRecolecciones->execute();
    $recolecciones = $stmtRecolecciones->get_result();

} else {
        // Consulta para obtener las recolecciones del contra recibo
        $queryRecolecciones = "SELECT 
        r.*,
        r.remision as remision,
        r.remi_compro as remi_compro,
        r.peso_conpro as peso_conpro,
        CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
        p.rs as proveedor,
        p.cod as cod_proveedor,
        t.razon_so as fletero,
        t.placas as cod_fletero,
        pr.nom_pro as producto,
        pc.precio as precio_compra,
        pv.precio as precio_venta,
        pf.precio as precio_flete,
        c.nombre as nombre_cliente,
        r.sub_tot_inv,
        r.factura_pro as factura_proveedor,
        r.factura_fle as factura_flete,
        r.im_tras_inv,
        r.im_rete_inv,
        r.factura_v as factura_venta,
        r.remision as remision,
        r.total_inv
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN clientes c ON r.id_cli = c.id_cli
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
        LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
        LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
        LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
        LEFT JOIN zonas z ON r.zona = z.id_zone
        WHERE (r.alias_inv_pro = ? AND r.folio_inv_pro = ?) 
        OR (r.alias_inv_fle = ? AND r.folio_inv_fle = ?)
        ORDER BY r.fecha_r, folio_completo";
        
        $stmtRecolecciones = $conn_mysql->prepare($queryRecolecciones);
        $stmtRecolecciones->bind_param("ssss", $Alias, $Folio, $Alias, $Folio);
        $stmtRecolecciones->execute();
        $recolecciones = $stmtRecolecciones->get_result();
    }
    ?>
    
    <div class="container">
        <!-- Botones discretos (solo visible en pantalla) -->
        <div class="no-print btn-container">
            <button class="btn-discreto primary" onclick="window.print()" title="Imprimir documento">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            
            <button class="btn-discreto success" onclick="window.close()" title="Cerrar esta ventana">
                <i class="bi bi-x-circle"></i> Cerrar
            </button>
            
            <button class="btn-discreto" onclick="location.reload()" title="Recargar página">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>

        </div>
        
        <!-- Encabezado del documento -->
        <div class="header">
            <div class="company-info">
                <h1>DEXA LAI</h1>
                <h2>CONTRA RECIBO</h2>
                <p>Sistema de Gestión de Recolecciones</p>
            </div>
        </div>
        
        <!-- Información del contra recibo -->
        <div class="contra-info">
            <table width="100%">
                <tr>
                    <td width="30%"><strong>Contra Recibo:</strong></td>
                    <td width="70%"><?= htmlspecialchars($ContraRecibo) ?></td>
                </tr>
                <tr>
                    <td><strong>Tipo:</strong></td>
                    <td><?= $tipo ?></td>
                </tr>
                <tr>
                    <td><strong>Entidad:</strong></td>
                    <td><?= htmlspecialchars($entidad) ?></td>
                </tr>
                <tr>
                    <td><strong>Codigo:</strong></td>
                    <td><?= htmlspecialchars($codigo) ?></td>
                </tr>
                <tr>
                    <td><strong>Fecha de Emisión:</strong></td>
                    <td><?= date('d/m/Y H:i:s') ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Tabla principal según tipo de contra recibo -->
        <h3><?php
            if ($tipo === 'VENTA') echo 'Ventas del Contra Recibo';
            elseif ($tipo === 'CAPTACION' || $tipo === 'CAPTACION_FLETE') echo 'Captaciones del Contra Recibo';
            else echo 'Recolecciones del Contra Recibo';
        ?></h3>
        
        <?php if ($tipo === 'VENTA'): ?>
            <!-- Vista para contra recibo de VENTA -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Peso (kg)</th>
                        <th>Precio</th>
                        <th>Total</th>
                        <th>Placas</th>
                        <th>Factura Transportista</th>
                        <th>C.R. Asignado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalPeso = 0;
                    $totalMonto = 0;
                    $total_recolecciones = 0;
                    while ($row = $recolecciones->fetch_assoc()):
                        $peso = $row['total_kilos'] ?: 0;
                        $precio = $row['precio_venta'] ?: 0;
                        $total = $peso * $precio;
                        $totalPeso += $peso;
                        $totalMonto += $total;
                        $total_recolecciones++;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['folio_completo'] ?? $row['folio_venta']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_venta'])) ?></td>
                        <td><?= htmlspecialchars($row['nombre_cliente'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['producto'] ?? '') ?></td>
                        <td class="text-right"><?= number_format($peso, 2) ?></td>
                        <td class="text-right">$<?= number_format($precio, 2) ?></td>
                        <td class="text-right text-bold">$<?= number_format($total, 2) ?></td>
                        <td><?= htmlspecialchars($row['placas_fletero'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['factura_transportista'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['folio_cr'] ?? ($row['alias_cr'] ?? '')) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php $totalGeneral = $totalMonto; ?>
                </tbody>
                <tfoot>
                    <tr class="text-bold">
                        <td colspan="4" class="text-right">TOTALES:</td>
                        <td class="text-right"><?= number_format($totalPeso, 2) ?> kg</td>
                        <td></td>
                        <td class="text-right">$<?= number_format($totalMonto, 2) ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>

       <?php elseif ($tipo === 'CAPTACION' || $tipo === 'CAPTACION_FLETE'): ?>
    
    <?php if ($tipo === 'CAPTACION'): ?>
        <!-- CONSULTA PARA CAPTACION NORMAL (detalle de productos) -->
        <?php
        $queryCapt = "SELECT ca.id_captacion, 
                             CONCAT('C-', z.cod, '-', DATE_FORMAT(ca.fecha_captacion, '%y%m'), LPAD(ca.folio,4,'0')) AS folio_completo,
                             ca.fecha_captacion, 
                             p.cod AS cod_proveedor, 
                             p.rs AS nombre_proveedor,
                             pr.nom_pro AS producto, 
                             cd.total_kilos, 
                             cd.pacas_kilos,
                             cd.pacas_cantidad, 
                             cd.numero_factura AS numero_factura_compra, 
                             cd.foliocap AS folio_cr, 
                             cd.aliascap AS alias_cr,
                             pc.precio AS precio_compra_por_kilo
                      FROM captacion ca
                      LEFT JOIN captacion_detalle cd ON ca.id_captacion = cd.id_captacion
                      LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                      LEFT JOIN productos pr ON cd.id_prod = pr.id_prod
                      LEFT JOIN precios pc ON cd.id_pre_compra = pc.id_precio
                      LEFT JOIN zonas z ON ca.zona = z.id_zone
                      WHERE cd.aliascap = ? AND cd.foliocap = ?
                      ORDER BY ca.fecha_captacion, folio_completo";

        $stmtCapt = $conn_mysql->prepare($queryCapt);
        $stmtCapt->bind_param("ss", $Alias, $Folio);
        $stmtCapt->execute();
        $resultCapt = $stmtCapt->get_result();
        ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Producto</th>
                    <th>Pacas</th>
                    <th>Peso (kg)</th>
                    <th>Precio/kg</th>
                    <th>Factura Compra</th>
                    <th>Total</th>
                    <th>C.R. Asignado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalPeso = 0;
                $totalPacas = 0;
                $totalMonto = 0;
                $total_recolecciones = 0;
                
                while ($row = $resultCapt->fetch_assoc()):
                    $total_recolecciones++;
                    $peso = $row['total_kilos'] ?? 0;
                    $pacas = $row['pacas_cantidad'] ?? 0;
                    $precio = $row['precio_compra_por_kilo'] ?? 0;
                    $totalRow = $peso * $precio;
                    
                    $totalPeso += $peso;
                    $totalPacas += $pacas;
                    $totalMonto += $totalRow;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['folio_completo']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['fecha_captacion'])) ?></td>
                    <td><?= htmlspecialchars($row['nombre_proveedor']) ?></td>
                    <td><?= htmlspecialchars($row['producto']) ?></td>
                    <td class="text-right"><?= number_format($pacas, 0) ?></td>
                    <td class="text-right"><?= number_format($peso, 2) ?></td>
                    <td class="text-right">$<?= number_format($precio, 2) ?></td>
                    <td><?= htmlspecialchars($row['numero_factura_compra'] ?? '') ?></td>
                    <td class="text-right text-bold">$<?= number_format($totalRow, 2) ?></td>
                    <td><?= htmlspecialchars($row['folio_cr'] ?? '') ?></td>
                </tr>
                <?php endwhile; ?>
                <?php $totalGeneral = $totalMonto; ?>
            </tbody>
            <tfoot>
                <tr class="text-bold">
                    <td colspan="4" class="text-right">TOTALES:</td>
                    <td class="text-right"><?= number_format($totalPacas, 0) ?></td>
                    <td class="text-right"><?= number_format($totalPeso, 2) ?> kg</td>
                    <td></td>
                    <td class="text-right">$<?= number_format($totalMonto, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
    <?php elseif ($tipo === 'CAPTACION_FLETE'): ?>
    <!-- CONSULTA PARA CAPTACION_FLETE (flete de la captación) -->
    <?php
    // 1. Primero obtener TODA la información básica del flete (sin LIMIT)
    $queryFlete = "SELECT ca.id_captacion, 
                          CONCAT('C-', z.cod, '-', DATE_FORMAT(ca.fecha_captacion, '%y%m'), LPAD(ca.folio,4,'0')) AS folio_completo,
                          ca.fecha_captacion, 
                          p.cod AS cod_proveedor, 
                          p.rs AS nombre_proveedor,
                          cf.foliocap_flete AS folio_cr_flete,
                          cf.aliascap_flete AS alias_cr_flete,
                          t.placas AS placas_fletero,
                          t.razon_so AS nombre_fletero,
                          pf.precio AS precio_flete,
                          pf.tipo AS tipo_flete,
                          cf.numero_factura_flete AS numero_factura_flete
                   FROM captacion ca
                   LEFT JOIN captacion_flete cf ON ca.id_captacion = cf.id_captacion
                   LEFT JOIN proveedores p ON ca.id_prov = p.id_prov
                   LEFT JOIN transportes t ON cf.id_fletero = t.id_transp
                   LEFT JOIN precios pf ON cf.id_pre_flete = pf.id_precio
                   LEFT JOIN zonas z ON ca.zona = z.id_zone
                   WHERE cf.aliascap_flete = ? AND cf.foliocap_flete = ?
                   ORDER BY ca.fecha_captacion, folio_completo";

    $stmtFlete = $conn_mysql->prepare($queryFlete);
    $stmtFlete->bind_param("ss", $Alias, $Folio);
    $stmtFlete->execute();
    $resultFlete = $stmtFlete->get_result();
    
    // Variables para totales
    $totalPeso = 0;
    $totalMonto = 0;
    $total_recolecciones = 0;
    $all_captacion_ids = []; // Para almacenar todas las captaciones
    
    // Preparar la consulta para obtener kilos de una captación (reutilizable)
    $stmtKilos = $conn_mysql->prepare("SELECT SUM(total_kilos) as total_kilos 
                                       FROM captacion_detalle 
                                       WHERE id_captacion = ? AND status = 1");
    ?>
    
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Folio Captación</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Fletero</th>
                <th>Kilos Totales</th>
                <th>Precio Flete</th>
                <th>Monto Flete</th>
                <th>Factura Flete</th>
                <th>C.R. Flete</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($fleteInfo = $resultFlete->fetch_assoc()): ?>
                <?php
                // Calcular la suma de kilos de TODOS los productos de esta captación
                $kilos_totales = 0;
                $monto_flete = 0;
                
                if (!empty($fleteInfo['id_captacion'])) {
                    $captacion_id = $fleteInfo['id_captacion'];
                    $all_captacion_ids[] = $captacion_id;
                    
                    // Sumar todos los kilos de los productos de esta captación
                    $stmtKilos->bind_param('i', $captacion_id);
                    $stmtKilos->execute();
                    $resultKilos = $stmtKilos->get_result();
                    $kilosData = $resultKilos->fetch_assoc();
                    $kilos_totales = (float)($kilosData['total_kilos'] ?? 0);
                    
                    // Calcular monto del flete según el tipo
                    $precio_flete = (float)($fleteInfo['precio_flete'] ?? 0);
                    $tipo_flete = $fleteInfo['tipo_flete'] ?? '';
                    
                    if ($tipo_flete === 'MFT') {
                        // Monto por tonelada: convertir kilos a toneladas
                        $toneladas = $kilos_totales / 1000;
                        $monto_flete = $toneladas * $precio_flete;
                    } else {
                        // Monto fijo
                        $monto_flete = $precio_flete;
                    }
                    
                    // Acumular totales
                    $totalPeso += $kilos_totales;
                    $totalMonto += $monto_flete;
                    $total_recolecciones++;
                }
                ?>
                <tr>
                    <td><?= htmlspecialchars($fleteInfo['folio_completo'] ?? '') ?></td>
                    <td><?= date('d/m/Y', strtotime($fleteInfo['fecha_captacion'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($fleteInfo['nombre_proveedor'] ?? '') ?></td>
                    <td><?= htmlspecialchars($fleteInfo['nombre_fletero'] ?? $fleteInfo['placas_fletero'] ?? '') ?></td>
                    <td class="text-right"><?= number_format($kilos_totales, 2) ?> kg</td>
                    <td class="text-right">
                        <?php if (!empty($tipo_flete) && $tipo_flete === 'MFT'): ?>
                            $<?= number_format($precio_flete, 2) ?> / ton
                        <?php else: ?>
                            $<?= number_format($precio_flete, 2) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-bold">$<?= number_format($monto_flete, 2) ?></td>
                    <td><?= htmlspecialchars($fleteInfo['numero_factura_flete'] ?? '') ?></td>
                    <td><?= htmlspecialchars($fleteInfo['folio_cr_flete'] ?? $ContraRecibo) ?></td>
                </tr>
            <?php endwhile; ?>
            <?php $totalGeneral = $totalMonto; ?>
        </tbody>
        <tfoot>
            <tr class="text-bold">
                <td colspan="4" class="text-right">TOTALES:</td>
                <td class="text-right"><?= number_format($totalPeso, 2) ?> kg</td>
                <td></td>
                <td class="text-right">$<?= number_format($totalMonto, 2) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Mostrar detalle de los productos sumados (opcional, para referencia) -->
    <?php if (!empty($all_captacion_ids)): ?>
    <div style="margin-top: 20px; font-size: 11px; color: #666;">
        <strong>Detalle de productos incluidos en el flete:</strong>
        <table class="table" style="font-size: 11px;">
            <thead>
                <tr>
                    <th>Folio Captación</th>
                    <th>Producto</th>
                    <th>Pacas</th>
                    <th>Kilos</th>
                    <th>Factura</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Crear una lista de placeholders para la consulta IN
                $placeholders = str_repeat('?,', count($all_captacion_ids) - 1) . '?';
                    
                $queryDetalle = "SELECT ca.folio as folio_captacion,
                                        pr.nom_pro AS producto, 
                                        cd.pacas_cantidad, 
                                        cd.total_kilos, 
                                        cd.numero_factura
                                 FROM captacion_detalle cd
                                 LEFT JOIN productos pr ON cd.id_prod = pr.id_prod
                                 LEFT JOIN captacion ca ON cd.id_captacion = ca.id_captacion
                                 WHERE cd.id_captacion IN ($placeholders) AND cd.status = 1
                                 ORDER BY ca.folio, pr.nom_pro";
                
                $stmtDetalle = $conn_mysql->prepare($queryDetalle);
                
                // Vincular parámetros dinámicamente
                $types = str_repeat('i', count($all_captacion_ids));
                $stmtDetalle->bind_param($types, ...$all_captacion_ids);
                $stmtDetalle->execute();
                $resultDetalle = $stmtDetalle->get_result();
                
                while ($detalle = $resultDetalle->fetch_assoc()):
                ?>
                <tr>
                    <td>C-<?= htmlspecialchars($detalle['folio_captacion'] ?? '') ?></td>
                    <td><?= htmlspecialchars($detalle['producto']) ?></td>
                    <td class="text-right"><?= number_format($detalle['pacas_cantidad'] ?? 0, 0) ?></td>
                    <td class="text-right"><?= number_format($detalle['total_kilos'] ?? 0, 2) ?> kg</td>
                    <td><?= htmlspecialchars($detalle['numero_factura'] ?? '') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
<?php endif; ?>

        <?php elseif ($tipo == 'COMPRA'): ?>
            <!-- Vista para contra recibo de COMPRA -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Remisión</th>
                        <th>Factura</th>
                        <th>Producto</th>
                        <th>Peso (kg)</th>
                        <th>Precio Compra</th>
                        <th>Total Compra</th>
                        <th>Cliente</th>
                        <th>Factura de venta</th>
                        <th>Peso en Conpro</th>
                        <th>Estado Inv</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalPeso = 0;
                    $totalMonto = 0;
                    $total_recolecciones = 0;
                    $estado_factura_compra = '';
                    $status_factura_compra = '';

                    while($row = $recolecciones->fetch_assoc()): 
                        $peso = $row['peso_prov'] ?: 0;
                        $precio = $row['precio_compra'] ?: 0;
                        $totalRecoleccion = $peso * $precio;

                        $totalPeso += $peso;
                        $totalMonto += $totalRecoleccion;
                        $total_recolecciones++ ;

                        if ($var_exter == '1') {

                            $BFC0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$codigo' AND subtotal = '$totalRecoleccion' AND folio = '".$row['factura_proveedor']."'");
                            $BFC1 = mysqli_fetch_array($BFC0);
                        if (!empty($BFC1['id'])) {// si existe la factura

                            // sacar estado e historial
                            $binario = decbin($BFC1['statusn']); // hacer binario
                            $estados = [
                                0 => "Pendiente",
                                1 => "Aceptada", 
                                2 => "CR",
                                4 => "Exportada",
                                8 => "Respaldada",
                                16 => "PROGPAGO",
                                32 => "<b class='pagada-text'>Pagada</b>",
                                64 => "<b class='pagada-text'>Pagada</b>",
                                128 => "Rechazada"
                            ];
                            // Obtener valores reales de los bits en 1
                            $valoresReales = [];
                            for ($i = 0; $i < strlen($binario); $i++) {
                                $bit = $binario[$i];
                                $posicion = strlen($binario) - $i - 1;
                                if ($bit === '1') {
                                    $valoresReales[] = pow(2, $posicion);
                                }
                            }

                            // Solo hacer visible en caso de usarlos
                            $estadosActivos = [];
                            $estadosEncontrados = [];
                            foreach ($valoresReales as $valor) {
                                if (isset($estados[$valor])) {
                                    $estadosActivos[] = $estados[$valor];
                                    $estadosEncontrados[] = $valor;
                                //echo "✓ Valor $valor: <strong>" . $estados[$valor] . "</strong><br>";
                                } else {
                                //echo "✗ Valor $valor: Estado no definido<br>";
                                }
                            }
                            // Determinar y mostrar el ESTADO ACTUAL (el valor más alto)
                            //echo "<strong>ESTADO ACTUAL:</strong><br>";
                            if (!empty($valoresReales)) {
                                $estadoActualValor = max($valoresReales);
                                $estadoActualNombre = $estados[$estadoActualValor] ?? "Desconocido";
                                //echo "🔹 <strong>$estadoActualNombre</strong> (Valor: $estadoActualValor)";
                                $status_factura_compra = $estadoActualNombre;// estado real de la factura 
                            } else {
                                $status_factura_compra = 'Pendiente';// estado real de la factura 
                            }
                            // fin de sacar estado e historial
                            $BCFC0 = $inv_mysql->query("SELECT * FROM contrafacturas WHERE idFactura = '".$BFC1['id']."'");
                            $BCFC1 = mysqli_fetch_array($BCFC0);// Buscamos Contra factura
                            
                            if (!empty($BCFC1['id'])) {// si encuentra la contra factura aparecera

                                $estado_factura_compra = ($BCFC1['autorizadaPor'] == '') ? '<b class="danger-text">Sin Autorizar</b>' : '<b>Autorizada</b>' ;
                                
                            }
                        }
                    }

                    $estado_ticket = '';
                    
                    if ($row['nombre_cliente'] == 'DEXA' || $row['nombre_cliente'] == 'BIDASOA' || $row['nombre_cliente'] == 'LAISA') {
                        if ($row['remi_compro'] == 1 && !empty($row['peso_conpro'])) {
                            $estado_ticket .= ' <span style="color: green;">' . number_format((float)$row['peso_conpro'], 2) . ' kg</span>';
                    } else {
                        $estado_ticket .= ' <span style="color: red;">Sin Peso Conpro</span>';
                    }
                    }else {
                    }

                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['folio_completo']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_r'])) ?></td>
                        <td><?= htmlspecialchars($row['cod_proveedor']) ?></td>
                        <td><?=$row['remision']?></td>
                        <td><?= htmlspecialchars($row['factura_proveedor']) ?></td>
                        <td><?= htmlspecialchars($row['producto']) ?></td>
                        <td class="text-right"><?= number_format($peso, 2) ?></td>
                        <td class="text-right">$<?= number_format($precio, 2) ?></td>
                        <td class="text-right text-bold">$<?= number_format($totalRecoleccion, 2) ?></td>
                        <td><?=$row['nombre_cliente']?></td>
                        <td><?=$row['factura_venta']?></td>
                        <td><?=$estado_ticket?></td>
                        <td><?=$estado_factura_compra?> <?=$status_factura_compra?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="text-bold">
                    <td colspan="6" class="text-right">TOTALES:</td>
                    <td class="text-right"><?= number_format($totalPeso, 2) ?> kg</td>
                    <td></td>
                    <td class="text-right">$<?= number_format($totalMonto, 2) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>

    <?php else: ?>
        <!-- Vista para contra recibo de FLETE -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Fletero</th>
                    <th>Factura</th>
                    <th>Producto</th>
                    <th>Peso (kg)</th>
                    <th>Flete</th>
                    <th>Imp. Traslados</th>
                    <th>Imp. Retenidos</th>
                    <th>Total</th>
                    <th>Estado Inv</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalPeso = 0;
                $totalFlete = 0;
                $totalTraslados = 0;
                $totalRetenidos = 0;
                $totalGeneral = 0;
                $total_recolecciones = 0;
                $estado_factura_Flete = '';
                $status_factura_flete = '';

                while($row = $recolecciones->fetch_assoc()): 
                    $peso = $row['peso_fle'] ?: 0;
                    $flete = $row['sub_tot_inv'] ?: 0;
                    $impTraslados = $row['im_tras_inv'] ?: 0;
                    $impRetenidos = $row['im_rete_inv'] ?: 0;
                    $total = $row['total_inv'] ?: 0;

                    $totalPeso += $peso;
                    $totalFlete += $flete;
                    $totalTraslados += $impTraslados;
                    $totalRetenidos += $impRetenidos;
                    $totalGeneral += $total;
                    $total_recolecciones++ ;

                    if ($var_exter == '1') {
                        $BFV0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$codigo' AND subtotal = '$flete' AND folio = '".$row['factura_flete']."'");
                        $BFV1 = mysqli_fetch_array($BFV0);

                        if (!empty($BFV1['id'])) {// si existe la factura

                            // sacar estado e historial
                            $binario = decbin($BFV1['statusn']); // hacer binario
                            $estados = [
                                0 => "Pendiente",
                                1 => "Aceptada", 
                                2 => "CR",
                                4 => "Exportada",
                                8 => "Respaldada",
                                16 => "PROGPAGO",
                                32 => "<b class='pagada-text'>Pagada</b>",
                                64 => "<b class='pagada-text'>Pagada</b>",
                                128 => "Rechazada"
                            ];
                            // Obtener valores reales de los bits en 1
                            $valoresReales = [];
                            for ($i = 0; $i < strlen($binario); $i++) {
                                $bit = $binario[$i];
                                $posicion = strlen($binario) - $i - 1;
                                if ($bit === '1') {
                                    $valoresReales[] = pow(2, $posicion);
                                }
                            }

                            // Solo hacer visible en caso de usarlos
                            $estadosActivos = [];
                            $estadosEncontrados = [];
                            foreach ($valoresReales as $valor) {
                                if (isset($estados[$valor])) {
                                    $estadosActivos[] = $estados[$valor];
                                    $estadosEncontrados[] = $valor;
                                //echo "✓ Valor $valor: <strong>" . $estados[$valor] . "</strong><br>";
                                } else {
                                //echo "✗ Valor $valor: Estado no definido<br>";
                                }
                            }
                            // Determinar y mostrar el ESTADO ACTUAL (el valor más alto)
                            //echo "<strong>ESTADO ACTUAL:</strong><br>";
                            if (!empty($valoresReales)) {
                                $estadoActualValor = max($valoresReales);
                                $estadoActualNombre = $estados[$estadoActualValor] ?? "Desconocido";
                                //echo " <strong>$estadoActualNombre</strong> (Valor: $estadoActualValor)";
                                $status_factura_flete = $estadoActualNombre;// estado real de la factura 
                            } else {
                                $status_factura_flete = 'Pendiente';// estado real de la factura 
                            }
                            // fin de sacar estado e historial

                            $BCFV0 = $inv_mysql->query("SELECT * FROM contrafacturas WHERE idFactura = '".$BFV1['id']."'");
                            $BCFV1 = mysqli_fetch_array($BCFV0);// Buscamos Contra factura

                            if (!empty($BCFV1['id'])) {// si encuentra la contra factura aparecera
                                /// ERROR AQUÍ
                                $estado_factura_Flete = ($BCFV1['autorizadaPor'] == '') ? '<b class="danger-text">Sin Autorizar</b>' : '<b>Autorizada</b>' ;
                                
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['folio_completo']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_r'])) ?></td>
                        <td><?= htmlspecialchars($row['fletero']) ?></td>
                        <td><?= htmlspecialchars($row['factura_flete']) ?></td>
                        <td><?= htmlspecialchars($row['producto']) ?></td>
                        <td class="text-right"><?= number_format($peso, 2) ?></td>
                        <td class="text-right">$<?= number_format($flete, 2) ?></td>
                        <td class="text-right">$<?= number_format($impTraslados, 2) ?></td>
                        <td class="text-right">-$<?= number_format($impRetenidos, 2) ?></td>
                        <td class="text-right text-bold">$<?= number_format($total, 2) ?></td>
                        <td><?=$estado_factura_Flete?> <?=$status_factura_flete?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="text-bold">
                    <td colspan="5" class="text-right">TOTALES:</td>
                    <td class="text-right"><?= number_format($totalPeso, 2) ?> kg</td>
                    <td class="text-right">$<?= number_format($totalFlete, 2) ?></td>
                    <td class="text-right">$<?= number_format($totalTraslados, 2) ?></td>
                    <td class="text-right">-$<?= number_format($totalRetenidos, 2) ?></td>
                    <td class="text-right">$<?= number_format($totalGeneral, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
    
    <!-- Resumen de totales -->
    <div class="totals">
        <h4>Resumen del Contra Recibo</h4>
        <table width="100%">
            <tr>
                <td width="60%">Número de Recolecciones:</td>
                <td width="40%" class="text-right text-bold"> <?=$total_recolecciones?></td>
            </tr>
            <?php if ($tipo == 'COMPRA'): ?>
                <tr>
                    <td>Peso Total:</td>
                    <td class="text-right text-bold"><?= number_format($totalPeso, 2) ?> kg</td>
                </tr>
                <tr>
                    <td>Monto Total del Contra Recibo:</td>
                    <td class="text-right text-bold">$<?= number_format($totalMonto, 2) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td>Peso Total Transportado:</td>
                    <td class="text-right text-bold"><?= number_format($totalPeso, 2) ?> kg</td>
                </tr>
                <tr>
                    <td>Total del Contra Recibo:</td>
                    <td class="text-right text-bold">$<?= number_format($totalGeneral, 2) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p>Documento generado automáticamente por el Sistema de Recolecciones DEXA LAI</p>
        <p>Fecha de generación: <?= date('d/m/Y H:i:s') ?></p>
    </div>
</div>

<script>

        // Mejorar la experiencia de impresión
    document.addEventListener('DOMContentLoaded', function() {
            // Agregar página de cortesía si hay muchas recolecciones
        const rows = document.querySelectorAll('.table tbody tr');
        if (rows.length > 15) {
            const container = document.querySelector('.container');
            const pageBreak = document.createElement('div');
            pageBreak.className = 'page-break';
            container.appendChild(pageBreak);
        }
        
            // Agregar tooltips a los botones
        const buttons = document.querySelectorAll('.btn-discreto');
        buttons.forEach(btn => {
            const title = btn.getAttribute('title');
            if (title) {
                btn.addEventListener('mouseenter', function() {
                        // Podrías agregar un tooltip personalizado aquí si quieres
                });
            }
        });
    });
</script>
</body>
</html>