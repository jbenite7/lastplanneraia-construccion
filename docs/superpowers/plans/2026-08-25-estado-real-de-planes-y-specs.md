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

---

## Resultado de la Tarea 0 — la señal quedó degradada

**Ejecutada el 2026-08-25.** Muestra de 5 de los 33, elegida de forma reproducible (orden por
`md5(ruta + "20260825")`, no por reloj: cualquiera puede repetir la selección exacta).

| # | Documento | Veredicto | Contra qué se comprobó |
|---|---|---|---|
| 1 | `plans/2026-08-20-deuda-ci-frente-2` | **acierta** | `ci.yml:104-113` y `:192-201` (buildx + `cache-from/to: type=gha`), `docker-compose.yml:7` (`image: lps-aia-app-ci:local`) |
| 2 | `plans/2026-08-06-adopcion-logo-construccion` | **acierta** | los 5 archivos que manda crear existen; `tokens.css:644` apunta a `glyph-mono.svg`; el SVG legado no lo cita ningún archivo |
| 3 | `plans/2026-08-04-biblia-t5-lectura` | **FALLA** | ver abajo |
| 4 | `specs/2026-08-24-pendientes-frente-tablas-design` | **acierta** | `ControlTowerService.php:2866-2872` devuelve `critical`/`brand-*`; cero `status-*` de relleno en el servicio y en `bi-spa.js`; `tokens.css:139` (`--ds-font-icon`) |
| 5 | `specs/2026-08-11-semana-fija-visual-design` | **acierta** | `programacion-intermedia.visual.mjs:92-99`: `SEMANA_DEL_GOLDEN` + `POST /context/week` comprobando la respuesta |

### El caso que falla, y por qué cuenta como fallo

`biblia-t5-lectura` tiene goal con `## Cierre` marcado **HECHO**, y su Task 3 —«Las pruebas
ejecutables de los críticos», que manda crear `e2e/tests/biblia/lectura.spec.mjs`— **no se
ejecutó**. El archivo no existe, mientras sus cuatro hermanos de otras tandas sí
(`cascada-lps`, `pdc`, `soporte`, `transversal`).

No es un descuido menor por tres razones:

1. El plan **preveía** que la prueba pudiera no escribirse —si no hay cuenta seed con rol
   restringido en `DEV_DOOR_USERS`— pero entonces exigía «documenta esa limitación en el spec y
   en el documento de escenarios». `docs/flujos/lectura-bi.md` lista cuatro pendientes y
   **ninguno es este**. La salida autorizada no se tomó.
2. El goal justifica la ausencia con otra razón: «este goal no exigía prueba ejecutable propia».
   El plan sí la exige: es una Task entera.
3. El plan manda además dos documentos (`lectura-indicadores.md`, `lectura-torre-de-control.md`)
   y se entregó uno fusionado (`lectura-bi.md`). Eso solo es una decisión de alcance razonable
   y no cuenta como fallo; lo que cuenta es la Task 3.

**Es exactamente el riesgo que la Tarea 0 nombraba:** el slug coincide y el alcance no. El goal
cerró un alcance más pequeño que el del plan, y el cruce por slug no puede ver esa diferencia.

### Consecuencia, aplicada sin ablandarla

Regla del plan: «si **uno solo** falla → la señal queda degradada a indicio y los 33 pasan al
lote de la Tarea 3. **No se ajusta la señal para que acierte.**»

- La señal **sigue sirviendo para preseleccionar** —decide a quién mirar primero— y **deja de
  servir para sellar**.
- Los 33 se suman a los 92. **El lote de verificación manual pasa de 92 a 127.**
- La tasa medida (4/5) no se usa como probabilidad para sellar los otros 28 sin mirarlos: una
  señal que acierta el 80 % aplicada a 33 documentos escribe unas 6 afirmaciones falsas, y
  `cerrado` es afirmación fuerte.

### Hallazgo lateral: `areas:` tiene la misma huella del backfill

Al repartir los lotes salió que **82 de los 127 declaran `areas: [proceso]`**. Es el mismo patrón
que `tipo: guia` en [[memoria/trampas/el-tipo-de-una-fuente-lo-dedujo-un-script]]: un cajón de
sastre que parece clasificación. Por eso el reparto de la Tarea 3 **no usa `areas`** —habría dado
un lote de 82 y veintitantos de uno— sino la familia del slug, que sí agrupa trabajo con contexto
compartido.

---

## Resultado de la Tarea 2 — el criterio de evidencia, fijado antes de aplicarlo

Escrito **antes** de tocar el primer documento, para que los lotes no deriven. Cada documento
acaba en uno de tres estados, y ninguno se escribe sin la línea que lo sostiene.

### `cerrado` — exige una de estas tres, citada dentro del propio documento

1. **El artefacto existe y hace lo que el documento dice.** El archivo, la ruta, el token o la
   función que el documento manda crear está en el árbol, y se cita `archivo:línea` leída en esta
   sesión. No basta que el archivo exista: tiene que contener lo que el documento prometía.
2. **Su gate está en verde y se puede citar** — nombre del gate y salida.
3. **`CHANGELOG.md` registra su liberación**, con la entrada concreta.

Y una condición que la Tarea 0 acaba de hacer obligatoria: **si el documento tiene varias tareas,
se comprueba que no quede una entera sin ejecutar.** El caso `biblia-t5-lectura` cerró con una
Task completa sin hacer y nadie lo vio porque el goal decía HECHO.

### `vigente` — deja de ser el valor por defecto y pasa a ser una afirmación

Se escribe solo cuando el trabajo **sigue vivo**, con el motivo en el documento: qué falta y
dónde se ve que falta. Un `vigente` sin motivo es el defecto que este frente vino a corregir, con
el valor contrario.

### `derogada` — la decisión que contiene dejó de ser cierta

Con la línea que dice **qué** dejó de ser cierto y **qué lo sustituyó**. No se borra el documento:
el repo conserva las decisiones muertas con su lápida.

### `sin resolver` — la casilla honesta

Cuando la verificación no alcanza —el artefacto es ambiguo, el alcance no se deja acotar, haría
falta correr algo que este frente no puede correr— **se declara así en la lista del final de este
plan**, con el motivo. Un «no pude verificarlo» escrito vale más que un `vigente` mudo: el
primero es un dato, el segundo es ruido.

### Lo que NO cuenta como evidencia, y ya causó daño

- **Casillas `- [ ]` marcadas o sin marcar.** `AGENTS.md` §Verificación: 2.127 casillas en 71
  planes, 162 marcadas, planes cerrados y en producción sin una sola marca.
- **La antigüedad del documento.** Un plan de julio puede seguir abierto.
- **Que «suene a hecho»**, o que el documento esté escrito en pasado.
- **Que otra sesión, otro goal o el propio documento lo diera por cerrado sin cita.** Es
  precisamente lo que falló en `biblia-t5-lectura`.
- **La coincidencia de slug con un goal cerrado.** Degradada a indicio por la Tarea 0.
