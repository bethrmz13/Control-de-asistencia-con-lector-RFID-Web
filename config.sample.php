<?php
// Copie este archivo como config.local.php y cambie los datos SOLO si su base de datos
// no es la de XAMPP (por ejemplo, en el hosting).
// config.local.php NO se sube a GitHub (esta en .gitignore): ahi van las claves reales.
return array(
    'host' => 'sql102.infinityfree.com ',        // En el hosting, el panel indica otro (no es localhost)
    'usuario' => 'if0_43500994',
    'clave' => 'GxSLhOcLpI',
    'bd' => 'if0_43500994_asistencia_web ',
    'depuracion' => false          // true: muestra los errores de PHP (local). En el hosting: false
);
