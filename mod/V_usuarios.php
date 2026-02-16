<?php
$id_usuario = $_GET['id'] ?? 0;
$zona_user = '';

$usuarioQuery = $conn_mysql->prepare("SELECT * FROM usuarios WHERE id_user = ?");
$usuarioQuery->bind_param('i', $id_usuario);
$usuarioQuery->execute();
$usuarioData = $usuarioQuery->get_result()->fetch_assoc();

if (!$usuarioData) {
    alert("Usuario no encontrado", 0, "usuarios");
    exit();
}

// Usar el sistema dinámico de permisos
$gruposPermisos = getPermisosFormulario();
$tipoUsuario = getNombreTipoUsuario($usuarioData['tipo']);
$badgeClass = getBadgeTipoUsuario($usuarioData['tipo']);

// Obtener información de zonas
$zonasUsuario = [];
$zona_user = '';

if ($usuarioData['zona'] == '0' || $usuarioData['zona'] == '') {
    $zona_user = 'Todas las zonas';
} else {
    // Separar las zonas por comas
    $zonasIds = explode(',', $usuarioData['zona']);
    
    if (count($zonasIds) > 0) {
        // Obtener nombres de las zonas
        $placeholders = str_repeat('?,', count($zonasIds) - 1) . '?';
        $queryZonas = $conn_mysql->prepare("SELECT id_zone, nom FROM zonas WHERE id_zone IN ($placeholders) ORDER BY id_zone");
        $queryZonas->bind_param(str_repeat('i', count($zonasIds)), ...$zonasIds);
        $queryZonas->execute();
        $resultZonas = $queryZonas->get_result();
        
        while ($zona = $resultZonas->fetch_assoc()) {
            $zonasUsuario[] = [
                'id' => $zona['id_zone'],
                'nombre' => $zona['nom']
            ];
        }
        
        if (count($zonasUsuario) == 1) {
            $zona_user = $zonasUsuario[0]['nombre'];
        } else {
            $zona_user = count($zonasUsuario) . ' zonas asignadas';
        }
    }
}
?>

