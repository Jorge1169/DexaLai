<?php
// Paginación
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registros_por_pagina = 20;
$offset = ($pagina - 1) * $registros_por_pagina;

// Filtros
$filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$filtro_zona = $_GET['zona'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';

// Construir consulta con filtros
$where = "c.status = 1";
$params = [];

if ($filtro_fecha_inicio && $filtro_fecha_fin) {
    $where .= " AND c.fecha_captacion BETWEEN ? AND ?";
    $params[] = $filtro_fecha_inicio;
    $params[] = $filtro_fecha_fin;
}

if ($filtro_zona) {
    $where .= " AND c.zona = ?";
    $params[] = $filtro_zona;
}

if ($filtro_proveedor) {
    $where .= " AND c.id_prov = ?";
    $params[] = $filtro_proveedor;
}

// Obtener total de registros
$sql_total = "SELECT COUNT(*) as total FROM captacion c WHERE $where";
$stmt_total = $conn_mysql->prepare($sql_total);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener captaciones
$sql_captaciones = "SELECT 
c.id_captacion,
c.folio,
c.fecha_captacion,
z.PLANTA as zona,
p.rs as proveedor,
a.nombre as almacen,
COUNT(cd.id_detalle) as productos,
SUM(cd.total_kilos) as total_kilos,
c.created_at
FROM captacion c
LEFT JOIN zonas z ON c.zona = z.id_zone
LEFT JOIN proveedores p ON c.id_prov = p.id_prov
LEFT JOIN almacenes a ON c.id_alma = a.id_alma
LEFT JOIN captacion_detalle cd ON c.id_captacion = cd.id_captacion AND cd.status = 1
WHERE $where
GROUP BY c.id_captacion
ORDER BY c.fecha_captacion DESC, c.id_captacion DESC
LIMIT ? OFFSET ?";

$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt_captaciones = $conn_mysql->prepare($sql_captaciones);
$types = str_repeat('s', count($params) - 2) . 'ii';
$stmt_captaciones->bind_param($types, ...$params);
$stmt_captaciones->execute();
$captaciones = $stmt_captaciones->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener zonas para filtro
$zonas = $conn_mysql->query("SELECT id_zone, PLANTA FROM zonas WHERE status = 1 ORDER BY PLANTA");

// Obtener proveedores para filtro
$proveedores = $conn_mysql->query("SELECT id_prov, rs FROM proveedores WHERE status = 1 ORDER BY rs");
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Captaciones Registradas
            </h5>
            <a class="btn btn-sm btn-light" href="?p=N_captacion" target="_blank">
                <i class="bi bi-plus-circle me-1"></i> Nueva Captación
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $filtro_fecha_inicio ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $filtro_fecha_fin ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Zona</label>
                        <select name="zona" class="form-select">
                            <option value="">Todas las zonas</option>
                            <?php while ($zona = $zonas->fetch_assoc()): ?>
                                <option value="<?= $zona['id_zone'] ?>" <?= $filtro_zona == $zona['id_zone'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($zona['PLANTA']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor" class="form-select">
                            <option value="">Todos los proveedores</option>
                            <?php while ($prov = $proveedores->fetch_assoc()): ?>
                                <option value="<?= $prov['id_prov'] ?>" <?= $filtro_proveedor == $prov['id_prov'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['rs']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <button type="button" onclick="window.location.href='captacion'" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Captaciones -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Zona</th>
                            <th>Proveedor</th>
                            <th>Almacén</th>
                            <th class="text-center">Productos</th>
                            <th class="text-end">Total Kilos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($captaciones)): ?>
                            <?php foreach ($captaciones as $captacion): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($captacion['folio']) ?></strong><br>
                                        <small class="text-muted">ID: <?= $captacion['id_captacion'] ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($captacion['fecha_captacion'])) ?></td>
                                    <td><?= htmlspecialchars($captacion['zona']) ?></td>
                                    <td><?= htmlspecialchars($captacion['proveedor']) ?></td>
                                    <td><?= htmlspecialchars($captacion['almacen']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $captacion['productos'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?= number_format($captacion['total_kilos'], 2) ?> kg</strong>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-info" href="?p=V_captacion&id=<?= $captacion['id_captacion'] ?>" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                            <a href="E_captacion?id=<?= $captacion['id_captacion'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>No se encontraron captaciones registradas.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>