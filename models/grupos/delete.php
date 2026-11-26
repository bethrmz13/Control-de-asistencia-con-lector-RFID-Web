<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    echo json_encode(array("error" => "Grupo inválido"));
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

// El grupo debe existir, ser del periodo activo y, si es COORDINADOR, de su carrera
$stmt = $conn->prepare("SELECT a.carrera_id
                        FROM grupo g
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE g.id = ? AND g.periodo_id = ?");
$stmt->bind_param("ii", $id, $periodoId);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$grupo) {
    echo json_encode(array("error" => "El grupo no existe en el periodo activo"));
    $conn->close();
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $grupo['carrera_id'] !== (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede eliminar grupos de su carrera"));
    $conn->close();
    exit;
}

// No se elimina un grupo con estudiantes matriculados
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM matricula WHERE grupo_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cuenta = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ((int) $cuenta['total'] > 0) {
    echo json_encode(array("error" => "No se puede eliminar el grupo porque tiene " . $cuenta['total'] . " estudiante(s) matriculado(s)"));
    $conn->close();
    exit;
}

// Sus horarios se borran solos por la clave foranea (ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM grupo WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("error" => "No se pudo eliminar el grupo"));
}
$conn->close();
