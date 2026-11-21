# Módulo de ejemplo: Asignaturas (versión básica)

Es el **patrón que sigue todo el equipo**: una vista con tabla, buscador y una ventana modal de Bootstrap para Nuevo y Editar, y un archivo de `models/` por acción que devuelve JSON. Se pega completo en el prompt de la IA, para que escriba su módulo igual.

**Tatiana** empieza por aquí: copia estos archivos a `views/asignaturas.php` y `models/asignaturas/`, y agrega lo que falta (está en su tarea). Los demás **no copian los archivos**, copian la forma de trabajar.

> Este ejemplo no se ha probado en un servidor. La primera persona que lo use debe abrirlo en su XAMPP y avisar si algo falla.

## Qué patrón enseña

- **Guardia al inicio** de cada archivo de `models/`: `exigirPerfil(array(...))`.
- **Consultas con `?`** (`prepare` y `bind_param`).
- **El filtro por carrera del coordinador se hace en el servidor.**
- **Respuestas JSON**: `{"success": true}` o `{"error": "mensaje"}`.
- **La vista no usa el número de fila**: cada botón guarda la posición en la lista que vino del servidor (`data-i`), así un buscador o un orden distinto no confunde a quién se edita.
- El texto que viene de la base se escapa con `escaparHtml` antes de mostrarlo.

## `models/asignaturas/select.php`

```php
<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));   // quién puede llamar a este archivo
$conn = conectar();

// El COORDINADOR solo ve su carrera: el filtro se hace aquí, en el servidor
$soloCarrera = ($usuario['perfil'] === 'COORDINADOR');
$carreraUsuario = (int) $usuario['carrera_id'];

if ($soloCarrera) {
    $stmt = $conn->prepare("SELECT a.id, a.nombre, a.nivel, a.carrera_id, c.nombre AS carrera
                            FROM asignatura a JOIN carrera c ON c.id = a.carrera_id
                            WHERE a.carrera_id = ? ORDER BY a.nivel, a.nombre");
    $stmt->bind_param("i", $carreraUsuario);
} else {
    $stmt = $conn->prepare("SELECT a.id, a.nombre, a.nivel, a.carrera_id, c.nombre AS carrera
                            FROM asignatura a JOIN carrera c ON c.id = a.carrera_id
                            ORDER BY c.nombre, a.nivel, a.nombre");
}
$stmt->execute();
$res = $stmt->get_result();
$asignaturas = array();
while ($fila = $res->fetch_assoc()) {
    $asignaturas[] = $fila;
}

// Carreras para el combo del formulario
if ($soloCarrera) {
    $stmt2 = $conn->prepare("SELECT id, nombre AS texto FROM carrera WHERE id = ?");
    $stmt2->bind_param("i", $carreraUsuario);
} else {
    $stmt2 = $conn->prepare("SELECT id, nombre AS texto FROM carrera ORDER BY nombre");
}
$stmt2->execute();
$res2 = $stmt2->get_result();
$carreras = array();
while ($fila = $res2->fetch_assoc()) {
    $carreras[] = $fila;
}

echo json_encode(array("asignaturas" => $asignaturas, "carreras" => $carreras));
$conn->close();
```

## `models/asignaturas/guardar.php`

```php
<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$nivel = isset($_POST['nivel']) ? (int) $_POST['nivel'] : 0;
$carrera = isset($_POST['carrera']) ? (int) $_POST['carrera'] : 0;

// Validación en el servidor (la del navegador no basta)
if ($nombre === '' || strlen($nombre) > 80) {
    echo json_encode(array("error" => "Ingrese el nombre (hasta 80 caracteres)"));
    exit;
}
if ($nivel < 1 || $nivel > 10) {
    echo json_encode(array("error" => "El nivel debe estar entre 1 y 10"));
    exit;
}
if ($carrera <= 0) {
    echo json_encode(array("error" => "Seleccione la carrera"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && $carrera != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede usar su carrera"));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare("INSERT INTO asignatura (carrera_id, nombre, nivel) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $carrera, $nombre, $nivel);

if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe una asignatura con ese nombre en la carrera"));
} else {
    echo json_encode(array("error" => "No se pudo guardar la asignatura"));
}
$conn->close();
```

