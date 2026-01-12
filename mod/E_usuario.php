<?php
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

// Verificar permisos
if ($TipoUserSession != 100) {
    // Si no es admin, solo puede editar su propio perfil
    if ($usuarioData['id_user'] != $idUser) {
        alert("No tienes permisos para editar este usuario", 0, "usuarios");
        logActivity('EDITAR', 'Intento editar el usuario '. $id_usuario);
        exit();
    }
    
    // Si es usuario normal editando su propio perfil, solo permitir cambio de contraseña
    $soloPassword = true;
} else {
    // Admin tiene acceso completo
    $soloPassword = false;
    
}

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
                    logActivity('EDITAR', 'No realizo cambios en el usuario '. $id_usuario);
                }
            } else {
                alert("No se proporcionó nueva contraseña", 0, "E_usuario&id=$id_usuario");
                logActivity('EDITAR', 'No proporciono nueva contraseña para el usuario '. $id_usuario);
            }
        } else {
            // Proceso normal para admin
            $UsuarioData = [
                'nombre' => $_POST['nombre'] ?? '',
                'correo' => $_POST['correo'] ?? '',
                'usuario' => $_POST['usuario'] ?? '',
                'tipo' => $_POST['tipo'] ?? 10,
                'a' => $_POST['a'] ?? 0,
                'b' => $_POST['b'] ?? 0,
                'c' => $_POST['c'] ?? 0,
                'd' => $_POST['d'] ?? 0,
                'e' => $_POST['e'] ?? 0,
                'f' => $_POST['f'] ?? 0,
                'g' => $_POST['g'] ?? 0,
                'h' => $_POST['h'] ?? 0,
                'a1' => $_POST['a1'] ?? 0,
                'b1' => $_POST['b1'] ?? 0,
                'c1' => $_POST['c1'] ?? 0,
                'd1' => $_POST['d1'] ?? 0,
                'e1' => $_POST['e1'] ?? 0,
                'f1' => $_POST['f1'] ?? 0,
                'g1' => $_POST['g1'] ?? 0,
                'h1' => $_POST['h1'] ?? 0,
                'zona' => isset($_POST['zonas']) ? implode(',', $_POST['zonas']) : '0',
                'af' => $_POST['af'] ?? 0,
                'acr' => $_POST['acr'] ?? 0,
                'acc' => $_POST['acc'] ?? 0,
                'en_correo' => $_POST['en_correo'] ?? 0,
                'prec' => $_POST['prec'] ?? 0,
                'zona_adm' => $_POST['zona_adm'] ?? 0
            ];

            // Si se proporcionó nueva contraseña
            if (!empty($_POST['pass'])) {
                $UsuarioData['pass'] = md5($_POST['pass']);
            }

            // Preparar consulta UPDATE
            $setClause = implode(' = ?, ', array_keys($UsuarioData)) . ' = ?';
            $sql = "UPDATE usuarios SET $setClause WHERE id_user = ?";
            $stmt = $conn_mysql->prepare($sql);
            
            // Valores para bind_param
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
                logActivity('EDITAR', 'No realizo cambios en el usuario '. $id_usuario);
            }
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_usuario&id=$id_usuario");
        logActivity('EDITAR', 'Error al querer realizar cambios en el usuario '. $id_usuario);
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $soloPassword ? 'Cambiar mi contraseña' : 'Editar Usuario' ?></h5>
            <a href="?p=V_usuarios&id=<?=$id_usuario?>">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <?php if ($soloPassword): ?>
                    <!-- Formulario simplificado solo para contraseña -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="pass" class="form-label">Nueva Contraseña</label>
                            <input name="pass" type="password" class="form-control" id="pass" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pass2" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="pass2" required>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulario completo para admin -->
                    <!-- Sección de información básica -->
                    <div class="form-section">
                        <h5 class="section-header">Información Básica</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input name="nombre" type="text" class="form-control" id="nombre" 
                                value="<?= htmlspecialchars($usuarioData['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input name="correo" type="email" class="form-control" id="correo" 
                                value="<?= htmlspecialchars($usuarioData['correo'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="usuario" class="form-label">Nombre de Usuario</label>
                                <input name="usuario" type="text" class="form-control" id="usuario" 
                                value="<?= htmlspecialchars($usuarioData['usuario'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="pass" class="form-label">Nueva Contraseña</label>
                                <input name="pass" type="password" class="form-control" id="pass" 
                                placeholder="Dejar en blanco para no cambiar">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de Usuario</label>
                                <select name="tipo" class="form-select" id="tipo" required>
                                    <option value="100" <?= ($usuarioData['tipo'] == 100) ? 'selected' : '' ?>>Administrador</option>
                                    <option value="50" <?= ($usuarioData['tipo'] == 50) ? 'selected' : '' ?>>Usuario A</option>
                                    <option value="30" <?= ($usuarioData['tipo'] == 30) ? 'selected' : '' ?>>Usuario B</option>
                                    <option value="10" <?= ($usuarioData['tipo'] == 10) ? 'selected' : '' ?>>Usuario C</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Zonas Asignadas</label>
                                <div class="border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                                 
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="zonas[]" id="zona_todas" value="0" 
                                        <?= ($usuarioData['zona'] == 0 || empty($usuarioData['zona'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="zona_todas">Todas las zonas</label>
                                    </div>
                                    
                                    <div class="row">
                                        <?php
                                        $zonasQuery = $conn_mysql->query("SELECT * FROM zonas WHERE status = '1' ORDER BY id_zone");
                                        $zonasUsuario = explode(',', $usuarioData['zona'] ?? '');
                                        
                                        while ($zona = mysqli_fetch_array($zonasQuery)) {
                                            $checked = false;
                                            
                                            
                                            if ($usuarioData['zona'] == 0) {
                                                $checked = true; 
                                            } else {
                                                $checked = in_array($zona['id_zone'], $zonasUsuario);
                                            }
                                            ?>
                                            <div class="col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input zona-checkbox" type="checkbox" 
                                                    name="zonas[]" id="zona_<?=$zona['id_zone']?>" 
                                                    value="<?=$zona['id_zone']?>" 
                                                    <?= $checked ? 'checked' : '' ?>
                                                    <?= ($usuarioData['zona'] == 0) ? 'disabled' : '' ?>>
                                                    <label class="form-check-label" for="zona_<?=$zona['id_zone']?>">
                                                        <?= htmlspecialchars($zona['nom']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <small class="text-muted">Seleccione "Todas las zonas" o zonas específicas</small>
                            </div>
                            <div class="col-md-3">
                                <label for="af" class="form-label">Actualizar Facturas</label>
                                <select name="af" class="form-select" id="af" required>
                                    <option value="0" <?= ($usuarioData['af'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['af'] == 1) ? 'selected' : '' ?>>Actualizar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="acr" class="form-label">Actualizar Contra R.</label>
                                <select name="acr" class="form-select" id="acr" required>
                                    <option value="0" <?= ($usuarioData['acr'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['acr'] == 1) ? 'selected' : '' ?>>Actualizar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="acc" class="form-label">Administrativo</label>
                                <select name="acc" class="form-select" id="acc" required>
                                    <option value="0" <?= ($usuarioData['acc'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['acc'] == 1) ? 'selected' : '' ?>>Autorizar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="en_correo" class="form-label">Enviar correos</label>
                                <select name="en_correo" class="form-select" id="en_correo" required>
                                    <option value="0" <?= ($usuarioData['en_correo'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['en_correo'] == 1) ? 'selected' : '' ?>>Autorizar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="prec" class="form-label">Dar de alta precios</label>
                                <select name="prec" class="form-select" id="prec" required>
                                    <option value="0" <?= ($usuarioData['prec'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['prec'] == 1) ? 'selected' : '' ?>>Autorizar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="zona_adm" class="form-label">Ver todas las zonas</label>
                                <select name="zona_adm" class="form-select" id="zona_adm" required>
                                    <option value="0" <?= ($usuarioData['zona_adm'] == 0) ? 'selected' : '' ?>>Sin permiso</option>
                                    <option value="1" <?= ($usuarioData['zona_adm'] == 1) ? 'selected' : '' ?>>Autorizar</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de permisos -->
                    <div class="form-section mt-4">
                        <h5 class="section-header">Permisos del Usuario</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Permisos de Creación</div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="a" id="a" value="1" 
                                            <?= ($usuarioData['a'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="a">Proveedores (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="b" id="b" value="1" 
                                            <?= ($usuarioData['b'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="b">Clientes (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="c" id="c" value="1" 
                                            <?= ($usuarioData['c'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="c">Productos (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="d" id="d" value="1" 
                                            <?= ($usuarioData['d'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="d">Almacenes (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="e" id="e" value="1" 
                                            <?= ($usuarioData['e'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="e">Transportistas (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="f" id="f" value="1" 
                                            <?= ($usuarioData['f'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="f">Recolección (Crear)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="g" id="g" value="1" 
                                            <?= ($usuarioData['g'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="g">Captación (Crear zona especial)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="h" id="h" value="1" 
                                            <?= ($usuarioData['h'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="h">Ventas (Crear zona especial)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Permisos de Edición</div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="a1" id="a1" value="1" 
                                            <?= ($usuarioData['a1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="a1">Proveedores (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="b1" id="b1" value="1" 
                                            <?= ($usuarioData['b1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="b1">Clientes (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="c1" id="c1" value="1" 
                                            <?= ($usuarioData['c1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="c1">Productos (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="d1" id="d1" value="1" 
                                            <?= ($usuarioData['d1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="d1">Almacenes (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="e1" id="e1" value="1" 
                                            <?= ($usuarioData['e1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="e1">Transportistas (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="f1" id="f1" value="1" 
                                            <?= ($usuarioData['f1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="f1">Recolección (Editar)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="g1" id="g1" value="1" 
                                            <?= ($usuarioData['g1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="g1">Captación (Editar zona especial)</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="h1" id="h1" value="1" 
                                            <?= ($usuarioData['h1'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="h1">Ventas (Editar zona especial)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const zonaTodasCheckbox = document.getElementById('zona_todas');
    const zonaCheckboxes = document.querySelectorAll('.zona-checkbox');
    
    // Lógica para "Todas las zonas"
    zonaTodasCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Deshabilitar y desmarcar todas las zonas individuales
            zonaCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.disabled = true;
            });
        } else {
            // Habilitar todas las zonas individuales
            zonaCheckboxes.forEach(checkbox => {
                checkbox.disabled = false;
            });
        }
    });
    
    // Lógica cuando se selecciona una zona individual
    zonaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Si se selecciona alguna zona individual, desmarcar "Todas"
            if (this.checked) {
                zonaTodasCheckbox.checked = false;
                zonaTodasCheckbox.disabled = false;
            }
            
            // Verificar si hay alguna zona seleccionada
            const algunaSeleccionada = Array.from(zonaCheckboxes).some(cb => cb.checked);
            
            if (!algunaSeleccionada) {
                zonaTodasCheckbox.disabled = false;
            }
        });
    });
    
    // Validación del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const zonasSeleccionadas = document.querySelectorAll('input[name="zonas[]"]:checked');
        
        if (zonasSeleccionadas.length === 0) {
            e.preventDefault();
            alert('Por favor, seleccione al menos una zona');
            return false;
        }
        
        // Si "Todas" está seleccionada, asegurarse de que solo tenga el valor 0
        const todasSeleccionada = document.getElementById('zona_todas').checked;
        
        if (todasSeleccionada && zonasSeleccionadas.length > 1) {
            e.preventDefault();
            alert('Si selecciona "Todas las zonas", no puede seleccionar zonas individuales');
            return false;
        }
    });
});
</script>
<script>
// Validación de contraseña para usuarios normales
    <?php if ($soloPassword): ?>
        document.querySelector('form').addEventListener('submit', function(e) {
            const pass1 = document.getElementById('pass').value;
            const pass2 = document.getElementById('pass2').value;

            if (pass1 !== pass2) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }

            if (pass1.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });
    <?php endif; ?>
</script>