<h3>Horarios</h3>
<div id="mensaje"></div>

<div class="d-flex justify-content-between mb-3">
    <select id="grupo" class="form-select w-50"></select>
    <button type="button" id="btnNuevo" class="btn btn-primary" disabled>Nuevo</button>
</div>

<table class="table table-striped" id="tabla">
    <thead>
        <tr><th>Día</th><th>Inicio</th><th>Fin</th><th>Aula</th><th></th></tr>
    </thead>
    <tbody></tbody>
</table>

<div class="modal fade" id="modalHorario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Horario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeModal"></div>
                <input type="hidden" id="id">
                <div class="mb-3">
                    <label for="dia" class="form-label">Día</label>
                    <select id="dia" class="form-select">
                        <option value="">Seleccione</option>
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="7">Domingo</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="inicio" class="form-label">Hora de inicio</label>
                        <input type="time" id="inicio" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label for="fin" class="form-label">Hora de fin</label>
                        <input type="time" id="fin" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="aula" class="form-label">Aula</label>
                    <input type="text" id="aula" class="form-control" maxlength="20">
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

        var modal = new bootstrap.Modal(document.getElementById('modalHorario'));
        var lista = [];     
        var dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Carga los grupos del periodo activo y, si hay uno elegido, sus horarios
        function cargar() {
            var elegido = $('#grupo').val();
            $.getJSON('models/horarios/select.php', { grupo: elegido ? elegido : 0 }, function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'warning');
                    $('#btnNuevo').prop('disabled', true);
                    return;
                }
                llenarCombo('#grupo', r.grupos, 'Seleccione un grupo del periodo ' + r.periodo);
                if (elegido) {
                    $('#grupo').val(elegido);
                }
                lista = r.horarios;
                var html = '';
                $.each(lista, function (i, h) {
                    html += '<tr>' +
                        '<td>' + escaparHtml(dias[h.dia_semana]) + '</td>' +
                        '<td>' + escaparHtml(h.hora_inicio) + '</td>' +
                        '<td>' + escaparHtml(h.hora_fin) + '</td>' +
                        '<td>' + escaparHtml(h.aula) + '</td>' +
                        '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btnEditar" data-i="' + i + '">Editar</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btnEliminar" data-i="' + i + '">Eliminar</button>' +
                        '</td></tr>';
                });
                if (elegido && html === '') {
                    html = '<tr><td colspan="5" class="text-muted">Este grupo todavía no tiene horarios</td></tr>';
                }
                $('#tabla tbody').html(html);
                $('#btnNuevo').prop('disabled', !$('#grupo').val());
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar la lista', 'danger');
            });
        }

        function limpiarFormulario() {
            $('#id').val('');
            $('#dia').val('');
            $('#inicio').val('');
            $('#fin').val('');
            $('#aula').val('');
            $('#mensajeModal').html('');
        }

        // Validación del navegador 
        function validar() {
            if ($('#dia').val() === '') {
                mostrarMensaje('#mensajeModal', 'Seleccione el día', 'warning');
                return false;
            }
            if ($('#inicio').val() === '' || $('#fin').val() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese la hora de inicio y la de fin', 'warning');
                return false;
            }
            if ($('#inicio').val() >= $('#fin').val()) {
                mostrarMensaje('#mensajeModal', 'La hora de inicio debe ser menor que la de fin', 'warning');
                return false;
            }
            if ($('#aula').val().trim() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el aula', 'warning');
                $('#aula').focus();
                return false;
            }
            return true;
        }

        $('#grupo').change(cargar);

        $('#btnNuevo').click(function () {
            limpiarFormulario();
            $('#tituloModal').text('Nuevo horario');
            modal.show();
        });

        $(document).on('click', '.btnEditar', function () {
            var h = lista[$(this).data('i')];
            limpiarFormulario();
            $('#tituloModal').text('Editar horario');
            $('#id').val(h.id);
            $('#dia').val(h.dia_semana);
            $('#inicio').val(h.hora_inicio);
            $('#fin').val(h.hora_fin);
            $('#aula').val(h.aula);
            modal.show();
        });

        $('#btnGuardar').click(function () {
            if (!validar()) {
                return;
            }
            var url = ($('#id').val() === '') ? 'models/horarios/guardar.php' : 'models/horarios/update.php';
            $.post(url, {
                id: $('#id').val(),
                grupo: $('#grupo').val(),
                dia: $('#dia').val(),
                inicio: $('#inicio').val(),
                fin: $('#fin').val(),
                aula: $('#aula').val().trim()
            }, function (r) {
                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje', 'Horario guardado correctamente', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensajeModal', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnEliminar', function () {
            var h = lista[$(this).data('i')];
            if (!confirm('¿Eliminar la clase del ' + dias[h.dia_semana] + ' de ' + h.hora_inicio + ' a ' + h.hora_fin + '? También se borran sus asistencias.')) {
                return;
            }
            $.post('models/horarios/delete.php', { id: h.id }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', 'Horario eliminado', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        cargar();
    });
</script>