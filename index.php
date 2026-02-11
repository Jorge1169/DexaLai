<?php 
include "config/conexiones.php";
if(!isset($p)){
  $p = "inicio";
}else{
  $p = $p;
}
?>
<!DOCTYPE html> 
<html data-bs-theme="light" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dexa Lai</title>
    <script>
        function getSavedTheme() {
            if (typeof localStorage !== 'undefined' && localStorage.getItem('theme')) {
                return localStorage.getItem('theme');
            }
            const match = document.cookie.match(/theme=([^;]+)/);
            return match ? match[1] : 'light';
        }
        
        // Aplicar el tema ANTES de que cargue cualquier CSS
        (function() {
            const savedTheme = getSavedTheme();
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    
    <!-- Estilos iniciales para evitar flash -->
    <style>
        body {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        body.theme-loaded {
            opacity: 1;
        }
    </style>
    <link rel="shortcut icon" href="img/logos/lai_esfera_BN.png"/>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- externo select -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- DataTables con Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"> 
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- pdf -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <!-- En el head o antes de cerrar el body -->
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.dataTables.min.css">
    <script src="https://cdn.datatables.net/fixedheader/3.3.2/js/dataTables.fixedHeader.min.js"></script>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="config/stil.css">
    <script>
        // ============================================
        // FUNCIONES PRINCIPALES (manteniendo tu código)
        // ============================================

        // Función para aplicar el tema (unificada)
        function applyTheme(theme) {
            // Aplicar inmediatamente al documento
            document.documentElement.setAttribute('data-bs-theme', theme);
            
            // Guardar en localStorage (más rápido)
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('theme', theme);
            }
            
            // También guardar en cookie para compatibilidad con PHP
            const date = new Date();
            date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.cookie = `theme=${theme};expires=${date.toUTCString()};path=/`;
            
            // Actualizar UI si los elementos existen
            const icon = document.getElementById('themeIcon');
            const text = document.getElementById('themeText');
            
            if (icon && text) {
                if (theme === 'dark') {
                    icon.classList.remove('bi-moon-fill');
                    icon.classList.add('bi-sun-fill');
                    text.textContent = 'Tema Claro';
                } else {
                    icon.classList.remove('bi-sun-fill');
                    icon.classList.add('bi-moon-fill');
                    text.textContent = 'Tema Oscuro';
                }
            }
        }

        // Función para cambiar el tema
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        }

        // Control del menú offcanvas (TU CÓDIGO ORIGINAL)
        function setupMenu() {
            const menuBtn = document.getElementById('menu-btn');
            const overlay = document.getElementById('menu-overlay');
            const offcanvasMenu = new bootstrap.Offcanvas('#offcanvasMenu');

            if (menuBtn) {
                menuBtn.addEventListener('click', function() {
                    offcanvasMenu.toggle();
                });
            }

            // Mostrar/ocultar overlay cuando el menú se muestra/oculta
            const offcanvasElement = document.getElementById('offcanvasMenu');
            if (offcanvasElement && overlay) {
                offcanvasElement.addEventListener('show.bs.offcanvas', function () {
                    overlay.classList.add('show');
                });

                offcanvasElement.addEventListener('hide.bs.offcanvas', function () {
                    overlay.classList.remove('show');
                });

                // Cerrar menú al hacer clic en el overlay
                overlay.addEventListener('click', function() {
                    offcanvasMenu.hide();
                });
            }
        }

        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Obtener y aplicar el tema guardado
            const savedTheme = getSavedTheme();
            applyTheme(savedTheme);
            
            // 2. Mostrar contenido (eliminar opacidad)
            document.body.classList.add('theme-loaded');
            
            // 3. Configurar botón de tema si existe
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', toggleTheme);
            }
            
            // 4. Configurar menú
            setupMenu();
            
            // 5. Activar elemento del menú al hacer clic (tu código original)
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</head>
<?php
// Obtener el color de la zona seleccionada
$color_zona = '#324B70'; // Color por defecto (DexaLai / todas las zonas)

