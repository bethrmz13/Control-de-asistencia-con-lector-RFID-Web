<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$nombre = isset($_POST['nombre']) && is_string($_POST['nombre'])
    ? trim($_POST['nombre']) : '';

$nivel = filter_input(INPUT_POST, 'nivel', FILTER_VALIDATE_INT);
$carrera = filter_input(INPUT_POST, 'carrera', FILTER_VALIDATE_INT);

// Validar los datos recibidos.
if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 80) {
    echo json_encode(array(
        "error" => "Ingrese el nombre (hasta 80 caracteres)"
    ));
    exit;
}

if (!$nivel || $nivel < 1 || $nivel > 10) {
    echo json_encode(array(
        "error" => "El nivel debe estar entre 1 y 10"
    ));
    exit;
}

if (!$carrera || $carrera <= 0) {
    echo json_encode(array(
        "error" => "Seleccione la carrera"
    ));
    exit;
}

// El coordinador solamente puede registrar en su carrera.
if ($usuario['perfil'] === 'COORDINADOR' &&
    $carrera != (int) $usuario['carrera_id']) {

    echo json_encode(array(
        "error" => "Solo puede usar su carrera"
    ));
    exit;
}

$conn = conectar();

// Comprobar que la carrera exista.
$stmt = $conn->prepare(
    "SELECT id FROM carrera WHERE id = ?"
);

$stmt->bind_param("i", $carrera);
$stmt->execute();

if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(array(
        "error" => "La carrera seleccionada no existe"
    ));
    $conn->close();
    exit;
}

$stmt->close();

// Guardar la nueva asignatura.
$stmt = $conn->prepare(
    "INSERT INTO asignatura (carrera_id, nombre, nivel)
     VALUES (?, ?, ?)"
);

$stmt->bind_param("isi", $carrera, $nombre, $nivel);

if (!$stmt->execute()) {

    if ($stmt->errno == 1062) {
        echo json_encode(array(
            "error" => "Ya existe una asignatura con ese nombre en la carrera"
        ));
    } else {
        echo json_encode(array(
            "error" => "No se pudo guardar la asignatura"
        ));
    }

    $conn->close();
    exit;
}

// Obtener el ID de la asignatura creada.
$idAsignatura = $conn->insert_id;

$stmt->close();

// Agregar la asignatura al registro académico
// de todos los estudiantes de la misma carrera.
$stmt = $conn->prepare(
    "INSERT INTO registro_academico
        (estudiante_cedula, asignatura_id, estado)
     SELECT cedula, ?, 'AUN_NO_CURSA'
     FROM estudiante
     WHERE carrera_id = ?"
);

$guardado = false;

if ($stmt) {
    $stmt->bind_param("ii", $idAsignatura, $carrera);
    $guardado = $stmt->execute();
}

// Si falla el registro académico, eliminar
// la asignatura que se acaba de crear.
if (!$guardado) {

    $eliminar = $conn->prepare(
        "DELETE FROM asignatura WHERE id = ?"
    );

    $eliminado = false;

    if ($eliminar) {
        $eliminar->bind_param("i", $idAsignatura);
        $eliminado = $eliminar->execute();
    }

    if ($eliminado) {
        echo json_encode(array(
            "error" => "No se pudo generar el registro académico. Se canceló la creación de la asignatura"
        ));
    } else {
        echo json_encode(array(
            "error" => "Error al generar el registro académico y deshacer la creación. Avise al administrador"
        ));
    }

    $conn->close();
    exit;
}

$totalEstudiantes = $stmt->affected_rows;

echo json_encode(array(
    "success" => true,
    "id" => $idAsignatura,
    "estudiantes" => $totalEstudiantes
));

$conn->close();
