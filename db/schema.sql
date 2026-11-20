-- =====================================================================
-- Control de asistencia con lector RFID - VERSION WEB (PHP + MySQL)
--
-- Diferencia con la version de escritorio: el estudiante se identifica por su
-- CEDULA (texto de 10 digitos), no por un numero incremental.
--
-- Como importarlo (XAMPP o hosting):
--   1. Crear la base de datos desde phpMyAdmin (XAMPP: se llama asistencia_web)
--      o desde el panel del hosting (el panel le pone su propio nombre).
--   2. Entrar a esa base y pulsar Importar > elegir este archivo > Continuar.
--      Este archivo NO crea la base: se importa dentro de la que se eligio.
--   3. Debe quedar con 10 tablas.
--   Si ya existia una version anterior, borrar las tablas antes de importar.
--
-- Guardar este archivo en UTF-8 para que las tildes se vean bien.
-- =====================================================================

-- ---------------------------------------------------------------------
-- TABLAS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS carrera (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS periodo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 0,
  matricula_abierta TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  clave VARCHAR(50) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  perfil ENUM('ADMIN','COORDINADOR','DOCENTE','REGISTRO','ESTUDIANTE') NOT NULL,
  carrera_id INT NULL COMMENT 'solo para COORDINADOR',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (carrera_id) REFERENCES carrera(id) ON DELETE SET NULL
);

-- La cedula es la clave del estudiante (texto, para no perder ceros ni mezclar con numeros)
CREATE TABLE IF NOT EXISTS estudiante (
  cedula VARCHAR(10) NOT NULL PRIMARY KEY,
  usuario_id INT NULL UNIQUE,
  codigo_rfid VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(60) NOT NULL,
  apellido VARCHAR(60) NOT NULL,
  correo VARCHAR(80),
  carrera_id INT NOT NULL,
  nivel TINYINT NOT NULL DEFAULT 1 COMMENT 'nivel maximo del que puede escoger materias',
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL,
  FOREIGN KEY (carrera_id) REFERENCES carrera(id)
);

CREATE TABLE IF NOT EXISTS asignatura (
  id INT AUTO_INCREMENT PRIMARY KEY,
  carrera_id INT NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  nivel TINYINT NOT NULL,
  FOREIGN KEY (carrera_id) REFERENCES carrera(id) ON DELETE CASCADE,
  UNIQUE KEY uq_asignatura (carrera_id, nombre)
);

-- Tabla de materias de cada estudiante (aprobada, reprobada o aun no cursa)
CREATE TABLE IF NOT EXISTS registro_academico (
  estudiante_cedula VARCHAR(10) NOT NULL,
  asignatura_id INT NOT NULL,
  estado ENUM('AUN_NO_CURSA','APROBADA','REPROBADA') NOT NULL DEFAULT 'AUN_NO_CURSA',
  PRIMARY KEY (estudiante_cedula, asignatura_id),
  FOREIGN KEY (estudiante_cedula) REFERENCES estudiante(cedula) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (asignatura_id) REFERENCES asignatura(id) ON DELETE CASCADE
);

-- Un grupo es una asignatura con su paralelo, en un periodo
CREATE TABLE IF NOT EXISTS grupo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodo_id INT NOT NULL,
  asignatura_id INT NOT NULL,
  paralelo VARCHAR(5) NOT NULL,
  docente_id INT NULL,
  FOREIGN KEY (periodo_id) REFERENCES periodo(id) ON DELETE CASCADE,
  FOREIGN KEY (asignatura_id) REFERENCES asignatura(id) ON DELETE CASCADE,
  FOREIGN KEY (docente_id) REFERENCES usuario(id) ON DELETE SET NULL,
  UNIQUE KEY uq_grupo (periodo_id, asignatura_id, paralelo)
);

-- Un horario es una clase semanal de un grupo (ej. lunes 16:00 a 18:00)
CREATE TABLE IF NOT EXISTS horario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  dia_semana TINYINT NOT NULL COMMENT '1=lunes ... 7=domingo',
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  aula VARCHAR(20),
  FOREIGN KEY (grupo_id) REFERENCES grupo(id) ON DELETE CASCADE,
  UNIQUE KEY uq_horario (grupo_id, dia_semana, hora_inicio)
);

