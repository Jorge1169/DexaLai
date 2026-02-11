<?php
/**
 * Sistema de Permisos - DexaLai
 * 
 * Sistema profesional y extensible para manejo de permisos de usuario.
 * Reemplaza el sistema anterior basado en style="display: none"
 * 
 * @version 2.0
 */

// ============================================================================
// DEFINICIÓN DE PERMISOS - Fácil de agregar nuevos
// ============================================================================

/**
 * Catálogo de permisos disponibles en el sistema
 * Formato: 'NOMBRE_PERMISO' => ['columna' => 'columna_db', 'descripcion' => 'Descripción']
 * 
 * Para agregar un nuevo permiso:
 * 1. Agregar la columna en la tabla usuarios (si no existe)
 * 2. Agregar entrada aquí
 * 3. El sistema lo reconocerá automáticamente
 */
define('PERMISOS_CATALOGO', [
    // === Permisos de Creación ===
    'PROVEEDORES_CREAR' => ['columna' => 'a', 'descripcion' => 'Crear proveedores', 'grupo' => 'creacion'],
    'CLIENTES_CREAR' => ['columna' => 'b', 'descripcion' => 'Crear clientes', 'grupo' => 'creacion'],
    'PRODUCTOS_CREAR' => ['columna' => 'c', 'descripcion' => 'Crear productos', 'grupo' => 'creacion'],
    'ALMACENES_CREAR' => ['columna' => 'd', 'descripcion' => 'Crear almacenes', 'grupo' => 'creacion'],
    'TRANSPORTES_CREAR' => ['columna' => 'e', 'descripcion' => 'Crear transportistas', 'grupo' => 'creacion'],
    'RECOLECCION_CREAR' => ['columna' => 'f', 'descripcion' => 'Crear recolecciones', 'grupo' => 'creacion'],
    'CAPTACION_CREAR' => ['columna' => 'g', 'descripcion' => 'Crear captaciones', 'grupo' => 'creacion'],
    'VENTAS_CREAR' => ['columna' => 'h', 'descripcion' => 'Crear ventas', 'grupo' => 'creacion'],
    
    // === Permisos de Edición ===
    'PROVEEDORES_EDITAR' => ['columna' => 'a1', 'descripcion' => 'Editar proveedores', 'grupo' => 'edicion'],
    'CLIENTES_EDITAR' => ['columna' => 'b1', 'descripcion' => 'Editar clientes', 'grupo' => 'edicion'],
    'PRODUCTOS_EDITAR' => ['columna' => 'c1', 'descripcion' => 'Editar productos', 'grupo' => 'edicion'],
    'ALMACENES_EDITAR' => ['columna' => 'd1', 'descripcion' => 'Editar almacenes', 'grupo' => 'edicion'],
    'TRANSPORTES_EDITAR' => ['columna' => 'e1', 'descripcion' => 'Editar transportistas', 'grupo' => 'edicion'],
    'RECOLECCION_EDITAR' => ['columna' => 'f1', 'descripcion' => 'Editar recolecciones', 'grupo' => 'edicion'],
    'CAPTACION_EDITAR' => ['columna' => 'g1', 'descripcion' => 'Editar captaciones', 'grupo' => 'edicion'],
    'VENTAS_EDITAR' => ['columna' => 'h1', 'descripcion' => 'Editar ventas', 'grupo' => 'edicion'],
    
    // === Permisos Especiales ===
    'FACTURAS_ACTUALIZAR' => ['columna' => 'af', 'descripcion' => 'Actualizar facturas', 'grupo' => 'especial'],
    'CONTRA_RECIBOS_ACTUALIZAR' => ['columna' => 'acr', 'descripcion' => 'Actualizar contra recibos', 'grupo' => 'especial'],
    'ADMINISTRATIVO' => ['columna' => 'acc', 'descripcion' => 'Acceso administrativo', 'grupo' => 'especial'],
    'ENVIAR_CORREOS' => ['columna' => 'en_correo', 'descripcion' => 'Enviar correos', 'grupo' => 'especial'],
    'PRECIOS_GESTIONAR' => ['columna' => 'prec', 'descripcion' => 'Gestionar precios', 'grupo' => 'especial'],
    'ZONAS_VER_TODAS' => ['columna' => 'zona_adm', 'descripcion' => 'Ver todas las zonas', 'grupo' => 'especial'],
]);

