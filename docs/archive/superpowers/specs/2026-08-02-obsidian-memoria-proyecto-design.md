---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-02
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/specs/2026-08-02-obsidian-memoria-proyecto-design.md
resumen: El conocimiento del proyecto vive en tres sitios que no se comunican:
---

# Wiki de proyecto en Obsidian (patrón LLM Wiki)

**Fecha:** 2026-08-02
**Estado:** aprobado
**Metodología:** [LLM Wiki, Andrej Karpathy](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f)
**Alcance:** documentación y memoria. No toca código PHP/JS/CSS ni schema de base de datos.

> **Aviso.** Este archivo reemplazó a una versión previa escrita el mismo día, junto con un primer
> tramo de `memoria/` (`Inicio.md` y dos páginas de `decisiones/`). Aquel trabajo no estaba en git
> y la versión anterior del spec se perdió al sobrescribirla; no hay historial que consultar. Se
> asume trabajo previo de esta misma sesión, en un tramo que no quedó registrado — no se pudo
> identificar otra autoría, y atribuirlo a otra sesión por coincidencia de hora resultó ser un
> error (ver `memoria/trampas/autoria-por-coincidencia-de-hora.md`).
>
> De aquel primer tramo sobreviven `memoria/index.md`, fusionado desde `Inicio.md`, y el campo
> `origen:` del frontmatter, que se adoptó para las 31 páginas migradas. El diseño de abajo es el
> acordado después, ya con el patrón LLM Wiki.

## Problema

El conocimiento del proyecto vive en tres sitios que no se comunican:

1. **Documentación del repo** — 9 `.md` en la raíz, 27 en `docs/` más subcarpetas
   (`design-system/`, `superpowers/`, `brand/`), y 16 goals en `goals/`. Ningún índice los
   relaciona. No existe registro de decisiones a nivel de repo: solo
   `docs/design-system/decisions.md`, acotado al design system.
2. **Memoria privada del asistente** — 30 notas del proyecto en
   `~/.claude/projects/-Volumes-Crucial-X6-Developer-lps-aia/memory/`: stacks Docker duplicados,
   baselines visuales rojas, la puerta de servicio `/dev/entrar`, trampas del audit CSS. Las
   escribe Claude, las lee solo Claude, no están versionadas y el equipo no las ve.
3. **Lo que no está escrito** — el porqué de las decisiones, que ni el código ni el historial de
   git registran.

El asistente redescubre lo mismo en cada sesión, y lo que aprende se pierde al cerrarla.

## Objetivo

Una wiki de proyecto en Obsidian, versionada dentro del repo, que el asistente construye y
mantiene de forma incremental. Sustituye el redescubrimiento por un artefacto persistente con
referencias cruzadas ya resueltas y contradicciones ya señaladas.

## Arquitectura: tres capas

| Capa | En este repo | Regla |
|---|---|---|
| **Raw sources** | `docs/`, `goals/`, `GLOSARIO.md`, `AGENTS.md`, `README.md`, el código | Se leen; **su contenido no se edita desde la wiki**. Son la fuente de verdad. |
| **Wiki** | `memoria/` | Artefacto generado. Lo escribe solo el asistente, nunca a mano. |
| **Schema** | Sección «Memoria del proyecto» en `CLAUDE.md` | Explica al asistente la estructura, las convenciones y las tres operaciones. |

Precedencia ante conflictos: **código > `AGENTS.md` > `memoria/`**. Si una nota contradice al
repo, gana el repo y la nota se marca `estado: derogada` en vez de borrarse: saber que algo dejó
de ser cierto es información.

### Excepción a la inmutabilidad de las fuentes (2026-08-02)

Con la wiki ya montada, el grafo mostraba `goals/` como 99 islas: los `goal.md` colgaban solo de
`memoria/goals/estado.md` y sus `plan.md`, `facts.md`, `briefs/` y `reports/` no colgaban de
nada. Enlazarlos desde la wiki habría producido un índice de contenidos, no una relación.

