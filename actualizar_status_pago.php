<?php
session_start();
require_once 'config/conexiones.php';

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$accion = trim($_POST['accion'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

if ($id <= 0 || !in_array($accion, ['activar', 'desactivar'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

if ($accion === 'desactivar' && $motivo === '') {
    echo json_encode(['success' => false, 'message' => 'Debe indicar un motivo de cancelación']);
    exit;
}

$targetStatus = $accion === 'activar' ? 1 : 0;

if ($accion === 'desactivar') {
    $stmt = $conn_mysql->prepare("UPDATE pagos SET status = ?, observaciones = ?, updated_at = NOW() WHERE id_pago = ?");
} else {
    $stmt = $conn_mysql->prepare("UPDATE pagos SET status = ?, updated_at = NOW() WHERE id_pago = ?");
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la actualización']);
    exit;
}

$motivoGuardado = $motivo;
if ($accion === 'desactivar') {
    $stmt->bind_param('isi', $targetStatus, $motivoGuardado, $id);
} else {
    $stmt->bind_param('ii', $targetStatus, $id);
}

if ($stmt->execute()) {
    $descripcion = strtoupper($accion) . ' pago ' . $id;
    if ($accion === 'desactivar') {
        $descripcion .= ' | Motivo: ' . $motivoGuardado;
    }
    logActivity('PAGO_STATUS', $descripcion);
    echo json_encode(['success' => true, 'message' => 'Estatus actualizado']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estatus']);
