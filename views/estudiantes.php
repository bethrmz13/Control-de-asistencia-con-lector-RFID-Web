<h3>Estudiantes</h3>
<div id="mensaje"></div>

<div class="d-flex justify-content-between mb-3">
    <input type="text" id="buscar" class="form-control w-25" placeholder="Buscar...">
    <button type="button" id="btnNuevo" class="btn btn-primary">Nuevo</button>
</div>

<table class="table table-striped" id="tabla">
    <thead>
        <tr><th>Cédula</th><th>Tarjeta</th><th>Apellidos y nombres</th><th>Correo</th><th>Carrera</th><th>Nivel</th><th></th></tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Ventana de Bootstrap para Nuevo y Editar -->
<div class="modal fade" id="modalEstudiante" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Estudiante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeModal"></div>
                <!-- Vacío = estudiante nuevo. Si tiene algo, es la cédula del estudiante que se edita -->
                <input type="hidden" id="editando">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" id="cedula" class="form-control" maxlength="10" inputmode="numeric" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="codigo" class="form-label">Código de la tarjeta</label>
                        <input type="text" id="codigo" class="form-control" maxlength="10" inputmode="numeric" autocomplete="off">
                        <div class="form-text">Pase la tarjeta por el lector: escribe el código y pulsa Enter.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" id="nombre" class="form-control" maxlength="60">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" id="apellido" class="form-control" maxlength="60">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" id="correo" class="form-control" maxlength="80">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="carrera" class="form-label">Carrera</label>
                        <select id="carrera" class="form-select"></select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nivel" class="form-label">Nivel de inicio: <strong id="nivelValor">1</strong></label>
                        <input type="range" id="nivel" class="form-range" min="1" max="10" value="1">
                        <div class="form-text" id="ayudaNivel"></div>
                    </div>
                </div>

                <!-- Usuario y clave: solo al crear -->
                <div class="row" id="bloqueUsuario">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" id="username" class="form-control" maxlength="30" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="clave" class="form-label">Clave</label>
                        <input type="password" id="clave" class="form-control" maxlength="50" autocomplete="new-password">
                    </div>
                </div>

                <h6>Tabla de materias</h6>
                <table class="table table-sm" id="tablaMaterias">
                    <thead>
                        <tr><th>Nivel</th><th>Asignatura</th><th>Estado</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
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

        var modal = new bootstrap.Modal(document.getElementById('modalEstudiante'));
        var lista = [];             // lo que llegó del servidor; los botones guardan la posición en esta lista
        var perfil = '';            // perfil del usuario (el coordinador tiene la carrera fija)
        var carreraUsuario = 0;
        var peticion = 0;           // para ignorar respuestas viejas de la tabla de materias

        var estados = [
            { valor: 'APROBADA', texto: 'Aprobada' },
            { valor: 'REPROBADA', texto: 'Reprobada' },
            { valor: 'AUN_NO_CURSA', texto: 'Aún no cursa' }
        ];

        function cargar() {
            $.getJSON('models/estudiantes/select.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                lista = r.estudiantes;
                perfil = r.perfil;
                carreraUsuario = r.carrera_usuario;
                var html = '';
                $.each(lista, function (i, e) {
                    html += '<tr>' +
                        '<td>' + escaparHtml(e.cedula) + '</td>' +
                        '<td>' + escaparHtml(e.codigo_rfid) + '</td>' +
                        '<td>' + escaparHtml(e.apellido) + ' ' + escaparHtml(e.nombre) + '</td>' +
                        '<td>' + escaparHtml(e.correo) + '</td>' +
                        '<td>' + escaparHtml(e.carrera) + '</td>' +
                        '<td>' + escaparHtml(e.nivel) + '</td>' +
                        '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary btnEditar" data-i="' + i + '">Editar</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btnEliminar" data-i="' + i + '">Eliminar</button>' +
                        '</td></tr>';
                });
                $('#tabla tbody').html(html);
                llenarCombo('#carrera', r.carreras, 'Seleccione');
            }).fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo cargar la lista', 'danger');
            });
        }

        function opcionesEstado(actual) {
            var html = '';
            $.each(estados, function (i, s) {
                html += '<option value="' + s.valor + '"' + (s.valor === actual ? ' selected' : '') + '>' + s.texto + '</option>';
            });
            return html;
        }

        // Pide al servidor la tabla de materias de la carrera. Con cédula (al editar) trae los estados guardados
        function cargarMaterias(cedula) {
            var carrera = $('#carrera').val();
            if (!carrera) {
                $('#tablaMaterias tbody').html('<tr><td colspan="3" class="text-muted">Seleccione la carrera</td></tr>');
                return;
            }
            var numero = ++peticion;
            $.getJSON('models/estudiantes/materias.php', {
                carrera: carrera,
                nivel: $('#nivel').val(),
                cedula: cedula
            }, function (r) {
                if (numero !== peticion) {
                    return;     // llegó una respuesta vieja: se ignora
                }
                if (r.error) {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                    return;
                }
                var html = '';
                $.each(r.materias, function (i, m) {
                    html += '<tr>' +
                        '<td>' + escaparHtml(m.nivel) + '</td>' +
                        '<td>' + escaparHtml(m.nombre) + '</td>' +
                        '<td><select class="form-select form-select-sm estadoMateria" data-id="' + escaparHtml(m.id) + '">' +
                        opcionesEstado(m.estado) + '</select></td></tr>';
                });
                if (html === '') {
                    html = '<tr><td colspan="3" class="text-muted">Esta carrera no tiene asignaturas</td></tr>';
                }
                $('#tablaMaterias tbody').html(html);
            }).fail(function () {
                mostrarMensaje('#mensajeModal', 'No se pudo cargar la tabla de materias', 'danger');
            });
        }

        function limpiarFormulario() {
            $('#editando').val('');
            $('#cedula').val('').prop('disabled', false);
            $('#codigo').val('');
            $('#nombre').val('');
            $('#apellido').val('');
            $('#correo').val('');
            $('#username').val('');
            $('#clave').val('');
            $('#nivel').val(1);
            $('#nivelValor').text('1');
            $('#ayudaNivel').text('');
            $('#bloqueUsuario').show();
            // El coordinador solo puede usar su carrera
            if (perfil === 'COORDINADOR') {
                $('#carrera').val(carreraUsuario).prop('disabled', true);
            } else {
                $('#carrera').val('').prop('disabled', false);
            }
            $('#mensajeModal').html('');
            $('#tablaMaterias tbody').html('');
        }

        // Validación del navegador (el servidor valida de nuevo, incluido el dígito verificador de la cédula)
        function validar() {
            var esNuevo = ($('#editando').val() === '');
            if (!/^[0-9]{10}$/.test($('#cedula').val().trim())) {
                mostrarMensaje('#mensajeModal', 'La cédula debe tener 10 dígitos', 'warning');
                $('#cedula').focus();
                return false;
            }
            if (!/^[0-9]{10}$/.test($('#codigo').val().trim())) {
                mostrarMensaje('#mensajeModal', 'El código de la tarjeta debe tener 10 dígitos', 'warning');
                $('#codigo').focus();
                return false;
            }
            if ($('#nombre').val().trim() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el nombre', 'warning');
                $('#nombre').focus();
                return false;
            }
            if ($('#apellido').val().trim() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el apellido', 'warning');
                $('#apellido').focus();
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($('#correo').val().trim())) {
                mostrarMensaje('#mensajeModal', 'Ingrese un correo válido', 'warning');
                $('#correo').focus();
                return false;
            }
            if (!$('#carrera').val()) {
                mostrarMensaje('#mensajeModal', 'Seleccione la carrera', 'warning');
                return false;
            }
            if (esNuevo && $('#username').val().trim() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el usuario', 'warning');
                $('#username').focus();
                return false;
            }
            if (esNuevo && $('#clave').val() === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese la clave', 'warning');
                $('#clave').focus();
                return false;
            }
            return true;
        }

        // El lector pulsa Enter al terminar el código: se pasa al siguiente campo y NO se envía nada
        $('#codigo').on('keydown', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#nombre').focus();
            }
        });

        // El número del nivel se ve mientras se mueve la barra; la tabla se pide al soltarla
        $('#nivel').on('input', function () {
            $('#nivelValor').text($(this).val());
        });
        $('#nivel').on('change', function () {
            cargarMaterias('');     // sin cédula: se recalculan los estados según el nivel
        });
        $('#carrera').on('change', function () {
            cargarMaterias('');
        });

        $('#btnNuevo').click(function () {
            limpiarFormulario();
            $('#tituloModal').text('Nuevo estudiante');
            modal.show();
            cargarMaterias('');
        });
        $('#modalEstudiante').on('shown.bs.modal', function () {
            // En un estudiante nuevo se empieza por la cédula
            if ($('#editando').val() === '') {
                $('#cedula').focus();
            }
        });

        // Los botones se crean al cargar la tabla, por eso el evento va en $(document)
        $(document).on('click', '.btnEditar', function () {
            var e = lista[$(this).data('i')];
            limpiarFormulario();
            $('#tituloModal').text('Editar estudiante');
            $('#editando').val(e.cedula);
            $('#cedula').val(e.cedula).prop('disabled', true);      // la cédula no se cambia
            $('#codigo').val(e.codigo_rfid);
            $('#nombre').val(e.nombre);
            $('#apellido').val(e.apellido);
            $('#correo').val(e.correo);
            $('#carrera').val(e.carrera_id).prop('disabled', true); // la carrera tampoco
            $('#nivel').val(e.nivel);
            $('#nivelValor').text(e.nivel);
            $('#ayudaNivel').text('Si mueve el nivel, la tabla de materias se recalcula.');
            $('#bloqueUsuario').hide();
            modal.show();
            cargarMaterias(e.cedula);   // con cédula: trae los estados que ya tiene
        });

        $('#btnGuardar').click(function () {
            if (!validar()) {
                return;
            }
            var esNuevo = ($('#editando').val() === '');

            // materias[id de la asignatura] = estado
            var materias = {};
            $('.estadoMateria').each(function () {
                materias[$(this).data('id')] = $(this).val();
            });

            var datos = {
                cedula: esNuevo ? $('#cedula').val().trim() : $('#editando').val(),
                codigo: $('#codigo').val().trim(),
                nombre: $('#nombre').val().trim(),
                apellido: $('#apellido').val().trim(),
                correo: $('#correo').val().trim(),
                carrera: $('#carrera').val(),
                nivel: $('#nivel').val(),
                materias: materias
            };
            if (esNuevo) {
                datos.username = $('#username').val().trim();
                datos.clave = $('#clave').val();
            }

            var url = esNuevo ? 'models/estudiantes/guardar.php' : 'models/estudiantes/update.php';
            $.post(url, datos, function (r) {
                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje', 'Estudiante guardado correctamente', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensajeModal', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnEliminar', function () {
            var e = lista[$(this).data('i')];
            if (!confirm('¿Eliminar al estudiante ' + e.apellido + ' ' + e.nombre + '? También se borran su usuario, matrículas y asistencias.')) {
                return;
            }
            $.post('models/estudiantes/delete.php', { cedula: e.cedula }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', 'Estudiante eliminado', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensaje', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        filtrarTabla('#buscar', '#tabla');
        cargar();
    });
</script>