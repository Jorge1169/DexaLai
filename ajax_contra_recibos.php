<?php
// ajax_contra_recibos.php
session_start();
require_once 'config/conexiones.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para log de errores
function logError($message) {
    //error_log("CONTRARECIBOS ERROR: " . $message);
    // También puedes enviarlo a la interfaz si quieres
    echo "<!-- ERROR: $message -->";
}

$accion = $_POST['accion'] ?? '';

switch($accion) {
    case 'cargarProveedores':
        cargarProveedores();
        break;
    case 'cargarFleteros':
        cargarFleteros();
        break;
    case 'cargarZonas':
        cargarZonas();
        break;
    case 'cargarContraRecibos':
        cargarContraRecibos();
        break;
    case 'cargarDetallesContra': // NUEVA ACCIÓN
        cargarDetallesContra();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
    case 'validarContraRecibo':
        $tipo = $_POST['tipo'] ?? '';
        $alias = $_POST['alias'] ?? '';
        $folio = $_POST['folio'] ?? '';
        $entidadId = $_POST['entidadId'] ?? '';
        
        if (empty($tipo) || empty($alias) || empty($folio) || empty($entidadId)) {
            echo json_encode(['valido' => false, 'mensaje' => 'Datos incompletos para validación']);
        } else {
            $resultado = validarContraRecibo($tipo, $alias, $folio, $entidadId);
            echo json_encode($resultado);
        }
        break;
}
function cargarProveedores() {
    global $conn_mysql;

    // Obtener zona desde sesión
    $zona_seleccionada = $_SESSION['selected_zone'] ?? '0';

    $query = "SELECT id_prov, cod, rs FROM proveedores WHERE status = 1";

    // Si no es zona 0, filtramos
    if ($zona_seleccionada != '0') {
        $query .= " AND zona = " . intval($zona_seleccionada);
    }

    $query .= " ORDER BY rs";

    $result = $conn_mysql->query($query);

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['id_prov']}'>{$row['cod']} - {$row['rs']}</option>";
    }
}

