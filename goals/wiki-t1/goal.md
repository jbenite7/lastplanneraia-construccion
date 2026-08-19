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
