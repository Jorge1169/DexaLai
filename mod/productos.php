<?php
if (isset($_POST['guardar01'])) {
    $tipo = clear($_POST['tipo_masivo']);
    $precio = clear($_POST['precio_masivo']);
    $fecha_ini = clear($_POST['fecha_ini_masivo']);
    $fecha_fin = clear($_POST['fecha_fin_masivo']);
    $productos = $_POST['productos'];
    $usuario = $_POST['usuario'];

    $conn_mysql->begin_transaction();
    
    try { 
        $procesados = 0;
        
        foreach ($productos as $id_producto) {
            // VERIFICAR si ya existe un precio activo con el mismo tipo, producto y precio
            $sql_verificar = "SELECT id_precio FROM precios 
            WHERE id_prod = ? 
            AND tipo = ? 
            AND precio = ?
            AND status = '1'";
            $stmt_verificar = $conn_mysql->prepare($sql_verificar);
            $stmt_verificar->bind_param('isd', $id_producto, $tipo, $precio);
            $stmt_verificar->execute();
            $result_verificar = $stmt_verificar->get_result();
            
            if ($result_verificar->num_rows > 0) {
                // Ya existe un precio con el mismo tipo, producto y precio → ACTUALIZAR FECHAS
                $precio_existente = $result_verificar->fetch_assoc();
                $sql_actualizar = "UPDATE precios SET fecha_ini = ?, fecha_fin = ?, usuario = ? 
                WHERE id_precio = ?";
                $stmt_actualizar = $conn_mysql->prepare($sql_actualizar);
                $stmt_actualizar->bind_param('ssii', $fecha_ini, $fecha_fin, $usuario, $precio_existente['id_precio']);
                $stmt_actualizar->execute();
            } else {
                // No existe un precio con estos datos → INSERTAR NUEVO PRECIO
                $sql_insert = "INSERT INTO precios (id_prod, precio, tipo, fecha_ini, fecha_fin, usuario, status) 
                VALUES (?, ?, ?, ?, ?, ?, '1')";
                $stmt_insert = $conn_mysql->prepare($sql_insert);
                $stmt_insert->bind_param('idsssi', $id_producto, $precio, $tipo, $fecha_ini, $fecha_fin, $usuario);
                $stmt_insert->execute();
            }
            
            $procesados++;
        }
        
        $conn_mysql->commit();
        
        alert("Precios aplicados correctamente", 1, "productos");
        logActivity('PRECIO', ' Agrego varios precios de compras');
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error al actualizar precios: " . $e->getMessage(), 2, "productos");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Productos</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Produ_Crear'];?> href="?p=N_producto" class="btn btn-primary btn-sm rounded-3">
                    <i class="bi bi-plus"></i> Nuevo Producto
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3" onclick="toggleInactive()">
                    <i class="bi bi-eye"></i> Mostrar Inactivos
                </button>
                <button <?= $perm['sub_precios'];?> class="btn btn-success btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalPreciosMasivos">
                    <i class="bi bi-collection"></i> Precios Masivos
                </button>
                <!-- NUEVO BOTÓN PARA REPORTE -->
                <a href="?p=reporte_precios" class="btn btn-info btn-sm rounded-3">
                    <i class="bi bi-graph-up"></i> Reporte Precios
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th data-priority="1">Código</th>
                            <th>Nombre</th>
                            <th>Línea</th>
                            <th>Zona</th>
                            <th>Último Precio Compra</th>
                            <th>Último Precio Venta</th>
                            <th>Estado Precios</th>
                            <th>Fecha de alta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // CONSULTA ACTUALIZADA PARA INCLUIR PRECIOS
                        if ($zona_seleccionada == '0') {
                            $query = "SELECT 
                            p.*, 
                            z.nom AS nom_zone,
                            -- Último precio de compra activo y vigente
                            (SELECT precio FROM precios 
                               WHERE id_prod = p.id_prod 
                               AND tipo = 'c' 
                               AND status = '1'
                               AND fecha_ini <= CURDATE() 
                               AND fecha_fin >= CURDATE()
                               ORDER BY fecha_ini DESC, id_precio DESC 
                               LIMIT 1) AS ultimo_precio_compra,
                            -- Último precio de venta activo y vigente (general)
                            (SELECT precio FROM precios 
                               WHERE id_prod = p.id_prod 
                               AND tipo = 'v' 
                               AND status = '1'
                               AND fecha_ini <= CURDATE() 
                               AND fecha_fin >= CURDATE()
                               ORDER BY fecha_ini DESC, id_precio DESC 
                               LIMIT 1) AS ultimo_precio_venta
                            FROM productos p 
                            LEFT JOIN zonas z ON p.zona = z.id_zone";
                        } else {
                            $query = "SELECT 
                            p.*, 
                            z.nom AS nom_zone,
                            -- Último precio de compra activo y vigente
                            (SELECT precio FROM precios 
                               WHERE id_prod = p.id_prod 
                               AND tipo = 'c' 
                               AND status = '1'
                               AND fecha_ini <= CURDATE() 
                               AND fecha_fin >= CURDATE()
                               ORDER BY fecha_ini DESC, id_precio DESC 
                               LIMIT 1) AS ultimo_precio_compra,
                            -- Último precio de venta activo y vigente (general)
                            (SELECT precio FROM precios 
                               WHERE id_prod = p.id_prod 
                               AND tipo = 'v' 
                               AND status = '1'
                               AND fecha_ini <= CURDATE() 
                               AND fecha_fin >= CURDATE()
                               ORDER BY fecha_ini DESC, id_precio DESC 
                               LIMIT 1) AS ultimo_precio_venta
                            FROM productos p 
                            LEFT JOIN zonas z ON p.zona = z.id_zone 
                            WHERE p.zona = '$zona_seleccionada'";
                        }
                        
                        $result = $conn_mysql->query($query);
                        $Contador = 0;
                        $Activos = 1;
                        $Desacti = 1;
                        
                        while ($Prod01 = mysqli_fetch_array($result)) {
                            ($Prod01['status'] == '1') ? $Contador = $Activos++ : $Contador = $Desacti++;
                            $fecha_alta = date('Y-m-d', strtotime($Prod01['fecha']));
                            $status = $Prod01['status'] == '1' ? 'Activo' : 'Inactivo';
                            $badgeClass = $Prod01['status'] == '1' ? 'bg-success' : 'bg-danger';
                            
                            // VERIFICAR ESTADO DE PRECIOS
                            $estado_precios = verificarEstadoPrecios($Prod01['id_prod'], $conn_mysql);
                            ?>
                            <tr>
                                <td class="text-center"><?= $Contador ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2">
                                        <?php if ($Prod01['status'] == '1'): ?>
                                            <a <?= $perm['Produ_Editar'];?> href="?p=E_producto&id=<?= $Prod01['id_prod'] ?>" 
                                             class="btn btn-info btn-sm rounded-3">
                                             <i class="bi bi-pencil"></i> Editar
                                         </a>

                                         <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-btn" 
                                             data-id="<?= $Prod01['id_prod'] ?>">
                                             <i class="bi bi-box-seam"></i> Desactivar
                                         </button>
                                     <?php else: ?>
                                        <button class="btn btn-info btn-sm rounded-3 activar-btn" 
                                        data-id="<?= $Prod01['id_prod'] ?>" 
                                        title="Activar cliente">
                                        <i class="bi bi-person-check"></i> Activar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" 
                            href="?p=V_producto&id=<?= $Prod01['id_prod'] ?>">
                            <?= htmlspecialchars($Prod01['cod']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($Prod01['nom_pro']) ?></td>
                    <td><?= htmlspecialchars($Prod01['lin']) ?></td>
                    <td><?= htmlspecialchars($Prod01['nom_zone']) ?></td>
                    
                    <!-- NUEVA COLUMNA: Último Precio Compra -->
                    <td class="text-end fw-semibold">
                        <?php if (!empty($Prod01['ultimo_precio_compra'])): ?>
                            $<?= number_format($Prod01['ultimo_precio_compra'], 2) ?>
                        <?php else: ?>
                            <span class="text-muted small">Sin precio</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- NUEVA COLUMNA: Último Precio Venta -->
                    <td class="text-end fw-semibold">
                        <?php if (!empty($Prod01['ultimo_precio_venta'])): ?>
                            $<?= number_format($Prod01['ultimo_precio_venta'], 2) ?>
                        <?php else: ?>
                            <span class="text-muted small">Sin precio</span>
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <?php echo mostrarBadgeEstadoPrecios($estado_precios); ?>
                    </td>
                    <td><?= $fecha_alta ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>
</div>
</div>
<!-- Modal para Precios Masivos -->
<div class="modal fade" id="modalPreciosMasivos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-bg-success">
                <h5 class="modal-title">
                    <i class="bi bi-tags me-2"></i> Actualización Masiva de Precios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="forms-sample" method="post" action="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tipo_masivo" class="form-label">Tipo de Precio</label>
                            <select class="form-select" name="tipo_masivo" id="tipo_masivo" required>
                                <option value="c">Compra</option>
                                <!-- <option value="v">Venta</option>-->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="precio_masivo" class="form-label">Precio $</label>
                            <input type="number" step="0.01" min="0" name="precio_masivo" id="precio_masivo" class="form-control" required>
                            <input type="hidden" name="usuario" value="<?=$idUser?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_ini_masivo" class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_ini_masivo" id="fecha_ini_masivo" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_fin_masivo" class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin_masivo" id="fecha_fin_masivo" class="form-control" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Seleccionar Productos</label>

                            <!-- Filtros de búsqueda -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" id="filtroBusqueda" placeholder="Buscar por código o nombre...">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" id="filtroLinea">
                                        <option value="">Todas las líneas</option>
                                        <?php
            // Filtrar líneas según la zona del usuario
                                        if ($zona_seleccionada == '0') {
                                            $lineasQuery = "SELECT DISTINCT lin FROM productos WHERE status = '1' AND lin != '' ORDER BY lin";
                                        } else {
                                            $lineasQuery = "SELECT DISTINCT lin FROM productos WHERE status = '1' AND lin != '' AND zona = '$zona_seleccionada' ORDER BY lin";
                                        }
                                        $lineas = $conn_mysql->query($lineasQuery);
                                        while ($linea = $lineas->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($linea['lin']) . '">' . htmlspecialchars($linea['lin']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php if($zona_seleccionada == '0'): ?>
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm" id="filtroZona">
                                            <option value="">Todas las zonas</option>
                                            <?php
                                            $zonas = $conn_mysql->query("SELECT z.id_zone, z.nom FROM zonas z 
                                             INNER JOIN productos p ON z.id_zone = p.zona 
                                             WHERE p.status = '1' 
                                             GROUP BY z.id_zone, z.nom 
                                             ORDER BY z.nom");
                                            while ($zona = $zonas->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($zona['id_zone']) . '">' . htmlspecialchars($zona['nom']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <!-- Si el usuario tiene zona específica, mostrar información de la zona -->
                                    <div class="col-md-4">
                                        <div class="form-control form-control-sm bg-light">
                                            <small class="text-muted">
                                                Zona: 
                                                <?php 
                                                $zona_nombre = $conn_mysql->query("SELECT nom FROM zonas WHERE id_zone = '$zona_seleccionada'")->fetch_assoc();
                                                echo htmlspecialchars($zona_nombre['nom'] ?? 'Zona actual');
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>


                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                        <label class="form-check-label fw-bold" for="selectAll">
                                            Seleccionar Todos (<span id="contadorVisible">0</span> productos visibles)
                                        </label>
                                    </div>
                                    <small class="text-muted" id="contadorSeleccionados">0 seleccionados</small>
                                </div>
                                <hr>
                                <div id="listaProductos">
                                    <!-- Los productos se cargarán aquí con JavaScript -->
                                </div>
                                <div id="sinResultados" class="text-center text-muted py-3" style="display: none;">
                                    No se encontraron productos que coincidan con los filtros.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" name="guardar01" class="btn btn-success btn-sm rounded-3">
                        <i class="bi bi-check-circle me-1"></i> Aplicar a Productos Seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Agregar estas funciones al inicio del archivo, antes del HTML -->
<?php
function verificarEstadoPrecios($id_producto, $conn) {
    $estado = [
        'sin_precio_compra' => false,
        'sin_precio_venta' => false,
        'precio_compra_caduca' => false,
        'precio_venta_caduca' => false,
        'dias_para_caducar' => 3 // Definir cuántos días consideramos "por caducar"
    ];
    
    // Verificar precios de compra
    $precios_compra = $conn->query("
        SELECT * FROM precios 
        WHERE id_prod = '$id_producto' 
        AND tipo = 'c' 
        AND status = '1'
        AND fecha_ini <= CURDATE() 
        AND (fecha_fin >= CURDATE() OR fecha_fin IS NULL)
        ORDER BY fecha_fin ASC 
        LIMIT 1
        ");
    
    if ($precios_compra->num_rows == 0) {
        $estado['sin_precio_compra'] = true;
    } else {
        $precio_compra = $precios_compra->fetch_assoc();
        if ($precio_compra['fecha_fin'] && esPrecioPorCaducar($precio_compra['fecha_fin'], $estado['dias_para_caducar'])) {
            $estado['precio_compra_caduca'] = true;
        }
    }
    
    // Verificar precios de venta
    $precios_venta = $conn->query("
        SELECT * FROM precios 
        WHERE id_prod = '$id_producto' 
        AND tipo = 'v' 
        AND status = '1'
        AND fecha_ini <= CURDATE() 
        AND (fecha_fin >= CURDATE() OR fecha_fin IS NULL)
        ORDER BY fecha_fin ASC 
        LIMIT 1
        ");
    
    if ($precios_venta->num_rows == 0) {
        $estado['sin_precio_venta'] = true;
    } else {
        $precio_venta = $precios_venta->fetch_assoc();
        if ($precio_venta['fecha_fin'] && esPrecioPorCaducar($precio_venta['fecha_fin'], $estado['dias_para_caducar'])) {
            $estado['precio_venta_caduca'] = true;
        }
    }
    
    return $estado;
}

function esPrecioPorCaducar($fecha_fin, $dias_anticipacion) {
    $fecha_actual = new DateTime();
    $fecha_caducidad = new DateTime($fecha_fin);
    $diferencia = $fecha_actual->diff($fecha_caducidad);
    
    return $diferencia->days <= $dias_anticipacion && $diferencia->invert == 0;
}

function mostrarBadgeEstadoPrecios($estado) {
    if ($estado['sin_precio_compra'] && $estado['sin_precio_venta']) {
        return '<span class="badge bg-danger" title="Sin precios de compra y venta"><i class="bi bi-exclamation-triangle"></i> Sin precios</span>';
    } elseif ($estado['sin_precio_compra']) {
        return '<span class="badge bg-primary" title="Sin precio de compra vigente"><i class="bi bi-cart-x"></i> Sin compra</span>';
    } elseif ($estado['sin_precio_venta']) {
        return '<span class="badge bg-indigo" title="Sin precio de venta vigente"><i class="bi bi-tag"></i> Sin venta</span>';
    } elseif ($estado['precio_compra_caduca'] && $estado['precio_venta_caduca']) {
        return '<span class="badge bg-warning" title="Precios de compra y venta por caducar"><i class="bi bi-clock"></i> Precios caducan</span>';
    } elseif ($estado['precio_compra_caduca']) {
        return '<span class="badge bg-warning" title="Precio de compra por caducar"><i class="bi bi-clock"></i> Compra caduca</span>';
    } elseif ($estado['precio_venta_caduca']) {
        return '<span class="badge bg-warning" title="Precio de venta por caducar"><i class="bi bi-clock"></i> Venta caduca</span>';
    } else {
        return '<span class="badge bg-success" title="Precios vigentes"><i class="bi bi-check-circle"></i> OK</span>';
    }
}
?>

<!-- El resto del código (toast, modal, script) permanece igual -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header encabezado-col">
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
         <div id="prueb" class="modal-header">
            <h5 class="modal-title">Confirmar acción</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p id="modalMessage">¿Estás seguro de que deseas desactivar este producto?</p>
            <input type="hidden" id="prodId">
            <input type="hidden" id="accion">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn" id="confirmBtn">Confirmar</button>
        </div>
    </div>
</div>
</div>
</div>

<script>
    $(document).ready(function() {
       const dataTable = $('#miTabla').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            "infoFiltered": ""
        },
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 1] }, // Columnas no ordenables
            { "type": "currency", "targets": [6, 7] }  // Nueva: definir tipo para columnas de precio
        ],
        "initComplete": function() {
                // Aplicar filtro inicial para mostrar solo activos
            this.api().column(10).search("^Activo$", true, false).draw(); // Cambiado a 10 por las nuevas columnas
        }
    });

     // Variable para rastrear el estado actual
       let showingInactives = false;

        // Función para alternar entre activos/inactivos
       window.toggleInactive = function() {
        const btn = $('button[onclick="toggleInactive()"]');

        if (showingInactives) {
                // Mostrar solo activos
            dataTable.column(10).search("^Activo$", true, false).draw(); // Cambiado a 10
            btn.html('<i class="bi bi-eye"></i> Mostrar Inactivos');
            btn.removeClass('btn-info').addClass('btn-secondary');
        } else {
                // Mostrar solo inactivos
            dataTable.column(10).search("^Inactivo$", true, false).draw(); // Cambiado a 10
            btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivos');
            btn.removeClass('btn-secondary').addClass('btn-info');
        }

        showingInactives = !showingInactives;
    };
    
    // Configurar modal para desactivar/activar productos
    $(document).on('click', '.desactivar-btn', function() {
        const id = $(this).data('id');
        $('#prodId').val(id);
        $('#accion').val('desactivar');
        $('#modalMessage').text('¿Estás seguro de que deseas desactivar este producto?');
        $('#confirmModal').modal('show');
        $('#prueb').addClass('text-bg-warning');
        $('#confirmBtn').addClass('btn-warning');
    });

    $(document).on('click', '.activar-btn', function() {
        const id = $(this).data('id');
        $('#prodId').val(id);
        $('#accion').val('activar');
        $('#modalMessage').text('¿Estás seguro de que deseas reactivar este producto?');
        $('#confirmModal').modal('show');
        $('#prueb').addClass('text-bg-info');
        $('#confirmBtn').addClass('btn-info');
    });

        // Confirmar acción
    $('#confirmBtn').click(function() {
        const id = $('#prodId').val();
        const accion = $('#accion').val();

        $.post('actualizar_status_pro.php', {
            id: id,
            accion: accion,
                tabla: 'productos'  // Añadido para identificar la tabla
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                alert('Error en la solicitud: ' + textStatus + ', ' + errorThrown);
            });
            
            $('#confirmModal').modal('hide');
        });
});
</script>
<script>
    let productosSeleccionados = new Set();

    // Cargar productos en el modal
    function cargarProductos(filtros = {}) {
        const params = new URLSearchParams();
        if (filtros.texto) params.append('texto', filtros.texto);
        if (filtros.linea) params.append('linea', filtros.linea);
        if (filtros.zona) params.append('zona', filtros.zona);

    // Pasar la zona del usuario (obtenida de PHP)
        params.append('zona_usuario', '<?= $zona_seleccionada ?>');

        $.ajax({
            url: 'obtener_productos_activos.php?' + params.toString(),
            type: 'GET',
            dataType: 'json',
            success: function(productos) {
                let html = '';
                if (productos.length > 0) {
                    productos.forEach(producto => {
                        const estaSeleccionado = productosSeleccionados.has(producto.id_prod.toString());
                        html += `
                    <div class="form-check producto-item" data-codigo="${producto.cod.toLowerCase()}" data-nombre="${producto.nom_pro.toLowerCase()}" data-linea="${producto.lin}" data-zona="${producto.zona}">
                        <input class="form-check-input producto-check" type="checkbox" name="productos[]" value="${producto.id_prod}" id="prod_${producto.id_prod}" ${estaSeleccionado ? 'checked' : ''}>
                        <label class="form-check-label" for="prod_${producto.id_prod}">
                            <strong>${producto.cod}</strong> - ${producto.nom_pro} 
                            <small class="text-muted">(${producto.lin} <?php if($zona_seleccionada == '0'): ?>- ${producto.nombre_zona || 'Sin zona'}<?php endif; ?>)</small>
                        </label>
                    </div>
                        `;
                    });
                    $('#listaProductos').html(html);
                    $('#sinResultados').hide();
                    $('#listaProductos').show();
                } else {
                    $('#listaProductos').html('');
                    $('#listaProductos').hide();
                    $('#sinResultados').show();
                }
                actualizarContadores();

            // CORRECCIÓN: Asegurarse de que todos los productos seleccionados estén en el formulario
            // Esto agrega checkboxes ocultos para productos seleccionados que no están en la lista actual
                agregarProductosOcultosAlFormulario();
            },
            error: function() {
                $('#listaProductos').html('<p class="text-danger">Error al cargar los productos.</p>');
            }
        });
    }

// Función para agregar productos seleccionados que no están visibles
    function agregarProductosOcultosAlFormulario() {
    // Primero, obtener todos los productos seleccionados que NO están en la lista visible
        const productosVisibles = new Set();
        $('.producto-check').each(function() {
            productosVisibles.add($(this).val());
        });

    // Agregar campos hidden para los seleccionados que no son visibles
        productosSeleccionados.forEach(productId => {
            if (!productosVisibles.has(productId)) {
                $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'productos[]')
                .attr('value', productId)
                .appendTo('form');
            }
        });
    }

    // Cuando se abre el modal
    $('#modalPreciosMasivos').on('show.bs.modal', function() {
        // Limpiar selecciones al abrir el modal
        productosSeleccionados.clear();
        
        // Cargar productos sin filtros inicialmente
        cargarProductos();
        
        // Limpiar filtros
        $('#filtroBusqueda').val('');
        $('#filtroLinea').val('');
        <?php if($zona_seleccionada == '0'): ?>
            $('#filtroZona').val('');
        <?php endif; ?>
    });

    // Aplicar filtros en tiempo real
    $('#filtroBusqueda').on('input', function() {
        aplicarFiltros();
    });

    $('#filtroLinea').on('change', function() {
        aplicarFiltros();
    });

    // Solo agregar el evento para zona si el filtro existe
    <?php if($zona_seleccionada == '0'): ?>
        $('#filtroZona').on('change', function() {
            aplicarFiltros();
        });
    <?php endif; ?>

    function aplicarFiltros() {
        const filtros = {
            texto: $('#filtroBusqueda').val(),
            linea: $('#filtroLinea').val()
        };
        
        // Solo incluir filtro de zona si el usuario tiene acceso a todas las zonas
        <?php if($zona_seleccionada == '0'): ?>
            filtros.zona = $('#filtroZona').val();
        <?php endif; ?>
        
        cargarProductos(filtros);
    }

    // CORREGIDO: Seleccionar/deseleccionar todos los productos VISIBLES
    $(document).on('change', '#selectAll', function() {
        const isChecked = $(this).prop('checked');
        
        $('.producto-item:visible .producto-check').each(function() {
            const productId = $(this).val();
            if (isChecked) {
                productosSeleccionados.add(productId);
            } else {
                productosSeleccionados.delete(productId);
            }
            $(this).prop('checked', isChecked);
        });
        
        actualizarContadores();
    });

    // Actualizar la selección cuando se marca/desmarca un producto individual
    $(document).on('change', '.producto-check', function() {
        const productId = $(this).val();
        const isChecked = $(this).prop('checked');
        
        if (isChecked) {
            productosSeleccionados.add(productId);
        } else {
            productosSeleccionados.delete(productId);
        }
        
        actualizarContadores();
    });

    // Actualizar contadores (FUNCIÓN ÚNICA - eliminé la duplicada)
    function actualizarContadores() {
        const totalVisible = $('.producto-item:visible').length;
        const seleccionados = productosSeleccionados.size;

        $('#contadorVisible').text(totalVisible);
        $('#contadorSeleccionados').text(seleccionados + ' seleccionados');

        // Actualizar el estado de "Seleccionar Todos" basado en los productos visibles
        const seleccionadosVisibles = $('.producto-item:visible .producto-check:checked').length;
        $('#selectAll').prop('checked', seleccionadosVisibles > 0 && seleccionadosVisibles === totalVisible);
        $('#selectAll').prop('indeterminate', seleccionadosVisibles > 0 && seleccionadosVisibles < totalVisible);
    }

    // Validación del formulario CORREGIDA
    $('form').on('submit', function(e) {
        // Solo validar si es el formulario de precios masivos
        if ($(this).find('input[name="precio_masivo"]').length > 0) {
            // CORREGIDO: Usar productosSeleccionados.size en lugar de contar checkboxes
            if (productosSeleccionados.size === 0) {
                e.preventDefault();
                alert('Por favor selecciona al menos un producto.');
                return false;
            }

            // Validar fechas
            const fechaIni = new Date($('#fecha_ini_masivo').val());
            const fechaFin = new Date($('#fecha_fin_masivo').val());

            if (fechaFin < fechaIni) {
                e.preventDefault();
                alert('La fecha final no puede ser anterior a la fecha inicial.');
                return false;
            }
        }
    });
</script>