## Entrega: `develop` → `main`
Versión que se entrega al ingeniero (26 de noviembre).

## Qué incluye
- (módulos y pantallas terminados)

## Limitaciones conocidas
- (lo que no se hizo o se dejó simple a propósito, explicado en una línea)

## Pruebas hechas en una copia limpia
Clonar `develop` en una carpeta nueva de `htdocs`, importar `db/schema.sql` y comprobar:
- [ ] La página principal abre sin errores ni avisos de PHP
- [ ] Entran los 6 usuarios de prueba: `admin`, `coordsw`, `docente1`, `registro`, `maria` y `luis`
- [ ] Cada perfil ve solo su menú y, al escribir en la barra la dirección de una página ajena, el sistema lo bloquea
- [ ] Registro de asistencia con la hora simulada y con el lector real
- [ ] Matrícula, notas y cierre de periodo
- [ ] Los reportes en PDF se descargan y se abren
- [ ] Hay contenido multimedia y un enlace de descarga (la guía lo pide)
- [ ] No quedan `var_dump`, `print_r`, `error_reporting` de pruebas ni rutas `C:\`

## Documentos
- [ ] README al día (cómo instalar y probar)
- [ ] Guía APE con resultados, conclusiones y recomendaciones

## Revisor
@usuario-de-github (que no sea quien abre este PR)
