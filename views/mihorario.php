<h3>Mi horario</h3>

<div id="mensaje"></div>

<!-- Información del usuario -->
<div class="card mb-4">

    <div class="card-header bg-primary text-white">
        Información del usuario
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-5 mb-2">
                <strong>Usuario:</strong>
                <span id="nombreUsuario">-</span>
            </div>

            <div class="col-md-3 mb-2">
                <strong>Perfil:</strong>
                <span id="perfilUsuario">-</span>
            </div>

            <div class="col-md-4 mb-2">
                <strong>Periodo:</strong>
                <span id="periodo">-</span>
            </div>

        </div>

    </div>

</div>

<!-- Tabla de horarios -->
<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">Horario de clases</h5>
    </div>

    <div class="card-body">

        <div class="row mb-3">

            <div class="col-md-5 mb-2">

                <input type="text"
                       id="buscar"
                       class="form-control"
                       placeholder="Buscar asignatura o aula...">

            </div>

            <div class="col-md-3 mb-2">

                <select id="filtroDia" class="form-select">

                    <option value="">Todos los días</option>
                    <option value="1">Lunes</option>
                    <option value="2">Martes</option>
                    <option value="3">Miércoles</option>
                    <option value="4">Jueves</option>
                    <option value="5">Viernes</option>
                    <option value="6">Sábado</option>
                    <option value="7">Domingo</option>

                </select>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle"
                   id="tablaHorario">

                <thead class="table-dark">

                    <tr>
                        <th>Día</th>
                        <th>Hora de inicio</th>
                        <th>Hora de fin</th>
                        <th>Asignatura</th>
                        <th>Paralelo</th>
                        <th>Aula</th>
                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

        <p class="text-muted mb-0">
            Horarios mostrados:
            <strong id="totalHorarios">0</strong>
        </p>

    </div>

</div>

<!-- Cuadrícula semanal -->
<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">Horario semanal</h5>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered align-top"
                   id="tablaSemanal">

                <thead class="table-dark text-center">

                    <tr>
                        <th>Lunes</th>
                        <th>Martes</th>
                        <th>Miércoles</th>
                        <th>Jueves</th>
                        <th>Viernes</th>
                        <th>Sábado</th>
                        <th>Domingo</th>
                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

        <p class="text-muted mb-0">
            Distribución semanal de las clases del periodo activo.
        </p>

    </div>

</div>

<script>

    $(function () {

        var horarios = [];

        // Consultar los horarios del usuario.
        function cargar() {

            $.getJSON(
                'models/mihorario/select.php',
                function (r) {

                    if (r.error) {

                        mostrarMensaje(
                            '#mensaje',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    $('#nombreUsuario').text(r.usuario);

                    $('#perfilUsuario').text(r.perfil);

                    $('#periodo').text(
                        r.periodo === null
                            ? 'Sin periodo activo'
                            : r.periodo
                    );

                    horarios = r.horarios;

                    if (r.periodo === null) {

                        mostrarMensaje(
                            '#mensaje',
                            'No existe un periodo académico activo.',
                            'info'
                        );

                    } else if (horarios.length === 0) {

                        mostrarMensaje(
                            '#mensaje',
                            'No tiene horarios registrados.',
                            'info'
                        );

                    }

                    mostrarTabla();

                    mostrarSemanal();

                }

            ).fail(function (xhr) {

                var mensaje = 'No se pudieron cargar los horarios';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensaje',
                    mensaje,
                    'danger'
                );

            });

        }

        // Mostrar la tabla aplicando los filtros.
        function mostrarTabla() {

            var html = '';

            var total = 0;

            var texto = $('#buscar').val().toLowerCase();

            var diaSeleccionado = $('#filtroDia').val();

            $.each(horarios, function (i, h) {

                var contenido = (
                    h.asignatura + ' ' +
                    (h.aula || '') + ' ' +
                    h.dia + ' ' +
                    h.paralelo
                ).toLowerCase();

                var coincideTexto =
                    contenido.indexOf(texto) !== -1;

                var coincideDia =
                    diaSeleccionado === '' ||
                    String(h.dia_semana) === diaSeleccionado;

                if (coincideTexto && coincideDia) {

                    html += '<tr>' +

                        '<td>' +
                        escaparHtml(h.dia) +
                        '</td>' +

                        '<td>' +
                        escaparHtml(h.hora_inicio) +
                        '</td>' +

                        '<td>' +
                        escaparHtml(h.hora_fin) +
                        '</td>' +

                        '<td>' +
                        escaparHtml(h.asignatura) +
                        '</td>' +

                        '<td>' +
                        escaparHtml(h.paralelo) +
                        '</td>' +

                        '<td>' +
                        escaparHtml(h.aula || 'Sin aula') +
                        '</td>' +

                        '</tr>';

                    total++;

                }

            });

            if (total === 0) {

                html = '<tr>' +

                    '<td colspan="6" class="text-center text-muted">' +

                    'No existen horarios para mostrar.' +

                    '</td></tr>';

            }

            $('#tablaHorario tbody').html(html);

            $('#totalHorarios').text(total);

        }

        // Distribuir las clases por día de la semana.
        function mostrarSemanal() {

            var html = '<tr>';

            for (var dia = 1; dia <= 7; dia++) {

                html += '<td style="min-width: 150px;">';

                var tieneClases = false;

                $.each(horarios, function (i, h) {

                    if (Number(h.dia_semana) === dia) {

                        tieneClases = true;

                        html +=

                            '<div class="border border-primary rounded p-2 mb-2 bg-light">' +

                            '<div class="text-primary fw-bold small">' +

                            escaparHtml(h.hora_inicio) +

                            ' - ' +

                            escaparHtml(h.hora_fin) +

                            '</div>' +

                            '<div class="fw-semibold small mt-1">' +

                            escaparHtml(h.asignatura) +

                            '</div>' +

                            '<div class="small mt-1">' +

                            'Paralelo: ' +

                            escaparHtml(h.paralelo) +

                            '</div>' +

                            '<div class="small text-muted">' +

                            'Aula: ' +

                            escaparHtml(h.aula || 'Sin aula') +

                            '</div>' +

                            '</div>';

                    }

                });

                if (!tieneClases) {

                    html +=

                        '<div class="text-center text-muted small py-3">' +

                        'Sin clases' +

                        '</div>';

                }

                html += '</td>';

            }

            html += '</tr>';

            $('#tablaSemanal tbody').html(html);

        }

        // Buscar por asignatura o aula.
        $('#buscar').on('keyup', function () {

            mostrarTabla();

        });

        // Filtrar por día.
        $('#filtroDia').on('change', function () {

            mostrarTabla();

        });

        cargar();

    });

</script>
