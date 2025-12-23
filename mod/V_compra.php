<?php
// Obtener el ID de la compra a visualizar
$id_compra = $_GET['id'] ?? 0;
$perm = permisos($TipoUserSession, $perMi);
// Consultar los datos completos de la compra
$compraQuery = $conn_mysql->prepare("
    SELECT c.*, 
    c.factura AS factura_c,
    c.d_prov AS documento_factura,
    c.acciones AS autorizar,
    z.nom AS nombre_zona,
    p.cod AS cod_proveedor,
    p.nombre AS nombre_proveedor,
    p.rs AS razon_social, 
    p.rfc AS rfc_proveedor, 
    p.id_prov AS id_proveedor,
    d.cod_al AS cod_direccion, 
    d.noma AS nombre_direccion, 
    d.calle, d.numext, d.numint, d.colonia, 
    d.mun AS municipio,
    d.estado, d.c_postal, d.pais, 
    d.tel AS telefono,
    d.email, d.atencion,t.placas, t.linea, t.tipo, t.chofer, t.placas_caja,
    pr.nom_pro AS nombre_producto,
    pr.cod AS cod_producto,
    u.nombre AS nombre_usuario,
    u.correo AS email_usuario
    FROM compras c
    LEFT JOIN zonas z ON c.zona = z.id_zone
    LEFT JOIN proveedores p ON c.id_prov = p.id_prov
    LEFT JOIN direcciones d ON c.id_direc = d.id_direc
    LEFT JOIN transportes t ON c.id_transp = t.id_transp
    LEFT JOIN productos pr ON c.id_prod = pr.id_prod
    LEFT JOIN usuarios u ON c.id_user = u.id_user
    WHERE c.id_compra = ?
    ");
$compraQuery->bind_param('i', $id_compra);
$compraQuery->execute();
$compraData = $compraQuery->get_result()->fetch_assoc();

$ExCompra = ($compraData['ex'] == 2) ? '<i class="bi bi-filetype-svg text-teal bg-teal bg-opacity-10 rounded-1 p-auto" style="font-size: 15px" title="Cargado desde Excel"></i>' : '' ;

if (!$compraData) {
    alert("Compra no encontrada", 0, "compras");
    exit();
}

$Auto1 = ($compraData['autorizar'] == 0 ) ? '' : 'style="display: none"' ;// si la compra esta autorizada
// Formatear fechas y valores
$fecha_compra = date('d/m/Y', strtotime($compraData['fecha']));
$total = number_format($compraData['neto'] * $compraData['pres'], 2);


if (isset($_POST['guardar01'])) {
    try {
        $facturaV = $_POST['factura_ven'] ?? '';

        $buscar0 = $conn_mysql->query("SELECT * FROM compras WHERE factura =  '$facturaV' AND status = '1'");
        $buscar1 = mysqli_fetch_array($buscar0);

        if (empty($buscar1['id_compra'])) {

            $conn_mysql->query("UPDATE compras SET factura = '$facturaV' WHERE id_compra = '$id_compra'");
            alert("Factura agregada", 1, "V_compra&id=$id_compra");
        }else {
            alert("Factura utilizada en otra compra", 2, "V_compra&id=$id_compra");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "V_compra&id=$id_compra");
    }
}
?>

<div class="container mt-2">
    <div class="card border-0 shadow-lg">
        <!-- Header con información resumida -->
        <div class="card-header encabezado-col text-white">
            <div class="d-flex justify-content-between align-items-center row">
                <div class="col-12 col-md-6">
                    <h4 class="mb-1 fw-light">DETALLE DE COMPRA</h4>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-white text-dark fs-6 py-2 px-3">
                            <i class="bi bi-cart-check-fill me-2"></i><?= htmlspecialchars($compraData['nombre']) ?>
                        </span>
                        <span class="small">
                            <i class="bi bi-calendar-event me-1"></i> <?= $fecha_compra ?>
                            <i class="bi bi-person-fill ms-3 me-1"></i> <?= htmlspecialchars($compraData['nombre_usuario']) ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-1 col-12 col-md-6 d-flex justify-content-end">
                    <span class="badge bg-<?= $compraData['status'] ? 'success' : 'danger' ?> py-2 px-3 fs-6 rounded-3">
                        <?= $compraData['status'] ? 'ACTIVA' : 'INACTIVA' ?>
                    </span>
                    <a href="?p=compras" class="btn btn-sm rounded-3 btn-outline-light">
                        <i class="bi bi-arrow-left me-1"></i> Regresar
                    </a>
                    <a <?= $perm['Prove_Editar'];?> <?=$Auto1?> href="?p=E_compra&id=<?= $id_compra ?>" class="btn btn-sm rounded-3 btn-light">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                    <?php if ($compraData['status']): ?>
                        <button <?= $perm['ACT_DES'];?> <?=$Auto1?> class="btn btn-sm rounded-3 btn-danger desactivar-compra-btn" data-id="<?= $id_compra ?>">
                            <i class="bi bi-x-circle me-1"></i> Desactivar
                        </button>
                    <?php else: ?>
                        <button <?= $perm['ACT_DES'];?> class="btn btn-sm rounded-3 btn-success activar-compra-btn" data-id="<?= $id_compra ?>">
                            <i class="bi bi-check-circle me-1"></i> Activar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Contenido de las pestañas -->
            <div class="tab-content" id="compraTabsContent">
                <!-- Pestaña General -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="col-lg-8">
                            <div class="row g-4">
                                <!-- Tarjeta de información básica -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-1 shadow-sm">
                                        <div class="card-header border-primary">
                                            <h6 class="mb-0"><i class="bi bi-file-text me-2 text-primary"></i> Información Básica <?=$ExCompra?></h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="small text-muted mb-1">Proveedor</label>
                                                <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="?p=V_proveedores&id=<?=$compraData['id_proveedor']?>">
                                                    <h5 class="mb-3"><?= htmlspecialchars($compraData['razon_social']) ?></h5>
                                                </a>
                                                <div class="d-flex justify-content-between border-bottom py-2">
                                                    <span class="text-muted">Código</span>
                                                    <strong><?= htmlspecialchars($compraData['cod_proveedor']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between border-bottom py-2">
                                                    <span class="text-muted">RFC</span>
                                                    <strong><?= htmlspecialchars($compraData['rfc_proveedor']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between border-bottom py-2">
                                                    <span class="text-muted">Remisión</span>
                                                    <strong><?= htmlspecialchars($compraData['fact']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between border-bottom py-2">
                                                    <span class="text-muted">Factura</span>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php if(empty($compraData['factura_c'])){ ?>
                                                            <!--<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                             <i class="bi bi-plus-lg"></i> Agregar
                                                         </button>-->
                                                     <?php } else {
                                                        ?>
                                                        <div class="bg-teal bg-opacity-25 p-1 rounded-1">
                                                            <a class="link-underline link-underline-opacity-0" href="<?=$invoiceLK.$compraData['documento_factura'].".pdf"?>" target="_blank" title="Abrir documento de la factura">

                                                                <i class="bi text-success bi-file-earmark-fill"></i>

                                                                <strong id="factura-display" class="text-success"><?=$compraData['factura_c'] ?></strong>
                                                            </a>
                                                        </div>
                                                        <?php
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-2">
                                                <span class="text-muted">Bodega</span>
                                                <strong><?= htmlspecialchars($compraData['nombre_direccion']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-2">
                                                <span class="text-muted">Producto</span>
                                                <strong><?= htmlspecialchars($compraData['nombre_producto']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-2">
                                                <span class="text-muted">Zona</span>
                                                <strong><?= htmlspecialchars($compraData['nombre_zona']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de pesos -->
                            <div class="col-md-6">
                                <div class="card border-1 shadow-sm">

                                    <div class="card-header border-primary">
                                        <h6 class="mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i> Pesos (kg)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">  
                                            <span class="badge bg-primary bg-opacity-10 text-primary fs-6 mb-2"><?= htmlspecialchars($compraData['cod_producto']) ?></span>
                                            <h5><?= htmlspecialchars($compraData['nombre_producto']) ?></h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4 border-end">
                                                <div class="p-2">
                                                    <span class="d-block text-muted small">Tara</span>
                                                    <span class="fw-bold fs-6"><?= number_format($compraData['tara'], 2) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-4 border-end">
                                                <div class="p-2">
                                                    <span class="d-block text-muted small">Bruto</span>
                                                    <span class="fw-bold fs-6"><?= number_format($compraData['bruto'], 2) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2">
                                                    <span class="d-block text-muted small">Neto</span>
                                                    <span class="fw-bold fs-6"><?= number_format($compraData['neto'], 2) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 p-3 rounded bg-primary bg-opacity-25 text-center">
                                            <span class="d-block text-muted small">Peso Neto</span>
                                            <span class="fw-bold fs-5"><?= number_format($compraData['neto'], 2) ?> kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen Financiero -->
                    <div class="col-lg-4">
                        <div class="card border-1 shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header border-primary">
                                <h6 class="mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i> Resumen Financiero</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Peso Neto</span>
                                    <span class="fw-bold"><?= number_format($compraData['neto'], 2) ?> kg</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Precio Unitario</span>
                                    <span class="fw-bold">$<?= number_format($compraData['pres'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 py-2 border-bottom border-top">
                                    <span class="text-muted">Total Compra</span>
                                    <span class="fw-bold text-primary">$<?= $total ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-3  border-1 shadow-sm">
                            <div class="card-header border-primary">
                                <h6 class="mb-0"><i class="bi bi-truck me-2 text-primary"></i> Transporte</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center">
                                            <i class="bi bi-upc-scan fs-4 text-primary mb-2"></i>
                                            <h6 class="mb-1">ID Fletero</h6>
                                            <p class="fw-bold mb-0"><?= htmlspecialchars($compraData['placas']) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center">
                                            <i class="bi bi-building fs-4 text-primary mb-2"></i>
                                            <h6 class="mb-1">Línea</h6>
                                            <p class="fw-bold mb-0"><?= htmlspecialchars($compraData['linea']) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="p-3 border rounded text-center">
                                            <i class="bi bi-tags fs-4 text-primary mb-2"></i>
                                            <h6 class="mb-1">Tipo</h6>
                                            <p class="fw-bold mb-0"><?= htmlspecialchars($compraData['tipo']) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="p-3 border rounded text-center">
                                            <i class="bi bi-person-badge fs-4 text-primary mb-2"></i>
                                            <h6 class="mb-1">Chofer</h6>
                                            <p class="fw-bold mb-0"><?= htmlspecialchars($compraData['chofer']) ?></p>
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
</div>
</div>

<style>
    .card-header.bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }
    .bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.1) !important;
    }
    .sticky-top {
        position: -webkit-sticky;
        position: sticky;
    }
</style>
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
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal de confirmación mejorado -->
<div class="modal fade" id="confirmCompraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-<?= $compraData['status'] ? 'danger' : 'success' ?> text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i> Confirmar acción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalCompraMessage">¿Estás seguro de que deseas desactivar esta compra?</p>
                <input type="hidden" id="compraId">
                <input type="hidden" id="compraAccion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-<?= $compraData['status'] ? 'danger' : 'success' ?>" id="confirmCompraBtn">
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
    // Configurar modal para desactivar/activar compras
        $(document).on('click', '.desactivar-compra-btn', function() {
            const id = $(this).data('id');
            $('#compraId').val(id);
            $('#compraAccion').val('desactivar');
            $('#modalCompraMessage').text('¿Estás seguro de que deseas desactivar esta compra?');
            $('#confirmCompraModal').modal('show');
        });
        
        $(document).on('click', '.activar-compra-btn', function() {
            const id = $(this).data('id');
            $('#compraId').val(id);
            $('#compraAccion').val('activar');
            $('#modalCompraMessage').text('¿Estás seguro de que deseas reactivar esta compra?');
            $('#confirmCompraModal').modal('show');
        });
        
    // Confirmar acción para compras
        $('#confirmCompraBtn').click(function() {
            const id = $('#compraId').val();
            const accion = $('#compraAccion').val();
            
            $.post('actualizar_status_com.php', {
                id: id,
                accion: accion,
                tabla: 'compras'
            }, function(response) {
                if (response.success) {
                // Mostrar notificación de éxito
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    $('#toastMessage').html(`<i class="bi bi-check-circle-fill text-success me-2"></i> Compra ${accion === 'activar' ? 'activada' : 'desactivada'} correctamente`);
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
            
            $('#confirmCompraModal').modal('hide');
        });
    });
</script>