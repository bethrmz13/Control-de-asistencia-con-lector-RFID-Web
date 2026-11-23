<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array('error' => 'Seleccione un periodo válido'));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare('SELECT id FROM periodo WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(array('error' => 'El periodo no existe'));
    $conn->close();
    exit;
}

// Esta única actualización activa el periodo elegido y desactiva los demás.
$stmt = $conn->prepare('UPDATE periodo SET activo = IF(id = ?, 1, 0)');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('error' => 'No se pudo activar el periodo'));
}
$conn->close();