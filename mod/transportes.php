<style>
    th {
        font-size: 13px;
    }
    td {
        font-size: 13px;
    }
    .btn {
        font-size: 12px;
    }
    .badge-estado-precio {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>
<div class="container mt-2">
    <div class="card shadow-sm"> 
        <h5 class="card-header encabezado-col text-white">Transporte</h5>
        <div class="card-body">
            <div class="mb-3">
                <a <?= $perm['Trans_Crear'];?> href="?p=N_transportista" class="btn btn-primary btn-sm rounded-3">
                    <i class="bi bi-plus"></i> Nuevo Transporte
                </a>
                <a <?= $perm['sub_precios'];?> href="?p=subir_precios_masivo" class="btn btn-success btn-sm rounded-3">
                    <i class="bi bi-upload"></i> Carga Masiva de Precios
                </a>
                <button <?= $perm['INACTIVO'];?> class="btn btn-secondary btn-sm rounded-3" onclick="toggleInactive()">
                    <i class="bi bi-eye"></i> Mostrar Inactivos
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Acciones</th>
                            <th data-priority="1">ID Fletero</th>
                            <th>R. SOCIAL</th>
                            <th>Línea</th>
                            <th>Tipo</th>
                            <th>Chofer</th>
                            <th>Correo</th>
                            <th>Placas</th>
                            <th>Zona</th>
                            <th>Fecha de alta</th>
                            <th>Estado Precios</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Función para verificar estado de precios
                        function obtenerEstadoPrecios($id_transporte, $conn_mysql) {
                            $hoy = date('Y-m-d');
                            $tres_dias = date('Y-m-d', strtotime('+3 days'));
                            
                            // Consulta para precios vigentes
                            $query = "SELECT COUNT(*) as total,
                                     SUM(CASE WHEN fecha_fin BETWEEN '$hoy' AND '$tres_dias' THEN 1 ELSE 0 END) as por_caducar,
                                     SUM(CASE WHEN fecha_fin < '$hoy' THEN 1 ELSE 0 END) as caducados
                                     FROM precios 
                                     WHERE (tipo = 'FT' OR tipo = 'FV') 
                                     AND status = '1' 
                                     AND id_prod = ?";
                            
                            $stmt = $conn_mysql->prepare($query);
                            $stmt->bind_param('i', $id_transporte);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $estado = $result->fetch_assoc();
                            
                            if ($estado['total'] == 0) {
                                return [
                                    'estado' => 'sin_precios',
                                    'texto' => 'Sin precios',
                                    'clase' => 'bg-danger',
                                    'icono' => 'bi-x-circle'
                                ];
                            } elseif ($estado['caducados'] > 0 && $estado['por_caducar'] == 0 && ($estado['total'] - $estado['caducados']) == 0) {
                                return [
                                    'estado' => 'caducados',
                                    'texto' => 'Precios caducados',
                                    'clase' => 'bg-danger',
                                    'icono' => 'bi-exclamation-triangle'
                                ];
                            } elseif ($estado['por_caducar'] > 0) {
                                return [
                                    'estado' => 'por_caducar',
                                    'texto' => 'Por caducar (' . $estado['por_caducar'] . ')',
                                    'clase' => 'bg-warning',
                                    'icono' => 'bi-clock'
                                ];
                            } else {
                                return [
                                    'estado' => 'vigentes',
                                    'texto' => 'Vigentes (' . $estado['total'] . ')',
                                    'clase' => 'bg-success',
                                    'icono' => 'bi-check-circle'
                                ];
                            }
                        }

                        if ($zona_seleccionada == '0') {
                            $query = "SELECT t.*, z.nom AS nom_zone FROM transportes t LEFT JOIN zonas z ON t.zona = z.id_zone ORDER BY t.razon_so";
                        } else {
                            $query = "SELECT t.*, z.nom AS nom_zone FROM transportes t LEFT JOIN zonas z ON t.zona = z.id_zone WHERE t.zona = '$zona_seleccionada' ORDER BY t.razon_so";
                        }

                        $result = $conn_mysql->query($query);

                        $Contador = 0;// vaciar contador
                        $Activos = 1;// contador de activos
                        $Desacti = 1;// contador de inactivos

                        while ($Transp01 = mysqli_fetch_array($result)) {

                            ($Transp01['status'] == '1') ? $Contador = $Activos++ : $Contador = $Desacti++ ;// codigo de contador

                            $fecha_alta = date('Y-m-d', strtotime($Transp01['fecha']));
                            $status = $Transp01['status'] == '1' ? 'Activo' : 'Inactivo';
                            $badgeClass = $Transp01['status'] == '1' ? 'bg-success' : 'bg-danger';
                            
                            // Obtener estado de precios solo para transportistas activos
                            if ($Transp01['status'] == '1') {
                                $estadoPrecios = obtenerEstadoPrecios($Transp01['id_transp'], $conn_mysql);
                            } else {
                                $estadoPrecios = [
                                    'estado' => 'inactivo',
                                    'texto' => 'Inactivo',
                                    'clase' => 'bg-secondary',
                                    'icono' => 'bi-pause-circle'
                                ];
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?= $Contador ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2">
                                        <?php if ($Transp01['status'] == '1'): ?>
                                            <a <?= $perm['Trans_Editar'];?> href="?p=E_transportista&id=<?= $Transp01['id_transp'] ?>" 
                                               class="btn btn-info btn-sm rounded-3">
                                               <i class="bi bi-pencil"></i> Editar
                                           </a>

                                           <button <?= $perm['ACT_DES'];?> class="btn btn-warning btn-sm rounded-3 desactivar-btn" 
                                               data-id="<?= $Transp01['id_transp'] ?>">
                                               <i class="bi bi-truck"></i> Desactivar
                                           </button>
                                       <?php else: ?>
                                        <button class="btn btn-info btn-sm rounded-3 activar-btn" 
                                        data-id="<?= $Transp01['id_transp'] ?>" 
                                        title="Activar cliente">
                                        <i class="bi bi-person-check"></i> Activar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" 
                                       href="?p=V_transporte&id=<?= $Transp01['id_transp'] ?>">
                                       <?= htmlspecialchars($Transp01['placas']) ?>
                              </a>
                        </td>
                        <td><?= $Transp01['razon_so'] ?></td>
                        <td><?= htmlspecialchars($Transp01['linea']) ?></td>
                        <td><?= htmlspecialchars($Transp01['tipo']) ?></td>
                        <td><?= htmlspecialchars($Transp01['chofer']) ?></td>
                        <td><?=$Transp01['correo']?></td>
                        <td><?= htmlspecialchars($Transp01['placas_caja']) ?></td>
                        <td><?= htmlspecialchars($Transp01['nom_zone']) ?></td>
                        <td><?= $fecha_alta ?></td>
                        <td class="text-center">
                            <span class="badge badge-estado-precio <?= $estadoPrecios['clase'] ?>" 
                                  title="<?= $estadoPrecios['texto'] ?>"
                                  data-bs-toggle="tooltip" data-bs-placement="top">
                                <i class="bi <?= $estadoPrecios['icono'] ?> me-1"></i>
                                <?= $estadoPrecios['texto'] ?>
                            </span>
                        </td>
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
</div>

<!-- Resto del código permanece igual -->
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
                <p id="modalMessage">¿Estás seguro de que deseas desactivar este transportista?</p>
                <input type="hidden" id="transpId">
                <input type="hidden" id="accion">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="confirmBtn">Confirmar</button>
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
            "initComplete": function() {
                // Aplicar filtro inicial para mostrar solo activos
                this.api().column(12).search("^Activo$", true, false).draw();
                
                // Inicializar tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
        
         // Variable para rastrear el estado actual
        let showingInactives = false;

        // Función para alternar entre activos/inactivos
        window.toggleInactive = function() {
            const btn = $('button[onclick="toggleInactive()"]');
            
            if (showingInactives) {
                // Mostrar solo activos
                dataTable.column(12).search("^Activo$", true, false).draw();
                btn.html('<i class="bi bi-eye"></i> Mostrar Inactivos');
                btn.removeClass('btn-info').addClass('btn-secondary');
            } else {
                // Mostrar solo inactivos
                dataTable.column(12).search("^Inactivo$", true, false).draw();
                btn.html('<i class="bi bi-eye-slash"></i> Ocultar Inactivos');
                btn.removeClass('btn-secondary').addClass('btn-info');
            }
            
            showingInactives = !showingInactives;
        };
        
        // Configurar modal para desactivar/activar transportistas
        $(document).on('click', '.desactivar-btn', function() {
            const id = $(this).data('id');
            $('#transpId').val(id);
            $('#accion').val('desactivar');
            $('#modalMessage').text('¿Estás seguro de que deseas desactivar este transportista?');
            $('#confirmModal').modal('show');
            $('#prueb').addClass('text-bg-warning');
            $('#confirmBtn').addClass('btn-warning');
        });
        
        $(document).on('click', '.activar-btn', function() {
            const id = $(this).data('id');
            $('#transpId').val(id);
            $('#accion').val('activar');
            $('#modalMessage').text('¿Estás seguro de que deseas reactivar este transportista?');
            $('#confirmModal').modal('show');
            $('#prueb').addClass('text-bg-info');
            $('#confirmBtn').addClass('btn-info');
        });
        
        // Confirmar acción
        $('#confirmBtn').click(function() {
            const id = $('#transpId').val();
            const accion = $('#accion').val();
            
            $.post('actualizar_status_t.php', {
                id: id,
                accion: accion,
                tabla: 'transportes'  // Añadido para identificar la tabla
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