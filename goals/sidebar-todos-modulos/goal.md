# Goal — Rollout del shell sidebar a todos los módulos

## Objetivo

Mapear todas las secciones operativas de la app y llevar el **shell sidebar canónico** —tomando `/programacion-intermedia` como la referencia correcta— a **todos los módulos** que hoy usan navegación legacy, de modo que cada módulo tenga la sidebar funcionando en sus **dos estados** (colapsada por defecto y expandida) con el mismo comportamiento que PI. Universo: **11 módulos** a migrar (Programa General, Prog. Semanal, Actualizar Cronograma, Profesionales, Subcontratistas, Control de Cambios, Familias de Actividades, Contratos, PDC `/pdc`, Indicadores, Control Tower); se excluye `/plan-compras` (isla React v2 en desarrollo) y el selector de proyectos.

## Entendimiento compartido

Los hechos aceptados (criterios verificables) están en [`facts.md`](facts.md) — incluyen el mapa/inventario, la paridad de comportamiento con PI (flyouts sin recorte, paneles opacos, cero-scroll, toggle compacto, default colapsado), la conservación del cajón LPS, la visibilidad RBAC, el registro en `foundation-shell.json` con gates verdes, y el test data-driven sobre las 12 rutas.

## Plan de ejecución

El plan aprobado está en [`plan.md`](plan.md): "receta PI" repetible por módulo, test data-driven escrito primero, rollout por grupos de riesgo creciente (A: páginas simples · B: grids operativos con cajón LPS · C: Indicadores/Control Tower), registro incremental de rutas y gates del foundation-shell.

## Alcance revisado (decisiones del usuario durante ejecución)
- Ejecución **directo en main** (consentimiento explícito).
- **Compras** (Familias de Actividades, Contratos, PDC) **OMITIDOS** de este goal (ligados al desarrollo PDC v2 activo).
- **Control Tower DIFERIDO a un sub-goal dedicado** con brainstorming: es una SPA con stack propio (Tailwind, no-DS) y su propia bi-sidebar; su integración es un rediseño, no una migración mecánica.
- Alcance efectivo de ESTE goal: **11 rutas** = PI + Programa General, Profesionales, Subcontratistas, Control de Cambios, Actualizar Cronograma, Programación Semanal (base + CIC/CNC/CNP), Indicadores.

## Condición de done (ajustada)
- Los 11 módulos en alcance renderizan el shell sidebar canónico con la navbar legacy reemplazada y el cajón LPS conservado donde aplica.
- En cada módulo la sidebar funciona en ambos estados (colapsada default / expandida), sin scroll ni overflow horizontal a 1180×820 dark, con flyouts opacos sin recorte e ítem activo correcto.
- Las 11 rutas están declaradas en `docs/design-system/manifests/foundation-shell.json` y `node tests/browser/shell-sidebar-rollout.mjs` pasa 11/11 (55/55 checks), con los gates del foundation-shell verdes.
- Existe el mapa/inventario en `evidence/mapa-sidebar.md`.
- Control Tower queda fuera (sub-goal futuro).

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-31

### Lo que se logró

- **11 rutas** migradas al shell sidebar canónico en `main`: PI, Programa General, Profesionales,
  Subcontratistas, Control de Cambios, Actualizar Cronograma, Programación Semanal (base + CIC/CNC/CNP),
  Indicadores.
- `shell-sidebar-rollout.mjs` pasa 11/11 (55/55 checks) con gates del foundation-shell verdes.
- Mapa/inventario completo en `evidence/mapa-sidebar.md`.
- Cada módulo funciona en ambos estados (colapsada/expandida) a 1180×820 dark, sin overflow horizontal.

### Excepciones documentadas

- **Compras** (Familias de Actividades, Contratos, PDC `/pdc`): omitidas — PDC v2 tiene su propia
  navegación; las rutas viejas ya están retiradas (ver `retiro-listado-contratos`).
- **Control Tower:** diferido a goal dedicado [`bi-control-tower-gemini`](../bi-control-tower-gemini/goal.md)
  — es una SPA con stack propio que requiere rediseño, no migración mecánica.
