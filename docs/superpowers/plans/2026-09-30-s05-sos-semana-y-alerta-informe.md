---
capa: fuente
tipo: plan
areas: [proceso]
fuente: docs/superpowers/plans/2026-09-30-s05-sos-semana-y-alerta-informe.md
resumen: Informe de sprint del arreglo de los dos fallos críticos del SOS (S05-SOS 1.2)
fecha: 2026-10-01
sprint: S05-SOS
ejecutor: claude
rama: fix/s05-sos-semana-y-alerta
estado: cerrado
---

# Informe de sprint S05-SOS — la semana y el id de la alerta

## Partida

- Spec `docs/superpowers/specs/2026-09-30-s05-sos-semana-y-alerta-design.md` (1.2, aprobada el
  2026-09-30) y plan `2026-09-30-s05-sos-semana-y-alerta.md` (aprobado el 2026-10-01), los dos sin
  sello.
- Ejecutor: Claude, por decisión de Felipe. La sesión principal coordinó con
  `subagent-driven-development`: un implementador por tarea (`ejecutor-php` o `ejecutor-front`, en
  sonnet), un revisor independiente por tarea y una revisión final de toda la rama en opus.
- Rama `fix/s05-sos-semana-y-alerta`, en el worktree `.claude/worktrees/sos-sprint`, desde
  `origin/main` `5e485e87`. Sin push ni merge.

## Implementación por tarea

| Tarea | Commits | Revisión |
|---|---|---|
| 1 · `Database::insertedId()` | `7e95630d`, `7857a3cc` | Una ronda: un INSERT que lanza ahora borra el id anterior |
| 2 · Los tres escritores usan `insertedId()` y el registro revierte con id 0 | `fc774e50` | Limpia |
| 3 · El resolvedor verifica la semana (`existsInWeek`) | `787c098d`, `eefd3d5b` | Una ronda: con alerta, un módulo distinto exige fila propia |
| 4 · El controlador lee y valida `semana` y `escalamiento_id` | `71c7454b`, `e170f50e` | Una ronda: un `escalamiento_id` malformado ya no se descarta en silencio |
| 5 · El cajón legado manda la semana o la alerta | `e959da38` | Limpia |
| 6 · React manda la semana y no disfraza el error de contrato | `4d6a0edf` | Limpia |
| Revisión final · prueba de contrato y bundle | `31ffb066`, `3038add6` | La ola de corrección quedó limpia |

## Decisiones de código (tomadas en el sprint)

- **R1, R7 y R8:** las pruebas corrieron en un contenedor aparte (`lps-sos-sprint-app`, puerto 8091)
  que monta el worktree, con la puerta de servicio abierta por variables y sin leer el archivo de
  entorno. El `app` compartido (8081) no se tocó. Si me equivoqué: no cuesta nada, el contenedor es
  desechable.
- **R6:** con alerta, un `modulo` distinto al de la alerta se acepta solo si la actividad existe en
  ese módulo en la semana de la alerta. No se rechaza en seco porque PG y PI comparten tabla. Si me
  equivoqué: pasa lo mismo que pasaba antes del sprint.
- **R9:** se recompiló y se versionó `public/app`. Si me equivoqué: el diff del bundle sería ruido;
  sin él, la pantalla no manda la semana.
- Comentario con id 0: no es defecto, porque el controlador ya responde «servicio no disponible».
  Si me equivoqué: un comentario fallido se mostraría como éxito.

## Verificación local (sobre `4d6a0edf`, salvo lo indicado)

- PHP `puro`: RC=0. PHP `http`: 121 de 122; el único fallo, `test_admin_modulos`, pasa con la cuenta
  D habilitada en la puerta, así que es del entorno.
- Contrato LPS: 106 de 106. PHPStan: sin errores.
- Frontend: vitest 1051 de 1051 y `tsc` RC=0.
- Playwright: cajón legado 9 de 9 y `lps-errores-contrato` en verde (sobre `3038add6`, con el rojo
  previo confirmado). Censo T02: en verde.
- Lint: los 748 errores de `check:frontend` son previos. `lps_drawer.js` tiene 3 errores en la base
  y 3 en la cabeza.

## Prueba en navegador con datos (2026-10-01, con el visto de producción de Felipe)

- Da Porto (proyecto 73), `test.R`, `/app/programa-general?semana=2`, en 1180×820.
- La foto previa tenía 0 alertas, 0 comentarios y 0 banderas.
- SOS sobre la actividad 1471: la pantalla dijo «Alerta registrada.» y «Alerta SOS LPS Activa». En
  la base quedó la alerta 1, con semana 2 y módulo PG; la bandera `alerta_crisis` se prendió solo en
  la semana 2. Consola sin errores.
- Restauración con marcador auditado. La foto posterior tiene los mismos conteos que la previa.
- No se hizo a mano un comentario desde PI. Lo cubren las nueve pruebas de Playwright del cajón.

## Hallazgos del sprint

- **El bundle de React versionado estaba viejo.** La tarea 6 cambió `frontend/src` sin recompilar
  `public/app`, y ni las pruebas ni las seis revisiones lo vieron. Lo destapó la prueba de
  navegador. Ahora está documentado en la trampa `el-bundle-de-react-versionado-no-se-recompila-solo`.
- **El archivo de entorno por symlink no se resuelve dentro del contenedor.** Esa trampa ya estaba
  en la wiki. Por eso los 7 fallos `http` de la línea base eran del entorno.

## Revisión y pendientes

- Revisión final de toda la rama (opus): la spec se cumple punto por punto y no hay nada crítico.
- La deuda está en `TASKS.md` («Diferibles», 2026-10-01): falta un gate de frescura del bundle; el
  mensaje crudo de `LpsDrawerProvider`; la consulta en producción de alertas con `unique_id` vacío
  (requiere el visto de producción); y menores cosméticos.

## Costo

Costo de la sesión: sin dato (2026-10-01). `costo-sesion.py` no lo reportó.
