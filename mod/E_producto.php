<?php
// Obtener el ID del producto a editar
$id_prod = $_GET['id'] ?? 0;

// Consultar los datos del producto
$prodQuery = $conn_mysql->prepare("SELECT * FROM productos WHERE id_prod = ?");
$prodQuery->bind_param('i', $id_prod);
$prodQuery->execute();
$prodData = $prodQuery->get_result()->fetch_assoc();

if (!$prodData) {
    alert("Producto no encontrado", 0, "productos");
    exit();
}

// Procesar el formulario de actualización
if (isset($_POST['guardar01'])) {
    try {
        $ProductoData = [
            'nom_pro' => $_POST['nom_pro'] ?? '',
            'cod' => $_POST['cod'] ?? '',
            'lin' => $_POST['lin'] ?? '',
            'id_user' => $idUser,
            'zona' => $_POST['zona'] ?? $zona_seleccionada
        ];

        // Actualizar producto con MySQLi
        $setClause = implode(' = ?, ', array_keys($ProductoData)) . ' = ?';
        $sql = "UPDATE productos SET $setClause WHERE id_prod = ?";
        $stmt = $conn_mysql->prepare($sql);
        
        // Pasar los valores en el orden correcto (datos + id)
        $values = array_values($ProductoData);
        $values[] = $id_prod;
        
        $types = str_repeat('s', count($ProductoData)) . 'i'; // 's' para strings, 'i' para id
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Producto actualizado exitosamente", 1, "productos");
            logActivity('EDITAR', 'Se edito el producto '. $id_prod);
        } else {
            alert("No se realizaron cambios en el producto", 1, "productos");
            logActivity('EDITAR', 'No realizo cambios en el producto '. $id_prod);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_producto&id=$id_prod");
        logActivity('EDITAR', 'Error al tratar de editar el producto '. $id_prod);
    }
}
?>
<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Producto</h5>
            <a href="?p=productos">
                <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
            </a>
        </div>
        <div class="card-body">
            <form class="forms-sample" method="post" action="">
                <!-- Sección de información básica del producto -->
                <div class="form-section">
                    <h5 class="section-header">Información del Producto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom_pro" class="form-label">Nombre del Producto</label>
                            <input name="nom_pro" type="text" class="form-control" id="nom_pro" 
                                   value="<?= htmlspecialchars($prodData['nom_pro'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cod" class="form-label">Código</label>
                            <input name="cod" type="text" class="form-control" id="cod" 
                                   value="<?= htmlspecialchars($prodData['cod'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label for="lin" class="form-label">Línea</label>
                            <input name="lin" type="text" class="form-control" id="lin" 
                                   value="<?= htmlspecialchars($prodData['lin'] ?? '') ?>" required>
                        </div>
                        <?php
                        if ($zona_seleccionada == '0') {
                            ?>
                            <div class="col-md-4">
                                <label for="zona" class="form-label">Zona</label>
                                <select class="form-select" name="zona" id="zona">
                                    <?php
                                    $zona0 = $conn_mysql->query("SELECT * FROM zonas WHERE status = 1");
                                    while ($zona1 = mysqli_fetch_array($zona0)) {
                                        ?>
                                        <option value="<?=$zona1['id_zone']?>" <?= ($prodData['zona'] ?? '') == $zona1['id_zone'] ? 'selected' : '' ?>> <?=$zona1['nom']?> </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardar01" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>