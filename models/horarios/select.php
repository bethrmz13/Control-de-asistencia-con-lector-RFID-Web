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
    exit;
}
$periodoId = (int) $periodo['id'];

// Grupos del periodo activo. El COORDINADOR solo ve los de su carrera (filtro en el servidor)
$base = "SELECT g.id, a.nombre AS asignatura, a.nivel, g.paralelo
         FROM grupo g
         JOIN asignatura a ON a.id = g.asignatura_id
         WHERE g.periodo_id = ?";
if ($usuario['perfil'] === 'COORDINADOR') {
    $carrera = (int) $usuario['carrera_id'];
    $stmt = $conn->prepare($base . " AND a.carrera_id = ? ORDER BY a.nivel, a.nombre, g.paralelo");
    $stmt->bind_param("ii", $periodoId, $carrera);
} else {
    $stmt = $conn->prepare($base . " ORDER BY a.nivel, a.nombre, g.paralelo");
    $stmt->bind_param("i", $periodoId);
}
$stmt->execute();
$res = $stmt->get_result();
$grupos = array();
while ($fila = $res->fetch_assoc()) {
    $grupos[] = array(
        "id" => $fila['id'],
        "texto" => $fila['asignatura'] . ' (nivel ' . $fila['nivel'] . ') - Paralelo ' . $fila['paralelo']
    );
}

// Horarios del grupo elegido (si llega). Solo si ese grupo está en la lista que el usuario puede ver
$grupoId = isset($_GET['grupo']) && ctype_digit((string) $_GET['grupo']) ? (int) $_GET['grupo'] : 0;
$horarios = array();
if ($grupoId > 0) {
    $permitido = false;
    foreach ($grupos as $g) {
        if ((int) $g['id'] === $grupoId) {
            $permitido = true;
        }
    }
    if (!$permitido) {
        echo json_encode(array("error" => "El grupo no existe o no es de su carrera"));
        exit;
    }
    $stmt = $conn->prepare("SELECT id, dia_semana, TIME_FORMAT(hora_inicio, '%H:%i') AS hora_inicio,
                                   TIME_FORMAT(hora_fin, '%H:%i') AS hora_fin, aula
                            FROM horario
                            WHERE grupo_id = ?
                            ORDER BY dia_semana, hora_inicio");
    $stmt->bind_param("i", $grupoId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $horarios[] = $fila;
    }
}

echo json_encode(array("periodo" => $periodo['nombre'], "grupos" => $grupos, "horarios" => $horarios));
$conn->close();