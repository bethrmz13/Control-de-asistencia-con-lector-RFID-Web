<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
require_once __DIR__ . '/../funciones.php';

exigirPerfil(array('ADMIN', 'COORDINADOR'));

// Devuelve el grupo (id, periodo_id, docente_id) si es del periodo ACTIVO y el usuario puede tocarlo.
function grupoDelUsuario($conn, $usuario, $grupoId) {
    $periodo = periodoActivo($conn);
    if ($periodo === null) {
        return null;
    }
    $periodoId = (int) $periodo['id'];
    if ($usuario['perfil'] === 'COORDINADOR') {
        $carrera = (int) $usuario['carrera_id'];
        $stmt = $conn->prepare("SELECT g.id, g.periodo_id, g.docente_id
                                FROM grupo g
                                JOIN asignatura a ON a.id = g.asignatura_id
                                WHERE g.id = ? AND g.periodo_id = ? AND a.carrera_id = ?");
        $stmt->bind_param("iii", $grupoId, $periodoId, $carrera);
    } else {
        $stmt = $conn->prepare("SELECT g.id, g.periodo_id, g.docente_id
                                FROM grupo g
                                WHERE g.id = ? AND g.periodo_id = ?");
        $stmt->bind_param("ii", $grupoId, $periodoId);
    }
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    return $fila ? $fila : null;
}

function normalizarHora($texto) {
    $texto = trim((string) $texto);
    if (!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $texto, $p)) {
        return '';
    }
    return $p[1] . ':' . $p[2] . ':00';
}

function revisarHorario($conn, $grupo, $dia, $inicio, $fin, $aula, $excluirId) {
    $grupoId = (int) $grupo['id'];
    $periodoId = (int) $grupo['periodo_id'];

    if ($inicio >= $fin) {
        return 'La hora de inicio debe ser menor que la de fin';
    }

    // 2. No se cruza con otro horario del MISMO grupo el mismo día
    $stmt = $conn->prepare("SELECT hora_inicio, hora_fin FROM horario
                            WHERE grupo_id = ? AND dia_semana = ?
                              AND hora_inicio < ? AND ? < hora_fin AND id <> ?
                            LIMIT 1");
    $stmt->bind_param("iissi", $grupoId, $dia, $fin, $inicio, $excluirId);
    $stmt->execute();
    $otro = $stmt->get_result()->fetch_assoc();
    if ($otro) {
        return 'Se cruza con otra clase de este grupo (' . substr($otro['hora_inicio'], 0, 5) . ' a ' . substr($otro['hora_fin'], 0, 5) . ')';
    }

    // 3. El mismo DOCENTE no puede tener dos clases a la vez (todos sus grupos del periodo activo)
    if ($grupo['docente_id'] !== null) {
        $docenteId = (int) $grupo['docente_id'];
        $stmt = $conn->prepare("SELECT a.nombre, g.paralelo
                                FROM horario h
                                JOIN grupo g ON g.id = h.grupo_id
                                JOIN asignatura a ON a.id = g.asignatura_id
                                WHERE g.docente_id = ? AND g.periodo_id = ? AND g.id <> ?
                                  AND h.dia_semana = ? AND h.hora_inicio < ? AND ? < h.hora_fin
                                LIMIT 1");
        $stmt->bind_param("iiiiss", $docenteId, $periodoId, $grupoId, $dia, $fin, $inicio);
        $stmt->execute();
        $otro = $stmt->get_result()->fetch_assoc();
        if ($otro) {
            return 'El docente del grupo ya tiene clase a esa hora: ' . $otro['nombre'] . ' (paralelo ' . $otro['paralelo'] . ')';
        }
    }

    // 4. La misma AULA no puede tener dos clases a la vez
    $stmt = $conn->prepare("SELECT a.nombre, g.paralelo
                            FROM horario h
                            JOIN grupo g ON g.id = h.grupo_id
                            JOIN asignatura a ON a.id = g.asignatura_id
                            WHERE g.periodo_id = ? AND h.aula = ? AND h.dia_semana = ?
                              AND h.hora_inicio < ? AND ? < h.hora_fin AND h.id <> ?
                            LIMIT 1");
    $stmt->bind_param("isissi", $periodoId, $aula, $dia, $fin, $inicio, $excluirId);
    $stmt->execute();
    $otro = $stmt->get_result()->fetch_assoc();
    if ($otro) {
        return 'El aula ' . $aula . ' ya está ocupada a esa hora por ' . $otro['nombre'] . ' (paralelo ' . $otro['paralelo'] . ')';
    }

    // 5. No choca con las otras materias de los estudiantes YA matriculados en este grupo
    $stmt = $conn->prepare("SELECT e.nombre, e.apellido, a.nombre AS asignatura, g2.paralelo
                            FROM matricula m1
                            JOIN estudiante e ON e.cedula = m1.estudiante_cedula
                            JOIN matricula m2 ON m2.estudiante_cedula = m1.estudiante_cedula
                                             AND m2.grupo_id <> m1.grupo_id
                            JOIN grupo g2 ON g2.id = m2.grupo_id AND g2.periodo_id = ?
                            JOIN horario h2 ON h2.grupo_id = g2.id
                            JOIN asignatura a ON a.id = g2.asignatura_id
                            WHERE m1.grupo_id = ? AND h2.dia_semana = ?
                              AND h2.hora_inicio < ? AND ? < h2.hora_fin
                            ORDER BY e.apellido, e.nombre
                            LIMIT 1");
    $stmt->bind_param("iiiss", $periodoId, $grupoId, $dia, $fin, $inicio);
    $stmt->execute();
    $otro = $stmt->get_result()->fetch_assoc();
    if ($otro) {
        return 'Choca con el horario del estudiante ' . $otro['apellido'] . ' ' . $otro['nombre']
            . ', que ya tiene ' . $otro['asignatura'] . ' (paralelo ' . $otro['paralelo'] . ') a esa hora';
    }

    return '';
}