<?php $u = usuarioActual(); $esCoordinador = ($u !== null && $u['perfil'] === 'COORDINADOR'); ?>
<h3>Horarios</h3>
<div id="mensaje"></div>

<div class="row g-2 align-items-end mb-3">
    <div id="campoCarreraHorario" class="col-md-3">
        <label for="carreraHorario" class="form-label">Carrera</label>
        <select id="carreraHorario" class="form-select" disabled><option value="">Seleccione una carrera</option></select>
    </div>
    <div class="col-md-2">
        <label for="nivelHorario" class="form-label">Nivel</label>
        <select id="nivelHorario" class="form-select" disabled><option value="">Seleccione un nivel</option></select>
    </div>
    <div class="col-md-4">
        <label for="asignaturaHorario" class="form-label">Asignatura</label>
        <select id="asignaturaHorario" class="form-select" disabled><option value="">Seleccione una asignatura</option></select>
    </div>
    <div class="col-md-3">
        <label for="grupo" class="form-label">Paralelo</label>
        <select id="grupo" class="form-select" disabled><option value="">Seleccione un paralelo</option></select>
    </div>
</div>
<div class="d-flex justify-content-end mb-3">
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
        var grupos = [];
        var esCoordinador = <?php echo $esCoordinador ? 'true' : 'false'; ?>;
        var dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        function actualizarNiveles(carrera, nivelElegido) {
            var niveles = [];
            $.each(grupos, function (_, g) {
                var nivel = String(g.nivel);
                if (String(g.carrera_id) === String(carrera) && niveles.indexOf(nivel) < 0) niveles.push(nivel);
            });
            niveles.sort(function (a, b) { return Number(a) - Number(b); });
            llenarCombo('#nivelHorario', niveles.map(function (n) { return {id:n, texto:'Nivel ' + n}; }), 'Seleccione un nivel');
            $('#nivelHorario').prop('disabled', !carrera || !niveles.length);
            if (nivelElegido) $('#nivelHorario').val(String(nivelElegido));
            actualizarAsignaturas(carrera, $('#nivelHorario').val());
        }

        function actualizarAsignaturas(carrera, nivel, asignaturaElegida) {
            var asignaturas = [];
            $.each(grupos, function (_, g) {
                if (String(g.carrera_id) === String(carrera) && String(g.nivel) === String(nivel) &&
                    !asignaturas.some(function (a) { return String(a.id) === String(g.asignatura_id); })) {
                    asignaturas.push({id:g.asignatura_id, texto:g.asignatura});
                }
            });
            llenarCombo('#asignaturaHorario', asignaturas, 'Seleccione una asignatura');
            $('#asignaturaHorario').prop('disabled', !carrera || !nivel || !asignaturas.length);
            if (asignaturaElegida) $('#asignaturaHorario').val(String(asignaturaElegida));
            actualizarGrupos(carrera, nivel, $('#asignaturaHorario').val());
        }

        function actualizarGrupos(carrera, nivel, asignatura, grupoElegido) {
            var opciones = [];
            $.each(grupos, function (_, g) {
                if (String(g.carrera_id) === String(carrera) && String(g.nivel) === String(nivel) &&
                    String(g.asignatura_id) === String(asignatura)) {
                    opciones.push({id:g.id, texto:'Paralelo ' + g.paralelo});
                }
            });
            llenarCombo('#grupo', opciones, 'Seleccione un paralelo');
            $('#grupo').prop('disabled', !asignatura || !opciones.length);
            if (grupoElegido) $('#grupo').val(String(grupoElegido));
        }

        function limpiarHorarios() {
            lista = [];
            $('#tabla tbody').html('<tr><td colspan="5" class="text-center text-muted">Seleccione carrera, nivel, asignatura y paralelo para ver el horario.</td></tr>');
            $('#btnNuevo').prop('disabled', true);
        }

        // Carga los grupos del periodo activo y, si hay uno elegido, sus horarios
        function cargar() {
            var elegido = $('#grupo').val();
            $.getJSON('models/horarios/select.php', { grupo: elegido ? elegido : 0 }, function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'warning');
                    $('#btnNuevo').prop('disabled', true);
                    return;
                }
                grupos = r.grupos || [];
                if (grupos.length && (typeof grupos[0].carrera_id === 'undefined' || typeof grupos[0].asignatura_id === 'undefined')) {
                    mostrarMensaje('#mensaje', 'La página necesita la versión actualizada de models/horarios/select.php para cargar los filtros de carrera y nivel.', 'warning');
                    $('#carreraHorario, #nivelHorario, #asignaturaHorario, #grupo, #btnNuevo').prop('disabled', true);
                    $('#tabla tbody').html('<tr><td colspan="5" class="text-center text-muted">Actualice models/horarios/select.php y vuelva a cargar la página.</td></tr>');
                    return;
                }
                if (!grupos.length) {
                    mostrarMensaje('#mensaje', 'No hay grupos disponibles para su carrera en el periodo activo. Revise la sección Grupos o el periodo académico.', 'info');
                    $('#carreraHorario, #nivelHorario, #asignaturaHorario, #grupo, #btnNuevo').prop('disabled', true);
                    $('#tabla tbody').html('<tr><td colspan="5" class="text-center text-muted">No hay grupos disponibles para mostrar horarios.</td></tr>');
                    return;
                }
                var carreras = [];
                $.each(grupos, function (_, g) {
                    if (!carreras.some(function (c) { return String(c.id) === String(g.carrera_id); })) {
                        carreras.push({id:g.carrera_id, texto:g.carrera});
                    }
                });
                llenarCombo('#carreraHorario', carreras, 'Seleccione una carrera');
                $('#carreraHorario').prop('disabled', !carreras.length);
                if (elegido) {
                    var seleccionado = grupos.find(function (g) { return String(g.id) === String(elegido); });
                    if (seleccionado) {
                        $('#carreraHorario').val(String(seleccionado.carrera_id));
                        actualizarNiveles(seleccionado.carrera_id, seleccionado.nivel);
                        actualizarAsignaturas(seleccionado.carrera_id, seleccionado.nivel, seleccionado.asignatura_id);
                        actualizarGrupos(seleccionado.carrera_id, seleccionado.nivel, seleccionado.asignatura_id, elegido);
                    }
                } else {
                    llenarCombo('#nivelHorario', [], 'Seleccione un nivel');
                    llenarCombo('#asignaturaHorario', [], 'Seleccione una asignatura');
                    llenarCombo('#grupo', [], 'Seleccione un paralelo');
                    $('#nivelHorario, #asignaturaHorario, #grupo').prop('disabled', true);
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
                    html = '<tr><td colspan="5" class="text-center text-muted">Este grupo todavía no tiene horarios</td></tr>';
                } else if (!elegido) {
                    html = '<tr><td colspan="5" class="text-center text-muted">Seleccione carrera, nivel, asignatura y paralelo para ver el horario.</td></tr>';
                }
                $('#tabla tbody').html(html);
                $('#btnNuevo').prop('disabled', !$('#grupo').val());
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar la lista', 'danger');
            });
        }

        $('#carreraHorario').on('change', function () {
            limpiarHorarios();
            actualizarNiveles($(this).val());
        });
        $('#nivelHorario').on('change', function () {
            limpiarHorarios();
            actualizarAsignaturas($('#carreraHorario').val(), $(this).val());
        });
        $('#asignaturaHorario').on('change', function () {
            limpiarHorarios();
            actualizarGrupos($('#carreraHorario').val(), $('#nivelHorario').val(), $(this).val());
        });
        $('#grupo').on('change', function () {
            if ($(this).val()) cargar();
            else limpiarHorarios();
        });

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
