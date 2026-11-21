<?php
// Funciones comunes. No se llaman entre modulos: solo usan estas.

// Escapa un texto para mostrarlo en HTML
function esc($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Periodo con activo = 1 (arreglo asociativo) o null si no hay
function periodoActivo($conn) {
    $res = $conn->query("SELECT * FROM periodo WHERE activo = 1 LIMIT 1");
    if ($res && $fila = $res->fetch_assoc()) {
        return $fila;
    }
    return null;
}

// Fecha y hora del SERVIDOR (Ecuador). Nunca se usa la hora del navegador.
// Si llega $simulada (formato 2026-11-09 16:10) se usa esa, para las pruebas.
// Devuelve un DateTime, o false si la fecha simulada esta mal escrita.
function ahora($simulada = '') {
    if ($simulada === '' || $simulada === null) {
        return new DateTime('now');
    }
    $d = DateTime::createFromFormat('Y-m-d H:i', $simulada);
    $errores = DateTime::getLastErrors();
    if ($d === false || ($errores && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        return false;
    }
    return $d;
}

// Cedula ecuatoriana: 10 digitos, provincia 01-24 (o 30), tercer digito menor a 6 y
// digito verificador correcto
function validarCedula($cedula) {
    if (!is_string($cedula) || !preg_match('/^[0-9]{10}$/', $cedula)) {
        return false;
    }
    $provincia = (int) substr($cedula, 0, 2);
    if ($provincia < 1 || ($provincia > 24 && $provincia != 30)) {
        return false;
    }
    if ((int) $cedula[2] > 5) {
        return false;
    }
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $d = (int) $cedula[$i];
        if ($i % 2 == 0) {
            $d = $d * 2;
            if ($d > 9) {
                $d = $d - 9;
            }
        }
        $suma = $suma + $d;
    }
    $verificador = (10 - ($suma % 10)) % 10;
    return $verificador == (int) $cedula[9];
}

// Codigo de la tarjeta: exactamente 10 digitos (texto, con ceros a la izquierda)
function validarCodigoTarjeta($codigo) {
    return is_string($codigo) && preg_match('/^[0-9]{10}$/', $codigo) === 1;
}
