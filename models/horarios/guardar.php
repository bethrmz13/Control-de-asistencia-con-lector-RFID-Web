<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/validar.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$grupoId = isset($_POST['grupo']) && ctype_digit((string) $_POST['grupo']) ? (int) $_POST['grupo'] : 0;
$dia = isset($_POST['dia']) && ctype_digit((string) $_POST['dia']) ? (int) $_POST['dia'] : 0;
$inicio = normalizarHora(isset($_POST['inicio']) ? $_POST['inicio'] : '');
$fin = normalizarHora(isset($_POST['fin']) ? $_POST['fin'] : '');
$aula = isset($_POST['aula']) ? trim($_POST['aula']) : '';

if ($grupoId <= 0) {
    echo json_encode(array("error" => "Seleccione un grupo"));
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

// El grupo debe ser del periodo activo y, si es coordinador, de su carrera
$grupo = grupoDelUsuario($conn, $usuario, $grupoId);
if ($grupo === null) {
    echo json_encode(array("error" => "El grupo no existe, no es del periodo activo o no es de su carrera"));
    exit;
}

$error = revisarHorario($conn, $grupo, $dia, $inicio, $fin, $aula, 0);
if ($error !== '') {
    echo json_encode(array("error" => $error));
    exit;
}

$stmt = $conn->prepare("INSERT INTO horario (grupo_id, dia_semana, hora_inicio, hora_fin, aula) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $grupoId, $dia, $inicio, $fin, $aula);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe una clase de este grupo ese día a esa hora de inicio"));
} else {
    echo json_encode(array("error" => "No se pudo guardar el horario"));
}
$conn->close();