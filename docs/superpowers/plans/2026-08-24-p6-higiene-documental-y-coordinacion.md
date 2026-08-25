---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-24-p6-higiene-documental-y-coordinacion.md
resumen: "P6 · La higiene que corre en paralelo a todo: ordenar el CHANGELOG, escribir los cierres que faltan, versionar el estado de coordinación, fusionar los tres archivos de instrucciones y arreglar el proxy de la alarma de veracidad"
---

# P6 · Higiene documental y de coordinación

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans` y la skill `llm-wiki`
> al tocar cualquiera de los cinco archivos de la raíz. **Este plan sí es repartible entre
> subagentes:** las tareas son independientes y ninguna toca código de producto.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]
**Depende de:** nada. **Corre en paralelo a P1–P5** — no compite por el contenedor compartido, así
que es el trabajo correcto para una sesión que quede libre mientras otra publica.

**Goal:** que el estado del repositorio se pueda leer sin reconstruirlo, y que las tres fuentes de
verdad dejen de contradecirse.

---

## Tarea 1 — Ordenar `CHANGELOG.md`

No está en orden cronológico inverso: `[1.1.1]` y `[1.1.0]` aparecen **antes** que `[Sin publicar]`
y que `[1.2.0]`. Detectado el 2026-08-19 y **no corregido en el mismo turno a propósito**:
reordenar 400 líneas de historia ajena a mano arriesga perder contenido.

- [ ] Reordenar con verificación de que **ninguna línea se pierde**: contar entradas antes y
      después, y diffear el conjunto de líneas ordenado

## Tarea 2 — Escribir los cierres que faltan

La regla de lectura cuenta como abierto todo goal sin encabezado `## Cierre`. Hay goals con el
trabajo hecho y en producción contados como abiertos.

- [ ] `pdc-tanda2-plan-verdad` y `adopcion-logo-construccion` — **es escribir el cierre, no
      re-ejecutar**
- [ ] Barrer el resto de `goals/` con el mismo criterio, verificando **contra el código**
- [ ] Decidir cuáles de los **8 `goal.md` que son andamiajes sin objetivo escrito** siguen vivos

**Recordatorio con evidencia:** de 435 casillas repartidas en 17 planes hay **0 marcadas**, incluidos
planes cuyo trabajo está en producción. **Las casillas no miden nada**: para saber si algo está
hecho se verifica contra el código.

## Tarea 3 — Versionar el estado de coordinación

`.claude/vistos/` está en `.gitignore:219` y `decisiones/gobierno-relato-de-autorizaciones.md` está
sin commitear, así que **ninguna sesión que trabaje en un worktree los ve**.

- [ ] Versionar lo que debe verse desde cualquier worktree
- [ ] Depurar `.claude/sesiones.md` moviendo las `terminada` a [[decisiones/sesiones-historial]]

**Precedente medido el 2026-08-11:** un archivo de estado compartido sin versionar se llevó doce
hallazgos sin diff y sin rastro.

## Tarea 4 — Registrar las dos reglas nuevas de coordinación

Salen del censo del 2026-08-24 y **están medidas, no supuestas**:

- [ ] **Un censo de sesiones lo hace una sola sesión.** Tres consolidadoras barrieron a la vez, dos
      desde el mismo worktree; cada sesión suspendida respondió lo mismo tres veces en tres
      formatos. Es la regla de pluma única de las specs, extendida al censo
- [ ] **Las sesiones se cuentan por repositorio, no por nombre de worktree.** El criterio correcto
      es `git -C <worktree> rev-parse --show-toplevel`. Una sesión de otro repo entró en el censo
      solo porque su worktree se llamaba `lps-aia-panels-deployment`

## Tarea 5 — Fusionar `AGENTS.md` / `GEMINI.md` / `CLAUDE.md`

Su contenido se solapa con lo que ahora vive en [[README]] y [[ROADMAP]]. En el bootstrap solo se
enlazaron, no se tocaron.

- [ ] **`AGENTS.md` gana donde haya conflicto** — es el contrato autoritativo
- [ ] Que cada regla viva en **un** sitio: una regla en dos sitios se separa en el primer ajuste

## Tarea 6 — Rediseñar el proxy de la alarma de veracidad

Hoy cuenta commits y **no sabe de qué habla la wiki**: pesa igual un commit en un área con quince
páginas que uno en un área sin ninguna.

- [ ] Afinar por área — las 13 áreas ya tienen mapa y las fuentes declaran su `areas`
- [ ] Asumir que es **cambiar el proxy entero, no recortarlo**: los tres descuentos del 2026-08-19
      ya exprimieron el atajo

## Tarea 7 — Los sueltos

- [ ] **Enchufar `--estricto` a `npm run test:wiki`**. Es decisión de contrato: a partir de ahí toda
      fuente nueva nace con frontmatter o el gate se pone rojo. **El hueco ya se midió**: una fuente
      entró sin declarar por un merge y el gate no lo detectó
- [ ] **Grupos de color del grafo** (`.obsidian/graph.json`) — lo único que quedó de la Fase 0b
- [ ] **`visor-gantt` apunta al disco Crucial X6** — roto desde la mudanza del 2026-08-18
- [ ] **Plan espacio SiteGround** — tareas 1–5 de
      `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`

## Tarea 8 — Dos propuestas para Felipe, que no se aplican

- [ ] **Hook `task-completed-verify.sh`**: corre `composer test` en el host, donde composer no existe
      (repo Docker-only) → rojo falso en toda tarea sin código. Es `~/.claude`: **se propone el fix,
      no se aplica**
- [ ] **Verificación de tests en contenedor como config por proyecto.** La vía Docker salió del gate
      global el 2026-08-19; este repo es 100 % dockerizado. Afecta config global, no solo este repo

**`~/.claude` no se toca sin el visto de Felipe en el chat, sesión por sesión, y ese visto no se
transmite por otro agente.**

---

## Condición de hecho

`CHANGELOG.md` en orden con recuento de entradas intacto; ningún goal ejecutado contado como
abierto; el estado de coordinación visible desde cualquier worktree; y las dos reglas nuevas
escritas en [[docs/coordinacion-sesiones]].

---

## Estado verificado — sigue vigente

Verificado contra el código el 2026-08-25. **`estado: vigente` aquí significa que el trabajo sigue abierto** — es una afirmación deliberada, no el valor por defecto del backfill.

**Qué falta:** ocho tareas sin seccion de Cierre ni resultado reportado dentro del documento: es lista de pendientes, no bitacora

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