if (isset($_SESSION['selected_zone']) && $_SESSION['selected_zone'] > 0) {
    $zone_id = intval($_SESSION['selected_zone']);
    
    $zone_query = mysqli_query($conn_mysql, "SELECT color FROM zonas WHERE id_zone = $zone_id LIMIT 1");
    if ($zone_data = mysqli_fetch_assoc($zone_query)) {
        $color_zona = $zone_data['color'];
    }
}
?>
<body style="--color-primario: <?= htmlspecialchars($color_zona) ?>;">
    <!-- Modal para cambio de contraseña genérica -->
    <div class="modal fade" id="passwordChangeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="passwordChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="passwordChangeModalLabel">
                        <i class="bi bi-shield-exclamation me-2"></i>Cambio de Contraseña Requerido
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Seguridad:</strong> Estás utilizando una contraseña genérica. Por seguridad, debes cambiar tu contraseña inmediatamente.
                    </div>

                    <form id="passwordChangeForm" method="post" action="procesar_cambio_password.php">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" placeholder="Mínimo 6 caracteres">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Repite tu nueva contraseña">
                            <div class="invalid-feedback" id="passwordError">Las contraseñas no coinciden</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="savePasswordBtn">
                        <i class="bi bi-check-circle me-2"></i>Cambiar Contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
