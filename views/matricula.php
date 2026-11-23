<h3>Matrícula</h3>
<div id="mensaje"></div>
<div id="avisoCerrada"></div>

<div class="mb-3">
    <strong id="textoContador">Matriculadas: 0 de 5</strong>
    <div class="progress mt-1" style="height: 22px;">
        <div class="progress-bar" id="barra" role="progressbar" style="width: 0%;"></div>
    </div>
</div>

<h5 class="mt-4">Grupos disponibles</h5>
<div class="table-responsive">
    <table class="table table-striped align-middle" id="tablaDisponibles">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Nivel</th>
                <th>Paralelo</th>
                <th>Docente</th>
                <th>Horario</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<h5 class="mt-4">Mis materias del periodo</h5>
<div class="table-responsive">
    <table class="table table-striped align-middle" id="tablaMis">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Paralelo</th>
                <th>Nota</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    $(function () {
        var disponibles = [];   // listas tal como llegan del servidor: los botones guardan la posicion (data-i)
        var mis = [];
        var abierta = false;

        function cargarMis() {
            return $.getJSON('models/matricula/mis.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                abierta = r.abierta;
                mis = r.materias || [];
                var total = parseInt(r.total, 10);
                var maximo = parseInt(r.maximo, 10);
                $('#textoContador').text('Matriculadas: ' + total + ' de ' + maximo);
                $('#barra').css('width', (total * 100 / maximo) + '%')
                           .toggleClass('bg-danger', total >= maximo)
                           .toggleClass('bg-success', total < maximo);

                var html = '';
                $.each(mis, function (i, m) {
                    var nota = (m.nota === null) ? 'Sin nota' : m.nota;
                    var deshabilitado = (!abierta || m.nota !== null) ? ' disabled' : '';
                    html += '<tr>' +
                        '<td>' + escaparHtml(m.asignatura) + '</td>' +
                        '<td>' + escaparHtml(m.paralelo) + '</td>' +
                        '<td>' + escaparHtml(nota) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-danger btnAnular" data-i="' + i + '"' + deshabilitado + '>Anular</button></td>' +
                        '</tr>';
                });
                if (html === '') {
                    html = '<tr><td colspan="4" class="text-center text-muted">No tiene materias matriculadas en este periodo</td></tr>';
                }
                $('#tablaMis tbody').html(html);

                if (abierta) {
                    $('#avisoCerrada').html('');
                } else {
                    $('#avisoCerrada').html('<div class="alert alert-warning">La matrícula está cerrada.</div>');
                }
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudieron cargar sus materias', 'danger');
            });
        }

        function cargarDisponibles() {
            $.getJSON('models/matricula/disponibles.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                disponibles = r.grupos || [];
                var html = '';
                $.each(disponibles, function (i, g) {
                    html += '<tr>' +
                        '<td>' + escaparHtml(g.asignatura) + '</td>' +
                        '<td>' + escaparHtml(g.nivel) + '</td>' +
                        '<td>' + escaparHtml(g.paralelo) + '</td>' +
                        '<td>' + escaparHtml(g.docente || 'Sin docente') + '</td>' +
                        '<td>' + escaparHtml(g.horarios) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-primary btnMatricular" data-i="' + i + '"' + (r.abierta ? '' : ' disabled') + '>Matricular</button></td>' +
                        '</tr>';
                });
                if (html === '') {
                    html = '<tr><td colspan="6" class="text-center text-muted">No hay grupos disponibles</td></tr>';
                }
                $('#tablaDisponibles tbody').html(html);
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudieron cargar los grupos disponibles', 'danger');
            });
        }

        function recargar() {
            cargarMis();
            cargarDisponibles();
        }

        $(document).on('click', '.btnMatricular', function () {
            var grupo = disponibles[$(this).data('i')];
            $.post('models/matricula/matricular.php', { grupo: grupo.id }, function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                } else {
                    mostrarMensaje('#mensaje', r.mensaje, 'success');
                }
                recargar();
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo matricular', 'danger');
            });
        });

        $(document).on('click', '.btnAnular', function () {
            var m = mis[$(this).data('i')];
            if (!confirm('¿Anular la matrícula de ' + m.asignatura + '?')) {
                return;
            }
            $.post('models/matricula/anular.php', { grupo: m.id }, function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                } else {
                    mostrarMensaje('#mensaje', r.mensaje, 'success');
                }
                recargar();
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo anular la matrícula', 'danger');
            });
        });

        recargar();
    });
</script>
