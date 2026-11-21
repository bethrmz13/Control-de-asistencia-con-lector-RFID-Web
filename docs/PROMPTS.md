# Prompts para la IA (versión web)

La IA no ve el proyecto, así que **hay que pegarle el contexto**: sin eso inventa nombres de columnas, de funciones y de archivos, y el código no funciona.

El ingeniero califica que sea **sencillo y con lo visto en clase**, y pregunta lo que no enseñó. Por eso los prompts piden código simple, con el estilo de clase y una explicación de cada archivo. **Lee el código, entiéndelo y pruébalo antes de subirlo**: tienes que poder explicarlo.

---

## 1. Cómo usarlos (en este orden)

1. Abre un **chat nuevo** en tu IA.
2. Pega el **bloque fijo** (sección 3), completo.
3. Pega los **archivos del proyecto** (sección 2), cada uno con su ruta arriba, por ejemplo `// ===== models/sesion.php =====`.
4. Pega **tu tarea** (sección 4). Pídele **una tarea a la vez**: "Haz solo la TAREA 1".
5. Copia los archivos a tu carpeta `htdocs/asistenciaweb` (dentro de tu rama) y **pruébalo en el navegador**.
6. Si falla, usa las plantillas de la sección 5.
7. Antes de abrir el PR, pídele que te explique tu código (sección 5).

Si la IA propone **cambiar un archivo que ya existe** (`template.php`, `model.php`, `sesion.php`...), **se ignora** y se avisa a Ali.

## 2. Archivos que se pegan (todos, los mismos)

Abre cada uno, copia todo su contenido y pégalo:

- `db/schema.sql`
- `models/config.php`, `models/conexion.php`, `models/sesion.php`, `models/funciones.php`
- `models/model.php`
- `js/comun.js`
- `views/template.php`
- Los cuatro archivos de `models/asignaturas/` y `views/asignaturas.php` de [MODULO_EJEMPLO.md](MODULO_EJEMPLO.md): **el módulo de ejemplo es el patrón a imitar**

## 3. Bloque fijo (igual para todos)

