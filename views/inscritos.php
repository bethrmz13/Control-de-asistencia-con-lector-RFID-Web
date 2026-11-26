<?php $u = usuarioActual(); $esCoordinador = ($u !== null && $u['perfil'] === 'COORDINADOR'); ?>
<h3>Inscritos</h3>

<div id="mensaje"></div>

<!-- Selección en cadena: carrera, nivel, asignatura y paralelo -->
<div class="card mb-4">

    <div class="card-header bg-primary text-white">
        Selección del grupo
    </div>

    <div class="card-body">

        <p>
            <strong>Periodo académico:</strong>
            <span id="periodo">-</span>
        </p>

        <div class="row">

            <div id="campoCarreraInscritos" class="col-md-3 mb-3">
                <label for="carreraGrupoInscritos" class="form-label">Carrera</label>
                <select id="carreraGrupoInscritos" class="form-select"><option value="">Seleccione una carrera</option></select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="nivelGrupoInscritos" class="form-label">Nivel</label>
                <select id="nivelGrupoInscritos" class="form-select" disabled><option value="">Seleccione carrera</option></select>
            </div>
            <div class="col-md-4 mb-3">

                <label for="asignatura" class="form-label">
                    Asignatura
                </label>

                <select id="asignatura" class="form-select" disabled>
                    <option value="">Seleccione una asignatura</option>
                </select>

            </div>

            <div class="col-md-3 mb-3">

                <label for="paralelo" class="form-label">
                    Paralelo
                </label>

                <select id="paralelo"
                        class="form-select"
                        disabled>

                    <option value="">Seleccione un paralelo</option>

                </select>

            </div>

        </div>

        <div id="avisoFiltros" aria-live="polite"></div>

        <p class="text-muted mb-0">
                    Seleccione carrera, nivel, asignatura y paralelo
            para consultar los estudiantes matriculados.
        </p>

    </div>

</div>

<!-- Lista de estudiantes inscritos -->
<div class="card mb-4">

    <div class="card-header">

        <h5 class="mb-0">
            Estudiantes inscritos
        </h5>

    </div>

    <div class="card-body">

        <div id="mensajeLista"></div>

        <div id="informacionGrupo" class="d-none mb-3">

            <div class="row">

                <div class="col-md-4 mb-2">

                    <strong>Asignatura:</strong>
                    <span id="nombreAsignatura"></span>

                </div>

                <div class="col-md-2 mb-2">
                    <strong>Nivel:</strong>
                    <span id="nivelGrupoElegido"></span>
                </div>

                <div class="col-md-2 mb-2">

                    <strong>Paralelo:</strong>
                    <span id="nombreParalelo"></span>

                </div>

                <div class="col-md-4 mb-2">

                    <strong>Carrera:</strong>
                    <span id="nombreCarrera"></span>

                </div>

            </div>

        </div>

        <input type="text"
               id="buscar"
               class="form-control mb-3"
               style="max-width: 350px;"
               placeholder="Buscar por cédula o nombre..."
               disabled>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle"
                   id="tablaInscritos">

                <thead class="table-dark">

                    <tr>
                        <th>N.º</th>
                        <th>Cédula</th>
                        <th>Apellido</th>
                        <th>Nombre</th>
                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td colspan="4"
                            class="text-center text-muted">

                            Seleccione una carrera, nivel, asignatura y paralelo.

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="d-flex flex-wrap
                    justify-content-between
                    align-items-center gap-2">

            <p class="text-muted mb-0">

                Estudiantes mostrados:
                <strong id="totalMostrados">0</strong>

            </p>

            <p class="mb-0">

                <strong>Total de inscritos:</strong>

                <span id="totalInscritos"
                      class="badge bg-primary">

                    0

                </span>

            </p>

        </div>

    </div>

</div>

