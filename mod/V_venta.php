<?php
// Obtener ID de la venta a visualizar

$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos de la venta con información relacionada
$venta = [];
if ($id_venta > 0) {
    $query = "SELECT v.*,
    v.factura AS factura_v, 
    v.fact_fle AS factura_f,
    v.d_prov AS documento_venta,
    v.d_fletero AS documento_flete,
    v.im_tras_inv AS im_tras_inv,
    v.im_rete_inv AS im_rete_inv,
    v.total_inv AS total_inv,
    v.aliasInv AS Alias_Contra,
    v.acciones AS autorizar,
    v.folio_contra AS folio_contra, 
    z.nom AS nombre_zona,
    Z.PLANTA AS nom_planta,
    c.id_cli AS id_cliente,
    c.cod AS cod_cliente, 
    c.rs AS nombre_cliente,
    c.rfc AS rfc_cliente,
    d.cod_al AS cod_direccion, 
    d.noma AS nombre_direccion, 
    d.tel AS telefono_direccion,
    d.email AS email_direccion,
    d.calle, d.numext, d.numint, d.colonia, 
    d.mun AS municipio, d.estado, d.c_postal, d.pais, d.tel AS telefono, d.email, d.atencion,
    co.id_compra AS id_compra,
    co.fact AS fact_compra,
    co.nombre AS nombre_compra,
    co.tara AS tara_compra,
    co.bruto AS bruto_compra,
    co.neto AS neto_compra,
    co.pres AS precio_compra,
    co.fecha AS fecha_compra,
    co.factura AS factura_compra,
    co.d_prov AS documento_compra,
    co.aliasInv AS Alias_Contra_com,
    co.folio_contra AS folio_contra_com,
    t.placas AS placas_transporte,
    t.razon_so AS transportista,
    t.correo AS correo_transporte,
    t.linea AS linea_transporte,
    t.tipo AS tipo_transporte,
    t.chofer AS chofer_transporte,
    p.cod AS cod_producto,
    p.nom_pro AS nombre_producto,
    pr.cod AS codigo_proveedor,
    pr.rs AS rs_proveedor,
    pr.rfc AS RFC_proveedor,
    dp.noma AS nombre_almacen_compra,
    dp.cod_al AS cod_almacen_compra,
    u.nombre AS nombre_usuario,
    u.correo AS email_usuario
    FROM ventas v
    LEFT JOIN zonas z ON v.zona = z.id_zone
    LEFT JOIN clientes c ON v.id_cli = c.id_cli
    LEFT JOIN direcciones d ON v.id_direc = d.id_direc
    LEFT JOIN compras co ON v.id_compra = co.id_compra
    LEFT JOIN proveedores pr ON co.id_prov = pr.id_prov
    LEFT JOIN direcciones dp ON co.id_direc = dp.id_direc
    LEFT JOIN transportes t ON co.id_transp = t.id_transp
    LEFT JOIN productos p ON v.id_prod = p.id_prod
    LEFT JOIN usuarios u ON v.id_user = u.id_user
    WHERE v.id_venta = ?";

    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
    $ExVenta = ($venta['ex'] == 2) ? '<i class="bi bi-filetype-svg text-teal bg-teal bg-opacity-10 rounded-1 p-auto" style="font-size: 15px" title="Cargado desde Excel"></i>' : '' ;
}

if (!$venta) {
    alert("Venta no encontrada", 0, "ventas");
    exit();
}

$Auto1 = ($venta['autorizar'] == 0 ) ? '' : 'style="display: none"' ;// si la venta esta autorizada
// link para avance
$mesesA = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");

$numero_mesA = date('m', strtotime($venta['fecha'])) - 1;

$anoA = date('Y', strtotime($venta['fecha']));

$url = 'https://glama.esasacloud.com/doctos/'.$venta['nom_planta'].'/FACTURAS/'.$anoA.'/'.$mesesA[$numero_mesA].'/SIGN_'.$venta['factura_v'].'.pdf';

// Función para validar si existe el archivo remoto
function urlExists($url) {
    $headers = @get_headers($url);
    return ($headers && strpos($headers[0], '200') !== false);
}

$existePDF = urlExists($url);
// Calcular totales y utilidad
$total_compra = $venta['neto_compra'] * $venta['precio_compra'];
$total_venta = $venta['precio'] * $venta['peso_cliente'];
$utilidad = $total_venta - $venta['costo_flete'] - $total_compra;
$utilidadEs = $total_venta - $total_compra - $venta['total_inv'];


