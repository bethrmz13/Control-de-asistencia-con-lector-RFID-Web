<?php $u = usuarioActual(); $esCoordinador = ($u !== null && $u['perfil'] === 'COORDINADOR'); ?>
<h3>Notas</h3>

<div id="mensaje"></div>

<!-- Filtros en cadena: carrera -> nivel -> asignatura -> paralelo -->
<div class="card mb-4">

    <div class="card-header bg-primary text-white">Selección del grupo</div>

    <div class="card-body">

        <p>
            <strong>Periodo académico:</strong>
            <span id="periodo">-</span>
        </p>

        <div class="row">

            <div id="campoCarreraNotas" class="col-md-3 mb-3">
                <label for="carrera" class="form-label">Carrera</label>
                <select id="carrera" class="form-select"><option value="">Seleccione una carrera</option></select>
            </div>

            <div class="col-md-2 mb-3">
                <label for="nivel" class="form-label">Nivel</label>
                <select id="nivel" class="form-select" disabled>
                    <option value="">Seleccione carrera</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="asignatura" class="form-label">Asignatura</label>
                <select id="asignatura" class="form-select" disabled>
                    <option value="">Seleccione una asignatura</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label for="paralelo" class="form-label">Paralelo</label>
                <select id="paralelo" class="form-select" disabled>
                    <option value="">Seleccione un paralelo</option>
                </select>
            </div>

        </div>

    </div>

</div>

<!-- Notas del grupo elegido -->
<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">Notas del grupo</h5>
    </div>

    <div class="card-body">

        <div id="mensajeNotas"></div>

        <p id="infoGrupo" class="d-none">
            <strong>Carrera:</strong> <span id="nombreCarreraGrupo"></span> &nbsp;
            <strong>Nivel:</strong> <span id="nivelSeleccionado"></span> &nbsp;
            <strong>Asignatura:</strong> <span id="nombreAsignatura"></span> &nbsp;
            <strong>Paralelo:</strong> <span id="nombreParalelo"></span>
        </p>

        <div class="table-responsive">

            <table class="table table-striped align-middle" id="tablaNotas">

                <thead class="table-dark">
                    <tr>
                        <th>N.º</th>
                        <th>Cédula</th>
                        <th>Apellido</th>
                        <th>Nombre</th>
                        <th style="width: 140px;">Nota (0 a 10)</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Seleccione una carrera, nivel, asignatura y paralelo.</td>
                    </tr>
                </tbody>

            </table>

        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

            <p class="text-muted mb-0">
                Estudiantes: <strong id="total">0</strong>
            </p>

            <button type="button" id="btnGuardar" class="btn btn-primary" disabled>
                Guardar notas del grupo
            </button>

        </div>

    </div>

</div>

