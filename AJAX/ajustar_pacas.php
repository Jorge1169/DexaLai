<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexiones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_inventario = isset($_POST['id_inventario']) ? intval($_POST['id_inventario']) : 0;
$nueva_pacas = isset($_POST['nueva_pacas']) ? intval($_POST['nueva_pacas']) : null;
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;

if ($id_inventario <= 0 || $nueva_pacas === null || $nueva_pacas < 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

try {
    $conn_mysql->begin_transaction();

    // Obtener datos actuales del inventario
    $sql_info = "SELECT pacas_cantidad_disponible, pacas_kilos_disponible, granel_kilos_disponible, id_bodega, id_prod FROM inventario_bodega WHERE id_inventario = ? FOR UPDATE";
    $stmt_info = $conn_mysql->prepare($sql_info);
    $stmt_info->bind_param('i', $id_inventario);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();

    if (!$info) {
        throw new Exception('Inventario no encontrado');
    }

    $pacas_actuales = intval($info['pacas_cantidad_disponible']);
    $pacas_kilos = floatval($info['pacas_kilos_disponible']);

    // Si la nueva cantidad es igual, no hay cambio
    if ($nueva_pacas === $pacas_actuales) {
        echo json_encode(['success' => true, 'message' => 'No hubo cambios en la cantidad de pacas']);
        $conn_mysql->commit();
        exit;
    }

    // Calcular diferencia (puede ser negativa si se reduce)
    $diff_pacas = $nueva_pacas - $pacas_actuales;

    // Registrar movimiento de ajuste: afectamos sólo la cantidad de pacas; kilos permanecen
    $sql_mov = "INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, granel_kilos_movimiento, pacas_cantidad_movimiento, pacas_kilos_movimiento, observaciones, id_user, created_at) VALUES (?, 'ajuste', 0, ?, 0, ?, ?, NOW())";
    $stmt_mov = $conn_mysql->prepare($sql_mov);
    $stmt_mov->bind_param('iisi', $id_inventario, $diff_pacas, $observaciones, $id_usuario);
    if (!$stmt_mov->execute()) {
        throw new Exception('Error al insertar movimiento: ' . $stmt_mov->error);
    }

    // Actualizar inventario_bodega
    $nuevo_pacas_cantidad = $pacas_actuales + $diff_pacas;
    $nuevo_pacas_cantidad = max(0, $nuevo_pacas_cantidad);

    $nuevo_peso_promedio = 0;
    if ($nuevo_pacas_cantidad > 0) {
        $nuevo_peso_promedio = $pacas_kilos / $nuevo_pacas_cantidad;
    }

    $sql_update = "UPDATE inventario_bodega SET pacas_cantidad_disponible = ?, pacas_peso_promedio = ?, updated_at = NOW() WHERE id_inventario = ?";
    $stmt_update = $conn_mysql->prepare($sql_update);
    $stmt_update->bind_param('idi', $nuevo_pacas_cantidad, $nuevo_peso_promedio, $id_inventario);
    if (!$stmt_update->execute()) {
        throw new Exception('Error al actualizar inventario: ' . $stmt_update->error);
    }

    $conn_mysql->commit();

    $mensaje = "Ajuste aplicado. Pacas: {$pacas_actuales} → {$nuevo_pacas_cantidad}. Peso total en pacas: " . number_format($pacas_kilos, 2) . " kg.";
    echo json_encode(['success' => true, 'message' => $mensaje]);
    exit;

} catch (Exception $e) {
    $conn_mysql->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

?>