<script>

    $(function () {

        var grupos = [];
        var estudiantes = [];
        var solicitudLista = null;
        var esCoordinador = <?php echo $esCoordinador ? 'true' : 'false'; ?>;

        // Cargar asignaturas y grupos permitidos.
        function cargar() {

            $.getJSON(
                'models/inscritos/select.php',
                function (r) {

                    if (r.error) {

                        mostrarMensaje(
                            '#mensaje',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    $('#periodo').text(
                        r.periodo === null
                            ? 'Sin periodo activo'
                            : r.periodo
                    );

                    grupos = r.grupos;

                    var carreras = [];
                    var carrerasVistas = {};
                    $.each(grupos, function (_, grupo) {
                        if (!carrerasVistas[grupo.carrera_id]) {
                            carrerasVistas[grupo.carrera_id] = true;
                            carreras.push({id:grupo.carrera_id, texto:grupo.carrera});
                        }
                    });
                    llenarCombo('#carreraGrupoInscritos', carreras, 'Seleccione una carrera');

                    llenarCombo('#asignatura', [], 'Seleccione una carrera primero');
                    $('#asignatura').prop('disabled', true);

                    if (r.periodo === null) {

                        mostrarMensaje(
                            '#mensaje',
                            'No existe un periodo académico activo.',
                            'info'
                        );

                        $('#carreraGrupoInscritos').prop('disabled', true);

                    } else if (r.asignaturas.length === 0) {

                        mostrarMensaje(
                            '#mensaje',
                            'No existen grupos disponibles para su perfil.',
                            'info'
                        );

                        $('#carreraGrupoInscritos').prop('disabled', true);

                    }

                }

            ).fail(function (xhr) {

                var mensaje =
                    'No se pudieron cargar las asignaturas y grupos';

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

        // Limpiar la información del grupo anterior.
        function limpiarLista() {

            if (solicitudLista !== null) {

                solicitudLista.abort();

                solicitudLista = null;

            }

            estudiantes = [];

            $('#informacionGrupo').addClass('d-none');

            $('#nombreAsignatura').text('');

            $('#nombreParalelo').text('');

            $('#nombreCarrera').text('');
            $('#nivelGrupoElegido').text('');

            $('#mensajeLista').html('');

            $('#buscar')
                .val('')
                .prop('disabled', true);

            $('#totalInscritos').text(0);

            $('#totalMostrados').text(0);

            $('#tablaInscritos tbody').html(

                '<tr>' +

                '<td colspan="4" class="text-center text-muted">' +

                'Seleccione una carrera, nivel, asignatura y paralelo.' +

                '</td>' +

                '</tr>'

            );

        }

        $('#carreraGrupoInscritos').on('change', function () {
            limpiarLista();
            $('#avisoFiltros').html('');
            llenarCombo('#asignatura', [], 'Seleccione una asignatura');
            llenarCombo('#paralelo', [], 'Seleccione un paralelo');
            $('#asignatura, #paralelo').prop('disabled', true);
            var carrera = $(this).val();
            var niveles = [];
            $.each(grupos, function (_, grupo) {
                var nivel = String(grupo.nivel);
                if (String(grupo.carrera_id) === carrera && niveles.indexOf(nivel) < 0) niveles.push(nivel);
            });
            niveles.sort(function (a, b) { return Number(a) - Number(b); });
            llenarCombo('#nivelGrupoInscritos', niveles.map(function (n) { return {id:n, texto:'Nivel ' + n}; }), 'Seleccione un nivel');
            $('#nivelGrupoInscritos').prop('disabled', !carrera || !niveles.length);
            if (carrera && !niveles.length) {
                mostrarMensaje('#avisoFiltros', 'No hay grupos creados para esta carrera en el periodo activo.', 'info');
            }
        });

        $('#nivelGrupoInscritos').on('change', function () {
            limpiarLista();
            $('#avisoFiltros').html('');
            llenarCombo('#paralelo', [], 'Seleccione un paralelo');
            $('#paralelo').prop('disabled', true);
            var carrera = $('#carreraGrupoInscritos').val();
            var nivel = $(this).val();
            var asignaturas = [];
            var vistas = {};
            $.each(grupos, function (_, grupo) {
                if (String(grupo.carrera_id) === carrera && String(grupo.nivel) === nivel && !vistas[grupo.asignatura_id]) {
                    vistas[grupo.asignatura_id] = true;
                    asignaturas.push({id:grupo.asignatura_id, texto:grupo.asignatura});
                }
            });
            llenarCombo('#asignatura', asignaturas, asignaturas.length ? 'Seleccione una asignatura' : 'No hay asignaturas con grupos');
            $('#asignatura').prop('disabled', !asignaturas.length);
            if (nivel && !asignaturas.length) {
                mostrarMensaje('#avisoFiltros', 'No hay grupos de esta carrera y nivel en el periodo activo. Cree primero el grupo en la sección Grupos.', 'info');
            }
        });

        // Cargar los paralelos de la asignatura seleccionada.
        $('#asignatura').on('change', function () {

            limpiarLista();

            var asignaturaId = $(this).val();

            var paralelos = [];

            $.each(grupos, function (i, grupo) {

                if (
                    String(grupo.carrera_id) === String($('#carreraGrupoInscritos').val()) &&
                    String(grupo.nivel) === String($('#nivelGrupoInscritos').val()) &&
                    String(grupo.asignatura_id) === asignaturaId
                ) {

                    paralelos.push({

                        id: grupo.id,

                        texto: grupo.texto

                    });

                }

            });

            llenarCombo(
                '#paralelo',
                paralelos,
                'Seleccione un paralelo'
            );

            $('#paralelo').prop(
                'disabled',
                paralelos.length === 0
            );

        });

        // Consultar los estudiantes al elegir un paralelo.
        $('#paralelo').on('change', function () {

            limpiarLista();

            var grupoId = $(this).val();

            if (grupoId === '') {
                return;
            }

            cargarInscritos(grupoId);

        });

        // Obtener los estudiantes matriculados.
        function cargarInscritos(grupoId) {

            mostrarMensaje(
                '#mensajeLista',
                'Cargando estudiantes inscritos...',
                'info'
            );

            solicitudLista = $.getJSON(

                'models/inscritos/lista.php',

                {
                    grupo: grupoId
                },

                function (r) {

                    $('#mensajeLista').html('');

                    if (r.error) {

                        mostrarMensaje(
                            '#mensajeLista',
                            r.error,
                            'danger'
                        );

                        return;
                    }

                    estudiantes = r.estudiantes;

                    $('#nombreAsignatura').text(
                        r.asignatura
                    );

                    $('#nombreParalelo').text(
                        r.paralelo
                    );

                    $('#nombreCarrera').text(
                        r.carrera
                    );

                    $('#nivelGrupoElegido').text(r.nivel);

                    $('#informacionGrupo').removeClass(
                        'd-none'
                    );

                    $('#totalInscritos').text(
                        r.total
                    );

                    $('#buscar').prop(
                        'disabled',
                        estudiantes.length === 0
                    );

                    mostrarTabla();

                }

            ).fail(function (xhr, estado) {

                if (estado === 'abort') {
                    return;
                }

                var mensaje =
                    'No se pudieron consultar los estudiantes';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }

                mostrarMensaje(
                    '#mensajeLista',
                    mensaje,
                    'danger'
                );

            }).always(function () {

                solicitudLista = null;

            });

        }

        // Mostrar la lista aplicando el buscador.
        function mostrarTabla() {

            var html = '';

            var total = 0;

            var texto = $('#buscar')
                .val()
                .trim()
                .toLowerCase();

            $.each(estudiantes, function (i, estudiante) {

                var contenido = (

                    estudiante.cedula + ' ' +

                    estudiante.apellido + ' ' +

                    estudiante.nombre

                ).toLowerCase();

                if (contenido.indexOf(texto) !== -1) {

                    total++;

                    html += '<tr>' +

                        '<td>' +

                        total +

                        '</td>' +

                        '<td>' +

                        escaparHtml(estudiante.cedula) +

                        '</td>' +

                        '<td>' +

                        escaparHtml(estudiante.apellido) +

                        '</td>' +

                        '<td>' +

                        escaparHtml(estudiante.nombre) +

                        '</td>' +

                        '</tr>';

                }

            });

            if (total === 0) {

                var mensaje = estudiantes.length === 0

                    ? 'No existen estudiantes matriculados en este grupo.'

                    : 'No se encontraron estudiantes con esa búsqueda.';

                html = '<tr>' +

                    '<td colspan="4" class="text-center text-muted">' +

                    escaparHtml(mensaje) +

                    '</td>' +

                    '</tr>';

            }

            $('#tablaInscritos tbody').html(html);

            $('#totalMostrados').text(total);

        }

        // Activar el buscador de estudiantes.
        $('#buscar').on('keyup', function () {

            mostrarTabla();

        });

        cargar();

    });

</script>
