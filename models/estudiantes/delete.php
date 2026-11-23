<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
if (!preg_match('/^[0-9]{10}$/', $cedula)) {
    echo json_encode(array("error" => "Datos inválidos"));
    exit;
}

$conn = conectar();

// El estudiante debe existir y, si es coordinador, ser de su carrera
$stmt = $conn->prepare("SELECT carrera_id, usuario_id FROM estudiante WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El estudiante no existe"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $actual['carrera_id'] != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede eliminar estudiantes de su carrera"));
    exit;
}

// Se borra el estudiante: sus matrículas, materias y asistencias se borran solas 
$stmt = $conn->prepare("DELETE FROM estudiante WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudo eliminar el estudiante"));
    exit;
}

// Y también su usuario (solo si es de perfil ESTUDIANTE)
if ($actual['usuario_id'] !== null) {
    $usuarioId = (int) $actual['usuario_id'];
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ? AND perfil = 'ESTUDIANTE'");
    $stmt->bind_param("i", $usuarioId);
    if (!$stmt->execute()) {
        echo json_encode(array("error" => "Se eliminó el estudiante, pero no se pudo eliminar su usuario"));
        exit;
    }
}

echo json_encode(array("success" => true));
$conn->close();