## ¿Qué hice?
(Descripción breve de lo que se agregó o corrigió)

## Archivos que toqué
-

## Revisor principal
@usuario-de-github (según la tabla de revisión del README)

## Cómo probarlo
1. Iniciar **Apache** y **MySQL** en XAMPP.
2. Si cambió `db/schema.sql`, borrar la BD `asistencia_web` e importarlo de nuevo.
3. Con esta rama activa (`git checkout feature/...`), abrir `http://localhost/asistenciaweb/`.
4. Pasos concretos para ver mi cambio (ej.: entrar con `admin` / `admin123`, abrir Estudiantes y crear uno):
5. Resultado esperado:

## Depende de
(Qué parte de otra persona necesita para probarse. Si no depende de nadie, escribir: ninguna)

## Checklist
- [ ] La página abre sin errores ni avisos de PHP (`Warning`, `Notice`, `Fatal error`)
- [ ] Solo modifiqué mis archivos (o avisé al grupo si toqué otros)
- [ ] No cambié la plantilla ni la conexión (`views/template.php`, `models/conexion.php`) sin avisar a Ali
- [ ] Cada archivo de `models/` empieza con la comprobación de sesión y de perfil
- [ ] Las consultas usan `?` (`prepare` y `bind_param`), sin variables pegadas dentro del SQL
- [ ] La cédula y el código de la tarjeta se tratan como **texto**
- [ ] Rutas relativas (nada de `C:\...`) y Bootstrap y jQuery desde el proyecto, no desde internet
- [ ] Probé con el perfil que corresponde y también con uno que **no** debe tener acceso
- [ ] Si cambié la BD, actualicé `db/schema.sql` y avisé para reimportar
- [ ] No subí `.vscode/`, logs ni copias de archivos
- [ ] Mis commits siguen el formato `tipo(donde): descripción`
- [ ] Puedo explicar mi código
