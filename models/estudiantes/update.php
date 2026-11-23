<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

// Cuenta caracteres (con tildes) sin depender de mbstring
function largo($texto) {
    preg_match_all('/./us', $texto, $m);
    return count($m[0]);
}

// La cédula identifica al estudiante y NO se modifica. Tampoco su carrera.
$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$nivel = isset($_POST['nivel']) && ctype_digit((string) $_POST['nivel']) ? (int) $_POST['nivel'] : 0;
$estadosRecibidos = (isset($_POST['materias']) && is_array($_POST['materias'])) ? $_POST['materias'] : array();

if (!validarCedula($cedula)) {
    echo json_encode(array("error" => "La cédula no es válida"));
    exit;
}
if (!validarCodigoTarjeta($codigo)) {
    echo json_encode(array("error" => "El código de la tarjeta debe tener 10 dígitos"));
    exit;
}
if ($nombre === '' || largo($nombre) > 60) {
    echo json_encode(array("error" => "Ingrese el nombre (hasta 60 caracteres)"));
    exit;
}
if ($apellido === '' || largo($apellido) > 60) {
    echo json_encode(array("error" => "Ingrese el apellido (hasta 60 caracteres)"));
    exit;
}
if (largo($correo) > 80 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array("error" => "Ingrese un correo válido (hasta 80 caracteres)"));
    exit;
}
if ($nivel < 1 || $nivel > 10) {
    echo json_encode(array("error" => "El nivel debe estar entre 1 y 10"));
    exit;
}

$conn = conectar();

// El estudiante debe existir y, si es coordinador, ser de su carrera
$stmt = $conn->prepare("SELECT carrera_id, usuario_id FROM estudiante WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "El estudiante no existe"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $actual['carrera_id'] != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede modificar estudiantes de su carrera"));
    exit;
}

// La tarjeta no puede ser de otro estudiante
$stmt = $conn->prepare("SELECT cedula FROM estudiante WHERE codigo_rfid = ? AND cedula <> ?");
$stmt->bind_param("ss", $codigo, $cedula);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(array("error" => "Esa tarjeta ya está asignada a otro estudiante"));
    exit;
}

// Estados de las materias de SU carrera (el estudiante no cambia de carrera)
$carrera = (int) $actual['carrera_id'];
$estadosValidos = array('APROBADA', 'REPROBADA', 'AUN_NO_CURSA');
$stmt = $conn->prepare("SELECT id, nivel FROM asignatura WHERE carrera_id = ?");
$stmt->bind_param("i", $carrera);
$stmt->execute();
$res = $stmt->get_result();
$materias = array();
while ($fila = $res->fetch_assoc()) {
    $id = (int) $fila['id'];
    if (isset($estadosRecibidos[$id]) && in_array($estadosRecibidos[$id], $estadosValidos, true)) {
        $materias[$id] = $estadosRecibidos[$id];
    } else {
        $materias[$id] = ((int) $fila['nivel'] < $nivel) ? 'APROBADA' : 'AUN_NO_CURSA';
    }
}

// Los datos del estudiante
$stmt = $conn->prepare("UPDATE estudiante SET codigo_rfid = ?, nombre = ?, apellido = ?, correo = ?, nivel = ? WHERE cedula = ?");
$stmt->bind_param("ssssis", $codigo, $nombre, $apellido, $correo, $nivel, $cedula);
if (!$stmt->execute()) {
    if ($stmt->errno == 1062) {
        echo json_encode(array("error" => "Esa tarjeta ya está asignada a otro estudiante"));
    } else {
        echo json_encode(array("error" => "No se pudo modificar el estudiante"));
    }
    exit;
}

// El nombre que se ve en el menú es el del usuario: se mantiene igual al del estudiante
if ($actual['usuario_id'] !== null) {
    $nombreUsuario = $nombre . ' ' . $apellido;
    if (largo($nombreUsuario) > 80) {
        preg_match('/^.{0,80}/us', $nombreUsuario, $corte);
        $nombreUsuario = $corte[0];
    }
    $usuarioId = (int) $actual['usuario_id'];
    $stmt = $conn->prepare("UPDATE usuario SET nombre = ? WHERE id = ?");
    $stmt->bind_param("si", $nombreUsuario, $usuarioId);
    $stmt->execute();
}

// Un UPDATE por cada materia (si la fila no existe, se crea)
$stmt = $conn->prepare("INSERT INTO registro_academico (estudiante_cedula, asignatura_id, estado)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE estado = ?");
$stmt->bind_param("siss", $cedula, $asignaturaId, $estado, $estado);
foreach ($materias as $asignaturaId => $estado) {
    if (!$stmt->execute()) {
        echo json_encode(array("error" => "Se modificaron los datos, pero falló la tabla de materias. Vuelva a intentarlo"));
        exit;
    }
}

echo json_encode(array("success" => true));
$conn->close();