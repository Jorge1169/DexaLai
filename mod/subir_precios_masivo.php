<?php
$tipoZonaActual = obtenerTipoZonaActual($conn_mysql);
$zona_actual = $_SESSION['selected_zone'] ?? '0';

// Procesar carga masiva de precios
if (isset($_POST['procesar_carga_masiva'])) {
    $fleteros_seleccionados = $_POST['fleteros'] ?? [];
    $todos_fleteros = isset($_POST['todos_fleteros']) && $_POST['todos_fleteros'] == '1';
    
    // Si se seleccionó "todos", obtener todos los fleteros activos de la zona
    if ($todos_fleteros) {
        $sqlFleteros = "SELECT id_transp FROM transportes WHERE status = '1'";
        if ($zona_actual != '0') {
            $sqlFleteros .= " AND zona = " . intval($zona_actual);
        }
        $resFleteros = $conn_mysql->query($sqlFleteros);
        $fleteros_seleccionados = [];
        while ($row = mysqli_fetch_assoc($resFleteros)) {
            $fleteros_seleccionados[] = $row['id_transp'];
        }
    }
    
    if (empty($fleteros_seleccionados)) {
        alert("Debe seleccionar al menos un fletero", 2, "subir_precios_masivo");
        exit;
    }
    
    // Obtener datos del formulario
    $tipo_precio = $_POST['tipo'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $origen = $_POST['origen'] ?? '';
    $destino = $_POST['destino'] ?? '';
    $conmin = $_POST['conmin'] ?? 0;
    $fecha_ini = $_POST['fechaini'] ?? date('Y-m-d');
    $fecha_fin = $_POST['fechafin'] ?? date('Y-m-d');
    $cap_ven = $_POST['cap_ven'] ?? '';
    
    // Validaciones
    if (empty($tipo_precio) || empty($precio) || empty($origen) || empty($destino)) {
        alert("Todos los campos obligatorios deben estar llenos", 2, "subir_precios_masivo");
        exit;
    }
    
    $conn_mysql->begin_transaction();
    
    try {
        $insertados = 0;
        $actualizados = 0;
        
        foreach ($fleteros_seleccionados as $id_fletero) {
            // Verificar si ya existe un precio con las mismas condiciones
            $sqlCheck = "SELECT id_precio FROM precios 
                        WHERE id_prod = ? 
                        AND origen = ? 
                        AND destino = ? 
                        AND tipo = ? 
                        AND status = '1'";
            
            $params = [$id_fletero, $origen, $destino, $tipo_precio];
            $types = 'ssss';
            
            // Para MEO, incluir cap_ven en la búsqueda
            if ($tipoZonaActual == 'MEO' && !empty($cap_ven)) {
                $sqlCheck .= " AND cap_ven = ?";
                $params[] = $cap_ven;
                $types .= 's';
            }
            
            $stmtCheck = $conn_mysql->prepare($sqlCheck);
            $stmtCheck->bind_param($types, ...$params);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            
            if ($resultCheck->num_rows > 0) {
                // Ya existe, actualizar solo la fecha_fin
                $row = $resultCheck->fetch_assoc();
                $id_precio_existente = $row['id_precio'];
                
                $sqlUpdate = "UPDATE precios 
                             SET fecha_fin = ?, 
                                 precio = ?, 
                                 conmin = ?, 
                                 usuario = ? 
                             WHERE id_precio = ?";
                $stmtUpdate = $conn_mysql->prepare($sqlUpdate);
                $stmtUpdate->bind_param('sssii', $fecha_fin, $precio, $conmin, $idUser, $id_precio_existente);
                $stmtUpdate->execute();
                $actualizados++;
            } else {
                // No existe, insertar nuevo registro
                $PrecioData = [
                    'id_prod' => $id_fletero,
                    'precio' => $precio,
                    'tipo' => $tipo_precio,
                    'origen' => $origen,
                    'destino' => $destino,
                    'conmin' => $conmin,
                    'fecha_ini' => $fecha_ini,
                    'fecha_fin' => $fecha_fin,
                    'usuario' => $idUser,
                    'status' => 1
                ];
                
                // Para MEO, agregar cap_ven
                if ($tipoZonaActual == 'MEO' && !empty($cap_ven)) {
                    $PrecioData['cap_ven'] = $cap_ven;
                }
                
                $columns = implode(', ', array_keys($PrecioData));
                $placeholders = str_repeat('?,', count($PrecioData) - 1) . '?';
                $sqlInsert = "INSERT INTO precios ($columns) VALUES ($placeholders)";
                $stmtInsert = $conn_mysql->prepare($sqlInsert);
                $typesInsert = str_repeat('s', count($PrecioData));
                $stmtInsert->bind_param($typesInsert, ...array_values($PrecioData));
                $stmtInsert->execute();
                $insertados++;
            }
        }
        
        $conn_mysql->commit();
        
        $mensaje = "Proceso completado: $insertados precios nuevos insertados, $actualizados precios actualizados";
        alert($mensaje, 1, "subir_precios_masivo");
        logActivity('PRECIO_MASIVO', "Carga masiva: $insertados insertados, $actualizados actualizados");
        
    } catch (Exception $e) {
        $conn_mysql->rollback();
        alert("Error en el proceso: " . $e->getMessage(), 2, "subir_precios_masivo");
        logActivity('PRECIO_MASIVO', 'Error en carga masiva: ' . $e->getMessage());
    }
}

// Obtener lista de fleteros activos de la zona
$sqlTransportistas = "SELECT id_transp, razon_so, placas FROM transportes WHERE status = '1'";
if ($zona_actual != '0') {
    $sqlTransportistas .= " AND zona = " . intval($zona_actual);
}
$sqlTransportistas .= " ORDER BY razon_so";
$resTransportistas = $conn_mysql->query($sqlTransportistas);

?>

<div class="container mt-2">
    <div class="card mb-4">
        <div class="card-header encabezado-col text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Carga Masiva de Precios de Flete</h5>
                <span class="small">Zona actual: <?= $tipoZonaActual ?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="?p=transportes" class="btn btn-sm rounded-3 btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i> Regresar
                </a>
            </div>
        </div>
        <div class="card-body">
            
            <!-- Instrucciones -->
            <div class="alert alert-info">
                <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Instrucciones</h6>
                <ul class="mb-0">
                    <li>Seleccione uno o varios fleteros para aplicar los precios</li>
                    <li>Si un precio ya existe con las mismas condiciones (origen, destino, tipo), solo se actualizará la fecha de fin y el precio</li>
                    <li>Si no existe, se creará un nuevo registro</li>
                </ul>
            </div>

            <!-- Formulario de carga -->
                    <form method="post" action="">
                        <div class="row g-3">
                            
                            <!-- Selección de fleteros -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-truck me-2"></i>1. Seleccionar Fleteros</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="todos_fleteros" name="todos_fleteros" value="1" onchange="toggleFleteros(this, 'fleteros_container')">
                                            <label class="form-check-label fw-bold" for="todos_fleteros">
                                                Aplicar a TODOS los fleteros activos de la zona
                                            </label>
                                        </div>
                                        
                                        <div id="fleteros_container">
                                            <label class="form-label">O seleccione fleteros específicos:</label>
                                            <div class="row">
                                                <?php while ($transp = mysqli_fetch_assoc($resTransportistas)): ?>
                                                    <div class="col-md-4 col-sm-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input fletero-check" type="checkbox" name="fleteros[]" 
                                                                value="<?= $transp['id_transp'] ?>" id="fletero_<?= $transp['id_transp'] ?>">
                                                            <label class="form-check-label" for="fletero_<?= $transp['id_transp'] ?>">
                                                                <small><?= htmlspecialchars($transp['placas']) ?> - <?= htmlspecialchars($transp['razon_so']) ?></small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos del precio -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>2. Datos del Precio</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            
                                            <?php if ($tipoZonaActual == 'MEO'): ?>
                                                <!-- Campos específicos para MEO -->
                                                <div class="col-lg-6">
                                                    <label for="cap_ven" class="form-label">Tipo de Movimiento *</label>
                                                    <select class="form-select" name="cap_ven" id="cap_ven" required 
                                                        onchange="cambiarOrigenDestinoMEOMasivo(this.value)">
                                                        <option value="">Seleccione...</option>
                                                        <option value="CAP">Captación/Compra</option>
                                                        <option value="VEN">Venta/Salida</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <label for="tipo" class="form-label">Tipo de Precio *</label>
                                                    <select class="form-select" name="tipo" id="tipo" required>
                                                        <option value="MFV">Por viaje (MEO)</option>
                                                        <option value="MFT">Por tonelada (MEO)</option>
                                                    </select>
                                                </div>
                                            <?php else: ?>
                                                <!-- Campos para zona NOR -->
                                                <div class="col-lg-6">
                                                    <label for="tipo" class="form-label">Tipo *</label>
                                                    <select class="form-select" name="tipo" id="tipo" required>
                                                        <option value="FT">Por tonelada</option>
                                                        <option value="FV">Por viaje</option>
                                                    </select>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="col-lg-6">
                                                <label for="precio" class="form-label">Precio $ *</label>
                                                <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" required>
                                            </div>
                                            
                                            <div class="col-lg-6">
                                                <label for="origen" class="form-label">Origen *</label>
                                                <select class="form-select select2" name="origen" id="origen" required>
                                                    <option value="">Seleccione origen...</option>
                                                    <?php
                                                    $sqlOrigen = "SELECT d.id_direc, d.cod_al, d.noma
                                                                  FROM direcciones d";
                                                    
                                                    if ($tipoZonaActual == 'MEO') {
                                                        // Para MEO inicial, mostrar todo o esperar selección
                                                        $sqlOrigen .= " WHERE d.status = '1'";
                                                    } else {
                                                        // Para NOR, mostrar proveedores
                                                        $sqlOrigen .= " INNER JOIN proveedores p ON d.id_prov = p.id_prov
                                                                       WHERE d.status = '1' AND p.status = '1'";
                                                        if ($zona_actual != '0') {
                                                            $sqlOrigen .= " AND p.zona = " . intval($zona_actual);
                                                        }
                                                    }
                                                    
                                                    $sqlOrigen .= " ORDER BY d.cod_al";
                                                    $resOrigen = $conn_mysql->query($sqlOrigen);
                                                    
                                                    while ($ori = mysqli_fetch_assoc($resOrigen)) {
                                                        echo '<option value="'.$ori['id_direc'].'">'.$ori['cod_al'].' ('.$ori['noma'].')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-lg-6">
                                                <label for="destino" class="form-label">Destino *</label>
                                                <select class="form-select select2" name="destino" id="destino" required>
                                                    <option value="">Seleccione destino...</option>
                                                    <?php
                                                    $sqlDestino = "SELECT d.id_direc, d.cod_al, d.noma
                                                                   FROM direcciones d";
                                                    
                                                    if ($tipoZonaActual == 'MEO') {
                                                        // Para MEO inicial, mostrar todo o esperar selección
                                                        $sqlDestino .= " WHERE d.status = '1'";
                                                    } else {
                                                        // Para NOR, mostrar clientes
                                                        $sqlDestino .= " INNER JOIN clientes c ON d.id_us = c.id_cli
                                                                        WHERE d.status = '1' AND c.status = '1'";
                                                        if ($zona_actual != '0') {
                                                            $sqlDestino .= " AND c.zona = " . intval($zona_actual);
                                                        }
                                                    }
                                                    
                                                    $sqlDestino .= " ORDER BY d.cod_al";
                                                    $resDestino = $conn_mysql->query($sqlDestino);
                                                    
                                                    while ($des = mysqli_fetch_assoc($resDestino)) {
                                                        echo '<option value="'.$des['id_direc'].'">'.$des['cod_al'].' ('.$des['noma'].')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <label for="conmin" class="form-label">Peso mínimo (ton)</label>
                                                <input type="number" step="0.01" min="0" name="conmin" value="0" id="conmin" class="form-control">
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <label for="fechaini" class="form-label">Fecha de Inicio *</label>
                                                <input type="date" value="<?= date('Y-m-d') ?>" name="fechaini" id="fechaini" class="form-control" required>
                                            </div>
                                            
                                            <div class="col-lg-4">
                                                <label for="fechafin" class="form-label">Fecha Final *</label>
                                                <input type="date" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" name="fechafin" id="fechafin" class="form-control" required>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón de envío -->
                            <div class="col-12">
                                <button type="submit" name="procesar_carga_masiva" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-check-circle me-2"></i>Procesar Carga Masiva
                                </button>
                            </div>
                            
                        </div>
                    </form>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar Select2 en los selectores
    $('.select2').select2({
        placeholder: "Buscar...",
        allowClear: true,
        language: "es",
        width: '100%'
    });
});

// Función para toggle de selección de todos los fleteros
function toggleFleteros(checkbox, containerId) {
    const container = document.getElementById(containerId);
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    
    if (checkbox.checked) {
        // Desactivar checkboxes individuales
        checkboxes.forEach(cb => {
            cb.checked = false;
            cb.disabled = true;
        });
        container.style.opacity = '0.5';
    } else {
        // Reactivar checkboxes individuales
        checkboxes.forEach(cb => {
            cb.disabled = false;
        });
        container.style.opacity = '1';
    }
}

// Función para cambiar origen/destino en MEO (solo para carga manual)
<?php if ($tipoZonaActual == 'MEO'): ?>
function cambiarOrigenDestinoMEOMasivo(tipo) {
    const origenSelect = document.getElementById('origen');
    const destinoSelect = document.getElementById('destino');
    
    // Limpiar opciones
    origenSelect.innerHTML = '<option value="">Cargando...</option>';
    destinoSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (tipo === 'CAP') {
        // CAP: Proveedores → Almacenes
        origenSelect.innerHTML = `
            <option value="">Seleccione proveedor...</option>
            <?php
            $provMEO = $conn_mysql->query("
                SELECT d.id_direc, d.cod_al, d.noma 
                FROM direcciones d
                INNER JOIN proveedores p ON d.id_prov = p.id_prov
                WHERE d.status = '1' AND p.status = '1'
                " . ($zona_actual != '0' ? " AND p.zona = " . intval($zona_actual) : "") . "
                ORDER BY d.cod_al
            ");
            while ($p = mysqli_fetch_assoc($provMEO)) {
                echo "<option value='{$p['id_direc']}'>{$p['cod_al']} ({$p['noma']})</option>";
            }
            ?>
        `;
        
        destinoSelect.innerHTML = `
            <option value="">Seleccione almacén...</option>
            <?php
            $almMEO = $conn_mysql->query("
                SELECT d.id_direc, d.cod_al, d.noma 
                FROM direcciones d
                INNER JOIN almacenes a ON d.id_alma = a.id_alma
                WHERE d.status = '1' AND a.status = '1'
                " . ($zona_actual != '0' ? " AND a.zona = " . intval($zona_actual) : "") . "
                ORDER BY d.cod_al
            ");
            while ($a = mysqli_fetch_assoc($almMEO)) {
                echo "<option value='{$a['id_direc']}'>{$a['cod_al']} ({$a['noma']})</option>";
            }
            ?>
        `;
    } else if (tipo === 'VEN') {
        // VEN: Almacenes → Clientes
        origenSelect.innerHTML = `
            <option value="">Seleccione almacén...</option>
            <?php
            $almMEO2 = $conn_mysql->query("
                SELECT d.id_direc, d.cod_al, d.noma 
                FROM direcciones d
                INNER JOIN almacenes a ON d.id_alma = a.id_alma
                WHERE d.status = '1' AND a.status = '1'
                " . ($zona_actual != '0' ? " AND a.zona = " . intval($zona_actual) : "") . "
                ORDER BY d.cod_al
            ");
            while ($a = mysqli_fetch_assoc($almMEO2)) {
                echo "<option value='{$a['id_direc']}'>{$a['cod_al']} ({$a['noma']})</option>";
            }
            ?>
        `;
        
        destinoSelect.innerHTML = `
            <option value="">Seleccione cliente...</option>
            <?php
            $cliMEO = $conn_mysql->query("
                SELECT d.id_direc, d.cod_al, d.noma 
                FROM direcciones d
                INNER JOIN clientes c ON d.id_us = c.id_cli
                WHERE d.status = '1' AND c.status = '1'
                " . ($zona_actual != '0' ? " AND c.zona = " . intval($zona_actual) : "") . "
                ORDER BY d.cod_al
            ");
            while ($c = mysqli_fetch_assoc($cliMEO)) {
                echo "<option value='{$c['id_direc']}'>{$c['cod_al']} ({$c['noma']})</option>";
            }
            ?>
        `;
    }
    
    // Re-inicializar select2
    $('#origen').select2('destroy').select2({
        placeholder: "Buscar...",
        allowClear: true,
        language: "es",
        width: '100%'
    });
    
    $('#destino').select2('destroy').select2({
        placeholder: "Buscar...",
        allowClear: true,
        language: "es",
        width: '100%'
    });
}
<?php endif; ?>
</script>