if (isset($_POST['guardar01'])) {
    try {
        $facturaV = $_POST['factura_ven'] ?? '';

        $buscar0 = $conn_mysql->query("SELECT * FROM ventas WHERE factura =  '$facturaV' AND status = '1'");
        $buscar1 = mysqli_fetch_array($buscar0);

        if (empty($buscar1['id_venta'])) {

            $conn_mysql->query("UPDATE ventas SET factura = '$facturaV' WHERE id_venta = '$id_venta'");
            alert("Factura agregada", 1, "V_venta&id=$id_venta");
        }else {
            alert("Factura utilizada en otra venta", 2, "V_venta&id=$id_venta");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "V_venta&id=$id_venta");
    }
}
if (isset($_POST['guardar02'])) {
    try {
        $factura_fle = $_POST['factura_fle'] ?? '';

        $buscar0 = $conn_mysql->query("SELECT * FROM ventas WHERE fact_fle =  '$factura_fle' AND status = '1'");
        $buscar1 = mysqli_fetch_array($buscar0);

        if (empty($buscar1['id_venta'])) {

            $conn_mysql->query("UPDATE ventas SET fact_fle = '$factura_fle' WHERE id_venta = '$id_venta'");
            alert("Factura de flete agregada", 1, "V_venta&id=$id_venta");
        }else {
            alert("Factura de flete utilizada en otra venta", 2, "V_venta&id=$id_venta");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "V_venta&id=$id_venta");
    }
}


?>

