<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$asignatura = filter_input(INPUT_POST, 'asignatura', FILTER_VALIDATE_INT);
$carrera = filter_input(INPUT_POST, 'carrera', FILTER_VALIDATE_INT);
$nivel = filter_input(INPUT_POST, 'nivel', FILTER_VALIDATE_INT);
$paralelo = isset($_POST['paralelo']) && is_string($_POST['paralelo']) ? trim($_POST['paralelo']) : '';
$docente = null;   // puede quedar sin docente
if (isset($_POST['docente']) && is_string($_POST['docente']) && trim($_POST['docente']) !== '') {
    $docente = filter_var($_POST['docente'], FILTER_VALIDATE_INT);
    if (!$docente || $docente <= 0) {
        echo json_encode(array("error" => "Docente inválido"));
        exit;
    }
}

if (!$asignatura || $asignatura <= 0) {
    echo json_encode(array("error" => "Seleccione la asignatura"));
    exit;
}
if (!$carrera || $carrera <= 0 || !$nivel || $nivel <= 0) {
    echo json_encode(array("error" => "Seleccione una carrera y un nivel válidos"));
    exit;
}
if (!in_array($paralelo, array('A', 'B', 'C'), true)) {
    echo json_encode(array("error" => "El paralelo debe ser A, B o C"));
    exit;
}

$conn = conectar();

// El grupo se crea en el periodo activo
$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];

// La asignatura debe existir y, si es COORDINADOR, ser de su carrera
$stmt = $conn->prepare("SELECT carrera_id, nivel FROM asignatura WHERE id = ?");
$stmt->bind_param("i", $asignatura);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$fila) {
    echo json_encode(array("error" => "La asignatura no existe"));
    $conn->close();
    exit;
}
if ((int) $fila['carrera_id'] !== $carrera || (int) $fila['nivel'] !== $nivel) {
    echo json_encode(array("error" => "La asignatura no corresponde a la carrera y nivel seleccionados"));
    $conn->close();
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $fila['carrera_id'] !== (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede crear grupos de asignaturas de su carrera"));
    $conn->close();
    exit;
}

// El docente (si se eligió) debe ser un usuario DOCENTE activo
if ($docente !== null) {
    $stmt = $conn->prepare("SELECT id FROM usuario WHERE id = ? AND perfil = 'DOCENTE' AND activo = 1");
    $stmt->bind_param("i", $docente);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$existe) {
        echo json_encode(array("error" => "El docente no existe o no está activo"));
        $conn->close();
        exit;
    }
}

// No se repite la misma asignatura con el mismo paralelo en el periodo
$stmt = $conn->prepare("SELECT id FROM grupo WHERE periodo_id = ? AND asignatura_id = ? AND paralelo = ?");
$stmt->bind_param("iis", $periodoId, $asignatura, $paralelo);
$stmt->execute();
$repetido = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($repetido) {
    echo json_encode(array("error" => "Ya existe un grupo de esa asignatura con el paralelo " . $paralelo . " en este periodo"));
    $conn->close();
    exit;
}

$stmt = $conn->prepare("INSERT INTO grupo (periodo_id, asignatura_id, paralelo, docente_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iisi", $periodoId, $asignatura, $paralelo, $docente);
if ($stmt->execute()) {
    echo json_encode(array("success" => true, "id" => $conn->insert_id));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe un grupo de esa asignatura con el paralelo " . $paralelo . " en este periodo"));
} else {
    echo json_encode(array("error" => "No se pudo guardar el grupo"));
}
$conn->close();
