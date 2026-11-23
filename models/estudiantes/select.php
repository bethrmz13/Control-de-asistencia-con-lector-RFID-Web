<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));
$conn = conectar();

// El COORDINADOR solo ve su carrera: el filtro se hace aquí, en el servidor
$soloCarrera = ($usuario['perfil'] === 'COORDINADOR');
$carreraUsuario = (int) $usuario['carrera_id'];

$base = "SELECT e.cedula, e.codigo_rfid, e.nombre, e.apellido, e.correo, e.carrera_id,
                c.nombre AS carrera, e.nivel, u.username
         FROM estudiante e
         JOIN carrera c ON c.id = e.carrera_id
         LEFT JOIN usuario u ON u.id = e.usuario_id";

if ($soloCarrera) {
    $stmt = $conn->prepare($base . " WHERE e.carrera_id = ? ORDER BY e.apellido, e.nombre");
    $stmt->bind_param("i", $carreraUsuario);
} else {
    $stmt = $conn->prepare($base . " ORDER BY c.nombre, e.apellido, e.nombre");
}
$stmt->execute();
$res = $stmt->get_result();
$estudiantes = array();
while ($fila = $res->fetch_assoc()) {
    $estudiantes[] = $fila;      // no se devuelve ninguna clave
}

// Carreras para el combo del formulario
if ($soloCarrera) {
    $stmt2 = $conn->prepare("SELECT id, nombre AS texto FROM carrera WHERE id = ?");
    $stmt2->bind_param("i", $carreraUsuario);
} else {
    $stmt2 = $conn->prepare("SELECT id, nombre AS texto FROM carrera ORDER BY nombre");
}
$stmt2->execute();
$res2 = $stmt2->get_result();
$carreras = array();
while ($fila = $res2->fetch_assoc()) {
    $carreras[] = $fila;
}

echo json_encode(array(
    "estudiantes" => $estudiantes,
    "carreras" => $carreras,
    "perfil" => $usuario['perfil'],
    "carrera_usuario" => $soloCarrera ? $carreraUsuario : 0
));
$conn->close();