/**
 * Mapeo de módulos a permisos requeridos
 * Define qué permiso se necesita para acceder a cada módulo
 */
define('MODULOS_PERMISOS', [
    // === Módulos de Creación ===
    'N_proveedor' => 'PROVEEDORES_CREAR',
    'N_cliente' => 'CLIENTES_CREAR',
    'N_producto' => 'PRODUCTOS_CREAR',
    'N_almacen' => 'ALMACENES_CREAR',
    'N_direccion_almacen' => 'ALMACENES_CREAR',
    'N_transportista' => 'TRANSPORTES_CREAR',
    'N_recoleccion' => 'RECOLECCION_CREAR',
    'N_captacion' => 'CAPTACION_CREAR',
    'N_venta' => 'VENTAS_CREAR',
    'N_compra' => 'PROVEEDORES_CREAR', // Compras usa mismo permiso que proveedores
    'N_usuario' => 'ADMIN',
    'N_direccion' => 'CLIENTES_EDITAR',
    'N_direccion_p' => 'PROVEEDORES_EDITAR',
    
    // === Módulos de Edición ===
    'E_proveedor' => 'PROVEEDORES_EDITAR',
    'E_Cliente' => 'CLIENTES_EDITAR',
    'E_producto' => 'PRODUCTOS_EDITAR',
    'E_almacen' => 'ALMACENES_EDITAR',
    'E_direccion_almacen' => 'ALMACENES_EDITAR',
    'E_transportista' => 'TRANSPORTES_EDITAR',
    'E_Transporte' => 'TRANSPORTES_EDITAR',
    'E_recoleccion' => 'RECOLECCION_EDITAR',
    'E_captacion' => 'CAPTACION_EDITAR',
    'E_venta' => 'VENTAS_EDITAR',
    'E_compra' => 'PROVEEDORES_EDITAR',
    'E_usuario' => 'ADMIN', // Nota: usuario editándose a sí mismo se maneja aparte
    'E_direccion' => 'CLIENTES_EDITAR',
    'E_direccion_p' => 'PROVEEDORES_EDITAR',
    
    // === Módulos Administrativos ===
    'usuarios' => 'ADMIN',
    'reportes_actividad' => 'ADMINISTRATIVO',
    'ia_test' => 'ADMINISTRATIVO',
    'importar_recolecciones' => 'UTILERIAS',
]);

/**
 * Tipos de usuario y sus niveles
 */
define('TIPOS_USUARIO', [
    100 => ['nombre' => 'Administrador', 'nivel' => 'admin', 'badge' => 'bg-danger'],
    50 => ['nombre' => 'Usuario A', 'nivel' => 'supervisor', 'badge' => 'bg-primary'],
    30 => ['nombre' => 'Usuario B', 'nivel' => 'operador', 'badge' => 'bg-info'],
    10 => ['nombre' => 'Usuario C', 'nivel' => 'basico', 'badge' => 'bg-secondary'],
]);

/**
 * Permisos por nivel de usuario (tipo)
 * Estos permisos se calculan automáticamente según el tipo de usuario
 */
define('PERMISOS_POR_NIVEL', [
    'admin' => [
        'VER_INACTIVOS' => true,
        'ACTIVAR_DESACTIVAR' => true,
        'ADMIN' => true,
        'REPORTES' => true,
        'UTILERIAS' => true,
    ],
    'supervisor' => [
        'VER_INACTIVOS' => false,
        'ACTIVAR_DESACTIVAR' => true,
        'ADMIN' => false,
        'REPORTES' => true,
        'UTILERIAS' => false,
    ],
    'operador' => [
        'VER_INACTIVOS' => false,
        'ACTIVAR_DESACTIVAR' => false,
        'ADMIN' => false,
        'REPORTES' => true,
        'UTILERIAS' => false,
    ],
    'basico' => [
        'VER_INACTIVOS' => false,
        'ACTIVAR_DESACTIVAR' => false,
        'ADMIN' => false,
        'REPORTES' => false,
        'UTILERIAS' => false,
    ],
]);

// ============================================================================
// CLASE PRINCIPAL DE PERMISOS
// ============================================================================

class PermissionManager {
    private static $instance = null;
    private $permisos = [];
    private $tipoUsuario = 10;
    private $datosUsuario = null;
    
    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {}
    
