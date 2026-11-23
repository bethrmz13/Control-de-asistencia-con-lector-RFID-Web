<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$estadoRecibido = isset($_POST['abierta']) ? (string) $_POST['abierta'] : '';
if ($id <= 0 || ($estadoRecibido !== '0' && $estadoRecibido !== '1')) {
    echo json_encode(array('error' => 'Seleccione un periodo y un estado de matrícula válidos'));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare('SELECT activo FROM periodo WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$periodo = $stmt->get_result()->fetch_assoc();
if (!$periodo) {
    echo json_encode(array('error' => 'El periodo no existe'));
    $conn->close();
    exit;
}
if ((int) $periodo['activo'] !== 1) {
    echo json_encode(array('error' => 'Solo se puede cambiar la matrícula del periodo activo'));
    $conn->close();
    exit;
}

$abierta = (int) $estadoRecibido;
$stmt = $conn->prepare('UPDATE periodo SET matricula_abierta = ? WHERE id = ? AND activo = 1');
$stmt->bind_param('ii', $abierta, $id);
if ($stmt->execute()) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('error' => 'No se pudo cambiar el estado de la matrícula'));
}
$conn->close();