```text
Soy estudiante de Ingeniería de Software y trabajo en equipo en un proyecto universitario: la
versión WEB de un sistema de control de asistencia con lector RFID (el lector funciona como
teclado: escribe el código de la tarjeta y pulsa Enter).

ENTORNO (respétalo exactamente):
- PHP 7.4 o superior, PERO sin sintaxis de PHP 8: nada de match, argumentos con nombre,
  str_contains, el operador ?->, ni propiedades en el constructor. Usa array(...) o [...].
- mysqli (NO PDO), MySQL, HTML5, CSS con Bootstrap 5, JavaScript y jQuery 3.
- Bootstrap y jQuery están en css/ y js/ del proyecto (no uses CDN ni internet).
- Sin frameworks, sin Composer, sin librerías extra (solo FPDF para reportes).
- Se prueba en XAMPP (htdocs/asistenciaweb) y se entrega en un hosting compartido.

ESTRUCTURA DE CLASE (MVC):
- Todo entra por index.php?accion=nombre. Las vistas están en views/ (una por página, con su
  propio <script> de jQuery al final). Los archivos de models/<módulo>/ devuelven JSON y hay uno
  por acción: select.php, guardar.php, update.php, delete.php (y los que haga falta).
- La vista llama a esos archivos con $.getJSON, $.post o $.ajax.
- Respuestas: {"success": true, ...} o {"error": "mensaje"}.
- Cada módulo trabaja SOLO en sus archivos y NO llama código de otros módulos. Si necesita una
  lista (por ejemplo carreras o grupos), la consulta en su propio models/.

SEGURIDAD (obligatoria, aunque en clase no se haga):
- Todo archivo de models/ empieza con:
    require_once __DIR__ . '/../conexion.php';
    require_once __DIR__ . '/../sesion.php';
    header('Content-Type: application/json; charset=utf-8');
    $usuario = exigirPerfil(array('ADMIN', ...));   // los perfiles que pueden llamarlo
- Consultas SIEMPRE con prepare() y bind_param() y signos ?, nunca variables pegadas en el SQL.
- Todo texto que viene de la base se escapa antes de mostrarlo: en PHP con esc(), en JavaScript
  con escaparHtml() (de js/comun.js).
- Los permisos y los filtros se aplican en el SERVIDOR: el COORDINADOR solo ve su carrera
  ($usuario['carrera_id']) y el DOCENTE solo sus grupos ($usuario['id'] = grupo.docente_id).
  Nunca confíes en un dato del navegador para decidir un permiso.
- Valida siempre en el servidor, aunque el navegador ya valide.

DATOS:
- La cédula (estudiante.cedula) y el código de tarjeta (estudiante.codigo_rfid) son SIEMPRE texto
  de 10 dígitos con ceros a la izquierda. Nunca los conviertas a número. Hay validarCedula() y
  validarCodigoTarjeta() en models/funciones.php.
- La hora es SIEMPRE la del servidor: usa ahora() de models/funciones.php, nunca la del navegador.
- El periodo activo se obtiene con periodoActivo($conn) (models/funciones.php).
- dia_semana: 1 = lunes ... 7 = domingo (en PHP: date('N')).
- Dos horarios se cruzan si inicio1 < fin2 Y inicio2 < fin1 (mismo día).
- ATENCIÓN con mysqli: $stmt->affected_rows vale 0 cuando el UPDATE no cambió ningún valor. NO lo
  uses para saber si un registro existe: compruébalo antes con un SELECT.

ESTILO:
- Nivel de estudiante universitario: simple y claro, nombres en español, comentarios breves y
  naturales (no excesivos).
- Sin transacciones (nada de begin_transaction, commit ni rollback). Si un guardado en varias
  tablas falla a la mitad, borra lo que ya guardaste y responde con el error.
- Las ventanas de agregar y modificar usan el patrón del módulo de ejemplo: tabla con buscador,
  ventana modal de Bootstrap para Nuevo y Editar, botones Editar y Eliminar en cada fila. Los
  botones guardan la POSICIÓN en la lista que llegó del servidor (data-i), no el número de fila
  de la pantalla.

REGLAS DE TRABAJO:
- Los archivos que te pego abajo YA EXISTEN y NO puedes modificarlos. Si necesitas algo que no
  existe, dímelo en lugar de inventarlo.
- Entrégame cada archivo COMPLETO, uno por bloque de código, con su ruta (por ejemplo
  models/estudiantes/guardar.php). Nunca fragmentos sueltos.
- Después de cada archivo, explícame en pocas líneas qué hace.
- Hazme UNA tarea a la vez y espera a que te pida la siguiente.

ARCHIVOS DEL PROYECTO (ya existen):
[PEGAR AQUÍ los archivos de la sección 2]

MI TAREA:
[PEGAR AQUÍ la tarea de mi persona]
```

## 4. Tareas por persona

Cada tarea se pega en `MI TAREA:` del bloque fijo.

### Ali

```text
Haz estas tareas, una por una, en este orden:

TAREA 1 - Módulo Usuarios (solo ADMIN). Archivos: views/usuarios.php y models/usuarios/select.php,
guardar.php, update.php y delete.php. Sigue el patrón del módulo de ejemplo.
Campos: usuario (username, único, hasta 30 caracteres), clave (hasta 50; al EDITAR, si se deja
vacía NO se cambia), nombre (hasta 80), perfil (combo con Seleccione y los perfiles ADMIN,
COORDINADOR, DOCENTE, REGISTRO y ESTUDIANTE), carrera (combo, solo se habilita si el perfil es
COORDINADOR y entonces es obligatoria; en los demás perfiles se guarda NULL) y activo (casilla).
Reglas, en el servidor:
- El username no se repite.
- Debe quedar siempre al menos un ADMIN activo: no se puede eliminar, desactivar ni cambiar de
  perfil al último ADMIN activo. Mensaje claro.
- No se puede eliminar un usuario que esté vinculado a un estudiante (estudiante.usuario_id): se
  elimina desde la página Estudiantes.
- La lista NUNCA devuelve la clave.

TAREA 2 - Módulo Periodos (solo ADMIN). Archivos: views/periodos.php y models/periodos/select.php,
guardar.php, update.php, activar.php y matricula.php.
- Tabla de periodos con nombre, fechas, "Activo / Inactivo" y "Matrícula abierta / cerrada".
- Nuevo y Editar en una ventana modal: nombre (único), fecha de inicio y fecha de fin
  (<input type="date">). Validar que el inicio sea anterior al fin.
- Botón Activar: deja un solo periodo activo con UNA sola consulta:
  UPDATE periodo SET activo = IF(id = ?, 1, 0)
- Botón de alternar "Matrícula abierta" (checkbox o botón): solo se puede cambiar en el periodo
  ACTIVO; en uno inactivo está deshabilitado.
- Un botón "Ir al cierre de periodo" que sea un enlace a index.php?accion=cierre (la página del
  cierre la hace Damaris; aquí NO se implementa el cierre).
```

