---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md
resumen: replantear toda la wiki sin perder la metodología LLM Wiki (Karpathy), con un Obsidian altamente visual y todos los documentos del vault organizados y…
---

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

## Cierre

**Ejecutado, con una salvedad pendiente ya anotada.** Las seis tandas del plan hermano
(`docs/superpowers/plans/2026-08-18-wiki-v2-visual.md`) publicaron: `wiki-lint.mjs --estricto`
verde sobre 156 páginas y 414 de 417 fuentes, 13 MOCs con Bases embebido, 3 canvas nativos,
`docs/wiki-operacion.md` reescrito para el esquema v2 (`66012929`, `58240c2c`, `26a8fe80`,
`e5c540c3`, `7208edf9`).

**§3 · Plugins de comunidad — resuelto y ejecutado el 2026-08-20 (`2888ab77`), no encolado.**
La Tanda 4 cerró "sin plugins" a la espera de la decisión del usuario; esa decisión llegó después
y ya se ejecutó: Dataview, Tasks, Kanban, Excalidraw, Homepage y el tema Minimal están instalados
y verificados en pantalla. **Iconize quedó fuera** — su autor lo declara descontinuado, así que no
entra por decisión técnica, no por indecisión. Kanban entró con advertencia (funciona, busca quien
lo mantenga). Esta sesión (2026-08-24) verificó que ya no hace falta grillar esta pregunta con el
usuario: la evidencia de ejecución ya estaba en `TASKS.md`.

**§2 · Grafo con grupos de color — ejecutado y verificado en pantalla, 2026-08-24.** Al preguntarle
a Felipe, confirmó que Obsidian ya estaba abierto con el vault de `lps-aia`, lo que resolvió el
bloqueo que había dejado esto pendiente desde la Tanda 4 ("no se puede verificar sin abrir
Obsidian"). Con acceso vía `computer-use`, se configuraron tres `colorGroups` en
`.obsidian/graph.json` (antes `[]`, sin tocar desde el 2026-08-03) y se verificaron pintando en
la Vista gráfica real del vault, no solo escritos en el JSON:

| Grupo | Consulta | Qué pinta |
|---|---|---|
| Wiki | `path:memoria` | Rojo — las 162 páginas de `memoria/` |
| Fuentes | `path:docs OR path:goals` | Ámbar — `docs/` y `goals/` |
| Contratos de raíz | `file:AGENTS OR file:CLAUDE OR file:GEMINI OR file:README OR file:TASKS OR file:ROADMAP OR file:CHANGELOG OR file:IMPLEMENTATION_PLAN_INVENTORY` | Verde |

Todo lo demás (código, tests, vendor) queda sin colorear a propósito — es ruido para el grafo de
la wiki, no señal. Hallazgo de paso: el vault de `lps-aia` **sí** estaba registrado en Obsidian
(contradice la nota de `TASKS.md` del 20 de agosto que decía lo contrario) — lo que faltaba era
tenerlo *activo*; Obsidian tenía abierto otro vault ("Gerencia") con una carpeta que replica
`proyectos/lps-aia/`, que no es el mismo vault que declara `CLAUDE.md`.

Los tres grupos son una simplificación de la letra de la spec: "wiki por tipo" pedía distinguir
los 9 `tipo` de `memoria/` (moc, dashboard, goal-doc, etc.), no pintarla como un solo bloque.
Presentado a Felipe como decisión de panel el 2026-08-24 —quedarse en 3 grupos amplios, expandir a
grano fino por tipo, o un punto intermedio (solo MOC y dashboard separados)—: **ratificó los 3
grupos como definitivos**, no como provisional a la espera de más granularidad.

**Condición de hecho global:** cumplida en su totalidad. Ningún pendiente real queda de esta spec.
