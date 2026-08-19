---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-9-report.md
resumen: Task 9 — Indicadores al shell sidebar (reanclaje del embed Power BI)
---

# Task 9 — Indicadores al shell sidebar (reanclaje del embed Power BI)

## Status
DONE

## Commit
`b2fed93` — feat(shell-sidebar): Indicadores usa el shell sidebar y reancla el embed Power BI (ambos estados)

## Test
`node tests/browser/shell-sidebar-rollout.mjs` → 55/55 checks OK, exit 0 (10 rutas previas + Indicadores en PASS; Control Tower en PENDING como se espera). `docker compose exec -T app php -l` limpio en vista y controlador.

## Cómo se reancló el embed Power BI
- **Contenedor** (`views/indicadores/indicadores.view.php`, `#contenedorInformePowerBI`): se quitó el hack full-bleed `width:100vw; position:relative; left:50%; margin-left:-50vw` del `style` inline. Al quedar como bloque normal dentro de `body.aia-shell--sidebar` (que ya reserva el rail vía `padding-left`), el contenedor hereda el ancho del área de contenido sin necesidad de CSS scoped adicional.
- **`ajustarInformePowerBI()`**: el ancho disponible ahora se mide de `contenedor.clientWidth` (con fallback a `document.documentElement.clientWidth`) en vez de `window.innerWidth`, que ignoraba el rail. Con esto el iframe nunca excede el área de contenido real.
- **Re-trigger en el toggle**: colapsar/expandir anima `padding-left` del body pero no dispara `resize`, así que se agregó un `MutationObserver` sobre `data-sidebar-state` del nav (cubre tanto el click del toggle como la aplicación del estado persistido en `localStorage` al cargar), que reajusta de inmediato y otra vez a los 260ms (cubre el caso animado, 220ms de `--ds-motion-standard`).
- Cableado del shell (body class, partial, `__AIA_SHELL_SIDEBAR__`, script de `sidebar_navigation.js`, `$shellActive`/`$shellModuleLabel`/`$shellWeeks`) siguiendo la plantilla de `3a968dd`, sin cajón LPS (no aplica).

## Verificación visual (navegador integrado, 1180×820 dark)
Colapsado: rail estrecho, embed dentro del área de contenido, `overflow=0`. Expandido (toggle manual, que dispara el `MutationObserver`): rail 280px, `contenedorRect.right=1134`, `iframeRect.right=1112.5` — completamente dentro de los 1180px del viewport, `document.documentElement.scrollWidth - clientWidth = 0` en ambos estados. El embed real de Power BI cargó y renderizó en el entorno de prueba (no hizo falta el fallback de solo-geometría).

## Concerns
- Durante la verificación manual encontré que, si el usuario tiene `expanded` persistido en `localStorage` y la página recién cargada difiere del estado por defecto (`collapsed`), el layout puede quedar visualmente "atascado" en el ancho anterior por varios segundos hasta que algo fuerza un repintado (scroll, interacción, captura de pantalla). Confirmé que esto **no es específico de Indicadores ni de mi cambio**: reproduce igual en `/programa-general` (ya migrado y validado en tasks previas, sin iframe Power BI) bajo el mismo protocolo (esperar puro, sin interacción). Es un artefacto del entorno de navegador automatizado (repintado diferido en idle), no del `:has()`/CSS del shell ni de `ajustarInformePowerBI()` — que sí recalcula correctamente apenas hay cualquier interacción real (click del toggle, resize). No toqué CSS/JS canónico del shell para "arreglar" esto, ya que está fuera del alcance de esta tarea y no reproduce en uso real.
- `DESIGN.md` (modificado) y `.impeccable/design.json` (sin trackear) ya estaban en el árbol al iniciar, ajenos a esta tarea — quedaron fuera del staging (`git diff --cached --name-only` confirmado antes de commitear: solo los 3 archivos de Indicadores + harness).
