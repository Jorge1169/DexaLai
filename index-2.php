

<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (isset($_SESSION['id_cliente'])) {
    echo "<!-- SESIÓN ACTIVA: " . $_SESSION['id_cliente'] . " -->";
} else {
    echo "<!-- NO HAY SESIÓN -->";
}
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
// Función para manejar cookies
    const cookieManager = {
        get: (name) => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },
        set: (name, value, days) => {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
        }
    };

// Función para aplicar el tema
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        const icon = document.getElementById('themeIcon');
        const text = document.getElementById('themeText');

        if (theme === 'dark') {
            icon.classList.remove('bi-moon-fill');
            icon.classList.add('bi-sun-fill');
            text.textContent = 'Tema Claro';
        } else {
            icon.classList.remove('bi-sun-fill');
            icon.classList.add('bi-moon-fill');
            text.textContent = 'Tema Oscuro';
        }

        cookieManager.set('theme', theme, 30);
    }

// Función para cambiar el tema
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    }

// Control del menú offcanvas
    function setupMenu() {
        const menuBtn = document.getElementById('menu-btn');
        const overlay = document.getElementById('menu-overlay');
        const offcanvasMenu = new bootstrap.Offcanvas('#offcanvasMenu');

        menuBtn.addEventListener('click', function() {
            offcanvasMenu.toggle();
        });

    // Mostrar/ocultar overlay cuando el menú se muestra/oculta
        document.getElementById('offcanvasMenu').addEventListener('show.bs.offcanvas', function () {
            overlay.classList.add('show');
        });

        document.getElementById('offcanvasMenu').addEventListener('hide.bs.offcanvas', function () {
            overlay.classList.remove('show');
        });

    // Cerrar menú al hacer clic en el overlay
        overlay.addEventListener('click', function() {
            offcanvasMenu.hide();
        });
    }

// Aplicar el tema guardado al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = cookieManager.get('theme') || 'light';
        applyTheme(savedTheme);
        document.getElementById('themeToggleBtn').addEventListener('click', toggleTheme);
        setupMenu();

    // Activar elemento del menú al hacer clic
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
</script>
</head>
<body>
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
<!-- Inicio de sesion -->
<?php

