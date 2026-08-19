<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t1

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Tanda 1 — Esquema y herramientas (base de todo)
Sha verificado: 0de2f902 (npm run test:wiki → RC=0, 51 tests, «Sin hallazgos. 145 páginas de wiki y 0 de 383 fuentes declaradas.»)
Presupuesto: ?

## Objetivo
Dejar lista la base del esquema v2 de la wiki: el manual reescrito, el lint capaz de validar el
esquema nuevo, un backfill de frontmatter que sabe qué escribiría, y plantillas por tipo. La
herramienta queda lista; este frente NO la usa sobre las fuentes (eso es la Tanda 2).

## Condición de hecho
`npm run test:wiki` en verde **con la wiki actual intacta** (retrocompatible antes de tocar
ninguna fuente) y `node scripts/wiki-frontmatter.mjs --dry-run` imprimiendo el censo completo sin
escribir nada.
Verificación: npm run test:wiki

## Posture
- No ejecutar el backfill: ninguna fuente de `docs/`, `goals/` ni de la raíz gana frontmatter aquí.
- No editar páginas de `memoria/` existentes: el lint v2 tiene que pasar sobre las 145 tal cual.
- No tocar `.obsidian/` (capa visual = Tanda 4) ni `memoria/index.md` (Tanda 4).
- Sin dependencias nuevas de npm.
- No relajar ninguna comprobación que hoy exista para que pase algo nuevo.

## Leer primero
- docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md
- docs/superpowers/plans/2026-08-18-wiki-v2-visual.md (solo Tanda 1)
- docs/wiki-operacion.md
- scripts/wiki-lint.mjs, scripts/wiki-veracidad.mjs
- AGENTS.md, CLAUDE.md

