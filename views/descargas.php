<!-- Responsable: Brayan. Aqui van los enlaces para descargar archivos (la guia lo pide) -->
<h2>Descargas</h2>
<p>Archivos disponibles para descargar:</p>
<ul id="listaDescargas">
    <li><a href="descargas/manual.pdf" download>Manual de usuario (PDF)</a></li>
</ul>

<script>
    // jQuery de Descargas: resalta el enlace al pasar el mouse
    $(function () {
        $('#listaDescargas a').hover(
            function () { $(this).css('font-weight', 'bold'); },
            function () { $(this).css('font-weight', 'normal'); }
        );
    });
</script>
