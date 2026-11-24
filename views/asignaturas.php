<h3>Asignaturas</h3>

<div id="mensaje"></div>

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">

    <input type="text"
           id="buscar"
           class="form-control"
           style="max-width: 300px;"
           placeholder="Buscar asignatura...">

    <button type="button"
            id="btnNuevo"
            class="btn btn-primary">
        Nueva asignatura
    </button>

</div>

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle"
           id="tabla">

        <thead class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Nivel</th>
                <th>Carrera</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>

        <tbody></tbody>

    </table>

</div>

<p class="text-muted">
    Total de asignaturas: <strong id="total">0</strong>
</p>

<!-- Ventana para registrar y modificar asignaturas -->
<div class="modal fade" id="modalAsignatura" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="tituloModal">
                    Asignatura
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                </button>

            </div>

            <div class="modal-body">

                <div id="mensajeModal"></div>

                <input type="hidden" id="id">

                <div class="mb-3">

                    <label for="nombre" class="form-label">
                        Nombre de la asignatura
                    </label>

                    <input type="text"
                           id="nombre"
                           class="form-control"
                           maxlength="80"
                           placeholder="Ingrese el nombre">

                </div>

                <div class="mb-3">

                    <label for="nivel" class="form-label">
                        Nivel
                    </label>

                    <input type="number"
                           id="nivel"
                           class="form-control"
                           min="1"
                           max="10"
                           step="1">

                    <div class="form-text">
                        Seleccione un nivel entre 1 y 10.
                    </div>

                </div>

                <div class="mb-3">

                    <label for="carrera" class="form-label">
                        Carrera
                    </label>

                    <select id="carrera"
                            class="form-select">
                    </select>

                    <div id="avisoCarrera"
                         class="form-text d-none">
                        La carrera no se puede cambiar al editar.
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="button"
                        class="btn btn-primary"
                        id="btnGuardar">
                    Guardar
                </button>

            </div>

        </div>

    </div>

</div>

