
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
                
                <!-- NUEVO BOTÓN PARA REPORTE -->
                <a href="?p=reporte_precios" class="btn btn-info btn-sm rounded-3">
                    <i class="bi bi-graph-up"></i> Reporte Precios
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="miTabla" style="width:100%">
                    <thead><
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