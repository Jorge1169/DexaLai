<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Correo para fletero</title>
    <link rel="shortcut icon" href="../img/logos/lai_esfera_BN.png"/>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* Mínimo CSS personalizado */
        .card-header-custom {
            background-color: #2c3e50;
        }
        .info-badge {
            border-left: 4px solid #3498db;
        }
        .completed-badge {
            background-color: #d4edda;
            color: #155724;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
   <?php
    require_once '../config/conexiones.php';
    $id_recoleccion = isset($_GET['id']) ? $_GET['id'] : null;
    
    // Inicializar variables de mensaje
    $success_message = null;
    $error_message = null;
    
    // Consulta para obtener los datos de la recolección (incluyendo los nuevos campos)
    $query = "SELECT 
    r.*,
    r.remision AS remision,
    r.peso_prov AS peso_proveedor,
    r.factura_fle AS factura_flete,
    r.peso_fle AS peso_flete,
    r.tipo_fle,  -- NUEVO: Tipo de camión
    r.nom_fle,   -- NUEVO: Nombre del chofer
    r.placas_fle, -- NUEVO: Placas de la unidad
    r.id_transp AS id_transportista,
    p.rs AS razon_social_proveedor,
    p.cod AS cod_proveedor,
    dp.noma AS nombre_bodega_proveedor,
    dp.cod_al AS cod_bodega_proveedor,
    t.razon_so AS razon_social_fletero,
    t.placas AS placas_fletero,
    c.nombre AS nombre_cliente,
    c.cod AS cod_cliente,
    dc.noma AS nombre_bodega_cliente,
    dc.cod_al AS cod_bodega_cliente,
    pr.nom_pro AS nombre_producto,
    pr.cod AS cod_producto,
    z.cod AS cod_zona,
    z.nom AS nombre_zona,
    z.PLANTA AS planta_zona,
    pf.precio AS precio_flete,
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
        echo "<div class='container mt-5'><div class='alert alert-danger'>La liga de recolección está mal, revisa tu correo</div></div>";
    } else {
        $recoleccion = $result->fetch_assoc();

        // Procesar el formulario si se envió
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $factura_flete = isset($_POST['factura_flete']) ? trim($_POST['factura_flete']) : '';
            $peso_flete = isset($_POST['peso_flete']) ? trim($_POST['peso_flete']) : '';
            $tipo_camion = isset($_POST['tipo_camion']) ? trim($_POST['tipo_camion']) : '';
            $nombre_chofer = isset($_POST['nombre_chofer']) ? trim($_POST['nombre_chofer']) : '';
            $placas_unidad = isset($_POST['placas_unidad']) ? trim($_POST['placas_unidad']) : '';

            // Validar que todos los campos obligatorios tengan datos
            $campos_faltantes = [];
            
            if (empty($factura_flete)) {
                $campos_faltantes[] = "Factura del flete";
            }
            if (empty($peso_flete)) {
                $campos_faltantes[] = "Peso del flete";
            }
            if (empty($tipo_camion)) {
                $campos_faltantes[] = "Tipo de camión";
            }
            if (empty($nombre_chofer)) {
                $campos_faltantes[] = "Nombre del chofer";
            }
            if (empty($placas_unidad)) {
                $campos_faltantes[] = "Placas de la unidad";
            }

            if (!empty($campos_faltantes)) {
                $error_message = "Los siguientes campos son obligatorios: " . implode(", ", $campos_faltantes);
            } else {
                // Validar que la factura no exista para el mismo fletero (solo si es nueva)
                if (empty($recoleccion['factura_flete'])) {
                    $stmt_busca = $conn_mysql->prepare("SELECT id_recol FROM recoleccion WHERE factura_fle = ? AND id_transp = ? AND id_recol != ?");
                    $stmt_busca->bind_param("sii", $factura_flete, $recoleccion['id_transportista'], $id_recoleccion);
                    $stmt_busca->execute();
                    $result_busca = $stmt_busca->get_result();

                    if ($result_busca->num_rows > 0) {
                        $error_message = "La factura '$factura_flete' ya fue usada para este fletero y no se puede guardar";
                    } else {
                        // Actualizar todos los campos
                        $update_query = "UPDATE recoleccion SET factura_fle = ?, peso_fle = ?, tipo_fle = ?, nom_fle = ?, placas_fle = ? WHERE id_recol = ?";
                        $stmt_update = $conn_mysql->prepare($update_query);
                        $stmt_update->bind_param("sssssi", $factura_flete, $peso_flete, $tipo_camion, $nombre_chofer, $placas_unidad, $id_recoleccion);

                        if ($stmt_update->execute()) {
                            $success_message = "Datos del fletero guardados correctamente";
                            // Actualizar los datos locales
                            $recoleccion['factura_flete'] = $factura_flete;
                            $recoleccion['peso_flete'] = $peso_flete;
                            $recoleccion['tipo_fle'] = $tipo_camion;
                            $recoleccion['nom_fle'] = $nombre_chofer;
                            $recoleccion['placas_fle'] = $placas_unidad;
                        } else {
                            $error_message = "Error al actualizar los datos: " . $conn_mysql->error;
                        }
                        $stmt_update->close();
                    }
                    $stmt_busca->close();
                } else {
                    // Si la factura ya existe, actualizar solo los otros campos
                    $update_query = "UPDATE recoleccion SET peso_fle = ?, tipo_fle = ?, nom_fle = ?, placas_fle = ? WHERE id_recol = ?";
                    $stmt_update = $conn_mysql->prepare($update_query);
                    $stmt_update->bind_param("ssssi", $peso_flete, $tipo_camion, $nombre_chofer, $placas_unidad, $id_recoleccion);

                    if ($stmt_update->execute()) {
                        $success_message = "Datos del fletero actualizados correctamente";
                        // Actualizar los datos locales
                        $recoleccion['peso_flete'] = $peso_flete;
                        $recoleccion['tipo_fle'] = $tipo_camion;
                        $recoleccion['nom_fle'] = $nombre_chofer;
                        $recoleccion['placas_fle'] = $placas_unidad;
                    } else {
                        $error_message = "Error al actualizar los datos: " . $conn_mysql->error;
                    }
                    $stmt_update->close();
                }
            }
        }

        $folio_completo = $recoleccion['cod_zona'] . "-" . date('ym', strtotime($recoleccion['fecha_r'])) . str_pad($recoleccion['folio'], 4, '0', STR_PAD_LEFT);
        $fecha_recoleccion = date('d/m/Y', strtotime($recoleccion['fecha_r']));
        $fecha_factura = date('d/m/Y', strtotime($recoleccion['fecha_v']));

        // Verificar si los datos ya están completos (todos los campos obligatorios)
        $factura_flete_completa = !empty($recoleccion['factura_flete']);
        $peso_flete_completo = !empty($recoleccion['peso_flete']);
        $tipo_camion_completo = !empty($recoleccion['tipo_fle']);
        $nombre_chofer_completo = !empty($recoleccion['nom_fle']);
        $placas_unidad_completas = !empty($recoleccion['placas_fle']);
        
        $todos_completos = $factura_flete_completa && $peso_flete_completo && 
                           $tipo_camion_completo && $nombre_chofer_completo && $placas_unidad_completas;
    ?>
    <div class="container py-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header card-header-custom text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="bi bi-truck me-2"></i>Orden de Recolección - Fletero</h4>
                        <p class="mb-0">Folio: <strong><?php echo $folio_completo; ?></strong> | Fecha: <?php echo $fecha_recoleccion; ?></p>
                    </div>
                    <?php if ($todos_completos): ?>
                        <span class="badge completed-badge rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i>Datos completos
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning rounded-pill">
                            <i class="bi bi-clock-history me-1"></i>Pendiente
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-building me-2"></i>Proveedor</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['razon_social_proveedor']); ?></p>
                                <small class="text-muted">Código: <?php echo htmlspecialchars($recoleccion['cod_proveedor']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-house me-2"></i>Bodega Proveedor</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['nombre_bodega_proveedor']); ?></p>
                                <small class="text-muted">Código: <?php echo htmlspecialchars($recoleccion['cod_bodega_proveedor']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-house me-2"></i>Remisión</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?= $recoleccion['remision'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Datos del Fletero</h5>
                        <p class="mb-0 small text-muted">Por favor, complete todos los datos obligatorios</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <!-- Campos originales (obligatorios) -->
                                <div class="col-md-6 mb-3">
                                    <label for="factura_flete" class="form-label required-field">Factura del Flete</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                        <input type="number" class="form-control" id="factura_flete" name="factura_flete" 
                                        value="<?= $recoleccion['factura_flete'] ?>" 
                                        placeholder="Ingrese el número de factura del flete"
                                        <?php echo $factura_flete_completa ? 'readonly' : ''; ?> required>
                                    </div>
                                    <?php if ($factura_flete_completa): ?>
                                        <div class="form-text text-success">
                                            <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="peso_flete" class="form-label required-field">Peso del Flete <b>(kg)</b></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                        <input type="number" step="0.01" class="form-control" id="peso_flete" name="peso_flete" 
                                        value="<?= $recoleccion['peso_flete'] ?>" 
                                        placeholder="Ingrese el peso del flete en kilogramos"
                                        <?php echo $peso_flete_completo ? 'readonly' : ''; ?> required>
                                    </div>
                                    <?php if ($peso_flete_completo): ?>
                                        <div class="form-text text-success">
                                            <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- NUEVOS CAMPOS OBLIGATORIOS -->
                                <div class="col-md-4 mb-3">
                                    <label for="tipo_camion" class="form-label required-field">Tipo de Camión</label>
                                    <select class="form-select" id="tipo_camion" name="tipo_camion" required 
                                        <?php echo $tipo_camion_completo ? 'disabled' : ''; ?>>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="CAMIONETA 3 1/2" <?= $recoleccion['tipo_fle'] == 'CAMIONETA 3 1/2' ? 'selected' : '' ?>>CAMIONETA 3 1/2</option>
                                        <option value="TRAILER" <?= $recoleccion['tipo_fle'] == 'TRAILER' ? 'selected' : '' ?>>TRAILER</option>
                                        <option value="TORTON" <?= $recoleccion['tipo_fle'] == 'TORTON' ? 'selected' : '' ?>>TORTON</option>
                                        <option value="CAMIONETA CHICA" <?= $recoleccion['tipo_fle'] == 'CAMIONETA CHICA' ? 'selected' : '' ?>>CAMIONETA CHICA</option>
                                        <option value="OTRO" <?= $recoleccion['tipo_fle'] == 'OTRO' ? 'selected' : '' ?>>OTRO</option>
                                    </select>
                                    <?php if ($tipo_camion_completo): ?>
                                        <input type="hidden" name="tipo_camion" value="<?= $recoleccion['tipo_fle'] ?>">
                                        <div class="form-text text-success">
                                            <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="nombre_chofer" class="form-label required-field">Nombre del Chofer</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="nombre_chofer" name="nombre_chofer" 
                                        value="<?= $recoleccion['nom_fle'] ?>" 
                                        placeholder="Ingrese el nombre del chofer"
                                        <?php echo $nombre_chofer_completo ? 'readonly' : ''; ?> required>
                                    </div>
                                    <?php if ($nombre_chofer_completo): ?>
                                        <div class="form-text text-success">
                                            <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="placas_unidad" class="form-label required-field">Placas de la Unidad</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" class="form-control" id="placas_unidad" name="placas_unidad" 
                                        value="<?= $recoleccion['placas_fle'] ?>" 
                                        placeholder="Ingrese las placas de la unidad"
                                        <?php echo $placas_unidad_completas ? 'readonly' : ''; ?> required>
                                    </div>
                                    <?php if ($placas_unidad_completas): ?>
                                        <div class="form-text text-success">
                                            <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!$todos_completos): ?>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Guardar Información
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Todos los datos han sido completados y no pueden ser modificados.
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Mostrar datos completados si existen -->
                <?php if ($todos_completos): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Datos Registrados</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Factura:</strong> <?= $recoleccion['factura_flete'] ?>
                            </div>
                            <div class="col-md-2">
                                <strong>Peso:</strong> <?= $recoleccion['peso_flete'] ?> kg
                            </div>
                            <div class="col-md-2">
                                <strong>Tipo:</strong> <?= $recoleccion['tipo_fle'] ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Chofer:</strong> <?= $recoleccion['nom_fle'] ?>
                            </div>
                            <div class="col-md-2">
                                <strong>Placas:</strong> <?= $recoleccion['placas_fle'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-box me-2"></i>Producto</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['nombre_producto']); ?></p>
                                <small class="text-muted">Código: <?php echo htmlspecialchars($recoleccion['cod_producto']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Fletero</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['razon_social_fletero']); ?></p>
                                <small class="text-muted">Placas: <?php echo htmlspecialchars($recoleccion['placas_fletero']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Cliente</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['nombre_cliente']); ?></p>
                                <small class="text-muted">Código: <?php echo htmlspecialchars($recoleccion['cod_cliente']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-house me-2"></i>Bodega Cliente</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><?php echo htmlspecialchars($recoleccion['nombre_bodega_cliente']); ?></p>
                                <small class="text-muted">Código: <?php echo htmlspecialchars($recoleccion['cod_bodega_cliente']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i> 
                            Si tiene alguna duda, contacte al administrador del sistema.
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            ID Recolección: <?php echo $id_recoleccion; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Mostrar alerta de éxito con SweetAlert si hay un mensaje de éxito
    <?php if (isset($success_message)): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?php echo $success_message; ?>',
            timer: 3000,
            showConfirmButton: false
        });
    <?php endif; ?>
    
    // Mostrar alerta de error con SweetAlert si hay un mensaje de error
    <?php if (isset($error_message)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $error_message; ?>'
        });
    <?php endif; ?>

    // Validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos obligatorios',
                        text: 'Por favor, complete todos los campos marcados como obligatorios (*)'
                    });
                }
            });
        }
    });
</script>
</body>
</html>