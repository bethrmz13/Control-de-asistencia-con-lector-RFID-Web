<h3>Reportes</h3>

<div id="mensaje"></div>

<div class="card mb-4">

    <div class="card-header bg-primary text-white">Generar reporte en PDF</div>

    <div class="card-body">

        <p>
            <strong>Periodo académico:</strong>
            <span id="periodo">-</span>
        </p>

        <div class="row">

            <div class="col-md-8 mb-3">
                <label for="grupo" class="form-label">Grupo</label>
                <select id="grupo" class="form-select">
                    <option value="">Seleccione un grupo</option>
                </select>
            </div>

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

        <button type="button" id="btnGenerar" class="btn btn-primary">Generar PDF</button>

    </div>

</div>

<script>
    $(function () {

        // Carga los grupos (el servidor ya filtra por perfil)
        $.getJSON('models/reportes/grupos.php', function (r) {

            if (r.error) {
                mostrarMensaje('#mensaje', r.error, 'danger');
                $('#btnGenerar').prop('disabled', true);
                return;
            }

            $('#periodo').text(r.periodo);
            llenarCombo('#grupo', r.grupos, 'Seleccione un grupo');

            if (r.grupos.length === 0) {
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
