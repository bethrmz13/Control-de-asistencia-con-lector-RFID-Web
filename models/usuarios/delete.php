<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array("error" => "Datos inválidos"));
    exit;
}
if ($id === (int) $usuario['id']) {
    echo json_encode(array("error" => "No puede eliminar su propio usuario"));
    exit;
}

$conn = conectar();

$stmt = $conn->prepare("SELECT perfil, activo FROM usuario WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El usuario no existe"));
    exit;
}

// Debe quedar al menos un ADMIN activo
if ($actual['perfil'] === 'ADMIN' && (int) $actual['activo'] === 1) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE perfil = 'ADMIN' AND activo = 1 AND id <> ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res->fetch_assoc();
    if ((int) $fila['total'] < 1) {
        echo json_encode(array("error" => "Debe quedar al menos un ADMIN activo"));
        exit;
    }
}

// Un usuario vinculado a un estudiante se elimina desde la página Estudiantes
$stmt = $conn->prepare("SELECT cedula FROM estudiante WHERE usuario_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->fetch_assoc()) {
    echo json_encode(array("error" => "Este usuario pertenece a un estudiante: elimínelo desde la página Estudiantes"));
    exit;
}

$stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("error" => "No se pudo eliminar el usuario"));
}
$conn->close();