## `models/asignaturas/update.php`

```php
<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$nivel = isset($_POST['nivel']) ? (int) $_POST['nivel'] : 0;
$carrera = isset($_POST['carrera']) ? (int) $_POST['carrera'] : 0;

if ($id <= 0 || $nombre === '' || strlen($nombre) > 80 || $nivel < 1 || $nivel > 10 || $carrera <= 0) {
    echo json_encode(array("error" => "Datos incompletos o inválidos"));
    exit;
}

$conn = conectar();

// Se comprueba que la asignatura exista y que el coordinador pueda tocarla
$stmt = $conn->prepare("SELECT carrera_id FROM asignatura WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "La asignatura no existe"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR'
        && ((int) $actual['carrera_id'] != (int) $usuario['carrera_id'] || $carrera != (int) $usuario['carrera_id'])) {
    echo json_encode(array("error" => "Solo puede modificar asignaturas de su carrera"));
    exit;
}

$stmt = $conn->prepare("UPDATE asignatura SET carrera_id = ?, nombre = ?, nivel = ? WHERE id = ?");
$stmt->bind_param("isii", $carrera, $nombre, $nivel, $id);

if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} elseif ($stmt->errno == 1062) {
    echo json_encode(array("error" => "Ya existe una asignatura con ese nombre en la carrera"));
} else {
    echo json_encode(array("error" => "No se pudo modificar la asignatura"));
}
$conn->close();
```

Aquí no se usa `affected_rows` para saber si existía: MySQL devuelve 0 cuando el valor **no cambió**. La existencia se comprueba con el `SELECT` de antes.

## `models/asignaturas/delete.php`

```php
<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array("error" => "Datos inválidos"));
    exit;
}

$conn = conectar();
$stmt = $conn->prepare("SELECT carrera_id FROM asignatura WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
if (!$actual) {
    echo json_encode(array("error" => "La asignatura no existe"));
    exit;
}
if ($usuario['perfil'] === 'COORDINADOR' && (int) $actual['carrera_id'] != (int) $usuario['carrera_id']) {
    echo json_encode(array("error" => "Solo puede eliminar asignaturas de su carrera"));
    exit;
}

$stmt = $conn->prepare("DELETE FROM asignatura WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("error" => "No se pudo eliminar la asignatura"));
}
$conn->close();
```

Este `delete.php` borra también en cascada los grupos, matrículas y notas. Por eso el ejemplo es **básico**: en su tarea, Tatiana lo bloquea si hay estudiantes matriculados.

## `views/asignaturas.php`