### Damaris

```text
Haz estas tareas, una por una, en este orden:

TAREA 1 - Registro de asistencia (perfiles ADMIN y REGISTRO). Archivos: views/registro.php y
models/registro/registrar.php.
Vista: una caja de texto grande con autofocus, que vuelve a tomar el foco después de cada lectura
y cuando se hace clic en cualquier parte de la página. Al pulsar Enter envía el código con $.post a
registrar.php, limpia la caja y muestra el resultado en un panel grande con el nombre del
estudiante, su carrera y nivel, la asignatura, el paralelo y la hora. Colores: verde si REGISTRADO,
amarillo si YA_REGISTRADO, rojo en los demás casos. El resultado queda en pantalla hasta la
siguiente lectura. Solo si el usuario es ADMIN (decídelo en PHP al armar la vista) se muestra una
casilla "Simular fecha y hora" con una caja de texto con formato 2026-11-09 16:10; para REGISTRO
esa casilla y esa caja NO existen.
registrar.php recibe codigo y, solo si el usuario es ADMIN, simulada (si el usuario NO es ADMIN se
IGNORA aunque llegue). Si simulada está mal escrita, responde error. Reglas, en este orden:
 1. Busca al estudiante por codigo_rfid. Si no existe: estado NO_ENCONTRADO.
 2. Busca el periodo activo. La fecha de "ahora" debe estar entre fecha_inicio y fecha_fin; si no
    hay periodo activo o la fecha está fuera: SIN_CLASES_HOY.
 3. Busca los horarios de HOY (dia_semana = date('N') de "ahora") de los grupos del periodo activo
    en los que el estudiante está matriculado (matricula -> grupo -> horario). Si no hay:
    SIN_CLASES_HOY.
 4. Elige el horario donde hora_inicio <= hora de ahora < hora_fin (la asistencia se puede
    registrar DURANTE TODA LA CLASE). Si ninguno cumple: FUERA_DE_HORARIO.
 5. Si ya existe asistencia (estudiante_cedula, horario_id, fecha): YA_REGISTRADO.
 6. Si no, la guarda. Si falla por duplicado (error 1062) vuelve a comprobar y responde
    YA_REGISTRADO; si falla por otra causa: ERROR_GUARDADO.
 7. Si guardó: REGISTRADO.
Responde un JSON con: estado, mensaje, estudiante (cedula, nombre, apellido, carrera, nivel),
asignatura, paralelo y hora (HH:MM). La hora y la fecha salen de ahora() (hora del servidor).

TAREA 2 - Matrícula del estudiante (perfil ESTUDIANTE). Archivos: views/matricula.php y
models/matricula/disponibles.php, matricular.php, anular.php y mis.php.
Al estudiante se le identifica con estudiante.usuario_id = $usuario['id'] (nunca por un dato del
navegador). Vista: dos tablas (grupos disponibles con botón Matricular, y mis materias del periodo
con botón Anular), un contador "Matriculadas: X de 5" con una barra de progreso de Bootstrap, y un
aviso si la matrícula está cerrada (los botones se deshabilitan).
- disponibles.php: si no hay periodo activo o la matrícula está cerrada, responde abierta=false.
  Si no, devuelve los grupos del periodo activo cuya asignatura es de la carrera del estudiante,
  de nivel menor o igual al suyo, con estado AUN_NO_CURSA o REPROBADA en registro_academico, y en
  las que NO está ya matriculado en ningún paralelo. Con asignatura, nivel, paralelo, docente y
  los horarios en texto (por ejemplo "Lun 16:00-18:00").
- matricular.php (recibe grupo): devuelve el PRIMER error, revisando en este orden:
  1) matrícula abierta, 2) el grupo es del periodo activo, 3) materia permitida (carrera, nivel y
  estado como arriba), 4) no está ya matriculado en esa asignatura en el periodo, 5) máximo 5
  materias en el periodo, 6) sin choque de horarios con sus otras materias (mismo día y horas que
  se cruzan).
- anular.php (recibe grupo): solo con la matrícula abierta y solo si la matrícula NO tiene nota.
- mis.php: sus matrículas del periodo activo (asignatura, paralelo, nota) y el total.

TAREA 3 - Cierre de periodo (solo ADMIN). Archivos: views/cierre.php y models/cierre/faltantes.php
y cerrar.php. La vista muestra el periodo activo, la lista de notas que faltan (si hay) y un botón
"Cerrar periodo" que pide confirmación y muestra al final los estudiantes "por revisar".
- faltantes.php: matrículas del periodo activo sin nota (estudiante, asignatura, paralelo).
- cerrar.php: SIN transacciones, revisando el resultado de cada paso y deteniéndose con un error
  claro si uno falla. Primero calcula todo en arreglos de PHP y después aplica los cambios.
  a) Si no hay periodo activo, o faltan notas, no cambia nada y responde error.
  b) Cada matrícula del periodo: nota >= 7 -> registro_academico de esa asignatura pasa a
     APROBADA; nota menor a 7 -> REPROBADA.
  c) Cada estudiante que tenía matrículas en el periodo: si TODAS sus matrículas del periodo
     quedaron aprobadas Y no tiene ninguna otra materia en estado REPROBADA en su registro
     académico, su nivel sube 1 (sin pasar del nivel más alto de las asignaturas de su carrera).
     En cualquier otro caso su nivel no cambia y se agrega a la lista "por revisar".
  d) Al final, el periodo queda con activo = 0 y matricula_abierta = 0.
  Responde: success, cuántos subieron de nivel y la lista por revisar (cédula, nombre y apellido).
```

