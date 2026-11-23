<h3>Cierre de periodo</h3>
<div id="mensaje"></div>

<div id="datosPeriodo" class="mb-3"></div>

<div id="bloqueFaltantes" style="display: none;">
    <div class="alert alert-warning">Faltan notas. No se puede cerrar el periodo hasta que estén todas.</div>
    <div class="table-responsive">
        <table class="table table-striped" id="tablaFaltantes">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Estudiante</th>
                    <th>Asignatura</th>
                    <th>Paralelo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<button type="button" id="btnCerrar" class="btn btn-danger" disabled>Cerrar periodo</button>

<div id="resultado" class="mt-4"></div>

<script>
    $(function () {

        function cargar() {
            $.getJSON('models/cierre/faltantes.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                if (r.periodo === null) {
                    $('#datosPeriodo').html('<div class="alert alert-secondary">No hay un periodo activo.</div>');
                    $('#bloqueFaltantes').hide();
                    $('#btnCerrar').prop('disabled', true);
                    return;
                }
                $('#datosPeriodo').html('<p><strong>Periodo activo:</strong> ' + escaparHtml(r.periodo.nombre) +
                    ' (' + escaparHtml(r.periodo.fecha_inicio) + ' a ' + escaparHtml(r.periodo.fecha_fin) + ')</p>');

                var faltantes = r.faltantes || [];
                if (faltantes.length > 0) {
                    var html = '';
                    $.each(faltantes, function (i, f) {
                        html += '<tr>' +
                            '<td>' + escaparHtml(f.cedula) + '</td>' +
                            '<td>' + escaparHtml(f.apellido + ' ' + f.nombre) + '</td>' +
                            '<td>' + escaparHtml(f.asignatura) + '</td>' +
                            '<td>' + escaparHtml(f.paralelo) + '</td>' +
                            '</tr>';
                    });
                    $('#tablaFaltantes tbody').html(html);
                    $('#bloqueFaltantes').show();
                    $('#btnCerrar').prop('disabled', true);
                } else {
                    $('#bloqueFaltantes').hide();
                    $('#btnCerrar').prop('disabled', false);
                }
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar el periodo', 'danger');
            });
        }

        $('#btnCerrar').click(function () {
            if (!confirm('¿Cerrar el periodo? Se pasarán las materias a aprobadas o reprobadas y se subirá de nivel a quien corresponda. No se puede deshacer.')) {
                return;
            }
            $('#btnCerrar').prop('disabled', true);
            $.post('models/cierre/cerrar.php', {}, function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    cargar();
                    return;
                }
                $('#mensaje').html('');
                var html = '<div class="alert alert-success">Periodo cerrado. Subieron de nivel: ' +
                    escaparHtml(r.subieron) + ' estudiante(s).</div>';
                var lista = r.porRevisar || [];
                if (lista.length > 0) {
                    html += '<h5>Estudiantes por revisar</h5>' +
                        '<p class="text-muted">Tienen materias reprobadas: el administrador define su nivel.</p>' +
                        '<ul class="list-group">';
                    $.each(lista, function (i, e) {
                        html += '<li class="list-group-item">' + escaparHtml(e.cedula) + ' - ' +
                            escaparHtml(e.apellido + ' ' + e.nombre) + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<p>No hay estudiantes por revisar.</p>';
                }
                $('#resultado').html(html);
                cargar();
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cerrar el periodo', 'danger');
                cargar();
            });
        });

        cargar();
    });
</script>
