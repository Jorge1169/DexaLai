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

switch ($usuarioData['tipo']) {
    case 100: $tipoUsuario = 'Administrador'; $badgeClass = 'bg-danger'; break;
    case 50:  $tipoUsuario = 'Usuario A';     $badgeClass = 'bg-primary'; break;
    case 30:  $tipoUsuario = 'Usuario B';     $badgeClass = 'bg-info'; break;
    case 10:  $tipoUsuario = 'Usuario C';     $badgeClass = 'bg-secondary'; break;
    default:  $tipoUsuario = 'Desconocido';   $badgeClass = 'bg-warning text-dark'; break;
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

                        <div class="row g-3">
                            <?php 
                            $permisos = [
                                'a' => 'Proveedores', 
                                'b' => 'Clientes', 
                                'c' => 'Productos', 
                                'd' => 'Almacenes', 
                                'e' => 'Transportistas',
                                'f' => 'Recolección'
                            ];
                            foreach ($permisos as $key => $label): ?>
                                <div class="col-6 col-md-4">
                                    <div class="perm-box text-center p-3 rounded-3">
                                        <div class="fw-medium mb-2"><?= $label ?></div>
                                        <span class="badge <?= ($usuarioData[$key] == 1) ? 'bg-success' : 'bg-secondary' ?> me-1">
                                            <i class="bi bi-plus-circle"></i> Crear
                                        </span>
                                        <span class="badge <?= ($usuarioData[$key.'1'] == 1) ? 'bg-success' : 'bg-secondary' ?>">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>

                        <div class="row g-3 mt-2">
                            <?php
                            $extras = [
                                'af' => ['Facturas', 'Actualizar'],
                                'acr' => ['Contra R.', 'Actualizar'],
                                'acc' => ['Autorizar', 'Administrativo'],
                                'en_correo' => ['Enviar correos', 'Correo'],
                                'prec' => ['Dar de alta precios', 'Precios'],
                                'zona_adm' => ['Ver todas las zonas', 'Zonas']
                            ];
                            foreach ($extras as $key => [$label, $desc]): ?>
                                <div class="col-6 col-md-4">
                                    <div class="perm-box text-center p-3 rounded-3">
                                        <div class="fw-medium mb-2"><?= $desc ?></div>
                                        <span class="badge <?= ($usuarioData[$key] == 1) ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ($usuarioData[$key] == 1) ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-x-circle"></i>'?> <?= $label ?>
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
    Swal.fire({
        title: '¿Iniciar sesión como otro usuario?',
        html: `Estás a punto de iniciar sesión como:<br><strong>${userName}</strong><br><br>
              <div class="alert alert-warning small text-start">
                  <i class="bi bi-exclamation-triangle"></i> 
                  Esta acción quedará registrada y podrás regresar a tu sesión original.
              </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
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
</style>