<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'DOCENTE'));

$grupoId = filter_input(INPUT_GET, 'grupo', FILTER_VALIDATE_INT);
if (!$grupoId || $grupoId <= 0) {
    echo json_encode(array("error" => "Seleccione un grupo válido"));
    exit;
}

$conn = conectar();

$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];

// El grupo debe ser del periodo activo y, si es DOCENTE, suyo (se decide en el servidor)
$stmt = $conn->prepare("SELECT g.paralelo, g.docente_id, a.nombre AS asignatura, a.nivel
                        FROM grupo g
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE g.id = ? AND g.periodo_id = ?");
$stmt->bind_param("ii", $grupoId, $periodoId);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$grupo) {
    echo json_encode(array("error" => "El grupo no existe en el periodo activo"));
    $conn->close();
    exit;
}
if ($usuario['perfil'] === 'DOCENTE' && (int) $grupo['docente_id'] !== (int) $usuario['id']) {
    echo json_encode(array("error" => "Solo puede ver las notas de sus propios grupos"));
    $conn->close();
    exit;
}

// Estudiantes matriculados con su nota (NULL si todavia no tiene)
$stmt = $conn->prepare("SELECT e.cedula, e.apellido, e.nombre, m.nota
                        FROM matricula m
                        JOIN estudiante e ON e.cedula = m.estudiante_cedula
                        WHERE m.grupo_id = ?
                        ORDER BY e.apellido, e.nombre");
$stmt->bind_param("i", $grupoId);
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudieron consultar los estudiantes"));
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$estudiantes = array();
while ($fila = $res->fetch_assoc()) {
    $estudiantes[] = $fila;
}

echo json_encode(array(
    "success" => true,
    "asignatura" => $grupo['asignatura'],
    "nivel" => $grupo['nivel'],
    "paralelo" => $grupo['paralelo'],
    "estudiantes" => $estudiantes
));
$conn->close();
