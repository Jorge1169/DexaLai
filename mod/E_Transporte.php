<?php
// Obtener ID del transporte
$id = clear($_GET['id'] ?? '');

// Obtener datos del transporte existente
$transporte = [];
if ($id) {
    $stmt = $conn_melodb->prepare("SELECT * FROM Transportes WHERE idTransporte = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $transporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transporte) {
        alert("Transporte no encontrado", 0, "transportes");
    }
}

// Verificar si se envió el formulario de actualización
if (isset($_POST['actualizar'])) {
    try {
        // Mapeo de campos del formulario con valores por defecto
        $TranspData = [
            'Linea' => $_POST['linea'] ?? '',
            'Camion' => $_POST['camion'] ?? '',
            'chofer' => $_POST['chofer'] ?? '',
            'IAVE' => $_POST['iave'] ?? '',
            'numEconomico' => $_POST['unidad'] ?? '',
            'TaraC' => !empty($_POST['tara']) ? $_POST['tara'] : 0,
            'Tolerancia' => !empty($_POST['tolerancia']) ? $_POST['tolerancia'] : 0,
            'CodigoBodega' => $_POST['bodega'] ?? 'PB'
        ];

        // Preparar consulta UPDATE
        $setParts = [];
        foreach ($TranspData as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE Transportes SET $setClause WHERE idTransporte = :id";
        $stmt = $conn_melodb->prepare($sql);
        
        // Agregar el ID a los parámetros
        $TranspData['id'] = $id;
        
        // Ejecutar la consulta
        $stmt->execute($TranspData);

        if ($stmt->rowCount() > 0) {
            alert("Transporte actualizado exitosamente", 1, "transportes");
        } else {
            alert("No se realizaron cambios en el transporte", 1, "transportes");
        }
    } catch (PDOException $e) {
        alert("Error de base de datos: " . $e->getMessage(), 0, "E_transporte&id=$id");
    } catch (Exception $e) {
        alert($e->getMessage(), 0, "E_transporte?id=$id");
    }
}
?>

<div class="card shadow-sm">
  <h5 class="card-header">Editar Transporte</h5>
  <div class="card-body">
    <form class="forms-sample" method="post" action="">
      <!-- Sección General -->
      <div class="form-section">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Placas</label>
            <input type="text" class="form-control" 
                   value="<?= htmlspecialchars($transporte['placas'] ?? '') ?>" 
                   readonly>
            <input type="hidden" name="placas" value="<?= htmlspecialchars($transporte['placas'] ?? '') ?>">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Línea</label>
            <select class="form-select" name="linea">
              <?php
              $Lineat0 = $conn_melodb->query("SELECT DISTINCT Linea FROM Transportes ORDER BY Linea");
              while ($Lineat1 = $Lineat0->fetch(PDO::FETCH_ASSOC)) {
                $selected = ($transporte['Linea'] ?? '') == $Lineat1['Linea'] ? 'selected' : '';
                ?>
                <option value="<?= htmlspecialchars($Lineat1['Linea']) ?>" <?= $selected ?>>
                  <?= htmlspecialchars($Lineat1['Linea']) ?>
                </option>
                <?php
              }
              ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Camión</label>
            <select class="form-select" name="camion">
              <?php
              $Cam0 = $conn_melodb->query("SELECT * FROM TiposTransportes");
              while ($Cam1 = $Cam0->fetch(PDO::FETCH_ASSOC)) {
                $selected = ($transporte['Camion'] ?? '') == $Cam1['Tipo'] ? 'selected' : '';
                ?>
                <option value="<?= htmlspecialchars($Cam1['Tipo']) ?>" <?= $selected ?>>
                  <?= htmlspecialchars($Cam1['Tipo']) ?>
                </option>
                <?php
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Chofer</label>
            <input type="text" class="form-control" name="chofer" required
                   value="<?= htmlspecialchars($transporte['chofer'] ?? '') ?>">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">IAVE</label>
            <input type="text" class="form-control" name="iave"
                   value="<?= htmlspecialchars($transporte['IAVE'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Unidad</label>
            <input type="text" class="form-control" name="unidad"
                   value="<?= htmlspecialchars($transporte['numEconomico'] ?? '') ?>">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Tara</label>
            <input type="number" class="form-control" name="tara" step="0.01"
                   value="<?= htmlspecialchars($transporte['TaraC'] ?? '0') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tolerancia</label>
            <input type="number" class="form-control" name="tolerancia" step="0.01"
                   value="<?= htmlspecialchars($transporte['Tolerancia'] ?? '0') ?>">
          </div>
        </div>
      </div>

      <!-- Sección Bodega -->
      <div class="form-section mt-4">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Bodega</label>
            <select class="form-select" name="bodega">
              <?php
              $Pla2 = $conn_usersdb->query("SELECT * FROM Usuarios_Regiones WHERE CodigoUsuario = '$Usuario'");
              while ($Pla3 = $Pla2->fetch(PDO::FETCH_ASSOC)) {
                $Slpl3 = $conn_usersdb->query("SELECT * FROM regiones_y_zonas WHERE CodigoBodega = '".$Pla3['CodigoBodega']."'");
                $Slpl4 = $Slpl3->fetch(PDO::FETCH_ASSOC);

                $selected = ($transporte['CodigoBodega'] ?? '') === $Pla3['CodigoBodega'] ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($Pla3['CodigoBodega']) . '" ' . $selected . '>' 
                     . htmlspecialchars($Slpl4['NombreBodega']) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Botones de acción -->
      <div class="d-flex justify-content-end gap-3 mt-4">
        <a href="?p=transportes">
          <button type="button" class="btn btn-secondary">Cancelar</button>
        </a>
        <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
      </div>
    </form>
  </div>
</div>