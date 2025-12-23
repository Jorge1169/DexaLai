<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php
// Verificar permisos de administrador
if ($TipoUserSession != 100 && $TipoUserSession != 50) {
    alert("No tienes permisos para ver los reportes de actividad", 0, 'inicio');
    exit;
}

?>
<div class="container mt-4 fade-in">
    <div class="card border-0 shadow-lg">
        <div class="card-header encabezado-col text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Reporte de Actividades del Sistema
                </h5>
            </div>
        </div>
        <div class="card-body p-4">
            <!-- Filtros rápidos -->
            <div class="bg-body-tertiary p-3 rounded mb-4">
                <div class="row g-2">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="filterAction">
                            <option value="">Todas las acciones</option>
                            <option value="LOGIN">Login</option>
                            <option value="LOGOUT">Logout</option>
                            <option value="CREAR">Crear</option>
                            <option value="EDITAR">Editar</option>
                            <option value="LOGIN_FAILED">Login fallido</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="filterUser" placeholder="Filtrar por usuario...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-sm btn-teal w-100" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="miTabla" style="width:100%">
                    <thead class="table-dark"> 
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Usuario</th>
                            <th width="12%" data-priority="1">Acción</th>
                            <th width="35%">Descripción</th>
                            <th width="15%">IP</th>
                            <th width="18%">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM user_activity_logs ORDER BY created_at DESC";
                        $Act = $conn_mysql->query($query);
                        $Contador = 0;
                        
                        while ($logs = mysqli_fetch_array($Act)) {
                            $Contador++;
                            
                            // Sistema de colores más profesional y consistente
                            $badgeClass = 'badge text-bg-light border'; // Default
                            $icon = 'fas fa-circle';
                            
                            switch($logs['action']) {
                                case 'LOGIN':
                                $badgeClass = 'badge bg-success text-white';
                                $icon = 'fas fa-sign-in-alt';
                                break;
                                case 'LOGOUT':
                                $badgeClass = 'badge bg-secondary text-white';
                                $icon = 'fas fa-sign-out-alt';
                                break;
                                case 'LOGIN_FAILED':
                                $badgeClass = 'badge bg-danger text-white';
                                $icon = 'fas fa-exclamation-triangle';
                                break;
                                case 'ACT/DEC':
                                $badgeClass = 'badge bg-warning text-dark';
                                $icon = 'fas fa-power-off';
                                break;
                                case 'PRECIO':
                                $badgeClass = 'badge bg-info text-dark';
                                $icon = 'fas fa-tag';
                                break;
                                case 'INV':
                                case 'EXCEL':
                                $badgeClass = 'badge bg-primary text-white';
                                $icon = 'fas fa-file-export';
                                break;
                                case 'EDITAR':
                                $badgeClass = 'badge bg-purple text-white';
                                $icon = 'fas fa-edit';
                                break;
                                case 'CREAR':
                                $badgeClass = 'badge bg-teal text-white';
                                $icon = 'fas fa-plus-circle';
                                break;
                            }
                            ?>
                            <tr>
                                <td class="fw-bold text-muted"><?=$Contador?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-muted me-2"></i>
                                        <span class="fw-medium"><?=$logs['username']?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="<?=$badgeClass?>">
                                        <i class="<?=$icon?> me-1"></i><?=$logs['action']?>
                                    </span>
                                </td>
                                <td class="text-truncate" style="max-width: 300px;" title="<?=htmlspecialchars($logs['description'])?>">
                                    <?=$logs['description']?>
                                </td>
                                <td>
                                    <code class="text-muted small"><?=$logs['ip_address']?></code>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?=date('d/m/Y H:i', strtotime($logs['created_at']))?>
                                    </small>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-body-tertiary py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-database me-1"></i>
                        Total de registros: <strong><?=$Contador?></strong>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Actualizado: <?=date('d/m/Y H:i')?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const dataTable = $('#miTabla').DataTable({
    language: {
        url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
        infoFiltered: "",
        lengthMenu: "Mostrar _MENU_ registros por página",
        zeroRecords: "No se encontraron registros coincidentes",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        search: "Buscar:",
        paginate: {
            first: "Primero",
            last: "Último",
            next: "Siguiente",
            previous: "Anterior"
        }
    },
    responsive: true,
    order: [[5, 'desc']],
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>B',
    buttons: [
        {
            extend: 'excelHtml5',
            text: '<i class="fas fa-file-excel me-1"></i> Exportar Excel',
            className: 'btn btn-primary btn-sm',
            title: 'Reporte_Actividades',
            exportOptions: {
                columns: ':visible'
            }
        }
    ],
    initComplete: function() {
        // Personalizar el dropdown de "Mostrar registros"
        $('.dataTables_length label').addClass('d-flex align-items-center');
        $('.dataTables_length select').addClass('form-select form-select-sm');
    }
});

// Filtros
$('#filterAction').on('change', function() {
    dataTable.column(2).search(this.value).draw();
});

$('#filterUser').on('keyup', function() {
    dataTable.column(1).search(this.value).draw();
});

function clearFilters() {
    $('#filterAction').val('');
    $('#filterUser').val('');
    dataTable.search('').columns().search('').draw();
}

// Actualizar el contador cuando cambie el número de registros mostrados
dataTable.on('draw.dt', function() {
    const info = dataTable.page.info();
    const totalFiltered = info.recordsDisplay;
    
    // Actualizar el contador en el footer
    $('.card-footer strong').text(totalFiltered);
});
</script>