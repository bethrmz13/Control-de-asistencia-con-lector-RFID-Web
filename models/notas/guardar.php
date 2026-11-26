<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'DOCENTE'));

$grupoId = filter_input(INPUT_POST, 'grupo', FILTER_VALIDATE_INT);
if (!$grupoId || $grupoId <= 0) {
    echo json_encode(array("error" => "Seleccione un grupo válido"));
    exit;
}
// Llega como notas[0][cedula], notas[0][nota], notas[1][cedula]...
if (!isset($_POST['notas']) || !is_array($_POST['notas']) || count($_POST['notas']) == 0) {
    echo json_encode(array("error" => "No llegaron notas para guardar"));
    exit;
}
$enviadas = $_POST['notas'];

$conn = conectar();

// (1) El grupo debe ser del periodo activo y, si es DOCENTE, suyo. El ADMIN puede con todos
$periodo = periodoActivo($conn);
if ($periodo === null) {
    echo json_encode(array("error" => "No hay un periodo activo"));
    $conn->close();
    exit;
}
$periodoId = (int) $periodo['id'];

$stmt = $conn->prepare("SELECT docente_id FROM grupo WHERE id = ? AND periodo_id = ?");
$stmt->bind_param("ii", $grupoId, $periodoId);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$grupo) {
    echo json_encode(array("error" => "El grupo no existe en el periodo activo"));
    $conn->close();
    exit;
}
if ($usuario['perfil'] === 'DOCENTE' && (int) $grupo['docente_id'] !== (int) $usuario['id']) {
    echo json_encode(array("error" => "Solo puede registrar notas de sus propios grupos"));
    $conn->close();
    exit;
}

// Matriculas actuales del grupo (con su nota). Sirve para saber quien existe (con un SELECT,
// no con affected_rows) y para poder devolver la nota anterior si algo falla
$stmt = $conn->prepare("SELECT e.cedula, e.apellido, e.nombre, m.nota
                        FROM matricula m
                        JOIN estudiante e ON e.cedula = m.estudiante_cedula
                        WHERE m.grupo_id = ?");
$stmt->bind_param("i", $grupoId);
$stmt->execute();
$res = $stmt->get_result();
$actuales = array();
while ($fila = $res->fetch_assoc()) {
    $actuales[$fila['cedula']] = $fila;
}
$stmt->close();

// (2) Se validan TODAS las notas antes de guardar ninguna
$nuevas = array();      // cedula => nota (float o null)
$errores = array();
foreach ($enviadas as $item) {
    if (!is_array($item) || !isset($item['cedula']) || !is_string($item['cedula'])) {
        $errores[] = "Hay un dato mal formado";
        continue;
    }
    $cedula = trim($item['cedula']);
    if (!isset($actuales[$cedula])) {
        $errores[] = "La cédula " . $cedula . " no está matriculada en este grupo";
        continue;
    }
    $nombre = $actuales[$cedula]['apellido'] . ' ' . $actuales[$cedula]['nombre'];

    $texto = isset($item['nota']) && is_string($item['nota']) ? trim($item['nota']) : '';
    $texto = str_replace(',', '.', $texto);        // acepta coma decimal
    if ($texto === '') {
        $nuevas[$cedula] = null;                   // vacia: se guarda NULL
    } elseif (preg_match('/^(10(\.0{1,2})?|[0-9](\.[0-9]{1,2})?)$/', $texto)) {
        $nuevas[$cedula] = (float) $texto;         // de 0 a 10, hasta 2 decimales
    } else {
        $errores[] = "La nota de " . $nombre . " no es válida (debe ser un número de 0 a 10 con hasta 2 decimales)";
    }
}
if (count($errores) > 0) {
    echo json_encode(array("error" => "No se guardó ninguna nota. " . implode('. ', $errores)));
    $conn->close();
    exit;
}

// (3) Se guarda una por una, revisando cada UPDATE. Solo las que cambiaron
$stmt = $conn->prepare("UPDATE matricula SET nota = ? WHERE grupo_id = ? AND estudiante_cedula = ?");
$guardadas = array();   // cedula => nota anterior (por si hay que devolverlas)
foreach ($nuevas as $cedula => $nota) {

    $anterior = $actuales[$cedula]['nota'] === null ? null : (float) $actuales[$cedula]['nota'];
    if ($anterior === $nota || ($anterior !== null && $nota !== null && abs($anterior - $nota) < 0.0001)) {
        continue;   // no cambio
    }

    $stmt->bind_param("dis", $nota, $grupoId, $cedula);
    if (!$stmt->execute()) {
        // Fallo: se devuelven las ya guardadas a su nota anterior
        $fallos = 0;
        foreach ($guardadas as $ced => $vieja) {
            $stmt->bind_param("dis", $vieja, $grupoId, $ced);
            if (!$stmt->execute()) {
                $fallos++;
            }
        }
        $quien = $actuales[$cedula]['apellido'] . ' ' . $actuales[$cedula]['nombre'];
        $mensaje = "No se pudo guardar la nota de " . $quien . ". Se devolvieron las notas anteriores";
        if ($fallos > 0) {
            $mensaje = "No se pudo guardar la nota de " . $quien . " y no se pudieron devolver " . $fallos
                . " nota(s) a su valor anterior. Revise la lista y avise al administrador";
        }
        echo json_encode(array("error" => $mensaje));
        $conn->close();
        exit;
    }
    $guardadas[$cedula] = $anterior;
}

echo json_encode(array("success" => true, "guardadas" => count($guardadas)));
$conn->close();