### Evelyn

```text
Haz estas tareas, una por una, en este orden:

TAREA 1 - Módulo Estudiantes (ADMIN y COORDINADOR; el coordinador solo ve y toca los de su
carrera). Archivos: views/estudiantes.php y models/estudiantes/select.php, guardar.php, update.php,
delete.php y materias.php.
Campos: cédula (texto de 10 dígitos, es la clave; se valida con validarCedula() y NO se puede
cambiar al editar), código de tarjeta (10 dígitos, único, validarCodigoTarjeta()), nombre,
apellido, correo (formato válido), carrera (combo; para el coordinador queda fija en la suya),
nivel de inicio (<input type="range"> de 1 a 10 con el número a la vista) y, solo al crear:
usuario (username único) y clave.
- La caja del código de tarjeta es donde se pasa la tarjeta: el lector escribe y pulsa Enter. Al
  pulsar Enter se pasa el foco al siguiente campo y NO se envía el formulario.
- Al elegir la carrera y mover el nivel, la vista pide a materias.php la tabla de materias de la
  carrera: nivel de la asignatura menor al de inicio = APROBADA, las demás = AUN_NO_CURSA. Se
  muestra en una tabla con un combo de estado por fila (APROBADA, REPROBADA, AUN_NO_CURSA), que el
  admin puede corregir antes de guardar.
- guardar.php: valida todo en el servidor (cédula, tarjeta, correo, nivel de 1 a 10, que la carrera
  sea la del coordinador). Antes de guardar comprueba que NO existan ya la cédula, el código de
  tarjeta ni el username. Guarda SIN transacciones, en este orden: (1) el usuario con perfil
  ESTUDIANTE, (2) el estudiante con ese usuario_id, (3) una fila de registro_academico por cada
  materia de la lista. Para conocer el id del usuario recién creado usa $conn->insert_id. Si un
  paso falla, borra lo que ya guardó (primero el estudiante, luego el usuario) y responde el error.
- update.php: modifica los datos, el nivel y los estados de las materias (UPDATE de
  registro_academico por cada fila). La cédula no se modifica.
- delete.php: elimina al estudiante y también su usuario (las matrículas, materias y asistencias se
  borran solas por las claves foráneas).
- select.php devuelve la lista (sin claves) y las carreras para el combo.

TAREA 2 - Módulo Horarios (ADMIN y COORDINADOR; el coordinador solo los grupos de su carrera).
Archivos: views/horarios.php y models/horarios/select.php, guardar.php, update.php y delete.php.
Vista: un combo con los grupos del periodo activo (se muestra asignatura, nivel y paralelo); al
elegir uno se carga su tabla de horarios. Formulario: día (combo de Lunes a Domingo, guarda 1 a 7),
hora de inicio y de fin (<input type="time">) y aula.
Validaciones en el servidor al crear y al modificar (al modificar se excluye el mismo horario):
 - La hora de inicio es menor que la de fin.
 - No se cruza con otro horario del MISMO grupo el mismo día.
 - El mismo DOCENTE no puede tener dos clases a la vez (revisa los horarios de todos los grupos
   de ese docente en el periodo activo, el mismo día).
 - La misma AULA no puede tener dos clases a la vez (mismo día, horas que se cruzan).
 - No choca con las otras materias de los estudiantes YA matriculados en este grupo: si choca,
   el mensaje nombra al primer estudiante afectado.
Este horario es el "evento" que pide la guía: se agrega, modifica y elimina.
```