<div class="container py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <!-- Encabezado -->
        <div class="card-header encabezado-col text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-person-circle me-2"></i>Información del Usuario</h5>
                <div class="d-flex gap-2">
                    <a <?= $perm['INACTIVO']; ?> href="?p=usuarios" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                    <a <?= $perm['INACTIVO']; ?> href="?p=E_usuario&id=<?= $id_usuario ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                    <?php if ($TipoUserSession == 100 && $id_usuario != $idUser && $usuarioData['status'] == 1): ?>
                        <button class="btn btn-warning btn-sm" onclick="confirmSudoLogin(<?= $id_usuario ?>, '<?= htmlspecialchars($usuarioData['nombre']) ?>')">
                            <i class="bi bi-person-check me-1"></i> Iniciar como
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cuerpo -->
        <div class="card-body user-body">
            <div class="row g-4">
                <!-- Perfil -->
                <div class="col-lg-4">
                    <div class="profile-card p-4 h-100 rounded-4 shadow-sm text-center">
                        <div class="avatar-lg mx-auto mb-3 bg-primary text-white rounded-circle fw-bold d-flex align-items-center justify-content-center">
                            <?= strtoupper(substr($usuarioData['nombre'], 0, 1)) ?>
                        </div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($usuarioData['nombre']) ?></h4>
                        <span class="badge <?= $badgeClass ?>"><?= $tipoUsuario ?></span>
                        <div class="mt-3 small text-muted">
                            <i class="bi bi-calendar"></i> Registro: <?= date('d/m/Y', strtotime($usuarioData['fecha'])) ?>
                        </div>
                        <hr>
                        <div class="text-start px-3 small">
                            <p class="mb-2"><strong>Usuario:</strong> <?= htmlspecialchars($usuarioData['usuario']) ?></p>
                            <p class="mb-2"><strong>Correo:</strong> <?= htmlspecialchars($usuarioData['correo']) ?></p>
                            <p class="mb-2">
                                <strong>Zona:</strong> 
                                <span class="fw-semibold"><?= $zona_user ?></span>
                                <?php if (count($zonasUsuario) > 0 && $usuarioData['zona'] != '0'): ?>
                                    <a href="javascript:void(0);" class="ms-1 text-decoration-none" 
                                       data-bs-toggle="modal" data-bs-target="#modalZonas">
                                        <i class="bi bi-eye-fill text-primary"></i> Ver detalles
                                    </a>
                                <?php endif; ?>
                            </p>
                            <p class="mb-0"><strong>Estado:</strong>
                                <span class="badge <?= ($usuarioData['status'] == 1) ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ($usuarioData['status'] == 1) ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </p>
                        </div>
                        
                        <!-- Zonas asignadas (solo mostrar si hay más de una) -->
                        <?php if (count($zonasUsuario) > 1 && $usuarioData['zona'] != '0'): ?>
                        <div class="mt-3 text-start px-3">
                            <small class="text-muted d-block mb-1">Zonas asignadas:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($zonasUsuario as $zona): ?>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($zona['nombre']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Permisos -->
                <div class="col-lg-8">
                    <div class="permissions-card p-4 rounded-4 shadow-sm">
                        <h5 class="fw-semibold mb-4"><i class="bi bi-shield-lock me-2 text-primary"></i>Permisos del Sistema</h5>

                        <!-- Permisos de Creación y Edición -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <h6 class="text-success fw-semibold mb-3">
                                    <i class="bi bi-plus-circle me-2"></i>Módulos (Crear / Editar)
                                </h6>
                            </div>
                            <?php
                            // Agrupar permisos de creación y edición por módulo
                            $modulosCrear = [];
                            $modulosEditar = [];
                            
                            foreach ($gruposPermisos['creacion'] ?? [] as $nombre => $config) {
                                $modulosCrear[$nombre] = $config;
                            }
                            foreach ($gruposPermisos['edicion'] ?? [] as $nombre => $config) {
                                $modulosEditar[$nombre] = $config;
                            }
                            
                            // Mapeo dinámico: usar claves del catálogo (ej. PROVEEDORES_CREAR / PROVEEDORES_EDITAR)
                            $bases = [];
                            foreach ($modulosCrear as $k => $c) {
                                if (preg_match('/^(.*)_CREAR$/', $k, $m)) $bases[] = $m[1];
                            }
                            foreach ($modulosEditar as $k => $e) {
                                if (preg_match('/^(.*)_EDITAR$/', $k, $m)) $bases[] = $m[1];
                            }
                            $bases = array_values(array_unique($bases));

                            foreach ($bases as $base):
                                $crearKey = $base . '_CREAR';
                                $editarKey = $base . '_EDITAR';
                                $crearConfig = $modulosCrear[$crearKey] ?? null;
                                $editarConfig = $modulosEditar[$editarKey] ?? null;
                                if (!$crearConfig && !$editarConfig) continue;

                                // Determinar nombre legible del módulo
                                if ($crearConfig) {
                                    $nombreModulo = preg_replace('/^Crear\s+/i', '', $crearConfig['descripcion']);
                                } elseif ($editarConfig) {
                                    $nombreModulo = preg_replace('/^Editar\s+/i', '', $editarConfig['descripcion']);
                                } else {
                                    $nombreModulo = ucwords(strtolower(str_replace('_', ' ', $base)));
                                }
                            ?>
                                <div class="col-6 col-md-4">
                                    <div class="perm-box text-center p-3 rounded-3">
                                        <div class="fw-medium mb-2"><?= htmlspecialchars($nombreModulo) ?></div>
                                        <?php if ($crearConfig): ?>
                                            <span class="badge <?= ($usuarioData[$crearConfig['columna']] == 1) ? 'bg-success' : 'bg-secondary' ?> me-1">
                                                <i class="bi bi-plus-circle"></i> Crear
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($editarConfig): ?>
                                            <span class="badge <?= ($usuarioData[$editarConfig['columna']] == 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>

                        <!-- Permisos Especiales -->
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <h6 class="text-warning fw-semibold mb-3">
                                    <i class="bi bi-star me-2"></i>Permisos Especiales
                                </h6>
                            </div>
                            <?php foreach ($gruposPermisos['especial'] ?? [] as $nombre => $config): ?>
                                <?php if (($config['columna'] ?? '') === 'zona_adm') continue; ?>
                                <div class="col-6 col-md-4">
                                    <div class="perm-box text-center p-3 rounded-3">
                                        <div class="fw-medium mb-2"><?= htmlspecialchars($config['descripcion']) ?></div>
                                        <span class="badge <?= ($usuarioData[$config['columna']] == 1) ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ($usuarioData[$config['columna']] == 1) 
                                                ? '<i class="bi bi-check-circle"></i> Activo' 
                                                : '<i class="bi bi-x-circle"></i> Inactivo' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver todas las zonas -->
<div class="modal fade" id="modalZonas" tabindex="-1" aria-labelledby="modalZonasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalZonasLabel">
                    <i class="bi bi-geo-alt me-2"></i>Zonas asignadas a <?= htmlspecialchars($usuarioData['nombre']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($usuarioData['zona'] == '0'): ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-globe text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-bold">Todas las zonas</h5>
                        <p class="text-muted">Este usuario tiene acceso a todas las zonas del sistema.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de la zona</th>
                                    <th>Acceso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zonasUsuario as $zona): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $zona['id'] ?></td>
                                    <td><?= htmlspecialchars($zona['nombre']) ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-lg"></i> Permitido
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Total:</strong> <?= count($zonasUsuario) ?> zonas asignadas
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="?p=E_usuario&id=<?= $id_usuario ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Editar zonas
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSudoLogin(userId, userName) {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    Swal.fire({
        title: '¿Iniciar sesión como otro usuario?',
        html: `Estás a punto de iniciar sesión como:<br><strong>${userName}</strong><br><br>
              <div class="alert alert-warning small text-start">
                  <i class="bi bi-exclamation-triangle"></i> 
                  Esta acción quedará registrada y podrás regresar a tu sesión original.
              </div>`,
        icon: 'warning',
        showCancelButton: true,
        buttonsStyling: false,
        background: isDark ? '#1f2937' : '#ffffff',
        color: isDark ? '#e5e7eb' : '#212529',
        customClass: {
            popup: isDark ? 'swal2-dark' : '',
            confirmButton: 'btn btn-warning',
            cancelButton: isDark ? 'btn btn-outline-light' : 'btn btn-secondary'
        },
        confirmButtonText: '<i class="bi bi-person-check me-1"></i> Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?p=sudo_login';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'target_user';
            input.value = userId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<style>
/* -------- Estilos base -------- */
.avatar-lg {
    width: 80px; height: 80px;
    font-size: 32px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.profile-card, .permissions-card {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
}
.perm-box {
    background-color: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    transition: transform 0.2s;
}
.perm-box:hover {
    transform: translateY(-2px);
}
.badge { font-weight: 500; font-size: 0.8rem; }

/* -------- Tema oscuro -------- */

[data-bs-theme="dark"] .profile-card,
[data-bs-theme="dark"] .permissions-card {
    background-color: #1f2937;
    color: #e5e7eb;
}
[data-bs-theme="dark"] .perm-box {
    background-color: #2d3748;
    border-color: #3a4658;
}
[data-bs-theme="dark"] .text-muted {
    color: #9ca3af !important;
}

/* Estilos para badges de zonas */
.badge.bg-light.text-dark.border {
    font-size: 0.75rem;
    padding: 0.25em 0.6em;
}

/* Modal personalizado */
#modalZonas .modal-header {
    background-color: var(--encabezado-color, #4e73df);
    color: white;
}
#modalZonas .modal-header .btn-close {
    filter: brightness(0) invert(1);
}

/* SweetAlert2: tema oscuro */
.swal2-dark {
    background-color: #1f2937 !important;
    color: #e5e7eb !important;
    border: 1px solid #374151;
}
.swal2-dark .swal2-title {
    color: #f3f4f6;
}
.swal2-dark .swal2-html-container {
    color: #d1d5db;
}
.swal2-dark .swal2-icon.swal2-warning {
    border-color: #f59e0b;
    color: #f59e0b;
}
.swal2-dark .swal2-actions .btn {
    box-shadow: none;
}
</style>