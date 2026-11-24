<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Validar el identificador.
if (!$id || $id <= 0) {
    echo json_encode(array(
        "error" => "Datos inválidos"
    ));
    exit;
}

$conn = conectar();


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


if ($usuario['perfil'] === 'COORDINADOR' &&
    (int) $actual['carrera_id'] != (int) $usuario['carrera_id']) {

    echo json_encode(array(
        "error" => "Solo puede eliminar asignaturas de su carrera"
    ));
    $conn->close();
    exit;
}


$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM matricula m
     JOIN grupo g ON g.id = m.grupo_id
     WHERE g.asignatura_id = ?"
);

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron comprobar las matrículas"
    ));
    $conn->close();
    exit;
}

$resultado = $stmt->get_result()->fetch_assoc();

$stmt->close();


if ((int) $resultado['total'] > 0) {
    echo json_encode(array(
        "error" => "No se puede eliminar la asignatura porque tiene estudiantes matriculados"
    ));
    $conn->close();
    exit;
}


$stmt = $conn->prepare(
    "DELETE FROM asignatura
     WHERE id = ?
     AND NOT EXISTS (
         SELECT 1
         FROM grupo g
         JOIN matricula m ON m.grupo_id = g.id
         WHERE g.asignatura_id = asignatura.id
     )"
);

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(array(
        "success" => true
    ));
} else {
    echo json_encode(array(
        "error" => "No se pudo eliminar la asignatura. Verifique que no tenga estudiantes matriculados"
    ));
}

$conn->close();
