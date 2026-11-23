<?php
// models/cierre/faltantes.php
// Periodo activo y las matriculas que todavia no tienen nota
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN'));
$conn = conectar();

$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array('success' => true, 'periodo' => null, 'faltantes' => array()));
    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];
$stmt = $conn->prepare('SELECT e.cedula, e.apellido, e.nombre, a.nombre AS asignatura, g.paralelo
                        FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        JOIN estudiante e ON e.cedula = m.estudiante_cedula
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE g.periodo_id = ? AND m.nota IS NULL
                        ORDER BY e.apellido, e.nombre, a.nombre');
$stmt->bind_param('i', $periodoId);
$stmt->execute();
$res = $stmt->get_result();
$faltantes = array();
while ($fila = $res->fetch_assoc()) {
    $faltantes[] = $fila;
}

echo json_encode(array(
    'success' => true,
    'periodo' => array(
        'id' => $periodo['id'],
        'nombre' => $periodo['nombre'],
        'fecha_inicio' => $periodo['fecha_inicio'],
        'fecha_fin' => $periodo['fecha_fin']
    ),
    'faltantes' => $faltantes
));
$conn->close();
