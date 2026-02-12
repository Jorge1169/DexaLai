<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stat-chip {
        padding: 0.35rem 0.6rem;
        border-radius: 999px;
        font-size: 0.85rem;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .month-card {
        border: 1px solid rgba(0,0,0,0.05);
    }
</style>

<?php
// Verificar permisos de administrador
if ($TipoUserSession != 100 && $TipoUserSession != 50) {
    alert("No tienes permisos para ver los reportes de actividad", 0, 'inicio');
    exit;
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$query = "SELECT * FROM user_activity_logs ORDER BY created_at DESC";
$Act = $conn_mysql->query($query);

$grouped = [];
$totalRegistros = 0;

while ($log = mysqli_fetch_assoc($Act)) {
    $totalRegistros++;
    $ts = strtotime($log['created_at']);
    $monthKey = date('Y-m', $ts);
    $monthLabel = $meses[(int)date('n', $ts)] . ' ' . date('Y', $ts);

    if (!isset($grouped[$monthKey])) {
        $grouped[$monthKey] = [
            'label' => $monthLabel,
            'logs' => [],
            'actions' => [],
            'users' => []
        ];
    }

    $grouped[$monthKey]['logs'][] = $log;
    $grouped[$monthKey]['actions'][$log['action']] = ($grouped[$monthKey]['actions'][$log['action']] ?? 0) + 1;
    $grouped[$monthKey]['users'][$log['username']] = true;
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
                <div class="row g-2 align-items-center">
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
                        <select class="form-select form-select-sm" id="filterMonth" onchange="scrollToMonth(this.value)">
                            <option value="">Mes (ir a)</option>
                            <?php foreach ($grouped as $key => $group) { ?>
                                <option value="<?="month-{$key}"?>"><?=$group['label']?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-sm btn-teal w-100" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Limpiar filtros
                        </button>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <span class="stat-chip shadow-sm"><i class="fas fa-database me-1 text-muted"></i>Total registros: <strong><?=$totalRegistros?></strong></span>
                    <span class="stat-chip shadow-sm"><i class="fas fa-calendar-alt me-1 text-muted"></i>Meses: <strong><?=count($grouped)?></strong></span>
                </div>
            </div>

            <?php foreach ($grouped as $key => $group) { 
                $actionBadges = '';
                foreach ($group['actions'] as $act => $count) {
                    $actionBadges .= "<span class='stat-chip shadow-sm me-1 mb-1'><i class='fas fa-bolt me-1 text-muted'></i>{$act}: <strong>{$count}</strong></span>";
                }
            ?>

            <div class="card mb-3 shadow-sm month-card" id="month-<?=$key?>">
                <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark text-uppercase"><?=$group['label']?></span>
                        <span class="stat-chip shadow-sm"><i class="fas fa-layer-group me-1 text-muted"></i>Registros: <strong><?=count($group['logs'])?></strong></span>
                        <span class="stat-chip shadow-sm"><i class="fas fa-users me-1 text-muted"></i>Usuarios: <strong><?=count($group['users'])?></strong></span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <?=$actionBadges?>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?=$key?>" aria-expanded="true" aria-controls="collapse-<?=$key?>">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div id="collapse-<?=$key?>" class="collapse show">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 activity-table" id="tabla-<?=$key?>" style="width:100%">
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
                                $Contador = 0;
                                foreach ($group['logs'] as $logs) {
                                    $Contador++;

                                    $badgeClass = 'badge text-bg-light border';
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
            </div>
            <?php } ?>
        </div>
        <div class="card-footer bg-body-tertiary py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-database me-1"></i>
                        Total de registros: <strong><?=$totalRegistros?></strong>
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
const tables = [];

$('.activity-table').each(function() {
    const table = $(this).DataTable({
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
            $('.dataTables_length label').addClass('d-flex align-items-center');
            $('.dataTables_length select').addClass('form-select form-select-sm');
        }
    });

    tables.push(table);
});

$('#filterAction').on('change', function() {
    const value = this.value;
    tables.forEach(t => t.column(2).search(value).draw());
});

$('#filterUser').on('keyup', function() {
    const value = this.value;
    tables.forEach(t => t.column(1).search(value).draw());
});

function clearFilters() {
    $('#filterAction').val('');
    $('#filterUser').val('');
    tables.forEach(t => t.search('').columns().search('').draw());
}

function scrollToMonth(targetId) {
    if (!targetId) return;
    const el = document.getElementById(targetId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>