function cargarFleteros() {
    global $conn_mysql;

    // Obtener zona desde sesión
    $zona_seleccionada = $_SESSION['selected_zone'] ?? '0';

    $query = "SELECT id_transp, placas, razon_so FROM transportes WHERE status = 1";

    // Si no es zona 0, filtramos
    if ($zona_seleccionada != '0') {
        $query .= " AND zona = " . intval($zona_seleccionada);
    }

    $query .= " ORDER BY razon_so";

    $result = $conn_mysql->query($query);

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['id_transp']}'>{$row['placas']} - {$row['razon_so']}</option>";
    }
}
function cargarContraRecibos() {
    global $conn_mysql;
    
    $tipo = $_POST['tipo'] ?? 'todos';
    $proveedor = $_POST['proveedor'] ?? '';
    $fletero = $_POST['fletero'] ?? '';
    $fechaDesde = $_POST['fechaDesde'] ?? '';
    $fechaHasta = $_POST['fechaHasta'] ?? '';
    $buscarFolio = $_POST['buscarFolio'] ?? '';
    $filtroAlias = $_POST['filtroAlias'] ?? '';
    
    $zona_seleccionada = $_SESSION['selected_zone'] ?? '0';
    
    $contraCompras = [];
    $contraFletes = []; 
    
    // ========== FILTROS COMUNES ==========
    $filtrosComunes = [];
    
    if ($zona_seleccionada != '0') {
        $filtrosComunes[] = "r.zona = " . intval($zona_seleccionada);
    }
    
    // ========== CONSULTA PARA COMPRAS ==========
    if ($tipo == 'todos' || $tipo == 'compras') {
        $whereCompras = ["r.folio_inv_pro IS NOT NULL", "r.folio_inv_pro != ''", "r.folio_inv_pro != '0'", "r.status = '1'"];
        
        if ($proveedor) {
            $whereCompras[] = "r.id_prov = " . intval($proveedor);
        }
        
        // FILTRO DE SEGURIDAD: Agrupar por proveedor y folio
        if (!empty($filtroAlias)) {
            $aliasLimpio = $conn_mysql->real_escape_string($filtroAlias);
            $whereCompras[] = "r.alias_inv_pro = '$aliasLimpio'";
        }
        
        if (!empty($buscarFolio)) {
            $folioLimpio = $conn_mysql->real_escape_string(trim($buscarFolio));
            $whereCompras[] = "r.folio_inv_pro = '$folioLimpio'";
        }
        
        $whereCompras = array_merge($whereCompras, $filtrosComunes);
        
        // CONSULTA MEJORADA CON AGRUPACIÓN POR PROVEEDOR Y FOLIO
        $queryCompras = "SELECT 
            r.alias_inv_pro as alias,
            r.folio_inv_pro as folio,
            p.id_prov,
            p.cod as cod_proveedor,
            p.rs as razon_social,
            COUNT(DISTINCT r.id_recol) as total_recolecciones,
            SUM(pc.precio * r.peso_prov) as monto_total,
            -- Verificar si hay duplicados para el mismo proveedor
            (SELECT COUNT(DISTINCT r2.id_prov) 
             FROM recoleccion r2 
             WHERE r2.alias_inv_pro = r.alias_inv_pro 
             AND r2.folio_inv_pro = r.folio_inv_pro
             AND r2.folio_inv_pro IS NOT NULL
             AND r2.folio_inv_pro != ''
            ) as proveedores_duplicados
        FROM recoleccion r
        INNER JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
        WHERE " . implode(" AND ", $whereCompras);
        
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $queryCompras = "SELECT 
                r.alias_inv_pro as alias,
                r.folio_inv_pro as folio,
                p.id_prov,
                p.cod as cod_proveedor,
                p.rs as razon_social,
                COUNT(DISTINCT r.id_recol) as total_recolecciones,
                SUM(pc.precio * r.peso_prov) as monto_total,
                (SELECT COUNT(DISTINCT r2.id_prov) 
                 FROM recoleccion r2 
                 WHERE r2.alias_inv_pro = r.alias_inv_pro 
                 AND r2.folio_inv_pro = r.folio_inv_pro
                 AND r2.folio_inv_pro IS NOT NULL
                 AND r2.folio_inv_pro != ''
                ) as proveedores_duplicados
            FROM recoleccion r
            INNER JOIN proveedores p ON r.id_prov = p.id_prov
            LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
            LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
            WHERE EXISTS (
                SELECT 1 FROM recoleccion r2 
                WHERE r2.alias_inv_pro = r.alias_inv_pro 
                AND r2.folio_inv_pro = r.folio_inv_pro
                AND r2.id_prov = r.id_prov
                AND r2.fecha_r BETWEEN '$fechaDesde' AND '$fechaHasta'
            ) AND " . implode(" AND ", $whereCompras);
        }
        
        $queryCompras .= " GROUP BY r.alias_inv_pro, r.folio_inv_pro, p.id_prov
        HAVING proveedores_duplicados = 1 -- SOLO MOSTRAR DONDE NO HAY DUPLICADOS
        ORDER BY r.alias_inv_pro, r.folio_inv_pro";
        
        //error_log("Consulta Compras: " . $queryCompras);
        
        $resultCompras = $conn_mysql->query($queryCompras);
        
        if ($resultCompras) {
            while($row = mysqli_fetch_assoc($resultCompras)) {
                $contraCompras[] = [
                    'tipo' => 'compras',
                    'alias' => $row['alias'],
                    'folio' => $row['folio'],
                    'entidad' => [
                        'id' => $row['id_prov'],
                        'codigo' => $row['cod_proveedor'],
                        'nombre' => $row['razon_social']
                    ],
                    'total_recolecciones' => $row['total_recolecciones'],
                    'monto_total' => $row['monto_total'],
                    'proveedores_duplicados' => $row['proveedores_duplicados']
                ];
            }
        }
    }
    
    // ========== CONSULTA PARA FLETES ==========
    if ($tipo == 'todos' || $tipo == 'fletes') {
        $whereFletes = ["r.folio_inv_fle IS NOT NULL", "r.folio_inv_fle != ''", "r.folio_inv_fle != '0'","r.status = '1'"];
        
        if ($fletero) {
            $whereFletes[] = "r.id_transp = " . intval($fletero);
        }
        
        // FILTRO DE SEGURIDAD: Agrupar por transportista y folio
        if (!empty($filtroAlias)) {
            $aliasLimpio = $conn_mysql->real_escape_string($filtroAlias);
            $whereFletes[] = "r.alias_inv_fle = '$aliasLimpio'";
        }
        
        if (!empty($buscarFolio)) {
            $folioLimpio = $conn_mysql->real_escape_string(trim($buscarFolio));
            $whereFletes[] = "r.folio_inv_fle = '$folioLimpio'";
        }
        
        $whereFletes = array_merge($whereFletes, $filtrosComunes);
        
        // CONSULTA MEJORADA CON AGRUPACIÓN POR TRANSPORTISTA Y FOLIO
        $queryFletes = "SELECT 
            r.alias_inv_fle as alias,
            r.folio_inv_fle as folio,
            t.id_transp,
            t.placas,
            t.razon_so as razon_social,
            COUNT(DISTINCT r.id_recol) as total_recolecciones,
            SUM(
                CASE 
                    WHEN r.sub_tot_inv IS NOT NULL AND r.sub_tot_inv > 0 THEN r.sub_tot_inv
                    ELSE (
                        pf.precio * 
                        CASE 
                            WHEN pf.tipo = 'FT' THEN 
                                CASE 
                                    WHEN pf.conmin > 0 AND (r.peso_fle/1000) <= pf.conmin THEN pf.conmin
                                    ELSE r.peso_fle/1000
                                END
                            ELSE 1
                        END
                    )
                END
            ) as monto_total,
            -- Verificar si hay duplicados para el mismo transportista
            (SELECT COUNT(DISTINCT r2.id_transp) 
             FROM recoleccion r2 
             WHERE r2.alias_inv_fle = r.alias_inv_fle 
             AND r2.folio_inv_fle = r.folio_inv_fle
             AND r2.folio_inv_fle IS NOT NULL
             AND r2.folio_inv_fle != ''
            ) as transportistas_duplicados
        FROM recoleccion r
        INNER JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
        WHERE " . implode(" AND ", $whereFletes);
        
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $queryFletes = "SELECT 
                r.alias_inv_fle as alias,
                r.folio_inv_fle as folio,
                t.id_transp,
                t.placas,
                t.razon_so as razon_social,
                COUNT(DISTINCT r.id_recol) as total_recolecciones,
                SUM(
                    CASE 
                        WHEN r.sub_tot_inv IS NOT NULL AND r.sub_tot_inv > 0 THEN r.sub_tot_inv
                        ELSE (
                            pf.precio * 
                            CASE 
                                WHEN pf.tipo = 'FT' THEN 
                                    CASE 
                                        WHEN pf.conmin > 0 AND (r.peso_fle/1000) <= pf.conmin THEN pf.conmin
                                        ELSE r.peso_fle/1000
                                    END
                                ELSE 1
                            END
                        )
                    END
                ) as monto_total,
                (SELECT COUNT(DISTINCT r2.id_transp) 
                 FROM recoleccion r2 
                 WHERE r2.alias_inv_fle = r.alias_inv_fle 
                 AND r2.folio_inv_fle = r.folio_inv_fle
                 AND r2.folio_inv_fle IS NOT NULL
                 AND r2.folio_inv_fle != ''
                ) as transportistas_duplicados
            FROM recoleccion r
            INNER JOIN transportes t ON r.id_transp = t.id_transp
            LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
            WHERE EXISTS (
                SELECT 1 FROM recoleccion r2 
                WHERE r2.alias_inv_fle = r.alias_inv_fle 
                AND r2.folio_inv_fle = r.folio_inv_fle
                AND r2.id_transp = r.id_transp
                AND r2.fecha_r BETWEEN '$fechaDesde' AND '$fechaHasta'
            ) AND " . implode(" AND ", $whereFletes);
        }
        
        $queryFletes .= " GROUP BY r.alias_inv_fle, r.folio_inv_fle, t.id_transp
        HAVING transportistas_duplicados = 1 -- SOLO MOSTRAR DONDE NO HAY DUPLICADOS
        ORDER BY r.alias_inv_fle, r.folio_inv_fle";
        
        //error_log("Consulta Fletes: " . $queryFletes);
        
        $resultFletes = $conn_mysql->query($queryFletes);
        
        if ($resultFletes) {
            while($row = mysqli_fetch_assoc($resultFletes)) {
                $contraFletes[] = [
                    'tipo' => 'fletes',
                    'alias' => $row['alias'],
                    'folio' => $row['folio'],
                    'entidad' => [
                        'id' => $row['id_transp'],
                        'codigo' => $row['placas'],
                        'nombre' => $row['razon_social']
                    ],
                    'total_recolecciones' => $row['total_recolecciones'],
                    'monto_total' => $row['monto_total'],
                    'transportistas_duplicados' => $row['transportistas_duplicados']
                ];
            }
        }
    }
    
    // Combinar y mostrar resultados
    mostrarResultados(array_merge($contraCompras, $contraFletes));
}
function validarContraRecibo($tipo, $alias, $folio, $entidadId) {
    global $conn_mysql;
    
    if ($tipo == 'compras') {
        $campoAlias = 'alias_inv_pro';
        $campoFolio = 'folio_inv_pro';
        $campoEntidad = 'id_prov';
        $tablaEntidad = 'proveedores';
    } else {
        $campoAlias = 'alias_inv_fle';
        $campoFolio = 'folio_inv_fle';
        $campoEntidad = 'id_transp';
        $tablaEntidad = 'transportes';
    }
    
    // Verificar si ya existe el mismo folio para otra entidad
    $query = "SELECT COUNT(DISTINCT r.$campoEntidad) as entidades_duplicadas
              FROM recoleccion r
              WHERE r.$campoAlias = ?
              AND r.$campoFolio = ?
              AND r.$campoFolio IS NOT NULL 
              AND r.$campoFolio != ''";
    
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("ss", $alias, $folio);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['entidades_duplicadas'] > 1) {
        return [
            'valido' => false,
            'mensaje' => "ERROR: El folio $folio-$alias ya está siendo usado por múltiples " . 
                        ($tipo == 'compras' ? 'proveedores' : 'transportistas') . 
                        ". Cada folio debe ser único por entidad."
        ];
    }
    
    // Verificar si ya existe para otra entidad específica
    $query = "SELECT r.$campoEntidad, 
                     CASE 
                         WHEN $tipo = 'compras' THEN p.rs 
                         ELSE t.razon_so 
                     END as nombre_entidad
              FROM recoleccion r
              " . ($tipo == 'compras' ? "LEFT JOIN proveedores p ON r.id_prov = p.id_prov" : "LEFT JOIN transportes t ON r.id_transp = t.id_transp") . "
              WHERE r.$campoAlias = ?
              AND r.$campoFolio = ?
              AND r.$campoFolio IS NOT NULL 
              AND r.$campoFolio != ''
              AND r.$campoEntidad != ?";
    
    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("ssi", $alias, $folio, $entidadId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $entidades = [];
        while ($row = $result->fetch_assoc()) {
            $entidades[] = $row['nombre_entidad'];
        }
        
        return [
            'valido' => false,
            'mensaje' => "ERROR: El folio $folio-$alias ya está siendo usado por: " . 
                        implode(", ", $entidades) . 
                        ". Cada folio debe ser único por " . ($tipo == 'compras' ? 'proveedor' : 'transportista')
        ];
    }
    
    return ['valido' => true, 'mensaje' => 'OK'];
}

