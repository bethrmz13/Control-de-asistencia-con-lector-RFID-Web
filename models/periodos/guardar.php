<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$fechaInicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
$fechaFin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';

$longitudNombre = preg_match_all('/./us', $nombre, $coincidenciasNombre);
if ($longitudNombre === false || $longitudNombre < 1 || $longitudNombre > 60) {
    echo json_encode(array('error' => 'Ingrese un nombre de periodo de hasta 60 caracteres'));
    exit;
}

$inicio = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
$erroresInicio = DateTime::getLastErrors();
$fin = DateTime::createFromFormat('!Y-m-d', $fechaFin);
$erroresFin = DateTime::getLastErrors();
if (!$inicio || $inicio->format('Y-m-d') !== $fechaInicio
    || ($erroresInicio && ($erroresInicio['warning_count'] > 0 || $erroresInicio['error_count'] > 0))
    || !$fin || $fin->format('Y-m-d') !== $fechaFin
    || ($erroresFin && ($erroresFin['warning_count'] > 0 || $erroresFin['error_count'] > 0))) {
    echo json_encode(array('error' => 'Ingrese fechas válidas para el periodo'));
    exit;
}
if ($fechaInicio >= $fechaFin) {
    echo json_encode(array('error' => 'La fecha de inicio debe ser anterior a la fecha de fin'));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare('INSERT INTO periodo (nombre, fecha_inicio, fecha_fin) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $nombre, $fechaInicio, $fechaFin);
if ($stmt->execute()) {
    echo json_encode(array('success' => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array('error' => 'Ya existe un periodo con ese nombre'));
} else {
    echo json_encode(array('error' => 'No se pudo guardar el periodo'));
}
$conn->close();