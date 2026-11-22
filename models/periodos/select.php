<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$conn = conectar();

$stmt = $conn->prepare('SELECT id, nombre, fecha_inicio, fecha_fin, activo, matricula_abierta FROM periodo ORDER BY activo DESC, fecha_inicio DESC, id DESC');
$stmt->execute();
$res = $stmt->get_result();
$periodos = array();
while ($fila = $res->fetch_assoc()) {
    $periodos[] = $fila;
}

echo json_encode(array('success' => true, 'periodos' => $periodos));
$conn->close();