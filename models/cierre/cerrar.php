<?php
// models/cierre/cerrar.php
// Cierra el periodo activo. Sin transacciones: primero se calcula todo en arreglos de PHP, despues
// se aplican los cambios uno por uno revisando cada resultado, y si uno falla se devuelven los ya
// hechos a su valor anterior.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$conn = conectar();

// Error simple: no se cambio nada
function fallo($conn, $mensaje) {
    echo json_encode(array('error' => $mensaje));
    $conn->close();
    exit;
}

// Devuelve lo ya cambiado a su valor anterior (primero los niveles, luego los estados, al reves
// del orden en que se hicieron). Devuelve cuantos cambios NO se pudieron devolver.
function deshacer($conn, $estadosHechos, $nivelesHechos) {
    $noDevueltos = 0;
    for ($i = count($nivelesHechos) - 1; $i >= 0; $i--) {
        $c = $nivelesHechos[$i];
        $stmt = $conn->prepare('UPDATE estudiante SET nivel = ? WHERE cedula = ?');
        $stmt->bind_param('is', $c['antes'], $c['cedula']);
        if (!$stmt->execute()) {
            $noDevueltos++;
        }
    }
    for ($i = count($estadosHechos) - 1; $i >= 0; $i--) {
        $c = $estadosHechos[$i];
        $stmt = $conn->prepare('UPDATE registro_academico SET estado = ? WHERE estudiante_cedula = ? AND asignatura_id = ?');
        $stmt->bind_param('ssi', $c['antes'], $c['cedula'], $c['asignatura']);
        if (!$stmt->execute()) {
            $noDevueltos++;
        }
    }
    return $noDevueltos;
}

// Un paso fallo: se devuelven los cambios y se responde el error
function fallarCierre($conn, $motivo, $estadosHechos, $nivelesHechos) {
    $noDevueltos = deshacer($conn, $estadosHechos, $nivelesHechos);
    $mensaje = 'No se pudo cerrar el periodo: ' . $motivo . '.';
    if ($noDevueltos == 0) {
        $mensaje .= ' Se devolvieron los cambios y el periodo sigue activo. Intente de nuevo.';
    } else {
        $mensaje .= ' No se pudieron devolver ' . $noDevueltos . ' cambios: revise los niveles y los estados de las materias de los estudiantes antes de volver a intentar.';
    }
    fallo($conn, $mensaje);
}

// a) Debe haber un periodo activo y no pueden faltar notas
$periodo = periodoActivo($conn);
if ($periodo === null) {
    fallo($conn, 'No hay un periodo activo');
}
$periodoId = (int) $periodo['id'];

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        WHERE g.periodo_id = ? AND m.nota IS NULL');
$stmt->bind_param('i', $periodoId);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
if ((int) $fila['total'] > 0) {
    fallo($conn, 'Faltan ' . $fila['total'] . ' notas. No se cambio nada');
}