<script>
    $(function () {

        var modal = new bootstrap.Modal(
            document.getElementById('modalAsignatura')
        );

        var lista = [];
        var carreras = [];

        // Cargar asignaturas y carreras.
        function cargar() {

            $.getJSON(
                'models/asignaturas/select.php',
                function (r) {

                    if (r.error) {

                        mostrarMensaje(
                            '#mensaje',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    lista = r.asignaturas;
                    carreras = r.carreras;

                    var html = '';

                    $.each(lista, function (i, a) {

                        html += '<tr>' +

                            '<td>' +
                            escaparHtml(a.nombre) +
                            '</td>' +

                            '<td>' +
                            escaparHtml(a.nivel) +
                            '</td>' +

                            '<td>' +
                            escaparHtml(a.carrera) +
                            '</td>' +

                            '<td class="text-end">' +

                            '<button type="button" ' +
                            'class="btn btn-sm btn-outline-primary btnEditar" ' +
                            'data-i="' + i + '">' +
                            'Editar</button> ' +

                            '<button type="button" ' +
                            'class="btn btn-sm btn-outline-danger btnEliminar" ' +
                            'data-i="' + i + '">' +
                            'Eliminar</button>' +

                            '</td>' +

                            '</tr>';

                    });

                    if (lista.length === 0) {

                        html = '<tr>' +
                            '<td colspan="4" class="text-center text-muted">' +
                            'No existen asignaturas registradas.' +
                            '</td></tr>';
                    }

                    $('#tabla tbody').html(html);

                    $('#total').text(lista.length);

                    llenarCombo(
                        '#carrera',
                        carreras,
                        'Seleccione'
                    );

                    $('#buscar').trigger('keyup');

                }

            ).fail(function () {

                mostrarMensaje(
                    '#mensaje',
                    'No se pudo cargar la lista de asignaturas',
                    'danger'
                );

            });

        }

        // Limpiar los campos del formulario.
        function limpiarFormulario() {

            $('#id').val('');
            $('#nombre').val('');
            $('#nivel').val('');

            $('#carrera')
                .prop('disabled', false)
                .val('');

            $('#mensajeModal').html('');

            $('#avisoCarrera').addClass('d-none');

            // Si solo hay una carrera disponible,
            // se selecciona automáticamente.
            if (carreras.length === 1) {

                $('#carrera')
                    .val(carreras[0].id)
                    .prop('disabled', true);

            }

        }

        // Validar los datos del formulario.
        function validar() {

            var nombre = $('#nombre').val().trim();

            var nivel = Number($('#nivel').val());

            if (nombre === '' || nombre.length > 80) {

                mostrarMensaje(
                    '#mensajeModal',
                    'Ingrese un nombre de hasta 80 caracteres',
                    'warning'
                );

                $('#nombre').focus();

                return false;
            }

            if (!Number.isInteger(nivel) ||
                nivel < 1 || nivel > 10) {

                mostrarMensaje(
                    '#mensajeModal',
                    'El nivel debe estar entre 1 y 10',
                    'warning'
                );

                $('#nivel').focus();

                return false;
            }

            if (!$('#carrera').val()) {

                mostrarMensaje(
                    '#mensajeModal',
                    'Seleccione la carrera',
                    'warning'
                );

                return false;
            }

            return true;

        }

        // Abrir ventana para una nueva asignatura.
        $('#btnNuevo').click(function () {

            limpiarFormulario();

            $('#tituloModal').text('Nueva asignatura');

            modal.show();

        });

        // Abrir ventana para editar.
        $(document).on('click', '.btnEditar', function () {

            var a = lista[$(this).data('i')];

            limpiarFormulario();

            $('#tituloModal').text('Editar asignatura');

            $('#id').val(a.id);

            $('#nombre').val(a.nombre);

            $('#nivel').val(a.nivel);

            $('#carrera')
                .val(a.carrera_id)
                .prop('disabled', true);

            $('#avisoCarrera').removeClass('d-none');

            modal.show();

        });

        // Guardar o actualizar la asignatura.
        $('#btnGuardar').click(function () {

            if (!validar()) {
                return;
            }

            var editando = $('#id').val() !== '';

            var url = editando
                ? 'models/asignaturas/update.php'
                : 'models/asignaturas/guardar.php';

            $('#btnGuardar').prop('disabled', true);

            $.post(url, {

                id: $('#id').val(),

                nombre: $('#nombre').val().trim(),

                nivel: $('#nivel').val(),

                carrera: $('#carrera').val()

            }, function (r) {

                if (r.success) {

                    modal.hide();

                    var mensaje = editando
                        ? 'Asignatura modificada correctamente'
                        : 'Asignatura registrada correctamente';

                    mostrarMensaje(
                        '#mensaje',
                        mensaje,
                        'success'
                    );

                    cargar();

                } else {

                    mostrarMensaje(
                        '#mensajeModal',
                        r.error,
                        'danger'
                    );

                }

            }, 'json').fail(function (xhr) {

                var mensaje = 'No se pudo conectar con el servidor';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensajeModal',
                    mensaje,
                    'danger'
                );

            }).always(function () {

                $('#btnGuardar').prop('disabled', false);

            });

        });

        // Eliminar una asignatura.
        $(document).on('click', '.btnEliminar', function () {

            var boton = $(this);

            var a = lista[boton.data('i')];

            if (!confirm(
                '¿Desea eliminar la asignatura ' +
                a.nombre + '?'
            )) {
                return;
            }

            boton.prop('disabled', true);

            $.post(
                'models/asignaturas/delete.php',

                {id: a.id},

                function (r) {

                    if (r.success) {

                        mostrarMensaje(
                            '#mensaje',
                            'Asignatura eliminada correctamente',
                            'success'
                        );

                        cargar();

                    } else {

                        mostrarMensaje(
                            '#mensaje',
                            r.error,
                            'danger'
                        );

                    }

                },

                'json'

            ).fail(function (xhr) {

                var mensaje = 'No se pudo eliminar la asignatura';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensaje',
                    mensaje,
                    'danger'
                );

            }).always(function () {

                boton.prop('disabled', false);

            });

        });

        // Activar el buscador de la tabla.
        filtrarTabla('#buscar', '#tabla');

        cargar();

    });
</script>