```php
<h3>Asignaturas</h3>
<div id="mensaje"></div>

<div class="d-flex justify-content-between mb-3">
    <input type="text" id="buscar" class="form-control w-25" placeholder="Buscar...">
    <button type="button" id="btnNuevo" class="btn btn-primary">Nuevo</button>
</div>

<table class="table table-striped" id="tabla">
    <thead>
        <tr><th>Nombre</th><th>Nivel</th><th>Carrera</th><th></th></tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Ventana de Bootstrap para Nuevo y Editar -->
<div class="modal fade" id="modalAsignatura" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Asignatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeModal"></div>
                <input type="hidden" id="id">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="nivel" class="form-label">Nivel (1 a 10)</label>
                    <input type="number" id="nivel" class="form-control" min="1" max="10">
                </div>
                <div class="mb-3">
                    <label for="carrera" class="form-label">Carrera</label>
                    <select id="carrera" class="form-select"></select>
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

        var modal = new bootstrap.Modal(document.getElementById('modalAsignatura'));
        var lista = [];     // lo que llegó del servidor; los botones guardan la posición en esta lista

        function cargar() {
            $.getJSON('models/asignaturas/select.php', function (r) {
                if (r.error) {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                    return;
                }
                lista = r.asignaturas;
                var html = '';
                $.each(lista, function (i, a) {
                    html += '<tr>' +
                        '<td>' + escaparHtml(a.nombre) + '</td>' +
                        '<td>' + escaparHtml(a.nivel) + '</td>' +
                        '<td>' + escaparHtml(a.carrera) + '</td>' +
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

        function limpiarFormulario() {
            $('#id').val('');
            $('#nombre').val('');
            $('#nivel').val('');
            $('#carrera').val('');
            $('#mensajeModal').html('');
        }

        // Validación del navegador (el servidor valida de nuevo)
        function validar() {
            var nombre = $('#nombre').val().trim();
            var nivel = parseInt($('#nivel').val(), 10);
            if (nombre === '') {
                mostrarMensaje('#mensajeModal', 'Ingrese el nombre', 'warning');
                $('#nombre').focus();
                return false;
            }
            if (isNaN(nivel) || nivel < 1 || nivel > 10) {
                mostrarMensaje('#mensajeModal', 'El nivel debe estar entre 1 y 10', 'warning');
                $('#nivel').focus();
                return false;
            }
            if ($('#carrera').val() === '') {
                mostrarMensaje('#mensajeModal', 'Seleccione la carrera', 'warning');
                return false;
            }
            return true;
        }

        $('#btnNuevo').click(function () {
            limpiarFormulario();
            $('#tituloModal').text('Nueva asignatura');
            modal.show();
        });

        // Los botones se crean al cargar la tabla, por eso el evento va en $(document)
        $(document).on('click', '.btnEditar', function () {
            var a = lista[$(this).data('i')];
            limpiarFormulario();
            $('#tituloModal').text('Editar asignatura');
            $('#id').val(a.id);
            $('#nombre').val(a.nombre);
            $('#nivel').val(a.nivel);
            $('#carrera').val(a.carrera_id);
            modal.show();
        });

        $('#btnGuardar').click(function () {
            if (!validar()) {
                return;
            }
            var url = ($('#id').val() === '') ? 'models/asignaturas/guardar.php' : 'models/asignaturas/update.php';
            $.post(url, {
                id: $('#id').val(),
                nombre: $('#nombre').val().trim(),
                nivel: $('#nivel').val(),
                carrera: $('#carrera').val()
            }, function (r) {
                if (r.success) {
                    modal.hide();
                    mostrarMensaje('#mensaje', 'Asignatura guardada correctamente', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensajeModal', r.error, 'danger');
                }
            }, 'json').fail(function () {
                mostrarMensaje('#mensajeModal', 'No se pudo conectar con el servidor', 'danger');
            });
        });

        $(document).on('click', '.btnEliminar', function () {
            var a = lista[$(this).data('i')];
            if (!confirm('¿Eliminar la asignatura ' + a.nombre + '?')) {
                return;
            }
            $.post('models/asignaturas/delete.php', { id: a.id }, function (r) {
                if (r.success) {
                    mostrarMensaje('#mensaje', 'Asignatura eliminada', 'success');
                    cargar();
                } else {
                    mostrarMensaje('#mensaje', r.error, 'danger');
                }
            }, 'json');
        });

        filtrarTabla('#buscar', '#tabla');
        cargar();
    });
</script>
```

## Qué hace falta en cualquier módulo nuevo

1. La vista usa `$.getJSON` para cargar y `$.post` para guardar, igual que arriba.
2. Cada archivo de `models/` empieza con `exigirPerfil(...)`.
3. Los permisos y los filtros (carrera, docente) se aplican en el servidor.
4. Si el módulo guarda en varias tablas, **sin transacciones**: si un paso falla, se borra lo ya guardado y se responde con el error.
