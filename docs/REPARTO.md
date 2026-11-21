# Reparto de tareas (versión web)

**Entrega:** jueves 26 de noviembre, **listo en el hosting el lunes 23**. Los prompts para la IA están en [PROMPTS.md](PROMPTS.md) y el módulo de ejemplo en [MODULO_EJEMPLO.md](MODULO_EJEMPLO.md).

## 1. Cómo se evita esperar a alguien

- **Ningún módulo llama código de otro.** Cada quien trabaja en su vista, su carpeta `models/<módulo>/` y, si hace falta, su JavaScript. Lo único compartido (conexión, sesión, funciones, plantilla, lista de páginas) ya está en el repositorio y no se toca.
- **Todas las páginas ya están registradas** en `models/model.php`: nadie edita ese archivo.
- **Cada módulo se prueba con los datos de `schema.sql`** (usuarios, estudiantes, asignaturas, grupos, horarios y matrículas de prueba). No hay que esperar a que otro suba su parte.
- Si necesitas **una lista de otro tema** (carreras, grupos, docentes...), la consultas en tu propio `models/`.

## 2. Orden de trabajo

| Día | Qué pasa |
|---|---|
| **Mié 18** | Ali sube la base y crea el hosting. Todos clonan, importan la base, abren el sitio y crean su rama. Cada quien empieza su primer módulo |
| **Jue 19 a sáb 21** | Cada quien termina sus módulos en **PR pequeños** (uno por módulo, con commits por paso). Se revisan y se mezclan a medida que llegan |
| **Dom 22** | Integración: Ali despliega todo en el hosting. **Cada quien prueba su perfil en el hosting** y anota lo que falla |
| **Lun 23** | **Listo en el hosting.** Solo correcciones, sin funciones nuevas |
| **Mar 24 y mié 25** | Colchón, ensayo y **aviso al ingeniero** con la dirección y los usuarios |
| **Jue 26** | Entrega |

## 3. Pull Requests y pruebas por persona

Los commits siguen `tipo(donde): descripción`. Un commit por cada paso que funciona.

### Ali

| PR sugerido | Prueba |
|---|---|
| `feat(usuarios): agrega el módulo de usuarios` | Crear un usuario COORDINADOR sin carrera: lo rechaza. Intentar desactivar o eliminar al único ADMIN: lo rechaza. Editar con la clave vacía: la clave no cambia. La lista no incluye la clave |
| `feat(periodos): agrega el módulo de periodos` | Fechas al revés: rechaza. Activar otro periodo: queda uno solo activo. Abrir la matrícula de un periodo inactivo: no se puede. El botón lleva a la página de cierre |
| `docs(hosting): agrega la guía de despliegue` | Con el sitio en el hosting: entran los 6 usuarios y cada perfil ve solo lo suyo |

**Debe poder explicar:** cómo funciona el login con sesión, el guardia `exigirPerfil` y por qué `config.local.php` no se sube a GitHub.

### Damaris

| PR sugerido | Prueba |
|---|---|
| `feat(registro): agrega la pantalla y la regla de asistencia` | Tabla de abajo |
| `feat(matricula): agrega la matrícula del estudiante` | Tabla de abajo |
| `feat(cierre): agrega el cierre de periodo` | Tabla de abajo |

**Asistencia** (lunes `2026-11-09`, con la simulación de `admin`; la asistencia se acepta **durante toda la clase**):

| Hora simulada | Tarjeta | Resultado esperado |
|---|---|---|
| 08:10 | `0001234567` (María) | `REGISTRADO`, Cálculo |
| 08:12 | `0001234567` | `YA_REGISTRADO` |
| 10:00 | `0001234567` | `FUERA_DE_HORARIO` (la clase termina a las 10:00) |
| 15:55 | `0001234567` | `FUERA_DE_HORARIO` (antes de que empiece) |
| 16:15 | `0001234567` | `REGISTRADO`, Programación Orientada a Objetos |
| 18:05 | `0001234567` | `REGISTRADO`, Base de Datos |
| 17:59 | `0007654321` (Luis) | `REGISTRADO`, Programación Orientada a Objetos |
| 08:05 | `0007654321` | `FUERA_DE_HORARIO` (hoy tiene clases, pero no en ese momento) |
| cualquiera | `9999999999` | `NO_ENCONTRADO` |
| martes 10:00 | `0001234567` | `SIN_CLASES_HOY` |
| `2026-08-10` 08:10 | `0001234567` | `SIN_CLASES_HOY` (fuera del periodo) |

Con `registro`: la casilla de simulación **no existe**, y aunque se mande el dato `simulada` desde la consola del navegador, el servidor lo ignora y usa su hora.

**Matrícula** (como `luis` o `maria`):

| Acción | Esperado |
|---|---|
| Luis ve las materias disponibles | No aparecen Cálculo (aprobada) ni Ingeniería de Software (nivel 3) |
| Luis intenta matricular Sistemas Operativos | Rechaza: choca con POO (lunes 16:00 a 18:00) |
| María matricula Estadística y luego Redes | Se matricula; ya lleva 5 |
| María matricula una sexta | Rechaza: máximo 5 |
| Matrícula cerrada (el admin la cierra) | Rechaza y los botones se deshabilitan |
| Anular una materia que ya tiene nota | Rechaza |

**Cierre** (hazlo en una base de la que puedas reimportar el script):

| Caso | Esperado |
|---|---|
| Faltan notas | No cierra y lista quiénes faltan |
| Todas las notas, María con 6.99 en Cálculo y Luis con 7 o más en todo | Luis sube a nivel 3 y María queda "por revisar" con Cálculo en `REPROBADA` |
| Después | El periodo queda inactivo y con la matrícula cerrada |

