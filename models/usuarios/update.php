<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';       // vacía = no se cambia
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
$carrera = isset($_POST['carrera']) ? (int) $_POST['carrera'] : 0;
$activo = (isset($_POST['activo']) && $_POST['activo'] == '1') ? 1 : 0;

$perfiles = array('ADMIN', 'COORDINADOR', 'DOCENTE', 'REGISTRO', 'ESTUDIANTE');

if ($id <= 0) {
    echo json_encode(array("error" => "Datos inválidos"));
    exit;
}
if (!preg_match('/^[A-Za-z0-9._-]{1,30}$/', $username)) {
    echo json_encode(array("error" => "El usuario debe tener de 1 a 30 caracteres: letras, números, punto, guion o guion bajo"));
    exit;
}
if ($clave !== '' && strlen($clave) > 50) {
    echo json_encode(array("error" => "La clave puede tener hasta 50 caracteres"));
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

// El usuario debe existir
$stmt = $conn->prepare("SELECT perfil, activo FROM usuario WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El usuario no existe"));
    exit;
}

// Protección del ADMIN: el sistema nunca se queda sin un ADMIN activo
$dejaDeSerAdminActivo = ($actual['perfil'] === 'ADMIN' && (int) $actual['activo'] === 1
        && ($perfil !== 'ADMIN' || $activo === 0));
if ($dejaDeSerAdminActivo) {
    if ($id === (int) $usuario['id']) {
        echo json_encode(array("error" => "No puede quitarse a sí mismo el perfil ADMIN ni desactivarse"));
        exit;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE perfil = 'ADMIN' AND activo = 1 AND id <> ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res->fetch_assoc();
    if ((int) $fila['total'] < 1) {
        echo json_encode(array("error" => "Debe quedar al menos un ADMIN activo"));
        exit;
    }
}

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

if ($clave !== '') {
    $stmt = $conn->prepare("UPDATE usuario SET username = ?, clave = ?, nombre = ?, perfil = ?, carrera_id = ?, activo = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", $username, $clave, $nombre, $perfil, $carreraGuardar, $activo, $id);
} else {
    $stmt = $conn->prepare("UPDATE usuario SET username = ?, nombre = ?, perfil = ?, carrera_id = ?, activo = ? WHERE id = ?");
    $stmt->bind_param("sssiii", $username, $nombre, $perfil, $carreraGuardar, $activo, $id);
}

if ($stmt->execute()) {
    // Si se editó a sí mismo, se actualizan los datos de su sesión
    if ($id === (int) $usuario['id']) {
        $_SESSION['usuario']['username'] = $username;
        $_SESSION['usuario']['nombre'] = $nombre;
    }
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe un usuario con ese nombre de usuario"));
} else {
    echo json_encode(array("error" => "No se pudo modificar el usuario"));
}
$conn->close();