<div class="container mt-2">
    <div class="card">
        <!-- Header con información resumida (se mantiene igual) -->
        <div class="card-header encabezado-col text-white">
            <div class="d-flex justify-content-between align-items-center row">
                <div class="col-12 col-md-6">
                    <h4 class="mb-1 fw-light">DETALLE DE VENTA</h4>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-white text-col fs-6 py-2 px-3">
                            <i class="bi bi-tag-fill me-2"></i><?= htmlspecialchars($venta['nombre'])?>
                        </span>
                        <span class="small">
                            <i class="bi bi-calendar-event me-1"></i> <?= date('d/m/Y', strtotime($venta['fecha'])) ?>
                            <i class="bi bi-person-fill ms-3 me-1"></i> <?= htmlspecialchars($venta['nombre_usuario'])?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-1 col-12 col-md-6 d-flex justify-content-end">
                    <span class="badge bg-<?= $venta['status'] ? 'success' : 'danger' ?> py-2 px-3 fs-6 rounded-3">
                        <?= $venta['status'] ? 'ACTIVA' : 'INACTIVA' ?>
                    </span>
                    <button id="btnCerrar" class="btn btn-sm rounded-3 btn-outline-light">Cerrar Ventana</button>
                    <script>
                      document.getElementById('btnCerrar').addEventListener('click', function() {
                        window.close();
                    });
                </script>
                <a <?= $perm['Clien_Editar'];?> <?=$Auto1?> href="?p=E_venta&id=<?= $id_venta ?>" class="btn btn-sm rounded-3 btn-light" title="Editar">
                    <i class="bi bi-pencil me-1"></i>
                </a>
                <?php
                if ($venta['autorizar'] == 1) {
                    ?>
                    <span class="badge bg-teal py-2 px-3 fs-6 rounded-3">
                        Autorizado
                    </span>
                    <button class="btn btn-teal btn-sm rounded-3" onclick="generarPDF()" title="Generar PDF">
                        <i class="bi bi-filetype-pdf"></i>
                    </button>

                    <?php
                }
                ?>
                <?php if ($venta['status']): ?>
                    <button <?= $perm['ACT_DES'];?><?=$Auto1?> class="btn btn-sm rounded-3 btn-danger desactivar-venta-btn" data-id="<?= $id_venta ?>" title="Desactivar">
                        <i class="bi bi-x-circle me-1"></i>
                    </button>
                <?php else: ?>
                    <button <?= $perm['ACT_DES'];?> class="btn btn-sm rounded-3 btn-success activar-venta-btn" data-id="<?= $id_venta ?>" title="Activar">
                        <i class="bi bi-check-circle me-1"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
       <div class="row g-4">
        <!-- Columna izquierda - Información principal -->
        <div class="col-lg-8">
            <!-- Tarjeta de información general -->
            <div class="card shadow-sm border-1 bg-transparent">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Información de venta -->
                        <div class="col-md-6">
                            <h5 class="mb-3 text-primary"><i class="bi bi-receipt me-2"></i> Información de Venta <?=$ExVenta?></h5>

                            <div class="mb-3">
                                <label class="small text-muted mb-1">Cliente</label>
                                <h6 class="mb-3">
                                    <a class="text-decoration-none text-primary" href="?p=V_cliente&id=<?=$venta['id_cliente']?>">
                                        <?= htmlspecialchars($venta['nombre_cliente']) ?>
                                    </a>
                                </h6>
                            </div>
                            <div class="list-group list-group-flush bg-transparent">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Remisión</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['fact']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Factura de venta</span>
                                    <div>
                                        <?php if(empty($venta['factura_v'])): ?>
                                            <button type="button" class="btn btn-sm btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                <i class="bi bi-plus-lg"></i> Agregar
                                            </button>
                                        <?php else: ?>
                                            <?php if($existePDF): ?>
                                                <!-- PDF disponible -->
                                                <a href="<?=$url?>" target="_blank" class="btn btn-sm btn-success rounded-3">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i> 
                                                    <?= $venta['factura_v'] ?>
                                                </a>
                                            <?php else: ?>
                                                <!-- PDF no disponible -->
                                                <button type="button" class="btn btn-sm btn-danger rounded-3" disabled>
                                                    <i class="bi bi-exclamation-triangle-fill"></i> 
                                                    <?= $venta['factura_v'] ?> (No disponible)
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Factura del flete</span>
                                    <div>
                                        <?php if(empty($venta['factura_f'])): ?>
                                            <!-- Botón comentado según código original -->
                                        <?php else: ?>
                                            <a href="<?=$invoiceLK.$venta['documento_flete'].".pdf"?>" target="_blank" class="text-decoration-none text-success">
                                                <i class="bi bi-file-earmark-fill me-1"></i>
                                                <strong><?= $venta['factura_f'] ?></strong>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($venta['Alias_Contra'])): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                        <span class="text-muted">Contra Recibo de flete</span>
                                        <strong><span class="text-success"><?=$venta['Alias_Contra']."-".$venta['folio_contra']?></span></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Código</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['cod_cliente']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">RFC</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['rfc_cliente']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Bodega</span>
                                    <div class="text-end">
                                        <strong class="text-body"><?= htmlspecialchars($venta['nombre_direccion']) ?></strong>
                                        <div class="text-muted small">(<?= htmlspecialchars($venta['cod_direccion']) ?>)</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Zona</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['nombre_zona']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Información de compra -->
                        <div class="col-md-6">
                            <h5 class="mb-3 text-primary"><i class="bi bi-cart-check me-2"></i> Información de Compra</h5>

                            <div class="mb-3">
                                <label class="small text-muted mb-1">Proveedor</label>
                                <h6 class="mb-3">
                                    <a class="text-decoration-none text-primary" href="?p=V_compra&id=<?=$venta['id_compra']?>">
                                        <?= htmlspecialchars($venta['rs_proveedor']) ?>
                                    </a>
                                </h6>
                            </div>

                            <div class="list-group list-group-flush bg-transparent">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Remisión</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['fact_compra']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Factura de compra</span>
                                    <div>
                                        <?php if (!empty($venta['factura_compra'])): ?>
                                            <a href="<?=$invoiceLK.$venta['documento_compra'].".pdf"?>" target="_blank" class="text-decoration-none text-success">
                                                <i class="bi bi-file-earmark-fill me-1"></i>
                                                <strong><?=$venta['factura_compra'] ?></strong>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Código</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['codigo_proveedor']) ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">RFC</span>
                                    <strong class="text-body"><?= htmlspecialchars($venta['RFC_proveedor']) ?></strong>
                                </div>
                                <?php
                                if (!empty($venta['folio_contra_com'])) {
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                        <span class="text-muted">Contra Recibo de compra</span>
                                        <strong class="text-success"><?=$venta['Alias_Contra_com'].'-'.$venta['folio_contra_com'] ?></strong>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Bodega</span>
                                    <div class="text-end">
                                        <strong class="text-body"><?= htmlspecialchars($venta['nombre_almacen_compra']) ?></strong>
                                        <div class="text-muted small">(<?= htmlspecialchars($venta['cod_almacen_compra']) ?>)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de producto y transporte -->
            <div class="row mt-4">
                <!-- Producto -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-1 h-100 bg-transparent">
                        <div class="card-body">
                            <h5 class="mb-3 text-primary"><i class="bi bi-box-seam me-2"></i> Producto</h5>

                            <div class="text-center mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-6 mb-2">
                                    <?= htmlspecialchars($venta['cod_producto']) ?>
                                </span>
                                <h6 class="fw-bold"><?= htmlspecialchars($venta['nombre_producto']) ?></h6>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="border rounded p-2 bg-body-secondary">
                                        <span class="d-block text-muted small">Peso Compra</span>
                                        <span class="fw-bold text-body"><?= number_format($venta['neto_compra'], 2) ?> kg</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2 bg-body-secondary">
                                        <span class="d-block text-muted small">Peso Cliente</span>
                                        <span class="fw-bold text-body"><?= number_format($venta['peso_cliente'], 2) ?> kg</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 rounded bg-<?= ($venta['neto_compra'] - $venta['peso_cliente']) >= 0 ? 'success' : 'danger' ?> bg-opacity-10 text-center border">
                                <span class="d-block text-muted small">Diferencia</span>
                                <span class="fw-bold fs-6 text-body"><?= number_format($venta['neto_compra'] - $venta['peso_cliente'], 2) ?> kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transporte -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-1 h-100 bg-transparent">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="text-primary mb-0"><i class="bi bi-truck me-2"></i> Transporte</h5>
                                <?php if (empty($venta['factura_f'])): ?>
                                    <button class="btn btn-sm btn-warning rounded-5" data-bs-toggle="modal" data-bs-target="#correoModal" title="Enviar correo a transportista">
                                        <i class="bi bi-envelope-arrow-up"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-body-secondary text-center">
                                        <i class="bi bi-upc-scan fs-5 text-primary mb-2"></i>
                                        <h6 class="mb-1">ID fletero</h6>
                                        <p class="fw-bold mb-0 text-body"><?= htmlspecialchars($venta['placas_transporte']) ?></p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-body-secondary text-center">
                                        <i class="bi bi-truck-front-fill fs-5 text-primary mb-2"></i>
                                        <h6 class="mb-1">Transportista</h6>
                                        <p class="fw-bold mb-0 text-body"><?=$venta['transportista'] ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha - Resumen financiero -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-1 sticky-top bg-transparent" style="top: 20px;">
                <div class="card-body">
                    <h5 class="mb-4 text-primary"><i class="bi bi-cash-stack me-2"></i> Resumen Financiero</h5>

                    <h6 class="mb-3 text-uppercase small text-muted border-bottom pb-2">Compra</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Peso</span>
                        <span class="fw-bold text-body"><?= number_format($venta['neto_compra'], 2) ?> kg</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Precio unitario</span>
                        <span class="fw-bold text-body">$<?= number_format($venta['precio_compra'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 py-2 border-bottom">
                        <span class="text-muted">Total compra</span>
                        <span class="fw-bold text-danger">$<?= number_format($total_compra, 2) ?></span>
                    </div>

                    <h6 class="mb-3 mt-4 text-uppercase small text-muted border-bottom pb-2">Venta</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Peso</span>
                        <span class="fw-bold text-body"><?= number_format($venta['peso_cliente'], 2) ?> kg</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Precio unitario</span>
                        <span class="fw-bold text-body">$<?= number_format($venta['precio'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 py-2 border-bottom">
                        <span class="text-muted">Total venta</span>
                        <span class="fw-bold text-success">$<?= number_format($total_venta, 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Costo flete</span>
                        <span class="fw-bold text-danger">$<?= number_format($venta['costo_flete'], 2) ?></span>
                    </div>

                    <div class="p-3 rounded bg-<?= $utilidad >= 0 ? 'success' : 'danger' ?> bg-opacity-10 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-body">UTILIDAD ESTIMADA</span>
                            <span class="fs-5 fw-bold text-<?= $utilidad >= 0 ? 'success' : 'danger' ?>">$<?= number_format($utilidad, 2) ?></span>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-<?= $utilidad >= 0 ? 'success' : 'danger' ?>" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <?php if (!empty($venta['factura_f'])): ?>
                        <h6 class="mb-3 mt-4 text-uppercase small text-muted border-bottom pb-2">Flete en invoice</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Costo flete</span>
                            <span class="fw-bold text-body">$<?= number_format($venta['costo_flete'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Impuestos Traslados</span>
                            <span class="text-body">$<?= number_format($venta['im_tras_inv'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Impuestos Retenidos</span>
                            <span class="text-body">- $<?= number_format($venta['im_rete_inv'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total</span>
                            <span class="fw-bold text-danger">$<?= number_format($venta['total_inv'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<!-- Modal -->
<div class="modal fade" id="correoModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <form id="form" action="correo.php" method="post" data-validate="parsley" data-trigger="change">
          <div class="modal-header bg-warning text-dark">
            <h1 class="modal-title fs-5" id="exampleModalLabel"><i class="bi bi-envelope-arrow-up"></i> Enviar correo al fletero </h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <h6 class="pb-2 text-uppercase small text-muted border-bottom">Datos del fletero</h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Id del fletero</span>
                <span class="fw-bold"><?= $venta['placas_transporte'] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Correo</span>
                <span class="fw-bold"><?= $venta['correo_transporte'] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Remision de venta</span>
                <span class="fw-bold"><?= $venta['fact'] ?></span>
            </div>
        </div>


        <input type="hidden" name="id_venta" value="<?=$id_venta?>">
        <input type="hidden" name="id_trans" value="<?=$venta['placas_transporte']?>">
        <input type="hidden" name="correoTr" value="<?=$venta['correo_transporte']?>">
        <input type="hidden" name="remisionV" value="<?=$venta['fact']?>">
        <input type="hidden" name="link_inv" value="<?=$invoiceLK?>">
        <div class="modal-footer">
            <?PHP
            if (empty($venta['correo_transporte'])) {
                ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <?php
            }else{
                ?>
                <input type="submit" class="btn btn-warning" value="Enviar mensaje" name="enviar">
                <?php
            }
            ?>

        </div>

    </form>
</div>
</div>
</div>
<script>
    async function generarPDF() {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

        // Configuración inicial
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 12; // Reducido para ahorrar espacio
        let y = 20; // Inicio más arriba

        // Paleta de colores más minimalista
        const primaryColor = [30, 58, 138];
        const secondaryColor = [192, 57, 43];
        const accentColor = [52, 152, 219];
        const lightGray = [245, 245, 245];
        const borderGray = [220, 220, 220];
        const textDark = [51, 51, 51];
        const textLight = [119, 119, 119];

        // Variables para control de paginación
        let currentPage = 1;
        const maxY = pageHeight - 15; // Más espacio utilizable

        // Función para agregar nueva página si es necesario
        const checkPageBreak = (neededSpace = 8) => {
            if (y + neededSpace > maxY) {
                doc.addPage();
                currentPage++;
                y = 20;

                // Encabezado simplificado para páginas adicionales
                doc.setFillColor(...primaryColor);
                doc.rect(0, 0, pageWidth, 15, 'F');

                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(255, 255, 255);
                doc.text(`Detalle Venta - ${"<?= htmlspecialchars($venta['fact'])?>"}`, margin, 10);
                doc.text(`Página ${currentPage}`, pageWidth - margin, 10, { align: 'right' });
            }
        };

        // Función para agregar texto con estilos
        const addText = (text, x, style = {}) => {
            const { 
                size = 8, // Tamaño de fuente más pequeño
                color = textDark, 
                align = 'left', 
                font = 'normal'
            } = style;

            checkPageBreak(size / 2);

            doc.setFontSize(size);
            doc.setFont(undefined, font);
            doc.setTextColor(...color);
            doc.text(text, x, y, { align });
        };

        // Función para agregar sección optimizada
        const addSection = (title) => {
            checkPageBreak(8);

            // Título de la sección más compacto
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(...primaryColor);
            doc.text(title.toUpperCase(), margin, y + 2);
            
            // Línea debajo del título más delgada
            doc.setDrawColor(...primaryColor);
            doc.setLineWidth(0.3);
            doc.line(margin, y + 3, margin + 30, y + 3);

            y += 6;
        };

        // Función para agregar campos en dos columnas (más compacta)
        const addTwoColumnFields = (fields) => {
            checkPageBreak(Math.ceil(fields.length / 2) * 5);
            
            const columnWidth = (pageWidth - margin * 2) / 2;
            let currentY = y;
            
            fields.forEach((field, index) => {
                const column = index % 2;
                const row = Math.floor(index / 2);
                const x = margin + (column * columnWidth);
                const fieldY = currentY + (row * 5); // Espaciado reducido
                
                // Etiqueta
                doc.setFontSize(7);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(...textLight);
                doc.text(field.label + ':', x, fieldY + 2);
                
                // Valor
                doc.setFontSize(8);
                doc.setFont(undefined, field.valueBold ? 'bold' : 'normal');
                doc.setTextColor(...(field.color || textDark));
                
                // Calcular posición x para alineación derecha en la columna
                const valueX = x + columnWidth - 3;
                doc.text(field.value, valueX, fieldY + 2, { align: 'right' });
            });
            
            // Ajustar Y según la cantidad de filas necesarias
            const rowsNeeded = Math.ceil(fields.length / 2);
            y += rowsNeeded * 5 + 3;
        };

        // Función para crear tablas compactas
        const addCompactTable = (title, rows, totals = []) => {
            checkPageBreak(10 + (rows.length * 5) + (totals.length * 6));
            
            if (title) addSection(title);
            
            const tableWidth = pageWidth - margin * 2;
            const columnWidth = tableWidth / 2.05;
            const headerY = y;
            
            // Encabezado de tabla más compacto
            doc.setFillColor(...primaryColor);
            doc.rect(margin, headerY, tableWidth, 5, 'F');
            
            doc.setFontSize(8);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255);
            doc.text("Concepto", margin + 3, headerY + 3.5);
            doc.text("Cantidad", margin + columnWidth, headerY + 3.5);
            doc.text("Monto", margin + columnWidth * 2, headerY + 3.5, { align: 'right' });
            
            y += 5;
            
            // Filas de datos más compactas
            let rowY = y;
            rows.forEach((row, index) => {
                // Fondo alternado para mejor legibilidad
                if (index % 2 === 0) {
                    doc.setFillColor(...lightGray);
                    doc.rect(margin, rowY, tableWidth, 5, 'F');
                }
                
                // Concepto
                doc.setFontSize(8);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(...textDark);
                doc.text(row.concept, margin + 3, rowY + 3.5);
                
                // Cantidad
                doc.setTextColor(...textDark);
                doc.text(row.quantity, margin + columnWidth, rowY + 3.5);
                
                // Monto
                doc.setFont(undefined, row.bold ? 'bold' : 'normal');
                doc.setTextColor(...(row.color || textDark));
                doc.text(row.amount, margin + columnWidth * 2, rowY + 3.5, { align: 'right' });
                
                rowY += 5;
            });
            
            y = rowY;
            
            // Totales más compactos
            if (totals.length > 0) {
                y += 2;
                
                totals.forEach(total => {
                    doc.setFontSize(9);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(...(total.color || primaryColor));
                    
                    doc.text(total.label, margin + columnWidth - 5, y + 3.5, { align: 'right' });
                    doc.text(total.value, margin + columnWidth * 2, y + 3.5, { align: 'right' });
                    
                    y += 5;
                });
            }
            
            y += 3;
        };

        // Función para destacado financiero optimizado
        const addFinancialHighlight = (label, value, isPositive = true) => {
            checkPageBreak(12);

            const bgColorR = isPositive ? 237 : 253;
            const bgColorG = isPositive ? 247 : 237;
            const bgColorB = isPositive ? 237 : 237;

            const borderColorR = isPositive ? 46 : 198;
            const borderColorG = isPositive ? 125 : 40;
            const borderColorB = isPositive ? 50 : 40;

            const textColorR = isPositive ? 46 : 198;
            const textColorG = isPositive ? 125 : 40;
            const textColorB = isPositive ? 50 : 40;

            doc.setFillColor(bgColorR, bgColorG, bgColorB);
            doc.rect(margin, y, pageWidth - margin * 2, 10, 'F');

            // Borde (CORREGIDO: usar setDrawColor con valores separados)
            doc.setDrawColor(borderColorR, borderColorG, borderColorB);
            doc.setLineWidth(0.5);
            doc.rect(margin, y, pageWidth - margin * 2, 10);

            // Texto
            doc.setFontSize(11);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(textColorR, textColorG, textColorB);
            doc.text(label, margin + 5, y + 7);

            doc.text(value, pageWidth - margin - 5, y + 7, { align: 'right' });

            y += 15;
        };

        // ===== COMIENZO DEL CONTENIDO OPTIMIZADO =====

        // Encabezado principal más compacto
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, pageWidth, 35, 'F');

        // Título principal
        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        doc.setFont(undefined, 'bold');
        doc.text('DETALLE DE VENTA', margin, 15);

        // Información de identificación compacta
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text(`Remisión: ${"<?= htmlspecialchars($venta['fact'])?>"}`, margin, 22);
        
        const fecha = "<?= date('d/m/Y', strtotime($venta['fecha'])) ?>";
        const usuario = "<?= htmlspecialchars($venta['nombre_usuario'])?>";
        doc.text(`Fecha: ${fecha} | Usuario: ${usuario}`, margin, 28);

        y = 40;

        // ===== INFORMACIÓN COMBINADA EN SECCIONES COMPACTAS =====

        // Sección de Información General
        addSection('Información General');

        // Combinar datos de cliente y proveedor en una sola sección de dos columnas
        const generalFields = [
            { label: 'Cliente', value: "<?= htmlspecialchars($venta['nombre_cliente']) ?>", valueBold: true },
            { label: 'Código Cliente', value: "<?= htmlspecialchars($venta['cod_cliente']) ?>" },
            { label: 'Proveedor', value: "<?= htmlspecialchars($venta['rs_proveedor']) ?>", valueBold: true },
            { label: 'Código Proveedor', value: "<?= htmlspecialchars($venta['codigo_proveedor']) ?>" },
            { label: 'Producto', value: "<?= htmlspecialchars($venta['nombre_producto']) ?>", valueBold: true },
            { label: 'Código Producto', value: "<?= htmlspecialchars($venta['cod_producto']) ?>" },
            { label: 'Transportista', value: "<?= $venta['transportista'] ?>", valueBold: true },
            { label: 'ID Fletero', value: "<?= htmlspecialchars($venta['placas_transporte']) ?>" }
        ];
        
        addTwoColumnFields(generalFields);

        // Sección de Documentación
        addSection('Documentación');

        const documentacionFields = [];
        <?php if (!empty($venta['factura_v'])): ?>
            documentacionFields.push({ label: 'Factura venta', value: "<?= $venta['factura_v'] ?>" });
        <?php endif; ?>
        <?php if (!empty($venta['factura_f'])): ?>
            documentacionFields.push({ label: 'Factura flete', value: "<?= $venta['factura_f'] ?>" });
        <?php endif; ?>
        <?php if (!empty($venta['Alias_Contra'])): ?>
            documentacionFields.push({ label: 'CR Flete', value: "<?= $venta['Alias_Contra'] . '-' . $venta['folio_contra'] ?>" });
        <?php endif; ?>
        <?php if (!empty($venta['factura_compra'])): ?>
            documentacionFields.push({ label: 'Factura compra', value: "<?= $venta['factura_compra'] ?>" });
        <?php endif; ?>
        <?php if (!empty($venta['folio_contra_com'])): ?>
            documentacionFields.push({ label: 'CR Compra', value: "<?= $venta['Alias_Contra_com'] . '-' . $venta['folio_contra_com'] ?>" });
        <?php endif; ?>
        
        if (documentacionFields.length > 0) {
            addTwoColumnFields(documentacionFields);
        }

        // ===== RESUMEN FINANCIERO COMBINADO =====
        addSection('Resumen Financiero');

        // Tabla combinada de compra y venta
        const financialRows = [
            {
                concept: "Compra",
                quantity: `${"<?= number_format($venta['neto_compra'], 2) ?>"} kg`,
                amount: `$${"<?= number_format($venta['precio_compra'], 2) ?>"}`,
                bold: false
            },
            {
                concept: "Venta",
                quantity: `${"<?= number_format($venta['peso_cliente'], 2) ?>"} kg`,
                amount: `$${"<?= number_format($venta['precio'], 2) ?>"}`,
                bold: false
            },
            {
                concept: "Costo flete",
                quantity: "-",
                amount: `- $${"<?= number_format($venta['costo_flete'], 2) ?>"}`,
                color: [192, 57, 43],
                bold: false
            }
        ];
        
        const financialTotals = [
            {
                label: "Total compra:",
                value: `$${"<?= number_format($total_compra, 2) ?>"}`,
                color: [192, 57, 43]
            },
            {
                label: "Total venta:",
                value: `$${"<?= number_format($total_venta, 2) ?>"}`,
                color: [46, 125, 50]
            }
        ];
        
        addCompactTable('', financialRows, financialTotals);

        // Utilidad
        const utilidad = <?= $utilidad ?>;
        addFinancialHighlight(
            'UTILIDAD ESTIMADA', 
        `$${"<?=number_format($utilidad, 2)?>"}`, 
        utilidad >= 0
        );

        // ===== DETALLES ADICIONALES SOLO SI EXISTEN =====
        <?php if (!empty($venta['factura_f'])): ?>
            const fleteRows = [
                {
                    concept: "Costo flete",
                    quantity: "-",
                    amount: `$${"<?= number_format($venta['costo_flete'], 2) ?>"}`,
                    bold: false
                },
                {
                    concept: "Impuestos Traslados",
                    quantity: "-",
                    amount: `$${"<?= number_format($venta['im_tras_inv'], 2) ?>"}`,
                    bold: false
                },
                {
                    concept: "Impuestos Retenidos",
                    quantity: "-",
                    amount: `- $${"<?= number_format($venta['im_rete_inv'], 2) ?>"}`,
                    color: [192, 57, 43],
                    bold: false
                }
            ];
            
            const fleteTotals = [
                {
                    label: "Total:",
                    value: `$${"<?= number_format($venta['total_inv'], 2) ?>"}`,
                    color: [192, 57, 43]
                }
            ];
            
            addCompactTable('Detalle Flete', fleteRows, fleteTotals);
        <?php endif; ?>

        // ===== PIE DE PÁGINA MÁS COMPACTO =====
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);

            // Línea separadora
            doc.setDrawColor(...borderGray);
            doc.setLineWidth(0.2);
            doc.line(margin, pageHeight - 12, pageWidth - margin, pageHeight - 12);

            // Información del documento
            doc.setFontSize(6);
            doc.setTextColor(...textLight);
            doc.text(`Generado el ${new Date().toLocaleString()}`, pageWidth / 2, pageHeight - 8, { align: 'center' });

            // Número de página
            doc.text(`Página ${i} de ${pageCount}`, pageWidth - margin, pageHeight - 8, { align: 'right' });

            // Información de confidencialidad
            doc.text('Confidencial - Uso interno', margin, pageHeight - 8);
        }

        // Guardar PDF
        doc.save(`Detalle_Venta_${"<?= htmlspecialchars($venta['fact'])?>"}.pdf`);

    } catch (error) {
        console.error('Error al generar PDF:', error);
        alert('Error al generar el PDF. Por favor, intente nuevamente.');
    }
}
</script>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Agregar Numero de factura</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="forms-sample" method="post" action="">
                <div class="modal-body">
                    <label for="factura_ven" class="form-label">Factura</label>
                    <input name="factura_ven" type="text" class="form-control" id="factura" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="exampleModal1" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-purple text-white">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Agregar Numero de factura de flete</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="forms-sample" method="post" action="">
                <div class="modal-body">
                    <label for="factura_fle" class="form-label">Factura</label>
                    <input name="factura_fle" type="text" class="form-control" id="factura" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar02" class="btn btn-purple">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal de confirmación mejorado -->
<div class="modal fade" id="confirmVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-<?= $venta['status'] ? 'danger' : 'success' ?> text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i> Confirmar acción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalVentaMessage">¿Estás seguro de que deseas desactivar esta venta?</p>
                <input type="hidden" id="ventaId">
                <input type="hidden" id="ventaAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-<?= $venta['status'] ? 'danger' : 'success' ?>" id="confirmVentaBtn">
                    <i class="bi bi-check-circle me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast para notificaciones -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header encabezado-col">
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<script>
    $(document).ready(function() {
    // Configurar modal para desactivar/activar ventas
        $(document).on('click', '.desactivar-venta-btn', function() {
            const id = $(this).data('id');
            $('#ventaId').val(id);
            $('#ventaAccion').val('desactivar');
            $('#modalVentaMessage').text('¿Estás seguro de que deseas desactivar esta venta?');
            $('#confirmVentaModal').modal('show');
        });

        $(document).on('click', '.activar-venta-btn', function() {
            const id = $(this).data('id');
            $('#ventaId').val(id);
            $('#ventaAccion').val('activar');
            $('#modalVentaMessage').text('¿Estás seguro de que deseas reactivar esta venta?');
            $('#confirmVentaModal').modal('show');
        });

    // Confirmar acción para ventas
        $('#confirmVentaBtn').click(function() {
            const id = $('#ventaId').val();
            const accion = $('#ventaAccion').val();

            $.post('actualizar_status_ven.php', {
                id: id,
                accion: accion,
                tabla: 'ventas'
            }, function(response) {
                if (response.success) {
                // Mostrar notificación de éxito
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html(`<i class="bi bi-check-circle-fill text-success me-2"></i> Venta ${accion === 'activar' ? 'activada' : 'desactivada'} correctamente`);
                    toast.show();

                // Recargar después de 1.5 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                // Mostrar notificación de error
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html(`<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error: ${response.message}`);
                    toast.show();
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                $('#toastMessage').html(`<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Error en la solicitud: ${textStatus}`);
                toast.show();
            });

            $('#confirmVentaModal').modal('hide');
        });
    });
</script>
