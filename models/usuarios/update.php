<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
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
if ($id <= 0) {
    echo json_encode(array('error' => 'El usuario seleccionado no es válido'));
    exit;
}
if ($username === '' || $longitudUsuario > 30) {
    echo json_encode(array('error' => 'Ingrese un usuario de hasta 30 caracteres'));
    exit;
}
preg_match_all('/./us', $clave, $coincidenciasClave);
$longitudClave = count($coincidenciasClave[0]);
if ($clave !== '' && $longitudClave > 50) {
    echo json_encode(array('error' => 'La clave puede tener hasta 50 caracteres'));
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
$stmt = $conn->prepare('SELECT perfil, activo FROM usuario WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
if (!$actual) {
    echo json_encode(array('error' => 'El usuario no existe'));
    $conn->close();
    exit;
}

$activo = (int) $activoRecibido;
$dejaDeSerAdminActivo = ($actual['perfil'] === 'ADMIN' && (int) $actual['activo'] === 1
    && ($perfil !== 'ADMIN' || $activo === 0));
if ($dejaDeSerAdminActivo) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE perfil = 'ADMIN' AND activo = 1 AND id <> ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ((int) $fila['total'] < 1) {
        echo json_encode(array('error' => 'Debe quedar al menos un ADMIN activo'));
        $conn->close();
        exit;
    }
}

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

if ($clave !== '') {
    $stmt = $conn->prepare('UPDATE usuario SET username = ?, clave = ?, nombre = ?, perfil = ?, carrera_id = ?, activo = ? WHERE id = ?');
    $stmt->bind_param('ssssiii', $username, $clave, $nombre, $perfil, $carreraGuardar, $activo, $id);
} else {
    $stmt = $conn->prepare('UPDATE usuario SET username = ?, nombre = ?, perfil = ?, carrera_id = ?, activo = ? WHERE id = ?');
    $stmt->bind_param('sssiii', $username, $nombre, $perfil, $carreraGuardar, $activo, $id);
}

if ($stmt->execute()) {
    if ($id === (int) $usuario['id']) {
        if ($activo === 0) {
            $_SESSION = array();
            session_destroy();
        } else {
            $_SESSION['usuario']['username'] = $username;
            $_SESSION['usuario']['nombre'] = $nombre;
            $_SESSION['usuario']['perfil'] = $perfil;
            $_SESSION['usuario']['carrera_id'] = $carreraGuardar;
        }
    }
    echo json_encode(array('success' => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array('error' => 'Ya existe un usuario con ese nombre de usuario'));
} else {
    echo json_encode(array('error' => 'No se pudo modificar el usuario'));
}
$conn->close();