if(isset($_SESSION['id_cliente'])){ // si hay una session iniciada este muestra el muestra dashboard

    $Pruev0 = $conn_mysql->query("SELECT * FROM usuarios where id_user = '".$_SESSION['id_cliente']."'");
    $Pruev1 = mysqli_fetch_array($Pruev0);// Aqui esta guardada la seccion
    $Usuario = $Pruev1['nombre'];// usuario que logiado
    $idUser = $Pruev1['id_user'];
    $zona_user = $Pruev1['zona'];
    $TipoUserSession = $Pruev1['tipo'];
    $perMi = $Pruev1['a'].','.$Pruev1['b'].','.$Pruev1['c'].','.$Pruev1['d'].','.$Pruev1['e'].','.$Pruev1['a1'].','.$Pruev1['b1'].','.$Pruev1['c1'].','.$Pruev1['d1'].','.$Pruev1['e1'].','.$Pruev1['af'].','.$Pruev1['acr'].','.$Pruev1['acc'].','.$Pruev1['f'].','.$Pruev1['f1'].','.$Pruev1['en_correo'].','.$Pruev1['prec'];
    // ============================================================================
    // VERIFICAR CONTRASEÑA GENÉRICA - AGREGAR ESTO
    // ============================================================================
    $has_generic_password = hasGenericPassword($_SESSION['id_cliente']);
     // Si tiene contraseña genérica, mostrar modal inmediatamente
    if ($has_generic_password && !isset($_SESSION['showing_password_modal'])) {
        $_SESSION['showing_password_modal'] = true;
        echo "
        <script>
        $(document).ready(function() {
            $('#passwordChangeModal').modal('show');
        });
        </script>
        ";
    }

    $dias = array("DOMINGO","LUNES","MARTES","MIÉRCOLES","JUEVES","VIERNES","SÁBADO");
    $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");

    $numero_dia = date('w');
    $dia = date('d');
    $numero_mes = date('n') - 1;
    $anio = date('Y');

    $fecha_formateada = $dias[$numero_dia] . ", " . $dia . " DE " . $meses[$numero_mes] . " DE " . $anio;
    $perm = permisos($TipoUserSession, $perMi);

    ?>

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
            <!-- Catálogos -->
            <div class="accordion accordion-flush" id="menuAccordion">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-3 py-2 menu-item" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCatalogos">
                            <i class="bi bi-collection me-2"></i>
                            <span>CATÁLOGOS</span>
                        </button>
                    </h2>
                    <div id="collapseCatalogos" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=clientes" class="boton-Subm">Clientes</a>
                            <a href="?p=proveedores" class="boton-Subm">Proveedores</a>
                            <a href="?p=transportes" class="boton-Subm">Transportes</a>
                            <a href="?p=productos" class="boton-Subm">Productos</a>
                        </div>
                    </div>
                </div>
                <!-- Ajustes -->
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-3 py-2 menu-item" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAjustes">
                            <i class="bi bi-clipboard-pulse me-2"></i>
                            <span>FLUJO</span>
                        </button>
                    </h2>
                    <div id="collapseAjustes" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=recoleccion" class="boton-Subm">Recolección</a>
                            <!--<a href="?p=compras" class="boton-Subm">Compra</a>
                            <a href="?p=ventas" class="boton-Subm">Ventas</a>-->
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0" <?= $perm['REPORTES'];?>>
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-3 py-2 menu-item" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReportes">
                            <i class="bi bi-clipboard2-data me-2"></i>
                            <span>Reportes</span>
                        </button>
                    </h2>
                    <div id="collapseReportes" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=contra_recibos" class="boton-Subm" <?= $perm['UTILERIAS'];?>>Contra Recibos</a>
                            <a href="?p=reporte_recole" class="boton-Subm">Reporte de recolección</a>
                            <!--<a href="?p=reporte_productos" class="boton-Subm" <?= $perm['UTILERIAS'];?>>Productos</a>-->
                            <!--<a href="?p=reporte_bodegas" class="boton-Subm" <?= $perm['UTILERIAS'];?>>Bodegas</a>-->
                            <!--<a href="?p=reporte_mov" class="boton-Subm">Movimientos</a>-->
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0" <?= $perm['UTILERIAS'];?>>
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-3 py-2 menu-item" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUtilerias">
                            <i class="bi bi-wrench-adjustable-circle me-2"></i> 
                            <span>Utilerias</span>
                        </button>
                    </h2>
                    <!--<div id="collapseUtilerias" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=masiva_flujo" class="boton-Subm">Subir Masivos</a>
                        </div>
                    </div>-->
                    <div id="collapseUtilerias" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=importar_recolecciones" class="boton-Subm">Importar R</a>
                            <a <?= $perm['ACT_AC'] ?> href="?p=reportes_actividad" class="boton-Subm">Actividad de Usuarios</a>
                        </div>
                    </div>
                </div>
                <!-- Usuarios -->
                <div  class="accordion-item border-0" <?= $perm['ADMIN'];?>>
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-3 py-2 menu-item" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsuarios">
                            <i class="bi bi-people me-2"></i>
                            <span>Usuarios</span>
                        </button>
                    </h2>
                    <div id="collapseUsuarios" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0 submenu">
                            <a href="?p=usuarios" class="boton-Subm">Lista de Usuarios</a>
                        </div>
                    </div>
                </div>
                
            </div>
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
<style>

    <?php
    $logos = ($zona_user == 0) ? $_SESSION['selected_zone'] : $zona_user ;
    ?>
</style>
<nav class="navbar navbar-expand-lg encabezado">
    <div class="container-fluid">
        <div class="d-flex align-items-center w-100">
            <!-- Menú hamburguesa -->
            <button id="menu-btn" class="menu-btn-header me-2 me-md-3">
                <i class="bi bi-list fs-5"></i>
            </button>
            
            <!-- Logo -->
            <a class="navbar-brand me-auto me-md-3" href="?p=inicio">
                <img src="img/logos/logo<?=$logos?>.png" alt="Logo" class="img-fluid">
            </a>
            
            <!-- Fecha -->
            <div class="date-display me-2 me-md-3 d-none d-lg-block">
                <i class="bi bi-calendar2"></i> <?=$fecha_formateada?>
            </div>
            <!-- Selector de Zona -->
            <div class="zone-selector me-2 me-md-3">
                <form id="zoneForm" method="post" action="procesar_zona.php">
                    <select class="sleczone" name="selected_zone" id="zoneSelect">
                        <?php
            // Verificar el permiso de zona del usuario
                        if ($zona_user == 0) {
                // Usuario puede ver todas las zonas
                            $zones_query = mysqli_query($conn_mysql, "SELECT * FROM zonas WHERE status = 1 ORDER BY nom");

                            if (!$zones_query) {
                    // Si hay error en la consulta
                                echo '<option value="0" selected>Todas</option>';
                                error_log("Error al cargar zonas: " . mysqli_error($conn_mysql));
                            } else {
                    // Verificar si ya hay una zona seleccionada en la sesión
                                $selected_zone = $_SESSION['selected_zone'] ?? '0';

                    // Opción para "Todas las zonas"
                                echo '<option value="0"' . ($selected_zone === '0' ? ' selected' : '') . '>Todas las zonas</option>';

                    // Opciones para cada zona
                                while ($zone = mysqli_fetch_assoc($zones_query)) {
                                    $selected = ($selected_zone == $zone['id_zone']) ? ' selected' : '';
                                    echo '<option value="' . $zone['id_zone'] . '"' . $selected . '>' . 
                                    htmlspecialchars($zone['nom']) . '</option>';
                                }

                    // Liberar el resultado
                                mysqli_free_result($zones_query);
                            }
                        } else {
                // Usuario solo puede ver su zona específica
                            $zone_query = mysqli_query($conn_mysql, "SELECT * FROM zonas WHERE id_zone = '$zona_user' AND status = 1");

                            if ($zone_query && mysqli_num_rows($zone_query) > 0) {
                                $zone = mysqli_fetch_assoc($zone_query);

                    // Mostrar solo la zona asignada (y seleccionada por defecto)
                                echo '<option value="' . $zone['id_zone'] . '" selected>' . 
                                htmlspecialchars($zone['nom']) . '</option>';

                    // Forzar la sesión a esta zona
                                $_SESSION['selected_zone'] = $zone['id_zone'];

                    // Ocultar el select (si lo prefieres)
                                echo '<style>#zoneSelect { display: none; }</style>';

                    // Liberar el resultado
                                mysqli_free_result($zone_query);
                            } else {
                    // Si no encuentra la zona, mostrar opción por defecto
                                echo '<option value="0" selected>Zona no disponible</option>';
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
                        <li><p class="dropdown-Tex"><?=$Pruev1['nombre']?><p></li>
                            <li><a class="dropdown-item" href="?p=V_usuarios&id=<?=$idUser?>"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="?p=E_usuario&id=<?=$idUser?>"><i class="bi bi-gear me-2"></i>Ajustes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Opción de Salir con confirmación especial en modo Sudo -->
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
 $zona_seleccionada = $_SESSION['selected_zone'] ?? '0';// ZONA SELECCIONADA 
 ?>
 <!-- Modal para selección de zona inicial -->
<?php if ($zona_user === '0' && (!isset($_SESSION['zona_inicial_seleccionada']) || $_SESSION['zona_inicial_seleccionada'] !== true)): ?>
<div class="modal fade" id="zonaInicialModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="zonaInicialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="zonaInicialModalLabel">
                    <i class="bi bi-compass me-2"></i>Selección de Zona
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bienvenido <?= $Usuario ?></strong>. Tienes acceso a todas las zonas. Por favor selecciona una zona para trabajar o elige "Todas las zonas".
                </div>
                
                <div class="mb-3">
                    <label for="zonaInicialSelect" class="form-label">Selecciona una zona:</label>
                    <select class="form-select" id="zonaInicialSelect" size="5">
                        <option value="0">🌎 Todas las zonas</option>
                        <?php
                        $zones_query = mysqli_query($conn_mysql, "SELECT * FROM zonas WHERE status = 1 ORDER BY nom");
                        while ($zone = mysqli_fetch_assoc($zones_query)) {
                            echo '<option value="' . $zone['id_zone'] . '">' . 
                                 htmlspecialchars($zone['nom']) . ' - ' . 
                                 htmlspecialchars($zone['PLANTA']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-text">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Puedes cambiar de zona en cualquier momento usando el selector en la barra superior.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmZonaBtn">
                    <i class="bi bi-check-circle me-2"></i>Confirmar Zona
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zonaModal = new bootstrap.Modal(document.getElementById('zonaInicialModal'));
    zonaModal.show();
    
    // Manejar la confirmación de zona
    document.getElementById('confirmZonaBtn').addEventListener('click', function() {
        const selectedZone = document.getElementById('zonaInicialSelect').value;
        
        // Deshabilitar botón durante el proceso
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Procesando...';
        
        // Usar procesar_zona.php para todo
        fetch('procesar_zona.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'selected_zone=' + selectedZone + '&ajax=true&zona_inicial=true'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Cerrar modal y recargar la página
                zonaModal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data.message || 'Error al seleccionar la zona');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error de conexión. Intenta nuevamente.',
                confirmButtonText: 'Entendido'
            });
            
            // Rehabilitar botón
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmar Zona';
        });
    });
    
    // Permitir selección con doble click
    document.getElementById('zonaInicialSelect').addEventListener('dblclick', function() {
        document.getElementById('confirmZonaBtn').click();
    });
    
    // Permitir selección con Enter
    document.getElementById('zonaInicialSelect').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('confirmZonaBtn').click();
        }
    });
});
</script>
<?php endif; ?>

 <div class="contenido">
    <div  class="row g-3 mt-3"></div>
