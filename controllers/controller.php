<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/funciones.php';
require_once __DIR__ . '/../models/model.php';

class MvcController {

    // Muestra la plantilla (banner, menu y seccion de trabajo)
    public function plantilla() {
        include __DIR__ . '/../views/template.php';
    }

    // Incluye la pagina pedida en la seccion de trabajo, si el perfil puede verla
    public function enlacesPagina() {
        $usuario = usuarioActual();
        $accion = isset($_GET['accion']) ? $_GET['accion'] : 'inicio';
        if (!is_string($accion)) {
            $accion = 'inicio';
        }

        // El puesto de lectura (perfil REGISTRO) solo ve la pantalla de lectura
        if ($usuario !== null && $usuario['perfil'] === 'REGISTRO') {
            $accion = 'registro';
        }

        include __DIR__ . '/../' . EnlacesModel::paginaPermitida($accion, $usuario);
    }
}
