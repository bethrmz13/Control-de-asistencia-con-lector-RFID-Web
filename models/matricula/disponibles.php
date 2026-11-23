<?php
// models/matricula/disponibles.php
// Grupos en los que el estudiante se puede matricular ahora
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));
$conn = conectar();

$idUsuario = (int) $usuario['id'];
$stmt = $conn->prepare('SELECT cedula, carrera_id, nivel FROM estudiante WHERE usuario_id = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
if (!$est) {
    echo json_encode(array('error' => 'Su usuario no esta vinculado a un estudiante'));
    $conn->close();
    exit;
}

// Sin periodo activo o con la matricula cerrada no se ofrece nada
$periodo = periodoActivo($conn);
if ($periodo === null || (int) $periodo['matricula_abierta'] !== 1) {
    echo json_encode(array('success' => true, 'abierta' => false, 'grupos' => array()));
    $conn->close();
    exit;
}

$periodoId = (int) $periodo['id'];
$carreraId = (int) $est['carrera_id'];
$nivel = (int) $est['nivel'];

// Grupos del periodo, de su carrera, de su nivel o inferior, con estado AUN_NO_CURSA o REPROBADA,
// y de asignaturas en las que todavia no esta matriculado (en ningun paralelo)
$sql = "SELECT g.id, a.nombre AS asignatura, a.nivel, g.paralelo, u.nombre AS docente
        FROM grupo g
        JOIN asignatura a ON a.id = g.asignatura_id
        JOIN registro_academico r ON r.asignatura_id = a.id AND r.estudiante_cedula = ?
        LEFT JOIN usuario u ON u.id = g.docente_id
        WHERE g.periodo_id = ? AND a.carrera_id = ? AND a.nivel <= ?
          AND r.estado IN ('AUN_NO_CURSA', 'REPROBADA')
          AND NOT EXISTS (SELECT 1 FROM matricula m
                          JOIN grupo g2 ON g2.id = m.grupo_id
                          WHERE m.estudiante_cedula = ? AND g2.asignatura_id = a.id
                            AND g2.periodo_id = g.periodo_id)
        ORDER BY a.nivel, a.nombre, g.paralelo";
$stmt = $conn->prepare($sql);
$stmt->bind_param('siiis', $est['cedula'], $periodoId, $carreraId, $nivel, $est['cedula']);
$stmt->execute();
$res = $stmt->get_result();
$grupos = array();
while ($fila = $res->fetch_assoc()) {
    $grupos[] = $fila;
}

// Los horarios de cada grupo en texto, por ejemplo "Lun 16:00-18:00"
$dias = array(1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom');
for ($i = 0; $i < count($grupos); $i++) {
    $grupoId = (int) $grupos[$i]['id'];
    $stmt = $conn->prepare('SELECT dia_semana, hora_inicio, hora_fin FROM horario
                            WHERE grupo_id = ? ORDER BY dia_semana, hora_inicio');
    $stmt->bind_param('i', $grupoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $textos = array();
    while ($h = $res->fetch_assoc()) {
        $textos[] = $dias[(int) $h['dia_semana']] . ' ' . substr($h['hora_inicio'], 0, 5) . '-' . substr($h['hora_fin'], 0, 5);
    }
    $grupos[$i]['horarios'] = count($textos) > 0 ? implode(', ', $textos) : 'Sin horario';
}

echo json_encode(array('success' => true, 'abierta' => true, 'grupos' => $grupos));
$conn->close();
