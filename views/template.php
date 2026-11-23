<?php
// Plantilla base: banner, menu y seccion de trabajo (como pide la guia)
$u = usuarioActual();
$kiosco = ($u !== null && $u['perfil'] === 'REGISTRO');   // puesto de lectura: sin menu
$menu = EnlacesModel::menu($u);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de asistencia RFID</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/estilo.css">
    <!-- jQuery y Bootstrap van aqui, en el head, para que las vistas puedan usarlos en sus scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/comun.js"></script>
</head>
<body>

    <header class="banner text-center">
        <h1>Control de asistencia con lector RFID</h1>
        <p>Computación Visual - FISEI, Universidad Técnica de Ambato</p>
    </header>

    <?php if (!$kiosco) { ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menu['publico'] as $item) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?accion=<?php echo esc($item[0]); ?>"><?php echo esc($item[1]); ?></a>
                        </li>
                    <?php } ?>

                    <?php if (count($menu['sistema']) > 0) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Sistema</a>
                            <ul class="dropdown-menu">
                                <?php foreach ($menu['sistema'] as $item) { ?>
                                    <li><a class="dropdown-item" href="index.php?accion=<?php echo esc($item[0]); ?>"><?php echo esc($item[1]); ?></a></li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if ($u === null) { ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?accion=login">Ingresar</a></li>
                    <?php } else { ?>
                        <li class="nav-item"><span class="navbar-text me-3"><?php echo esc($u['nombre']); ?> (<?php echo esc($u['perfil']); ?>)</span></li>
                        <li class="nav-item"><a class="nav-link" href="models/logout.php">Salir</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php } ?>

    <?php if ($kiosco) { ?>
        <div class="container text-end my-2">
            <a href="models/logout.php"
            class="btn btn-outline-secondary btn-sm"
            onclick="return confirm('¿Desea cerrar la sesión?');">
                Salir
            </a>
        </div>
    <?php } ?>

    <!-- Seccion de trabajo: aqui se muestra la pagina pedida -->
    <main class="container my-4">
        <?php $this->enlacesPagina(); ?>
    </main>

    <footer class="text-center">
        <div class="container">Control de asistencia con lector RFID &copy; <?php echo date('Y'); ?></div>
    </footer>

</body>
</html>
