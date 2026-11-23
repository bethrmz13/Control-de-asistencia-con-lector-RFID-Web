<?php
// models/matricula/matricular.php
// Matricula al estudiante en un grupo. Devuelve el PRIMER error que encuentre, en este orden:
// 1) matricula abierta, 2) grupo del periodo activo, 3) materia permitida, 4) no repetida,
// 5) maximo de materias, 6) sin choque de horarios.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ESTUDIANTE'));

$grupoId = isset($_POST['grupo']) && ctype_digit((string) $_POST['grupo']) ? (int) $_POST['grupo'] : 0;
if ($grupoId <= 0) {
    echo json_encode(array('error' => 'Seleccione un grupo'));
    exit;
}

$conn = conectar();

// Si hay un error: lo envia, cierra la conexion y termina
function fallo($conn, $mensaje) {
    echo json_encode(array('error' => $mensaje));
    $conn->close();
    exit;
}

$maximo = 5;

// El estudiante es el de la sesion
$idUsuario = (int) $usuario['id'];
$stmt = $conn->prepare('SELECT cedula, carrera_id, nivel FROM estudiante WHERE usuario_id = ?');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
if (!$est) {
    fallo($conn, 'Su usuario no esta vinculado a un estudiante');
}
$cedula = $est['cedula'];

// 1. La matricula debe estar abierta
$periodo = periodoActivo($conn);
if ($periodo === null) {
    fallo($conn, 'No hay un periodo activo');
}
if ((int) $periodo['matricula_abierta'] !== 1) {
    fallo($conn, 'Las matriculas estan cerradas');
}
$periodoId = (int) $periodo['id'];

// 2. El grupo debe ser del periodo activo
$stmt = $conn->prepare('SELECT g.asignatura_id, a.nombre, a.carrera_id, a.nivel
                        FROM grupo g
                        JOIN asignatura a ON a.id = g.asignatura_id
                        WHERE g.id = ? AND g.periodo_id = ?');
$stmt->bind_param('ii', $grupoId, $periodoId);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
if (!$grupo) {
    fallo($conn, 'El grupo no existe o no es del periodo activo');
}
$asignaturaId = (int) $grupo['asignatura_id'];

// 3. La materia debe estar permitida: de su carrera, de su nivel o inferior, y con estado
// AUN_NO_CURSA o REPROBADA en su registro academico
if ((int) $grupo['carrera_id'] !== (int) $est['carrera_id']) {
    fallo($conn, 'La materia no es de su carrera');
}
if ((int) $grupo['nivel'] > (int) $est['nivel']) {
    fallo($conn, 'La materia es de un nivel superior al suyo');
}
$stmt = $conn->prepare('SELECT estado FROM registro_academico WHERE estudiante_cedula = ? AND asignatura_id = ?');
$stmt->bind_param('si', $cedula, $asignaturaId);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
if (!$registro) {
    fallo($conn, 'La materia no esta en su registro academico');
}
if ($registro['estado'] === 'APROBADA') {
    fallo($conn, 'La materia ya esta aprobada');
}

// 4. No puede estar ya matriculado en esa asignatura en el periodo (en ningun paralelo)
$stmt = $conn->prepare('SELECT m.grupo_id FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        WHERE m.estudiante_cedula = ? AND g.asignatura_id = ? AND g.periodo_id = ?');
$stmt->bind_param('sii', $cedula, $asignaturaId, $periodoId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    fallo($conn, 'Ya esta matriculado en ' . $grupo['nombre']);
}

// 5. Maximo de materias por periodo
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM matricula m
                        JOIN grupo g ON g.id = m.grupo_id
                        WHERE m.estudiante_cedula = ? AND g.periodo_id = ?');
$stmt->bind_param('si', $cedula, $periodoId);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
if ((int) $fila['total'] >= $maximo) {
    fallo($conn, 'Solo puede matricularse en ' . $maximo . ' materias por periodo');
}

// 6. Choque de horarios: dos clases del mismo dia se cruzan si inicio1 < fin2 Y inicio2 < fin1.
// h1 son los horarios del grupo nuevo y h2 los de las materias en las que ya esta matriculado.
$stmt = $conn->prepare('SELECT a2.nombre AS asignatura
                        FROM horario h1
                        JOIN horario h2 ON h2.dia_semana = h1.dia_semana
                                       AND h2.hora_inicio < h1.hora_fin
                                       AND h1.hora_inicio < h2.hora_fin
                        JOIN matricula m ON m.grupo_id = h2.grupo_id AND m.estudiante_cedula = ?
                        JOIN grupo g2 ON g2.id = h2.grupo_id AND g2.periodo_id = ?
                        JOIN asignatura a2 ON a2.id = g2.asignatura_id
                        WHERE h1.grupo_id = ?
                        LIMIT 1');
$stmt->bind_param('sii', $cedula, $periodoId, $grupoId);
$stmt->execute();
$choque = $stmt->get_result()->fetch_assoc();
if ($choque) {
    fallo($conn, 'El horario choca con ' . $choque['asignatura']);
}

// Todo bien: se guarda
$stmt = $conn->prepare('INSERT INTO matricula (estudiante_cedula, grupo_id) VALUES (?, ?)');
$stmt->bind_param('si', $cedula, $grupoId);
if ($stmt->execute()) {
    echo json_encode(array('success' => true, 'mensaje' => 'Matriculado en ' . $grupo['nombre']));
} else {
    echo json_encode(array('error' => 'No se pudo guardar la matricula'));
}
$conn->close();
