# Control de asistencia con lector RFID (versión web)

Sistema web para registrar la asistencia de estudiantes por clase con un lector RFID USB (125 kHz), que funciona como teclado. Proyecto de **Computación Visual**, Carrera de Software, FISEI, Universidad Técnica de Ambato. Es la versión web del sistema de escritorio hecho en Java.

Desarrollado en **PHP** (mysqli), **MySQL**, **HTML5**, **CSS** (Bootstrap), **JavaScript** y **jQuery**, con la estructura MVC de clase. Reportes en PDF con **FPDF**. Se entrega **subido a un hosting**.

## Requisitos

- XAMPP (Apache y MySQL) para trabajar en local
- Visual Studio Code
- Git
- Una cuenta en un hosting gratuito con PHP y MySQL (para la entrega)

El código funciona con PHP 7.4 o superior.

## Cómo probar el proyecto en local

1. Abrir XAMPP e iniciar **Apache** y **MySQL**.
2. Clonar el repositorio **dentro de `htdocs`**, con esta carpeta:
   ```
   cd /c/xampp/htdocs
   git clone https://github.com/bethrmz13/Control-de-asistencia-web.git asistenciaweb
   ```
3. En `http://localhost/phpmyadmin`: crear una base de datos llamada **`asistencia_web`** (cotejamiento `utf8mb4_general_ci`), entrar en ella y usar **Importar** con `db/schema.sql`. Deben quedar **10 tablas**. Si ya existía una versión anterior, borrar antes las tablas.
4. Abrir `http://localhost/asistenciaweb/` en el navegador.

No hace falta configurar nada más: sin `config.local.php`, el proyecto usa la base de XAMPP (`root`, sin clave).

### Usuarios de prueba

| Usuario | Clave | Perfil |
|---|---|---|
| `admin` | `admin123` | ADMIN |
| `coordsw` | `coord123` | COORDINADOR (Software) |
| `docente1` | `docente123` | DOCENTE |
| `registro` | `registro123` | REGISTRO |
| `maria` | `est123` | ESTUDIANTE (cédula `1800000000`) |
| `luis` | `est123` | ESTUDIANTE (cédula `1800000018`) |

Tarjetas de prueba (de mentira): María `0001234567`, Luis `0007654321`. **Estas claves son solo para trabajar en local**: en el hosting se cambian (ver *Subir al hosting*).

### Formato de los códigos de las tarjetas

El lector escribe **10 dígitos decimales con ceros a la izquierda** (por ejemplo `0001736800`) y pulsa Enter solo. Por eso:

- El código y la cédula se guardan y se usan siempre como **texto**, nunca como número: se perderían los ceros del principio.
- La pantalla de registro mantiene el foco en la caja de texto: el lector escribe y el Enter envía el formulario.

## Estructura del proyecto

```
index.php            Punto de entrada: index.php?accion=nombre_de_la_pagina
config.sample.php    Ejemplo de configuración (la real, config.local.php, NO se sube a GitHub)
controllers/         controller.php: arma la plantilla y elige la página
models/              Lista de páginas por perfil, conexión, sesión y un archivo por acción:
  config.php           hora de Ecuador y errores
  conexion.php         conexión mysqli
  sesion.php           sesión y guardia de perfil: exigirPerfil(...)
  funciones.php        funciones comunes (cédula, periodo activo, hora del servidor)
  model.php            lista blanca de páginas y quién puede abrirlas
  login.php, logout.php
  <módulo>/            select.php, guardar.php, update.php, delete.php, buscar.php
views/               template.php (banner + menú + sección de trabajo) y una vista por página
css/, js/            Estilos y JavaScript (Bootstrap y jQuery guardados aquí, no por internet)
images/, descargas/  Multimedia y archivos para descargar
reportes/            Reportes en PDF con FPDF
fpdf19/              Librería FPDF
db/                  schema.sql
```

### Cómo se conectan las piezas

1. `index.php` carga el controlador, que muestra la plantilla (banner, menú y sección de trabajo).
2. El controlador busca la página pedida en la **lista blanca** de `models/model.php`. Si el perfil no puede verla, muestra el login o "sin permiso".
3. Cada vista usa jQuery (`$.ajax`, `$.post`) para llamar a los archivos de `models/<módulo>/`, que devuelven **JSON** (`{"success": ...}` o `{"error": ...}`).
4. **Todo archivo de `models/` empieza con el guardia** `exigirPerfil(...)`. Ocultar una opción del menú no basta: cualquiera puede escribir la dirección de un archivo.

