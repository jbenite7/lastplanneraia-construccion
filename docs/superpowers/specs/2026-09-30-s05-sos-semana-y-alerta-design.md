---
capa: fuente
tipo: spec
estado: abierto
id: S05-SOS
fecha: 2026-09-30
superficie: programa-general
rutas: ["/programa-general", "/api/lps/crisis/register", "/api/lps/comments", "/api/lps/comments/add"]
depende_de: [S05, T02]
version: 1.1
areas: [lps, qa, datos]
fuente: "prueba en navegador de Programa General del 2026-09-30 (memoria/arquitectura/programa-general-funciones-y-flujos.md, sección 7), decisión de Felipe del mismo día de abrir el arreglo ya, y paso 02 de verificación del 2026-09-30"
resumen: "Arreglo de los dos fallos críticos del SOS de Programa General: la crisis y los comentarios caen en otra semana, y un SOS registrado se muestra como fallo porque el id de la alerta vuelve como 0."
---

# S05 — Los dos fallos críticos del SOS

**Estado: propuesta, pendiente del visto de Felipe.** Felipe decidió el 2026-09-30 abrir el
arreglo ya; esta spec describe qué se arregla y cómo se comprueba, y no autoriza implementación
hasta su aprobación.

**Versión 1.1 (2026-09-30).** El paso 02 cerró los vacíos y corrigió la causa del segundo fallo:
la 1.0 lo atribuía a un `AUTO_INCREMENT` perdido en la base local y proponía restaurarlo. Es
falso: en las tablas globales el id se asigna por proyecto a propósito, y el arreglo 5 de la 1.0
se retira.

## Qué pasa hoy, en simple

1. **La crisis cae en otra semana.** Viendo la semana 2 de Da Porto, un SOS sobre una actividad
   quedó guardado en la semana 1. Nadie lo ve en la semana donde se declaró. Lo mismo les pasa a
   los comentarios del cajón.
2. **Un SOS que sí se registró se muestra como fallido.** La pantalla dice «Tu sesión venció… la
   crisis no se registró», así que quien la declara cree que no pasó y puede repetirla o
   abandonarla.

Los dos afectan cómo se escala una crisis en obra. Lo demás de la prueba (17 fallos) no entra
aquí: sigue en la ficha y en su sprint de verificación.

## Causa, verificada

### Fallo 1: la semana

- La petición no lleva semana. `LpsTargetRequest` solo tiene `activityId`, `module`, `alertId` y
  `escalamientoId` (`src/Services/Lps/LpsTargetRequest.php`), y `buildTargetRequest()` no lee
  ninguna semana del request (`src/Controllers/Api/LpsApiController.php:261`).
- El servidor la adivina con `resolveWeek()`, que hace `LIMIT 1` **sin `ORDER BY`** en los tres
  adaptadores: PG y PI sobre `programa_consolidado`
  (`LpsLegacyGeneralActivityAdapter.php:35`, `LpsLegacyIntermediateActivityAdapter.php`) y PS sobre
  `programacion_semanal` (`LpsLegacyWeeklyActivityAdapter.php`). La vía legada sin `modulo`
  (`LpsTargetResolver::resolveLegacyModule`) recorre esos mismos adaptadores.
- **Pesa en casi todas las actividades.** En la base local, el 2026-09-30: 4 586 de 5 264
  actividades de `programa_consolidado` (87 %) tienen más de una semana, hasta 28; en
  `programacion_semanal`, 1 198 de 1 692 (71 %), hasta 22.
- El objetivo resuelto lo usan cuatro rutas: leer comentarios, agregar comentario, registrar
  crisis y (por `alerta_id`, sin este defecto) cerrar crisis.

### Fallo 2: el `alertId: 0`

- `lps_escalamientos` es una tabla global con **id por proyecto**: clave primaria
  `(project_id, id)` y sin `AUTO_INCREMENT`, a propósito. `Database::rewriteInsert()` le asigna el
  id con `MAX(id) + 1` del proyecto (`src/Core/Database.php:721`, mapa `PROJECT_SCOPED_IDS`).
- Por eso `lastInsertId()` devuelve 0 en esa tabla: no hubo valor autoincremental que leer. La
  fila sí queda bien creada (en la prueba quedó con `id = 1`), pero `insertAlert()` devuelve el 0
  sin comprobarlo (`src/Services/Lps/LpsLegacyCrisisRepository.php:82`).
- **El mismo defecto lo tiene el alta de comentarios** del cajón, sobre `lps_drawer_comentarios`
  (`src/Services/Lps/LpsLegacyThreadRepository.php:121`).