Por decisión explícita del usuario, cada `goals/<slug>/goal.md` recibe al final una sección
«Archivos de este goal» que enlaza a sus hermanos **versionados** —solo los que viajan en git, para
no dejar enlaces rotos en un clon— y a `estado.md`. Es navegación añadida al pie: no altera el
contenido del goal.

Resultado medido: `goals/` pasa de 16 a 97 nodos conectados de 99; el grafo completo, de 75 a 156
de 265. Los 2 restantes son los archivos que deliberadamente no viajan en git.

`docs/` no se toca: sus 112 documentos siguen conectados solo cuando un mapa de la wiki los cita.

## El vault es la raíz del repo

`.obsidian/` se coloca en la raíz y las notas nuevas se agrupan en `memoria/`.

Obsidian solo resuelve wikilinks dentro de su propio vault. Si el vault fuera `memoria/`, no
podría enlazar a `docs/pdc-v2.md` ni a `goals/*/goal.md`, y se perdería justo lo que lo hace
útil: grafo, backlinks y autocompletado. Con el vault en la raíz, `[[GLOSARIO]]` y
`[[docs/pdc-v2]]` funcionan de forma nativa y el grafo cubre la documentación existente **sin
modificar ningún archivo de `docs/`**, que es exactamente la capa 1 del patrón.

Alternativas descartadas:

- **Vault aislado en `memoria/`** — más ordenado en disco, pero los enlaces hacia `docs/` serían
  rutas markdown que Obsidian no indexa: sin grafo ni backlinks.
- **Vault en la raíz sembrando wikilinks dentro de `docs/`** — grafo más rico, pero modifica
  archivos que `AGENTS.md` y `CLAUDE.md` tratan como contrato, y rompe la inmutabilidad de la
  capa 1.

El ruido se controla con `userIgnoreFilters` en `.obsidian/app.json`, que excluye del grafo, la
búsqueda y el autocompletado: `vendor/`, `node_modules/`, `pdc-app/`, `public/pdc-app/`,
`docs/qa/`, la evidencia y las mediciones del design system, `tests/`, `e2e/`, `storage/`,
`.claude/` y `.superpowers/`.

## Estructura de `memoria/`

```
memoria/
  index.md          catálogo por categoría: enlace + resumen de una línea por página
  log.md            bitácora append-only: fecha · operación · asunto · páginas tocadas
  mapas/            un MOC por área, enlaza a las fuentes
    arquitectura.md          front controller, src/, admin/, tablas globales
    design-system.md         DS, tokens, gates, baselines, lab
    pdc.md                   Plan de Compras v2 (SPA + PHP)
    lps-dominio.md           PG/PI/PS, estados, cajón contextual
    rbac-y-rutas.md
    entorno-y-despliegue.md  Docker, dev door, SiteGround
    qa-y-gates.md            suites, rojos preexistentes, evidencia
  decisiones/       ADR ligero, una nota por decisión
  trampas/          lo migrado de ~/.claude, una nota por trampa
  referencias/      punteros a recursos externos (dashboards, servidores)
  goals/estado.md   índice vivo de los 16 goals
```

No se crea `dominio/` por ahora: `GLOSARIO.md` ya cubre el vocabulario y una carpeta vacía en el
índice promete algo que no existe. Se abrirá cuando haya un concepto que merezca página propia.

Se conservan carpetas temáticas en lugar de la wiki plana del gist: con ~40 notas desde el primer
día, el explorador de archivos de Obsidian sería inservible. El índice sigue siendo la puerta de
entrada.

Frontmatter con propiedades nativas, sin plugins de comunidad:

```yaml
---
tipo: decision | trampa | mapa | goal | concepto
estado: vigente | derogada | abierto | cerrado
fecha: 2026-08-02
areas: [design-system, docker]
fuente: memoria-claude | sesion | git
---
```

Sin Dataview ni Templater: el vault debe funcionar en cualquier máquina sin instalar nada.

## Las tres operaciones

### Ingest

Se dispara al cerrar una tarea o cuando aparece una fuente nueva. El asistente lee la fuente,
comenta el hallazgo, escribe o actualiza la página, actualiza `index.md`, revisa las páginas
relacionadas por si alguna quedó obsoleta, y anexa una línea a `log.md`.

