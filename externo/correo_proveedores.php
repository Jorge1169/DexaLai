<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Correo para proveedores</title>
    <link rel="shortcut icon" href="../img/logos/lai_esfera_BN.png"/>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* Mínimo CSS personalizado */
        .card-header-custom {
            background-color: #2c3e50;
        }
        .completed-badge {
            background-color: #d4edda;
            color: #155724;
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
    
    // Consulta para obtener los datos de la recolección
    $query = "SELECT 
    r.*,
    r.remision AS remision,
    r.id_prov AS id_proveedor,
    r.peso_prov AS peso_proveedor,
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
            $remision = isset($_POST['remision']) ? trim($_POST['remision']) : '';
            $peso_proveedor = isset($_POST['peso_proveedor']) ? trim($_POST['peso_proveedor']) : '';

            // Validar que al menos un campo tenga datos
            if (empty($remision) && empty($peso_proveedor)) {
                $error_message = "Debe ingresar al menos la remisión o el peso del proveedor";
            } else {
                // Validar que la remisión no exista para el mismo proveedor
                $stmt_busca = $conn_mysql->prepare("SELECT id_recol FROM recoleccion WHERE remision = ? AND id_prov = ? AND id_recol != ?");
                $stmt_busca->bind_param("sii", $remision, $recoleccion['id_proveedor'], $id_recoleccion);
                $stmt_busca->execute();
                $result_busca = $stmt_busca->get_result();

                if ($result_busca->num_rows > 0) {
                    $error_message = "La remisión '$remision' ya fue usada para este proveedor y no se puede guardar";
                } else {
                    // Construir la consulta de actualización dinámicamente
                    $update_query = "UPDATE recoleccion SET ";
                    $params = array();
                    $types = "";

                    if (!empty($remision)) {
                        $update_query .= "remision = ?, ";
                        $params[] = $remision;
                        $types .= "s";
                    }

                    if (!empty($peso_proveedor)) {
                        $update_query .= "peso_prov = ?, ";
                        $params[] = $peso_proveedor;
                        $types .= "s";
                    }

                    // Eliminar la última coma y espacio
                    $update_query = rtrim($update_query, ", ");
                    $update_query .= " WHERE id_recol = ?";
                    $params[] = $id_recoleccion;
                    $types .= "i";

                    $stmt_update = $conn_mysql->prepare($update_query);
                    $stmt_update->bind_param($types, ...$params);

                    if ($stmt_update->execute()) {
                        $success_message = "Datos actualizados correctamente";
                        // Actualizar los datos locales para mostrar los nuevos valores
                        $recoleccion['remision'] = $remision;
                        $recoleccion['peso_proveedor'] = $peso_proveedor;
                    } else {
                        $error_message = "Error al actualizar los datos: " . $conn_mysql->error;
                    }
                    $stmt_update->close();
                }
                $stmt_busca->close();
            }
        }

        $folio_completo = $recoleccion['cod_zona'] . "-" . date('ym', strtotime($recoleccion['fecha_r'])) . str_pad($recoleccion['folio'], 4, '0', STR_PAD_LEFT);
        $fecha_recoleccion = date('d/m/Y', strtotime($recoleccion['fecha_r']));
        $fecha_factura = date('d/m/Y', strtotime($recoleccion['fecha_v']));
        
        // Verificar si los datos ya están completos
        $remision_completa = !empty($recoleccion['remision']);
        $peso_completo = !empty($recoleccion['peso_proveedor']);
        $todos_completos = $remision_completa && $peso_completo;
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
                            <h4 class="mb-1"><i class="bi bi-truck me-2"></i>Orden de Recolección - Proveedor</h4>
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
                        <div class="col-md-6 mb-3">
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
                        <div class="col-md-6 mb-3">
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
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Completar Información</h5>
                            <p class="mb-0 small text-muted">Por favor, ingrese los datos de remisión y peso</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="proveedorForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="remision" class="form-label">Número de Remisión</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                            <input type="text" class="form-control" id="remision" name="remision" 
                                            value="<?= $recoleccion['remision'] ?>" 
                                            placeholder="Ingrese el número de remisión"
                                            <?php echo $remision_completa ? 'disabled' : ''; ?>>
                                        </div>
                                        <?php if ($remision_completa): ?>
                                            <div class="form-text text-success">
                                                <i class="bi bi-check-circle-fill"></i> Este dato ya fue registrado
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="peso_proveedor" class="form-label">Peso del Proveedor (kg)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                            <input type="number" step="0.01" class="form-control" id="peso_proveedor" name="peso_proveedor" 
                                            value="<?= $recoleccion['peso_proveedor'] ?>" 
                                            placeholder="Ingrese el peso en kilogramos"
                                            <?php echo $peso_completo ? 'disabled' : ''; ?>>
                                        </div>
                                        <?php if ($peso_completo): ?>
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
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i> 
                                Si tiene alguna duda, contacte al administrador del sistema.
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                ID Recolección: <span class="fw-semibold"><?php echo $id_recoleccion; ?></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

            // Prevenir envío doble del formulario
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('proveedorForm');
                if (form) {
                    form.addEventListener('submit', function() {
                        const submitBtn = this.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Guardando...';
                        }
                    });
                }
            });
        </script>
        <?php
    }
    ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>