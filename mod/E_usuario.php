<?php
/**
 * Editar Usuario - DexaLai
 * Formulario para editar usuarios usando el sistema de permisos profesional
 */

// Obtener ID del usuario a editar
$id_usuario = $_GET['id'] ?? 0;

// Consultar datos del usuario
$usuarioQuery = $conn_mysql->prepare("SELECT * FROM usuarios WHERE id_user = ?");
$usuarioQuery->bind_param('i', $id_usuario);
$usuarioQuery->execute();
$usuarioData = $usuarioQuery->get_result()->fetch_assoc();

if (!$usuarioData) {
    alert("Usuario no encontrado", 0, "usuarios");
    exit();
}

// Verificar permisos usando las variables de sesión globales
// Admin puede editar cualquier usuario, otros solo pueden editar su propio perfil
$esAdmin = ($TipoUserSession == 100);
$esMiPerfil = ($usuarioData['id_user'] == $idUser);

if (!$esAdmin && !$esMiPerfil) {
    alert("No tienes permisos para editar este usuario", 0, "usuarios");
    logActivity('EDITAR', 'Intento editar el usuario '. $id_usuario);
    exit();
}

// Solo password si no es admin (editando su propio perfil)
$soloPassword = !$esAdmin;

// Obtener grupos de permisos
$gruposPermisos = getPermisosFormulario();
$tiposUsuario = getTiposUsuarioSelect();
$resumenTipos = getResumenTiposUsuario();

