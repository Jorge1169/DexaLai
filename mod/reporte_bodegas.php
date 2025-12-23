<div class="container mt-2">
<div class="card shadow-sm">
    <h5 class="card-header encabezado-col text-white">Reporte de productos con movimientos</h5>
    <div class="card-body">
        <div class="mb-3">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="miTabla" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th data-priority="1">Código</th>
                            <th>Nombre</th>
                            <th>Tipo de bodega</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $Contador = 0;
                        $Bodegas0 = $conn_mysql->query("SELECT * FROM direcciones");
                        while ($Bodegas1 = mysqli_fetch_array($Bodegas0)) {
                            $Contador++;
                            ?>
                            <tr>
                                <td class="text-center"><?= $Contador ?></td>
                                <td><?= htmlspecialchars($Bodegas1['cod_al']) ?></td>
                                <td>
                                     <a class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="?p=rp_bodega&id=<?= $Bodegas1['id_direc'] ?>" class="text-primary">
                            <?= htmlspecialchars($Bodegas1['noma']) ?>
                        </a>
                                </td>
                                <td><span class="badge bg-<?= ($Bodegas1['id_prov'] == '')  ?  'purple text-dark' : 'primary'?>"><?= ($Bodegas1['id_prov'] == '')  ? 'Cliente' : 'Proveedor' ?></span></td>
                                <td><span class="badge bg-<?= ($Bodegas1['status'] ?? 0) == 1 ? 'success' : 'danger' ?>"><?= ($Bodegas1['status'] ?? 0) == 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<script>
$(document).ready(function() {
    $('#miTabla').DataTable({
        "language": {
                "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "responsive": true
    });
});
</script>