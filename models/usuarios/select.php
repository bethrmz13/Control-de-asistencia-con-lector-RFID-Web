<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$conn = conectar();

// La lista nunca incluye la clave.
$stmt = $conn->prepare("SELECT u.id, u.username, u.nombre, u.perfil, u.carrera_id, u.activo, c.nombre AS carrera
                        FROM usuario u
                        LEFT JOIN carrera c ON c.id = u.carrera_id
                        WHERE u.id > ?
                        ORDER BY u.nombre, u.id");
$desde = 0;
$stmt->bind_param('i', $desde);
$stmt->execute();
$res = $stmt->get_result();
$usuarios = array();
while ($fila = $res->fetch_assoc()) {
    $usuarios[] = $fila;
}

$stmtCarreras = $conn->prepare("SELECT id, nombre AS texto FROM carrera WHERE id > ? ORDER BY nombre");
$stmtCarreras->bind_param('i', $desde);
$stmtCarreras->execute();
$resCarreras = $stmtCarreras->get_result();
$carreras = array();
while ($fila = $resCarreras->fetch_assoc()) {
    $carreras[] = $fila;
}

echo json_encode(array(
    'success' => true,
    'usuarios' => $usuarios,
    'carreras' => $carreras,
    'yo' => (int) $usuario['id']
));
$conn->close();
