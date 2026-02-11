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
        if ($this->tipoUsuario === 100) {
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
        return $this->tipoUsuario === 100;
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
 * @param string $permi String de permisos separados por coma
 * @return array Array asociativo con permisos (formato antiguo)
 */
function permisos($tipo, $permi): array {
    $permiso = explode(",", $permi);
    
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
    
    // Mapeo de permisos de base de datos
    return [
        // Permisos de Creación
        'Prove_Crear' => ($esAdmin || ($permiso[0] ?? '0') == '1') ? '' : 'style="display: none"',
        'Clien_Crear' => ($esAdmin || ($permiso[1] ?? '0') == '1') ? '' : 'style="display: none"',
        'Produ_Crear' => ($esAdmin || ($permiso[2] ?? '0') == '1') ? '' : 'style="display: none"',
        'Almac_Crear' => ($esAdmin || ($permiso[3] ?? '0') == '1') ? '' : 'style="display: none"',
        'Trans_Crear' => ($esAdmin || ($permiso[4] ?? '0') == '1') ? '' : 'style="display: none"',
        'Recole_Crear' => ($esAdmin || ($permiso[13] ?? '0') == '1') ? '' : 'style="display: none"',
        'captacion_crear' => ($esAdmin || ($permiso[17] ?? '0') == '1') ? '' : 'style="display: none"',
        'ventas_crear' => ($esAdmin || ($permiso[19] ?? '0') == '1') ? '' : 'style="display: none"',
        
        // Permisos de Edición
        'Prove_Editar' => ($esAdmin || ($permiso[5] ?? '0') == '1') ? '' : 'style="display: none"',
        'Clien_Editar' => ($esAdmin || ($permiso[6] ?? '0') == '1') ? '' : 'style="display: none"',
        'Produ_Editar' => ($esAdmin || ($permiso[7] ?? '0') == '1') ? '' : 'style="display: none"',
        'Almac_Editar' => ($esAdmin || ($permiso[8] ?? '0') == '1') ? '' : 'style="display: none"',
        'Trans_Editar' => ($esAdmin || ($permiso[9] ?? '0') == '1') ? '' : 'style="display: none"',
        'Recole_Editar' => ($esAdmin || ($permiso[14] ?? '0') == '1') ? '' : 'style="display: none"',
        'captacion_editar' => ($esAdmin || ($permiso[18] ?? '0') == '1') ? '' : 'style="display: none"',
        'ventas_editar' => ($esAdmin || ($permiso[20] ?? '0') == '1') ? '' : 'style="display: none"',
        
        // Permisos Especiales
        'ACT_FAC' => ($esAdmin || ($permiso[10] ?? '0') == '1') ? '' : 'style="display: none"',
        'ACT_CR' => ($esAdmin || ($permiso[11] ?? '0') == '1') ? '' : 'style="display: none"',
        'ACT_AC' => ($esAdmin || ($permiso[12] ?? '0') == '1') ? '' : 'style="display: none"',
        'en_correo' => ($esAdmin || ($permiso[15] ?? '0') == '1') ? '' : 'style="display: none"',
        'sub_precios' => ($esAdmin || ($permiso[16] ?? '0') == '1') ? '' : 'style="display: none"',
        
        // Permisos por nivel de usuario
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
