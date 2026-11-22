<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));   // solo el ADMIN administra usuarios
$conn = conectar();

// La lista NUNCA incluye la clave
$res = $conn->query("SELECT u.id, u.username, u.nombre, u.perfil, u.carrera_id, u.activo, c.nombre AS carrera
                     FROM usuario u LEFT JOIN carrera c ON c.id = u.carrera_id
                     ORDER BY u.nombre");
$usuarios = array();
while ($fila = $res->fetch_assoc()) {
    $usuarios[] = $fila;
}

// Carreras para el combo (solo se usa con el perfil COORDINADOR)
$res2 = $conn->query("SELECT id, nombre AS texto FROM carrera ORDER BY nombre");
$carreras = array();
while ($fila = $res2->fetch_assoc()) {
    $carreras[] = $fila;
}

// "yo" sirve para que la vista no ofrezca eliminar al propio usuario
echo json_encode(array("usuarios" => $usuarios, "carreras" => $carreras, "yo" => (int) $usuario['id']));
$conn->close();