## Reglas de negocio

**Asistencia.** La tarjeta registra asistencia si el estudiante está matriculado en una clase de hoy, la fecha está dentro del periodo activo y pasa la tarjeta **durante la clase**: desde la hora de inicio hasta antes de la hora de fin. Antes del inicio o después del fin no se registra. Resultados: `REGISTRADO`, `YA_REGISTRADO`, `NO_ENCONTRADO`, `SIN_CLASES_HOY`, `FUERA_DE_HORARIO` y `ERROR_GUARDADO`. La hora es **siempre la del servidor**. La simulación de fecha y hora solo está disponible para el ADMIN, nunca para REGISTRO.

**Matrícula.** La abre y la cierra el administrador. El estudiante se matricula en **máximo 5 materias** por periodo, eligiendo materia y paralelo. Solo ve materias de su carrera, de su nivel o inferiores, con estado `AUN_NO_CURSA` o `REPROBADA`, sin choque de horarios. Solo puede anular con la matrícula abierta y la materia sin nota.

**Horarios.** Hora de inicio menor que la de fin, sin cruces dentro del mismo grupo, sin el mismo docente en dos clases a la vez, sin la misma aula en dos clases a la vez y sin chocar con estudiantes ya matriculados.

**Notas.** Las registra el docente de cada grupo (el administrador, todas). Escala de 0 a 10. Se validan todas antes de guardar.

**Cierre de periodo.** Revisa que no falten notas. Nota de **7 o más** pasa a `APROBADA`, menos a `REPROBADA`. Sube de nivel quien aprobó todo lo que matriculó y no tiene otra materia reprobada pendiente; los demás quedan "por revisar" y el administrador define su nivel. El periodo queda inactivo y con la matrícula cerrada.

**Cédula.** 10 dígitos con el dígito verificador correcto (`validarCedula` en `models/funciones.php`). Es la clave del estudiante.

**Sin transacciones.** Si un paso de un guardado en varias tablas falla, se borra lo ya guardado.

## Perfiles

| Perfil | Páginas |
|---|---|
| ADMIN | Todas las del sistema |
| COORDINADOR | Estudiantes, Asignaturas, Grupos, Horarios, Reportes e Inscritos, **solo de su carrera** |
| DOCENTE | Notas (de sus grupos), Reportes, Inscritos y Mi horario |
| REGISTRO | Solo Registro de asistencia, sin menú (puesto de lectura) |
| ESTUDIANTE | Matrícula, Mis notas y asistencias y Mi horario |

El filtro por carrera (coordinador) y por docente se aplica **en el servidor**, en el archivo de `models/`, no en el navegador.

## Reglas del equipo

1. **Cada quien trabaja solo en sus archivos:** su vista (`views/<módulo>.php`), su carpeta `models/<módulo>/` y, si hace falta, su JavaScript. **Ningún módulo llama código de otro módulo**: solo usa lo común (`conexion.php`, `sesion.php`, `funciones.php`, `comun.js`). Así nadie espera a nadie.
2. **No se editan** `views/template.php`, `models/model.php`, `controllers/controller.php`, `models/config.php`, `models/conexion.php`, `models/sesion.php` ni `models/funciones.php` sin avisar a Ali. Todas las páginas ya están registradas en `model.php`.
3. **Consultas con `?`** (`prepare` y `bind_param`), nunca variables pegadas dentro del SQL.
4. **Guardia en cada archivo de `models/`**: `$usuario = exigirPerfil(array('ADMIN'));`.
5. **Rutas relativas** (nada de `C:\...`) y Bootstrap y jQuery guardados en el proyecto.
6. **Nunca se sube `config.local.php`** ni claves de ningún tipo a GitHub.
7. Cédula y código de tarjeta: siempre **texto**.
8. Reportes con **FPDF** (carpeta `fpdf19/`).
9. Commits con el formato `tipo(donde): descripción`.

### Cómo se escribe un módulo

