<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('DOCENTE', 'ESTUDIANTE'));

$conn = conectar();

$usuarioId = (int) $usuario['id'];

// Obtener el periodo activo.
$periodo = periodoActivo($conn);

if (!$periodo) {
    echo json_encode(array(
        "success" => true,
        "usuario" => $usuario['nombre'],
        "perfil" => $usuario['perfil'],
        "periodo" => null,
        "horarios" => array(),
        "mensaje" => "No existe un periodo activo"
    ));

    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];

// Consultar los horarios según el perfil.
if ($usuario['perfil'] === 'DOCENTE') {

    // El docente solamente consulta sus grupos.
    $stmt = $conn->prepare(
        "SELECT h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                a.nombre AS asignatura,
                g.paralelo,
                h.aula
         FROM horario h
         JOIN grupo g ON g.id = h.grupo_id
         JOIN asignatura a ON a.id = g.asignatura_id
         WHERE g.docente_id = ?
           AND g.periodo_id = ?
         ORDER BY h.dia_semana, h.hora_inicio, a.nombre"
    );

} else {

    // El estudiante solamente consulta sus matrículas.
    $stmt = $conn->prepare(
        "SELECT h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                a.nombre AS asignatura,
                g.paralelo,
                h.aula
         FROM estudiante e
         JOIN matricula m
             ON m.estudiante_cedula = e.cedula
         JOIN grupo g
             ON g.id = m.grupo_id
         JOIN asignatura a
             ON a.id = g.asignatura_id
         JOIN horario h
             ON h.grupo_id = g.id
         WHERE e.usuario_id = ?
           AND g.periodo_id = ?
         ORDER BY h.dia_semana, h.hora_inicio, a.nombre"
    );
}

if (!$stmt) {
    echo json_encode(array(
        "error" => "No se pudo preparar la consulta de horarios"
    ));

    $conn->close();
    exit;
}

$stmt->bind_param("ii", $usuarioId, $periodoId);

if (!$stmt->execute()) {
    echo json_encode(array(
        "error" => "No se pudieron consultar los horarios"
    ));

    $stmt->close();
    $conn->close();
    exit;
}

$res = $stmt->get_result();

$horarios = array();

$dias = array(
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
);

// Preparar los datos para la vista.
while ($fila = $res->fetch_assoc()) {

    $dia = (int) $fila['dia_semana'];

    $fila['dia'] = isset($dias[$dia])
        ? $dias[$dia]
        : 'Desconocido';

    $fila['hora_inicio'] = substr(
        $fila['hora_inicio'], 0, 5
    );

    $fila['hora_fin'] = substr(
        $fila['hora_fin'], 0, 5
    );

    $horarios[] = $fila;
}

echo json_encode(array(
    "success" => true,
    "usuario" => $usuario['nombre'],
    "perfil" => $usuario['perfil'],
    "periodo" => $periodo['nombre'],
    "horarios" => $horarios,
    "total" => count($horarios)
));

$stmt->close();
$conn->close();
