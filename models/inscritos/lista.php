<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR', 'DOCENTE'));

$grupo = filter_input(INPUT_GET, 'grupo', FILTER_VALIDATE_INT);

// Validar el grupo seleccionado.
if (!$grupo || $grupo <= 0) {
    echo json_encode(array(
        "error" => "Seleccione un grupo válido"
    ));
    exit;
}

$conn = conectar();

// Obtener el periodo activo.
$periodo = periodoActivo($conn);

if (!$periodo) {
    echo json_encode(array(
        "error" => "No existe un periodo activo"
    ));

    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];

// Comprobar que el grupo exista en el periodo activo.
$stmt = $conn->prepare(
    "SELECT g.id, g.paralelo, g.docente_id,
            a.nombre AS asignatura, a.carrera_id,
            c.nombre AS carrera
     FROM grupo g
     JOIN asignatura a ON a.id = g.asignatura_id
     JOIN carrera c ON c.id = a.carrera_id
     WHERE g.id = ?
       AND g.periodo_id = ?"
);

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta del grupo"
    ));

    $conn->close();
    exit;
}

$stmt->bind_param("ii", $grupo, $periodoId);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudo consultar el grupo"
    ));

    $conn->close();
    exit;
}

$datosGrupo = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$datosGrupo) {
    echo json_encode(array(
        "error" => "El grupo no existe en el periodo activo"
    ));

    $conn->close();
    exit;
}

// El coordinador solo puede consultar grupos de su carrera.
if ($usuario['perfil'] === 'COORDINADOR' &&
    (int) $datosGrupo['carrera_id'] !=
    (int) $usuario['carrera_id']) {

    echo json_encode(array(
        "error" => "Solo puede consultar grupos de su carrera"
    ));

    $conn->close();
    exit;
}

// El docente solo puede consultar sus propios grupos.
if ($usuario['perfil'] === 'DOCENTE' &&
    (int) $datosGrupo['docente_id'] !=
    (int) $usuario['id']) {

    echo json_encode(array(
        "error" => "Solo puede consultar sus propios grupos"
    ));

    $conn->close();
    exit;
}

// Consultar los estudiantes matriculados.
$stmt = $conn->prepare(
    "SELECT e.cedula,
            e.apellido,
            e.nombre
     FROM matricula m
     JOIN estudiante e
         ON e.cedula = m.estudiante_cedula
     WHERE m.grupo_id = ?
     ORDER BY e.apellido, e.nombre"
);

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta de inscritos"
    ));

    $conn->close();
    exit;
}

$stmt->bind_param("i", $grupo);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar los estudiantes inscritos"
    ));

    $conn->close();
    exit;
}

$res = $stmt->get_result();

$estudiantes = array();

while ($fila = $res->fetch_assoc()) {
    $estudiantes[] = $fila;
}

// Devolver la lista y el total de estudiantes.
echo json_encode(array(
    "success" => true,
    "periodo" => $periodo['nombre'],
    "asignatura" => $datosGrupo['asignatura'],
    "paralelo" => $datosGrupo['paralelo'],
    "carrera" => $datosGrupo['carrera'],
    "estudiantes" => $estudiantes,
    "total" => count($estudiantes)
));

$stmt->close();
$conn->close();