// CALCULO (todavia no se cambia nada en la base)
$stmt = $conn->prepare('SELECT m.estudiante_cedula, g.asignatura_id, m.nota, e.nombre, e.apellido, e.nivel, e.carrera_id
                        FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        JOIN estudiante e ON e.cedula = m.estudiante_cedula
                        WHERE g.periodo_id = ?
                        ORDER BY m.estudiante_cedula');
$stmt->bind_param('i', $periodoId);
$stmt->execute();
$res = $stmt->get_result();
$matriculas = array();
while ($fila = $res->fetch_assoc()) {
    $matriculas[] = $fila;
}
if (count($matriculas) == 0) {
    fallo($conn, 'El periodo no tiene matriculas');
}

$cambiosEstado = array();   // cedula, asignatura, nuevo estado, estado anterior
$estudiantes = array();     // cedula => datos del estudiante y los estados que tendra su registro
foreach ($matriculas as $m) {
    $cedula = $m['estudiante_cedula'];
    $asignatura = (int) $m['asignatura_id'];
    $nombre = $m['nombre'] . ' ' . $m['apellido'];

    // nota >= 7 aprueba; menos, reprueba
    $nuevo = ((float) $m['nota'] >= 7) ? 'APROBADA' : 'REPROBADA';

    // Estado anterior de esa materia en el registro academico (para poder devolverlo)
    $stmt = $conn->prepare('SELECT estado FROM registro_academico WHERE estudiante_cedula = ? AND asignatura_id = ?');
    $stmt->bind_param('si', $cedula, $asignatura);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    if (!$registro) {
        fallo($conn, 'No se pudo cerrar el periodo: ' . $nombre . ' tiene una materia que no esta en su registro academico. No se cambio nada');
    }
    $cambiosEstado[] = array('cedula' => $cedula, 'asignatura' => $asignatura, 'nuevo' => $nuevo, 'antes' => $registro['estado']);

    // La primera vez que aparece el estudiante se carga todo su registro academico
    if (!isset($estudiantes[$cedula])) {
        $stmt = $conn->prepare('SELECT asignatura_id, estado FROM registro_academico WHERE estudiante_cedula = ?');
        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $res = $stmt->get_result();
        $estados = array();
        while ($r = $res->fetch_assoc()) {
            $estados[(int) $r['asignatura_id']] = $r['estado'];
        }
        $estudiantes[$cedula] = array(
            'nombre' => $m['nombre'],
            'apellido' => $m['apellido'],
            'nivel' => (int) $m['nivel'],
            'carrera' => (int) $m['carrera_id'],
            'estados' => $estados
        );
    }
    // El registro ya con el resultado de este periodo
    $estudiantes[$cedula]['estados'][$asignatura] = $nuevo;
}

// Nivel: sube 1 quien aprobo todo lo que matriculo Y no tiene otra materia REPROBADA en su registro
// (sin pasar del nivel mas alto de su carrera). Como el registro ya incluye los resultados de este
// periodo, basta con que no quede ninguna materia REPROBADA. Los demas quedan "por revisar".
$cambiosNivel = array();    // cedula, nivel nuevo, nivel anterior
$porRevisar = array();
foreach ($estudiantes as $cedula => $e) {
    // PHP convierte en numero una clave como '1800000000': se vuelve a texto (la cedula siempre es texto)
    $cedula = (string) $cedula;
    $hayReprobada = false;
    foreach ($e['estados'] as $estado) {
        if ($estado === 'REPROBADA') {
            $hayReprobada = true;
        }
    }
    if ($hayReprobada) {
        $porRevisar[] = array('cedula' => $cedula, 'nombre' => $e['nombre'], 'apellido' => $e['apellido']);
        continue;
    }
    $carrera = $e['carrera'];
    $stmt = $conn->prepare('SELECT MAX(nivel) AS maximo FROM asignatura WHERE carrera_id = ?');
    $stmt->bind_param('i', $carrera);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ($e['nivel'] < (int) $fila['maximo']) {
        $cambiosNivel[] = array('cedula' => $cedula, 'nuevo' => $e['nivel'] + 1, 'antes' => $e['nivel']);
    }
}

// APLICAR los cambios, revisando cada uno. Se anota lo que ya se hizo para poder deshacerlo.
$estadosHechos = array();
$nivelesHechos = array();

foreach ($cambiosEstado as $c) {
    $stmt = $conn->prepare('UPDATE registro_academico SET estado = ? WHERE estudiante_cedula = ? AND asignatura_id = ?');
    $stmt->bind_param('ssi', $c['nuevo'], $c['cedula'], $c['asignatura']);
    if (!$stmt->execute()) {
        fallarCierre($conn, 'no se pudo guardar el estado de una materia', $estadosHechos, $nivelesHechos);
    }
    $estadosHechos[] = $c;
}

foreach ($cambiosNivel as $c) {
    $stmt = $conn->prepare('UPDATE estudiante SET nivel = ? WHERE cedula = ?');
    $stmt->bind_param('is', $c['nuevo'], $c['cedula']);
    if (!$stmt->execute()) {
        fallarCierre($conn, 'no se pudo subir de nivel a un estudiante', $estadosHechos, $nivelesHechos);
    }
    $nivelesHechos[] = $c;
}

// Al final el periodo queda inactivo y con la matricula cerrada
$stmt = $conn->prepare('UPDATE periodo SET activo = 0, matricula_abierta = 0 WHERE id = ?');
$stmt->bind_param('i', $periodoId);
if (!$stmt->execute()) {
    fallarCierre($conn, 'no se pudo marcar el periodo como cerrado', $estadosHechos, $nivelesHechos);
}

echo json_encode(array(
    'success' => true,
    'subieron' => count($cambiosNivel),
    'porRevisar' => $porRevisar
));
$conn->close();
