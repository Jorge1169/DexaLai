<?php
$id_bodega = $_GET['id'] ?? 0;


$bodega = [];
if ($id_bodega > 0) {
    $query = "SELECT b.cod_al AS codigo_bodega,
    b.noma AS nombre_bodega,
    b.status AS status_bodega,
    b.id_prov AS id_proveedor_b,
    b.id_us AS id_cliente_b
    FROM direcciones b
    WHERE b.id_direc = ?";

    $stmt = $conn_mysql->prepare($query);
    $stmt->bind_param("i", $id_bodega);
    $stmt->execute();
    $result = $stmt->get_result();
    $bodega = $result->fetch_assoc();
}
if (!$bodega) {
    alert("Bodega no encontrada",0,"reporte_bodegas");
}
$tipo = [];
$dn_bode = '';
$base = '';
if (!empty($bodega['id_proveedor_b'])) {
    $dn_bode = 'Proveedor';
    $base = 'compras';

    $tipo0 = $conn_mysql->query("SELECT t.*,
        t.nombre AS nombre_t,
        t.rs AS rs_t,
        t.rfc AS rfc_t
        FROM proveedores t WHERE t.id_prov = '".$bodega['id_proveedor_b']."'");
    $tipo = mysqli_fetch_array($tipo0);
}
if (!empty($bodega['id_cliente_b'])) {
    $dn_bode = 'Cliente';
    $base = 'ventas';
    $tipo0 = $conn_mysql->query("SELECT t.*,
        t.nombre AS nombre_t,
        t.rs AS rs_t,
        t.rfc AS rfc_t
        FROM clientes t WHERE t.id_cli = '".$bodega['id_cliente_b']."'");
    $tipo = mysqli_fetch_array($tipo0);
}
?>
<div class="container mt-2">
    <div class="card">
        <div class="card-header encabezado-col text-white">
            <div class="d-flex justify-content-between align-items-center row">
                <div class="col-12 col-md-6">
                    <h4 class="mb-1 fw-light">Reporte de movimientos de la bodega </h4>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-white text-col fs-6 py-2 px-3">
                            <i class="bi bi-building me-2"></i><?= htmlspecialchars($bodega['codigo_bodega']) ?>
                        </span>
                        <span class="small">
                            <i class="bi bi-geo-fill ms-3 me-1"></i> <?= htmlspecialchars($bodega['nombre_bodega']) ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-1 col-12 col-md-6 d-flex justify-content-end">
                    <span class="badge bg-<?= $bodega['status_bodega'] ? 'success' : 'danger' ?> py-2 px-3 fs-6">
                        <?= $bodega['status_bodega'] ? 'ACTIVA' : 'INACTIVA' ?>
                    </span>
                    <a href="?p=reporte_bodegas" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-arrow-left me-1"></i> Regresar
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-12 col-md-3">
                    <div class="card shadow-sm mb-3">
                        <h6 class="card-header border-primary"><?= htmlspecialchars($tipo['rs_t']) ?></h6>
                        <div class="card-body">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Tipo</span>
                                <strong><?= htmlspecialchars($dn_bode) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Nombre</span>
                                <strong><?= htmlspecialchars($tipo['nombre_t']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">RFC</span>
                                <strong><?= htmlspecialchars($tipo['rfc_t']) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm mb-3">
                        <h6 class="card-header border-primary">Bodega</h6>
                        <div class="card-body">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">C. bodega</span>
                                <strong><?= htmlspecialchars($bodega['codigo_bodega']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Nombre</span>
                                <strong><?= htmlspecialchars($bodega['nombre_bodega']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <div class="card shadow-sm mb-3">
                        <h6 class="card-header border-primary">Reporte de productos</h6>
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <!-- Select -->
                                <input type="text" value="<?=$base?>" name="tp_mv" id="tp_mv" class="form-control flex-grow-1" style="min-width: 120px; max-width: 200px;" readonly>

                                <!-- Fecha inicial -->
                                <input id="dt_mvI" type="date" class="form-control" name="dt_mvI" 
                                value="<?= date('Y-m-d', strtotime('-5 days')) ?>" 
                                max="<?= date('Y-m-d') ?>"
                                style="width: 160px;">

                                <!-- Fecha final -->
                                <input id="dt_mvF" type="date" class="form-control" name="dt_mvF" 
                                value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"
                                style="width: 160px;">

                                <!-- Campo oculto -->
                                <input type="hidden" name="id_bodega" id="id_bodega" value="<?=$id_bodega?>">

                                <!-- Botón -->
                                <button class="btn btn-sm btn-primary px-3" onclick="pre1()">
                                    <i class="bi bi-search me-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm mb-3">
                        <h6 class="card-header border-primary">Reporte </h6>
                        <div class="card-body">
                            <div class="form-group" id="res1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function pre1(str){
      tp_mv=document.getElementById('tp_mv').value;
      dt_mvI=document.getElementById('dt_mvI').value;
      dt_mvF=document.getElementById('dt_mvF').value;
      id_bodega=document.getElementById('id_bodega').value;
      var parametros = 
      {
        "tp_mv" : tp_mv,
        "dt_mvI" : dt_mvI,
        "dt_mvF" : dt_mvF,
        "id_bodega" : id_bodega
    };

    $.ajax({
        data: parametros,
        url: 'reporte_p.php',
        type: 'POST',

        beforesend: function()
        {
          $('#res1').html("Mensaje antes de Enviar");
      },

      success: function(mensaje)
      {
          $('#res1').html(mensaje);
      }
  });

}
</script>
