---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: []
fuente: sesion
resumen: "Puerta de entrada a la wiki: qué es, cómo se opera y catálogo de todas sus páginas"
---
# Memoria del proyecto

Esta carpeta es la **memoria del proyecto**: el porqué de las decisiones, las trampas que ya
costaron tiempo, y un mapa por área que enlaza con la documentación que ya existe en el repo.

Sigue el patrón [LLM Wiki](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f):
tres capas, y el asistente mantiene la de en medio.

| Capa | Dónde | Regla |
|---|---|---|
| Fuentes | `docs/`, `goals/`, los `.md` de la raíz, el código | Se leen. **Su contenido no se edita desde aquí.** |
| Wiki | `memoria/` | La escribe el asistente. **Nunca se edita a mano.** |
| Esquema | Sección «Memoria del proyecto» de [[CLAUDE]] | Explica esta estructura y las tres operaciones. |

**Una excepción, decidida el 2026-08-02:** cada `goals/<slug>/goal.md` lleva al final una sección
«Archivos de este goal» que enlaza a sus hermanos y a [[estado|Estado de los goals]]. Es
navegación añadida al pie, no contenido modificado, y es lo único que hace que los 99 archivos de
`goals/` aparezcan tejidos en el grafo en vez de como islas. `docs/` sigue intacto.

El vault de Obsidian es la **raíz del repo**, no esta carpeta. Por eso los enlaces alcanzan a
`docs/`, `goals/` y a los `.md` de la raíz sin copiarlos aquí.

## Precedencia

**Código > [[AGENTS]] > `memoria/`.**

Nada de lo que hay aquí es contrato. Si una nota contradice al repo, gana el repo: corrige la nota
y márcala `estado: derogada` en vez de borrarla — saber que algo dejó de ser cierto también es
memoria.

**Áreas válidas** (lista cerrada de trece; `scripts/wiki-lint.mjs` la comprueba): `design-system` ·
`qa` · `docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` · `bi` · `admin` ·
`proceso` · `arquitectura`. Si necesitas una nueva, añádela primero al script y explica aquí qué
cubre; una lista que crece sin control deja de servir para filtrar.

## Las tres operaciones

- **Ingest** — al cerrar una tarea o al aparecer una fuente nueva: se escribe o actualiza la
  página, se actualiza este índice, se revisan las páginas relacionadas y se anexa una línea a
  [[log]].
- **Query** — preguntas contra la wiki, respondidas citando páginas. Si la respuesta era valiosa y
  no estaba escrita, se convierte en página.
- **Lint** — al cerrar un sprint o a petición: barrido en busca de contradicciones, afirmaciones
  que el repo ya desmintió, páginas huérfanas y referencias ausentes.

Reglas de escritura: **una nota, un hecho**; si no cabe en una pantalla, probablemente son dos. Y
antes de tocar un área, lee su mapa: dice qué documentos mandan y qué trampas hay puestas.

## Mapas por área

| Mapa | Cubre |
|---|---|
| [[arquitectura]] | Front controller, `src/`, el mini-app `admin/`, tablas globales |
| [[design-system]] | Tokens, capas CSS, gates, baselines, el laboratorio |
| [[pdc]] | Plan de Compras v2: SPA en `pdc-app/` + servicios PHP |
| [[lps-dominio]] | Programación general, intermedia y semanal; estados; cajón contextual |
| [[rbac-y-rutas]] | Roles, capacidades, rutas protegidas, sesión |
| [[entorno-y-despliegue]] | Docker, puerta de servicio, worktrees, SiteGround |
| [[qa-y-gates]] | Suites de prueba, rojos preexistentes, evidencia |

Además: **[[estado|Estado de los goals]]** (qué goal está abierto, cerrado o absorbido) y
**[[log]]** (bitácora cronológica de lo que se ha ingerido y verificado).

## Catálogo

Decisiones, trampas y referencias generadas desde el frontmatter de cada página (`tipo`,
`resumen`, `areas`, `fecha`). La base trae las tres vistas seleccionables — no hace falta un
embebido por tabla.

![[paginas.base]]

## Contratos del repo (no viven aquí)

[[AGENTS]] es el contrato autoritativo · [[CLAUDE]] orienta al asistente · [[DESIGN]] es el
contrato de consumo de UI · [[GLOSARIO]] fija el vocabulario · [[ROADMAP]] y [[CHANGELOG]] cuentan
hacia dónde va y por dónde pasó.
