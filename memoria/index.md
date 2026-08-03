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

## Decisiones

| Página | De qué va | Fecha |
|---|---|---|
| [[compras-migrado-shell-sidebar]] | Compras ya usa el shell sidebar, revirtiendo la exclusión que sigue escrita en el goal | 2026-07-29 |
| [[dev-door-acceso-local]] | La sesión local se abre siempre por `/dev/entrar`, nunca tecleando credenciales | 2026-07-30 |
| [[goal-dark-mode-todos-modulos]] | Decisiones vinculantes F0–F6: retirada del tema `linen`, exclusión de AdminLTE | 2026-07-29 |
| [[no-enriquecer-daporto-para-medir]] | No enriquecer el proyecto real 73 para medir; usar uno sintético | 2026-07-30 |
| [[powerbi-indicadores]] | `/indicadores` dejó Data Studio por Power BI publish-to-web, y sus límites | 2026-07-23 |
| [[sidebar-default-collapsed]] | El shell sidebar se queda colapsado por defecto | 2026-07-24 |

## Trampas

| Página | Qué muerde | Fecha |
|---|---|---|
| [[admin-adminlte-adaptador]] | `admin/` tiene entrypoint CSS propio; para ganar a un `!important` del vendor hay que capar en `@layer reset` | 2026-07-29 |
| [[aislar-stack-docker-por-worktree]] | Receta para dar a un worktree stack Docker y base propios sin recrear el ajeno | 2026-07-29 |
| [[audit-ve-color-en-comentarios]] | El audit cuenta hex y `rgba()` escritos dentro de comentarios CSS | 2026-07-29 |
| [[autoria-por-coincidencia-de-hora]] | La hora de última actividad de una sesión no prueba que escribiera un archivo | 2026-08-02 |
| [[branch-preexisting-red-gates]] | Rojos preexistentes tolerados de los gates del design system, y cómo validarlos en worktrees | 2026-07-23 |
| [[browser-qa-pitfalls]] | La sesión cae a los 60–90 s en el panel, y la vista semanal auto-dispara mutaciones al cargar | 2026-07-29 |
| [[css-layer-cascade]] | `styles.css` vive en `module.components`; para ganarle en `!important` hace falta capa superior | 2026-07-22 |
| [[dos-stacks-docker]] | Dos stacks con MySQL propio: conectarse al equivocado escribe en la base de otra sesión | 2026-07-28 |
| [[drawer-en-handsontable-module]] | La geometría del Cajón Contextual vive en `handsontable-module.css`, que `core.css` no importa | 2026-07-29 |
| [[hot-container-height-ownership]] | La altura de `#hot-container` la resuelve JS; `calc(100vh - Npx)` sobre él es siempre falso | 2026-07-25 |
| [[lab-desktop-layout-suite]] | La suite desktop-layout corre fuera del carril `runtime` | 2026-07-27 |
| [[lab-header-offset-medido]] | El offset del header del lab se mide con `ResizeObserver`; el `calc()` es solo respaldo | 2026-07-27 |
| [[lab-sticky-body-overflow]] | `.ds-lab` necesita `overflow: visible` en ambos ejes, y borrar la declaración no basta | 2026-07-27 |
| [[manifiesto-ds-exige-golden]] | Un manifiesto de módulo no se puede crear «en seco»: exige un golden real con `sha256` que case | 2026-07-29 |
| [[navbar-css-consumidor-vivo]] | Antes de borrar una hoja CSS hay que grepear también el JS de runtime | 2026-07-25 |
| [[path-with-space-esm-guard-noop]] | La ruta del repo tiene un espacio: el guard `file://${argv[1]}` es no-op | 2026-07-21 |
| [[pdc-e2e-sandbox]] | Los e2e del PDC v2 corren contra el proyecto sacrificable 990100 | 2026-07-28 |
| [[servir-worktree-stack-efimero]] | Para correr e2e sobre un worktree hace falta identidad compose, no `docker run` | 2026-07-30 |
| [[siteground-sin-tunel-ssh]] | SiteGround prohíbe el reenvío de puertos: no hay forma de ver producción como local | 2026-07-30 |
| [[stack-principal-migraciones-pdc-pendientes]] | Replayar las migraciones del PDC exige todo el DDL antes que los seeds | 2026-07-28 |
| [[suite-php-rojos-preexistentes]] | Rojos preexistentes de la suite PHP, y las dos trampas al medirlos en macOS | 2026-07-29 |
| [[tests-browser-allowlist]] | `tests/browser/` está gitignorado con lista blanca: un test nuevo no se commitea solo | 2026-07-28 |
| [[visual-baselines-estado-real]] | Las baselines visuales del lab están rojas: mide el delta antes de culpar a tu cambio | 2026-07-28 |
| [[worktree-compartido-arrastra-commits]] | Dos sesiones en el mismo worktree se arrastran los cambios sin commitear | 2026-07-28 |

## Referencias

| Página | Apunta a | Fecha |
|---|---|---|
| [[artefacto-estado-dark-mode]] | Dashboard vivo del goal de dark mode; se regenera desde el audit, no a mano | 2026-07-29 |
| [[produccion-deploy]] | Cómo se despliega producción en SiteGround y cuánto va por detrás de `main` | 2026-07-23 |

## Contratos del repo (no viven aquí)

[[AGENTS]] es el contrato autoritativo · [[CLAUDE]] orienta al asistente · [[DESIGN]] es el
contrato de consumo de UI · [[GLOSARIO]] fija el vocabulario · [[ROADMAP]] y [[CHANGELOG]] cuentan
hacia dónde va y por dónde pasó.
