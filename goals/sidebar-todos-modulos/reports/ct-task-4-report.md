# CT Task 4 — Dark polish del dashboard (CSS) + QA de las 8 hojas

- **Status:** DONE
- **Commit:** (pendiente de asignar tras `git commit`, ver mensaje `feat(control-tower): dashboards en dark coherente (tokens DS)`)

## Qué se hizo

1. **Auditoría de hardcodes light (Step 1).** `grep` de hex/`rgb()`/`white`/`black` literales en `bi-control-tower.css` y `views/bi/*.php`: sin resultados — CT-1/CT-2/CT-3 ya habían tokenizado superficies, KPIs, estados y footer con `--ds-*`. El único hallazgo real de "light residual" no era un hex sino un **bug de cascada**: `.bi-control-tower-page { color: var(--ds-active-text-primary); }` (sin `!important`) perdía contra el reset unlayered de Tailwind CDN, resolviendo a `rgb(29,29,31)` (casi negro) en vez del token claro — confirmado por introspección de `getComputedStyle`/CSSOM (ratio de contraste real 1.14:1 en las tabs de hoja y heredado por todo texto sin `!important` propio). Mismo patrón en `.nav-item.active` (`background-color`/`border-left-color`) y `.nav-item:hover:not(.active)` (`background-color`), que tampoco aplicaban. Se corrigió agregando `!important` a esas 4 declaraciones (mismo patrón ya usado por el resto del archivo, p.ej. `background-color` de la misma regla `.bi-control-tower-page` ya lo tenía). Verificado antes/después con introspección JS: texto de tabs pasó de `rgb(29,29,31)` a `rgb(247,250,248)` (~18:1 contra el canvas `#0b100d`), y el estado activo/hover de las tabs ahora sí pinta su fondo verde/borde naranja.
2. **Re-tematizado (Step 2):** no hizo falta re-tematizar superficies/cards/KPIs (ya en tokens `--ds-*`); el trabajo real fue el fix de cascada arriba.
3. **Poda de deuda muerta** (alcance explícito del brief):
   - `public/css/bi-control-tower.css`: eliminadas todas las reglas huérfanas `.bi-sidebar*` (aside retirado en CT-1: `.bi-sidebar`, `.bi-sidebar > header`, `.bi-sidebar-scroll`, `.bi-sidebar nav`, `.bi-sidebar .nav-item`, `.bi-sidebar #project-dropdown-text` y combinaciones, tanto en el bloque desktop `@media (min-width:768px)` como en el bloque mobile `@media (max-width:767px)`) y `.bi-mobile-filter-toggle`/`.bi-mobile-filter-panel` (toggle retirado en CT-2). Archivo: 2079 → 1931 líneas netas antes del fix de contraste (que sumó 4 palabras `!important`). Selectores mixtos (p.ej. `.bi-filter-form .aia-btn--secondary, .bi-mobile-filter-toggle.aia-btn--secondary`) se podaron parcialmente, dejando solo el selector vivo.
   - `public/js/modules/bi-spa.js`: eliminada `toggleMobileFilters()` (0 referencias en toda la base — ni `views/bi/*.php` ni el propio archivo la invocan). **No** se eliminó `closeMobileFilters()` ni `renderMobileFilterState()` (el "showMobileFilterCount" del brief no existe con ese nombre; la función real es `renderMobileFilterState`, que sí tiene 6 call-sites activos — eliminarla habría roto `switchView`/`applyFilters`/etc. con `ReferenceError`). Verificado con `grep` exhaustivo antes de tocar nada.
   - `views/bi/_nav.php`: ARIA de las 8 tabs mejorada al patrón de `control-tower.php` (radar tabs): `aria-selected="true|false"` + `tabindex="0|-1"` (roving, inicial server-side por `$reportKey`) + `aria-controls="view-<id>"` en vez de `aria-current="page"` (redundante con `aria-selected` en `role="tab"`, no usado por CSS/JS). `views/bi/control-tower.php`: agregado `role="tabpanel"` + `aria-labelledby="nav-<id>"` a las 8 `<section id="view-*">`. `bi-spa.js`: `switchView()` ahora alterna `aria-selected`/`tabindex` (antes `aria-current`); nueva `wireSheetTabsEvents()`/`handleSheetTabsKeydown()` implementa roving tabindex real (ArrowLeft/ArrowRight/Home/End + foco), siguiendo el mismo patrón que `handleRadarDrilldownKeydown`.
