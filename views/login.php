<div class="row justify-content-center">
    <div class="col-md-4">
        <h3 class="mb-3">Ingresar</h3>
        <div id="mensaje"></div>
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuario</label>
            <input type="text" id="usuario" class="form-control" autofocus>
        </div>
        <div class="mb-3">
            <label for="clave" class="form-label">Clave</label>
            <input type="password" id="clave" class="form-control">
        </div>
        <button type="button" id="btnIngresar" class="btn btn-primary w-100">Ingresar</button>
    </div>
</div>

<script>
    $(function () {

        function ingresar() {
            $.post('models/login.php', {
                usuario: $('#usuario').val(),
                clave: $('#clave').val()
            }, function (r) {
                if (r.success) {
                    window.location.href = 'index.php?accion=' + r.destino;
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    $('#clave').val('').focus();
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo conectar con el servidor', 'danger');
            });
        }

        $('#btnIngresar').click(ingresar);
        $('#clave').keypress(function (e) {
            if (e.which == 13) {
                ingresar();
            }
        });
    });
</script>
