<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$paralelo = isset($_POST['paralelo']) && is_string($_POST['paralelo']) ? trim($_POST['paralelo']) : '';
$docente = null;
if (isset($_POST['docente']) && is_string($_POST['docente']) && trim($_POST['docente']) !== '') {
    $docente = filter_var($_POST['docente'], FILTER_VALIDATE_INT);
    if (!$docente || $docente <= 0) {
        echo json_encode(array("error" => "Docente inválido"));
        exit;
    }
}

if (!$id || $id <= 0) {
    echo json_encode(array("error" => "Grupo inválido"));
    exit;
}
if (!in_array($paralelo, array('A', 'B', 'C'), true)) {
    echo json_encode(array("error" => "El paralelo debe ser A, B o C"));
    exit;
}

$conn = conectar();

$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];

// El grupo debe existir, ser del periodo activo y, si es COORDINADOR, de su carrera
$stmt = $conn->prepare("SELECT g.id, g.asignatura_id, g.paralelo, g.docente_id, a.carrera_id
                        FROM grupo g
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE g.id = ? AND g.periodo_id = ?");
$stmt->bind_param("ii", $id, $periodoId);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$actual) {
    echo json_encode(array("error" => "El grupo no existe en el periodo activo"));
    $conn->close();
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $actual['carrera_id'] !== (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede modificar grupos de su carrera"));
    $conn->close();
    exit;
}

// No se repite la misma asignatura con el mismo paralelo (se excluye este mismo grupo)
$asignaturaId = (int) $actual['asignatura_id'];
$stmt = $conn->prepare("SELECT id FROM grupo
                        WHERE periodo_id = ? AND asignatura_id = ? AND paralelo = ? AND id <> ?");
$stmt->bind_param("iisi", $periodoId, $asignaturaId, $paralelo, $id);
$stmt->execute();
$repetido = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($repetido) {
    echo json_encode(array("error" => "Ya existe un grupo de esa asignatura con el paralelo " . $paralelo . " en este periodo"));
    $conn->close();
    exit;
}

// Si CAMBIA el docente, se revisa que sea valido y que no choque con sus otras clases
$docenteActual = $actual['docente_id'] === null ? null : (int) $actual['docente_id'];
if ($docente !== null && $docente !== $docenteActual) {

    $stmt = $conn->prepare("SELECT nombre FROM usuario WHERE id = ? AND perfil = 'DOCENTE' AND activo = 1");
    $stmt->bind_param("i", $docente);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$doc) {
        echo json_encode(array("error" => "El docente no existe o no está activo"));
        $conn->close();
        exit;
    }

    // Para cada horario de ESTE grupo: el docente no puede tener otro que se cruce el mismo dia
    $stmt = $conn->prepare("SELECT a.nombre, g2.paralelo, h2.dia_semana, h2.hora_inicio, h2.hora_fin
                            FROM horario h1
                            JOIN horario h2 ON h2.dia_semana = h1.dia_semana
                                           AND h2.hora_inicio < h1.hora_fin
                                           AND h1.hora_inicio < h2.hora_fin
                            JOIN grupo g2 ON g2.id = h2.grupo_id
                                         AND g2.docente_id = ? AND g2.periodo_id = ? AND g2.id <> ?
                            JOIN asignatura a ON a.id = g2.asignatura_id
                            WHERE h1.grupo_id = ?
                            ORDER BY h2.dia_semana, h2.hora_inicio
                            LIMIT 1");
    $stmt->bind_param("iiii", $docente, $periodoId, $id, $id);
    $stmt->execute();
    $choque = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($choque) {
        $dias = array('', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo');
        echo json_encode(array("error" => "El docente " . $doc['nombre'] . " ya tiene clase a esa hora: "
            . $choque['nombre'] . " (paralelo " . $choque['paralelo'] . "), "
            . $dias[(int) $choque['dia_semana']] . " "
            . substr($choque['hora_inicio'], 0, 5) . " a " . substr($choque['hora_fin'], 0, 5)));
        $conn->close();
        exit;
    }
}

$stmt = $conn->prepare("UPDATE grupo SET paralelo = ?, docente_id = ? WHERE id = ?");
$stmt->bind_param("sii", $paralelo, $docente, $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe un grupo de esa asignatura con el paralelo " . $paralelo . " en este periodo"));
} else {
    echo json_encode(array("error" => "No se pudo modificar el grupo"));
}
$conn->close();
