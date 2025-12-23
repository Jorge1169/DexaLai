<?php
// Datos de ejemplo - en un caso real estos vendrían de una base de datos
$ventas = [
    ['nombre' => 'Producto A', 'monto' => 1500, 'fecha' => '2023-01-15'],
    ['nombre' => 'Producto B', 'monto' => 2300, 'fecha' => '2023-01-18'],
    ['nombre' => 'Producto C', 'monto' => 1800, 'fecha' => '2023-01-20']
];

$compras = [
    ['monto' => 1200, 'fecha' => '2023-01-14'],
    ['monto' => 2000, 'fecha' => '2023-01-17'],
    ['monto' => 1500, 'fecha' => '2023-01-19']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico de Ventas vs Compras</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Comparación de Ventas y Compras</h1>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        Gráfico de Ventas vs Compras
                    </div>
                    <div class="card-body">
                        <canvas id="miGrafico" height="400"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h3>Datos de Ventas</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ventas as $venta): ?>
                        <tr>
                            <td><?= htmlspecialchars($venta['nombre']) ?></td>
                            <td>$<?= number_format($venta['monto'], 2) ?></td>
                            <td><?= htmlspecialchars($venta['fecha']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h3>Datos de Compras</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                        <tr>
                            <td>$<?= number_format($compra['monto'], 2) ?></td>
                            <td><?= htmlspecialchars($compra['fecha']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('miGrafico').getContext('2d');
            
            // Preparar datos desde PHP a JavaScript
            const ventas = <?= json_encode($ventas) ?>;
            const compras = <?= json_encode($compras) ?>;
            
            // Extraer fechas únicas y ordenarlas
            const todasFechas = [
                ...ventas.map(v => v.fecha),
                ...compras.map(c => c.fecha)
            ].filter((fecha, i, arr) => arr.indexOf(fecha) === i).sort();
            
            // Mapear montos por fecha
            const montosVentas = todasFechas.map(fecha => {
                const venta = ventas.find(v => v.fecha === fecha);
                return venta ? venta.monto : 0;
            });
            
            const montosCompras = todasFechas.map(fecha => {
                const compra = compras.find(c => c.fecha === fecha);
                return compra ? compra.monto : 0;
            });
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: todasFechas,
                    datasets: [
                        {
                            label: 'Ventas',
                            data: montosVentas,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Compras',
                            data: montosCompras,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Monto ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>