<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));

$conn = conectar();

$usuarioId = (int) $usuario['id'];

// Identificar al estudiante mediante la sesión.
$stmt = $conn->prepare(
    "SELECT cedula
     FROM estudiante
     WHERE usuario_id = ?"
);

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta del estudiante"
    ));
    $conn->close();
    exit;
}

$stmt->bind_param("i", $usuarioId);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudo consultar al estudiante"
    ));
    $conn->close();
    exit;
}

$estudiante = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$estudiante) {
    echo json_encode(array(
        "error" => "No se encontró el estudiante asociado al usuario"
    ));
    $conn->close();
    exit;
}

$cedula = $estudiante['cedula'];

// Consultar el récord académico del estudiante.
$stmt = $conn->prepare(
    "SELECT a.nivel,
            a.nombre AS asignatura,
            r.estado
     FROM registro_academico r
     JOIN asignatura a ON a.id = r.asignatura_id
     WHERE r.estudiante_cedula = ?
     ORDER BY a.nivel, a.nombre"
);

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta académica"
    ));
    $conn->close();
    exit;
}

$stmt->bind_param("s", $cedula);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudo consultar el récord académico"
    ));
    $conn->close();
    exit;
}

$res = $stmt->get_result();

$record = array();

while ($fila = $res->fetch_assoc()) {
    $record[] = $fila;
}

echo json_encode(array(
    "success" => true,
    "record" => $record
));

$stmt->close();
$conn->close();
