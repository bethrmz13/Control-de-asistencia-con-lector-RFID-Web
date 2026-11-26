<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'DOCENTE'));
$conn = conectar();

$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];

// Grupos del periodo activo. El DOCENTE solo ve los suyos (filtro en el servidor)
$sql = "SELECT g.id, g.asignatura_id, a.nombre AS asignatura, a.nivel, a.carrera_id, c.nombre AS carrera, g.paralelo
        FROM grupo g
        JOIN asignatura a ON a.id = g.asignatura_id
        JOIN carrera c ON c.id = a.carrera_id
        WHERE g.periodo_id = ?";
if ($usuario['perfil'] === 'DOCENTE') {
    $docenteId = (int) $usuario['id'];
    $stmt = $conn->prepare($sql . " AND g.docente_id = ? ORDER BY a.nivel, a.nombre, g.paralelo");
    $stmt->bind_param("ii", $periodoId, $docenteId);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY a.nivel, a.nombre, g.paralelo");
    $stmt->bind_param("i", $periodoId);
}
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudieron consultar los grupos"));
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$grupos = array();
while ($fila = $res->fetch_assoc()) {
    $grupos[] = $fila;
}

echo json_encode(array("success" => true, "periodo" => $periodo['nombre'], "grupos" => $grupos));
$conn->close();
