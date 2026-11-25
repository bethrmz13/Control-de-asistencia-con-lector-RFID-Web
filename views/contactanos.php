<h2>Contáctanos</h2>
<p>Completa el formulario y te mostraremos una confirmación.</p>

<div id="mensajeContacto" aria-live="polite"></div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <form id="formContacto" novalidate>
            <div class="mb-3">
                <label for="nombreContacto" class="form-label">Nombre</label>
                <input type="text" id="nombreContacto" class="form-control" maxlength="80" autocomplete="name" required aria-describedby="errorNombre">
                <div id="errorNombre" class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="correoContacto" class="form-label">Correo</label>
                <input type="email" id="correoContacto" class="form-control" maxlength="120" autocomplete="email" required aria-describedby="errorCorreo">
                <div id="errorCorreo" class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="textoContacto" class="form-label">Mensaje</label>
                <textarea id="textoContacto" class="form-control" rows="5" maxlength="1000" required aria-describedby="errorTexto"></textarea>
                <div id="errorTexto" class="invalid-feedback"></div>
            </div>
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
    </div>
</div>

<script>
    $(function () {
        var formulario = $('#formContacto');
        var correoValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function marcarError(campo, mensaje, selectorError) {
            campo.addClass('is-invalid').removeClass('is-valid').attr('aria-invalid', 'true');
            $(selectorError).text(mensaje);
        }

        function limpiarError(campo, selectorError) {
            campo.removeClass('is-invalid is-valid').removeAttr('aria-invalid');
            $(selectorError).text('');
        }

        formulario.on('submit', function (evento) {
            evento.preventDefault();
            $('#mensajeContacto').empty();

            var nombre = $('#nombreContacto');
            var correo = $('#correoContacto');
            var texto = $('#textoContacto');
            var nombreValor = nombre.val().trim();
            var correoValor = correo.val().trim();
            var textoValor = texto.val().trim();
            var valido = true;

            limpiarError(nombre, '#errorNombre');
            limpiarError(correo, '#errorCorreo');
            limpiarError(texto, '#errorTexto');

            if (nombreValor === '') {
                marcarError(nombre, 'Escribe tu nombre.', '#errorNombre');
                valido = false;
            }
            if (correoValor === '') {
                marcarError(correo, 'Escribe tu correo.', '#errorCorreo');
                valido = false;
            } else if (!correoValido.test(correoValor)) {
                marcarError(correo, 'Escribe un correo válido.', '#errorCorreo');
                valido = false;
            }
            if (textoValor === '') {
                marcarError(texto, 'Escribe tu mensaje.', '#errorTexto');
                valido = false;
            }

            if (!valido) {
                mostrarMensaje('#mensajeContacto', 'Revisa los campos señalados.', 'warning');
                return;
            }

            mostrarMensaje('#mensajeContacto', 'Gracias, ' + nombreValor + '. El formulario se validó correctamente.', 'success');
            formulario[0].reset();
            limpiarError(nombre, '#errorNombre');
            limpiarError(correo, '#errorCorreo');
            limpiarError(texto, '#errorTexto');
        });

        formulario.find('input, textarea').on('input', function () {
            var campo = $(this);
            campo.removeClass('is-invalid is-valid').removeAttr('aria-invalid');
            campo.next('.invalid-feedback').text('');
            $('#mensajeContacto').empty();
        });
    });
</script>