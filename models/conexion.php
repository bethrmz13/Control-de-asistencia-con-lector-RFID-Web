<?php
require_once __DIR__ . '/config.php';

// Devuelve la conexion a la base de datos (mysqli, como en clase)
function conectar() {
    $cfg = configuracion();
    $conn = new mysqli($cfg['host'], $cfg['usuario'], $cfg['clave'], $cfg['bd']);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("error" => "No se pudo conectar a la base de datos"));
        exit;
    }
    $conn->set_charset("utf8mb4");
    // Misma hora de Ecuador para PHP y para MySQL, este donde este el servidor
    $conn->query("SET time_zone = '-05:00'");
    return $conn;
}
