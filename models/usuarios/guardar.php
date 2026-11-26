<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
$carreraId = isset($_POST['carrera']) && ctype_digit((string) $_POST['carrera']) ? (int) $_POST['carrera'] : 0;
$activoRecibido = isset($_POST['activo']) ? (string) $_POST['activo'] : '';
$perfiles = array('ADMIN', 'COORDINADOR', 'DOCENTE', 'REGISTRO', 'ESTUDIANTE');

preg_match_all('/./us', $username, $coincidenciasUsuario);
$longitudUsuario = count($coincidenciasUsuario[0]);
preg_match_all('/./us', $nombre, $coincidenciasNombre);
$longitudNombre = count($coincidenciasNombre[0]);

if ($username === '' || $longitudUsuario > 30) {
    echo json_encode(array('error' => 'Ingrese un usuario de hasta 30 caracteres'));
    exit;
}
preg_match_all('/./us', $clave, $coincidenciasClave);
$longitudClave = count($coincidenciasClave[0]);
if ($clave === '' || $longitudClave > 50) {
    echo json_encode(array('error' => 'Ingrese la clave (hasta 50 caracteres)'));
    exit;
}
if ($nombre === '' || $longitudNombre > 80) {
    echo json_encode(array('error' => 'Ingrese el nombre (hasta 80 caracteres)'));
    exit;
}
if (!in_array($perfil, $perfiles, true)) {
    echo json_encode(array('error' => 'Seleccione un perfil válido'));
    exit;
}
if ($activoRecibido !== '0' && $activoRecibido !== '1') {
    echo json_encode(array('error' => 'Indique si el usuario está activo'));
    exit;
}

$conn = conectar();
$carreraGuardar = null;
if ($perfil === 'COORDINADOR') {
    $stmt = $conn->prepare('SELECT id FROM carrera WHERE id = ?');
    $stmt->bind_param('i', $carreraId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(array('error' => 'Seleccione una carrera válida para el coordinador'));
        $conn->close();
        exit;
    }
    $carreraGuardar = $carreraId;
}

$activo = (int) $activoRecibido;
$stmt = $conn->prepare('INSERT INTO usuario (username, clave, nombre, perfil, carrera_id, activo) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssii', $username, $clave, $nombre, $perfil, $carreraGuardar, $activo);
if ($stmt->execute()) {
    echo json_encode(array('success' => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array('error' => 'Ya existe un usuario con ese nombre de usuario'));
} else {
    echo json_encode(array('error' => 'No se pudo guardar el usuario'));
}
$conn->close();

