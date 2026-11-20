<?php
// Copie este archivo como config.local.php y cambie los datos SOLO si su base de datos
// no es la de XAMPP (por ejemplo, en el hosting).
// config.local.php NO se sube a GitHub (esta en .gitignore): ahi van las claves reales.
return array(
    'host' => 'localhost',        // En el hosting, el panel indica otro (no es localhost)
    'usuario' => 'root',
    'clave' => '',
    'bd' => 'asistencia_web',
    'depuracion' => true          // true: muestra los errores de PHP (local). En el hosting: false
);
