---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-30
areas: [datos, lps]
fuente: src/Core/Database.php
resumen: en las tablas globales con id por proyecto, lastInsertId() devuelve 0 aunque la fila se cree bien; el id lo asigna Database::rewriteInsert con MAX(id)+1
---
Las tablas globales del mapa `PROJECT_SCOPED_IDS` de `src/Core/Database.php` (entre ellas
`lps_escalamientos`, `lps_drawer_comentarios`, `profesionales`, `subcontratistas`, `cambios` y
`semanas_activas`) **no tienen `AUTO_INCREMENT`, a propósito**: su clave primaria es
`(project_id, id)` y `Database::rewriteInsert()` les asigna el id con `MAX(id) + 1` del proyecto.

Por eso `$this->db->lastInsertId()` devuelve **0** después de insertar en ellas, aunque la fila
quede bien creada. No falla: devuelve un número que parece válido para el código y no lo es.

Costó una vuelta el 2026-09-30. Un SOS de Programa General se registraba y la pantalla decía que
no, porque el servidor devolvía `alertId: 0`. La primera hipótesis fue un `AUTO_INCREMENT` perdido
en la base local, porque la migración `001_create_global_tables.sql` sí lo declara. Era falsa: esa
migración es historia, y el código manda (ver [[datos]]). La fila de la prueba tenía `id = 1`.

**Cómo no caer:** antes de usar `lastInsertId()`, mirar si la tabla está en `PROJECT_SCOPED_IDS`.
Si está, el id hay que obtenerlo de lo que asignó la capa de datos, nunca de `lastInsertId()`.
El arreglo del SOS está en la spec `docs/superpowers/specs/2026-09-30-s05-sos-semana-y-alerta-design.md`;
los demás `lastInsertId()` sobre esas tablas quedan por barrer.
