<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));

$conn = conectar();

$usuarioId = (int) $usuario['id'];

// Identificar al estudiante que inició sesión.
$stmt = $conn->prepare(
    "SELECT e.cedula, e.nombre, e.apellido, e.nivel,
            c.nombre AS carrera
     FROM estudiante e
     JOIN carrera c ON c.id = e.carrera_id
     WHERE e.usuario_id = ?"
);

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

// Obtener el periodo activo.
$periodo = periodoActivo($conn);

// Si no existe un periodo activo, devolver la lista vacía.
if (!$periodo) {
    echo json_encode(array(
        "success" => true,
        "estudiante" => $estudiante,
        "periodo" => null,
        "notas" => array(),
        "mensaje" => "No existe un periodo activo"
    ));

    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];
$cedula = $estudiante['cedula'];

// Consultar las matrículas, asistencias y notas.
$stmt = $conn->prepare(
    "SELECT a.nombre AS asignatura,
            g.paralelo,
            COUNT(asi.id) AS asistencias,
            m.nota
     FROM matricula m
     JOIN grupo g ON g.id = m.grupo_id
     JOIN asignatura a ON a.id = g.asignatura_id
     LEFT JOIN horario h ON h.grupo_id = g.id
     LEFT JOIN asistencia asi
         ON asi.horario_id = h.id
         AND asi.estudiante_cedula = m.estudiante_cedula
     WHERE m.estudiante_cedula = ?
       AND g.periodo_id = ?
     GROUP BY g.id, a.nombre, g.paralelo, m.nota, a.nivel
     ORDER BY a.nivel, a.nombre"
);

$stmt->bind_param("si", $cedula, $periodoId);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar las notas y asistencias"
    ));
    $conn->close();
    exit;
}

$res = $stmt->get_result();

$notas = array();

while ($fila = $res->fetch_assoc()) {
    $notas[] = $fila;
}

echo json_encode(array(
    "success" => true,
    "estudiante" => $estudiante,
    "periodo" => $periodo['nombre'],
    "notas" => $notas
));

$conn->close();
