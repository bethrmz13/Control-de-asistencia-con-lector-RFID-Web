<section class="mt-5" aria-labelledby="tituloProyecto">
    <h2 id="tituloProyecto">Nuestro proyecto</h2>
    <p>Conoce el objetivo y las funciones principales del sistema.</p>
    <button type="button" id="btnProyecto" class="btn btn-outline-primary" aria-expanded="false" aria-controls="descripcionProyecto">
        Mostrar descripción
    </button>
    <div id="descripcionProyecto" class="mt-3 p-4 bg-light border rounded">
        <h3 class="h5">Control de asistencia con lector RFID</h3>
        <p>El proyecto es una aplicación web para apoyar la gestión académica y el registro de control de asistencia.</p>
        <p>El lector RFID identifica al estudiante y registra su asistencia durante el horario de clase. El sistema también permite administrar usuarios y periodos, gestionar estudiantes, asignaturas, grupos, horarios y matrículas, registrar notas y consultar reportes.</p>
        <p class="mb-0">Se desarrolló con PHP y MySQL en el servidor, y HTML, Bootstrap, JavaScript y jQuery en la interfaz.</p>
    </div>
</section>

<script>
    $(function () {
        var panel = $('#descripcionProyecto');
        var boton = $('#btnProyecto');

        panel.hide();
        boton.click(function () {
            panel.slideToggle(250, function () {
                var abierto = panel.is(':visible');
                boton.attr('aria-expanded', abierto ? 'true' : 'false');
                boton.text(abierto ? 'Ocultar descripción' : 'Mostrar descripción');
            });
        });
    });
</script>