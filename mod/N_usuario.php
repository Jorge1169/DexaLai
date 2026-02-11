<?php
/**
 * Nuevo Usuario - DexaLai
 * Formulario para crear nuevos usuarios usando el sistema de permisos profesional
 */

// Verificar permisos (solo admin puede crear usuarios)
if ($TipoUserSession != 100) {
    alert("No tienes permisos para crear usuarios", 0, "usuarios");
    exit();
}

// Procesar formulario
if (isset($_POST['guardar01'])) {
    try {
        // Preparar datos básicos
        $UsuarioData = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'pass' => md5($_POST['pass']),
            'tipo' => intval($_POST['tipo'] ?? 10),
            'zona' => isset($_POST['zonas']) ? implode(',', $_POST['zonas']) : '0',
        ];

        // Agregar permisos desde el catálogo
        foreach (PERMISOS_CATALOGO as $nombre => $config) {
            $columna = $config['columna'];
            $UsuarioData[$columna] = isset($_POST[$columna]) ? 1 : 0;
        }

        // Campos adicionales no en catálogo
        $UsuarioData['zona_adm'] = isset($_POST['zona_adm']) ? 1 : 0;

        // Insertar usuario
        $columns = implode(', ', array_keys($UsuarioData));
        $placeholders = str_repeat('?,', count($UsuarioData) - 1) . '?';
        $sql = "INSERT INTO usuarios ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        $types = str_repeat('s', count($UsuarioData));
        $values = array_values($UsuarioData);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Usuario registrado exitosamente", 1, "usuarios");
            logActivity('CREAR', 'Dio de alta un nuevo usuario: ' . $UsuarioData['usuario']);
        } else {
            alert("Error al registrar el usuario", 0, "N_usuario");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_usuario");
    }
}

// Obtener grupos de permisos
$gruposPermisos = getPermisosFormulario();
$tiposUsuario = getTiposUsuarioSelect();
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h5>
            <a href="?p=usuarios" class="btn btn-sm btn-danger">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="" id="formNuevoUsuario">
                
                <!-- Información Básica -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Información Básica</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input name="nombre" type="text" class="form-control" id="nombre" required 
                                       placeholder="Nombre del usuario">
                            </div>
                            <div class="col-md-4">
                                <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                <input name="correo" type="email" class="form-control" id="correo" required
                                       placeholder="correo@ejemplo.com">
                            </div>
                            <div class="col-md-4">
                                <label for="usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <input name="usuario" type="text" class="form-control" id="usuario" required
                                       placeholder="nombre_usuario">
                            </div>
                            <div class="col-md-4">
                                <label for="pass" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input name="pass" type="password" class="form-control" id="pass" value="12345" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('pass')">
                                        <i class="bi bi-eye" id="pass-icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Contraseña por defecto: 12345</small>
                            </div>
                            <div class="col-md-4">
                                <label for="tipo" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                <select name="tipo" class="form-select" id="tipo" required>
                                    <?php foreach ($tiposUsuario as $valor => $nombre): ?>
                                        <option value="<?= $valor ?>" <?= $valor == 10 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="zonas[]" id="zona_todas" value="0" checked>
                                <label class="form-check-label fw-bold text-primary" for="zona_todas">
                                    <i class="bi bi-globe me-1"></i>Todas las zonas
                                </label>
                            </div>
                            <hr>
                            <div class="row">
                                <?php
                                $zonasQuery = $conn_mysql->query("SELECT * FROM zonas WHERE status = '1' ORDER BY id_zone");
                                while ($zona = mysqli_fetch_array($zonasQuery)):
                                ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input zona-checkbox" type="checkbox" 
                                                   name="zonas[]" id="zona_<?= $zona['id_zone'] ?>" 
                                                   value="<?= $zona['id_zone'] ?>" disabled>
                                            <label class="form-check-label" for="zona_<?= $zona['id_zone'] ?>">
                                                <?= htmlspecialchars($zona['nom']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="zona_adm" id="zona_adm" value="1">
                                <label class="form-check-label" for="zona_adm">
                                    <i class="bi bi-eye me-1"></i>Ver datos de todas las zonas (sin restricción)
                                </label>
                            </div>
                        </div>
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
                                                       id="perm_<?= $config['columna'] ?>" value="1">
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
                                                       id="perm_<?= $config['columna'] ?>" value="1">
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
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="<?= $config['columna'] ?>" 
                                                       id="perm_<?= $config['columna'] ?>" value="1">
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

                <!-- Botones de acción -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="?p=usuarios" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                    </a>
                    <button type="submit" name="guardar01" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar Usuario
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
    const form = document.getElementById('formNuevoUsuario');
    
    // Lógica para "Todas las zonas"
    zonaTodasCheckbox.addEventListener('change', function() {
        zonaCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.disabled = this.checked;
        });
    });
    
    // Lógica cuando se selecciona una zona individual
    zonaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                zonaTodasCheckbox.checked = false;
            }
        });
    });
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const zonasSeleccionadas = document.querySelectorAll('input[name="zonas[]"]:checked');
        
        if (zonasSeleccionadas.length === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Seleccione al menos una zona', 'error');
            return false;
        }
        
        const pass = document.getElementById('pass').value;
        if (pass.length < 5) {
            e.preventDefault();
            Swal.fire('Error', 'La contraseña debe tener al menos 5 caracteres', 'error');
            return false;
        }
        
        const usuario = document.getElementById('usuario').value;
        if (usuario.length < 3) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre de usuario debe tener al menos 3 caracteres', 'error');
            return false;
        }
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