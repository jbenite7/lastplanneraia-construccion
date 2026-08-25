---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-25
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs.md
resumen: "Cómo verificar contra el código el estado de los 127 planes y specs: validar la señal en muestra, aplicarla a 33, y los 92 restantes por lotes con evidencia citada"
---

# Verificar el estado real de los 127 planes y specs

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`.
> **Este plan SÍ es repartible entre subagentes** —los lotes de la Tarea 3 son independientes y
> ninguno toca código de producto—, pero con una condición que no es negociable: **un subagente
> devuelve la evidencia, no escribe el `estado`.** El porqué está en la Tarea 0.

**Spec:** [[docs/superpowers/specs/2026-08-25-estado-real-de-planes-y-specs-design]]
**Depende de:** nada. El frente anterior quedó publicado en `ca608402`.
**No compite por el contenedor compartido:** todo es lectura de `.md` y de código. Es trabajo
apto para una sesión que corra en paralelo a otra que esté publicando.

**Goal:** que `estado:` en `docs/superpowers/` sea una afirmación de alguien y no el valor por
defecto de un backfill.

---

## Tarea 0 — Validar la señal antes de confiarle 33 documentos

La señal «existe `goals/<slug>/goal.md` con `## Cierre`» cubre 33 de 127. **Está sin validar**:
que el slug coincida no prueba que el alcance coincida. Un plan puede llamarse igual que un goal
y abarcar más.

- [ ] Tomar **5 de los 33 al azar** y verificar a mano, contra el código, si el trabajo que
      describe el documento está de verdad hecho
- [ ] Si los 5 aciertan → la señal sirve para **preseleccionar**, y la Tarea 1 sigue
- [ ] Si **uno solo** falla → la señal queda degradada a indicio y los 33 pasan al lote de la
      Tarea 3. **No se ajusta la señal para que acierte:** eso es elegir el criterio por su
      resultado

**Por qué esta tarea va primera y no se salta.** Es el frente entero en miniatura: la tentación
es aplicar el cruce a los 33 y ahorrarse la mitad del trabajo. Sin validar, eso escribe 33
afirmaciones fuertes apoyadas en una coincidencia de nombres.

## Tarea 1 — Los 33 con goal cerrado

Solo si la Tarea 0 salió limpia.

- [ ] Para cada uno, escribir `estado: cerrado` **y añadir al documento la cita concreta**: qué
      goal lo cierra y con qué evidencia. Un `cerrado` sin cita reproduce el problema con el
      valor contrario
- [ ] Los **2 con goal abierto** pasan a `vigente` **deliberado**, anotando por qué siguen vivos

## Tarea 2 — Fijar el criterio de evidencia, por escrito

Antes de tocar los 92. Qué cuenta como prueba de que un documento está cerrado:

- [ ] **Vale:** el código o el archivo que el documento manda crear existe y hace lo que dice ·
      su gate está en verde y se puede citar · el CHANGELOG registra su liberación con fecha
- [ ] **No vale:** casillas marcadas · antigüedad · que «suene a hecho» · que otra sesión lo
      diera por cerrado sin cita
- [ ] Escribir el criterio en el propio plan antes de aplicarlo, para que los lotes no deriven

## Tarea 3 — Los 92 sin señal, por lotes

Repartir por `areas` del frontmatter, no por fecha: un lote de un área se verifica con el mismo
contexto cargado.

- [ ] Lote por área, empezando por las de menos documentos para calibrar el ritmo
- [ ] Cada documento acaba en `cerrado` (con cita), `vigente` (con motivo) o `derogada` (con la
      línea que dice qué dejó de ser cierto)
- [ ] **Los que no se puedan resolver se declaran así**, en una lista al final de este plan. Un
      «no pude verificarlo» escrito vale más que un `vigente` mudo

## Tarea 4 — Cierre

- [ ] `npm run test:wiki` sin hallazgos
- [ ] Regenerar `node scripts/wiki-registro.mjs --escribir`
- [ ] `ingest` en [[log]] con el reparto final y las señales que fallaron
- [ ] `CHANGELOG.md` y [[TASKS]] en el mismo turno
- [ ] Publicar por el gate de `AGENTS.md` §Publicación. **Si `publicar.sh` deniega por el
      contenedor compartido**, aplicar la comprobación de
      [[memoria/trampas/publicar-sh-choca-con-dos-worktrees-verificando]] en vez de reapuntarlo:
      con un frente 100 % markdown el diff en lo que el PHP lee sale vacío y el verde vale

---

## Condición de hecho

Ningún documento de `docs/superpowers/` en `estado: vigente` sin que ese valor sea deliberado;
cada `cerrado` con su evidencia citada dentro del documento; los irresolubles listados como
tales; y `npm run test:wiki` sin hallazgos.
