<style>
    .btn-circle {
    border-radius: 100%;
    height: 2.5rem;
    width: 2.5rem;
    font-size: 1rem;
    display: inline-flex
;
    align-items: center;
    justify-content: center;
}
</style>

<div class="container mt-2" <?= $perm['REPORTES'];?>>
    <div class="card shadow-sm">
        <h5 class="card-header encabezado-col text-white">Subir Masivos</h5>
        <div class="card-body">
            <div class="mb-3">
                 <form action="?p=excel_flujo" method="POST" enctype="multipart/form-data"/>
                    <div class="mb-3">
                      <input class="form-control" id="file-input" name="dataCliente" type="file" accept=".csv" id="formFile" required> 
                      <label class="file-input__label" for="file-input"><br>
                      <input type="submit" name="subir" class="btn btn-outline-primary" value="Subir Excel"/>
                      <a class="btn btn-outline-purple" href="descargas/PlantillaParaSubirCompraVentas.csv" download="PlantillaExcelMovimientos"><i class="fa-solid fa-circle-down"></i> Descargar plantilla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>