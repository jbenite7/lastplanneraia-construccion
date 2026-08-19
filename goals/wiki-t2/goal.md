<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t2

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 2 · Tanda 2 — Frontmatter a las fuentes (por lotes)
Sha verificado: ?
Presupuesto: ?

## Objetivo
Aplicar el backfill de frontmatter a la capa de fuentes, por lotes y con revisión entre uno y otro,
usando la herramienta que dejó lista la Fase 1. Al terminar, `wiki-lint.mjs --estricto` pasa sobre
todo el vault.

## Condición de hecho
`npm run test:wiki` verde tras cada lote, y al final `node scripts/wiki-lint.mjs --estricto` en
verde. `git diff` de cada lote muestra **solo bloques de frontmatter**: ningún cuerpo tocado.
Verificación: npm run test:wiki

## Posture
- **No tocar el cuerpo de ninguna fuente.** El backfill antepone o fusiona metadato; nada más.
- No reescribir un frontmatter ajeno: en `DESIGN.md` se fusiona, no se sustituye.
- No inventar valores. Lo que la cascada no deduzca queda vacío y se cuenta.
- No editar `memoria/` (es la Tanda 3) ni `.obsidian/` (Tanda 4).
- No cambiar las reglas de deducción para que un lote salga más redondo: si un lote destapa un
  fallo de regla, se arregla la regla y se vuelve a medir, no se maquilla el resultado.
- Sin dependencias nuevas.

## Leer primero
- docs/wiki-operacion.md (esquema v2, ya reescrito)
- goals/wiki-t1/goal.md § Cierre y su addenda — el censo y lo que hereda esta fase
- scripts/wiki-frontmatter.mjs y .reglas.mjs

## Archivos declarados
docs/**,goals/**,decisiones/**,*.md,database/**,docker/**

## Lotes
Orden del plan, cada uno con su verificación y su revisión muestral antes del siguiente:

1. Raíz (9 `.md`) + `docs/design-system/contracts/`
2. `docs/flujos/` + `docs/design-system/`
3. Resto de `docs/` (sin `docs/archive/`)
4. `goals/` + `decisiones/` + `database/` + `docker/`
5. `docs/archive/` (tag `archivo`)

## Contención
`docs/` y `goals/` los están tocando otras sesiones ahora mismo: entre las 00:30 y las 02:00 del
2026-08-19 entraron 14 commits ajenos a `origin/main` que crearon archivos bajo `goals/`. **Por eso
los lotes son cortos y se integra justo antes de publicar cada uno**, en vez de hacer una pasada
larga: un backfill de 412 archivos en un solo commit chocaría con todo lo que entre mientras corre.
El backfill es idempotente, así que un archivo ajeno que llegue tarde se recoge en el lote siguiente
o en una pasada final sin coste.

## Cadena de herramientas
- `node scripts/wiki-frontmatter.mjs --solo <ruta>` — ensayo del lote antes de escribirlo.
- `node scripts/wiki-frontmatter.mjs --solo <ruta> --escribir` — el lote.
- `npm run test:wiki` — la condición de hecho.
- `git diff -U0` filtrado — la prueba de que no se tocó ningún cuerpo.
