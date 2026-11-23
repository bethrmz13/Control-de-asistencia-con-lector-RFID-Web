<?php
// models/matricula/anular.php
// Anula una matricula: solo con la matricula abierta y solo si la materia todavia no tiene nota
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));

$grupoId = isset($_POST['grupo']) && ctype_digit((string) $_POST['grupo']) ? (int) $_POST['grupo'] : 0;
if ($grupoId <= 0) {
    echo json_encode(array('error' => 'Seleccione un grupo'));
    exit;
}

$conn = conectar();

function fallo($conn, $mensaje) {
    echo json_encode(array('error' => $mensaje));
    $conn->close();
    exit;
}

$idUsuario = (int) $usuario['id'];
$stmt = $conn->prepare('SELECT cedula FROM estudiante WHERE usuario_id = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
if (!$est) {
    fallo($conn, 'Su usuario no esta vinculado a un estudiante');
}
$cedula = $est['cedula'];

$periodo = periodoActivo($conn);
if ($periodo === null || (int) $periodo['matricula_abierta'] !== 1) {
    fallo($conn, 'Las matriculas estan cerradas');
}
$periodoId = (int) $periodo['id'];

// La matricula debe existir, ser de este estudiante y de un grupo del periodo activo.
// Se comprueba con un SELECT (no con affected_rows).
$stmt = $conn->prepare('SELECT m.nota FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        WHERE m.estudiante_cedula = ? AND m.grupo_id = ? AND g.periodo_id = ?');
$stmt->bind_param('sii', $cedula, $grupoId, $periodoId);
$stmt->execute();
$matricula = $stmt->get_result()->fetch_assoc();
if (!$matricula) {
    fallo($conn, 'No esta matriculado en ese grupo');
}
if ($matricula['nota'] !== null) {
    fallo($conn, 'No se puede anular: la materia ya tiene nota');
}

$stmt = $conn->prepare('DELETE FROM matricula WHERE estudiante_cedula = ? AND grupo_id = ? AND nota IS NULL');
$stmt->bind_param('si', $cedula, $grupoId);
if ($stmt->execute()) {
    echo json_encode(array('success' => true, 'mensaje' => 'Matricula anulada'));
} else {
    echo json_encode(array('error' => 'No se pudo anular la matricula'));
}
$conn->close();
