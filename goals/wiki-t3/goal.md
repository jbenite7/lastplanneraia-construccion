---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/wiki-t3/goal.md
resumen: Que las 151 páginas de memoria/ declaren capa: wiki y lleven los tags transversales que les corresponden, para que el catálogo y las vistas puedan filtrar por…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t3

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 3 · Tanda 3 — Retag fino de la wiki (151 páginas)
Sha verificado: ?
Presupuesto: ?

## Objetivo
Que las 151 páginas de `memoria/` declaren `capa: wiki` y lleven los `tags` transversales que les
corresponden, para que el catálogo y las vistas puedan filtrar por algo más que `tipo` y `areas`.

## Condición de hecho
`npm run test:wiki` verde y `node scripts/wiki-lint.mjs --estricto` verde. La vista «Abierto ahora»
y el catálogo de `memoria/paginas.base` siguen listando lo mismo que antes: el retag **no puede
sacar ninguna página de una vista donde ya estaba**.
Verificación: npm run test:wiki

## Posture
- **No tocar el cuerpo de ninguna página.** Solo el bloque de frontmatter.
- No cambiar `tipo`, `estado`, `fecha` ni `areas` de ninguna página: este frente añade `capa` y
  `tags`, no reclasifica lo que ya estaba decidido.
- No inventar un tag para que una página encaje: el vocabulario es cerrado y son ocho.
- No tocar `memoria/index.md` ni `.obsidian/` (Tandas 4 y 5).
- No tocar los tres archivos congelados por `goal-provenance.json` (ratificado el 2026-08-19).
- No cambiar lo que una vista de `paginas.base` mide.

## Leer primero
- docs/wiki-operacion.md § Los ocho `tags`
- goals/wiki-t2/goal.md § Cierre — los seis defectos que destapó aplicar
- memoria/paginas.base — qué mide cada vista hoy

## Archivos declarados
memoria/**

## Contención
`memoria/` no lo está tocando ninguna otra sesión: las tres vivas trabajan design system y CI.
El riesgo aquí no es la colisión sino la regresión silenciosa — de ahí la segunda mitad de la
condición de hecho, que compara las vistas antes y después en vez de confiar en que el lint pase.

## Cadena de herramientas
- `node scripts/wiki-lint.mjs --estricto` — la condición de hecho.
- Un censo de vistas antes y después, para probar que ninguna página se cayó de donde estaba.
