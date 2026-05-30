# Plan de Implementación: Replicación de Resaltado Intensificado y Fix de Overlays en Todo el Sistema Operativo LPS

Este plan tiene como objetivo estandarizar y replicar el comportamiento de resaltado visible de filas y la remoción de interferencias de overlays visuales (`box-shadow: 9999px`) en todas las grillas e interfaces de la aplicación (Programa General, Programación Semanal, Plan de Compras, etc.), garantizando coherencia visual y usabilidad en cualquier monitor.

---

## 🧠 Enfoque Metodológico: Kaizen PDCA Atómico

1. **PLAN (Planificar):** Diseñar las reglas CSS unificadas en `styles.css` utilizando variables de la escala Tailwind 200/300, asegurar especificidad con `#hot-container` e identificar las vistas Handsontable exactas con overlays visuales.
2. **DO (Hacer):** Aplicar quirúrgicamente las intensificaciones en `styles.css` y los selectores `:not(.row-state)` en las vistas de PG y Actualizar Cronograma.
3. **CHECK (Verificar):** Ejecutar inspección visual dinámica en el navegador para cada uno de los 12 módulos en desarrollo, con y sin filtros de leyenda, y comprobar los valores RGB reales.
4. **ACT (Actuar/Consolidar):** Actualizar el cache-buster transversal, realizar auditoría unificada de errores de consola y actualizar el roadmap general.

---

## 🎯 Proposed Changes

### 1. Estandarización Cromática y Especificidad Transversal (Fase 1)

#### [MODIFY] [styles.css](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/public/css/styles.css)
- **Variables de Programa General (PG):** Intensificar las variables locales `--pg-...` de la escala Tailwind 100 a ~200-300:
  - `--pg-critical-bg`: `#fecaca` (red-200)
  - `--pg-delayed-bg`: `#fed7aa` (orange-200)
  - `--pg-due-bg`: `#fde68a` (amber-200)
  - `--pg-future-bg`: `#d9f99d` (lime-200)
  - `--pg-progress-bg`: `#bae6fd` (sky-200) (o `#bbf7d0` green-200 según convenga)
  - `--pg-restr-0-bg`: `#fde68a` (amber-200)
  - `--pg-restr-1-bg`: `#fed7aa` (orange-200)
  - `--pg-restr-2-3-bg`: `#fef08a` (yellow-200)
  - `--pg-restr-4-6-bg`: `#bbf7d0` (green-200)
- **Especificidad en PG:** Anteponer `.pg-page #hot-container .handsontable td.pg-state-...` en todas las reglas de celda de Handsontable para asegurar prioridad absoluta.
- **Variables de Programación Semanal (PS):** Definir variables locales específicas en `.ps-page` para la escala Tailwind 200/300 y mapearlas homólogamente:
  - `--ps-critical-bg`: `#fecaca` (red-200)
  - `--ps-high-bg`: `#fed7aa` (orange-200)
  - `--ps-medium-bg`: `#fde68a` (amber-200)
  - `--ps-info-bg`: `#bae6fd` (sky-200)
  - `--ps-control-bg`: `#bbf7d0` (green-200)
  - `--ps-neutral-bg`: `#f1f5f9` (gray-100)
- **Especificidad en PS:** Anteponer `.ps-page #hot-container .handsontable td.ps-row-state.ps-alert-...` en todas las reglas de celda de Handsontable.
- **Variables de Plan de Compras (PDC) / DataTables:** Intensificar las variables de `:root` para PDC a la misma escala Tailwind 200/300:
  - `--pdc-missing-bg`: `#f3e8ff` → `#e9d5ff` (morado-200)
  - `--pdc-critical-bg`: `#fee2e2` → `#fecaca` (rojo-200)
  - `--pdc-delayed-bg`: `#fff1e7` → `#fed7aa` (naranja-200)
  - `--pdc-completed-late-bg`: `#fff7d6` → `#fef08a` (amarillo-200)
  - `--pdc-completed-ontime-bg`: `#e8f6ec` → `#bbf7d0` (verde-200)
  - `--pdc-active-bg`: `#e6f0ff` → `#bae6fd` (azul-200)

### 2. Remoción de Overlays Box-Shadow (Fase 2)

#### [MODIFY] [programa_general.view.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/views/programa-general/programa_general.view.php)
- Modificar las reglas `.pg-page #hot-container td.pg-cell-editable` and `.pg-page #hot-container td.pg-cell-readonly` para que su `box-shadow: inset 0 0 0 9999px` aplique únicamente con el selector `:not(.pg-state-atrasado):not(.pg-state-atrasada):not(.pg-state-restr-0):not(.pg-state-debe-iniciar):not(.pg-state-actividad-futura):not(.pg-state-en-curso):not(.pg-state-a-tiempo-en-curso):not(.pg-state-terminada):not(.pg-state-sin-datos)`.
- Esto garantiza que los fondos de estado de fila de PG nunca se deslavan por el box-shadow overlay, conservando la visibilidad del color original.

#### [MODIFY] [programaGeneralActualizar.view.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/views/programa-general-actualizar/programaGeneralActualizar.view.php)
- Modificar `.pg-page #hot-container td.pg-cell-editable` and `.pg-page #hot-container td.pg-cell-readonly` para aplicar el mismo condicional `:not(...)` para todos los estados de fila.

### 3. Cache Buster Transversal (Fase 3)

#### [MODIFY] [linksComunesHead2.js](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/public/js/linksComunesHead2.js)
- Cambiar la versión string de `styles.css?v=piStateColors1` a `styles.css?v=piStateColors2` para forzar la recarga del archivo CSS global en todos los navegadores de los usuarios.

---

## 🔬 Verification & Validation Plan

### Checklist de Cumplimiento Técnico
- [ ] Modificadas las variables y reglas CSS de PG en `styles.css` con especificidad `#hot-container`.
- [ ] Modificadas las variables y reglas CSS de PS en `styles.css` con especificidad `#hot-container`.
- [ ] Modificadas las variables y reglas CSS de PDC (Plan de Compras) en `styles.css`.
- [ ] Aplicado el fix de overlays `:not(...)` en `programa_general.view.php`.
- [ ] Aplicado el fix de overlays `:not(...)` en `programaGeneralActualizar.view.php`.
- [ ] Actualizado el cache-buster `piStateColors2` en `linksComunesHead2.js`.

### Checklist de Validación con Navegador (Glance)
- [ ] **Programa General (PG):** Verificar coloreado intenso en grilla y persistencia al aplicar filtros.
- [ ] **Programación Semanal (PS):** Verificar coloreado intenso en grilla y leyenda.
- [ ] **PDC / Plan de Compras:** Verificar coloreado intenso en filas de DataTables y legend badges.
- [ ] **Actualizar Cronograma:** Verificar coloreado correcto de celdas y bypass de overlays.
- [ ] **Subcontratistas / Profesionales / Listado Actividades / control-cambios:** Confirmar que no hay errores de CSS en consola y que las dimensiones de Handsontable cargan de forma íntegra.

---

## ❓ Preguntas Abiertas & Dudas
- Ninguna. Los detalles técnicos y la paleta cromática unificada han sido plenamente consensuados bajo la directiva `/grill-me`.
