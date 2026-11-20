<?php
// Punto de entrada. Todo pasa por aqui: index.php?accion=nombre_de_la_pagina
require_once "controllers/controller.php";

$mvc = new MvcController();
$mvc->plantilla();