### Victoria

```text
Haz estas tareas, una por una, en este orden:

TAREA 1 - Módulo Grupos (ADMIN y COORDINADOR; el coordinador solo las asignaturas de su carrera).
Archivos: views/grupos.php y models/grupos/select.php, guardar.php, update.php y delete.php.
Trabaja con los grupos del PERIODO ACTIVO (al crear, periodo_id es el del periodo activo; si no
hay uno activo, responde error). Campos: asignatura (combo), paralelo (tres botones de opción A, B
y C) y docente (combo con los usuarios de perfil DOCENTE activos; puede quedar sin docente).
- No se repite la misma asignatura con el mismo paralelo en el periodo.
- Al CAMBIAR el docente de un grupo, se revisa que no choque con sus otras clases: para cada
  horario de este grupo, el docente no puede tener otro horario que se cruce el mismo día.
- No se puede eliminar un grupo que tenga estudiantes matriculados: mensaje claro.

TAREA 2 - Módulo Notas (ADMIN y DOCENTE; el docente solo sus grupos). Archivos: views/notas.php y
models/notas/select.php, grupos.php y guardar.php.
- Vista: filtros en cadena (nivel -> asignatura -> paralelo), o un combo de grupos, de los grupos
  del periodo activo; al elegir uno se muestra la tabla de estudiantes matriculados con una caja
  para la nota (vacía si no tiene). Botón Guardar notas del grupo.
- guardar.php recibe el grupo y todas las notas. En el SERVIDOR: (1) comprueba que el grupo sea
  del periodo activo y que sea del docente (el ADMIN puede todos). (2) Valida TODAS las notas ANTES
  de guardar: cada una vacía (se guarda NULL) o un número entre 0 y 10 con hasta 2 decimales
  (acepta coma decimal: se cambia por punto). Si alguna falla, no guarda ninguna y dice cuál.
  (3) Lee las notas actuales, guarda una por una con UPDATE matricula SET nota = ? ... y revisa que
  cada UPDATE se ejecutó. Si uno falla, devuelve las ya guardadas a su nota anterior y responde
  con el nombre del estudiante que falló. Para saber si la matrícula existe usa un SELECT, NO
  affected_rows (vale 0 si la nota no cambió).

TAREA 3 - Reportes en PDF con FPDF (ADMIN, COORDINADOR y DOCENTE). La librería FPDF está en la
carpeta fpdf19/ (require_once __DIR__ . '/../fpdf19/fpdf.php'). Archivos: views/reportes.php,
reportes/asistencia_clase.php y reportes/total_estudiante.php.
- Vista: combo de grupos (el DOCENTE ve los suyos, el COORDINADOR los de su carrera, el ADMIN
  todos, del periodo activo), una fecha (<input type="date">, hoy por defecto), dos botones de
  opción para elegir el reporte y un botón Generar que abre el PDF en una pestaña nueva
  (target _blank). Avisa si no hay grupo elegido.
- Los dos archivos de reportes/ empiezan con el guardia exigirPerfil(...) ANTES de generar
  cualquier salida, comprueban que el grupo sea del usuario (docente o carrera) y generan el PDF
  con la clase FPDF y Cell(), como en clase (reportes.php). Cada PDF lleva un título, la asignatura
  y el paralelo, la fecha, una tabla y la fila "Total".
- asistencia_clase.php (grupo, fecha): cada estudiante matriculado con PRESENTE o FALTA:
    SELECT e.cedula, e.apellido, e.nombre, h.hora_inicio, a.hora AS hora_llegada,
           IF(a.id IS NULL, 'FALTA', 'PRESENTE') AS estado
    FROM matricula m
    JOIN estudiante e ON e.cedula = m.estudiante_cedula
    JOIN horario h ON h.grupo_id = m.grupo_id
    LEFT JOIN asistencia a ON a.estudiante_cedula = e.cedula AND a.horario_id = h.id
                          AND a.fecha = ?
    WHERE m.grupo_id = ? AND h.dia_semana = WEEKDAY(?) + 1
    ORDER BY e.apellido, e.nombre
- total_estudiante.php (grupo): el total de asistencias de cada estudiante:
    SELECT e.cedula, e.apellido, e.nombre, COUNT(a.id) AS asistencias
    FROM matricula m
    JOIN estudiante e ON e.cedula = m.estudiante_cedula
    LEFT JOIN horario h ON h.grupo_id = m.grupo_id
    LEFT JOIN asistencia a ON a.horario_id = h.id AND a.estudiante_cedula = e.cedula
    WHERE m.grupo_id = ?
    GROUP BY e.cedula, e.apellido, e.nombre ORDER BY e.apellido
- Si la fecha no cae en un día de clase del grupo, el reporte sale vacío, sin error.
- El PDF también sirve como el "enlace para descargar archivos" que pide la guía.
```