**Debe poder explicar:** el orden de las reglas de matrícula, cómo se detecta un choque de horarios y qué se hace si un paso del cierre falla.

### Evelyn

| PR sugerido | Prueba |
|---|---|
| `feat(estudiantes): agrega el módulo de estudiantes` | Cédula inválida (por ejemplo `1800000001`): rechaza. Cédula, tarjeta o usuario repetidos: rechaza **sin crear nada a medias**. Un estudiante nuevo con la tarjeta `0002293528` queda con su tabla de materias según su nivel. Un coordinador solo ve los de su carrera |
| `feat(horarios): agrega el módulo de horarios` | Inicio mayor que fin: rechaza. Dos clases cruzadas en el mismo grupo, con el mismo docente o en la misma aula: rechaza. Un horario que choca con un estudiante ya matriculado: rechaza y nombra al estudiante |

**Debe poder explicar:** cómo se crea un estudiante con su usuario, cómo se genera la tabla de materias y qué se hace si un paso falla.

### Victoria

| PR sugerido | Prueba |
|---|---|
| `feat(grupos): agrega el módulo de grupos` | La misma asignatura con el mismo paralelo: rechaza. Eliminar un grupo con matriculados: rechaza. Cambiar de docente con choque de horarios: rechaza |
| `feat(notas): agrega el módulo de notas` | Como `docente1`: guardar notas válidas. Una nota de 11 o `abc`: no guarda ninguna y dice cuál. Pedir un grupo de otro docente cambiando el dato en el navegador: el servidor rechaza |
| `feat(reportes): agrega los reportes en PDF con FPDF` | POO, `2026-11-09`: dos filas (Gómez FALTA, Torres PRESENTE si se registró). `2026-11-10`: reporte vacío sin error. Un docente no puede abrir el reporte de un grupo ajeno escribiendo el número en la dirección |

**Debe poder explicar:** cómo se guardan todas las notas validando antes y cómo se arma un PDF con FPDF.

### Tatiana

| PR sugerido | Prueba |
|---|---|
| `feat(asignaturas): agrega el módulo de asignaturas` | Crear una asignatura nueva: los estudiantes existentes la ven como `AUN_NO_CURSA`. Eliminar una con matriculados: rechaza. Un coordinador no puede tocar otra carrera |
| `feat(consulta): agrega mis notas y asistencias` | Como `maria`: ve sus matrículas con asistencias y nota, y su récord académico. No hay forma de pedir los datos de otro estudiante |
| `feat(mihorario): agrega mi horario` | Como `docente1` y como `maria`: cada uno ve solo sus clases |
| `feat(inscritos): agrega la lista de inscritos` | Un docente solo ve sus grupos. El total coincide con la lista |

**Debe poder explicar:** cómo se identifica al estudiante que inició sesión y por qué no se pide la cédula al navegador.

### Brayan

| PR sugerido | Prueba |
|---|---|
| `feat(inicio): agrega la página de inicio con carrusel y video` | Se ve bien en pantalla grande y en el celular, sin iniciar sesión. El video se reproduce |
| `feat(nosotros): agrega la página nosotros` | El acordeón abre y cierra |
| `feat(contactanos): agrega el formulario con validación` | Campos vacíos y correo sin `@`: avisa |
| `feat(descargas): agrega los archivos para descargar` | El enlace descarga el PDF |
| `docs(guia): agrega la guía APE y las capturas` | La guía sigue el formato del ingeniero: resultados, conclusiones y recomendaciones |

**Debe poder explicar:** el jQuery de cada página y cómo se arma el menú con la plantilla.

## 4. Si algo falla en el hosting

| Síntoma | Qué mirar |
|---|---|
| Página en blanco | Poner `'depuracion' => true` en `config.local.php` **un momento** para ver el error, y volverlo a `false` |
| "No se pudo conectar a la base de datos" | Host, usuario, clave y nombre de la base en `config.local.php`: el host del hosting **no es `localhost`** |
| Error de `get_result` | El hosting no tiene `mysqlnd`: avisa a Ali, hay que cambiar esa función |
| Las horas salen distintas | La zona horaria está fijada en `models/config.php`; revisa que ese archivo esté subido |
| No cargan los estilos | Faltan `css/bootstrap.min.css` o `js/*.js`, o están subidos en otra carpeta |
| Las tildes se ven mal | El `schema.sql` se importó sin UTF-8: reimportar eligiendo `utf8mb4` |
| Entra, pero al recargar pide el login | La sesión no se guarda: avisa a Ali |

## 5. Entrega final

- [ ] El sitio abre en el hosting, sin errores de PHP
- [ ] Entran los 6 perfiles y cada uno ve solo su menú
- [ ] Al escribir en la barra la dirección de una página o de un archivo de `models/` ajeno, el sistema lo bloquea
- [ ] El registro funciona con el lector real y con la simulación (solo el ADMIN)
- [ ] Matrícula, notas y cierre de periodo, probados
- [ ] Los reportes en PDF se descargan y se abren
- [ ] Hay contenido multimedia y enlaces de descarga
- [ ] **Las claves de prueba están cambiadas** en la base del hosting
- [ ] README, guía APE y capturas al día
- [ ] El ingeniero está avisado, con la dirección y un usuario por perfil

### Mensaje para el ingeniero

```text
Buenas tardes, Ing. Le comparto el trabajo final de Computación Visual: sistema de control de
asistencia con lector RFID (versión web).
Dirección: https://...
Integrantes: Ali, Damaris, Evelyn, Victoria, Tatiana y Brayan.
Los usuarios para revisar cada perfil se los envío por este medio: (admin, coordinador, docente,
registro y estudiante).
```