Un archivo por acción, como en clase, con el guardia al inicio y la consulta con `?`:

```php
<?php
// models/asignaturas/select.php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../sesion.php';
header('Content-Type: application/json; charset=utf-8');

$usuario = exigirPerfil(array('ADMIN', 'COORDINADOR'));   // 1. quién puede llamar a este archivo

$conn = conectar();
$sql = "SELECT id, nombre, nivel FROM asignatura WHERE carrera_id = ? ORDER BY nivel, nombre";
$stmt = $conn->prepare($sql);                             // 2. consulta con ?
$stmt->bind_param("i", $carrera);
$carrera = (int) $usuario['carrera_id'];                  // 3. el filtro por carrera se hace aquí
$stmt->execute();
$res = $stmt->get_result();

$lista = array();
while ($fila = $res->fetch_assoc()) {
    $lista[] = $fila;
}
echo json_encode($lista);
$conn->close();
```

La vista llama con jQuery: `$.getJSON('models/asignaturas/select.php', function (datos) { ... });`.

## Flujo de trabajo en Git

- `main`: versión estable. No se trabaja aquí.
- `develop`: integración. **Todos los Pull Request apuntan a `develop`.**
- Cada tarea en su propia rama: `feature/nombre-de-la-tarea`.

```
git checkout develop
git pull
git checkout -b feature/nombre-de-la-tarea
```

Antes de seguir trabajando, trae lo nuevo de `develop`:

```
git checkout develop
git pull
git checkout feature/nombre-de-la-tarea
git merge develop
```

La rama que tienes activa es la que sirve Apache: si cambias de rama, cambia lo que ves en el navegador.

### Formato de commits

`tipo(donde): descripción`

| Tipo | Cuándo se usa |
|---|---|
| `feat` | Una funcionalidad nueva |
| `fix` | Corrección de un error |
| `refactor` | Cambio de código que no cambia lo que hace |
| `docs` | README, comentarios o documentación |
| `chore` | Configuración, archivos del proyecto |

Ejemplos: `feat(login): agrega login con sesión`, `fix(matricula): corrige el límite de 5 materias`, `docs(readme): agrega la guía de despliegue`. Un commit por cada paso que funciona, no uno solo por Pull Request.

## Pull Request y revisión

1. Sube tu rama: `git push -u origin feature/nombre-de-la-tarea`.
2. En GitHub: **Compare & pull request**. Verifica que diga **base: `develop`** ← **compare: tu rama**.
3. Llena la plantilla y elige a tu **revisor principal** en *Reviewers*. Avisa en el grupo.

| Autor | Revisor principal | Respaldo |
|---|---|---|
| Ali | Victoria | Damaris |
| Damaris | Ali | Victoria |
| Evelyn | Damaris | Ali |
| Victoria | Evelyn | Damaris |
| Tatiana | Brayan | Evelyn |
| Brayan | Tatiana | Victoria |

- Nadie aprueba ni mezcla su propio PR.
- El revisor principal tiene unas 3 horas para revisar. Si no puede, avisa en el grupo y revisa el respaldo.
- Quien aprueba y comprueba que funciona hace el **Merge** (solo método *Merge*).
- Un PR debe ser pequeño: una sola funcionalidad.

### Cómo revisar un PR

Revisar es bajar la rama y probarla **sin tocar tu propio trabajo**. Para eso se usa una **segunda copia** del repositorio, que nunca se edita:

```
cd /c/xampp/htdocs
git clone https://github.com/bethrmz13/Control-de-asistencia-web.git asistenciaweb_revision
```

Se abre en `http://localhost/asistenciaweb_revision/` y se pueden tener las dos copias abiertas a la vez (comparten la base de datos).

1. Lee el PR en GitHub: descripción y pestaña **Files changed**.
2. En `asistenciaweb_revision`: `git fetch origin`, `git checkout feature/xxx`, `git pull`.
3. Prueba lo que dice "Cómo probarlo", con el perfil que corresponde **y con uno que no debería tener acceso**: al escribir la dirección de un archivo de `models/` sin sesión, debe responder "No tiene permiso".
4. Revisa con la lista de la plantilla del PR.
5. Comenta en **Review changes**: **Approve** o **Request changes**. Para aprobar, copia este texto:

