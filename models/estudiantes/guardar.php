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

// Si el guardado falla a la mitad se borra lo ya guardado: primero el estudiante, luego el usuario
function deshacer($conn, $cedula, $usuarioId) {
    if ($cedula !== '') {
        $stmt = $conn->prepare("DELETE FROM estudiante WHERE cedula = ?");
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
    }
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
}

$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$carrera = isset($_POST['carrera']) && ctype_digit((string) $_POST['carrera']) ? (int) $_POST['carrera'] : 0;
$nivel = isset($_POST['nivel']) && ctype_digit((string) $_POST['nivel']) ? (int) $_POST['nivel'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
// materias llega como materias[id_asignatura] = estado
$estadosRecibidos = (isset($_POST['materias']) && is_array($_POST['materias'])) ? $_POST['materias'] : array();

// ---------- Validación en el servidor ----------
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
if ($carrera <= 0) {
    echo json_encode(array("error" => "Seleccione la carrera"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && $carrera != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede registrar estudiantes de su carrera"));
    exit;
}
if (!preg_match('/^[A-Za-z0-9._-]{1,30}$/', $username)) {
    echo json_encode(array("error" => "El usuario debe tener de 1 a 30 caracteres: letras, números, punto, guion o guion bajo"));
    exit;
}
if ($clave === '' || largo($clave) > 50) {
    echo json_encode(array("error" => "Ingrese la clave (hasta 50 caracteres)"));
    exit;
}

$conn = conectar();

// La carrera debe existir
$stmt = $conn->prepare("SELECT id FROM carrera WHERE id = ?");
$stmt->bind_param("i", $carrera);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(array("error" => "La carrera no existe"));
    exit;
}

// ---------- Que no existan ya la cédula, la tarjeta ni el usuario ----------
$stmt = $conn->prepare("SELECT cedula FROM estudiante WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(array("error" => "Ya existe un estudiante con esa cédula"));
    exit;
}
$stmt = $conn->prepare("SELECT cedula FROM estudiante WHERE codigo_rfid = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(array("error" => "Esa tarjeta ya está asignada a otro estudiante"));
    exit;
}
$stmt = $conn->prepare("SELECT id FROM usuario WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(array("error" => "Ya existe un usuario con ese nombre de usuario"));
    exit;
}

// ---------- Tabla de materias: se arma con las asignaturas de la carrera ----------
// Se usa el estado que llegó si es válido; si no, el de la regla (nivel menor = APROBADA)
$estadosValidos = array('APROBADA', 'REPROBADA', 'AUN_NO_CURSA');
$stmt = $conn->prepare("SELECT id, nivel FROM asignatura WHERE carrera_id = ?");
$stmt->bind_param("i", $carrera);
$stmt->execute();
$res = $stmt->get_result();
$materias = array();     // id de la asignatura => estado
while ($fila = $res->fetch_assoc()) {
    $id = (int) $fila['id'];
    if (isset($estadosRecibidos[$id]) && in_array($estadosRecibidos[$id], $estadosValidos, true)) {
        $materias[$id] = $estadosRecibidos[$id];
    } else {
        $materias[$id] = ((int) $fila['nivel'] < $nivel) ? 'APROBADA' : 'AUN_NO_CURSA';
    }
}

// ---------- Guardado, sin transacciones ----------
// Paso 1: el usuario con perfil ESTUDIANTE
$nombreUsuario = $nombre . ' ' . $apellido;
if (largo($nombreUsuario) > 80) {
    preg_match('/^.{0,80}/us', $nombreUsuario, $corte);
    $nombreUsuario = $corte[0];
}
$stmt = $conn->prepare("INSERT INTO usuario (username, clave, nombre, perfil, carrera_id, activo)
                        VALUES (?, ?, ?, 'ESTUDIANTE', NULL, 1)");
$stmt->bind_param("sss", $username, $clave, $nombreUsuario);
if (!$stmt->execute()) {
    if ($stmt->errno == 1062) {
        echo json_encode(array("error" => "Ya existe un usuario con ese nombre de usuario"));
    } else {
        echo json_encode(array("error" => "No se pudo crear el usuario del estudiante"));
    }
    exit;
}
$usuarioId = $conn->insert_id;

// Paso 2: el estudiante, con el id del usuario recién creado
$stmt = $conn->prepare("INSERT INTO estudiante (cedula, usuario_id, codigo_rfid, nombre, apellido, correo, carrera_id, nivel)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sissssii", $cedula, $usuarioId, $codigo, $nombre, $apellido, $correo, $carrera, $nivel);
if (!$stmt->execute()) {
    $duplicado = ($stmt->errno == 1062);
    deshacer($conn, '', $usuarioId);              
    if ($duplicado) {
        echo json_encode(array("error" => "La cédula o la tarjeta ya están registradas"));
    } else {
        echo json_encode(array("error" => "No se pudo guardar el estudiante"));
    }
    exit;
}

// Paso 3: una fila de registro_academico por cada materia
$stmt = $conn->prepare("INSERT INTO registro_academico (estudiante_cedula, asignatura_id, estado) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $cedula, $asignaturaId, $estado);
foreach ($materias as $asignaturaId => $estado) {
    if (!$stmt->execute()) {
        deshacer($conn, $cedula, $usuarioId);     
        echo json_encode(array("error" => "No se pudo guardar la tabla de materias; no se guardó nada"));
        exit;
    }
}

echo json_encode(array("success" => true));
$conn->close();