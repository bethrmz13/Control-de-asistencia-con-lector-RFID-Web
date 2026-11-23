<h3>Periodos</h3>
<div id="mensaje"></div>

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <input type="text" id="buscarPeriodo" class="form-control" style="max-width: 320px;" placeholder="Buscar periodo...">
    <div>
        <a href="index.php?accion=cierre" class="btn btn-outline-warning">Ir al cierre de periodo</a>
        <button type="button" id="btnNuevoPeriodo" class="btn btn-primary">Nuevo periodo</button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="tablaPeriodos">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha de inicio</th>
                <th>Fecha de fin</th>
                <th>Estado</th>
                <th>Matrícula</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="modalPeriodo" tabindex="-1" aria-labelledby="tituloModalPeriodo" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalPeriodo">Nuevo periodo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeModalPeriodo"></div>
                <input type="hidden" id="periodoId">
                <div class="mb-3">
                    <label for="nombrePeriodo" class="form-label">Nombre</label>
                    <input type="text" id="nombrePeriodo" class="form-control" maxlength="60">
                </div>
                <div class="mb-3">
                    <label for="fechaInicio" class="form-label">Fecha de inicio</label>
                    <input type="date" id="fechaInicio" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="fechaFin" class="form-label">Fecha de fin</label>
                    <input type="date" id="fechaFin" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarPeriodo" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var modal = new bootstrap.Modal(document.getElementById('modalPeriodo'));
        var lista = [];

        function cargarPeriodos() {
            $.getJSON('models/periodos/select.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                lista = r.periodos || [];
                var html = '';
                $.each(lista, function (i, periodo) {
                    var activo = parseInt(periodo.activo, 10) === 1;
                    var abierta = parseInt(periodo.matricula_abierta, 10) === 1;
                    var estadoHtml = activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>';
                    var matriculaHtml = abierta
                        ? '<span class="badge bg-success">Abierta</span>'
                        : '<span class="badge bg-secondary">Cerrada</span>';
                    var botonActivar = activo
                        ? '<button type="button" class="btn btn-sm btn-success" disabled>Activo</button>'
                        : '<button type="button" class="btn btn-sm btn-outline-success btnActivarPeriodo" data-i="' + i + '">Activar</button>';
                    var botonMatricula = '<button type="button" class="btn btn-sm btn-outline-primary btnMatricula" data-i="' + i + '" data-abierta="' + (abierta ? 0 : 1) + '"' + (activo ? '' : ' disabled') + '>' + (abierta ? 'Cerrar matrícula' : 'Abrir matrícula') + '</button>';
                    html += '<tr>' +
                        '<td>' + escaparHtml(periodo.nombre) + '</td>' +
                        '<td>' + escaparHtml(periodo.fecha_inicio) + '</td>' +
                        '<td>' + escaparHtml(periodo.fecha_fin) + '</td>' +
                        '<td>' + estadoHtml + '</td>' +
                        '<td>' + matriculaHtml + '</td>' +
                        '<td class="text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btnEditarPeriodo me-1" data-i="' + i + '">Editar</button>' +
                        botonActivar + ' ' + botonMatricula +
                        '</td></tr>';
                });
                $('#tablaPeriodos tbody').html(html);
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar la lista de periodos', 'danger');
            });
        }

        function limpiarFormulario() {
            $('#periodoId').val('');
            $('#nombrePeriodo').val('');
            $('#fechaInicio').val('');
            $('#fechaFin').val('');
            $('#mensajeModalPeriodo').html('');
        }

        function validarFormulario() {
            if ($('#nombrePeriodo').val().trim() === '') {
                mostrarMensaje('#mensajeModalPeriodo', 'Ingrese el nombre del periodo', 'warning');
                $('#nombrePeriodo').focus();
                return false;
            }
            if ($('#fechaInicio').val() === '' || $('#fechaFin').val() === '') {
                mostrarMensaje('#mensajeModalPeriodo', 'Ingrese las fechas de inicio y fin', 'warning');
                return false;
            }
            if ($('#fechaInicio').val() >= $('#fechaFin').val()) {
                mostrarMensaje('#mensajeModalPeriodo', 'La fecha de inicio debe ser anterior a la fecha de fin', 'warning');
                return false;
            }
            return true;
        }

        $('#btnNuevoPeriodo').click(function () {
            limpiarFormulario();
            $('#tituloModalPeriodo').text('Nuevo periodo');
            modal.show();
        });

        $(document).on('click', '.btnEditarPeriodo', function () {
            var periodo = lista[$(this).data('i')];
            limpiarFormulario();
            $('#tituloModalPeriodo').text('Editar periodo');
            $('#periodoId').val(periodo.id);
            $('#nombrePeriodo').val(periodo.nombre);
            $('#fechaInicio').val(periodo.fecha_inicio);
            $('#fechaFin').val(periodo.fecha_fin);
            modal.show();
        });

        $('#btnGuardarPeriodo').click(function () {
            if (!validarFormulario()) {
                return;
            }
            var id = $('#periodoId').val();
            var url = id === '' ? 'models/periodos/guardar.php' : 'models/periodos/update.php';
            $.post(url, {
                id: id,
                nombre: $('#nombrePeriodo').val().trim(),
                fecha_inicio: $('#fechaInicio').val(),
                fecha_fin: $('#fechaFin').val()
            }, function (r) {
                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje', id === '' ? 'Periodo creado correctamente' : 'Periodo actualizado correctamente', 'success');
                    cargarPeriodos();
                } else {
                    mostrarMensaje('#mensajeModalPeriodo', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensajeModalPeriodo', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnActivarPeriodo', function () {
            var periodo = lista[$(this).data('i')];
            if (!confirm('¿Activar el periodo "' + periodo.nombre + '"? Los demás quedarán inactivos.')) {
                return;
            }
            $.post('models/periodos/activar.php', { id: periodo.id }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', 'Periodo activado correctamente', 'success');
                    cargarPeriodos();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnMatricula:not(:disabled)', function () {
            var periodo = lista[$(this).data('i')];
            var abierta = parseInt($(this).data('abierta'), 10);
            $.post('models/periodos/matricula.php', { id: periodo.id, abierta: abierta }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', abierta === 1 ? 'Matrícula abierta' : 'Matrícula cerrada', 'success');
                    cargarPeriodos();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        filtrarTabla('#buscarPeriodo', '#tablaPeriodos');
        cargarPeriodos();
    });
</script>