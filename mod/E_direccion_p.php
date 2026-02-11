<?php
// E_direccion.php - Editar Dirección/Bodega

// Verificación de permisos - Backend
requirePermiso('PROVEEDORES_EDITAR', 'proveedores');

$id_direccion = clear($_GET['id'] ?? '');
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql); // Obtener tipo de zona

$direccion = [];
$cliente = [];

if ($id_direccion) {
    // Obtener datos de la dirección
    $sqlDireccion = "SELECT * FROM direcciones WHERE id_direc = ?";
    $stmtDireccion = $conn_mysql->prepare($sqlDireccion);
    $stmtDireccion->bind_param('i', $id_direccion);
    $stmtDireccion->execute();
    $resultDireccion = $stmtDireccion->get_result();
    $direccion = $resultDireccion->fetch_assoc();
    
    // Obtener datos del cliente asociado
    if (!empty($direccion['id_prov'])) {
        $sqlCliente = "SELECT id_prov, cod, nombre, rs FROM proveedores WHERE id_prov = ?";
        $stmtCliente = $conn_mysql->prepare($sqlCliente);
        $stmtCliente->bind_param('i', $direccion['id_prov']);
        $stmtCliente->execute();
        $resultCliente = $stmtCliente->get_result();
        $cliente = $resultCliente->fetch_assoc();
    }
}

if (isset($_POST['guardarDireccion'])) {
    try {
        $DireccionData = [
            'cod_al' => $_POST['cod_al'] ?? '',
            'noma' => $_POST['noma'] ?? '',
            'atencion' => $_POST['atencion'] ?? '',
            'tel' => $_POST['tel'] ?? '',
            'email' => $_POST['email'] ?? '',
            'obs' => $_POST['obs'] ?? '',
            'id_direc' => $id_direccion
        ];

            $DireccionData['calle'] = $_POST['calle'] ?? '';
            $DireccionData['c_postal'] = $_POST['c_postal'] ?? '';
            $DireccionData['numext'] = $_POST['numext'] ?? '';
            $DireccionData['numint'] = $_POST['numint'] ?? '';
            $DireccionData['pais'] = $_POST['pais'] ?? 'México';
            $DireccionData['estado'] = $_POST['estado'] ?? '';
            $DireccionData['colonia'] = $_POST['colonia'] ?? '';

        // Actualizar dirección
        $setParts = [];
        $types = '';
        $values = [];
        
        foreach ($DireccionData as $key => $value) {
            if ($key !== 'id_direc') {
                $setParts[] = "$key = ?";
                $types .= 's';
                $values[] = $value;
            }
        }
        
        $values[] = $id_direccion; // Para el WHERE
        
        $sql = "UPDATE direcciones SET " . implode(', ', $setParts) . " WHERE id_direc = ?";
        $stmt = $conn_mysql->prepare($sql);
        $stmt->bind_param($types . 'i', ...$values);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            alert("Bodega actualizada exitosamente", 1, "V_proveedores&id=" . $cliente['id_prov']);
            logActivity('EDITAR', 'Edito la bodega ' . $id_direccion . ' Para el proveedor '. $cliente['id_prov']);
        } else {
            alert("No se realizaron cambios en la bodega", 1, "V_proveedores&id=" . $cliente['id_prov']);
            logActivity('EDITAR', 'No realizo cambios en la bodega ' . $id_direccion . ' Para el proveedor '. $cliente['id_prov']);
        }
    } catch (mysqli_sql_exception $e) {
        alert("Error: " . $e->getMessage(), 0, "E_direccion_p&id=$id_direccion");
        logActivity('EDITAR', 'Error al tratar de editar la bodega ' . $id_direccion . ' Para el proveedor '. $cliente['id_prov']);
    }
}
?>

