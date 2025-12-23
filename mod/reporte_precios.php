<?php
// reporte_precios.php

// Obtener parámetros del reporte
$tipo_precio = clear($_GET['tipo'] ?? 'c'); // 'c' para compra, 'v' para venta
$id_producto = clear($_GET['id_producto'] ?? '');
$zona_seleccionada = $_SESSION['selected_zone'] ?? '0';

// Obtener mes y año específicos del filtro
$mes_seleccionado = clear($_GET['mes'] ?? date('m'));
$anio_seleccionado = clear($_GET['anio'] ?? date('Y'));

// Función para detectar el rango de fechas con precios
function detectarRangoPrecios($conn, $id_producto, $tipo, $zona_seleccionada) {
    $sql = "SELECT 
                MIN(pr.fecha_ini) as primera_fecha,
                MAX(pr.fecha_fin) as ultima_fecha
            FROM precios pr
            INNER JOIN productos p ON pr.id_prod = p.id_prod
            WHERE pr.tipo = ? 
            AND pr.status = '1'";
    
    if (!empty($id_producto)) {
        $sql .= " AND p.id_prod = ?";
    }
    
    // Agregar filtro de zona
    if ($zona_seleccionada != '0') {
        $sql .= " AND p.zona = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($id_producto) && $zona_seleccionada != '0') {
        $stmt->bind_param('sii', $tipo, $id_producto, $zona_seleccionada);
    } elseif (!empty($id_producto)) {
        $stmt->bind_param('si', $tipo, $id_producto);
    } elseif ($zona_seleccionada != '0') {
        $stmt->bind_param('si', $tipo, $zona_seleccionada);
    } else {
        $stmt->bind_param('s', $tipo);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rango = $result->fetch_assoc();
    
    return $rango;
}

// Función para verificar si un periodo es el actual
function esPeriodoActual($periodo_nombre) {
    $periodo_actual = date('F Y');
    return $periodo_nombre === $periodo_actual;
}

// Función para generar periodos específicos basados en mes y año seleccionados
function generarPeriodosEspecificos($conn, $id_producto, $tipo, $zona_seleccionada, $mes_seleccionado, $anio_seleccionado) {
    $periodos = [];
    
    // Crear fecha base del periodo seleccionado
    $fecha_base = new DateTime("$anio_seleccionado-$mes_seleccionado-15");
    
    // Generar periodos: actual, 3 anteriores y 2 siguientes
    for ($i = -3; $i <= 2; $i++) {
        $fecha_periodo = clone $fecha_base;
        $fecha_periodo->modify("$i months");
        
        // Periodo: 16 del mes anterior al 15 del mes actual
        $periodo_fin = new DateTime($fecha_periodo->format('Y-m-15'));
        $periodo_ini = new DateTime($fecha_periodo->format('Y-m-16'));
        $periodo_ini->modify('-1 month');
        
        $periodo_nombre = $periodo_ini->format('F Y');
        $es_actual = esPeriodoActual($periodo_nombre);
        $es_seleccionado = ($periodo_ini->format('Y-m') === "$anio_seleccionado-$mes_seleccionado");
        
        // Verificar si hay precios en este periodo
        $sql = "SELECT COUNT(*) as total 
                FROM precios pr
                INNER JOIN productos p ON pr.id_prod = p.id_prod
                WHERE pr.tipo = ? 
                AND pr.status = '1'
                AND (
                    (pr.fecha_ini <= ? AND pr.fecha_fin >= ?) OR
                    (pr.fecha_ini BETWEEN ? AND ?) OR
                    (pr.fecha_fin BETWEEN ? AND ?)
                )";
        
        // Agregar filtro de zona
        if ($zona_seleccionada != '0') {
            $sql .= " AND p.zona = ?";
        }
        
        if (!empty($id_producto)) {
            $sql .= " AND p.id_prod = ?";
        }
        
        $params = [
            $tipo,                                   // 1 string
            $periodo_fin->format('Y-m-d'),           // 2 string  
            $periodo_ini->format('Y-m-d'),           // 3 string
            $periodo_ini->format('Y-m-d'),           // 4 string
            $periodo_fin->format('Y-m-d'),           // 5 string
            $periodo_ini->format('Y-m-d'),           // 6 string
            $periodo_fin->format('Y-m-d')            // 7 string
        ];
        
        $types = 'sssssss'; // 7 strings
        
        // Agregar zona si es necesario
        if ($zona_seleccionada != '0') {
            $params[] = $zona_seleccionada;
            $types .= 'i';
        }
        
        // Agregar producto si es necesario
        if (!empty($id_producto)) {
            $params[] = $id_producto;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        // Solo agregar periodos que tengan precios
        if ($count > 0) {
            $periodos[$periodo_nombre] = [
                'inicio' => $periodo_ini->format('Y-m-d'),
                'fin' => $periodo_fin->format('Y-m-d'),
                'nombre' => $periodo_nombre,
                'total_precios' => $count,
                'es_actual' => $es_actual,
                'es_seleccionado' => $es_seleccionado,
                'mes' => $periodo_ini->format('m'),
                'anio' => $periodo_ini->format('Y')
            ];
        }
    }
    
    // Ordenar del más reciente al más antiguo
    krsort($periodos);
    
    return $periodos;
}

// Función principal para obtener precios por periodo
function obtenerPreciosPorPeriodo($conn, $id_producto, $tipo, $periodos, $zona_seleccionada) {
    $precios_por_periodo = [];
    
    foreach ($periodos as $periodo_nombre => $periodo) {
        $precios_por_periodo[$periodo_nombre] = [
            'periodo' => $periodo,
            'precios' => []
        ];
        
        // Consulta para obtener precios que estuvieron activos durante este periodo
        $sql = "SELECT p.*, 
                       pr.precio, 
                       pr.fecha_ini, 
                       pr.fecha_fin,
                       pr.destino,
                       d.noma as nombre_cliente,
                       d.cod_al as codigo_cliente,
                       z.nom as nombre_zona
                FROM productos p
                INNER JOIN precios pr ON p.id_prod = pr.id_prod
                LEFT JOIN direcciones d ON pr.destino = d.id_direc
                LEFT JOIN zonas z ON p.zona = z.id_zone
                WHERE pr.tipo = ?
                AND pr.status = '1'
                AND (
                    (pr.fecha_ini <= ? AND pr.fecha_fin >= ?) OR
                    (pr.fecha_ini BETWEEN ? AND ?) OR
                    (pr.fecha_fin BETWEEN ? AND ?)
                )";
        
        // Agregar filtro de zona
        if ($zona_seleccionada != '0') {
            $sql .= " AND p.zona = ?";
        }
        
        if (!empty($id_producto)) {
            $sql .= " AND p.id_prod = ?";
        }
        
        $sql .= " ORDER BY p.cod, pr.fecha_ini";
        
        $params = [
            $tipo,                      // 1 string
            $periodo['fin'],            // 2 string  
            $periodo['inicio'],         // 3 string
            $periodo['inicio'],         // 4 string
            $periodo['fin'],            // 5 string
            $periodo['inicio'],         // 6 string
            $periodo['fin']             // 7 string
        ];
        
        $types = 'sssssss'; // 7 strings
        
        // Agregar zona si es necesario
        if ($zona_seleccionada != '0') {
            $params[] = $zona_seleccionada;
            $types .= 'i';
        }
        
        // Agregar producto si es necesario
        if (!empty($id_producto)) {
            $params[] = $id_producto;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $precios_por_periodo[$periodo_nombre]['precios'][] = $row;
        }
    }
    
    return $precios_por_periodo;
}

// Obtener lista de productos para el filtro (con filtro de zona)
$productos_query_sql = "
    SELECT p.id_prod, p.cod, p.nom_pro, z.nom as nombre_zona
    FROM productos p 
    LEFT JOIN zonas z ON p.zona = z.id_zone 
    WHERE p.status = '1'";
    
if ($zona_seleccionada != '0') {
    $productos_query_sql .= " AND p.zona = '$zona_seleccionada'";
}

$productos_query_sql .= " ORDER BY p.cod";
$productos_query = $conn_mysql->query($productos_query_sql);

// Obtener lista de zonas para el filtro (si el usuario tiene acceso a todas)
$zonas_query = $conn_mysql->query("
    SELECT id_zone, nom 
    FROM zonas 
    WHERE status = '1' 
    ORDER BY nom
");

// Obtener rango de años disponibles para el filtro
$anios_query = $conn_mysql->query("
    SELECT DISTINCT YEAR(fecha_ini) as anio 
    FROM precios 
    WHERE status = '1' 
    ORDER BY anio DESC
");

// Obtener periodos específicos basados en la selección
$periodos = generarPeriodosEspecificos($conn_mysql, $id_producto, $tipo_precio, $zona_seleccionada, $mes_seleccionado, $anio_seleccionado);

// Obtener precios por periodo
$precios_por_periodo = obtenerPreciosPorPeriodo($conn_mysql, $id_producto, $tipo_precio, $periodos, $zona_seleccionada);

// Determinar periodo actual y seleccionado
$periodo_actual_nombre = date('F Y');
$periodo_seleccionado_nombre = DateTime::createFromFormat('!m', $mes_seleccionado)->format('F') . ' ' . $anio_seleccionado;

// Función para exportar a CSV
function exportarCSV($precios_por_periodo, $tipo_precio, $zona_seleccionada) {
    // Configurar headers para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_precios_' . date('Y-m-d') . '.csv"');
    
    // Crear output stream
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para Excel)
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Encabezados CSV
    $encabezados = ['Periodo', 'Fecha Inicio Periodo', 'Fecha Fin Periodo', 'Código Producto', 'Nombre Producto'];
    
    if ($zona_seleccionada == '0') {
        $encabezados[] = 'Zona';
    }
    
    $encabezados = array_merge($encabezados, [
        'Precio',
        'Fecha Inicio Vigencia',
        'Fecha Fin Vigencia',
        'Tipo Cliente',
        'Código Cliente',
        'Nombre Cliente'
    ]);
    
    fputcsv($output, $encabezados, ';');
    
    // Datos
    if (!empty($precios_por_periodo)) {
        foreach ($precios_por_periodo as $periodo_nombre => $data) {
            if (!empty($data['precios'])) {
                foreach ($data['precios'] as $precio) {
                    $fila = [
                        $periodo_nombre,
                        date('d/m/Y', strtotime($data['periodo']['inicio'])),
                        date('d/m/Y', strtotime($data['periodo']['fin'])),
                        $precio['cod'],
                        $precio['nom_pro']
                    ];
                    
                    if ($zona_seleccionada == '0') {
                        $fila[] = $precio['nombre_zona'] ?? 'Sin zona';
                    }
                    
                    $fila = array_merge($fila, [
                        number_format($precio['precio'], 2),
                        date('d/m/Y', strtotime($precio['fecha_ini'])),
                        date('d/m/Y', strtotime($precio['fecha_fin'])),
                        ($tipo_precio == 'v' && $precio['destino'] != '0') ? 'Específico' : 'General',
                        ($tipo_precio == 'v' && $precio['destino'] != '0') ? $precio['codigo_cliente'] : '',
                        ($tipo_precio == 'v' && $precio['destino'] != '0') ? $precio['nombre_cliente'] : ''
                    ]);
                    
                    fputcsv($output, $fila, ';');
                }
            }
        }
    }
    
    fclose($output);
    exit;
}

// Procesar exportación CSV si se solicita
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    exportarCSV($precios_por_periodo, $tipo_precio, $zona_seleccionada);
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-graph-up me-2"></i>Reporte Histórico de Precios
            </h5>
            <div class="d-flex gap-2">
                <a href="?p=productos" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtros del Reporte -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Tipo de Precio</label>
                    <select class="form-select" id="tipoPrecio" onchange="actualizarFiltros()">
                        <option value="c" <?= $tipo_precio == 'c' ? 'selected' : '' ?>>Compra</option>
                        <option value="v" <?= $tipo_precio == 'v' ? 'selected' : '' ?>>Venta</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Producto</label>
                    <select class="form-select" id="idProducto" onchange="actualizarFiltros()">
                        <option value="">Todos los productos</option>
                        <?php while ($prod = $productos_query->fetch_assoc()): ?>
                            <option value="<?= $prod['id_prod'] ?>" <?= $id_producto == $prod['id_prod'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prod['cod']) ?> - <?= htmlspecialchars($prod['nom_pro']) ?>
                                <?php if($zona_seleccionada == '0'): ?> (<?= htmlspecialchars($prod['nombre_zona']) ?>)<?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <?php if($zona_seleccionada == '0'): ?>
                <div class="col-md-2">
                    <label class="form-label">Zona</label>
                    <select class="form-select" id="zonaFiltro" onchange="actualizarFiltros()">
                        <option value="">Todas las zonas</option>
                        <?php while ($zona = $zonas_query->fetch_assoc()): ?>
                            <option value="<?= $zona['id_zone'] ?>" <?= isset($_GET['zona']) && $_GET['zona'] == $zona['id_zone'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($zona['nom']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-md-2">
                    <label class="form-label">Zona Actual</label>
                    <div class="form-control bg-light">
                        <small class="text-muted">
                            <?php 
                            $zona_nombre = $conn_mysql->query("SELECT nom FROM zonas WHERE id_zone = '$zona_seleccionada'")->fetch_assoc();
                            echo htmlspecialchars($zona_nombre['nom'] ?? 'Zona actual');
                            ?>
                        </small>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <select class="form-select" id="mesFiltro" onchange="actualizarFiltros()">
                        <?php 
                        $meses = [
                            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                        ];
                        foreach ($meses as $numero => $nombre): ?>
                            <option value="<?= $numero ?>" <?= $mes_seleccionado == $numero ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Año</label>
                    <select class="form-select" id="anioFiltro" onchange="actualizarFiltros()">
                        <?php 
                        $anio_actual = date('Y');
                        for ($i = $anio_actual - 2; $i <= $anio_actual + 1; $i++): ?>
                            <option value="<?= $i ?>" <?= $anio_seleccionado == $i ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" onclick="actualizarFiltros()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                </div>
            </div>

            <!-- Navegación Rápida de Periodos -->
            <?php if (!empty($periodos)): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar-range me-2"></i>Navegación Rápida de Periodos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 text-center">
                        <?php 
                        $contador = 0;
                        foreach ($periodos as $periodo_nombre => $data): 
                            $clase_badge = '';
                            if ($data['es_actual']) {
                                $clase_badge = 'bg-success';
                            } elseif ($data['es_seleccionado']) {
                                $clase_badge = 'bg-primary';
                            } else {
                                $clase_badge = 'bg-secondary';
                            }
                            ?>
                            <div class="col">
                                <a href="?p=reporte_precios&tipo=<?= $tipo_precio ?>&mes=<?= $data['mes'] ?>&anio=<?= $data['anio'] ?><?= !empty($id_producto) ? '&id_producto=' . $id_producto : '' ?><?= isset($_GET['zona']) ? '&zona=' . $_GET['zona'] : '' ?>" 
                                   class="text-decoration-none">
                                    <div class="card h-100 <?= $data['es_seleccionado'] ? 'border-primary' : '' ?>">
                                        <div class="card-body p-2">
                                            <span class="badge <?= $clase_badge ?> mb-1">
                                                <?php if ($data['es_actual']): ?>
                                                    <i class="bi bi-star-fill me-1"></i>
                                                <?php elseif ($data['es_seleccionado']): ?>
                                                    <i class="bi bi-check-circle me-1"></i>
                                                <?php endif; ?>
                                                <?= $data['total_precios'] ?> precios
                                            </span>
                                            <h6 class="card-title mb-1 <?= $data['es_seleccionado'] ? 'text-primary fw-bold' : '' ?>">
                                                <?= $periodo_nombre ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?= date('d/m', strtotime($data['inicio'])) ?>-<?= date('d/m', strtotime($data['fin'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php 
                            $contador++;
                            if ($contador % 6 == 0) echo '</div><div class="row g-2 text-center mt-2">';
                        endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información del Rango -->
            <?php if (!empty($periodos)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Mostrando <strong><?= count($periodos) ?> periodos</strong> alrededor de 
                <strong><?= $periodo_seleccionado_nombre ?></strong>.
                <?php if($zona_seleccionada != '0'): ?>
                    <br>Filtrado por zona: <strong><?= htmlspecialchars($zona_nombre['nom'] ?? '') ?></strong>
                <?php endif; ?>
                <div class="mt-2">
                    <span class="badge bg-success"><i class="bi bi-star-fill me-1"></i> Periodo Actual</span>
                    <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> Periodo Seleccionado</span>
                    <span class="badge bg-secondary"><i class="bi bi-calendar me-1"></i> Otros Periodos</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reporte de Precios -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="tablaReporte">
                    <thead class="table-dark">
                        <tr>
                            <th>Periodo</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <?php if($zona_seleccionada == '0'): ?>
                            <th>Zona</th>
                            <?php endif; ?>
                            <th>Precio</th>
                            <th>Vigencia Real</th>
                            <th>Cliente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_precios_general = 0;
                        $total_precios_actual = 0;
                        $total_precios_seleccionado = 0;
                        
                        if (!empty($precios_por_periodo)): 
                            foreach ($precios_por_periodo as $periodo_nombre => $data): 
                                if (!empty($data['precios'])): 
                                    foreach ($data['precios'] as $precio): 
                                        $total_precios_general++;
                                        $es_periodo_actual = $data['periodo']['es_actual'];
                                        $es_periodo_seleccionado = $data['periodo']['es_seleccionado'];
                                        
                                        if ($es_periodo_actual) $total_precios_actual++;
                                        if ($es_periodo_seleccionado) $total_precios_seleccionado++;
                                        
                                        // Determinar clase de la fila
                                        $clase_fila = '';
                                        if ($es_periodo_actual) {
                                            $clase_fila = 'table-success';
                                        } elseif ($es_periodo_seleccionado) {
                                            $clase_fila = 'table-primary';
                                        }
                                        ?>
                                        <tr class="<?= $clase_fila ?>">
                                            <td class="fw-semibold">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($es_periodo_actual): ?>
                                                        <span class="badge bg-success me-2" title="Periodo Actual">
                                                            <i class="bi bi-star-fill"></i>
                                                        </span>
                                                    <?php elseif ($es_periodo_seleccionado): ?>
                                                        <span class="badge bg-primary me-2" title="Periodo Seleccionado">
                                                            <i class="bi bi-check-circle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <div>
                                                        <?= $periodo_nombre ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            (<?= date('d/m', strtotime($data['periodo']['inicio'])) ?> - <?= date('d/m', strtotime($data['periodo']['fin'])) ?>)
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($precio['cod']) ?></td>
                                            <td><?= htmlspecialchars($precio['nom_pro']) ?></td>
                                            <?php if($zona_seleccionada == '0'): ?>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($precio['nombre_zona'] ?? 'Sin zona') ?></span>
                                            </td>
                                            <?php endif; ?>
                                            <td class="text-end fw-bold <?= $es_periodo_seleccionado ? 'text-white bg-primary rounded' : ($es_periodo_actual ? 'text-white bg-success rounded' : 'text-success') ?>">
                                                $<?= number_format($precio['precio'], 2) ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= date('d/m/Y', strtotime($precio['fecha_ini'])) ?> - 
                                                    <?= date('d/m/Y', strtotime($precio['fecha_fin'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($tipo_precio == 'v' && $precio['destino'] != '0'): ?>
                                                    <span class="badge bg-info">
                                                        <?= $precio['codigo_cliente'] ?> - <?= $precio['nombre_cliente'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">General</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; 
                                endif; 
                            endforeach; 
                        else: ?>
                            <tr>
                                <td colspan="<?= $zona_seleccionada == '0' ? '7' : '6' ?>" class="text-center text-muted py-3">
                                    <i class="bi bi-inbox me-2"></i>No se encontraron precios para los filtros seleccionados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen Estadístico -->
            <?php if (!empty($precios_por_periodo)): ?>
            <div class="row mt-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Periodos</h6>
                            <h3><?= count($precios_por_periodo) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Total Precios</h6>
                            <h3><?= $total_precios_general ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Precios Seleccionado</h6>
                            <h3><?= $total_precios_seleccionado ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Periodo Seleccionado</h6>
                            <h5><?= $periodo_seleccionado_nombre ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h6 class="card-title">Rango Mostrado</h6>
                            <h5><?= count($periodos) ?> meses</h5>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function actualizarFiltros() {
    const tipo = document.getElementById('tipoPrecio').value;
    const producto = document.getElementById('idProducto').value;
    const zona = document.getElementById('zonaFiltro') ? document.getElementById('zonaFiltro').value : '';
    const mes = document.getElementById('mesFiltro').value;
    const anio = document.getElementById('anioFiltro').value;
    
    const params = new URLSearchParams({
        p: 'reporte_precios',
        tipo: tipo,
        mes: mes,
        anio: anio
    });
    
    if (producto) {
        params.append('id_producto', producto);
    }
    
    if (zona) {
        params.append('zona', zona);
    }
    
    window.location.href = '?' + params.toString();
}

function exportarExcel() {
    // Crear una tabla temporal para la exportación
    const tabla = document.getElementById('tablaReporte').cloneNode(true);
    
    // Eliminar elementos HTML innecesarios
    const badges = tabla.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.parentNode.replaceChild(document.createTextNode(badge.textContent), badge);
    });
    
    // Crear contenido HTML para exportar
    const html = `
        <html>
            <head>
                <meta charset="utf-8">
                <title>Reporte de Precios Históricos</title>
            </head>
            <body>
                <h2>Reporte de Precios Históricos</h2>
                <p>Generado: ${new Date().toLocaleString()}</p>
                ${tabla.outerHTML}
            </body>
        </html>
    `;
    
    // Descargar como archivo
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reporte_precios_${new Date().toISOString().split('T')[0]}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Inicializar DataTable
$(document).ready(function() {
    $('#tablaReporte').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
        },
        "pageLength": 25,
        "order": [[0, 'desc']],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });
});
</script>

<style>
.current-period-highlight {
    border-left: 4px solid #198754 !important;
}

.table-success {
    background-color: rgba(25, 135, 84, 0.05) !important;
}

.table-primary {
    background-color: rgba(13, 110, 253, 0.05) !important;
}

.table-success td,
.table-primary td {
    border-color: inherit !important;
}
</style>