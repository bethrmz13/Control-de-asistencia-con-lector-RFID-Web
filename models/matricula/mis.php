<?php
// models/matricula/mis.php
// Materias en las que el estudiante esta matriculado en el periodo activo
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));
$conn = conectar();

// Al estudiante se le identifica por su usuario de sesion, nunca por un dato del navegador
$idUsuario = (int) $usuario['id'];
$stmt = $conn->prepare('SELECT cedula FROM estudiante WHERE usuario_id = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
if (!$est) {
    echo json_encode(array('error' => 'Su usuario no esta vinculado a un estudiante'));
    $conn->close();
    exit;
}

$periodo = periodoActivo($conn);
$materias = array();
if ($periodo !== null) {
    $periodoId = (int) $periodo['id'];
    $stmt = $conn->prepare('SELECT g.id, a.nombre AS asignatura, c.nombre AS carrera, g.paralelo, m.nota
                            FROM matricula m
                            JOIN grupo g ON g.id = m.grupo_id
                            JOIN asignatura a ON a.id = g.asignatura_id
                            JOIN carrera c ON c.id = a.carrera_id
                            WHERE m.estudiante_cedula = ? AND g.periodo_id = ?
                            ORDER BY a.nombre');
    $stmt->bind_param('si', $est['cedula'], $periodoId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $materias[] = $fila;
    }
}

echo json_encode(array(
    'success' => true,
    'abierta' => ($periodo !== null && (int) $periodo['matricula_abierta'] === 1),
    'maximo' => 5,
    'total' => count($materias),
    'materias' => $materias
));
$conn->close();
