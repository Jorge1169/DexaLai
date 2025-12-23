<?php
// Verificar permisos (solo admin puede crear usuarios)
if ($TipoUserSession != 100) {
    alert("No tienes permisos para crear usuarios", 0, "usuarios");
    exit();
}

if (isset($_POST['guardar01'])) {
    try {
        $UsuarioData = [
            'nombre' => $_POST['nombre'] ?? '',
            'correo' => $_POST['correo'] ?? '',
            'usuario' => $_POST['usuario'] ?? '',
            'pass' => md5($_POST['pass']), // Encriptación MD5
            'tipo' => $_POST['tipo'] ?? 10, // Por defecto Usuario C (10)
            'a' => $_POST['a'] ?? 0,
            'b' => $_POST['b'] ?? 0,
            'c' => $_POST['c'] ?? 0,
            'd' => $_POST['d'] ?? 0,
            'e' => $_POST['e'] ?? 0,
            'f' => $_POST['f'] ?? 0,
            'a1' => $_POST['a1'] ?? 0,
            'b1' => $_POST['b1'] ?? 0,
            'c1' => $_POST['c1'] ?? 0,
            'd1' => $_POST['d1'] ?? 0,
            'e1' => $_POST['e1'] ?? 0,
            'f1' => $_POST['f1'] ?? 0,
            'zona' => isset($_POST['zonas']) ? implode(',', $_POST['zonas']) : '0', // Zonas separadas por comas
            'af' => $_POST['af'] ?? 0,
            'acr' => $_POST['acr'] ?? 0,
            'acc' => $_POST['acc'] ?? 0,
            'en_correo' => $_POST['en_correo'] ?? 0,
            'prec' => $_POST['prec'] ?? 0,
            'zona_adm' => $_POST['zona_adm'] ?? 0
        ];

        // Insertar usuario con MySQLi
        $columns = implode(', ', array_keys($UsuarioData));
        $placeholders = str_repeat('?,', count($UsuarioData) - 1) . '?';
        $sql = "INSERT INTO usuarios ($columns) VALUES ($placeholders)";
        $stmt = $conn_mysql->prepare($sql);
        
        // Pasar los valores en el orden correcto
        $types = str_repeat('s', count($UsuarioData)); // Todos como strings
        $stmt->bind_param($types, ...array_values($UsuarioData));
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Usuario registrado exitosamente", 1, "usuarios");
            logActivity('CREAR', 'Dio de alta un nuevo usuario');
        } else {
            alert("Error al registrar el usuario", 0, "N_usuario");
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "N_usuario");
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Usuario</h5>
            <a href="?p=usuarios">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="" id="formNuevoUsuario">
                <!-- Sección de información básica -->
                <div class="form-section">
                    <h5 class="section-header">Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input name="correo" type="email" class="form-control" id="correo" required>
                        </div>
                        <div class="col-md-4">
                            <label for="usuario" class="form-label">Nombre de Usuario</label>
                            <input name="usuario" type="text" class="form-control" id="usuario" required>
                        </div>
                        <div class="col-md-3">
                            <label for="pass" class="form-label">Contraseña</label>
                            <input name="pass" type="password" class="form-control" id="pass" value="12345" required>
                            <small class="text-muted">Contraseña por defecto: 12345</small>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo de Usuario</label>
                            <select name="tipo" class="form-select" id="tipo" required>
                                <option value="10">Usuario C</option>
                                <option value="30">Usuario B</option>
                                <option value="50">Usuario A</option>
                                <option value="100">Administrador</option>
                            </select>
                        </div>
                        
                        <!-- NUEVA SECCIÓN DE ZONAS CON CHECKBOXES -->
                        <div class="col-md-12">
                            <label class="form-label">Zonas Asignadas</label>
                            <div class="border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                                <!-- Checkbox para "Todas las zonas" -->
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="zonas[]" id="zona_todas" value="0" checked>
                                    <label class="form-check-label fw-bold" for="zona_todas">Todas las zonas</label>
                                </div>
                                
                                <div class="row">
                                    <?php
                                    $zonasQuery = $conn_mysql->query("SELECT * FROM zonas WHERE status = '1' ORDER BY id_zone");
                                    
                                    while ($zona = mysqli_fetch_array($zonasQuery)) {
                                        ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input zona-checkbox" type="checkbox" 
                                                       name="zonas[]" id="zona_<?=$zona['id_zone']?>" 
                                                       value="<?=$zona['id_zone']?>" disabled>
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
                        <!-- FIN NUEVA SECCIÓN -->
                        
                        <div class="col-md-3">
                            <label for="af" class="form-label">Actualizar Facturas</label>
                            <select name="af" class="form-select" id="af" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Actualizar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="acr" class="form-label">Actualizar Contra R.</label>
                            <select name="acr" class="form-select" id="acr" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Actualizar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="acc" class="form-label">Administrativo</label>
                            <select name="acc" class="form-select" id="acc" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Autorizar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="en_correo" class="form-label">Enviar correos</label>
                            <select name="en_correo" class="form-select" id="en_correo" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Autorizar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="prec" class="form-label">Dar de alta precios</label>
                            <select name="prec" class="form-select" id="prec" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Autorizar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="zona_adm" class="form-label">Ver todas las zonas</label>
                            <select name="zona_adm" class="form-select" id="zona_adm" required>
                                <option value="0">Sin permiso</option>
                                <option value="1">Autorizar</option>
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
                                        <input class="form-check-input" type="checkbox" name="a" id="a" value="1">
                                        <label class="form-check-label" for="a">Proveedores (Crear)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="b" id="b" value="1">
                                        <label class="form-check-label" for="b">Clientes (Crear)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="c" id="c" value="1">
                                        <label class="form-check-label" for="c">Productos (Crear)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="d" id="d" value="1">
                                        <label class="form-check-label" for="d">Almacenes (Crear)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="e" id="e" value="1">
                                        <label class="form-check-label" for="e">Transportistas (Crear)</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="f" id="f" value="1">
                                        <label class="form-check-label" for="f">Recolección (Crear)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Permisos de Edición</div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="a1" id="a1" value="1">
                                        <label class="form-check-label" for="a1">Proveedores (Editar)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="b1" id="b1" value="1">
                                        <label class="form-check-label" for="b1">Clientes (Editar)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="c1" id="c1" value="1">
                                        <label class="form-check-label" for="c1">Productos (Editar)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="d1" id="d1" value="1">
                                        <label class="form-check-label" for="d1">Almacenes (Editar)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="e1" id="e1" value="1">
                                        <label class="form-check-label" for="e1">Transportistas (Editar)</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="f1" id="f1" value="1">
                                        <label class="form-check-label" for="f1">Recolección (Editar)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar</button>
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
        });
    });
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        // Validar zonas
        const zonasSeleccionadas = document.querySelectorAll('input[name="zonas[]"]:checked');
        
        if (zonasSeleccionadas.length === 0) {
            e.preventDefault();
            alert('Por favor, seleccione al menos una zona');
            return false;
        }
        
        // Verificar si "Todas" está seleccionada junto con otras zonas
        const todasSeleccionada = document.getElementById('zona_todas').checked;
        const zonasIndividualesSeleccionadas = Array.from(zonaCheckboxes).filter(cb => cb.checked);
        
        if (todasSeleccionada && zonasIndividualesSeleccionadas.length > 0) {
            e.preventDefault();
            alert('Si selecciona "Todas las zonas", no puede seleccionar zonas individuales');
            return false;
        }
        
        // Validar contraseña
        const pass = document.getElementById('pass').value;
        if (pass.length < 5) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 5 caracteres');
            return false;
        }
        
        // Validar usuario único (opcional - puedes agregar AJAX si lo necesitas)
        const usuario = document.getElementById('usuario').value;
        if (usuario.length < 3) {
            e.preventDefault();
            alert('El nombre de usuario debe tener al menos 3 caracteres');
            return false;
        }
    });
    
    // Validar contraseña en tiempo real
    document.getElementById('pass').addEventListener('input', function() {
        const pass = this.value;
        const feedback = document.createElement('small');
        feedback.className = 'form-text';
        
        if (pass.length < 5) {
            feedback.style.color = 'red';
            feedback.textContent = 'La contraseña es muy corta (mínimo 5 caracteres)';
        } else if (pass.length < 8) {
            feedback.style.color = 'orange';
            feedback.textContent = 'Contraseña aceptable';
        } else {
            feedback.style.color = 'green';
            feedback.textContent = 'Contraseña segura';
        }
        
        // Actualizar o crear el mensaje de feedback
        const existingFeedback = this.parentNode.querySelector('.form-text');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        this.parentNode.appendChild(feedback);
    });
});
</script>