## Archivos declarados
docs/wiki-operacion.md,scripts/wiki-*.mjs,tests/wiki/**,memoria/templates/**

## Contención
Medido el 2026-08-19 sobre `main`: ninguna otra sesión declara estos globs en `.claude/sesiones.md`.
Último commit que toca `scripts/wiki-*.mjs` o `docs/wiki-operacion.md`: 613decb2 (2026-08-18).

## Cadena de herramientas
- `npm run test:wiki` — la condición de hecho, tal cual la declara el plan.
- `node --test tests/wiki/*.test.mjs` — pruebas del módulo, sin el lint, para iterar rápido.

## Publicaciones

- **2026-08-19 · `f705e549` → `origin/main`.** Un solo push, con `bash scripts/publicar.sh`.
  Verificación previa sobre ese mismo sha, los tres gates del script en verde:

  ```
  Verificando sobre f705e549…
    ✔ design-system:static               RC=0
    ✔ contrato piloto PG                 RC=0
    ✔ wiki (lint + veracidad)            RC=0
  Publicando…
     720b27b9..f705e549  HEAD -> main
  ```

  Confirmado con `git fetch origin`: `git rev-parse origin/main` = `f705e549`, el sha medido.

  Para verificar hubo que apuntar el contenedor compartido a este worktree
  (`LPS_CODE_ROOT="$(pwd)" docker compose up -d app`), porque `publicar.sh` deniega cuando `app`
  monta otro árbol. **Devuelto a la raíz al terminar**, comprobado por `docker inspect`. Las otras
  sesiones vivas durante esa ventana no podían recibir un verde falso: el mismo invariante las
  habría denegado a ellas.

  El worktree nacía sin `.env` (está en `.gitignore`); se enlazó al de la raíz, no se copió.

## Cierre

**Fase 1 · Tanda 1 cerrada el 2026-08-19 sobre `f705e549`.** Los cuatro puntos del plan, hechos:

1. `docs/wiki-operacion.md` reescrito al esquema v2 — tres capas con el campo `capa`, diecisiete
   `tipo`, ocho `tags`, y la regla nueva de plugins de comunidad («amplifican, no sostienen»).
2. `scripts/wiki-lint.mjs` v2 — lintea las tres capas con reglas distintas: a las fuentes solo el
   frontmatter, nunca el cuerpo. Bandera `--estricto` para cuando la Tanda 2 termine.
3. `scripts/wiki-frontmatter.mjs` + `.reglas.mjs` — backfill idempotente, ensayo por defecto,
   `--escribir` explícito y `--solo` para ir por tandas.
4. Cinco moldes en `memoria/templates/` (`decision`, `trampa`, `concepto`, `spec`, `plan`).

Condición de hecho, cumplida: `npm run test:wiki` → RC=0, 55 tests, «Sin hallazgos. 151 páginas de
wiki y 0 de 391 fuentes declaradas», con la wiki intacta y **cero fuentes tocadas**; y
`node scripts/wiki-frontmatter.mjs` imprimiendo el censo de las 391 sin escribir nada.

### Lo que este frente descubrió y la Tanda 2 necesita

- **Censo:** 391 fuentes. Por tipo deducido: 92 `plan` · 79 `spec` · 74 `guia` · 64 `goal-doc` ·
  35 `reporte` · 29 `evidencia` · 7 `contrato` · 3 `biblia`.
- **Lo que las reglas NO pueden deducir:** 4 sin fecha · **17 sin resumen** · 29 sin área.
  *(Corregido el 2026-08-19: esta línea decía «217 sin resumen» y era un fallo de la deducción, no
  del repositorio — ver la addenda al final.)*
- **`DESIGN.md` ya tenía frontmatter, y es de otra herramienta** (el linter Stitch y el panel live
  leen ahí los tokens). Por eso una fuente entra al lint solo si declara `capa:` —tener un bloque
  `---` no es declararse parte de este esquema— y por eso el backfill **fusiona** en vez de
  reescribir. Hay una prueba que fija ese comportamiento.

### Decisión que estaba abierta, y su addenda — resuelta el 2026-08-19

**`resumen` es obligatorio en fuentes.** Decisión del usuario, tomada tras medir el coste real.

Al cerrar este frente la pregunta quedó abierta con una cifra equivocada: «217 sin resumen», y con
ella la Tanda 2 parecía costar 217 textos escritos a mano. **Esos 217 eran un fallo de mi
deducción, no del repositorio.** Los planes de `writing-plans` abren con una cita para agentes
(`> **For agentic workers:**`) y mi regla se paraba justo ahí — una línea antes del `**Goal:**`
que era exactamente el resumen que buscaba. 73 de los 92 planes estaban en ese caso.

Con una cascada de cuatro respaldos —párrafo · `**Goal:**` · párrafo bajo `## Objetivo` · el
propio título— el reparto real sobre las 391 fuentes es:

```
De dónde sale el resumen:
   169  parrafo
    84  titulo
    77  etiqueta
    44  seccion
    17  ninguno
```

**290 con prosa de verdad, 84 con su título, 17 a mano.** De esos 17, catorce son `goal.md` y
`facts.md` de otros frentes, que sus dueños rellenan en su propio cierre.

La lección, que vale más que el número: **antes de aceptar que algo es caro, comprueba si lo caro
es la medida.** Estuve a punto de dejar un campo opcional para siempre por un coste que no
existía.

### Residuo

`decisiones/wiki-t1-coordinadora.md` quedó vacío: lo creó `cas-frente.sh` al deducir mal el rol de
esta sesión (sin MCP en su cascada). La cola real es `decisiones/wiki-t1-ejecutor.md`. No se borra
desde aquí: borrar es de la lista que bloquea.

### Nota de infraestructura

El módulo CAS no existe en la versión instalada del plugin (`loop-engineering/0.3.0`); solo en el
caché de `0.2.0`, y `.claude/cas-root` apunta a una ruta que ya no está. Los gates de rutas,
presupuesto y push **no estuvieron activos** durante este frente: el cumplimiento fue disciplina,
no mecánica. El frente se declaró con el `cas-frente.sh` de 0.2.0 usando `--sin-plan`, porque ese
script solo reconoce encabezados `Task N`/`Fase N` y el plan usaba `Tanda N` — lo que otra sesión
corrigió después en `7b7c2b9d`.
