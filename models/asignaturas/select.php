<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$conn = conectar();

$soloCarrera = ($usuario['perfil'] === 'COORDINADOR');
$carreraUsuario = (int) $usuario['carrera_id'];

// Listado de asignaturas según el perfil.
if ($soloCarrera) {
    $stmt = $conn->prepare(
        "SELECT a.id, a.nombre, a.nivel, a.carrera_id,
                c.nombre AS carrera
         FROM asignatura a
         JOIN carrera c ON c.id = a.carrera_id
         WHERE a.carrera_id = ?
         ORDER BY a.nivel, a.nombre"
    );

    $stmt->bind_param("i", $carreraUsuario);
} else {
    $stmt = $conn->prepare(
        "SELECT a.id, a.nombre, a.nivel, a.carrera_id,
                c.nombre AS carrera
         FROM asignatura a
         JOIN carrera c ON c.id = a.carrera_id
         ORDER BY c.nombre, a.nivel, a.nombre"
    );
}

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar las asignaturas"
    ));
    $conn->close();
    exit;
}

$res = $stmt->get_result();
$asignaturas = array();

while ($fila = $res->fetch_assoc()) {
    $asignaturas[] = $fila;
}

// Carreras disponibles para el formulario.
if ($soloCarrera) {
    $stmt2 = $conn->prepare(
        "SELECT id, nombre AS texto
         FROM carrera
         WHERE id = ?"
    );

    $stmt2->bind_param("i", $carreraUsuario);
} else {
    $stmt2 = $conn->prepare(
        "SELECT id, nombre AS texto
         FROM carrera
         ORDER BY nombre"
    );
}

if (!$stmt2->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar las carreras"
    ));
    $conn->close();
    exit;
}

$res2 = $stmt2->get_result();
$carreras = array();

while ($fila = $res2->fetch_assoc()) {
    $carreras[] = $fila;
}

echo json_encode(array(
    "success" => true,
    "asignaturas" => $asignaturas,
    "carreras" => $carreras
));

$conn->close();
