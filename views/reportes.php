<?php $u = usuarioActual(); $esCoordinador = ($u !== null && $u['perfil'] === 'COORDINADOR'); ?>
<h3>Reportes</h3>

<div id="mensaje"></div>

<div class="card mb-4">

    <div class="card-header bg-primary text-white">Generar reporte en PDF</div>

    <div class="card-body">

        <p>
            <strong>Periodo académico:</strong>
            <span id="periodo">-</span>
        </p>

        <div class="row g-2">
            <div id="campoCarreraReporte" class="col-md-3 mb-3">
                <label for="carreraReporte" class="form-label">Carrera</label>
                <select id="carreraReporte" class="form-select" disabled><option value="">Seleccione una carrera</option></select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="nivelReporte" class="form-label">Nivel</label>
                <select id="nivelReporte" class="form-select" disabled><option value="">Seleccione un nivel</option></select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="asignaturaReporte" class="form-label">Asignatura</label>
                <select id="asignaturaReporte" class="form-select" disabled><option value="">Seleccione una asignatura</option></select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="grupo" class="form-label">Paralelo</label>
                <select id="grupo" class="form-select" disabled><option value="">Seleccione un paralelo</option></select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" id="fecha" class="form-control" value="<?php echo esc(date('Y-m-d')); ?>">
            </div>
        </div>

        <div class="mb-3">

            <label class="form-label d-block">Tipo de reporte</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="reporte" id="repAsistencia" value="asistencia" checked>
                <label class="form-check-label" for="repAsistencia">
                    Asistencia de la clase (presentes y faltas en la fecha elegida)
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="reporte" id="repTotal" value="total">
                <label class="form-check-label" for="repTotal">
                    Total de asistencias por estudiante (no usa la fecha)
                </label>
            </div>

        </div>

        <button type="button" id="btnGenerar" class="btn btn-primary" disabled>Generar PDF</button>

    </div>

</div>

<script>
    $(function () {

        var grupos = [];
        var esCoordinador = <?php echo $esCoordinador ? 'true' : 'false'; ?>;

        function actualizarNiveles(carrera) {
            var niveles = [];
            $.each(grupos, function (_, g) {
                var nivel = String(g.nivel);
                if (String(g.carrera_id) === String(carrera) && niveles.indexOf(nivel) < 0) niveles.push(nivel);
            });
            niveles.sort(function (a, b) { return Number(a) - Number(b); });
            llenarCombo('#nivelReporte', niveles.map(function (n) { return {id:n, texto:'Nivel ' + n}; }), 'Seleccione un nivel');
            $('#nivelReporte').prop('disabled', !carrera || !niveles.length);
            actualizarAsignaturas(carrera, $('#nivelReporte').val());
        }

        function actualizarAsignaturas(carrera, nivel) {
            var asignaturas = [];
            $.each(grupos, function (_, g) {
                if (String(g.carrera_id) === String(carrera) && String(g.nivel) === String(nivel) &&
                    !asignaturas.some(function (a) { return String(a.id) === String(g.asignatura_id); })) {
                    asignaturas.push({id:g.asignatura_id, texto:g.asignatura});
                }
            });
            llenarCombo('#asignaturaReporte', asignaturas, 'Seleccione una asignatura');
            $('#asignaturaReporte').prop('disabled', !carrera || !nivel || !asignaturas.length);
            actualizarParalelos(carrera, nivel, $('#asignaturaReporte').val());
        }

        function actualizarParalelos(carrera, nivel, asignatura) {
            var paralelos = [];
            $.each(grupos, function (_, g) {
                if (String(g.carrera_id) === String(carrera) && String(g.nivel) === String(nivel) &&
                    String(g.asignatura_id) === String(asignatura)) {
                    paralelos.push({id:g.id, texto:'Paralelo ' + g.paralelo});
                }
            });
            llenarCombo('#grupo', paralelos, 'Seleccione un paralelo');
            $('#grupo').prop('disabled', !asignatura || !paralelos.length);
            $('#btnGenerar').prop('disabled', !$('#grupo').val());
        }

        $('#carreraReporte').on('change', function () {
            actualizarNiveles($(this).val());
            $('#btnGenerar').prop('disabled', true);
        });
        $('#nivelReporte').on('change', function () {
            actualizarAsignaturas($('#carreraReporte').val(), $(this).val());
            $('#btnGenerar').prop('disabled', true);
        });
        $('#asignaturaReporte').on('change', function () {
            actualizarParalelos($('#carreraReporte').val(), $('#nivelReporte').val(), $(this).val());
        });
        $('#grupo').on('change', function () {
            $('#btnGenerar').prop('disabled', !$(this).val());
        });

        $('input[name="reporte"]').on('change', function () {
            $('#fecha').prop('disabled', $('#repTotal').is(':checked'));
        });

        // Carga los grupos (el servidor ya filtra por perfil)
        $.getJSON('models/reportes/grupos.php', function (r) {

            if (r.error) {
                mostrarMensaje('#mensaje', r.error, 'danger');
                $('#btnGenerar').prop('disabled', true);
                return;
            }

            $('#periodo').text(r.periodo);
            grupos = r.grupos || [];
            var carreras = [];
            $.each(grupos, function (_, g) {
                if (!carreras.some(function (c) { return String(c.id) === String(g.carrera_id); })) {
                    carreras.push({id:g.carrera_id, texto:g.carrera});
                }
            });
            llenarCombo('#carreraReporte', carreras, 'Seleccione una carrera');
            $('#carreraReporte').prop('disabled', !carreras.length);

            if (grupos.length === 0) {
                mostrarMensaje('#mensaje', 'No hay grupos disponibles para su perfil en este periodo.', 'info');
                $('#btnGenerar').prop('disabled', true);
            }

        }).fail(function (xhr) {

            var mensaje = 'No se pudieron cargar los grupos';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                mensaje = xhr.responseJSON.error;
            }
            mostrarMensaje('#mensaje', mensaje, 'danger');
            $('#btnGenerar').prop('disabled', true);

        });

        // Abre el PDF en una pestaña nueva
        $('#btnGenerar').click(function () {

            var grupo = $('#grupo').val();
            var fecha = $('#fecha').val();
            var tipo = $('input[name="reporte"]:checked').val();

            if (grupo === '') {
                mostrarMensaje('#mensaje', 'Seleccione un grupo para generar el reporte', 'warning');
                return;
            }

            if (tipo === 'asistencia') {

                if (fecha === '') {
                    mostrarMensaje('#mensaje', 'Seleccione la fecha de la clase', 'warning');
                    return;
                }

                window.open('reportes/asistencia_clase.php?grupo=' + encodeURIComponent(grupo) +
                    '&fecha=' + encodeURIComponent(fecha), '_blank');

            } else {

                window.open('reportes/total_estudiante.php?grupo=' + encodeURIComponent(grupo), '_blank');

            }

            $('#mensaje').html('');
        });

    });
</script>
