<h3>Usuarios</h3>
<div id="mensaje"></div>

<div class="d-flex justify-content-between mb-3">
    <input type="text" id="buscar" class="form-control w-25" placeholder="Buscar...">
    <button type="button" id="btnNuevo" class="btn btn-primary">Nuevo</button>
</div>

<table class="table table-striped" id="tabla">
    <thead>
        <tr><th>Usuario</th><th>Nombre</th><th>Perfil</th><th>Carrera</th><th>Activo</th><th></th></tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Ventana de Bootstrap para Nuevo y Editar -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeModal"></div>
                <input type="hidden" id="id">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" class="form-control" maxlength="30">
                </div>
                <div class="mb-3">
                    <label for="clave" class="form-label">Clave</label>
                    <input type="password" id="clave" class="form-control" maxlength="50" autocomplete="new-password">
                    <div class="form-text" id="ayudaClave"></div>
                </div>
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" class="form-control" maxlength="80">
                </div>
                <div class="mb-3">
                    <label for="perfil" class="form-label">Perfil</label>
                    <select id="perfil" class="form-select">
                        <option value="">Seleccione</option>
                        <option value="ADMIN">ADMIN</option>
                        <option value="COORDINADOR">COORDINADOR</option>
                        <option value="DOCENTE">DOCENTE</option>
                        <option value="REGISTRO">REGISTRO</option>
                        <option value="ESTUDIANTE">ESTUDIANTE</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="carrera" class="form-label">Carrera (solo para el coordinador)</label>
                    <select id="carrera" class="form-select" disabled></select>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="activo" class="form-check-input" checked>
                    <label for="activo" class="form-check-label">Activo</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {

        var modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
        var lista = [];     // lo que llegÃ³ del servidor; los botones guardan la posiciÃ³n en esta lista
        var yo = 0;         // id del usuario que iniciÃ³ sesiÃ³n

        function cargar() {
            $.getJSON('models/usuarios/select.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                lista = r.usuarios;
                yo = r.yo;
                var html = '';
                $.each(lista, function (i, u) {
                    var estado = (u.activo == 1)
                        ? '<span class="badge bg-success">SÃ­</span>'
                        : '<span class="badge bg-secondary">No</span>';
                    // Al propio usuario no se le ofrece eliminar
                    var eliminar = (u.id == yo) ? '' :
                        '<button type="button" class="btn btn-sm btn-outline-danger btnEliminar" data-i="' + i + '">Eliminar</button>';
                    html += '<tr>' +
                        '<td>' + escaparHtml(u.username) + '</td>' +
                        '<td>' + escaparHtml(u.nombre) + '</td>' +
                        '<td>' + escaparHtml(u.perfil) + '</td>' +
                        '<td>' + escaparHtml(u.carrera) + '</td>' +
                        '<td>' + estado + '</td>' +
                        '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btnEditar" data-i="' + i + '">Editar</button> ' +
                        eliminar + '</td></tr>';
                });
                $('#tabla tbody').html(html);
                llenarCombo('#carrera', r.carreras, 'Seleccione');
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar la lista', 'danger');
            });
        }

        // La carrera solo se habilita para el perfil COORDINADOR
        function actualizarCarrera() {
            var esCoordinador = ($('#perfil').val() === 'COORDINADOR');
            $('#carrera').prop('disabled', !esCoordinador);
            if (!esCoordinador) {
                $('#carrera').val('');
            }
        }

        function limpiarFormulario() {
            $('#id').val('');
            $('#username').val('');
            $('#clave').val('');
            $('#nombre').val('');
            $('#perfil').val('');
            $('#carrera').val('');
            $('#activo').prop('checked', true);
            $('#mensajeModal').html('');
            actualizarCarrera();
        }

        // ValidaciÃ³n del navegador (el servidor valida de nuevo)
        function validar() {
            var esNuevo = ($('#id').val() === '');
            if (!/^[A-Za-z0-9._-]{1,30}$/.test($('#username').val().trim())) {
                mostrarMensaje('#mensajeModal', 'El usuario debe tener de 1 a 30 caracteres: letras, nÃºmeros, punto, guion o guion bajo', 'warning');
                $('#username').focus();
                return false;
            }
            if (esNuevo && $('#clave').val() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese la clave', 'warning');
                $('#clave').focus();
                return false;
            }
            if ($('#nombre').val().trim() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el nombre', 'warning');
                $('#nombre').focus();
                return false;
            }
            if ($('#perfil').val() === '') {
                mostrarMensaje('#mensajeModal', 'Seleccione el perfil', 'warning');
                return false;
            }
            if ($('#perfil').val() === 'COORDINADOR' && $('#carrera').val() === '') {
                mostrarMensaje('#mensajeModal', 'Seleccione la carrera del coordinador', 'warning');
                return false;
            }
            return true;
        }

        $('#perfil').change(actualizarCarrera);

        $('#btnNuevo').click(function () {
            limpiarFormulario();
            $('#tituloModal').text('Nuevo usuario');
            $('#ayudaClave').text('');
            modal.show();
        });

        // Los botones se crean al cargar la tabla, por eso el evento va en $(document)
        $(document).on('click', '.btnEditar', function () {
            var u = lista[$(this).data('i')];
            limpiarFormulario();
            $('#tituloModal').text('Editar usuario');
            $('#ayudaClave').text('Dejela vacía para no cambiar la clave');
            $('#id').val(u.id);
            $('#username').val(u.username);
            $('#nombre').val(u.nombre);
            $('#perfil').val(u.perfil);
            actualizarCarrera();
            if (u.perfil === 'COORDINADOR') {
                $('#carrera').val(u.carrera_id);
            }
            $('#activo').prop('checked', u.activo == 1);
            modal.show();
        });

        $('#btnGuardar').click(function () {
            if (!validar()) {
                return;
            }
            var url = ($('#id').val() === '') ? 'models/usuarios/guardar.php' : 'models/usuarios/update.php';
            $.post(url, {
                id: $('#id').val(),
                username: $('#username').val().trim(),
                clave: $('#clave').val(),
                nombre: $('#nombre').val().trim(),
                perfil: $('#perfil').val(),
                carrera: $('#carrera').val(),
                activo: $('#activo').is(':checked') ? 1 : 0
            }, function (r) {
                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje', 'Usuario guardado correctamente', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensajeModal', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnEliminar', function () {
            var u = lista[$(this).data('i')];
            if (!confirm('¿Eliminar al usuario ' + u.username + '?')) {
                return;
            }
            $.post('models/usuarios/delete.php', { id: u.id }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', 'Usuario eliminado', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json');
        });

        filtrarTabla('#buscar', '#tabla');
        cargar();
    });
</script>

