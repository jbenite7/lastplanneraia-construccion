---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-06
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design.md
resumen: Hacer reales en el servidor cuatro reglas que hoy existen solo como declaración o solo en el navegador: CSRF en seis módulos que mutan datos, CSRF en la…
---

# Cierre de los hallazgos de seguridad de la biblia de flujos — diseño

- **Fecha:** 2026-08-06
- **Estado:** aprobado en sesión de consolidación de pendientes
- **Fuente de los hallazgos:** `docs/EXPERIMENTS.md` filas 24, 25, 26 y 49 (biblia de flujos, tandas T2/T4/T5)

## Objetivo

Hacer reales en el servidor cuatro reglas que hoy existen solo como declaración o solo en el
navegador: CSRF en seis módulos que mutan datos, CSRF en la operación `sanear`, la restricción de
editar el pasado del Programa General, y el ocultamiento del informe de `/indicadores`.

## Alcance

Cuatro hallazgos, en una sola rama de trabajo. Fuera de alcance: feedback visual en cliente para la
regla del pasado del PG (tarea aparte si se quiere), `PdcApiController` (fila 75 de EXPERIMENTS.md,
módulo v2 con su propio carril), y cualquier refactor adyacente.

## Decisiones tomadas en el grilleo

1. **Entrega del token CSRF:** meta tag por vista + helper JS compartido (elegido sobre
   token-por-respuesta-de-API y sobre middleware global).
2. **Semántica de «el pasado» del PG:** semanas anteriores a la máxima semana activa del proyecto
   (mismo criterio de `LpsWeekEditPolicy`). Definida aquí porque el grilleo demostró que
   `canEditPastGeneralProgram` está declarada pero **nadie la consume, ni siquiera el navegador**.
3. **`/indicadores` entra al alcance** como cuarto hallazgo (mismo patrón de regla solo en cliente).

## Diseño

### 1 · CSRF en los seis módulos

Módulos y controladores: `SubcontratistasApiController` (4 escrituras),
`ProfesionalesApiController` (5), `ControlCambiosApiController` (3), `CicApiController` (10),
`CncApiController` (1), `CnpApiController` (2).

- **Servidor:** cada método de escritura añade `legacy_require_csrf('<modulo>')`
  (`src/Legacy/rbac_guard.php:83-89`, responde 403 JSON) después del guard RBAC existente. Un
  `formKey` por módulo: `subcontratistas`, `profesionales`, `control-cambios`, `cic`, `cnc`, `cnp`.
- **Cliente:** cada vista emite
  `<meta name="csrf-token" content="<?= \App\Security\CsrfTokenManager::generate('<modulo>') ?>">`.
  Un helper nuevo `public/js/modules/aia_ui/csrf.js` (~10 líneas) lee la meta y expone
  `aiaCsrfToken()`; cada llamada de escritura de esos módulos adjunta `_csrf_token`. Los GET no
  cambian.
- **Censo previo obligatorio:** antes de tocar cada módulo, inventariar todas sus llamadas de
  escritura (JS y formularios) para no dejar ninguna sin token.

### 2 · `sanear` en Programación Semanal

`SemanalApiController::save()` tiene dos listas: la que exige token (`:128-129`) y
`$mutatingOptions` (`:145-148`). `sanear` está en la segunda pero no en la primera, y ejecuta
`DELETE` + `INSERT`. Fix: añadir `'sanear'` a la lista que exige token. Premisa a verificar antes
de darla por buena: el frontend de PS ya envía `_csrf_token` en esa llamada (patrón
`weekCsrfToken` / `window.__lpsWeekCsrf`); si no la envía en `sanear`, ajustar esa llamada.

### 3 · Pasado del Programa General

En `GeneralApiController::update()` (`:136`), `updateBatch()` (`:379`) y `deleteUpdate()`
(`:1172`): si la fila afectada pertenece a una semana `< MAX(Semana)` de `semanas_activas` del
proyecto y el rol (normalizado con `RoleManager::cleanCargo()`) no tiene
`canEditPastGeneralProgram` (A, D según `RbacManager:37`), responder 403 con mensaje claro. La
consulta de semana máxima reutiliza el patrón de `LpsWeekEditPolicy` (`TableResolver` +
`queryWithProject`). El cliente no cambia.

### 4 · `/indicadores`

`IndicadoresController` replica el 403 de servidor de `BiViewController:179` para los roles
restringidos (`G`, `S`, `SG`, `C`). Además, `views/indicadores/indicadores.view.php:111` deja de
emitir `POWER_BI_REPORT_URL` en el HTML cuando el rol no puede ver el informe (hoy la URL viaja a
todos los roles aunque `:151` la oculte visualmente).

## Tests

Estilo del repo: `tests/test_*.php` autoejecutables, sin runner. AGENTS.md exige al menos un rol
permitido y uno denegado por cambio de RBAC/rutas.

- **CSRF módulos:** un test paramétrico que recorra los seis módulos: mutación sin token → 403;
  con token válido → pasa el guard CSRF.
- **`sanear`:** sin token → 403.
- **PG pasado:** rol R sobre semana pasada → 403; rol A sobre semana pasada → pasa; rol R sobre
  semana vigente → pasa.
- **`/indicadores`:** rol V → ve; rol G → 403; el HTML servido a un rol restringido no contiene
  `POWER_BI_REPORT_URL`.
- **Navegador (dev door, Docker):** smoke de guardado real en los seis módulos con `test.A`/
  `test.R`; consola limpia.

## Riesgos

- **Romper un guardado de UI** por una llamada de escritura no inventariada → mitigado por el
  censo previo por módulo y el smoke final en navegador.
- **`sanear` sin token en cliente** → la premisa se verifica antes de activar la validación.
- **Falsos 403 en PG** si la semana máxima se calcula distinto que en el cliente → el criterio es
  de servidor (semanas_activas), documentado aquí como fuente de verdad.

## Condición de hecho

Los cuatro hallazgos pasan de «abierto» a cerrados con: tests nuevos en verde, smoke de navegador
por módulo sin regresiones, y las filas 24-26 y 49 de `docs/EXPERIMENTS.md` actualizadas con el
commit que las cierra.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem plan hermano

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