### Tatiana

```text
Haz estas tareas, una por una, en este orden:

TAREA 1 - Módulo Asignaturas (ADMIN y COORDINADOR). Empieza copiando el módulo de ejemplo
(views/asignaturas.php y models/asignaturas/) y agrega lo que le falta:
- Al CREAR una asignatura, agrégala al registro académico de los estudiantes que ya existen en esa
  carrera, con estado AUN_NO_CURSA (un INSERT ... SELECT sobre estudiante y registro_academico),
  para que puedan matricularse en ella.
- NO se puede eliminar una asignatura si algún grupo suyo tiene estudiantes matriculados: mensaje
  claro (el DELETE no debe borrar notas en cascada).
- Si el COORDINADOR intenta tocar otra carrera, el servidor lo rechaza.

TAREA 2 - Mis notas y asistencias (perfil ESTUDIANTE, solo lectura). Archivos: views/consulta.php y
models/consulta/notas.php y record.php. Se identifica al estudiante con estudiante.usuario_id =
$usuario['id']; NUNCA se pide una cédula ni un código al navegador.
- Arriba, su nombre, su carrera y su nivel.
- Tabla 1, del periodo activo: una fila por matrícula con asignatura, paralelo, asistencias
  (COUNT de asistencia en los horarios de ese grupo) y nota ("Sin nota" si es NULL). Si no hay
  periodo activo, la tabla queda vacía con un aviso.
- Tabla 2, su récord académico: nivel, asignatura y estado (APROBADA, REPROBADA o AUN_NO_CURSA).
- No hay botones de guardar ni de modificar.

TAREA 3 - Mi horario (perfiles DOCENTE y ESTUDIANTE, solo lectura). Archivos: views/mihorario.php y
models/mihorario/select.php. El DOCENTE ve los horarios de sus grupos (grupo.docente_id = su id) y
el ESTUDIANTE los de los grupos en los que está matriculado, del periodo activo. Una tabla con día
(Lunes a Domingo), hora de inicio, hora de fin, asignatura, paralelo y aula, ordenada por día y
hora. Opcional si hay tiempo: una cuadrícula semanal armada con jQuery.

TAREA 4 - Inscritos (ADMIN, COORDINADOR y DOCENTE, solo lectura). Archivos: views/inscritos.php y
models/inscritos/select.php y lista.php. Dos combos en cadena, asignatura y paralelo, con las
asignaturas y los grupos del periodo activo que el usuario puede ver (el COORDINADOR los de su
carrera, el DOCENTE los suyos, el ADMIN todos). Al elegir se muestra la lista de estudiantes
matriculados (cédula, apellido y nombre) y el total.
```

