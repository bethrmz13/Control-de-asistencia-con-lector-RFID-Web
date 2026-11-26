<?php $u = usuarioActual(); $esCoordinador = ($u !== null && $u['perfil'] === 'COORDINADOR'); ?>
<h3>Grupos</h3>

<div id="mensaje"></div>

<p>
    <strong>Periodo activo:</strong>
    <span id="periodo">-</span>
</p>

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">

    <input type="text"
           id="buscar"
           class="form-control"
           style="max-width: 300px;"
           placeholder="Buscar grupo...">

    <button type="button" id="btnNuevo" class="btn btn-primary">
        Nuevo grupo
    </button>

</div>

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle" id="tabla">

        <thead class="table-dark">
            <tr>
                <th>Carrera</th>
                <th>Asignatura</th>
                <th>Nivel</th>
                <th>Paralelo</th>
                <th>Docente</th>
                <th>Matriculados</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>

        <tbody></tbody>

    </table>

</div>

<p class="text-muted">
    Total de grupos: <strong id="total">0</strong>
</p>

<!-- Ventana para registrar y modificar grupos -->
<div class="modal fade" id="modalGrupo" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">

                <div id="mensajeModal"></div>

                <input type="hidden" id="id">

                <div id="campoCarreraGrupo" class="mb-3">
                    <label for="carreraGrupo" class="form-label">Carrera</label>
                    <select id="carreraGrupo" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label for="nivelGrupo" class="form-label">Nivel</label>
                    <select id="nivelGrupo" class="form-select" disabled></select>
                </div>
                <div class="mb-3">
                    <label for="asignatura" class="form-label">Asignatura</label>
                    <select id="asignatura" class="form-select" disabled></select>
                    <div id="avisoAsignatura" class="form-text d-none">
                        La asignatura no se puede cambiar al editar.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Paralelo</label>

                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="paralelo" id="paraleloA" value="A">
                        <label class="form-check-label" for="paraleloA">A</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="paralelo" id="paraleloB" value="B">
                        <label class="form-check-label" for="paraleloB">B</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="paralelo" id="paraleloC" value="C">
                        <label class="form-check-label" for="paraleloC">C</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="docente" class="form-label">Docente</label>
                    <select id="docente" class="form-select"></select>
                    <div class="form-text">Puede quedar sin docente.</div>
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

        var modal = new bootstrap.Modal(document.getElementById('modalGrupo'));

        var lista = [];
        var asignaturas = [];
        var docentes = [];
        var carreras = [];
        var esCoordinador = <?php echo $esCoordinador ? 'true' : 'false'; ?>;

        function actualizarNiveles(seleccionado) {
            var carrera = $('#carreraGrupo').val();
            var niveles = [];
            $.each(asignaturas, function (_, a) {
                if (String(a.carrera_id) === String(carrera) && niveles.indexOf(String(a.nivel)) < 0) niveles.push(String(a.nivel));
            });
            niveles.sort(function (a, b) { return Number(a) - Number(b); });
            llenarCombo('#nivelGrupo', niveles.map(function (n) { return {id:n, texto:'Nivel ' + n}; }), 'Seleccione un nivel');
            $('#nivelGrupo').prop('disabled', !carrera || !niveles.length);
            if (seleccionado) $('#nivelGrupo').val(String(seleccionado));
            actualizarAsignaturas();
        }

        function actualizarAsignaturas(seleccionada) {
            var carrera = $('#carreraGrupo').val();
            var nivel = $('#nivelGrupo').val();
            var opciones = asignaturas.filter(function (a) { return String(a.carrera_id) === String(carrera) && String(a.nivel) === String(nivel); });
            llenarCombo('#asignatura', opciones, 'Seleccione una asignatura');
            $('#asignatura').prop('disabled', !carrera || !nivel || !opciones.length);
            if (seleccionada) $('#asignatura').val(String(seleccionada));
        }

        // Carga los grupos del periodo activo y los combos
        function cargar() {

            $.getJSON('models/grupos/select.php', function (r) {

                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    $('#btnNuevo').prop('disabled', true);
                    return;
                }

                $('#btnNuevo').prop('disabled', false);
                $('#periodo').text(r.periodo);

                lista = r.grupos;
                asignaturas = r.asignaturas;
                docentes = r.docentes;
                carreras = r.carreras;

                var html = '';

                $.each(lista, function (i, g) {

                    var docente = g.docente === null
                        ? '<span class="text-muted">Sin docente</span>'
                        : escaparHtml(g.docente);

                    html += '<tr>' +
                        '<td>' + escaparHtml(g.carrera) + '</td>' +
                        '<td>' + escaparHtml(g.asignatura) + '</td>' +
                        '<td>' + escaparHtml(g.nivel) + '</td>' +
                        '<td>' + escaparHtml(g.paralelo) + '</td>' +
                        '<td>' + docente + '</td>' +
                        '<td>' + escaparHtml(g.matriculados) + '</td>' +
                        '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btnEditar" data-i="' + i + '">Editar</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btnEliminar" data-i="' + i + '">Eliminar</button>' +
                        '</td>' +
                        '</tr>';
                });

                if (lista.length === 0) {
                    html = '<tr><td colspan="7" class="text-center text-muted">' +
                        'No existen grupos en este periodo.</td></tr>';
                }

                $('#tabla tbody').html(html);
                $('#total').text(lista.length);

                $('#buscar').trigger('keyup');

            }).fail(function (xhr) {

                var mensaje = 'No se pudo cargar la lista de grupos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensaje', mensaje, 'danger');

            });
        }

        // Deja el formulario vacio y llena los combos
        function limpiarFormulario() {

            $('#id').val('');
            $('#mensajeModal').html('');
            $('#avisoAsignatura').addClass('d-none');

            llenarCombo('#carreraGrupo', carreras, 'Seleccione una carrera');
            llenarCombo('#nivelGrupo', [], 'Seleccione primero una carrera');
            llenarCombo('#asignatura', [], 'Seleccione primero un nivel');
            $('#carreraGrupo').prop('disabled', false).val('');
            $('#nivelGrupo, #asignatura').prop('disabled', true);

            llenarCombo('#docente', docentes, 'Sin docente');
            $('#docente').val('');

            $('input[name="paralelo"]').prop('checked', false);
        }

        function validar() {

            if (!$('#carreraGrupo').val() || !$('#nivelGrupo').val() || !$('#asignatura').val()) {
                mostrarMensaje('#mensajeModal', 'Seleccione carrera, nivel y asignatura', 'warning');
                return false;
            }

            if (!$('input[name="paralelo"]:checked').val()) {
                mostrarMensaje('#mensajeModal', 'Seleccione el paralelo (A, B o C)', 'warning');
                return false;
            }

            return true;
        }

        $('#btnNuevo').click(function () {
            limpiarFormulario();
            $('#tituloModal').text('Nuevo grupo');
            modal.show();
        });

        $('#carreraGrupo').on('change', function () { actualizarNiveles(); });
        $('#nivelGrupo').on('change', function () { actualizarAsignaturas(); });

        $(document).on('click', '.btnEditar', function () {

            var g = lista[$(this).data('i')];

            limpiarFormulario();
            $('#tituloModal').text('Editar grupo');
            $('#id').val(g.id);

            $('#carreraGrupo').val(String(g.carrera_id)).prop('disabled', true);
            actualizarNiveles(g.nivel);
            $('#nivelGrupo').prop('disabled', true);
            $('#asignatura').val(String(g.asignatura_id)).prop('disabled', true);
            $('#avisoAsignatura').removeClass('d-none');

            $('input[name="paralelo"][value="' + g.paralelo + '"]').prop('checked', true);

            // Si el docente actual ya no esta activo, se agrega al combo para no quitarlo sin querer
            if (g.docente_id !== null && $('#docente option[value="' + g.docente_id + '"]').length === 0) {
                $('#docente').append('<option value="' + escaparHtml(g.docente_id) + '">' +
                    escaparHtml(g.docente) + ' (inactivo)</option>');
            }
            $('#docente').val(g.docente_id === null ? '' : g.docente_id);

            modal.show();
        });

        $('#btnGuardar').click(function () {

            if (!validar()) {
                return;
            }

            var editando = $('#id').val() !== '';
            var url = editando ? 'models/grupos/update.php' : 'models/grupos/guardar.php';

            $('#btnGuardar').prop('disabled', true);

            $.post(url, {
                id: $('#id').val(),
                carrera: $('#carreraGrupo').val(),
                nivel: $('#nivelGrupo').val(),
                asignatura: $('#asignatura').val(),
                paralelo: $('input[name="paralelo"]:checked').val(),
                docente: $('#docente').val()
            }, function (r) {

                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje',
                        editando ? 'Grupo modificado correctamente' : 'Grupo registrado correctamente',
                        'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                }

            }, 'json').fail(function (xhr) {

                var mensaje = 'No se pudo conectar con el servidor';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensajeModal', mensaje, 'danger');

            }).always(function () {
                $('#btnGuardar').prop('disabled', false);
            });
        });

        $(document).on('click', '.btnEliminar', function () {

            var boton = $(this);
            var g = lista[boton.data('i')];

            if (!confirm('¿Desea eliminar el grupo ' + g.asignatura + ' (paralelo ' + g.paralelo + ')?')) {
                return;
            }

            boton.prop('disabled', true);

            $.post('models/grupos/delete.php', {id: g.id}, function (r) {

                if (r.success) {
                    mostrarMensaje('#mensaje', 'Grupo eliminado correctamente', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }

            }, 'json').fail(function (xhr) {

                var mensaje = 'No se pudo eliminar el grupo';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensaje', mensaje, 'danger');

            }).always(function () {
                boton.prop('disabled', false);
            });
        });

        filtrarTabla('#buscar', '#tabla');

        cargar();

    });
</script>