    /**
     * Obtener instancia única
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar permisos del usuario
     * @param array $datosUsuario Datos del usuario desde la DB
     */
    public function inicializar(array $datosUsuario): void {
        $this->datosUsuario = $datosUsuario;
        $this->tipoUsuario = intval($datosUsuario['tipo'] ?? 10);
        $this->cargarPermisos();
    }
    
    /**
     * Cargar permisos desde los datos del usuario
     */
    private function cargarPermisos(): void {
        // Cargar permisos de base de datos
        foreach (PERMISOS_CATALOGO as $nombre => $config) {
            $columna = $config['columna'];
            $this->permisos[$nombre] = ($this->datosUsuario[$columna] ?? 0) == 1;
        }
        
        // Cargar permisos por nivel de usuario
        $nivel = TIPOS_USUARIO[$this->tipoUsuario]['nivel'] ?? 'basico';
        $permisosPorNivel = PERMISOS_POR_NIVEL[$nivel] ?? PERMISOS_POR_NIVEL['basico'];
        
        foreach ($permisosPorNivel as $permiso => $valor) {
            $this->permisos[$permiso] = $valor;
        }
    }
    
    /**
     * Verificar si tiene un permiso específico
     * @param string $permiso Nombre del permiso
     * @return bool
     */
    public function tiene(string $permiso): bool {
        // Admin siempre tiene todos los permisos
        if ($this->tipoUsuario == 100) {
            return true;
        }
        return $this->permisos[$permiso] ?? false;
    }
    