- El cliente rechaza el 0 porque su esquema exige un entero positivo
  (`frontend/src/shared/lps/api/crisis.ts:19`), y `mensajeErrorSos()` traduce **toda** respuesta
  200 con forma inválida como sesión vencida
  (`frontend/src/modules/programa-general/domain/erroresSos.ts:23`). Lo hace porque el guardia
  legado responde 200 con `respuesta: ERROR` cuando la sesión expira, pero así un error de
  contrato se disfraza de sesión vencida.

## Qué se arregla

1. **La semana viaja desde la pantalla.** El cliente React y el cajón legado
   (`public/js/modules/lps_drawer.js`) mandan la semana que se está viendo en las rutas de
   comentarios y de registro de crisis. El servidor la lee, comprueba que la actividad exista en
   esa semana y, si no, responde «objetivo no encontrado», igual que hoy para una actividad ajena.
2. **Sin semana, nada de adivinar al azar.** Si una llamada no la manda, el servidor no elige una
   fila cualquiera: la regla determinista, o el rechazo, la fija el plan. Hoy todos los callers
   conocidos (cliente React y cajón legado) se actualizan en el mismo cambio.
3. **El id de una fila nueva es el que se asignó.** El alta de alertas y la de comentarios
   devuelven el id que la capa de datos asignó por proyecto, no `lastInsertId()`. Si el id no es
   positivo, el servidor revierte la transacción y responde con error: nunca devuelve
   `alertId: 0`.
4. **El error de contrato no se disfraza de sesión vencida.** La pantalla distingue la sesión
   expirada (respuesta `ERROR` del guardia) de una respuesta exitosa con forma inválida, y esta
   última la muestra como error del sistema.

Ninguno de los cuatro cambia el esquema ni los datos: el arreglo es de código.

## Hechos y vacíos

| Afirmación | Estado | Fuente |
|---|---|---|
| El SOS de la semana 2 quedó en la semana 1 | Hecho | Prueba en navegador del 2026-09-30, ficha §7 |
| La petición no lleva semana | Hecho | `LpsTargetRequest.php`, `LpsApiController.php:261` |
| `resolveWeek` hace `LIMIT 1` sin `ORDER BY` en PG, PI y PS | Hecho | Los tres adaptadores `LpsLegacy*ActivityAdapter.php` |
| 87 % (PG y PI) y 71 % (PS) de las actividades tienen varias semanas | Hecho, medido el 2026-09-30 en la base local | Consulta de solo lectura |
| Callers sin semana: cliente React (`queryDeTarget`) y cajón legado | Hecho | `esquemas.ts:134`, `lps_drawer.js:883`, `:1061`, `:1213` |
| `lps_escalamientos` y `lps_drawer_comentarios` tienen id por proyecto, sin `AUTO_INCREMENT` a propósito | Hecho | `Database.php` (`PROJECT_SCOPED_IDS`, `rewriteInsert`, `:721`) |
| `lastInsertId()` vale 0 en esas tablas; la fila se crea con id correcto | Hecho | Prueba del 2026-09-30 (fila con `id = 1`) |
| «Tu sesión venció» sale de mapear todo 200 con forma inválida | Hecho | `erroresSos.ts:23` |
| Si producción perdió el `AUTO_INCREMENT` | Ya no aplica: la tabla no lo tiene por diseño | `Database.php` |
| `insertProjectId` no reconoce el nombre entre comillas invertidas | Hecho, sin efecto aquí: `Database::query()` inyecta `project_id` por `ProjectSqlGuard` | `Database.php:221`, `:116` |
| Otros `lastInsertId()` sobre tablas con id por proyecto (fuera de este alcance) | Vacío, pendiente 2026-09-30 | Barrido aparte |
| Concurrencia de `MAX(id) + 1` entre dos SOS simultáneos | Fuera de alcance | Diseño de tablas globales |

## Condición de hecho propuesta

- Pruebas PHP nuevas que fallan antes del arreglo y pasan después: semana enviada, actividad
  inexistente en esa semana, alta de alerta y de comentario con id positivo, y alta sin id
  válido que revierte.
- Pruebas del cliente: la semana viaja en la petición, y una respuesta 200 con forma inválida no
  se muestra como sesión vencida.
- `docker compose exec app php scripts/run-php-tests.php --nivel=puro` y el `npm` del frontend
  con código 0.
- Prueba en navegador en la copia local de Da Porto: un SOS y un comentario hechos viendo la
  semana 2 quedan en la semana 2, y la pantalla confirma el registro. Escribe en la base local,
  así que necesita respaldo, `/visto-prod` y restauración al final, como en la prueba del
  2026-09-30.

## Decisiones abiertas

- **Ejecutor.** Por el reparto vigente, Codex ejecuta y revisa; Claude cierra.
- **Sin semana: regla o rechazo.** Técnica; la fija el plan tras confirmar que no quedan callers
  sin actualizar.
