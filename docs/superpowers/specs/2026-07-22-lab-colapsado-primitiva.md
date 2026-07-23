# Colapsado del sidebar como primitiva canónica adoptada en el laboratorio

**Fecha:** 2026-07-22 · **Estado:** aprobado por el usuario (chat) · **Alcance:** desktop ≥1180px, dark (+ linen para coherencia de árbol)

## Problema

El pulido del rail colapsado (sin-scroll, píldora de label al hover/foco, separadores hairline,
iconos a 20px, jerarquía de color de icono, anillo del activo) se implementó en el shell real de
`programacion-intermedia`, pero vive scopeado a `body.aia-shell--sidebar` en
`public/css/design-system/adapters/shell-sidebar.css`. El **laboratorio del design system**
(`/internal/design-system`, familia `shell-navigation`) — que DESIGN.md define como "la referencia
visual versionada de esas mismas primitivas" — no muestra ese pulido: no añade la clase
`aia-shell--sidebar`, no carga `shell-sidebar.css`, y su fixture está desactualizado (11 ítems con
iconos viejos, arranca expandido). El lab no es fiel a lo que se sirve en producción.

## Decisiones del usuario

1. **Promover a primitiva canónica**: mover el colapsado pulido a `navigation.css`
   (`.aia-navigation--sidebar[data-sidebar-state="collapsed"]`), que el lab, el shell y el
   project-selector cargan todos.
2. **Solo las primitivas genéricas** suben. Los flyouts de GESTIÓN de semanas
   (`.shell-week-flyout*`, dependientes de `shell_week_admin.js` + `#shellWeekMenusData`) se quedan
   en `shell-sidebar.css` — son del shell, no del componente.
3. **Project-selector hereda** las primitivas (consistencia total) y su golden se regenera.
4. **Golden del lab captura el estado colapsado por defecto** (`initialState => 'collapsed'`).

## Diseño

### 1. Promoción del CSS (shell-sidebar.css → navigation.css)

Transformación mecánica de cada selector de primitiva:
`body.aia-shell--sidebar .aia-navigation--sidebar[data-sidebar-state="collapsed"] X`
→ `.aia-navigation--sidebar[data-sidebar-state="collapsed"] X`.

**Se promueve** (bloques hoy en shell-sidebar.css, aprox. líneas 141-360, EXCLUYENDO los
`.shell-week-flyout*`):
- `overflow: visible` del aside/nav colapsado (sin-scroll).
- padding comprimido de header/footer/nav; gap de `.aia-sidebar__group ul`.
- ocultar `.aia-sidebar__context` y `.aia-sidebar__group h3` en colapsado.
- separador hairline `.aia-sidebar__group + .aia-sidebar__group::before`.
- iconos a 20px (`.aia-sidebar__nav .aia-icon__glyph`), jerarquía de color de icono
  (reposo `--ds-active-text-secondary`, hover/foco/activo encienden), anillo del activo.
- píldora de label (`.aia-sidebar__label` / `.aia-sidebar__toggle-label` absolutas, reveal al
  hover/focus, `prefers-reduced-motion`).

**NO se promueve** (se queda en shell-sidebar.css):
- push-layout del body `body.aia-shell--sidebar:has([data-sidebar-state="collapsed"]) { padding-left }`
  (línea ~17) — es cómo el shell reserva el ancho del contenido, no una primitiva.
- context-bar y chip de semana (`.context-bar`, `.context-week-*`).
- todos los `.shell-week-flyout*` (gestión de semanas).

**Ajuste de capa**: navigation.css vence el reset global `* { padding: 0 }` de styles.css con
`!important` en sus paddings (documentado en navigation.css:138). Las primitivas promovidas que
fijan padding llevarán `!important` siguiendo ese patrón, en vez de depender de `@layer
legacy-overrides` (que era el mecanismo en shell-sidebar.css). El resto (overflow, position del
flyout, colores, transiciones) no necesita `!important`.