```markdown
✅ Aprobado
- Probado en: `asistenciaweb_revision`, rama `feature/...`
- Perfil(es) probados:
- Casos probados:
- Intenté entrar con un perfil ajeno: bloqueado / no aplica
- Observaciones (opcionales):
```

6. Si aprobaste, **Merge pull request**. Después: `git checkout develop` y `git pull` en tu copia de revisión.

Si hay algo por arreglar, **no lo corrijas en la copia de revisión**: coméntalo en el PR.

## Reparto de tareas

Los módulos son independientes entre sí: cada quien puede hacer y probar el suyo con los datos de `schema.sql`, sin esperar a nadie.

| Integrante | Módulos |
|---|---|
| **Ali** | Base del proyecto, login y sesión, menú por perfil, hosting y despliegue, **Usuarios**, **Periodos** (activar y abrir o cerrar la matrícula), integración |
| **Damaris** | **Registro de asistencia** (lector y regla de asistencia), **Matrícula** del estudiante, **Cierre de periodo** (página propia, solo ADMIN) |
| **Evelyn** | **Estudiantes** (cédula, tarjeta, tabla de materias), **Horarios** (con sus validaciones) |
| **Victoria** | **Grupos**, **Notas**, **Reportes** (FPDF) |
| **Tatiana** | **Asignaturas**, **Mis notas y asistencias**, **Mi horario**, **Inscritos** (consultas de solo lectura y un CRUD sencillo) |
| **Brayan** | Páginas públicas: **Inicio, Nosotros, Contáctanos y Descargas**, con el jQuery de cada una, las imágenes y el video, el archivo para descargar, la **guía APE** y las capturas |


## Subir al hosting

El ingeniero revisa el trabajo **hospedado** (gratuito o pagado). Lo hace Ali, y conviene hacer un primer despliegue con la base vacía desde el primer día, para descubrir los problemas pronto.

1. Crear la cuenta y un sitio (subdominio gratuito) en el hosting. Anotar la dirección.
2. Desde el panel, **crear la base de datos** y anotar su nombre, usuario, clave y **host** (no es `localhost`).
3. En el phpMyAdmin del hosting: elegir esa base y **Importar** `db/schema.sql`.
4. Copiar `config.sample.php` como `config.local.php` **dentro del hosting** y poner los datos de la base, con `'depuracion' => false`. Este archivo **nunca** se sube a GitHub.
5. Subir los archivos por FTP o con el administrador de archivos del hosting, a la carpeta pública (en InfinityFree es `htdocs`): `index.php`, `controllers/`, `models/`, `views/`, `css/`, `js/`, `images/`, `descargas/`, `reportes/` y `fpdf19/`. **No subir** `.git`, `.github`, `docs` ni `db`.
6. Probar: login con cada perfil, el registro con el lector y los reportes.
7. **Cambiar las claves de prueba** en la base del hosting (las de este README son públicas) y dárselas al ingeniero por un canal privado.
8. Avisar al ingeniero: la dirección del sitio y un usuario por perfil.

## Qué pide la guía y cómo se cubre

| La guía pide | Dónde está |
|---|---|
| Plantilla con menú, banner y parte de trabajo | `views/template.php` |
| Mínimo 4 enlaces | Inicio, Nosotros, Contáctanos, Descargas y el Sistema |
| Un jQuery en cada enlace | Cada página trae su script; los módulos usan `$.ajax` |
| Contenido multimedia | Imágenes y un video del lector funcionando |
| Enlace para descargar archivos | `Descargas` y los reportes en PDF |
| Subir el sitio a un hosting | Sección *Subir al hosting* |
| Login y perfiles | `login.php` y la lista blanca de `model.php` |
| Reportes | Reportes en PDF con FPDF |

## Estado del proyecto

- [x] Estructura MVC, plantilla y lista de páginas por perfil
- [x] Base de datos con la cédula como clave
- [x] Login con sesión y guardia de perfil
- [ ] Hosting creado y primer despliegue
- [ ] Módulos del sistema
- [ ] Páginas públicas con jQuery y multimedia
- [ ] Reportes en PDF
- [ ] Pruebas con el lector RFID real, en el hosting
- [ ] Guía APE