<script>
    $(function () {

        var grupos = [];
        var estudiantes = [];
        var grupoActual = null;
        var esCoordinador = <?php echo $esCoordinador ? 'true' : 'false'; ?>;

        // Carga los grupos (el servidor ya filtra por docente)
        function cargar() {

            $.getJSON('models/notas/grupos.php', function (r) {

                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    $('#nivel').prop('disabled', true);
                    $('#carrera').prop('disabled', true);
                    return;
                }

                $('#periodo').text(r.periodo);
                grupos = r.grupos;

                // Carreras disponibles entre los grupos que puede gestionar el usuario.
                var carreras = [];
                var vistasCarreras = {};
                $.each(grupos, function (i, g) {
                    if (!vistasCarreras[g.carrera_id]) {
                        vistasCarreras[g.carrera_id] = true;
                        carreras.push({id:g.carrera_id, texto:g.carrera});
                    }
                });
                llenarCombo('#carrera', carreras, 'Seleccione una carrera');

                if (grupos.length === 0) {
                    mostrarMensaje('#mensaje', 'No tiene grupos asignados en este periodo.', 'info');
                    $('#nivel').prop('disabled', true);
                    $('#carrera').prop('disabled', true);
                }

            }).fail(function (xhr) {

                var mensaje = 'No se pudieron cargar los grupos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensaje', mensaje, 'danger');

            });
        }

        function limpiarNotas() {
            grupoActual = null;
            estudiantes = [];
            $('#mensajeNotas').html('');
            $('#infoGrupo').addClass('d-none');
            $('#total').text(0);
            $('#btnGuardar').prop('disabled', true);
            $('#tablaNotas tbody').html(
                '<tr><td colspan="5" class="text-center text-muted">' +
                'Seleccione una carrera, nivel, asignatura y paralelo.</td></tr>');
        }

        // Carrera -> niveles disponibles.
        $('#carrera').on('change', function () {
            limpiarNotas();
            llenarCombo('#asignatura', [], 'Seleccione una asignatura');
            llenarCombo('#paralelo', [], 'Seleccione un paralelo');
            $('#asignatura, #paralelo').prop('disabled', true);
            var carrera = $(this).val();
            var niveles = [];
            $.each(grupos, function (_, g) {
                if (String(g.carrera_id) === carrera && niveles.indexOf(String(g.nivel)) < 0) niveles.push(String(g.nivel));
            });
            niveles.sort(function (a, b) { return Number(a) - Number(b); });
            llenarCombo('#nivel', niveles.map(function (n) { return {id:n, texto:'Nivel ' + n}; }), 'Seleccione un nivel');
            $('#nivel').prop('disabled', !carrera || !niveles.length);
        });

        // Nivel -> asignaturas de la carrera seleccionada.
        $('#nivel').on('change', function () {

            limpiarNotas();
            llenarCombo('#paralelo', [], 'Seleccione un paralelo');
            $('#paralelo').prop('disabled', true);

            var nivel = $(this).val();
            var asignaturas = [];
            var vistas = {};

            $.each(grupos, function (i, g) {
                if (String(g.carrera_id) === String($('#carrera').val()) && String(g.nivel) === nivel && !vistas[g.asignatura_id]) {
                    vistas[g.asignatura_id] = true;
                    asignaturas.push({id: g.asignatura_id, texto: g.asignatura});
                }
            });

            llenarCombo('#asignatura', asignaturas, 'Seleccione una asignatura');
            $('#asignatura').prop('disabled', asignaturas.length === 0);
        });

        // Asignatura -> paralelos
        $('#asignatura').on('change', function () {

            limpiarNotas();

            var asignaturaId = $(this).val();
            var paralelos = [];

            $.each(grupos, function (i, g) {
                if (String(g.carrera_id) === String($('#carrera').val()) && String(g.nivel) === String($('#nivel').val()) && String(g.asignatura_id) === asignaturaId) {
                    paralelos.push({id: g.id, texto: 'Paralelo ' + g.paralelo});
                }
            });

            llenarCombo('#paralelo', paralelos, 'Seleccione un paralelo');
            $('#paralelo').prop('disabled', paralelos.length === 0);
        });

        // Paralelo -> estudiantes con su nota
        $('#paralelo').on('change', function () {

            limpiarNotas();

            var grupoId = $(this).val();
            if (grupoId === '') {
                return;
            }

            $.getJSON('models/notas/select.php', {grupo: grupoId}, function (r) {

                if (r.error) {
                    mostrarMensaje('#mensajeNotas', r.error, 'danger');
                    return;
                }

                grupoActual = grupoId;
                estudiantes = r.estudiantes;

                var grupoSeleccionado = grupos.find(function (g) { return String(g.id) === String(grupoId); });
                $('#nombreCarreraGrupo').text(grupoSeleccionado ? grupoSeleccionado.carrera : '');
                $('#nivelSeleccionado').text(grupoSeleccionado ? grupoSeleccionado.nivel : '');

                $('#nombreAsignatura').text(r.asignatura);
                $('#nombreParalelo').text(r.paralelo);
                $('#infoGrupo').removeClass('d-none');

                var html = '';

                $.each(estudiantes, function (i, e) {

                    var nota = e.nota === null ? '' : e.nota;

                    html += '<tr>' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + escaparHtml(e.cedula) + '</td>' +
                        '<td>' + escaparHtml(e.apellido) + '</td>' +
                        '<td>' + escaparHtml(e.nombre) + '</td>' +
                        '<td><input type="text" class="form-control form-control-sm cajaNota" ' +
                        'data-i="' + i + '" maxlength="5" value="' + escaparHtml(nota) + '"></td>' +
                        '</tr>';
                });

                if (estudiantes.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted">' +
                        'No hay estudiantes matriculados en este grupo.</td></tr>';
                }

                $('#tablaNotas tbody').html(html);
                $('#total').text(estudiantes.length);
                $('#btnGuardar').prop('disabled', estudiantes.length === 0);

            }).fail(function (xhr) {

                var mensaje = 'No se pudieron cargar los estudiantes';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensajeNotas', mensaje, 'danger');

            });
        });

        // Guardar todas las notas del grupo (el servidor valida todo antes de guardar)
        $('#btnGuardar').click(function () {

            if (grupoActual === null) {
                return;
            }

            // Se arma la lista con la cedula (de la lista que llego del servidor) y la nota escrita
            var notas = [];
            $('.cajaNota').each(function () {
                var e = estudiantes[$(this).data('i')];
                notas.push({cedula: e.cedula, nota: $(this).val()});
            });

            $('#btnGuardar').prop('disabled', true);

            $.post('models/notas/guardar.php', {grupo: grupoActual, notas: notas}, function (r) {

                if (r.success) {
                    mostrarMensaje('#mensajeNotas', 'Notas guardadas correctamente (' + r.guardadas + ' modificada(s))', 'success');
                } else {
                    mostrarMensaje('#mensajeNotas', r.error, 'danger');
                }

            }, 'json').fail(function (xhr) {

                var mensaje = 'No se pudo conectar con el servidor';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarMensaje('#mensajeNotas', mensaje, 'danger');

            }).always(function () {
                $('#btnGuardar').prop('disabled', false);
            });
        });

        cargar();

    });
</script>
