# Wiki v2 — visual, etiquetada, misma metodología

**Fecha:** 2026-08-18 · **Decisión del usuario:** replantear toda la wiki sin perder la
metodología LLM Wiki (Karpathy), con un Obsidian altamente visual y todos los documentos del
vault organizados y etiquetados. Dos decisiones de grilleo que fijan el diseño: **se permiten
plugins de comunidad** (cae la regla «solo nativo» de `docs/wiki-operacion.md:24`) y **el
frontmatter se extiende a todas las fuentes** (docs/, goals/, .md de la raíz), como ampliación de
la excepción que ya existía para `goal.md`.

## Qué se conserva (el esqueleto Karpathy)

1. **Tres capas intactas:** fuentes (cuerpo intocable desde la wiki) · wiki (`memoria/`, la
   escribe el asistente) · esquema (`docs/wiki-operacion.md`). El frontmatter en fuentes es
   **metadato añadido, nunca contenido editado** — misma naturaleza que el pie «Archivos de este
   goal».
2. **Las cuatro operaciones** (ingest, query, lint, veracidad) y la alarma por commits.
3. **Precedencia:** código > `AGENTS.md` > `memoria/`. Nada de la wiki es contrato.
4. **Una nota, un hecho**; `estado: derogada` en vez de borrar.

## Qué cambia

### 1 · Esquema de metadatos único para todo el vault

Todo archivo `.md` del vault lleva frontmatter. Campos: los seis de siempre (`tipo`, `estado`,
`fecha`, `areas`, `fuente`, `resumen`) más **`tags` con vocabulario cerrado**.

- **`tipo` se amplía** con los tipos de fuente: `contrato`, `spec`, `plan`, `reporte`,
  `evidencia`, `biblia`, `guia`, `goal-doc` (se suman a los nueve de la wiki).
- **`capa: fuente | wiki | esquema`** — nuevo campo que le dice al lint qué reglas aplicar: a las
  fuentes solo se les exige frontmatter bien formado; el cuerpo no se lintea ni se toca.
- **`tags` cerrados y transversales** (no duplican `tipo` ni `areas`): `moc`, `dashboard`,
  `plantilla`, `pendiente`, `trampa`, `leer-antes-de-tocar`, `generado`, `archivo`.
- Las **13 áreas no cambian**.

### 2 · Navegación y jerarquía visual

- **Home dashboard** (`memoria/index.md` renovado): callouts de estado, catálogo Bases embebido,
  accesos a los 13 MOCs, la cola de pendientes y los canvas. Se abre solo al entrar (plugin
  Homepage).
- **13 MOCs de área** (hoy hay 7 mapas): uno por área válida, taggeados `moc`, cada uno con su
  Bases embebido filtrando su área — el auto-MOC vive en la vista, no en enlaces a mano.
- **Canvas nativos** (se activa el core plugin): `tablero-de-control.canvas` (la cola visual),
  `mapa-del-sistema.canvas` (arquitectura app+admin+datos), `cascada-lps.canvas` (PG→PI→PS con
  sus estados). Los flujos finos siguen en Mermaid dentro de las páginas.
- **Grafo con grupos de color** por capa y área (`.obsidian/graph.json`): wiki por tipo, fuentes
  por carpeta, contratos de raíz destacados.
- **CSS snippet propio** con la cascada de severidad del design system (Crítico → Sin problema)
  para callouts y badges — el vault habla el mismo idioma visual que la app.

### 3 · Plugins de comunidad (curados, versionados en `.obsidian/plugins/`)

| Plugin | Para qué |
|---|---|
| **Dataview** | Consultas dinámicas: tareas abiertas, decisiones recientes, huérfanas |
| **Tasks** | Tareas con estado/fecha consultables en cualquier página |
| **Kanban** | La cola de pendientes como tablero arrastrable |
| **Excalidraw** | Esquemas ricos a mano alzada (arquitectura, flujos de negocio) |
| **Iconize** | Iconos por carpeta y nota — orientación visual inmediata |
| **Homepage** | Abre el dashboard al entrar |
| Tema **Minimal** + Style Settings | Estética sobria y densidad configurable |

Regla nueva: la wiki debe seguir **leyéndose** sin plugins (Bases y Markdown puro como base);
los plugins amplifican, no sostienen. Se versionan para que cualquier máquina los tenga al clonar.

### 4 · Los scripts acompañan el esquema

- `scripts/wiki-lint.mjs` v2: valida el vocabulario de `tags`, los `tipo` nuevos, el campo
  `capa`, y a las fuentes solo su frontmatter. La alarma de veracidad no cambia.
- `scripts/wiki-frontmatter.mjs` (nuevo): backfill idempotente del frontmatter en fuentes,
  deduciendo `tipo`/`areas` de la ruta, con `--dry-run` y aplicación por tandas.
- `scripts/wiki-arquitectura.mjs` y `wiki-registro.mjs` siguen igual (sus zonas generadas ganan
  el tag `generado`).

## Condición de hecho global

Lint v2 en verde sobre **todo el vault** (wiki + fuentes con frontmatter), Home dashboard y 13
MOCs operativos, los 3 canvas creados, plugins versionados y funcionando en frío (clon limpio),
y `docs/wiki-operacion.md` reescrito documentando el esquema v2. La ejecución por tandas y sus
verificaciones, en el plan hermano.
