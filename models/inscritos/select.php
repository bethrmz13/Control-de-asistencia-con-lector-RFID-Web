<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR', 'DOCENTE'));

$conn = conectar();

// Obtener el periodo activo.
$periodo = periodoActivo($conn);

if (!$periodo) {
    echo json_encode(array(
        "success" => true,
        "periodo" => null,
        "asignaturas" => array(),
        "grupos" => array(),
        "mensaje" => "No existe un periodo activo"
    ));

    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];
$usuarioId = (int) $usuario['id'];
$carreraUsuario = (int) $usuario['carrera_id'];

// Consultar los grupos permitidos según el perfil.
if ($usuario['perfil'] === 'COORDINADOR') {

    $stmt = $conn->prepare(
        "SELECT g.id, g.asignatura_id, g.paralelo,
                a.nombre AS asignatura, a.nivel, a.carrera_id,
                c.nombre AS carrera
         FROM grupo g
         JOIN asignatura a ON a.id = g.asignatura_id
         JOIN carrera c ON c.id = a.carrera_id
         WHERE g.periodo_id = ?
           AND a.carrera_id = ?
         ORDER BY c.nombre, a.nivel, a.nombre, g.paralelo"
    );

    if ($stmt) {
        $stmt->bind_param("ii", $periodoId, $carreraUsuario);
    }

} elseif ($usuario['perfil'] === 'DOCENTE') {

    $stmt = $conn->prepare(
        "SELECT g.id, g.asignatura_id, g.paralelo,
                a.nombre AS asignatura, a.nivel, a.carrera_id,
                c.nombre AS carrera
         FROM grupo g
         JOIN asignatura a ON a.id = g.asignatura_id
         JOIN carrera c ON c.id = a.carrera_id
         WHERE g.periodo_id = ?
           AND g.docente_id = ?
         ORDER BY c.nombre, a.nivel, a.nombre, g.paralelo"
    );

    if ($stmt) {
        $stmt->bind_param("ii", $periodoId, $usuarioId);
    }

} else {

    // El administrador puede consultar todos los grupos.
    $stmt = $conn->prepare(
        "SELECT g.id, g.asignatura_id, g.paralelo,
                a.nombre AS asignatura, a.nivel, a.carrera_id,
                c.nombre AS carrera
         FROM grupo g
         JOIN asignatura a ON a.id = g.asignatura_id
         JOIN carrera c ON c.id = a.carrera_id
         WHERE g.periodo_id = ?
         ORDER BY c.nombre, a.nivel, a.nombre, g.paralelo"
    );

    if ($stmt) {
        $stmt->bind_param("i", $periodoId);
    }
}

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta de grupos"
    ));

    $conn->close();
    exit;
}

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar los grupos"
    ));

    $stmt->close();
    $conn->close();
    exit;
}

$res = $stmt->get_result();

$asignaturas = array();
$grupos = array();
$registradas = array();

// Preparar los datos de los dos combos.
while ($fila = $res->fetch_assoc()) {

    $idAsignatura = (int) $fila['asignatura_id'];

    // Evitar repetir asignaturas con varios paralelos.
    if (!isset($registradas[$idAsignatura])) {

        $asignaturas[] = array(
            "id" => $idAsignatura,
            "texto" => $fila['asignatura'] .
                       ' - Nivel ' . $fila['nivel'] .
                       ' - ' . $fila['carrera']
        );

        $registradas[$idAsignatura] = true;
    }

    $grupos[] = array(
        "id" => (int) $fila['id'],
        "asignatura_id" => $idAsignatura,
        "asignatura" => $fila['asignatura'],
        "carrera_id" => (int) $fila['carrera_id'],
        "carrera" => $fila['carrera'],
        "nivel" => (int) $fila['nivel'],
        "paralelo" => $fila['paralelo'],
        "texto" => 'Paralelo ' . $fila['paralelo']
    );
}

echo json_encode(array(
    "success" => true,
    "periodo" => $periodo['nombre'],
    "asignaturas" => $asignaturas,
    "grupos" => $grupos
));

$stmt->close();
$conn->close();
