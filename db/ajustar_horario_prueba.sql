-- Corrige el cruce de prueba entre POO y Sistemas Operativos del lunes.
-- Mueve Sistemas Operativos del grupo A a las 14:00-16:00.
UPDATE horario h
JOIN grupo g ON g.id = h.grupo_id
JOIN asignatura a ON a.id = g.asignatura_id
SET h.hora_inicio = '14:00:00',
    h.hora_fin = '16:00:00',
    h.aula = 'A-105'
WHERE BINARY a.nombre = BINARY 'Sistemas Operativos'
  AND BINARY g.paralelo = BINARY 'A'
  AND h.dia_semana = 1
  AND h.hora_inicio = '16:00:00'
  AND h.hora_fin = '18:00:00';
