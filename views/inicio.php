<style>
    #inicio {
        --inicio-azul: #0d3b66;
        --inicio-borde: #d8e6f0;
    }

    #inicio .inicio-portada {
        background: linear-gradient(125deg, #eef7ff, #f7fbfe);
        border: 1px solid var(--inicio-borde);
    }

    #inicio .inicio-titulo {
        color: var(--inicio-azul);
    }

    #inicio .inicio-carrusel {
        background: #f5f9fc;
        border: 1px solid var(--inicio-borde);
    }

    #inicio .inicio-ilustracion {
        display: block;
        width: 100%;
        height: auto;
    }

    #inicio .inicio-controles {
        border-top: 1px solid var(--inicio-borde);
    }

    #inicio .carousel-indicators {
        position: static;
        margin: 0;
    }

    #inicio .carousel-indicators [data-bs-target] {
        background-color: var(--inicio-azul);
        height: .3rem;
    }

    #inicio .inicio-tarjeta {
        border: 1px solid var(--inicio-borde);
        border-top: .2rem solid #1d6fa5;
    }

    @media (max-width: 767.98px) {
        #inicio .inicio-carrusel-texto {
            min-height: 10rem;
        }
    }
</style>

<div id="inicio">
    <section class="inicio-portada rounded-3 p-4 p-md-5 mb-4" aria-labelledby="inicioTitulo">
        <h2 id="inicioTitulo" class="inicio-titulo display-5 fw-bold mb-3">Control de Asistencia con Lector RFID</h2>
        <p class="lead mb-4">Sistema web desarrollado para gestionar el registro de asistencia de estudiantes mediante tarjetas RFID, facilitando el control de horarios y la administración académica.</p>
        <a class="btn btn-primary" href="index.php?accion=nosotros">Conoce el proyecto</a>
    </section>

    <section class="mb-5" aria-labelledby="inicioCarruselTitulo">
        <h2 id="inicioCarruselTitulo" class="inicio-titulo h3 mb-3">Asistencia y gestión académica</h2>
        <div id="inicioCarrusel" class="carousel slide inicio-carrusel rounded-3 overflow-hidden" aria-labelledby="inicioCarruselTitulo">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-7">
                            <img src="images/inicio-rfid.svg" class="inicio-ilustracion" width="640" height="360" alt="Una tarjeta RFID junto a un lector que registra su lectura">
                        </div>
                        <div class="col-md-5 p-4 p-lg-5 inicio-carrusel-texto">
                            <h3 class="inicio-titulo h4">Registro con tarjetas RFID</h3>
                            <p class="mb-0">El lector identifica la tarjeta del estudiante para asociar el registro de asistencia con su clase.</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-7">
                            <img src="images/inicio-horarios.svg" class="inicio-ilustracion" width="640" height="360" alt="Calendario semanal con bloques de clase y un reloj">
                        </div>
                        <div class="col-md-5 p-4 p-lg-5 inicio-carrusel-texto">
                            <h3 class="inicio-titulo h4">Organización de horarios</h3>
                            <p class="mb-0">La administración académica organiza las clases de cada grupo por día, hora y aula.</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-7">
                            <img src="images/inicio-consulta.svg" class="inicio-ilustracion" width="640" height="360" alt="Una lupa destaca una fila en una lista de asistencia">
                        </div>
                        <div class="col-md-5 p-4 p-lg-5 inicio-carrusel-texto">
                            <h3 class="inicio-titulo h4">Consulta de asistencia</h3>
                            <p class="mb-0">Los registros de asistencia sirven para revisar la participación de los estudiantes en sus clases.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="inicio-controles d-flex flex-wrap justify-content-center align-items-center gap-2 p-2 p-sm-3">
                <button type="button" class="btn btn-outline-primary" data-bs-target="#inicioCarrusel" data-bs-slide="prev">Anterior</button>
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#inicioCarrusel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Registro con tarjetas RFID"></button>
                    <button type="button" data-bs-target="#inicioCarrusel" data-bs-slide-to="1" aria-label="Organización de horarios"></button>
                    <button type="button" data-bs-target="#inicioCarrusel" data-bs-slide-to="2" aria-label="Consulta de asistencia"></button>
                </div>
                <button type="button" class="btn btn-outline-primary" data-bs-target="#inicioCarrusel" data-bs-slide="next">Siguiente</button>
            </div>
            <p id="inicioCarruselEstado" class="visually-hidden" role="status"></p>
        </div>
    </section>

    <section class="mb-5" aria-labelledby="inicioCaracteristicas">
        <h2 id="inicioCaracteristicas" class="inicio-titulo h3 mb-3">Características del sistema</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
            <div class="col">
                <article class="card inicio-tarjeta h-100">
                    <div class="card-body">
                        <h3 class="inicio-titulo h5">Registro de asistencia con RFID</h3>
                        <p class="card-text">Asociar la lectura de la tarjeta con el estudiante y con su horario de clase.</p>
                    </div>
                </article>
            </div>
            <div class="col">
                <article class="card inicio-tarjeta h-100">
                    <div class="card-body">
                        <h3 class="inicio-titulo h5">Gestión de estudiantes</h3>
                        <p class="card-text">Organizar los datos académicos de los estudiantes y vincular sus tarjetas.</p>
                    </div>
                </article>
            </div>
            <div class="col">
                <article class="card inicio-tarjeta h-100">
                    <div class="card-body">
                        <h3 class="inicio-titulo h5">Administración de horarios</h3>
                        <p class="card-text">Definir los días, las horas y las aulas de las clases de cada grupo.</p>
                    </div>
                </article>
            </div>
            <div class="col">
                <article class="card inicio-tarjeta h-100">
                    <div class="card-body">
                        <h3 class="inicio-titulo h5">Consulta de información académica</h3>
                        <p class="card-text">Reunir los registros de asistencia, notas y horarios para su consulta.</p>
                    </div>
                </article>
            </div>
            <div class="col">
                <article class="card inicio-tarjeta h-100">
                    <div class="card-body">
                        <h3 class="inicio-titulo h5">Control por perfiles de usuario</h3>
                        <p class="card-text">Asignar el acceso a las herramientas según las responsabilidades de cada perfil.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</div>

<script>
    $(function () {
        var $carrusel = $('#inicioCarrusel');
        var total = $carrusel.find('.carousel-item').length;
        bootstrap.Carousel.getOrCreateInstance($carrusel[0]);

        $carrusel.on('slid.bs.carousel', function (evento) {
            var titulo = $(evento.relatedTarget).find('h3').text();
            $('#inicioCarruselEstado').text('Diapositiva ' + (evento.to + 1) + ' de ' + total + '. ' + titulo);
        });
    });
</script>
