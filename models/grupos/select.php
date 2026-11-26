<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));
$conn = conectar();

$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];
$soloCarrera = ($usuario['perfil'] === 'COORDINADOR');
$carreraUsuario = (int) $usuario['carrera_id'];

// Grupos del periodo activo. El COORDINADOR solo ve los de su carrera (filtro en el servidor)
$sql = "SELECT g.id, g.asignatura_id, a.nombre AS asignatura, a.nivel, a.carrera_id, c.nombre AS carrera, g.paralelo,
               g.docente_id, u.nombre AS docente,
               (SELECT COUNT(*) FROM matricula m WHERE m.grupo_id = g.id) AS matriculados
        FROM grupo g
        JOIN asignatura a ON a.id = g.asignatura_id
        JOIN carrera c ON c.id = a.carrera_id
        LEFT JOIN usuario u ON u.id = g.docente_id
        WHERE g.periodo_id = ?";
if ($soloCarrera) {
    $stmt = $conn->prepare($sql . " AND a.carrera_id = ? ORDER BY a.nivel, a.nombre, g.paralelo");
    $stmt->bind_param("ii", $periodoId, $carreraUsuario);
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
$stmt->close();

// Combo de asignaturas (solo las de su carrera si es COORDINADOR)
$sql = "SELECT a.id, a.carrera_id, a.nivel, a.nombre AS texto
        FROM asignatura a
        JOIN carrera c ON c.id = a.carrera_id";
if ($soloCarrera) {
    $stmt = $conn->prepare($sql . " WHERE a.carrera_id = ? ORDER BY a.nivel, a.nombre");
    $stmt->bind_param("i", $carreraUsuario);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY c.nombre, a.nivel, a.nombre");
}
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudieron consultar las asignaturas"));
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$asignaturas = array();
while ($fila = $res->fetch_assoc()) {
    $asignaturas[] = $fila;
}
$stmt->close();

// Carreras disponibles para el selector inicial.
if ($soloCarrera) {
    $stmt = $conn->prepare("SELECT id, nombre AS texto FROM carrera WHERE id = ? ORDER BY nombre");
    $stmt->bind_param("i", $carreraUsuario);
} else {
    $stmt = $conn->prepare("SELECT id, nombre AS texto FROM carrera ORDER BY nombre");
}
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudieron consultar las carreras"));
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$carreras = array();
while ($fila = $res->fetch_assoc()) $carreras[] = $fila;
$stmt->close();

// Combo de docentes: usuarios DOCENTE activos
$stmt = $conn->prepare("SELECT id, nombre AS texto FROM usuario
                        WHERE perfil = 'DOCENTE' AND activo = 1 ORDER BY nombre");
if (!$stmt->execute()) {
    echo json_encode(array("error" => "No se pudieron consultar los docentes"));
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$docentes = array();
while ($fila = $res->fetch_assoc()) {
    $docentes[] = $fila;
}
$stmt->close();

echo json_encode(array(
    "success" => true,
    "periodo" => $periodo['nombre'],
    "grupos" => $grupos,
    "asignaturas" => $asignaturas,
    "carreras" => $carreras,
    "docentes" => $docentes
));
$conn->close();
