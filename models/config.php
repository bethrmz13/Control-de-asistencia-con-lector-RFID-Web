<?php
// Configuracion comun: hora de Ecuador, errores y datos de la base de datos
date_default_timezone_set('America/Guayaquil');

// En PHP 8.1 o superior mysqli lanza excepciones por defecto. Se desactivan para manejar
// los errores como en clase: revisando si la consulta devolvio false.
mysqli_report(MYSQLI_REPORT_OFF);

// Lee config.local.php si existe (hosting). Si no, usa los datos de XAMPP.
function configuracion() {
    static $cfg = null;
    if ($cfg === null) {
        $archivo = __DIR__ . '/../config.local.php';
        if (file_exists($archivo)) {
            $cfg = include $archivo;
        } else {
            $cfg = array(
                'host' => 'localhost',
                'usuario' => 'root',
                'clave' => '',
                'bd' => 'asistencia_web',
                'depuracion' => true
            );
        }
    }
    return $cfg;
}

$cfgInicial = configuracion();
error_reporting(E_ALL);
ini_set('display_errors', $cfgInicial['depuracion'] ? '1' : '0');
