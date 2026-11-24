<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$nombre = isset($_POST['nombre']) && is_string($_POST['nombre'])
    ? trim($_POST['nombre']) : '';

$nivel = filter_input(INPUT_POST, 'nivel', FILTER_VALIDATE_INT);
$carrera = filter_input(INPUT_POST, 'carrera', FILTER_VALIDATE_INT);

// Validar los datos recibidos.
if (!$id || $id <= 0) {
    echo json_encode(array(
        "error" => "Asignatura inválida"
    ));
    exit;
}

if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 80) {
    echo json_encode(array(
        "error" => "Ingrese el nombre (hasta 80 caracteres)"
    ));
    exit;
}

if (!$nivel || $nivel < 1 || $nivel > 10) {
    echo json_encode(array(
        "error" => "El nivel debe estar entre 1 y 10"
    ));
    exit;
}

if (!$carrera || $carrera <= 0) {
    echo json_encode(array(
        "error" => "Seleccione la carrera"
    ));
    exit;
}

$conn = conectar();

// Comprobar que la asignatura exista.
$stmt = $conn->prepare(
    "SELECT carrera_id
     FROM asignatura
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudo consultar la asignatura"
    ));
    $conn->close();
    exit;
}

$actual = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$actual) {
    echo json_encode(array(
        "error" => "La asignatura no existe"
    ));
    $conn->close();
    exit;
}

// El coordinador solamente puede modificar su carrera.
if ($usuario['perfil'] === 'COORDINADOR' &&
    ((int) $actual['carrera_id'] != (int) $usuario['carrera_id'] ||
    $carrera != (int) $usuario['carrera_id'])) {

    echo json_encode(array(
        "error" => "Solo puede modificar asignaturas de su carrera"
    ));
    $conn->close();
    exit;
}

// Evitar inconsistencias con el registro académico.
if ($carrera != (int) $actual['carrera_id']) {
    echo json_encode(array(
        "error" => "No se puede cambiar la carrera de una asignatura existente"
    ));
    $conn->close();
    exit;
}

// Modificar la asignatura.
$stmt = $conn->prepare(
    "UPDATE asignatura
     SET nombre = ?, nivel = ?
     WHERE id = ?"
);

$stmt->bind_param("sii", $nombre, $nivel, $id);

if ($stmt->execute()) {

    echo json_encode(array(
        "success" => true
    ));

} elseif ($stmt->errno == 1062) {

    echo json_encode(array(
        "error" => "Ya existe una asignatura con ese nombre en la carrera"
    ));

} else {

    echo json_encode(array(
        "error" => "No se pudo modificar la asignatura"
    ));
}

$conn->close();
