<?php
require_once __DIR__ . '/../models/conexion.php';
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/funciones.php';
require_once __DIR__ . '/../fpdf19/fpdf.php';

// El guardia va ANTES de generar cualquier salida
$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR', 'DOCENTE'));

// FPDF trabaja en Windows-1252: se convierte el texto de la base (UTF-8) para ver tildes y eñes
function t($texto) {
    $r = iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $texto);
    return $r === false ? (string) $texto : $r;
}

// Termina con un mensaje corto (sin PDF)
function terminar($codigo, $mensaje) {
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

$grupoId = filter_input(INPUT_GET, 'grupo', FILTER_VALIDATE_INT);
$fecha = isset($_GET['fecha']) && is_string($_GET['fecha']) ? trim($_GET['fecha']) : '';

if (!$grupoId || $grupoId <= 0) {
    terminar(400, 'Grupo no válido');
}
$partes = explode('-', $fecha);
if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $fecha) || !checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
    terminar(400, 'Fecha no válida (use el formato AAAA-MM-DD)');
}

$conn = conectar();

// El grupo debe ser del usuario: el DOCENTE el suyo, el COORDINADOR uno de su carrera
$stmt = $conn->prepare("SELECT g.paralelo, g.docente_id, a.nombre AS asignatura, a.carrera_id, p.nombre AS periodo
                        FROM grupo g
                        JOIN asignatura a ON a.id = g.asignatura_id
                        JOIN periodo p ON p.id = g.periodo_id
                        WHERE g.id = ?");
$stmt->bind_param("i", $grupoId);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$permitido = false;
if ($grupo) {
    if ($usuario['perfil'] === 'ADMIN') {
        $permitido = true;
    } elseif ($usuario['perfil'] === 'DOCENTE') {
        $permitido = ((int) $grupo['docente_id'] === (int) $usuario['id']);
    } elseif ($usuario['perfil'] === 'COORDINADOR') {
        $permitido = ((int) $grupo['carrera_id'] === (int) $usuario['carrera_id']);
    }
}
if (!$permitido) {
    $conn->close();
    terminar(403, 'No tiene permiso para ver el reporte de este grupo');
}

// Cada estudiante matriculado con PRESENTE o FALTA en la fecha elegida.
// Si la fecha no cae en un dia de clase del grupo, no sale ninguna fila
$stmt = $conn->prepare("SELECT e.cedula, e.apellido, e.nombre, h.hora_inicio, a.hora AS hora_llegada,
                               IF(a.id IS NULL, 'FALTA', 'PRESENTE') AS estado
                        FROM matricula m
                        JOIN estudiante e ON e.cedula = m.estudiante_cedula
                        JOIN horario h ON h.grupo_id = m.grupo_id
                        LEFT JOIN asistencia a ON a.estudiante_cedula = e.cedula AND a.horario_id = h.id
                                              AND a.fecha = ?
                        WHERE m.grupo_id = ? AND h.dia_semana = WEEKDAY(?) + 1
                        ORDER BY e.apellido, e.nombre");
$stmt->bind_param("sis", $fecha, $grupoId, $fecha);
if (!$stmt->execute()) {
    $conn->close();
    terminar(500, 'No se pudo consultar la asistencia');
}
$res = $stmt->get_result();
$filas = array();
while ($fila = $res->fetch_assoc()) {
    $filas[] = $fila;
}
$stmt->close();
$conn->close();

// ---------- PDF ----------
class PDF extends FPDF {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->Cell(0, 10, t('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}

$dias = array('', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo');
$fechaObj = new DateTime($fecha);
$fechaTexto = $dias[(int) $fechaObj->format('N')] . ' ' . $fechaObj->format('d/m/Y');

// Anchos de las columnas (suman 190)
$ancho = array(10, 28, 50, 50, 25, 27);

function encabezadoTabla($pdf, $ancho) {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetFillColor(33, 37, 41);
    $pdf->SetTextColor(255, 255, 255);
    $titulos = array('N.º', 'Cédula', 'Apellido', 'Nombre', 'Llegada', 'Estado');
    for ($i = 0; $i < count($titulos); $i++) {
        $pdf->Cell($ancho[$i], 8, t($titulos[$i]), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetTitle(t('Reporte de asistencia'));
$pdf->AddPage();

$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 10, t('Reporte de asistencia por clase'), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Helvetica', '', 11);
$pdf->Cell(0, 6, t('Periodo: ' . $grupo['periodo']), 0, 1);
$pdf->Cell(0, 6, t('Asignatura: ' . $grupo['asignatura']), 0, 1);
$pdf->Cell(0, 6, t('Paralelo: ' . $grupo['paralelo']), 0, 1);
$pdf->Cell(0, 6, t('Fecha: ' . $fechaTexto), 0, 1);
$pdf->Ln(4);

encabezadoTabla($pdf, $ancho);

$presentes = 0;
$faltas = 0;
$pdf->SetFont('Helvetica', '', 10);

if (count($filas) == 0) {
    $pdf->Cell(array_sum($ancho), 8, t('No hay clase de este grupo en la fecha indicada.'), 1, 1, 'C');
} else {
    foreach ($filas as $i => $f) {

        if ($pdf->GetY() > 265) {       // nueva hoja: se repite el encabezado
            $pdf->AddPage();
            encabezadoTabla($pdf, $ancho);
            $pdf->SetFont('Helvetica', '', 10);
        }

        $llegada = $f['hora_llegada'] === null ? '-' : substr($f['hora_llegada'], 0, 5);
        if ($f['estado'] === 'PRESENTE') {
            $presentes++;
        } else {
            $faltas++;
        }

        $pdf->Cell($ancho[0], 7, $i + 1, 1, 0, 'C');
        $pdf->Cell($ancho[1], 7, t($f['cedula']), 1, 0, 'C');
        $pdf->Cell($ancho[2], 7, t($f['apellido']), 1, 0);
        $pdf->Cell($ancho[3], 7, t($f['nombre']), 1, 0);
        $pdf->Cell($ancho[4], 7, $llegada, 1, 0, 'C');
        $pdf->Cell($ancho[5], 7, $f['estado'], 1, 1, 'C');
    }
}

// Fila Total
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetFillColor(222, 226, 230);
$pdf->Cell($ancho[0] + $ancho[1] + $ancho[2] + $ancho[3], 8, 'Total de estudiantes: ' . count($filas), 1, 0, 'L', true);
$pdf->Cell($ancho[4] + $ancho[5], 8, 'Presentes: ' . $presentes . '   Faltas: ' . $faltas, 1, 1, 'C', true);

$pdf->Output('I', 'asistencia_clase.pdf');