// Procesar formulario de actualización
if (isset($_POST['guardar01'])) {
    try {
        if ($soloPassword) {
            // Solo actualizar contraseña si se proporcionó
            if (!empty($_POST['pass'])) {
                $pass = md5($_POST['pass']);
                $sql = "UPDATE usuarios SET pass = ? WHERE id_user = ?";
                $stmt = $conn_mysql->prepare($sql);
                $stmt->bind_param('si', $pass, $id_usuario);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    alert("Contraseña actualizada exitosamente", 1, "V_usuarios&id=$id_usuario");
                    logActivity('EDITAR', 'Actualizo contraseña '. $id_usuario);
                } else {
                    alert("No se realizaron cambios", 1, "E_usuario&id=$id_usuario");
                }
            } else {
                alert("No se proporcionó nueva contraseña", 0, "E_usuario&id=$id_usuario");
            }
        } else {
            // Proceso normal para admin
            $UsuarioData = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'usuario' => trim($_POST['usuario'] ?? ''),
                'tipo' => intval($_POST['tipo'] ?? 10),
                'zona' => isset($_POST['zonas']) ? implode(',', $_POST['zonas']) : '0',
            ];

            // Agregar permisos desde el catálogo
            foreach (PERMISOS_CATALOGO as $nombre => $config) {
                $columna = $config['columna'];
                $UsuarioData[$columna] = isset($_POST[$columna]) ? 1 : 0;
            }

            // Campos adicionales
            // 'zona_adm' eliminado: el control se realiza con 'zona' (Zonas Asignadas)

            // Si se proporcionó nueva contraseña
            if (!empty($_POST['pass'])) {
                $UsuarioData['pass'] = md5($_POST['pass']);
            }

            // Preparar consulta UPDATE
            $setClause = implode(' = ?, ', array_keys($UsuarioData)) . ' = ?';
            $sql = "UPDATE usuarios SET $setClause WHERE id_user = ?";
            $stmt = $conn_mysql->prepare($sql);
            
            $values = array_values($UsuarioData);
            $values[] = $id_usuario;
            
            $types = str_repeat('s', count($UsuarioData)) . 'i';
            $stmt->bind_param($types, ...$values);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                alert("Usuario actualizado exitosamente", 1, "V_usuarios&id=$id_usuario");
                logActivity('EDITAR', 'Actualizo el usuario '. $id_usuario);
            } else {
                alert("No se realizaron cambios en el usuario", 1, "E_usuario&id=$id_usuario");
            }
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_usuario&id=$id_usuario");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-<?= $soloPassword ? 'key' : 'pencil-square' ?> me-2"></i>
                <?= $soloPassword ? 'Cambiar mi contraseña' : 'Editar Usuario' ?>
            </h5>
            <a href="?p=V_usuarios&id=<?= $id_usuario ?>" class="btn btn-sm btn-danger">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="" id="formEditarUsuario">
                <?php if ($soloPassword): ?>
                    <!-- Formulario simplificado solo para contraseña -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Nueva Contraseña</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="pass" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input name="pass" type="password" class="form-control" id="pass" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('pass')">
                                            <i class="bi bi-eye" id="pass-icon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="pass2" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="pass2" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('pass2')">
                                            <i class="bi bi-eye" id="pass2-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulario completo para admin -->
                    
                    <!-- Información Básica -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Información Básica</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                    <input name="nombre" type="text" class="form-control" id="nombre" 
                                           value="<?= htmlspecialchars($usuarioData['nombre'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input name="correo" type="email" class="form-control" id="correo" 
                                           value="<?= htmlspecialchars($usuarioData['correo'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                    <input name="usuario" type="text" class="form-control" id="usuario" 
                                           value="<?= htmlspecialchars($usuarioData['usuario'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="pass" class="form-label">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input name="pass" type="password" class="form-control" id="pass" 
                                               placeholder="Dejar en blanco para no cambiar">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('pass')">
                                            <i class="bi bi-eye" id="pass-icon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="tipo" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                    <select name="tipo" class="form-select" id="tipo" required>
                                        <?php foreach ($tiposUsuario as $valor => $nombre): ?>
                                            <option value="<?= $valor ?>" <?= ($usuarioData['tipo'] == $valor) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($nombre) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ayuda: Qué puede hacer cada tipo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>¿Qué puede hacer cada tipo?</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Los tipos definen permisos base (reportes, administración, utilerías). Los switches de abajo añaden permisos específicos.</p>
                            <div class="row g-3">
                                <?php foreach ($resumenTipos as $tipo => $info): ?>
                                    <div class="col-md-6 col-lg-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex align-items-center mb-2 gap-2">
                                                <span class="badge <?= htmlspecialchars($info['badge']) ?>">Tipo <?= htmlspecialchars($tipo) ?></span>
                                                <strong><?= htmlspecialchars($info['nombre']) ?></strong>
                                            </div>
                                            <p class="mb-2 small"><?= htmlspecialchars($info['descripcion']) ?></p>
                                            <?php if (!empty($info['permisos_base'])): ?>
                                                <ul class="mb-0 small ps-3">
                                                    <?php foreach ($info['permisos_base'] as $permisoTexto): ?>
                                                        <li><?= htmlspecialchars($permisoTexto) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="mb-0 small text-muted">Sin permisos base; depende de los permisos específicos.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Zonas -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Zonas Asignadas</h6>
                        </div>
                        <div class="card-body">
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php $todasZonas = ($usuarioData['zona'] == '0' || empty($usuarioData['zona'])); ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="zonas[]" id="zona_todas" 
                                           value="0" <?= $todasZonas ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-primary" for="zona_todas">
                                        <i class="bi bi-globe me-1"></i>Todas las zonas
                                    </label>
                                </div>
                                <hr>
                                <div class="row">
                                    <?php
                                    $zonasQuery = $conn_mysql->query("SELECT * FROM zonas WHERE status = '1' ORDER BY id_zone");
                                    $zonasUsuarioArr = explode(',', $usuarioData['zona'] ?? '');
                                    
                                    while ($zona = mysqli_fetch_array($zonasQuery)):
                                        $checked = !$todasZonas && in_array($zona['id_zone'], $zonasUsuarioArr);
                                    ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input zona-checkbox" type="checkbox" 
                                                       name="zonas[]" id="zona_<?= $zona['id_zone'] ?>" 
                                                       value="<?= $zona['id_zone'] ?>" 
                                                       <?= $checked ? 'checked' : '' ?>
                                                       <?= $todasZonas ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="zona_<?= $zona['id_zone'] ?>">
                                                    <?= htmlspecialchars($zona['nom']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                            <!-- 'zona_adm' eliminado del formulario: el sistema usa Zonas Asignadas -->
                        </div>
                    </div>

                    <!-- Permisos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Permisos del Usuario</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Permisos de Creación -->
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-plus-circle me-2"></i>Permisos de Creación
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($gruposPermisos['creacion'] ?? [] as $nombre => $config): ?>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="<?= $config['columna'] ?>" 
                                                           id="perm_<?= $config['columna'] ?>" value="1"
                                                           <?= ($usuarioData[$config['columna']] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="perm_<?= $config['columna'] ?>">
                                                        <?= htmlspecialchars($config['descripcion']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Permisos de Edición -->
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-info">
                                        <div class="card-header bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-pencil-square me-2"></i>Permisos de Edición
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($gruposPermisos['edicion'] ?? [] as $nombre => $config): ?>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="<?= $config['columna'] ?>" 
                                                           id="perm_<?= $config['columna'] ?>" value="1"
                                                           <?= ($usuarioData[$config['columna']] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="perm_<?= $config['columna'] ?>">
                                                        <?= htmlspecialchars($config['descripcion']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permisos Especiales -->
                            <div class="card border-warning">
                                <div class="card-header bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-star me-2"></i>Permisos Especiales
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($gruposPermisos['especial'] ?? [] as $nombre => $config): ?>
                                            <?php if (($config['columna'] ?? '') === 'zona_adm') continue; ?>
                                                    <div class="col-md-4 mb-2">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="<?= $config['columna'] ?>" 
                                                                   id="perm_<?= $config['columna'] ?>" value="1"
                                                                   <?= ($usuarioData[$config['columna']] == 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="perm_<?= $config['columna'] ?>">
                                                                <?= htmlspecialchars($config['descripcion']) ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="?p=V_usuarios&id=<?= $id_usuario ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                    </a>
                    <button type="submit" name="guardar01" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zonaTodasCheckbox = document.getElementById('zona_todas');
    const zonaCheckboxes = document.querySelectorAll('.zona-checkbox');
    const form = document.getElementById('formEditarUsuario');
    
    if (zonaTodasCheckbox) {
        zonaTodasCheckbox.addEventListener('change', function() {
            zonaCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.disabled = this.checked;
            });
        });
        
        zonaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    zonaTodasCheckbox.checked = false;
                }
            });
        });
    }
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        <?php if ($soloPassword): ?>
        const pass1 = document.getElementById('pass').value;
        const pass2 = document.getElementById('pass2').value;
        
        if (pass1 !== pass2) {
            e.preventDefault();
            Swal.fire('Error', 'Las contraseñas no coinciden', 'error');
            return false;
        }
        
        if (pass1.length < 5) {
            e.preventDefault();
            Swal.fire('Error', 'La contraseña debe tener al menos 5 caracteres', 'error');
            return false;
        }
        <?php else: ?>
        const zonasSeleccionadas = document.querySelectorAll('input[name="zonas[]"]:checked');
        
        if (zonasSeleccionadas.length === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Seleccione al menos una zona', 'error');
            return false;
        }
        <?php endif; ?>
    });
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>