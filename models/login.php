<?php
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';

if ($usuario === '' || $clave === '') {
    echo json_encode(array("error" => "Ingrese el usuario y la clave"));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare("SELECT id, username, nombre, perfil, carrera_id FROM usuario WHERE username = ? AND clave = ? AND activo = 1");
$stmt->bind_param("ss", $usuario, $clave);
$stmt->execute();
$res = $stmt->get_result();

if ($fila = $res->fetch_assoc()) {
    session_regenerate_id(true);
    $_SESSION['usuario'] = $fila;

    // A donde va cada perfil al entrar
    $destino = 'inicio';
    if ($fila['perfil'] === 'REGISTRO') {
        $destino = 'registro';
    } elseif ($fila['perfil'] === 'ESTUDIANTE') {
        $destino = 'consulta';
    }
    echo json_encode(array("success" => true, "destino" => $destino));
} else {
    echo json_encode(array("error" => "Usuario o clave incorrectos"));
}
$conn->close();
