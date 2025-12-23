<?php
require_once 'config/conexiones.php';

$dt_mvI = $_POST['dt_mvI'];// fecha de inicio Y-m-d
$dt_mvF = $_POST['dt_mvF'];// fecha final
$zona_seleccionada = $_POST['zona'] ?? '0';

// FUNCIÓN PARA CALCULAR PRECIO FLETE REAL (COPIADA DE AJAX)
function calcularPrecioFleteReal($precio_base, $tipo_flete, $peso_minimo, $peso_flete_kg) {
    if (empty($peso_flete_kg) || $peso_flete_kg <= 0) {
        return floatval($precio_base);
    }
    
    $peso_flete_ton = floatval($peso_flete_kg) / 1000;
    $precio_base = floatval($precio_base);
    $peso_minimo = floatval($peso_minimo);
    
    if ($tipo_flete == 'FV') {
        return $precio_base;
    } elseif ($tipo_flete == 'FT') {
        if ($peso_minimo > 0) {
            return $peso_flete_ton <= $peso_minimo ? $precio_base * $peso_minimo : $precio_base * $peso_flete_ton;
        } else {
            return $precio_base * $peso_flete_ton;
        }
    }
    
    return $precio_base;
}

// CONSULTA BASADA EN LA LÓGICA DE AJAX_REPORTE_RECOLECCIONES
$recolecciones0 = $conn_mysql->query("
    SELECT 
        r.*,
        pc.precio AS precio_compra,
        pv.precio AS precio_venta,
        pf.precio AS precio_flete_base,
        pf.tipo AS tipo_flete,
        pf.conmin AS peso_minimo,
        pr.nom_pro AS nombre_producto
    FROM recoleccion r
    LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
    LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
    LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
    LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
    LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
    WHERE r.status = '1' 
    AND r.fecha_r BETWEEN '$dt_mvI' AND '$dt_mvF'
    AND r.remision IS NOT NULL AND r.remision != '' 
    AND r.factura_fle IS NOT NULL AND r.factura_fle != ''
    " . ($zona_seleccionada != '0' ? " AND r.zona = '$zona_seleccionada'" : ""));
// INICIALIZAR VARIABLES (COMO EN AJAX)
$utilidad_total = 0;
$total_ventas = 0;
$total_compras = 0;
$total_fletes = 0;
$total_productos = 0;
$total_recolecciones = 0;
// VERIFICAR SI HAY DATOS
if ($recolecciones0 && $recolecciones0->num_rows > 0) {
    // PROCESAR CADA RECOLECCIÓN
    while ($row = $recolecciones0->fetch_assoc()) {
        $total_recolecciones++;

        // Cálculos básicos
        $precio_flete_real = calcularPrecioFleteReal(
            $row['precio_flete_base'] ?? 0, 
            $row['tipo_flete'] ?? '', 
            $row['peso_minimo'] ?? 0, 
            $row['peso_fle'] ?? 0
        );

        $peso_prov = floatval($row['peso_prov'] ?? 0);
        $precio_compra = floatval($row['precio_compra'] ?? 0);
        $peso_fle = floatval($row['peso_fle'] ?? 0);
        $precio_venta = floatval($row['precio_venta'] ?? 0);
        $total_compra = $peso_prov * $precio_compra;
        
        // VERIFICAR SI EL FLETE ES GRATIS (precio = 0)
        $flete_gratis = (floatval($row['precio_flete_base'] ?? 0) == 0 && $precio_flete_real == 0);
        
        // CÁLCULO DE UTILIDAD
        $utilidad_estimada = 0;

        if ($flete_gratis) {
            $total_venta = $peso_prov * $precio_venta;
            $utilidad_estimada = $total_venta - $total_compra;
        } else {
            $total_venta = $peso_fle * $precio_venta;
            if ($peso_prov > 0 && $precio_compra > 0 && $peso_fle > 0 && $precio_venta > 0) {
                $utilidad_estimada = $total_venta - $total_compra - $precio_flete_real;
            }
        }

        // Acumular totales
        $utilidad_total += $utilidad_estimada;
        $total_ventas += $total_venta;
        $total_compras += $total_compra;

        if (!$flete_gratis) {
            $total_fletes += $precio_flete_real;
        }

        $total_productos += $peso_prov;
    }
} else {
    echo "<!-- No se encontraron recolecciones completas con los criterios actuales -->";
}

// PROCESAR CADA RECOLECCIÓN (COMO EN AJAX)
while ($row = $recolecciones0->fetch_assoc()) {
    $total_recolecciones++;

    // Cálculos básicos (MISMA LÓGICA QUE AJAX)
    $precio_flete_real = calcularPrecioFleteReal(
        $row['precio_flete_base'] ?? 0, 
        $row['tipo_flete'] ?? '', 
        $row['peso_minimo'] ?? 0, 
        $row['peso_fle'] ?? 0
    );

    $peso_prov = floatval($row['peso_prov'] ?? 0);
    $precio_compra = floatval($row['precio_compra'] ?? 0);
    $peso_fle = floatval($row['peso_fle'] ?? 0);
    $precio_venta = floatval($row['precio_venta'] ?? 0);
    $total_compra = $peso_prov * $precio_compra;
    
    // VERIFICAR SI EL FLETE ES GRATIS (precio = 0)
    $flete_gratis = (floatval($row['precio_flete_base'] ?? 0) == 0 && $precio_flete_real == 0);
    
    // CÁLCULO DE UTILIDAD - MISMA LÓGICA QUE AJAX
    $utilidad_estimada = 0;

    if ($flete_gratis) {
        $total_venta = $peso_prov * $precio_venta;
        // Si el flete es gratis, la utilidad es: venta - compra
        $utilidad_estimada = $total_venta - $total_compra;
    } else {
        $total_venta = $peso_fle * $precio_venta;
        if ($peso_prov > 0 && $precio_compra > 0 && $peso_fle > 0 && $precio_venta > 0) {
            // Si hay costo de flete, la utilidad es: venta - compra - flete
            $utilidad_estimada = $total_venta - $total_compra - $precio_flete_real;
        }
    }

    // Acumular totales (MISMA LÓGICA QUE AJAX)
    $utilidad_total += $utilidad_estimada;
    $total_ventas += $total_venta;
    $total_compras += $total_compra;

    // Para el total de fletes, solo sumar si NO es gratis
    if (!$flete_gratis) {
        $total_fletes += $precio_flete_real;
    }

    $total_productos += $peso_prov;
}

// Consulta de recolecciones pendientes (ACTUALIZADA A CONTRA RECIBOS)
$pendientes0 = $conn_mysql->query("
    SELECT COUNT(*) as pendientes
    FROM recoleccion 
    WHERE status = '1' 
    AND fecha_r BETWEEN '$dt_mvI' AND '$dt_mvF'
    AND (folio_inv_pro IS NULL OR folio_inv_pro = '') 
    AND (folio_inv_fle IS NULL OR folio_inv_fle = '') 
    AND pre_flete != 0
    " . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : ""));

$pendientes_data = mysqli_fetch_array($pendientes0);
$recolecciones_pendientes = $pendientes_data['pendientes'] ?? 0;

// Consulta de total general
$total_general0 = $conn_mysql->query("
    SELECT COUNT(*) as total
    FROM recoleccion 
    WHERE status = '1' 
    AND fecha_r BETWEEN '$dt_mvI' AND '$dt_mvF'
    " . ($zona_seleccionada != '0' ? " AND zona = '$zona_seleccionada'" : ""));

$total_general_data = mysqli_fetch_array($total_general0);
$total_general = $total_general_data['total'] ?? 0;
?>

<!-- Tarjetas principales -->
<div class="col-lg-2 col-md-4 col-6 mb-3">
    <div class="card border-indigo shadow-sm h-100 hover-card">
        <div class="card-body text-center p-3">
            <div class="bg-indigo bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                <i class="bi bi-truck text-indigo fs-5"></i>
            </div>
            <h4 class="fw-bold text-indigo mb-1"><?= number_format($total_recolecciones) ?></h4>
            <p class="text-muted small mb-2">Recolecciones Completas</p>
            <span class="badge bg-indigo bg-opacity-25 text-indigo small"><?= date('d/m/y', strtotime($dt_mvI)) ?> - <?= date('d/m/y', strtotime($dt_mvF)) ?></span>
        </div>
    </div>
</div>

<div class="col-lg-2 col-md-4 col-6 mb-3">
    <div class="card border-success shadow-sm h-100 hover-card">
        <div class="card-body text-center p-3">
            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                <i class="bi bi-arrow-up-circle text-success fs-5"></i>
            </div>
            <h4 class="fw-bold text-success mb-1">$<?= number_format($total_ventas, 0) ?></h4>
            <p class="text-muted small mb-2">Ventas</p>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-success" style="width: 100%"></div>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-2 col-md-4 col-6 mb-3">
    <div class="card border-primary shadow-sm h-100 hover-card">
        <div class="card-body text-center p-3">
            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                <i class="bi bi-arrow-down-circle text-primary fs-5"></i>
            </div>
            <h4 class="fw-bold text-primary mb-1">$<?= number_format($total_compras, 0) ?></h4>
            <p class="text-muted small mb-2">Compras</p>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-primary" style="width: 100%"></div>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-2 col-md-4 col-6 mb-3">
    <div class="card border-orange shadow-sm h-100 hover-card">
        <div class="card-body text-center p-3">
            <div class="bg-orange bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                <i class="bi bi-fuel-pump text-orange fs-5"></i>
            </div>
            <h4 class="fw-bold text-orange mb-1">$<?= number_format($total_fletes, 0) ?></h4>
            <p class="text-muted small mb-2">Fletes</p>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-orange" style="width: 100%"></div>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4 col-md-8 col-12 mb-3">
    <div class="card border-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> shadow-sm h-100">
        <div class="card-header bg-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> bg-opacity-10 border-0">
            <h6 class="mb-0 text-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?>">
                <i class="bi bi-graph-up-arrow me-2"></i>Resumen Financiero
            </h6>
        </div>
        <div class="card-body text-center">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div class="bg-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-currency-dollar text-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> fs-4"></i>
                </div>
                <div>
                    <h2 class="fw-bold text-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> mb-0">
                        $<?= number_format($utilidad_total, 2) ?>
                    </h2>
                    <p class="text-muted small mb-0">Utilidad Neta</p>
                </div>
            </div>
            
            <div class="row text-center mt-3">
                <div class="col-6 border-end">
                    <h6 class="text-success mb-1">$<?= number_format($total_ventas, 2) ?></h6>
                    <p class="text-muted small mb-0">Ingresos</p>
                </div>
                <div class="col-6">
                    <h6 class="text-danger mb-1">$<?= number_format($total_compras + $total_fletes, 2) ?></h6>
                    <p class="text-muted small mb-0">Egresos</p>
                </div>
            </div>
            
            <!-- VERIFICACIÓN DE CÁLCULO 
            <div class="mt-2 p-2 bg-body-tertiary rounded small">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Fórmula:</span>
                    <span class="fw-bold">Ventas - Compras - Fletes</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted">Resultado:</span>
                    <span class="fw-bold text-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?>">
                        $<?= number_format($total_ventas, 2) ?> - $<?= number_format($total_compras, 2) ?> - $<?= number_format($total_fletes, 2) ?> = $<?= number_format($utilidad_total, 2) ?>
                    </span>
                </div>
            </div> -->
            
            <div class="mt-3 p-2 bg-body-tertiary rounded">
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Recolecciones:</span>
                    <span class="fw-bold"><?= number_format($total_recolecciones) ?> completas</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small mt-1">
                    <span class="text-muted">Producto movido:</span>
                    <span class="fw-bold"><?= number_format($total_productos, 2) ?> kg</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small mt-1">
                    <span class="text-muted">Periodo:</span>
                    <span class="badge bg-teal"><?= date('d/m/Y', strtotime($dt_mvI)) ?> - <?= date('d/m/Y', strtotime($dt_mvF)) ?></span>
                </div>
            </div>
            
            <a href="?p=recoleccion<?= $zona_seleccionada != '0' ? "&zona=$zona_seleccionada" : '' ?>" 
               class="btn btn-<?= $utilidad_total >= 0 ? 'success' : 'danger' ?> btn-sm mt-3">
                <i class="bi bi-clipboard-data me-1"></i>Ver Detalles
            </a>
        </div>
    </div>
</div>

<!-- Tarjeta adicional para estadísticas rápidas -->
<div class="col-12 mt-2">
    <div class="card border-secondary shadow-sm">
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-md-3 border-end">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-arrow-up-right-circle-fill text-indigo me-2"></i>
                        <div>
                            <h6 class="mb-0 text-indigo"><?= number_format($total_general) ?></h6>
                            <small class="text-muted">Total Recolecciones</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <div>
                            <h6 class="mb-0 text-success"><?= number_format($total_recolecciones) ?></h6>
                            <small class="text-muted">Completadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-clock-fill text-warning me-2"></i>
                        <div>
                            <h6 class="mb-0 text-warning"><?= number_format($total_general - $total_recolecciones) ?></h6>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-box-seam text-info me-2"></i>
                        <div>
                            <h6 class="mb-0 text-info"><?= number_format($total_productos, 0) ?> kg</h6>
                            <small class="text-muted">Producto Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
    cursor: pointer;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
</style>