if(isset($_SESSION['id_cliente'])){ // si hay una session iniciada este muestra el dashboard
    $user_id = $_SESSION['id_cliente'];
    $Pruev0 = $conn_mysql->query("SELECT * FROM usuarios where id_user = '$user_id'");
    $Pruev1 = mysqli_fetch_array($Pruev0);
    
    if (!$Pruev1) {
        session_destroy();
        redir("?p=login");
    }
    
    $Usuario = $Pruev1['nombre'];
    $idUser = $Pruev1['id_user'];
    $zona_user = $Pruev1['zona'];
    $AdminZonas = $Pruev1['zona_adm'];
    $TipoUserSession = $Pruev1['tipo'];
    
    // ============================================================================
    // NUEVA LÓGICA DE ZONAS - CORREGIDA
    // ============================================================================
    
    // Determinar zonas permitidas
    $zonasPermitidas = [];
    if ($zona_user == '0' || empty($zona_user)) {
        $zonasPermitidas = 'todas';
    } else {
        $zonasPermitidas = explode(',', $zona_user);
    }
    
    // Lógica para determinar si necesita seleccionar zona
    $necesitaSeleccionarZona = false;
    $mostrarModalZona = false;
    
    if ($zonasPermitidas === 'todas') {
    // Usuario con acceso a todas las zonas
        if (!isset($_SESSION['selected_zone'])) {
            $necesitaSeleccionarZona = true;
            $mostrarModalZona = true;
        }
    } else {
    // Usuario con zonas específicas
        if (empty($zonasPermitidas)) {
        // No tiene zonas asignadas - asignar la primera zona disponible
            $zones_query = mysqli_query($conn_mysql, "SELECT id_zone FROM zonas WHERE status = 1 ORDER BY id_zone LIMIT 1");
            if ($zone = mysqli_fetch_assoc($zones_query)) {
                $_SESSION['selected_zone'] = $zone['id_zone'];
            }
        } elseif (count($zonasPermitidas) == 1) {
        // Solo tiene una zona - asignarla automáticamente
            $_SESSION['selected_zone'] = $zonasPermitidas[0];
        } else {
        // Tiene múltiples zonas
            if (!isset($_SESSION['selected_zone']) || !in_array($_SESSION['selected_zone'], $zonasPermitidas)) {
                $necesitaSeleccionarZona = true;
                $mostrarModalZona = true;
            }
        }
    }

    
    // ============================================================================
    // VERIFICAR CONTRASEÑA GENÉRICA
    // ============================================================================
    $has_generic_password = hasGenericPassword($user_id);
    $password_already_changed = isset($_SESSION['password_changed_successfully']) 
    && $_SESSION['password_changed_successfully'] === true;
    
    // ============================================================================
    // LÓGICA PARA MOSTRAR MODALES
    // ============================================================================
    $scriptModales = '';
    
    if ($has_generic_password && !$password_already_changed) {
        // Mostrar modal de contraseña primero
        $scriptModales .= "
        <script>
        $(document).ready(function() {
            setTimeout(function() {
                var passwordModal = new bootstrap.Modal(document.getElementById('passwordChangeModal'), {
                    backdrop: 'static',
                    keyboard: false
                    });
                    passwordModal.show();
                    
                // Cuando se cierre el modal de contraseña, mostrar el de zona si es necesario
                    $('#passwordChangeModal').on('hidden.bs.modal', function () {
                        " . ($mostrarModalZona ? "
                            setTimeout(function() {
                                var zoneModal = new bootstrap.Modal(document.getElementById('zoneSelectModal'), {
                                    backdrop: 'static',
                                    keyboard: false
                                    });
                                    zoneModal.show();
                                    }, 300);" : "") . "
                        });
                        }, 1000);
                        });
                        </script>";
                    } elseif ($mostrarModalZona) {
        // Mostrar solo modal de zona
                        $scriptModales .= "
                        <script>
                        $(document).ready(function() {
                            setTimeout(function() {
                                var zoneModal = new bootstrap.Modal(document.getElementById('zoneSelectModal'), {
                                    backdrop: 'static',
                                    keyboard: false
                                    });
                                    zoneModal.show();
                                    }, 1000);
                                    });
                                    </script>";
                                }
                                
    // Imprimir los scripts de modales
                                echo $scriptModales;
                                
    // ============================================================================
    // DATOS PARA LA INTERFAZ
    // ============================================================================
                                $dias = array("DOMINGO","LUNES","MARTES","MIÉRCOLES","JUEVES","VIERNES","SÁBADO");
                                $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");

                                $numero_dia = date('w');
                                $dia = date('d');
                                $numero_mes = date('n') - 1;
                                $anio = date('Y');

                                $fecha_formateada = $dias[$numero_dia] . ", " . $dia . " DE " . $meses[$numero_mes] . " DE " . $anio;
                                
                                // Cargar permisos directamente desde datos del usuario (ya no necesita $perMi)
                                $perm = permisos($TipoUserSession, $Pruev1);
                                ?>
<!-- Modal para seleccionar zona al iniciar sesión -->
<div class="modal fade" id="zoneSelectModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="zoneSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header encabezado-col text-white">
                <h5 class="modal-title" id="zoneSelectModalLabel">
                    <i class="bi bi-geo-alt-fill me-2"></i>Elige la zona con la que trabajarás
                </h5>
            </div>
            <div class="modal-body">
                <form id="zoneSelectForm" method="post" action="procesar_zona.php">
                    <div class="mb-3">
                        <label for="zoneSelectInicio" class="form-label">Elige la zona en la que vas a trabajar:</label>
                        <select class="form-select" size="5" name="selected_zone" id="zoneSelectInicio" required>
                            <?php
                            // Mostrar opciones según las zonas permitidas
                            if ($zonasPermitidas === 'todas') {
                                // Si puede ver todas las zonas
                                $zones_query = mysqli_query($conn_mysql, "SELECT * FROM zonas WHERE status = 1 ORDER BY nom");
                                
                                while ($zone = mysqli_fetch_assoc($zones_query)) {
                                    echo '<option value="'.$zone['id_zone'].'">'.htmlspecialchars($zone['nom']).'</option>';
                                }
                            } else {
                                // Si solo puede ver zonas específicas
                                if (!empty($zonasPermitidas)) {
                                    $placeholders = str_repeat('?,', count($zonasPermitidas) - 1) . '?';
                                    $stmt = $conn_mysql->prepare("SELECT * FROM zonas WHERE id_zone IN ($placeholders) AND status = 1 ORDER BY nom");
                                    $stmt->bind_param(str_repeat('i', count($zonasPermitidas)), ...$zonasPermitidas);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    while ($zone = $result->fetch_assoc()) {
                                        $selected = ($_SESSION['selected_zone'] ?? 0) == $zone['id_zone'] ? ' selected' : '';
                                        echo '<option value="'.$zone['id_zone'].'"'.$selected.'>'.htmlspecialchars($zone['nom']).'</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                </form>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Después de seleccionar la zona donde quieres trabajar, puedes cambiarla en cualquier momento desde la lista desplegable ubicada junto a la fecha.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveZoneBtn">
                    <i class="bi bi-check-circle me-2"></i>Confirmar Zona
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Overlay para el menú -->
<div id="menu-overlay" class="offcanvas-overlay"></div>

<!-- Menú lateral usando Offcanvas de Bootstrap -->
<div class="offcanvas offcanvas-start offcanvas-custom" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Menú Principal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <nav class="nav flex-column p-3">
            <!-- Catálogos - Siempre visible para todos los tipos de zona -->
            <!-- Catálogos -->
            <?php if (mostrarSeccionMenu('catalogos', $conn_mysql)): ?>
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button-2 collapsed px-3 py-2 menu-item" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#collapseCatalogos">
                        <i class="bi bi-collection me-2"></i>
                        <span>CATÁLOGOS</span>
                    </button>
                </h2>
                <div id="collapseCatalogos" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                    <div class="accordion-body p-0 submenu">
                        <a href="?p=clientes" class="boton-Subm <?= obtenerClaseMenu('clientes', $conn_mysql) ?>">
                            Clientes
                        </a>
                        <a href="?p=proveedores" class="boton-Subm <?= obtenerClaseMenu('proveedores', $conn_mysql) ?>">
                            Proveedores
                        </a>
                        <a href="?p=transportes" class="boton-Subm <?= obtenerClaseMenu('transportes', $conn_mysql) ?>">
                            Transportes
                        </a>
                        <a href="?p=productos" class="boton-Subm <?= obtenerClaseMenu('productos', $conn_mysql) ?>">
                            Productos
                        </a>
                        <!-- NUEVO: ALMACÉN - SOLO PARA ZONAS MEO -->
                        <a href="?p=almacenes" class="boton-Subm <?= obtenerClaseMenu('almacenes', $conn_mysql) ?>">
                            Almacenes
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <?php if (mostrarSeccionMenu('flujo', $conn_mysql)): ?>
            <div class="accordion-item border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button-2 collapsed px-3 py-2 menu-item" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#collapseFlujo">
                    <i class="bi bi-clipboard-pulse me-2"></i>
                    <span>FLUJO</span>
                </button>
            </h2>
            <div id="collapseFlujo" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                <div class="accordion-body p-0 submenu">
                    <a href="?p=recoleccion" class="boton-Subm <?= obtenerClaseMenu('recoleccion', $conn_mysql) ?>">
                        Recolección
                    </a>
                </div>
            </div>
            <div id="collapseFlujo" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                <div class="accordion-body p-0 submenu">
                    <a href="?p=captacion" class="boton-Subm <?= obtenerClaseMenu('captacion', $conn_mysql) ?>">
                        Captación
                    </a>
                    <a href="?p=almacenes_info" class="boton-Subm <?= obtenerClaseMenu('almacenes_info', $conn_mysql) ?>">
                        Almacenes
                    </a>
                    <a href="?p=ventas" class="boton-Subm <?= obtenerClaseMenu('ventas', $conn_mysql) ?>">
                        Ventas
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

<!-- Reportes -->
<?php if (mostrarSeccionMenu('reportes', $conn_mysql)): ?>
    <div class="accordion-item border-0">
        <h2 class="accordion-header">
            <button class="accordion-button-2 collapsed px-3 py-2 menu-item" type="button" 
            data-bs-toggle="collapse" data-bs-target="#collapseReportes"
            <?= $perm['REPORTES'] ?>>
            <i class="bi bi-clipboard2-data me-2"></i>
            <span>Reportes</span>
        </button>
    </h2>
    <div id="collapseReportes" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
        <div class="accordion-body p-0 submenu">
            <a href="?p=contra_recibos" 
            class="boton-Subm <?= obtenerClaseMenu('contra_recibos', $conn_mysql, $perm) ?>">
            Contra Recibos
        </a>
        
        <?php 
        $moduloReporte = obtenerUrlSegunZona('reporte_recole', $conn_mysql);
        ?>
        <a href="?p=<?= $moduloReporte ?>" 
         class="boton-Subm <?= obtenerClaseMenu($moduloReporte, $conn_mysql) ?>">
         Reporte de recolección
     </a>
 </div>
</div>
</div>
<?php endif; ?>

<!-- Utilerias -->
<?php if (mostrarSeccionMenu('utilerias', $conn_mysql)): ?>
    <div class="accordion-item border-0">
        <h2 class="accordion-header">
            <button class="accordion-button-2 collapsed px-3 py-2 menu-item" type="button" 
            data-bs-toggle="collapse" data-bs-target="#collapseUtilerias"
            <?= $perm['UTILERIAS'] ?>>
            <i class="bi bi-wrench-adjustable-circle me-2"></i> 
            <span>Utilerias</span>
        </button>
    </h2>
    <div id="collapseUtilerias" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
        <div class="accordion-body p-0 submenu">
            <a href="?p=importar_recolecciones" 
            class="boton-Subm <?= obtenerClaseMenu('importar_recolecciones', $conn_mysql) ?>">
            Importar R
        </a>
        <a href="?p=reportes_actividad" 
        class="boton-Subm <?= obtenerClaseMenu('reportes_actividad', $conn_mysql, $perm) ?>">
        Actividad de Usuarios
    </a>
    <a href="?p=ia_test" 
    class="boton-Subm <?= obtenerClaseMenu('ia_test', $conn_mysql, $perm) ?>">
    I.A
</a>
</div>
</div>
</div>
<?php endif; ?>

<!-- Usuarios -->
<?php if (mostrarSeccionMenu('usuarios', $conn_mysql)): ?>
    <div class="accordion-item border-0">
        <h2 class="accordion-header">
            <button class="accordion-button-2 collapsed px-3 py-2 menu-item" type="button" 
            data-bs-toggle="collapse" data-bs-target="#collapseUsuarios"
            <?= $perm['ADMIN'] ?>>
            <i class="bi bi-people me-2"></i>
            <span>Usuarios</span>
        </button>
    </h2>
    <div id="collapseUsuarios" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
        <div class="accordion-body p-0 submenu">
            <a href="?p=usuarios" 
            class="boton-Subm <?= obtenerClaseMenu('usuarios', $conn_mysql) ?>">
            Lista de Usuarios
        </a>
    </div>
</div>
</div>
<?php endif; ?>
</nav>

<!-- Pie del menú con botón de tema -->
<div class="mt-auto p-3 border-top">
    <button id="themeToggleBtn" class="btn btn-sw w-100">
        <i id="themeIcon" class="bi bi-sun-fill me-2"></i>
        <span id="themeText">Tema Claro</span>
    </button>
</div>
</div>
</div>

<!-- Contenido principal -->
 
<nav class="navbar navbar-expand-lg encabezado">
    <div class="container-fluid">
        <div class="d-flex align-items-center w-100">
            <!-- Menú hamburguesa -->
            <button id="menu-btn" class="menu-btn-header me-2 me-md-3">
                <i class="bi bi-list fs-5"></i>
            </button>
            <!-- BOTÓN PARA REGRESAR AL INDEX - AGREGADO -->
            <a href="./" class="inicio-btn-copy me-2 me-md-3" title="Ir al inicio">
                <i class="bi bi-house-door me-1"></i>
                <span class="d-none d-md-inline">Inicio</span>
            </a>
            <!-- Fecha -->
            <div class="date-display me-2 me-md-3 d-none d-lg-block">
                <i class="bi bi-calendar2"></i> <?= $fecha_formateada ?>
            </div>
            
            <!-- Selector de Zona -->
            <div class="zone-selector me-2 me-md-3">
                <form id="zoneForm" method="post" action="procesar_zona.php">
                    <select class="sleczone" name="selected_zone" id="zoneSelect">
                        <?php
                        $selected_zone = $_SESSION['selected_zone'] ?? 0;
                        
                        if ($zonasPermitidas === 'todas') {
                            // Usuario puede ver todas las zonas
                            $zones_query = mysqli_query($conn_mysql, "SELECT * FROM zonas WHERE status = 1 ORDER BY nom");
                            
                            // Opción para "Todas las zonas" solo si zona_adm = 1
                            
                            while ($zone = mysqli_fetch_assoc($zones_query)) {
                                $selected = ($selected_zone == $zone['id_zone']) ? ' selected' : '';
                                echo '<option value="' . $zone['id_zone'] . '"' . $selected . '>' . 
                                htmlspecialchars($zone['nom']) . '</option>';
                            }
                        } else {
                            // Usuario solo puede ver zonas específicas
                            if (!empty($zonasPermitidas)) {
                                $placeholders = str_repeat('?,', count($zonasPermitidas) - 1) . '?';
                                $stmt = $conn_mysql->prepare("SELECT * FROM zonas WHERE id_zone IN ($placeholders) AND status = 1 ORDER BY nom");
                                $stmt->bind_param(str_repeat('i', count($zonasPermitidas)), ...$zonasPermitidas);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while ($zone = $result->fetch_assoc()) {
                                    $selected = ($selected_zone == $zone['id_zone']) ? ' selected' : '';
                                    echo '<option value="' . $zone['id_zone'] . '"' . $selected . '>' . 
                                    htmlspecialchars($zone['nom']) . '</option>';
                                }
                                
                                // Si solo tiene una zona, ocultar el select
                                if (count($zonasPermitidas) == 1) {
                                    echo '<style>#zoneSelect { display: none; }</style>';
                                }
                            } else {
                                echo '<option value="0" selected>Sin zonas asignadas</option>';
                            }
                        }
                        ?>
                    </select>
                </form>
            </div>
            
            <!-- Usuario dropdown -->
            <div class="user-dropdown ms-auto">
                <div class="dropdown">
                    <button class="btn dropdown-toggle d-flex align-items-center <?= isSudoSession() ? 'btn-warning' : '' ?>" 
                        type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i>
                        <span>
                            <?= $Pruev1['usuario'] ?>
                            <?php if (isSudoSession()): ?>
                                <span class="badge bg-danger ms-1">Sudo</span>
                            <?php endif; ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if (isSudoSession()): ?>
                            <li><div class="dropdown-header text-warning">
                                <i class="bi bi-shield-check"></i> Modo Administrador
                            </div></li>
                            <li><a class="dropdown-item text-warning" href="?p=sudo_logout">
                                <i class="bi bi-arrow-left-circle me-2"></i>Regresar a mi sesión
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><p class="dropdown-Tex"><?= $Pruev1['nombre'] ?><p></li>
                            <li><a class="dropdown-item" href="?p=V_usuarios&id=<?= $idUser ?>"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="?p=E_usuario&id=<?= $idUser ?>"><i class="bi bi-gear me-2"></i>Ajustes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <?php if (isSudoSession()): ?>
                                    <a class="dropdown-item text-danger" href="#" onclick="confirmSudoLogout()">
                                        <i class="bi bi-box-arrow-right me-2"></i>Salir
                                    </a>
                                <?php else: ?>
                                    <a class="dropdown-item text-danger" href="?p=salir">
                                        <i class="bi bi-box-arrow-right me-2"></i>Salir
                                    </a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
// JavaScript para manejar el cambio de zona (solo si el select es visible)
        document.getElementById('zoneSelect')?.addEventListener('change', function() {
            this.form.submit();
        });
    </script>

    <?php
    $zona_seleccionada = $_SESSION['selected_zone'] ?? '0';
    ?>
    <div class="contenido">
        <div class="row g-3 mt-3"></div>
        <?php
    // Primero, verificar si el módulo está disponible para el tipo de zona actual
        if (moduloDisponibleParaZona($p, $conn_mysql)) {
            // Verificar permisos de acceso al módulo
            if (isset(MODULOS_PERMISOS[$p]) && !puedeAccederModulo($p, $TipoUserSession, $Pruev1)) {
                ?>
                <div class="container my-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-lg rounded-4">
                                <div class="card-body text-center p-5">
                                    <span class="badge bg-warning-subtle text-warning mb-3 px-3 py-2 rounded-pill">
                                        Acceso Restringido
                                    </span>
                                    <div class="display-1 fw-bold text-warning opacity-75 mb-3">
                                        <i class="bi bi-shield-lock"></i>
                                    </div>
                                    <h2 class="fw-bold text-body mb-3">Sin Permisos</h2>
                                    <p class="text-muted mb-4">
                                        No tienes permisos para acceder a esta sección. 
                                        Contacta al administrador si crees que deberías tener acceso.
                                    </p>
                                    <a href="./" class="btn btn-primary rounded-pill px-4">
                                        <i class="bi bi-house me-2"></i>Volver al Inicio
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            } elseif(file_exists("mod/".$p.".php")){
                include "mod/".$p.".php";
            } else if ($p == "sudo_login") {
                include "mod/sudo_login.php";
            } else if ($p == "sudo_logout") {
                include "mod/sudo_logout.php";
            } else {
                ?>
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">

                            <div class="card border-0 shadow-lg rounded-4">
                                <div class="card-body text-center p-5">

                                    <span class="badge bg-danger-subtle text-danger mb-3 px-3 py-2 rounded-pill">
                                        Error 404
                                    </span>

                                    <div class="display-1 fw-bold text-danger opacity-75 mb-3">
                                        404
                                    </div>

                                    <h3 class="fw-semibold mb-3">
                                        Módulo no disponible
                                    </h3>

                                    <p class="text-muted mb-4">
                                        El módulo
                                        <span class="fw-semibold">
                                            <?= htmlspecialchars($p) ?>
                                        </span>
                                        no existe o no tienes permisos para acceder.
                                    </p>

                                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                                        <a href="./" class="btn btn-primary btn-lg px-4">
                                            <i class="bi bi-house-door me-2"></i> Inicio
                                        </a>
                                        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg px-4">
                                            Volver
                                        </a>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <?php
            }

        } else {
        // Módulo no disponible para este tipo de zona
            ?>
            <div class="container my-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">

                        <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4">

                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 fs-1 text-danger">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-semibold">Acceso denegado</h5>
                                    <small class="text-muted">
                                        Restricción de permisos y zonas
                                    </small>
                                </div>
                            </div>

                            <p class="mb-3">
                                Este módulo no está disponible para el tipo de zona actual
                                o no cuentas con los permisos necesarios para acceder.
                            </p>

                            <a href="?p=inicio" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-house-door me-1"></i> Volver al inicio
                            </a>

                        </div>

                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        <?php
    } else {
    // Página de login
        if(isset($_SESSION['id_cliente'])){
            redir("./");
        }

        if (isset($iniciar)) {
            $nombre = clear($nombre);
            $passsword = clear($passsword);
            $hashedPassword = md5($passsword);
            
            $q = $conn_mysql->query("SELECT * FROM usuarios WHERE usuario = '$nombre' AND pass = '$hashedPassword' AND status = '1'");

            if (mysqli_num_rows($q) > 0) {
                $r = mysqli_fetch_array($q);
                $_SESSION['id_cliente'] = $r['id_user'];
                $_SESSION['username'] = $r['usuario'];
                $_SESSION['TipoUserSession'] = $r['tipo'];

            // Registrar log de login exitoso
                logActivity('LOGIN', 'Inicio de sesión exitoso');

            // Verificar si tiene contraseña genérica y guardar en sesión
                if ($r['pass'] === GENERIC_PASSWORD_MD5) {
                    $_SESSION['has_generic_password'] = true;
                }

                if (isset($return)) {
                    redir("?p=".$return);
                } else {
                    redir("./");
                }
            } else {
                logActivity('LOGIN_FAILED', 'Intento de inicio de sesión fallido para usuario: ' . $nombre);
                alert("Los datos no son válidos", 0, 'login');
            }
        }
        ?>
        <style>
            /* Estilos para el login */
        </style>
        <div class="login-container">
            <div class="login-card">
                <div class="row no-gutters">
                    <!-- Sección de branding -->
                    <div class="col-md-6 login-branding">
                        <img src="img/logos/logo.png" alt="Distribuciones Industriales Melo">
                        <h3></h3>
                        <p>Aplicación de registro de movimientos en compra y venta para <b>LAI-DKL</b> y <b>DEXA HEINEKEN</b></p>
                    </div>
                    <!-- Sección de formulario -->
                    <div class="col-md-6 login-form">
                        <h4>¡Bienvenido de nuevo!</h4>
                        <p class="subtitle">Inicia sesión para acceder a tu cuenta</p>
                        <form method="post" action="" autocomplete="on" name="loginForm">
                            <div class="mb-3 row">
                                <label for="username" class="col-sm-2 col-form-label">Usuario</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="username" name="nombre" required autocomplete="username">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password" class="col-sm-2 col-form-label">Clave</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="password" name="passsword" required autocomplete="current-password">
                                </div>
                            </div>
                            <button type="submit" name="iniciar" class="btn btn-login btn-block text-white">
                                Ingresar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<!-- Externo select -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<script>
// JavaScript para el cambio de contraseña
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('passwordChangeModal');
        if (modal) {
        // Toggle para mostrar/ocultar contraseña
            document.getElementById('togglePassword')?.addEventListener('click', function() {
                const passwordInput = document.getElementById('new_password');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });

        // Validación en tiempo real de coincidencia de contraseñas
            const confirmPasswordInput = document.getElementById('confirm_password');
            const newPasswordInput = document.getElementById('new_password');
            const passwordError = document.getElementById('passwordError');

            function validatePasswords() {
                if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.classList.add('is-invalid');
                    passwordError.style.display = 'block';
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                    passwordError.style.display = 'none';
                }
            }

            newPasswordInput?.addEventListener('input', validatePasswords);
            confirmPasswordInput?.addEventListener('input', validatePasswords);

        // Envío del formulario
            document.getElementById('savePasswordBtn')?.addEventListener('click', function() {
                const form = document.getElementById('passwordChangeForm');
                const formData = new FormData(form);

            // Validación final
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    validatePasswords();
                    return;
                }

            // Deshabilitar botón durante el envío
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Procesando...';

                fetch('procesar_cambio_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                    // Mostrar mensaje de éxito y cerrar modal
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message,
                            confirmButtonText: 'Continuar',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                            // Cerrar el modal de contraseña
                                const passwordModal = bootstrap.Modal.getInstance(document.getElementById('passwordChangeModal'));
                                passwordModal.hide();

                            // Recargar la página para actualizar el estado
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            }
                        });
                    } else {
                    // Mostrar error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonText: 'Entendido'
                        });

                    // Rehabilitar botón
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Cambiar Contraseña';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Intenta nuevamente.',
                        confirmButtonText: 'Entendido'
                    });

                // Rehabilitar botón
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Cambiar Contraseña';
                });
            });

        // Prevenir que el modal se cierre
            modal.addEventListener('hide.bs.modal', function(event) {
            // Solo permitir cerrar si no hay contraseña genérica
                if (<?php echo isset($has_generic_password) && $has_generic_password ? 'true' : 'false'; ?>) {
                    event.preventDefault();
                }
            });
        }
    });
</script>

<script>
    $(document).ready(function() {
        $('#saveZoneBtn').click(function() {
            const selectedZone = $('#zoneSelectInicio').val();
            if (!selectedZone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona una zona',
                    text: 'Debes elegir una zona antes de continuar.'
                });
                return;
            }
        // Enviar formulario
            $('#zoneSelectForm').submit();
        });
    });
</script>