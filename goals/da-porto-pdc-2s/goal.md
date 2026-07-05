# Goal — Da Porto PDC 2 semanas de control end-to-end

## Articulated goal

Automatizar end-to-end, 100% con Playwright MCP contra la app Docker corriendo en `http://localhost:8081`, la creación completa de un **Plan de Compras y Contrataciones (PDC) desde cero absoluto** para el proyecto **Da Porto** (project_id=73), recorriendo los tres módulos encadenados (Listado de Actividades → Contratos → PDC) y validando en cada nivel: familias, modalidades de contratación, paquetes de contratación, número de contratos por paquete, insumos y recursos, y fechas de proceso calculadas. Luego, **controlar durante 2 semanas del proyecto**: crear dos semanas nuevas vía `nueva_semana.php` y validar que el PDC se auto-actualiza en cada una (copia + recompute de paquetes + recompute de estados + duplicación de subcontratos), más una prueba de propagación de cambios en Familias hacia el PDC con recálculo de fechas. Todo con evidencia: capturas, aserciones SQL, log de runs semi-auto, y un mini-reporte de métricas final. Dejar un test `.mjs` reutilizable en `tests/browser/da-porto-pdc-full-cycle.mjs`.

## Shared understanding

Ver [`facts.md`](./facts.md) — 25 hechos verificables, todos con verificación automatizada.

## Execution plan

Ver [`plan.md`](./plan.md) — 14 pasos ordenados (Step 0 al 13) con verification por hecho, riesgos y preguntas abiertas.

## Done condition

Los 25 facts aceptados están verificados con evidencia concreta:
- Screenshots en `tests/browser/evidence/da-porto-pdc-2s/`
- Aserciones SQL vía `docker compose exec db mysql`
- Aserciones DOM vía `playwright_browser_snapshot` / `verify_*`
- `goals/da-porto-pdc-2s/metrics-report.md` generado
- `tests/browser/da-porto-pdc-full-cycle.mjs` escrito y compilable
	Datos persistidos en Da Porto (sin rollback — el usuario eligió "persist_as_new_seed").

## Launch

```
/goal goals/da-porto-pdc-2s/goal.md
```