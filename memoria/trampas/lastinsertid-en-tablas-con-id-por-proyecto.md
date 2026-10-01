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
El arreglo del SOS está en la spec `docs/superpowers/specs/2026-09-30-s05-sos-semana-y-alerta-design.md`.

**Barrido del 2026-09-30** (`git grep lastInsertId` en `src/` y `admin/src`, sobre `a691d7f0`). De
18 llamadas (sin contar la definición, `Database.php:365`), siete caen en tablas del mapa y once en
tablas fuera de él. `admin/` usa la misma
clase `\Database`, así que la trampa también le aplica. Esquema confirmado ese día en local y en
producción (`dbhif4pdimjtxe`): las doce tablas del mapa sin `AUTO_INCREMENT`.

| Dónde | Tabla | Efecto hoy |
|---|---|---|
| `LpsLegacyCrisisRepository.php:82`, `LpsLegacyThreadRepository.php:121` | `lps_escalamientos`, `lps_drawer_comentarios` | Los cubre la spec del SOS |
| `LpsService.php:224` | `lps_drawer_comentarios` | Ninguno: `escalarAlertasActivas()` está dormido y descarta el retorno. **La spec no lo nombra** |
| `ProjectProfessionalsSyncService.php:100` y `:157` | `profesionales` | Latente: el id 0 queda en memoria y ningún `UPDATE` del mismo pase lo usa |
| `ProfesionalesApiController.php:290`, `SubcontratistasApiController.php:224` | `profesionales`, `subcontratistas` | Ninguno: el `?: lastInsertId()` no se alcanza, porque un `SELECT` por correo único encuentra la fila |

**El arreglo está en `main` desde el 2026-10-01 (PR #100, merge `2bebf864`):**
`Database::insertedId()` devuelve el id que asignó `rewriteInsert`, solo tras un INSERT con filas
afectadas, y cae a `lastInsertId()` cuando la capa no asignó nada. Un INSERT que lanza, uno por
`prepare()` o uno con id propio lo borran; un `SELECT` no. Lo usan las alertas, los comentarios y
`LpsService::addActivityComment`. Las demás llamadas siguen como deuda en `TASKS.md`.
