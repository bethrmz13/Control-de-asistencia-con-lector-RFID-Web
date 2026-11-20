<!-- Responsable: Brayan. Ampliar con el equipo, el proyecto, multimedia y un jQuery propio -->
<h2>Nosotros</h2>
<p>Somos estudiantes de la Carrera de Software de la Universidad Técnica de Ambato.</p>
<button type="button" id="btnMas" class="btn btn-outline-primary btn-sm">Ver más</button>
<div id="mas" class="mt-3">
    <p>Aquí va la descripción del proyecto y del equipo.</p>
</div>

<script>
    // jQuery de Nosotros: mostrar y ocultar
    $(function () {
        $('#mas').hide();
        $('#btnMas').click(function () {
            $('#mas').slideToggle();
        });
    });
</script>