### Brayan

```text
Haz estas tareas, una por una, en este orden. NO usan base de datos ni sesión: son páginas con HTML5,
CSS y jQuery. Los cuatro archivos ya existen en views/ con un contenido básico; mejóralos sin
cambiar su nombre. Cada página debe tener su PROPIO script de jQuery (la guía lo pide).

TAREA 1 - views/inicio.php: la página de bienvenida. Un presentación del sistema, un carrusel de
Bootstrap con 3 imágenes o una galería, y un efecto de jQuery (por ejemplo contadores que suben, o
tarjetas que aparecen con fadeIn al hacer scroll). Incluye contenido multimedia: un <video controls>
con un video corto del lector funcionando (images/demo.mp4, pesado a lo sumo 10 MB).

TAREA 2 - views/nosotros.php: quiénes somos. Tarjetas del equipo (Ali, Damaris, Evelyn, Victoria,
Tatiana y Brayan) y una sección con la descripción del proyecto en un acordeón de jQuery (slideToggle).

TAREA 3 - views/contactanos.php: formulario de contacto con validación en jQuery (campos
obligatorios, correo válido, mensajes de Bootstrap). No se envía a ninguna parte: al validar muestra
un mensaje de agradecimiento.

TAREA 4 - views/descargas.php: una lista de archivos para descargar con la etiqueta <a download>:
el manual de usuario (descargas/manual.pdf) y la guía del proyecto. Con un efecto de jQuery al pasar
el mouse. Prepara también el manual de usuario en PDF, corto, con capturas del sistema.

Regla de rutas: todas relativas (images/..., descargas/...), nunca C:\...
Las imágenes y el video deben pesar poco, porque se suben a un hosting gratuito.
```

## 5. Plantillas para cuando algo falla

**Error o página en blanco** (en el mismo chat):

```text
Al probar me sale este error (o la página sale en blanco). Corrige SOLO ese problema, sin cambiar
los archivos que ya existen, y devuélveme el archivo completo.
[PEGAR EL ERROR O LO QUE SE VE; en la consola del navegador (F12) y en el log de Apache de XAMPP]
```

**Para entender tu código** (antes de abrir el PR):

```text
Explícame este código como si tuviera que exponerlo al ingeniero: qué hace cada archivo, qué consulta
SQL ejecuta, cómo se valida, quién puede llamarlo y por qué. Dime qué pregunta me podría hacer sobre
él y cómo responderla con palabras sencillas.
```

**Si la IA usa algo que no vimos en clase:**

```text
Reescribe ese código con lo que vimos en clase: PHP con mysqli (prepare y bind_param), JSON, jQuery
($.ajax, $.post, $.getJSON) y Bootstrap. Sin frameworks, sin PDO, sin transacciones y sin sintaxis
de PHP 8.
```
