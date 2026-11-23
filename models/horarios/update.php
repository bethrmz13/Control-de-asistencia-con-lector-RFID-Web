<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/validar.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$dia = isset($_POST['dia']) && ctype_digit((string) $_POST['dia']) ? (int) $_POST['dia'] : 0;
$inicio = normalizarHora(isset($_POST['inicio']) ? $_POST['inicio'] : '');
$fin = normalizarHora(isset($_POST['fin']) ? $_POST['fin'] : '');
$aula = isset($_POST['aula']) ? trim($_POST['aula']) : '';

if ($id <= 0) {
    echo json_encode(array("error" => "El horario seleccionado no es válido"));
    exit;
}
if ($dia < 1 || $dia > 7) {
    echo json_encode(array("error" => "Seleccione el día"));
    exit;
}
if ($inicio === '' || $fin === '') {
    echo json_encode(array("error" => "Ingrese la hora de inicio y la de fin"));
    exit;
}
if ($aula === '' || strlen($aula) > 20) {
    echo json_encode(array("error" => "Ingrese el aula (hasta 20 caracteres)"));
    exit;
}

$conn = conectar();

// Se busca a qué grupo pertenece el horario
$stmt = $conn->prepare("SELECT grupo_id FROM horario WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El horario no existe"));
    exit;
}

$grupo = grupoDelUsuario($conn, $usuario, (int) $actual['grupo_id']);
if ($grupo === null) {
    echo json_encode(array("error" => "Ese horario no es del periodo activo o no es de su carrera"));
    exit;
}

// Se excluye el mismo horario ($id) para que no choque consigo mismo
$error = revisarHorario($conn, $grupo, $dia, $inicio, $fin, $aula, $id);
if ($error !== '') {
    echo json_encode(array("error" => $error));
    exit;
}

$stmt = $conn->prepare("UPDATE horario SET dia_semana = ?, hora_inicio = ?, hora_fin = ?, aula = ? WHERE id = ?");
$stmt->bind_param("isssi", $dia, $inicio, $fin, $aula, $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe una clase de este grupo ese día a esa hora de inicio"));
} else {
    echo json_encode(array("error" => "No se pudo modificar el horario"));
}
$conn->close();