<div class="container mt-2">
    <div class="card shadow-sm">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Bodega</h5>
            <a href="?p=V_proveedores&id=<?= $cliente['id_prov'] ?? '' ?>" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Regresar
            </a>
        </div>
        <div class="card-body">
            <!-- Información del cliente -->
            <div class="mb-4 p-3 border rounded">
                <h6>Proveedor asociado:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Código:</strong> <?= htmlspecialchars($cliente['cod'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Razón Social:</strong> <?= htmlspecialchars($cliente['rs'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            
            <form class="forms-sample" method="post" action="">
                <!-- Campos básicos de la bodega -->
                <div class="form-section">
                    <h5 class="section-header">Información de la Bodega</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cod_al" class="form-label">Código de Bodega</label>
                            <input name="cod_al" type="text" class="form-control" id="cod_al" 
                                   value="<?= htmlspecialchars($direccion['cod_al'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="noma" class="form-label">Nombre de Bodega</label>
                            <input name="noma" type="text" class="form-control" id="noma" 
                                   value="<?= htmlspecialchars($direccion['noma'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="atencion" class="form-label">Atención</label>
                            <input name="atencion" type="text" class="form-control" id="atencion" 
                                   value="<?= htmlspecialchars($direccion['atencion'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="tel" class="form-label">Teléfono</label>
                            <input name="tel" type="tel" class="form-control" id="tel" 
                                   value="<?= htmlspecialchars($direccion['tel'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" id="email" 
                                   value="<?= htmlspecialchars($direccion['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="obs" class="form-label">Observaciones</label>
                            <textarea name="obs" class="form-control" id="obs" rows="3"><?= htmlspecialchars($direccion['obs'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE DIRECCIÓN FÍSICA (solo para MEO) -->
                <div class="form-section mt-4">
                    <h5 class="section-header text-info">
                        <i class="bi bi-geo-alt me-2"></i> Dirección Física
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="calle" class="form-label">Calle</label>
                            <input name="calle" type="text" class="form-control" id="calle" 
                                   value="<?= htmlspecialchars($direccion['calle'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numext" class="form-label">Número Exterior</label>
                            <input name="numext" type="text" class="form-control" id="numext" 
                                   value="<?= htmlspecialchars($direccion['numext'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numint" class="form-label">Número Interior</label>
                            <input name="numint" type="text" class="form-control" id="numint" 
                                   value="<?= htmlspecialchars($direccion['numint'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="colonia" class="form-label">Colonia</label>
                            <input name="colonia" type="text" class="form-control" id="colonia" 
                                   value="<?= htmlspecialchars($direccion['colonia'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="c_postal" class="form-label">Código Postal</label>
                            <input name="c_postal" type="text" class="form-control" id="c_postal" maxlength="5"
                                   value="<?= htmlspecialchars($direccion['c_postal'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" class="form-select" id="estado">
                                <option value="">Seleccionar estado...</option>
                                <option value="Aguascalientes" <?= ($direccion['estado'] ?? '') == 'Aguascalientes' ? 'selected' : '' ?>>Aguascalientes</option>
                                <option value="Baja California" <?= ($direccion['estado'] ?? '') == 'Baja California' ? 'selected' : '' ?>>Baja California</option>
                                <option value="Baja California Sur" <?= ($direccion['estado'] ?? '') == 'Baja California Sur' ? 'selected' : '' ?>>Baja California Sur</option>
                                <option value="Campeche" <?= ($direccion['estado'] ?? '') == 'Campeche' ? 'selected' : '' ?>>Campeche</option>
                                <option value="Chiapas" <?= ($direccion['estado'] ?? '') == 'Chiapas' ? 'selected' : '' ?>>Chiapas</option>
                                <option value="Chihuahua" <?= ($direccion['estado'] ?? '') == 'Chihuahua' ? 'selected' : '' ?>>Chihuahua</option>
                                <option value="Ciudad de México" <?= ($direccion['estado'] ?? '') == 'Ciudad de México' ? 'selected' : '' ?>>Ciudad de México</option>
                                <option value="Coahuila" <?= ($direccion['estado'] ?? '') == 'Coahuila' ? 'selected' : '' ?>>Coahuila</option>
                                <option value="Colima" <?= ($direccion['estado'] ?? '') == 'Colima' ? 'selected' : '' ?>>Colima</option>
                                <option value="Durango" <?= ($direccion['estado'] ?? '') == 'Durango' ? 'selected' : '' ?>>Durango</option>
                                <option value="Estado de México" <?= ($direccion['estado'] ?? '') == 'Estado de México' ? 'selected' : '' ?>>Estado de México</option>
                                <option value="Guanajuato" <?= ($direccion['estado'] ?? '') == 'Guanajuato' ? 'selected' : '' ?>>Guanajuato</option>
                                <option value="Guerrero" <?= ($direccion['estado'] ?? '') == 'Guerrero' ? 'selected' : '' ?>>Guerrero</option>
                                <option value="Hidalgo" <?= ($direccion['estado'] ?? '') == 'Hidalgo' ? 'selected' : '' ?>>Hidalgo</option>
                                <option value="Jalisco" <?= ($direccion['estado'] ?? '') == 'Jalisco' ? 'selected' : '' ?>>Jalisco</option>
                                <option value="Michoacán" <?= ($direccion['estado'] ?? '') == 'Michoacán' ? 'selected' : '' ?>>Michoacán</option>
                                <option value="Morelos" <?= ($direccion['estado'] ?? '') == 'Morelos' ? 'selected' : '' ?>>Morelos</option>
                                <option value="Nayarit" <?= ($direccion['estado'] ?? '') == 'Nayarit' ? 'selected' : '' ?>>Nayarit</option>
                                <option value="Nuevo León" <?= ($direccion['estado'] ?? '') == 'Nuevo León' ? 'selected' : '' ?>>Nuevo León</option>
                                <option value="Oaxaca" <?= ($direccion['estado'] ?? '') == 'Oaxaca' ? 'selected' : '' ?>>Oaxaca</option>
                                <option value="Puebla" <?= ($direccion['estado'] ?? '') == 'Puebla' ? 'selected' : '' ?>>Puebla</option>
                                <option value="Querétaro" <?= ($direccion['estado'] ?? '') == 'Querétaro' ? 'selected' : '' ?>>Querétaro</option>
                                <option value="Quintana Roo" <?= ($direccion['estado'] ?? '') == 'Quintana Roo' ? 'selected' : '' ?>>Quintana Roo</option>
                                <option value="San Luis Potosí" <?= ($direccion['estado'] ?? '') == 'San Luis Potosí' ? 'selected' : '' ?>>San Luis Potosí</option>
                                <option value="Sinaloa" <?= ($direccion['estado'] ?? '') == 'Sinaloa' ? 'selected' : '' ?>>Sinaloa</option>
                                <option value="Sonora" <?= ($direccion['estado'] ?? '') == 'Sonora' ? 'selected' : '' ?>>Sonora</option>
                                <option value="Tabasco" <?= ($direccion['estado'] ?? '') == 'Tabasco' ? 'selected' : '' ?>>Tabasco</option>
                                <option value="Tamaulipas" <?= ($direccion['estado'] ?? '') == 'Tamaulipas' ? 'selected' : '' ?>>Tamaulipas</option>
                                <option value="Tlaxcala" <?= ($direccion['estado'] ?? '') == 'Tlaxcala' ? 'selected' : '' ?>>Tlaxcala</option>
                                <option value="Veracruz" <?= ($direccion['estado'] ?? '') == 'Veracruz' ? 'selected' : '' ?>>Veracruz</option>
                                <option value="Yucatán" <?= ($direccion['estado'] ?? '') == 'Yucatán' ? 'selected' : '' ?>>Yucatán</option>
                                <option value="Zacatecas" <?= ($direccion['estado'] ?? '') == 'Zacatecas' ? 'selected' : '' ?>>Zacatecas</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pais" class="form-label">País</label>
                            <input name="pais" type="text" class="form-control" id="pais" 
                                   value="<?= htmlspecialchars($direccion['pais'] ?? 'México') ?>" readonly>
                        </div>
                    </div>
                </div>
                <!-- Botones de acción -->
                <div class="d-flex justify-content-md-end mt-4">
                    <button type="submit" name="guardarDireccion" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación para desactivar dirección -->
<div class="modal fade" id="confirmDesactivarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar desactivación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas desactivar esta bodega?</p>
                <input type="hidden" id="direccionDesactivarId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDesactivarBtn">Desactivar</button>
            </div>
        </div>
    </div>
</div>