function mostrarResultados($contraRecibos) {
     $filtrosAplicados = [];
    
    if (!empty($_POST['fechaDesde']) && !empty($_POST['fechaHasta'])) {
        $filtrosAplicados[] = "Fechas: " . date('d/m/Y', strtotime($_POST['fechaDesde'])) . " a " . date('d/m/Y', strtotime($_POST['fechaHasta']));
    }
    
    if (!empty($_POST['buscarFolio'])) {
        $filtrosAplicados[] = "Folio: " . htmlspecialchars($_POST['buscarFolio']);
    }
    
    if (!empty($_POST['filtroAlias'])) {
        $filtrosAplicados[] = "Alias: " . htmlspecialchars($_POST['filtroAlias']);
    }
    
    if (!empty($_POST['proveedor'])) {
        $filtrosAplicados[] = "Proveedor filtrado";
    }
    
    if (!empty($_POST['fletero'])) {
        $filtrosAplicados[] = "Fletero filtrado";
    }
    
    if (empty($contraRecibos)) {
        $mensaje = '<div class="alert alert-info text-center">No se encontraron contra recibos con los filtros aplicados.</div>';
        
        if (!empty($filtrosAplicados)) {
            $mensaje .= '<div class="alert alert-warning text-center small">Filtros aplicados: ' . implode(', ', $filtrosAplicados) . '</div>';
        }
        
        echo $mensaje;
        
        echo '<script>
            $("#totalContras").text("0");
            $("#totalCompras").text("0");
            $("#totalFletes").text("0");
        </script>';
        return;
    }

    if (empty($contraRecibos)) {
        echo '<div class="alert alert-info text-center">No se encontraron contra recibos con los filtros aplicados.</div>';
        
        echo '<script>
            $("#totalContras").text("0");
            $("#totalCompras").text("0");
            $("#totalFletes").text("0");
        </script>';
        return;
    }
    
    $totalCompras = 0;
    $totalFletes = 0;
    
    foreach ($contraRecibos as $contra) {
        if ($contra['tipo'] == 'compras') {
            $totalCompras++;
        } else {
            $totalFletes++;
        }
    }
    
    $totalContras = $totalCompras + $totalFletes;
    
    $agrupados = [];
    foreach ($contraRecibos as $contra) {
        $clave = $contra['alias'] . '-' . $contra['folio'];
        if (!isset($agrupados[$clave])) {
            $agrupados[$clave] = [
                'alias' => $contra['alias'],
                'folio' => $contra['folio'],
                'tipo' => 'mixto',
                'entidades' => []
            ];
        }
        $agrupados[$clave]['entidades'][] = $contra;
        
        if (count($agrupados[$clave]['entidades']) > 1) {
            $tipos = array_unique(array_column($agrupados[$clave]['entidades'], 'tipo'));
            if (count($tipos) == 1) {
                $agrupados[$clave]['tipo'] = $tipos[0];
            }
        } else {
            $agrupados[$clave]['tipo'] = $contra['tipo'];
        }
    }
    
    echo '<div class="accordion" id="accordionContraRecibos">';
    
    $index = 0;
    foreach ($agrupados as $clave => $grupo) {
        $index++;
        $accordionId = 'accordion' . $index;
        $collapseId = 'collapse' . $index;
        
        $totalRecolecciones = 0;
        $totalMonto = 0;
        foreach ($grupo['entidades'] as $entidad) {
            $totalRecolecciones += $entidad['total_recolecciones'];
            $totalMonto += $entidad['monto_total'];
        }
        foreach ($grupo['entidades'] as $entidad) {
        // ... código existente para mostrar la entidad ...
        
        // Mostrar advertencia si hay duplicados
        if (isset($entidad['proveedores_duplicados']) && $entidad['proveedores_duplicados'] > 1) {
            echo '<div class="alert alert-warning alert-sm mt-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Advertencia:</strong> Este folio está siendo usado por múltiples proveedores.
                  </div>';
        }
        
        if (isset($entidad['transportistas_duplicados']) && $entidad['transportistas_duplicados'] > 1) {
            echo '<div class="alert alert-warning alert-sm mt-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Advertencia:</strong> Este folio está siendo usado por múltiples transportistas.
                  </div>';
        }
        
        // ... resto del código para mostrar detalles ...
    }
        $tipoClase = $grupo['tipo'] == 'compras' ? 'compras' : 
                    ($grupo['tipo'] == 'fletes' ? 'fletes' : 'mixto');        
        echo '
        <div class="accordion-item" data-tipo="' . $tipoClase . '">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" data-alias="' . $grupo['alias'] . '" data-folio="' . $grupo['folio'] . '">
                    <div class="d-none d-md-flex justify-content-between align-items-center w-100 me-3 accordion-header-desktop">
                        <div>
                            <span class="badge bg-primary me-2">' . $grupo['alias'] . '-' . $grupo['folio'] . '</span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-orange badge-counter me-2">' . $totalRecolecciones . ' recolecciones</span>
                            <span class="badge bg-success badge-counter">$' . number_format($totalMonto, 2) . '</span>
                        </div>
                    </div>
                    
                    <div class="d-md-none w-100 accordion-header-mobile">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-primary">' . $grupo['alias'] . '-' . $grupo['folio'] . '</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary badge-counter">' . $totalRecolecciones . ' rec.</span>
                            <span class="badge bg-success badge-counter">$' . number_format($totalMonto, 2) . '</span>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="' . $collapseId . '" class="accordion-collapse collapse" data-bs-parent="#accordionContraRecibos">
                <div class="accordion-body p-0">';
        
        // Mostrar detalles automáticamente para cada entidad del grupo
        foreach ($grupo['entidades'] as $entidad) {
            $link = "http://localhost/DexaLai/doc/contra_recibos.php?id=";
            $tipoBadge = $entidad['tipo'] == 'compras' ? 
                '<span class="badge bg-success">Compra</span>' : 
                '<span class="badge bg-indigo">Flete</span>';
            
            echo '<div class="entidad-detalle mb-4">';
            echo '<div class="entidad-header bg-body-tertiary p-3 rounded-top">';
            echo '<div class="d-flex justify-content-between align-items-center">';
            echo '<div>';
            echo '<h6 class="mb-1">' . $tipoBadge . ' - ' . $entidad['entidad']['codigo'] . '</h6>';
            echo '<small class="text-muted">' . $entidad['entidad']['nombre'] . '</small>';
            echo '</div>';
            echo '<div class="text-end">';
            echo '<span class="badge bg-orange me-2">' . $entidad['total_recolecciones'] . ' recolecciones</span>';
            echo '<span class="badge bg-success me-2">$' . number_format($entidad['monto_total'], 2) . '</span>';
            echo '<a href="'.$link.$entidad['alias'].'-'.$entidad['folio'].'" target="_blank"><button class="btn btn-sm btn-teal" title="Abrir el documento del Contra Recibo"><i class="bi bi-file-earmark-medical"></i></button></a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Área para los detalles (se cargará automáticamente)
            echo '<div id="detalles-' . $index . '-' . $entidad['entidad']['id'] . '" class="detalles-contenido p-3 border border-top-0 rounded-bottom">';
            echo '<div class="text-center py-3">';
            echo '<div class="spinner-border text-primary" role="status">';
            echo '<span class="visually-hidden">Cargando detalles...</span>';
            echo '</div>';
            echo '<p class="mt-2 small">Cargando detalles de ' . $entidad['entidad']['codigo'] . '...</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>'; // cierre entidad-detalle
        }
        
        echo '</div></div></div>';
    }
    
    echo '</div>';
    
    $fechaDesde = $_POST['fechaDesde'] ?? '';
    $fechaHasta = $_POST['fechaHasta'] ?? '';
    
     if (!empty($filtrosAplicados)) {
        echo '<div class="alert alert-light mt-3 small">';
        echo '<i class="bi bi-funnel me-1"></i>';
        echo '<strong>Filtros aplicados:</strong> ' . implode(', ', $filtrosAplicados);
        
        // Información adicional sobre el comportamiento de fechas
        if (!empty($_POST['fechaDesde']) && !empty($_POST['fechaHasta'])) {
            echo '<br><i class="bi bi-info-circle me-1"></i>';
            echo '<small>Se muestran contra recibos que tengan al menos una recolección en el rango de fechas especificado.</small>';
        }
        echo '</div>';
    }
    echo '<script>
        $("#totalContras").text("' . $totalContras . '");
        $("#totalCompras").text("' . $totalCompras . '");
        $("#totalFletes").text("' . $totalFletes . '");
        
        // Cargar detalles automáticamente cuando se expanda un acordeón
        $(".accordion-button").on("click", function() {
            const collapseId = $(this).data("bs-target");
            const isExpanding = !$(this).hasClass("collapsed");
            
            if (isExpanding) {
                // Esperar a que la animación del collapse termine
                setTimeout(() => {
                    const alias = $(this).data("alias");
                    const folio = $(this).data("folio");
                    cargarDetallesAutomáticos(collapseId, alias, folio);
                }, 300);
            }
        });
    </script>';
}
function cargarDetallesContra() {
    global $conn_mysql;
    $invoiceLK = "http://globaltycloud.com.mx:81/invoice/";
    
    $tipo = $_POST['tipo'];
    $alias = $_POST['alias'];
    $folio = $_POST['folio'];
    $entidadId = $_POST['entidadId'];
    
    if ($tipo == 'compras') {
        $campoAlias = 'alias_inv_pro';
        $campoFolio = 'folio_inv_pro';
        $joinEntidad = 'r.id_prov = ' . intval($entidadId);
        
        $query = "SELECT 
            r.*,
            r.factura_pro AS factura_proveedor,
            r.doc_pro as documento_proveedor,
            CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
            p.rs as proveedor,
            t.razon_so as fletero,
            t.placas as cod_fletero,
            pr.nom_pro as producto,
            pc.precio as precio_compra,
            pv.precio as precio_venta
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
        LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
        LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
        LEFT JOIN zonas z ON r.zona = z.id_zone
        WHERE r.{$campoAlias} = '{$alias}' 
        AND r.{$campoFolio} = '{$folio}'
        AND {$joinEntidad}
        ORDER BY r.fecha_r DESC";
        
    } else {
        $campoAlias = 'alias_inv_fle';
        $campoFolio = 'folio_inv_fle';
        $joinEntidad = 'r.id_transp = ' . intval($entidadId);
        
        $query = "SELECT 
            r.*,
            r.factura_fle as factura_flete,
            r.doc_fle as documento_flete,
            CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
            p.rs as proveedor,
            p.cod as cod_proveedor,
            t.razon_so as fletero,
            t.placas as cod_fletero,
            pr.nom_pro as producto,
            r.sub_tot_inv,
            r.im_tras_inv,
            r.im_rete_inv,
            r.total_inv
        FROM recoleccion r
        LEFT JOIN proveedores p ON r.id_prov = p.id_prov
        LEFT JOIN transportes t ON r.id_transp = t.id_transp
        LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
        LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
        LEFT JOIN zonas z ON r.zona = z.id_zone
        WHERE r.{$campoAlias} = '{$alias}' 
        AND r.{$campoFolio} = '{$folio}'
        AND {$joinEntidad}
        ORDER BY r.fecha_r DESC";
    }
    
    $result = $conn_mysql->query($query);
    
    if ($result->num_rows > 0) {
        if ($tipo == 'compras') {
            echo '<div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Fletero</th>
                                <th>Producto</th>
                                <th>Peso Prov (kg)</th>
                                <th>Precio Compra</th>
                                <th>Total Compra</th>
                                <th>Factura</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            $totalMonto = 0;
            while($row = mysqli_fetch_assoc($result)) {
                $totalCompra = $row['peso_prov'] * $row['precio_compra'];
                $totalMonto += $totalCompra;
                
                $docButton = !empty($row['documento_proveedor']) ? 
                    '<a href="'.$invoiceLK.$row['documento_proveedor'].'.pdf" target="_blank" class="btn btn-sm btn-outline-success" title="Ver PDF">
                         <i class="bi bi-file-earmark-pdf-fill"></i>
                     </a>' : '';
                
                echo '<tr>
                        <td><a href="?p=V_recoleccion&id=' . $row['id_recol'] . '" target="_blank" class="text-decoration-none">' . $row['folio_completo'] . '</a></td>
                        <td>' . date('d/m/Y', strtotime($row['fecha_r'])) . '</td>
                        <td>' . $row['proveedor'] . '</td>
                        <td>' . $row['fletero'] . '<br><small>(' . $row['cod_fletero'] . ')</small></td>
                        <td>' . $row['producto'] . '</td>
                        <td>' . number_format($row['peso_prov'], 2) . '</td>
                        <td>$' . number_format($row['precio_compra'], 2) . '</td>
                        <td><strong>$' . number_format($totalCompra, 2) . '</strong></td>
                        <td>'.$row['factura_proveedor'].'</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?p=V_recoleccion&id=' . $row['id_recol'] . '" target="_blank" class="btn btn-outline-primary" title="Ver recolección">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                ' . $docButton . '
                            </div>
                        </td>
                      </tr>';
            }
            
            echo '</tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="8" class="text-end"><strong>Total:</strong></td>
                            <td colspan="2"><strong>$' . number_format($totalMonto, 2) . '</strong></td>
                        </tr>
                    </tfoot>
                  </table>
                </div>';
                
        } else {
            echo '<div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-indigo">
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Fletero</th>
                                <th>Producto</th>
                                <th>Peso Flete (kg)</th>
                                <th>Flete</th>
                                <th>Imp. Traslados</th>
                                <th>Imp. Retenidos</th>
                                <th>Total</th>
                                <th>Factura</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            $totalFlete = 0;
            $totalTraslados = 0;
            $totalRetenidos = 0;
            $totalGeneral = 0;
            
            while($row = mysqli_fetch_assoc($result)) {
                $fleteInvoice = $row['sub_tot_inv'] ?? 0;
                $impTraslados = $row['im_tras_inv'] ?? 0;
                $impRetenidos = $row['im_rete_inv'] ?? 0;
                $total = $row['total_inv'] ?? 0;
                
                $totalFlete += $fleteInvoice;
                $totalTraslados += $impTraslados;
                $totalRetenidos += $impRetenidos;
                $totalGeneral += $total;
                
                $docButton = !empty($row['documento_flete']) ? 
                    '<a href="'.$invoiceLK.$row['documento_flete'].'.pdf" target="_blank" class="btn btn-sm btn-outline-success" title="Ver PDF">
                         <i class="bi bi-file-earmark-pdf-fill"></i>
                     </a>' : '';
                
                echo '<tr>
                        <td><a href="?p=V_recoleccion&id=' . $row['id_recol'] . '" target="_blank" class="text-decoration-none">' . $row['folio_completo'] . '</a></td>
                        <td>' . date('d/m/Y', strtotime($row['fecha_r'])) . '</td>
                        <td>' . $row['cod_proveedor'] . '<br><small>' . $row['proveedor'] . '</small></td>
                        <td>' . $row['cod_fletero'] . '<br><small>' . $row['fletero'] . '</small></td>
                        <td>' . $row['producto'] . '</td>
                        <td>' . number_format($row['peso_fle'], 2) . '</td>
                        <td>$' . number_format($fleteInvoice, 2) . '</td>
                        <td>$' . number_format($impTraslados, 2) . '</td>
                        <td class="text-danger">-$' . number_format($impRetenidos, 2) . '</td>
                        <td><strong>$' . number_format($total, 2) . '</strong></td>
                        <td>'.$row['factura_flete'].'</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?p=V_recoleccion&id=' . $row['id_recol'] . '" target="_blank" class="btn btn-outline-primary" title="Ver recolección">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                ' . $docButton . '
                            </div>
                        </td>
                      </tr>';
            }
            
            echo '</tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="6" class="text-end"><strong>Totales:</strong></td>
                            <td><strong>$' . number_format($totalFlete, 2) . '</strong></td>
                            <td><strong>$' . number_format($totalTraslados, 2) . '</strong></td>
                            <td class="text-danger"><strong>-$' . number_format($totalRetenidos, 2) . '</strong></td>
                            <td><strong>$' . number_format($totalGeneral, 2) . '</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                  </table>
                </div>';
        }
        
    } else {
        echo '<div class="alert alert-warning">No se encontraron recolecciones para este contra recibo.</div>';
    }
}
// Nueva función para cargar zonas
function cargarZonas() {
    global $conn_mysql;
    
    $query = "SELECT id_zone, cod, nom FROM zonas WHERE status = 1 ORDER BY nom";
    $result = $conn_mysql->query($query);
    
    while($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['id_zone']}'>{$row['cod']} - {$row['nom']}</option>";
    }
}
?>