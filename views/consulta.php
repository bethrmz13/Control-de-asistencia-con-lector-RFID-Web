<h3>Mis notas y asistencias</h3>

<div id="mensaje"></div>

<!-- Información del estudiante -->
<div class="card mb-4">

    <div class="card-header bg-primary text-white">
        Información del estudiante
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-2">
                <strong>Estudiante:</strong>
                <span id="nombreEstudiante">-</span>
            </div>

            <div class="col-md-4 mb-2">
                <strong>Carrera:</strong>
                <span id="carreraEstudiante">-</span>
            </div>

            <div class="col-md-2 mb-2">
                <strong>Nivel:</strong>
                <span id="nivelEstudiante">-</span>
            </div>

        </div>

    </div>

</div>

<!-- Notas y asistencias del periodo activo -->
<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">Notas y asistencias</h5>
    </div>

    <div class="card-body">

        <p>
            <strong>Periodo académico:</strong>
            <span id="periodo">-</span>
        </p>

        <div id="mensajeNotas"></div>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle"
                   id="tablaNotas">

                <thead class="table-dark">

                    <tr>
                        <th>Asignatura</th>
                        <th class="text-center">Paralelo</th>
                        <th class="text-center">Asistencias</th>
                        <th class="text-center">Nota</th>
                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

        <p class="text-muted mb-0">
            Total de asignaturas matriculadas:
            <strong id="totalMatriculas">0</strong>
        </p>

    </div>

</div>

<!-- Récord académico -->
<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">Récord académico</h5>
    </div>

    <div class="card-body">

        <div id="mensajeRecord"></div>

        <div class="row mb-3">

            <div class="col-md-4 mb-2">

                <div class="border rounded p-3 text-center">

                    <h4 class="text-success" id="totalAprobadas">0</h4>

                    <span>Materias aprobadas</span>

                </div>

            </div>

            <div class="col-md-4 mb-2">

                <div class="border rounded p-3 text-center">

                    <h4 class="text-danger" id="totalReprobadas">0</h4>

                    <span>Materias reprobadas</span>

                </div>

            </div>

            <div class="col-md-4 mb-2">

                <div class="border rounded p-3 text-center">

                    <h4 class="text-secondary" id="totalPendientes">0</h4>

                    <span>Materias aún no cursadas</span>

                </div>

            </div>

        </div>

        <input type="text"
               id="buscarRecord"
               class="form-control mb-3"
               style="max-width: 300px;"
               placeholder="Buscar asignatura...">

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle"
                   id="tablaRecord">

                <thead class="table-dark">

                    <tr>
                        <th>Nivel</th>
                        <th>Asignatura</th>
                        <th>Estado</th>
                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

        <p class="text-muted mb-0">

            Total de asignaturas:
            <strong id="totalRecord">0</strong>

        </p>

    </div>

</div>

<script>

    $(function () {

        // Consultar información, notas y asistencias.
        function cargarNotas() {

            $.getJSON(
                'models/consulta/notas.php',
                function (r) {

                    if (r.error) {

                        mostrarMensaje(
                            '#mensajeNotas',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    var estudiante = r.estudiante;

                    $('#nombreEstudiante').text(
                        estudiante.nombre + ' ' + estudiante.apellido
                    );

                    $('#carreraEstudiante').text(
                        estudiante.carrera
                    );

                    $('#nivelEstudiante').text(
                        estudiante.nivel
                    );

                    $('#periodo').text(
                        r.periodo === null
                            ? 'Sin periodo activo'
                            : r.periodo
                    );

                    var html = '';

                    if (r.periodo === null) {

                        mostrarMensaje(
                            '#mensajeNotas',
                            'No existe un periodo académico activo.',
                            'info'
                        );

                    } else if (r.notas.length === 0) {

                        mostrarMensaje(
                            '#mensajeNotas',
                            'No tiene asignaturas matriculadas en este periodo.',
                            'info'
                        );

                    }

                    $.each(r.notas, function (i, nota) {

                        var textoNota = '';

                        if (nota.nota === null) {

                            textoNota =
                                '<span class="text-muted">' +
                                'Sin nota</span>';

                        } else {

                            textoNota =
                                escaparHtml(nota.nota);

                        }

                        html += '<tr>' +

                            '<td>' +
                            escaparHtml(nota.asignatura) +
                            '</td>' +

                            '<td class="text-center">' +
                            escaparHtml(nota.paralelo) +
                            '</td>' +

                            '<td class="text-center">' +
                            escaparHtml(nota.asistencias) +
                            '</td>' +

                            '<td class="text-center">' +
                            textoNota +
                            '</td>' +

                            '</tr>';

                    });

                    $('#tablaNotas tbody').html(html);

                    $('#totalMatriculas').text(
                        r.notas.length
                    );

                }

            ).fail(function (xhr) {

                var mensaje =
                    'No se pudieron cargar las notas y asistencias';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensajeNotas',
                    mensaje,
                    'danger'
                );

            });

        }

        // Consultar el récord académico.
        function cargarRecord() {

            $.getJSON(
                'models/consulta/record.php',
                function (r) {

                    if (r.error) {

                        mostrarMensaje(
                            '#mensajeRecord',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    var html = '';

                    var aprobadas = 0;
                    var reprobadas = 0;
                    var pendientes = 0;

                    $.each(r.record, function (i, materia) {

                        var clase = 'bg-secondary';

                        if (materia.estado === 'APROBADA') {

                            clase = 'bg-success';
                            aprobadas++;

                        } else if (materia.estado === 'REPROBADA') {

                            clase = 'bg-danger';
                            reprobadas++;

                        } else if (
                            materia.estado === 'AUN_NO_CURSA'
                        ) {

                            pendientes++;

                        }

                        html += '<tr>' +

                            '<td>' +
                            escaparHtml(materia.nivel) +
                            '</td>' +

                            '<td>' +
                            escaparHtml(materia.asignatura) +
                            '</td>' +

                            '<td>' +

                            '<span class="badge ' + clase + '">' +
                            escaparHtml(materia.estado) +
                            '</span>' +

                            '</td>' +

                            '</tr>';

                    });

                    if (r.record.length === 0) {

                        mostrarMensaje(
                            '#mensajeRecord',
                            'No existen registros académicos.',
                            'info'
                        );

                    }

                    $('#tablaRecord tbody').html(html);

                    $('#totalAprobadas').text(aprobadas);

                    $('#totalReprobadas').text(reprobadas);

                    $('#totalPendientes').text(pendientes);

                    $('#totalRecord').text(r.record.length);

                }

            ).fail(function (xhr) {

                var mensaje =
                    'No se pudo cargar el récord académico';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensajeRecord',
                    mensaje,
                    'danger'
                );

            });

        }

        // Activar buscador del récord académico.
        filtrarTabla('#buscarRecord', '#tablaRecord');

        // Cargar la información al abrir la página.
        cargarNotas();
        cargarRecord();

    });

</script>