<!-- ESTADO DE LA SELECCION -->
<?php
if(file_exists("mod/".$p.".php")){
    include "mod/".$p.".php";
} else if ($p == "sudo_login") {
    include "mod/sudo_login.php";
} else if ($p == "sudo_logout") {
    include "mod/sudo_logout.php";
} else {
  ?>
  <div class="error mx-auto" data-text="404">404</div>

  <?php
  echo "<i>No se ha encontrado el modulo <b>".$p."</b> <a href='./'>Regresar</a></i>";
}
?>
<?php
} else {
  if(isset($_SESSION['id_cliente'])){
    redir("./");
}

if (isset($iniciar)) {
    $nombre = clear($nombre);
    $passsword = clear($passsword);
    $hashedPassword = md5($passsword); // Calcular el hash MD5 de la contraseña ingresada
        //$hashedPassword = md5($passsword); // Calcular el hash MD5 de la contraseña ingresada

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
         // ============================================================================
        // REGISTRAR LOG DE INTENTO FALLIDO
        // ============================================================================
        logActivity('LOGIN_FAILED', 'Intento de inicio de sesión fallido para usuario: ' . $nombre);

        alert("Los datos no son válidos", 0, 'login');
        //redir("?p=login");
    }
}
?>
<style>

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

<?php
}?>
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
                        confirmButtonText: 'Continuar'
                    }).then(() => {
                        // Cerrar el modal
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                        
                        // Recargar la página para actualizar el estado
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
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
            if (<?php echo $has_generic_password ? 'true' : 'false'; ?>) {
                event.preventDefault();
            }
        });
    }
});
</script>