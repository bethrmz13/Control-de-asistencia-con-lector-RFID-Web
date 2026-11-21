<!-- Responsable: Brayan. Formulario de contacto con validacion en jQuery -->
<h2>Contáctanos</h2>
<div id="mensajeContacto"></div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="nombreContacto" class="form-label">Nombre</label>
            <input type="text" id="nombreContacto" class="form-control">
        </div>
        <div class="mb-3">
            <label for="correoContacto" class="form-label">Correo</label>
            <input type="text" id="correoContacto" class="form-control">
        </div>
        <div class="mb-3">
            <label for="textoContacto" class="form-label">Mensaje</label>
            <textarea id="textoContacto" class="form-control" rows="4"></textarea>
        </div>
        <button type="button" id="btnEnviarContacto" class="btn btn-primary">Enviar</button>
    </div>
</div>

<script>
    // jQuery de Contactanos: validacion del formulario
    $(function () {
        $('#btnEnviarContacto').click(function () {
            var nombre = $('#nombreContacto').val().trim();
            var correo = $('#correoContacto').val().trim();
            var texto = $('#textoContacto').val().trim();
            if (nombre === '' || correo === '' || texto === '') {
                mostrarMensaje('#mensajeContacto', 'Complete todos los campos', 'warning');
            } else if (correo.indexOf('@') < 1) {
                mostrarMensaje('#mensajeContacto', 'Ingrese un correo válido', 'warning');
            } else {
                mostrarMensaje('#mensajeContacto', 'Gracias, ' + nombre + '. Su mensaje fue recibido.', 'success');
            }
        });
    });
</script>
