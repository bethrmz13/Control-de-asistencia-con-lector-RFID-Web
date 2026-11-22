<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
$carrera = isset($_POST['carrera']) ? (int) $_POST['carrera'] : 0;
$activo = (isset($_POST['activo']) && $_POST['activo'] == '1') ? 1 : 0;

$perfiles = array('ADMIN', 'COORDINADOR', 'DOCENTE', 'REGISTRO', 'ESTUDIANTE');

// Validación en el servidor
if (!preg_match('/^[A-Za-z0-9._-]{1,30}$/', $username)) {
    echo json_encode(array("error" => "El usuario debe tener de 1 a 30 caracteres: letras, números, punto, guion o guion bajo"));
    exit;
}
if ($clave === '' || strlen($clave) > 50) {
    echo json_encode(array("error" => "Ingrese la clave (hasta 50 caracteres)"));
    exit;
}
if ($nombre === '' || strlen($nombre) > 80) {
    echo json_encode(array("error" => "Ingrese el nombre (hasta 80 caracteres)"));
    exit;
}
if (!in_array($perfil, $perfiles, true)) {
    echo json_encode(array("error" => "Seleccione el perfil"));
    exit;
}

$conn = conectar();

// La carrera solo se guarda para el COORDINADOR, y entonces es obligatoria
$carreraGuardar = null;
if ($perfil === 'COORDINADOR') {
    $stmt = $conn->prepare("SELECT id FROM carrera WHERE id = ?");
    $stmt->bind_param("i", $carrera);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->fetch_assoc()) {
        echo json_encode(array("error" => "Seleccione la carrera del coordinador"));
        exit;
    }
    $carreraGuardar = $carrera;
}

$stmt = $conn->prepare("INSERT INTO usuario (username, clave, nombre, perfil, carrera_id, activo) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssii", $username, $clave, $nombre, $perfil, $carreraGuardar, $activo);

if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe un usuario con ese nombre de usuario"));
} else {
    echo json_encode(array("error" => "No se pudo guardar el usuario"));
}
$conn->close();
