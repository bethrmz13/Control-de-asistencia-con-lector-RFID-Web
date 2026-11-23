<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/validar.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array("error" => "El horario seleccionado no es válido"));
    exit;
}

$conn = conectar();

$stmt = $conn->prepare("SELECT grupo_id FROM horario WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El horario no existe"));
    exit;
}
if (grupoDelUsuario($conn, $usuario, (int) $actual['grupo_id']) === null) {
    echo json_encode(array("error" => "Ese horario no es del periodo activo o no es de su carrera"));
    exit;
}

$stmt = $conn->prepare("DELETE FROM horario WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("error" => "No se pudo eliminar el horario"));
}
$conn->close();