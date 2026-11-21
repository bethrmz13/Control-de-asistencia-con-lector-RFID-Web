<?php
// LISTA BLANCA de paginas y quien puede abrirlas.
// perfiles = null  -> pagina publica (no pide sesion)
// perfiles = lista -> solo esos perfiles
// menu = 'publico' | 'sistema' | 'no'  -> en que parte del menu aparece
// Todas las paginas ya estan registradas: nadie necesita editar este archivo.
class EnlacesModel {

    public static $paginas = array(
        'inicio'      => array('titulo' => 'Inicio',                  'perfiles' => null, 'menu' => 'publico'),
        'nosotros'    => array('titulo' => 'Nosotros',                'perfiles' => null, 'menu' => 'publico'),
        'contactanos' => array('titulo' => 'Contáctanos',             'perfiles' => null, 'menu' => 'publico'),
        'descargas'   => array('titulo' => 'Descargas',               'perfiles' => null, 'menu' => 'publico'),
        'login'       => array('titulo' => 'Ingresar',                'perfiles' => null, 'menu' => 'no'),

        'usuarios'    => array('titulo' => 'Usuarios',                'perfiles' => array('ADMIN'), 'menu' => 'sistema'),
        'estudiantes' => array('titulo' => 'Estudiantes',             'perfiles' => array('ADMIN', 'COORDINADOR'), 'menu' => 'sistema'),
        'asignaturas' => array('titulo' => 'Asignaturas',             'perfiles' => array('ADMIN', 'COORDINADOR'), 'menu' => 'sistema'),
        'grupos'      => array('titulo' => 'Grupos',                  'perfiles' => array('ADMIN', 'COORDINADOR'), 'menu' => 'sistema'),
        'horarios'    => array('titulo' => 'Horarios',                'perfiles' => array('ADMIN', 'COORDINADOR'), 'menu' => 'sistema'),
        'periodos'    => array('titulo' => 'Periodos',                'perfiles' => array('ADMIN'), 'menu' => 'sistema'),
        'registro'    => array('titulo' => 'Registro de asistencia',  'perfiles' => array('ADMIN', 'REGISTRO'), 'menu' => 'sistema'),
        'notas'       => array('titulo' => 'Notas',                   'perfiles' => array('ADMIN', 'DOCENTE'), 'menu' => 'sistema'),
        'reportes'    => array('titulo' => 'Reportes',                'perfiles' => array('ADMIN', 'COORDINADOR', 'DOCENTE'), 'menu' => 'sistema'),
        'inscritos'   => array('titulo' => 'Inscritos',               'perfiles' => array('ADMIN', 'COORDINADOR', 'DOCENTE'), 'menu' => 'sistema'),
        'mihorario'   => array('titulo' => 'Mi horario',              'perfiles' => array('DOCENTE', 'ESTUDIANTE'), 'menu' => 'sistema'),
        'matricula'   => array('titulo' => 'Matrícula',               'perfiles' => array('ESTUDIANTE'), 'menu' => 'sistema'),
        'consulta'    => array('titulo' => 'Mis notas y asistencias', 'perfiles' => array('ESTUDIANTE'), 'menu' => 'sistema')
    );

    // true si el usuario (o nadie, si es publica) puede abrir la pagina
    public static function puede($accion, $usuario) {
        if (!isset(self::$paginas[$accion])) {
            return false;
        }
        $perfiles = self::$paginas[$accion]['perfiles'];
        if ($perfiles === null) {
            return true;
        }
        if ($usuario === null) {
            return false;
        }
        return in_array($usuario['perfil'], $perfiles);
    }

    // Archivo de la vista que se debe mostrar
    public static function paginaPermitida($accion, $usuario) {
        if (!isset(self::$paginas[$accion])) {
            return 'views/noexiste.php';
        }
        if (self::puede($accion, $usuario)) {
            return 'views/' . $accion . '.php';
        }
        if ($usuario === null) {
            return 'views/login.php';
        }
        return 'views/sinpermiso.php';
    }

    // Opciones del menu para este usuario: array('publico' => [...], 'sistema' => [...])
    public static function menu($usuario) {
        $menu = array('publico' => array(), 'sistema' => array());
        foreach (self::$paginas as $accion => $info) {
            if ($info['menu'] === 'no') {
                continue;
            }
            if (self::puede($accion, $usuario)) {
                $menu[$info['menu']][] = array($accion, $info['titulo']);
            }
        }
        return $menu;
    }
}