    /**
     * Verificar múltiples permisos (OR)
     * @param array $permisos Lista de permisos
     * @return bool True si tiene al menos uno
     */
    public function tieneAlguno(array $permisos): bool {
        foreach ($permisos as $permiso) {
            if ($this->tiene($permiso)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verificar múltiples permisos (AND)
     * @param array $permisos Lista de permisos
     * @return bool True si tiene todos
     */
    public function tieneTodos(array $permisos): bool {
        foreach ($permisos as $permiso) {
            if (!$this->tiene($permiso)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Obtener tipo de usuario
     * @return int
     */
    public function getTipoUsuario(): int {
        return $this->tipoUsuario;
    }
    
    /**
     * Verificar si es administrador
     * @return bool
     */
    public function esAdmin(): bool {
        return $this->tipoUsuario == 100;
    }
    
    /**
     * Obtener todos los permisos actuales
     * @return array
     */
    public function getPermisos(): array {
        return $this->permisos;
    }
    
    /**
     * Obtener información del tipo de usuario
     * @return array
     */
    public function getInfoTipo(): array {
        return TIPOS_USUARIO[$this->tipoUsuario] ?? TIPOS_USUARIO[10];
    }
}

// ============================================================================
// FUNCIONES HELPER PARA USO EN PLANTILLAS
// ============================================================================

/**
 * Inicializar el sistema de permisos
 * @param array $datosUsuario Datos del usuario
 * @return PermissionManager
 */
function inicializarPermisos(array $datosUsuario): PermissionManager {
    $pm = PermissionManager::getInstance();
    $pm->inicializar($datosUsuario);
    return $pm;
}

/**
 * Verificar permiso - Forma corta
 * @param string $permiso Nombre del permiso
 * @return bool
 */
function tienePermiso(string $permiso): bool {
    return PermissionManager::getInstance()->tiene($permiso);
}

/**
 * Generar atributo hidden para elementos sin permiso
 * Usa clase CSS en lugar de style inline
 * @param string $permiso Nombre del permiso
 * @return string Atributo class o vacío
 */
function sinPermiso(string $permiso): string {
    return tienePermiso($permiso) ? '' : 'class="d-none"';
}

/**
 * Generar clase CSS para ocultar elementos sin permiso
 * Compatible hacia atrás - retorna 'style="display: none"' o ''
 * @param string $permiso Nombre del permiso
 * @return string style o vacío
 * @deprecated Usar sinPermiso() en su lugar
 */
function ocultarSinPermiso(string $permiso): string {
    return tienePermiso($permiso) ? '' : 'style="display: none"';
}

/**
 * Generar atributo disabled para elementos sin permiso
 * @param string $permiso Nombre del permiso
 * @return string 'disabled' o vacío
 */
function deshabilitadoSinPermiso(string $permiso): string {
    return tienePermiso($permiso) ? '' : 'disabled';
}

// ============================================================================
// FUNCIÓN DE COMPATIBILIDAD - Mapeo con sistema anterior
// ============================================================================

/**
 * Función de compatibilidad con el sistema anterior
 * Mantiene la misma interfaz pero usa el nuevo sistema internamente
 * 
 * @param int $tipo Tipo de usuario
 * @param array|string $datos Array con datos del usuario o string de permisos (legacy)
 * @return array Array asociativo con permisos (formato antiguo)
 */
function permisos($tipo, $datos = []): array {
    // Permisos base por tipo de usuario
    $esAdmin = ($tipo == 100);
    $esSupervisor = ($tipo == 50);
    $esOperador = ($tipo == 30);
    
    // Calcular permisos por nivel
    $Admin = $esAdmin ? '' : 'style="display: none"';
    $DesAct = ($esAdmin || $esSupervisor) ? '' : 'style="display: none"';
    $Inactivos = $esAdmin ? '' : 'style="display: none"';
    $Reportes = ($esAdmin || $esSupervisor || $esOperador) ? '' : 'style="display: none"';
    $utilerias = $esAdmin ? '' : 'style="display: none"';
    
    // Detectar si es array (nuevo) o string (legacy)
    if (is_array($datos)) {
        // Nuevo formato: array directo con datos del usuario
        $u = $datos;
        return [
            // Permisos de Creación
            'Prove_Crear' => ($esAdmin || ($u['a'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Clien_Crear' => ($esAdmin || ($u['b'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Produ_Crear' => ($esAdmin || ($u['c'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Almac_Crear' => ($esAdmin || ($u['d'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Trans_Crear' => ($esAdmin || ($u['e'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Recole_Crear' => ($esAdmin || ($u['f'] ?? '0') == '1') ? '' : 'style="display: none"',
            'captacion_crear' => ($esAdmin || ($u['g'] ?? '0') == '1') ? '' : 'style="display: none"',
            'ventas_crear' => ($esAdmin || ($u['h'] ?? '0') == '1') ? '' : 'style="display: none"',
            
            // Permisos de Edición
            'Prove_Editar' => ($esAdmin || ($u['a1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Clien_Editar' => ($esAdmin || ($u['b1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Produ_Editar' => ($esAdmin || ($u['c1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Almac_Editar' => ($esAdmin || ($u['d1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Trans_Editar' => ($esAdmin || ($u['e1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'Recole_Editar' => ($esAdmin || ($u['f1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'captacion_editar' => ($esAdmin || ($u['g1'] ?? '0') == '1') ? '' : 'style="display: none"',
            'ventas_editar' => ($esAdmin || ($u['h1'] ?? '0') == '1') ? '' : 'style="display: none"',
            
            // Permisos Especiales
            'ACT_FAC' => ($esAdmin || ($u['af'] ?? '0') == '1') ? '' : 'style="display: none"',
            'ACT_CR' => ($esAdmin || ($u['acr'] ?? '0') == '1') ? '' : 'style="display: none"',
            'ACT_AC' => ($esAdmin || ($u['acc'] ?? '0') == '1') ? '' : 'style="display: none"',
            'en_correo' => ($esAdmin || ($u['en_correo'] ?? '0') == '1') ? '' : 'style="display: none"',
            'sub_precios' => ($esAdmin || ($u['prec'] ?? '0') == '1') ? '' : 'style="display: none"',
            
            // Permisos por nivel de usuario
            'ACT_DES' => $DesAct,
            'ADMIN' => $Admin,
            'INACTIVO' => $Inactivos,
            'REPORTES' => $Reportes,
            'UTILERIAS' => $utilerias,
        ];
    }
    
    // Si no es array, retornar permisos vacíos (formato legacy ya no soportado)
    return [
        'Prove_Crear' => 'style="display: none"',
        'Clien_Crear' => 'style="display: none"',
        'Produ_Crear' => 'style="display: none"',
        'Almac_Crear' => 'style="display: none"',
        'Trans_Crear' => 'style="display: none"',
        'Recole_Crear' => 'style="display: none"',
        'captacion_crear' => 'style="display: none"',
        'ventas_crear' => 'style="display: none"',
        'Prove_Editar' => 'style="display: none"',
        'Clien_Editar' => 'style="display: none"',
        'Produ_Editar' => 'style="display: none"',
        'Almac_Editar' => 'style="display: none"',
        'Trans_Editar' => 'style="display: none"',
        'Recole_Editar' => 'style="display: none"',
        'captacion_editar' => 'style="display: none"',
        'ventas_editar' => 'style="display: none"',
        'ACT_FAC' => 'style="display: none"',
        'ACT_CR' => 'style="display: none"',
        'ACT_AC' => 'style="display: none"',
        'en_correo' => 'style="display: none"',
        'sub_precios' => 'style="display: none"',
        'ACT_DES' => $DesAct,
        'ADMIN' => $Admin,
        'INACTIVO' => $Inactivos,
        'REPORTES' => $Reportes,
        'UTILERIAS' => $utilerias,
    ];
}

// ============================================================================
// FUNCIONES PARA FORMULARIOS DE USUARIO
// ============================================================================

/**
 * Obtener catálogo de permisos para formularios
 * @return array
 */
function getPermisosFormulario(): array {
    $grupos = [];
    foreach (PERMISOS_CATALOGO as $nombre => $config) {
        $grupo = $config['grupo'];
        if (!isset($grupos[$grupo])) {
            $grupos[$grupo] = [];
        }
        $grupos[$grupo][$nombre] = $config;
    }
    return $grupos;
}

/**
 * Obtener tipos de usuario para select
 * @return array
 */
function getTiposUsuarioSelect(): array {
    $opciones = [];
    foreach (TIPOS_USUARIO as $valor => $info) {
        $opciones[$valor] = $info['nombre'];
    }
    return $opciones;
}

/**
 * Obtener badge class para tipo de usuario
 * @param int $tipo
 * @return string
 */
function getBadgeTipoUsuario(int $tipo): string {
    return TIPOS_USUARIO[$tipo]['badge'] ?? 'bg-secondary';
}

/**
 * Obtener nombre de tipo de usuario
 * @param int $tipo
 * @return string
 */
function getNombreTipoUsuario(int $tipo): string {
    return TIPOS_USUARIO[$tipo]['nombre'] ?? 'Desconocido';
}

/**
 * Preparar datos de permisos para guardar en BD
 * @param array $postData Datos del formulario POST
 * @return array Datos listos para INSERT/UPDATE
 */
function prepararPermisosParaGuardar(array $postData): array {
    $permisos = [];
    foreach (PERMISOS_CATALOGO as $nombre => $config) {
        $columna = $config['columna'];
        // El checkbox envía valor solo si está marcado
        $permisos[$columna] = isset($postData[$columna]) ? 1 : 0;
    }
    return $permisos;
}

/**
 * Verificar si usuario tiene permiso desde datos de BD
 * @param array $usuarioData Datos del usuario
 * @param string $columna Columna de la BD
 * @return bool
 */
function usuarioTienePermiso(array $usuarioData, string $columna): bool {
    return ($usuarioData[$columna] ?? 0) == 1;
}

// ============================================================================
// FUNCIONES DE CONTROL DE ACCESO A MÓDULOS
// ============================================================================

/**
 * Verificar si el usuario actual puede acceder a un módulo
 * @param string $modulo Nombre del módulo (ej: 'N_usuario', 'E_proveedor')
 * @param int $tipoUsuario Tipo de usuario actual
 * @param array $datosUsuario Datos del usuario desde la BD
 * @return bool
 */
function puedeAccederModulo(string $modulo, int $tipoUsuario, array $datosUsuario): bool {
    // Admin siempre puede acceder
    if ($tipoUsuario == 100) {
        return true;
    }
    
    // Caso especial: E_usuario permite editar el propio perfil
    if ($modulo === 'E_usuario' && isset($_GET['id']) && isset($datosUsuario['id_user'])) {
        if ($_GET['id'] == $datosUsuario['id_user']) {
            return true; // Usuario editándose a sí mismo
        }
    }
    
    // Verificar si el módulo requiere permiso
    if (!isset(MODULOS_PERMISOS[$modulo])) {
        return true; // Módulo sin restricción
    }
    
    $permisoRequerido = MODULOS_PERMISOS[$modulo];
    
    // Verificar permisos por nivel (ADMIN, UTILERIAS, etc.)
    if (isset(PERMISOS_POR_NIVEL['admin'][$permisoRequerido])) {
        $nivel = TIPOS_USUARIO[$tipoUsuario]['nivel'] ?? 'basico';
        return PERMISOS_POR_NIVEL[$nivel][$permisoRequerido] ?? false;
    }
    
    // Verificar permisos de catálogo (columnas de BD)
    if (isset(PERMISOS_CATALOGO[$permisoRequerido])) {
        $columna = PERMISOS_CATALOGO[$permisoRequerido]['columna'];
        return ($datosUsuario[$columna] ?? 0) == 1;
    }
    
    return false;
}

/**
 * Requerir permiso para continuar - redirige si no tiene acceso
 * Usar al inicio de módulos protegidos
 * @param string $permiso Nombre del permiso requerido
 * @param string $redireccion URL a redirigir si no tiene permiso (default: inicio)
 * @return void
 */
function requirePermiso(string $permiso, string $redireccion = 'inicio'): void {
    global $TipoUserSession, $Pruev1;
    
    // Admin siempre tiene acceso
    if (isset($TipoUserSession) && $TipoUserSession == 100) {
        return;
    }
    
    $tieneAcceso = false;
    
    // Verificar permisos por nivel
    if (isset($TipoUserSession)) {
        $nivel = TIPOS_USUARIO[$TipoUserSession]['nivel'] ?? 'basico';
        if (isset(PERMISOS_POR_NIVEL[$nivel][$permiso])) {
            $tieneAcceso = PERMISOS_POR_NIVEL[$nivel][$permiso];
        }
    }
    
    // Verificar permisos de catálogo
    if (!$tieneAcceso && isset(PERMISOS_CATALOGO[$permiso]) && isset($Pruev1)) {
        $columna = PERMISOS_CATALOGO[$permiso]['columna'];
        $tieneAcceso = ($Pruev1[$columna] ?? 0) == 1;
    }
    
    if (!$tieneAcceso) {
        // Usar función alert si existe, sino redirigir directamente
        if (function_exists('alert')) {
            alert("No tienes permiso para acceder a esta sección", 0, $redireccion);
        } else {
            header("Location: ?p=" . $redireccion);
        }
        exit;
    }
}

/**
 * Verificar permiso de acceso al módulo actual automáticamente
 * Llama a requirePermiso si el módulo está en MODULOS_PERMISOS
 * @param string $modulo Nombre del módulo
 * @return void
 */
function verificarAccesoModulo(string $modulo): void {
    if (isset(MODULOS_PERMISOS[$modulo])) {
        $permisoRequerido = MODULOS_PERMISOS[$modulo];
        
        // Caso especial: E_usuario puede ser el propio usuario editándose
        if ($modulo === 'E_usuario' && isset($_GET['id']) && isset($_SESSION['id_cliente'])) {
            if ($_GET['id'] == $_SESSION['id_cliente']) {
                return; // Usuario editándose a sí mismo, permitido
            }
        }
        
        requirePermiso($permisoRequerido);
    }
}

/**
 * Obtener el permiso requerido para un módulo
 * @param string $modulo Nombre del módulo
 * @return string|null Nombre del permiso o null si no requiere
 */
function getPermisoModulo(string $modulo): ?string {
    return MODULOS_PERMISOS[$modulo] ?? null;
}

/**
 * Helper para renderizar solo si tiene permiso
 * Uso: <?= renderSiPermiso('PERMISO', '<button>...</button>') ?>
 * @param string $permiso Nombre del permiso
 * @param string $html HTML a renderizar si tiene permiso
 * @return string HTML o cadena vacía
 */
function renderSiPermiso(string $permiso, string $html): string {
    global $TipoUserSession, $Pruev1;
    
    // Admin siempre ve todo
    if (isset($TipoUserSession) && $TipoUserSession == 100) {
        return $html;
    }
    
    // Verificar permiso por nivel
    if (isset($TipoUserSession)) {
        $nivel = TIPOS_USUARIO[$TipoUserSession]['nivel'] ?? 'basico';
        if (isset(PERMISOS_POR_NIVEL[$nivel][$permiso]) && PERMISOS_POR_NIVEL[$nivel][$permiso]) {
            return $html;
        }
    }
    
    // Verificar permiso de catálogo
    if (isset(PERMISOS_CATALOGO[$permiso]) && isset($Pruev1)) {
        $columna = PERMISOS_CATALOGO[$permiso]['columna'];
        if (($Pruev1[$columna] ?? 0) == 1) {
            return $html;
        }
    }
    
    return '';
}