### Query

Preguntas contra la wiki. El asistente busca las páginas pertinentes y responde citándolas. Si la
respuesta resultó valiosa y no estaba escrita, se promueve a página nueva.

### Lint

Se dispara al cerrar un sprint o a petición. Un subagente de bajo coste barre `memoria/` buscando
contradicciones entre páginas, afirmaciones que el repo ya desmintió, páginas huérfanas y
referencias cruzadas ausentes. Devuelve solo la lista de conflictos; el asistente los resuelve.
Deja línea en `log.md`.

No corre en cada ingesta: encarecería cada cierre de tarea sin aportar en proporción.

## Migración de la memoria privada

Las 30 notas del proyecto se reescriben en `memoria/trampas/`, salvo las que en realidad
registran una decisión —por ejemplo `lps-aia-goal-dark-mode-todos-modulos.md`, que fija acuerdos
vinculantes F0–F6—, que van a `memoria/decisiones/`. Los originales ya usan sintaxis `[[…]]` en
el cuerpo, así que los enlaces entre notas se conservan.

Cada archivo original queda reducido a un puntero a la nota del vault, y `MEMORY.md` pasa a ser un
aviso de que la memoria se mudó. Queda fuera una sola memoria, que no es del proyecto:
`mensajes-entre-sesiones-sin-mando.md`. En cambio `path-with-space-esm-guard-noop.md` **sí** se
migra: es tooling genérico, pero describe una trampa de este repo —la ruta contiene un espacio— y
otra nota la enlaza, así que dejarla fuera rompería el enlace. Total migrado: 31 páginas.

## Git

`memoria/` no está cubierta por ningún patrón de `.gitignore`, de modo que se versiona sin
cambios. Se añaden únicamente el estado personal de la ventana y las cachés:
`.obsidian/workspace.json`, `.obsidian/workspace-mobile.json`, `.obsidian/cache`,
`.obsidian/plugins/` y `.obsidian/themes/`.

Cuidado con dos patrones existentes:

- `MEMORY.md` está ignorado **en cualquier ruta**, por eso el índice de la wiki se llama
  `index.md` y no `MEMORY.md`.
- `goals/` usa lista blanca invertida (`.gitignore` líneas 56–125 y 270–274). Varios goals no
  están incluidos —`cierre-dark-mode-y-tablas`, `shell-layout-design-system`,
  `sidebar-todos-modulos`, `bi-control-tower-gemini`, `pdc-preparar-b1`—, así que los wikilinks
  hacia ellos funcionan en la máquina local y quedan rotos en un clon fresco.
  `memoria/goals-estado.md` los marca como «local, no versionado» en vez de aparentar que el
  enlace resuelve. Corregir la whitelist es una decisión aparte.

## Verificación

- **Enlaces:** extraer todo `[[…]]` de `memoria/**/*.md` y comprobar que el destino existe en el
  árbol. Cero enlaces rotos, salvo los marcados como locales.
- **Clon limpio:** clonar a un temporal y repetir la comprobación, para confirmar qué rompe la
  whitelist de `goals/`.
- **Obsidian real:** abrir el vault en la raíz y confirmar que el grafo conecta `memoria/` con
  `docs/` y `goals/`, y que `vendor/` y `node_modules/` no aparecen.
- **Cobertura:** contar las notas migradas frente a las 30 originales.
- **Índice:** toda página de `memoria/` aparece en `index.md`; ninguna entrada del índice apunta
  a un archivo inexistente.
- **No regresión:** ningún `.md` de `docs/` modificado; `git status` sin cambios ajenos.

No se toca código ejecutable, así que no aplican PHPStan, Playwright ni validación en navegador.

## Fuera de alcance

- Reorganizar o mover documentación existente de `docs/`.
- Plugins de comunidad, Obsidian Sync, publicación.
- Hooks o automatismos: las tres operaciones las dispara una persona o el cierre de una tarea.
- Corregir la whitelist de `goals/` en `.gitignore`.