4. **Hallazgo NO tocado (fuera del alcance explícito de este brief):** `.bi-sheet-selector`/`.bi-sheet-select-control`/`.bi-sheet-nav-list` (CSS) y el no-op `document.getElementById('bi-mobile-sheet-select')` en `switchView()` (JS) son deuda muerta adicional (0 referencias de markup en toda la base) — no estaban en la lista de 3 ítems del brief, así que se flaggeó como tarea separada (`spawn_task`, id `task_a0684468`) en vez de expandir el diff de esta tarea.

## QA visual (8 hojas, 1180×820, dark, `docker` sirviendo `localhost:8081`)

Recorridas las 8 hojas vía `switchView()` (equivalente exacto del click de tab): **torre-control** (Resumen Ejecutivo), **programa-general**, **curva-s**, **intermedia**, **semanal**, **pdc**, **cic**, **cip**. Para cada una:
- Screenshot a 1180×820 dark — superficies, cards, KPIs, tablas y badges de estado (`OK`, `Alto`, `Adelantado`, `Cumple`) coherentes en dark, sin restos light.
- Contraste automatizado: script inyectado (WCAG 2.1 relative-luminance) recorrió **todos** los nodos de texto visibles de `.bi-control-tower-page` en las 8 hojas — **0 elementos por debajo de 4.5:1** tras el fix de cascada (antes del fix: las 8 etiquetas de tab + sus íconos fallaban en 1.14:1).
- `document.documentElement.scrollWidth === clientWidth` (1180 === 1180) en las 8 hojas — sin overflow horizontal.
- Cajón de filtros (`data-bi-filter-trigger` → abre/cierra) revisado también: dark, legible, botón "Aplicar" con acento naranja de marca.
- Roving tabindex verificado con `KeyboardEvent('ArrowRight')` real: mueve el foco, actualiza `aria-selected`/`tabindex` y dispara `switchView()`.

**Datos reales vs. vacíos (según lo advertido en el contexto de la tarea — el corte activo no tiene datos para los proyectos de este entorno):**
- **Con datos reales:** `programa-general` (Curva S con curva teórica + real, gauges "Avance físico 2,7%"/"Cumplimiento 150%" con pills semánticas verdes "Adelantado"/"Cumple").
- **Sin datos para el corte activo (charts/tablas vacíos, `0`/`--`):** `torre-control`, `curva-s`, `intermedia`, `semanal`, `pdc`, `cic`, `cip`. Se verificó que el estado vacío se ve coherente (dark, sin quiebres de layout, badges "Alto"/"OK" legibles) aunque no se pudo confirmar visualmente el aspecto con series pobladas en esas 7 hojas.

## Comandos y salida real

```
npx biome check public/css/bi-control-tower.css public/js/modules/bi-spa.js
# baseline (git stash, antes de esta tarea): 6 errors, 169 warnings, 7 infos
# tras poda de dead code:                     6 errors, 106 warnings, 7 infos
# tras el fix de cascada (+4 !important):      6 errors, 110 warnings, 7 infos
# → errors/infos idénticos a baseline; warnings NETO por debajo de baseline (110 < 169). Sin deuda nueva.

node --check public/js/modules/bi-spa.js   # sin salida (sintaxis OK)

node tests/browser/shell-sidebar-rollout.mjs
# 63/63 checks OK (antes y después de los cambios de esta tarea)
```

## Concerns

- El fix de contraste (4 `!important` nuevos) no estaba en el checklist original del brief (que asumía "sin hardcodes light" = sin hex nuevos), pero es un hallazgo real de legibilidad descubierto durante el Step 3 (QA visual) y directamente exigido por el propio brief ("confirma legibilidad texto ≥4.5:1"); se corrigió con el mismo patrón `!important` que ya usa el 95% del archivo para superar el mismo problema de cascada (Tailwind CDN unlayered vs. `@layer components`).
- Deuda `.bi-sheet-*` (CSS) + `bi-mobile-sheet-select` no-op (JS) flaggeada aparte (`task_a0684468`), no incluida en este commit.
- No se tocó `Programa General` y sus archivos protegidos fuera de lo ya cubierto por este brief (solo `bi-control-tower.css`, `bi-spa.js`, `views/bi/_nav.php`, `views/bi/control-tower.php`).
- `.impeccable/design.json` y `DESIGN.md` quedan fuera del `git add` de este commit (ya estaban modificados por trabajo ajeno en el árbol, consistente con el estado reportado por CT-1/CT-3).
