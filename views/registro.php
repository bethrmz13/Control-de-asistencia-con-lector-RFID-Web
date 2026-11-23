<?php
// Solo el ADMIN ve la simulacion de fecha y hora (se decide aqui, en el servidor)
$u = usuarioActual();
$esAdmin = ($u !== null && $u['perfil'] === 'ADMIN');
?>
<h3>Registro de asistencia</h3>
<p class="text-muted">Pase la tarjeta por el lector. El lector escribe el código y pulsa Enter.</p>

<form id="frmRegistro" autocomplete="off">
    <input type="text" id="codigo" class="form-control form-control-lg text-center" maxlength="20"
           placeholder="Pase la tarjeta..." autofocus>
</form>

<?php if ($esAdmin) { ?>
<div class="mt-3 d-flex flex-wrap align-items-center gap-2">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="chkSimular">
        <label class="form-check-label" for="chkSimular">Simular fecha y hora</label>
    </div>
    <input type="text" id="simulada" class="form-control" style="max-width: 220px;"
           placeholder="2026-11-09 16:10" disabled>
</div>
<?php } ?>

<div id="panelResultado" class="rounded p-4 mt-4 text-center border" style="background: #ffffff; min-height: 220px;">
    <h2 id="resNombre"></h2>
    <p class="fs-5 mb-1" id="resCarrera"></p>
    <p class="fs-5 mb-1" id="resDetalle"></p>
    <h3 class="mt-3" id="resMensaje">Esperando una tarjeta...</h3>
</div>

<script>
    $(function () {
        var ocupado = false;

        // El foco vuelve a la caja despues de cada lectura y al hacer clic en cualquier parte
        function enfocar() {
            $('#codigo').focus();
        }
        $(document).on('click', function (e) {
            if (!$(e.target).is('#chkSimular, #simulada, label')) {
                enfocar();
            }
        });

        $('#chkSimular').change(function () {
            $('#simulada').prop('disabled', !this.checked);
            if (this.checked) {
                $('#simulada').focus();
            } else {
                enfocar();
            }
        });

        function pintar(color, nombre, carrera, detalle, mensaje) {
            $('#panelResultado').css('background', color);
            $('#resNombre').text(nombre);
            $('#resCarrera').text(carrera);
            $('#resDetalle').text(detalle);
            $('#resMensaje').text(mensaje);
        }

        function mostrar(r) {
            if (r.error) {
                pintar('#ff9696', '', '', '', r.error);
                return;
            }
            var color = '#ff9696';                                  // rojo: los demas casos
            if (r.estado === 'REGISTRADO') {
                color = '#90ee90';                                  // verde
            } else if (r.estado === 'YA_REGISTRADO') {
                color = '#ffeb82';                                  // amarillo
            }
            var nombre = '';
            var carrera = '';
            if (r.estudiante) {
                nombre = r.estudiante.nombre + ' ' + r.estudiante.apellido;
                carrera = r.estudiante.carrera + ' - Nivel ' + r.estudiante.nivel;
            }
            var detalle = r.hora;
            if (r.asignatura) {
                detalle = r.asignatura + ' - Paralelo ' + r.paralelo + ' - ' + r.hora;
            }
            pintar(color, nombre, carrera, detalle, r.mensaje);
        }

        // Enter: el lector pulsa Enter al terminar y eso envia el formulario
        $('#frmRegistro').submit(function (e) {
            e.preventDefault();
            var codigo = $('#codigo').val().trim();
            if (codigo === '' || ocupado) {
                return;
            }
            var datos = { codigo: codigo };
            if ($('#chkSimular').length && $('#chkSimular').is(':checked')) {
                datos.simulada = $('#simulada').val().trim();
            }
            ocupado = true;
            $('#codigo').val('');
            $.post('models/registro/registrar.php', datos, function (r) {
                mostrar(r);
            }, 'json').fail(function (xhr) {
                var mensaje = 'No se pudo conectar con el servidor';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                pintar('#ff9696', '', '', '', mensaje);
            }).always(function () {
                ocupado = false;
                enfocar();
            });
        });
    });
</script>
