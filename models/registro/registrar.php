<?php
// models/registro/registrar.php
// Recibe el codigo de la tarjeta y registra la asistencia. Devuelve un JSON con el estado.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'REGISTRO'));

// Arma y envia la respuesta (siempre con la misma forma) y termina
function responder($estado, $mensaje, $estudiante = null, $asignatura = '', $paralelo = '', $hora = '') {
    echo json_encode(array(
        'success' => true,
        'estado' => $estado,
        'mensaje' => $mensaje,
        'estudiante' => $estudiante,
        'asignatura' => $asignatura,
        'paralelo' => $paralelo,
        'hora' => $hora
    ));
    exit;
}

$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
if ($codigo === '') {
    echo json_encode(array('error' => 'No se recibio el codigo de la tarjeta'));
    exit;
}

// La fecha y hora simuladas SOLO las puede mandar el ADMIN. Para los demas se ignora
// aunque llegue el dato: se usa siempre la hora del servidor.
$simulada = '';
if ($usuario['perfil'] === 'ADMIN' && isset($_POST['simulada'])) {
    $simulada = trim($_POST['simulada']);
}
$ahora = ahora($simulada);
if ($ahora === false) {
    echo json_encode(array('error' => 'Fecha y hora simuladas invalidas. Use el formato 2026-11-09 16:10'));
    exit;
}
$fecha = $ahora->format('Y-m-d');
$hora = $ahora->format('H:i');
$horaCompleta = $ahora->format('H:i:s');
$dia = (int) $ahora->format('N');   // 1 = lunes ... 7 = domingo

$conn = conectar();

// 1. Buscar al estudiante por el codigo de la tarjeta (el codigo es texto)
$stmt = $conn->prepare("SELECT e.cedula, e.nombre, e.apellido, e.nivel, c.nombre AS carrera
                        FROM estudiante e
                        JOIN carrera c ON c.id = e.carrera_id
                        WHERE e.codigo_rfid = ?");
$stmt->bind_param('s', $codigo);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
if (!$est) {
    $conn->close();
    responder('NO_ENCONTRADO', 'Tarjeta no registrada', null, '', '', $hora);
}
$nombreCompleto = $est['nombre'] . ' ' . $est['apellido'];

// 2. Periodo activo: la fecha debe estar entre su inicio y su fin
$periodo = periodoActivo($conn);
if ($periodo === null || $fecha < $periodo['fecha_inicio'] || $fecha > $periodo['fecha_fin']) {
    $conn->close();
    responder('SIN_CLASES_HOY', $nombreCompleto . ' no tiene clases hoy (fuera del periodo)', $est, '', '', $hora);
}

// 3. Horarios de HOY de los grupos del periodo activo en los que esta matriculado
$periodoId = (int) $periodo['id'];
$stmt = $conn->prepare("SELECT h.id, h.hora_inicio, h.hora_fin, a.nombre AS asignatura, g.paralelo
                        FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        JOIN horario h ON h.grupo_id = g.id
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE m.estudiante_cedula = ? AND g.periodo_id = ? AND h.dia_semana = ?
                        ORDER BY h.hora_inicio");
$stmt->bind_param('sii', $est['cedula'], $periodoId, $dia);
$stmt->execute();
$res = $stmt->get_result();
$horarios = array();
while ($fila = $res->fetch_assoc()) {
    $horarios[] = $fila;
}
if (count($horarios) == 0) {
    $conn->close();
    responder('SIN_CLASES_HOY', $nombreCompleto . ' no tiene clases hoy', $est, '', '', $hora);
}

// 4. La asistencia vale durante toda la clase: inicio <= ahora < fin
$elegido = null;
foreach ($horarios as $h) {
    if ($h['hora_inicio'] <= $horaCompleta && $horaCompleta < $h['hora_fin']) {
        $elegido = $h;
        break;
    }
}
if ($elegido === null) {
    $conn->close();
    responder('FUERA_DE_HORARIO', 'Fuera del horario de clase', $est, '', '', $hora);
}
$horarioId = (int) $elegido['id'];
$materia = $elegido['asignatura'];
$paralelo = $elegido['paralelo'];

// 5. Si ya registro en esta clase hoy
$stmt = $conn->prepare("SELECT id FROM asistencia WHERE estudiante_cedula = ? AND horario_id = ? AND fecha = ?");
$stmt->bind_param('sis', $est['cedula'], $horarioId, $fecha);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $conn->close();
    responder('YA_REGISTRADO', 'Ya registro asistencia en ' . $materia, $est, $materia, $paralelo, $hora);
}

// 6. Guardar. Si falla (por ejemplo, otro puesto la guardo justo antes: error 1062) se vuelve a
// comprobar: si ya existe no es un error.
$stmt = $conn->prepare("INSERT INTO asistencia (estudiante_cedula, horario_id, fecha, hora) VALUES (?, ?, ?, ?)");
$stmt->bind_param('siss', $est['cedula'], $horarioId, $fecha, $horaCompleta);
if (!$stmt->execute()) {
    $stmt2 = $conn->prepare("SELECT id FROM asistencia WHERE estudiante_cedula = ? AND horario_id = ? AND fecha = ?");
    $stmt2->bind_param('sis', $est['cedula'], $horarioId, $fecha);
    $stmt2->execute();
    $existe = $stmt2->get_result()->fetch_assoc();
    $conn->close();
    if ($existe) {
        responder('YA_REGISTRADO', 'Ya registro asistencia en ' . $materia, $est, $materia, $paralelo, $hora);
    }
    responder('ERROR_GUARDADO', 'No se pudo guardar la asistencia. Intente de nuevo', $est, $materia, $paralelo, $hora);
}

// 7. Guardada
$conn->close();
responder('REGISTRADO', 'Asistencia registrada en ' . $materia . ' a las ' . $hora, $est, $materia, $paralelo, $hora);