CREATE TABLE IF NOT EXISTS matricula (
  estudiante_cedula VARCHAR(10) NOT NULL,
  grupo_id INT NOT NULL,
  nota DECIMAL(4,2) NULL COMMENT 'de 0 a 10, vacia hasta que el docente la registre',
  PRIMARY KEY (estudiante_cedula, grupo_id),
  FOREIGN KEY (estudiante_cedula) REFERENCES estudiante(cedula) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (grupo_id) REFERENCES grupo(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS asistencia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_cedula VARCHAR(10) NOT NULL,
  horario_id INT NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  FOREIGN KEY (estudiante_cedula) REFERENCES estudiante(cedula) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (horario_id) REFERENCES horario(id) ON DELETE CASCADE,
  UNIQUE KEY uq_asistencia (estudiante_cedula, horario_id, fecha)
);

-- ---------------------------------------------------------------------
-- DATOS DE PRUEBA
-- (se pueden importar varias veces sin duplicar)
-- ---------------------------------------------------------------------

INSERT IGNORE INTO carrera (nombre) VALUES ('Software'), ('Telecomunicaciones');

-- Periodo activo con matriculas abiertas
INSERT IGNORE INTO periodo (nombre, fecha_inicio, fecha_fin, activo, matricula_abierta)
VALUES ('Periodo de prueba', '2026-09-28', '2027-02-26', 1, 1);

-- Usuarios (la clave se guarda tal cual). Ver la tabla de claves en el README
INSERT IGNORE INTO usuario (username, clave, nombre, perfil, carrera_id) VALUES
('admin',    'admin123',    'Administrador',        'ADMIN',       NULL),
('coordsw',  'coord123',    'Coordinador Software', 'COORDINADOR', (SELECT id FROM carrera WHERE nombre='Software')),
('docente1', 'docente123',  'Docente Prueba',       'DOCENTE',     NULL),
('registro', 'registro123', 'Puesto de lectura',    'REGISTRO',    NULL),
('maria',    'est123',      'María Torres',         'ESTUDIANTE',  NULL),
('luis',     'est123',      'Luis Gómez',           'ESTUDIANTE',  NULL);

-- Asignaturas de Software. Las tres de nivel 2 al final (Estadistica, Redes y
-- Sistemas Operativos) existen para probar el limite de 5 materias y el choque de horarios.
INSERT IGNORE INTO asignatura (carrera_id, nombre, nivel)
SELECT c.id, x.nombre, x.nivel
FROM carrera c
CROSS JOIN (SELECT 'Cálculo' AS nombre, 1 AS nivel
            UNION SELECT 'Programación Orientada a Objetos', 2
            UNION SELECT 'Base de Datos', 2
            UNION SELECT 'Estadística', 2
            UNION SELECT 'Redes de Computadores', 2
            UNION SELECT 'Sistemas Operativos', 2
            UNION SELECT 'Ingeniería de Software', 3) x
WHERE c.nombre = 'Software';

-- Una asignatura de otra carrera, para probar que el coordinador solo ve la suya
INSERT IGNORE INTO asignatura (carrera_id, nombre, nivel)
SELECT id, 'Circuitos Eléctricos', 1 FROM carrera WHERE nombre = 'Telecomunicaciones';

-- Un grupo (paralelo A) por cada asignatura de Software, con el docente de prueba
INSERT IGNORE INTO grupo (periodo_id, asignatura_id, paralelo, docente_id)
SELECT p.id, a.id, 'A', (SELECT id FROM usuario WHERE username = 'docente1')
FROM periodo p
JOIN asignatura a ON a.carrera_id = (SELECT id FROM carrera WHERE nombre = 'Software')
WHERE p.activo = 1;

-- Horarios (1 = lunes ... 7 = domingo)
-- Sistemas Operativos choca a proposito con POO (lunes 16:00 a 18:00)
INSERT IGNORE INTO horario (grupo_id, dia_semana, hora_inicio, hora_fin, aula) VALUES
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Cálculo' AND g.paralelo = 'A'),
  1, '08:00:00', '10:00:00', 'A-101'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Programación Orientada a Objetos' AND g.paralelo = 'A'),
  1, '16:00:00', '18:00:00', 'A-102'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Base de Datos' AND g.paralelo = 'A'),
  1, '18:00:00', '20:00:00', 'A-102'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Sistemas Operativos' AND g.paralelo = 'A'),
  1, '16:00:00', '18:00:00', 'A-105'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Ingeniería de Software' AND g.paralelo = 'A'),
  2, '10:00:00', '12:00:00', 'A-103'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Estadística' AND g.paralelo = 'A'),
  3, '08:00:00', '10:00:00', 'A-104'),
((SELECT g.id FROM grupo g JOIN asignatura a ON a.id = g.asignatura_id WHERE a.nombre = 'Redes de Computadores' AND g.paralelo = 'A'),
  4, '08:00:00', '10:00:00', 'A-106');

-- Estudiantes de nivel 2. Las cedulas son ficticias (pasan la validacion del digito verificador).
-- OJO: los codigos son de mentira. Cambiarlos por los de las tarjetas reales.
INSERT IGNORE INTO estudiante (cedula, usuario_id, codigo_rfid, nombre, apellido, correo, carrera_id, nivel) VALUES
('1800000000', (SELECT id FROM usuario WHERE username = 'maria'), '0001234567', 'María', 'Torres', 'maria@correo.com',
  (SELECT id FROM carrera WHERE nombre = 'Software'), 2),
('1800000018', (SELECT id FROM usuario WHERE username = 'luis'),  '0007654321', 'Luis',  'Gómez',  'luis@correo.com',
  (SELECT id FROM carrera WHERE nombre = 'Software'), 2);

-- Tabla de materias de cada estudiante: niveles menores al suyo = APROBADA, el resto
-- AUN_NO_CURSA. Excepcion: Maria tiene Calculo REPROBADA (la esta repitiendo).
INSERT IGNORE INTO registro_academico (estudiante_cedula, asignatura_id, estado)
SELECT e.cedula, a.id,
  CASE WHEN a.nivel < e.nivel
       THEN (CASE WHEN e.nombre = 'María' AND a.nombre = 'Cálculo' THEN 'REPROBADA' ELSE 'APROBADA' END)
       ELSE 'AUN_NO_CURSA'
  END
FROM estudiante e
JOIN asignatura a ON a.carrera_id = e.carrera_id;

-- Matriculas: Maria repite Calculo y toma POO y Base de Datos. Luis solo POO y Base de Datos.
INSERT IGNORE INTO matricula (estudiante_cedula, grupo_id)
SELECT e.cedula, g.id
FROM estudiante e
CROSS JOIN grupo g
JOIN asignatura a ON a.id = g.asignatura_id
WHERE e.nombre = 'María'
  AND a.nombre IN ('Cálculo', 'Programación Orientada a Objetos', 'Base de Datos');

INSERT IGNORE INTO matricula (estudiante_cedula, grupo_id)
SELECT e.cedula, g.id
FROM estudiante e
CROSS JOIN grupo g
JOIN asignatura a ON a.id = g.asignatura_id
WHERE e.nombre = 'Luis'
  AND a.nombre IN ('Programación Orientada a Objetos', 'Base de Datos');
