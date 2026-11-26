<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

// Datos que llegan por la dirección 
$carrera = isset($_GET['carrera']) && ctype_digit((string) $_GET['carrera']) ? (int) $_GET['carrera'] : 0;
$nivel = isset($_GET['nivel']) && ctype_digit((string) $_GET['nivel']) ? (int) $_GET['nivel'] : 0;
$cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';

if ($carrera <= 0 || $nivel < 1 || $nivel > 10) {
    echo json_encode(array("error" => "Seleccione la carrera y un nivel de 1 a 10"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && $carrera != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede usar su carrera"));
    exit;
}

$conn = conectar();

// Si llega una cédula (al editar), el estudiante debe existir y ser de esa carrera
if ($cedula !== '') {
    $stmt = $conn->prepare("SELECT cedula FROM estudiante WHERE cedula = ? AND carrera_id = ?");
    $stmt->bind_param("si", $cedula, $carrera);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(array("error" => "El estudiante no existe en esa carrera"));
        exit;
    }
}

// Todas las asignaturas de la carrera, con el estado que ya tiene el estudiante 
$stmt = $conn->prepare("SELECT a.id, a.nombre, a.nivel, ra.estado
                        FROM asignatura a
                        LEFT JOIN registro_academico ra
                               ON ra.asignatura_id = a.id AND ra.estudiante_cedula = ?
                        WHERE a.carrera_id = ?
                        ORDER BY a.nivel, a.nombre");
$stmt->bind_param("si", $cedula, $carrera);
$stmt->execute();
$res = $stmt->get_result();

$materias = array();
while ($fila = $res->fetch_assoc()) {
    if ($fila['estado'] === null) {
        $fila['estado'] = ((int) $fila['nivel'] < $nivel) ? 'APROBADA' : 'AUN_NO_CURSA';
    }
    $materias[] = $fila;
}

echo json_encode(array("materias" => $materias));
$conn->close();