**Verificación de no-regresión del scope**: tras mover, `shell-sidebar.css` conserva solo el
push-layout del body, la context-bar y los `.shell-week-flyout*`. El shell de PI hereda las
primitivas desde navigation.css (idéntico resultado visual, verificado por probe).

### 2. Fixture del laboratorio (`views/design-system/families/shell-navigation.php`)

- `'initialState' => 'expanded'` → `'collapsed'`.
- Iconos remapeados a los 13 únicos: Subcontratistas `contract`→`company`; Programación
  Intermedia `tasks`→`unlock`; Programación Semanal `calendar`→`week-commit`; Familias
  `list`→`hierarchy`; Paquetes conserva `contract`; Semanas del Proyecto conserva `calendar`;
  Control Tower `chart`, Programa General `program`, Profesionales `user`, Plan de Compras
  `clipboard` sin cambio.
- 3 ítems nuevos: **Indicadores LPS** (`gauge`, grupo Información), **Control de Cambios**
  (`change`, Información), **Actualizar Cronograma** (`sync`, Obra) → total 13 en 3 grupos.
- Semanas del Proyecto: quitar `'state' => 'disabled'` → link normal (en el lab no hay gestión de
  semanas; es ítem navegable de demostración, no el action con flyout del shell).
- Guardas de contrato respetadas: labels obligatorios presentes; ningún label "Integración";
  3 grupos Información/Obra/Compras.

### 3. Tests de conteo (`tests/browser/design-system-lab-sidebar.mjs`)

Actualizar los asserts al fixture nuevo: Información pasa de 4 a 6 ítems (añade Indicadores y
Control de Cambios), Obra de 3 a 4 (añade Actualizar Cronograma), Compras sigue en 3. El assert
"no Integración" se mantiene. El estado inicial ahora es `collapsed` — ajustar cualquier assert
que asuma `expanded` de arranque (el toggle sigue alternando ambos en runtime).

### 4. Goldens y gobernanza

- **shell-navigation** (lab): regenerar los 2 escenarios declarados en `laboratory.json`
  (`shell-navigation-dark-1180x820`, `-1440x900`) → ahora rail colapsado pulido; actualizar sus
  sha256. Regenerar también los PNG no declarados (linen ×3, dark-390) para coherencia de árbol.
- **project-selector**: regenerar `project-selector-dark-1180/1440` (hereda primitivas al colapsar)
  y actualizar sha si su manifiesto los declara.
- **Aprobación humana explícita** de las capturas nuevas ANTES de sellar sha256 (AGENTS.md). Nada
  de `--update-snapshots` a ciegas.
- `decisions.md`: añadir línea registrando que el estado colapsado pulido pasa a primitiva
  canónica versionada por el lab. No se toca `family-approvals.json` (mismo candidato
  `sidebar-shell`, misma matriz dark 1180/1440).

## Verificación

- **Regresión del shell (crítica)**: probe Playwright de computed styles en `/programacion-intermedia`
  1180×820 dark — rail colapsado sin scroll, glifos 20px, píldora al hover, separadores — idéntico
  a antes de mover el CSS. `tests/browser/shell-week-admin.mjs` sigue 11/11 (flyouts de gestión
  intactos).
- **Contratos estáticos**: `shell-navigation.test.mjs`, `contracts.test.mjs` (conteo 20 + sha),
  `test_design_system_components.php`, `test_foundation_shell_contract.mjs` verdes.
- **Lab**: `design-system-lab-sidebar.mjs` (conteos nuevos) + `design-system-lab.visual.mjs`
  (goldens regenerados) verdes tras aprobación.
- **Evidencia en vivo**: capturas del lab colapsado (dark 1180/1440), PI colapsado y
  project-selector colapsado para confirmar paridad — worktree aislado en puerto propio.

## Fuera de alcance

- Flyouts de gestión de semanas como primitiva (siguen exclusivos del shell).
- Mobile/tablet (390 se regenera por coherencia de árbol, no se rediseña); linen se regenera pero
  el alcance de diseño es dark desktop.
- Tocar `family-approvals.json` o el candidato aprobado.
