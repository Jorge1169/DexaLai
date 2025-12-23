<?php
// Obtener productos de la sesión
$productos_agregados = $_SESSION['productos_agregados'] ?? [];

if (!empty($productos_agregados)): 
    // Calcular totales
    $total_general_kilos = 0;
    $total_general_granel = 0;
    $total_general_pacas = 0;
    $total_general_cantidad = 0;
    
    foreach ($productos_agregados as $producto) {
        $total_general_kilos += $producto['total_kilos'];
        $total_general_granel += $producto['granel_kilos'];
        $total_general_pacas += $producto['pacas_kilos'];
        $total_general_cantidad += $producto['pacas_cantidad'];
    }
?>
<div class="form-section shadow-sm mb-4" id="tabla-productos-container">
    <h5 class="section-header">Productos Agregados 
        <span class="badge bg-primary"><?= count($productos_agregados) ?> productos</span>
        <span class="badge bg-success"><?= number_format($total_general_kilos, 2) ?> kg total</span>
    </h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Granel (kg)</th>
                    <th>Pacas (cant)</th>
                    <th>Pacas (kg)</th>
                    <th>Promedio</th>
                    <th>Total (kg)</th>
                    <th>Precio</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos_agregados as $index => $producto): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($producto['cod_producto']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($producto['nombre_producto']) ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $producto['tipo_almacen'] == 'granel' ? 'bg-warning' : 'bg-info' ?>">
                                <?= ucfirst($producto['tipo_almacen']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?= $producto['granel_kilos'] > 0 ? number_format($producto['granel_kilos'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['pacas_cantidad'] > 0 ? $producto['pacas_cantidad'] : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['pacas_kilos'] > 0 ? number_format($producto['pacas_kilos'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <?= $producto['peso_promedio'] > 0 ? number_format($producto['peso_promedio'], 2) . ' kg' : '-' ?>
                        </td>
                        <td class="text-end">
                            <strong><?= number_format($producto['total_kilos'], 2) ?> kg</strong>
                        </td>
                        <td class="text-end">$<?= number_format($producto['precio_valor'], 2) ?></td>
                        <td><small><?= htmlspecialchars($producto['observaciones']) ?></small></td>
                        <td>
                            <button type="button" onclick="eliminarProducto(<?= $index ?>)" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Totales -->
                <tr class="table-secondary fw-bold">
                    <td colspan="3" class="text-end">TOTALES:</td>
                    <td class="text-end"><?= number_format($total_general_granel, 2) ?> kg</td>
                    <td class="text-end"><?= $total_general_cantidad ?></td>
                    <td class="text-end"><?= number_format($total_general_pacas, 2) ?> kg</td>
                    <td class="text-end">-</td>
                    <td class="text-end"><?= number_format($total_general_kilos, 2) ?> kg</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay productos agregados aún. Agregue productos usando el formulario superior.
</div>
<?php endif; ?>