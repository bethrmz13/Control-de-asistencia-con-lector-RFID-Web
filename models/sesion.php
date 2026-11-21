<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos del usuario que inicio sesion (id, username, nombre, perfil, carrera_id) o null
function usuarioActual() {
    return isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

// GUARDIA. Va al inicio de CADA archivo de models/ que devuelve datos o cambia algo.
// Si no hay sesion o el perfil no esta en la lista, responde error y corta.
// Ejemplo: $usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));
function exigirPerfil($perfiles) {
    $u = usuarioActual();
    if ($u === null || !in_array($u['perfil'], $perfiles)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array("error" => "No tiene permiso para esta accion"));
        exit;
    }
    return $u;
}
