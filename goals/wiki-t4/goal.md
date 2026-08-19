---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/wiki-t4/goal.md
resumen: Dar al vault su capa visual solo con lo nativo de Obsidian: dashboard, canvas, vistas Bases por área, grupos de color del grafo y un snippet CSS de severidad…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t4

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 4 · Tanda 4 — Capa visual
Sha verificado: ?
Presupuesto: ?

## Objetivo
Dar al vault su capa visual **solo con lo nativo de Obsidian**: dashboard, canvas, vistas Bases por
área, grupos de color del grafo y un snippet CSS de severidad. Sin plugins de comunidad.

## Condición de hecho
`npm run test:wiki` y `wiki-lint.mjs --estricto` en verde; los tres canvas validan como JSON con
todos sus destinos existentes; ninguna vista de `paginas.base` cambia salvo la que se movió a
propósito.
Verificación: npm run test:wiki

## Posture
- **Sin plugins de comunidad.** Decisión del usuario del 2026-08-19: los decide aparte.
- No escribir sintaxis que no se pueda verificar. Si no hay forma de comprobarla, no se escribe.
- No tocar el cuerpo de las páginas salvo el índice, que es el entregable.
- No inventar una escala de severidad: se espeja la que ya existe y se dice que es un espejo.

## Leer primero
- docs/design-system/auditoria/escala-severidad.md
- memoria/paginas.base — la forma de vista que ya funciona

## Archivos declarados
memoria/**,.obsidian/**,scripts/wiki-*.mjs,tests/wiki/**

## Cierre

**Fase 4 · Tanda 4 (sin plugins) cerrada el 2026-08-19.**

```
npm run test:wiki                      →  RC=0 · 156 páginas de wiki y 413 de 416 fuentes declaradas
node scripts/wiki-lint.mjs --estricto  →  RC=0
node --test tests/wiki/*.test.mjs      →  75 tests, 0 fallos
canvas: 6+8+15 nodos, 4+8+0 aristas, todos los destinos existen
vistas: solo cambió la que se movió a propósito en la Tanda 5
```

### Lo que la Tanda 5 dejó a deber, y aquí se pagó

La Tanda 5 no creó ninguna vista Bases por área **porque no podía verificar la sintaxis de
filtrado por listas**, y un `.base` mal escrito no lo caza ningún gate: se rompe dentro de Obsidian
mientras el lint sigue verde.

**Aquí sí se verificó, contra el binario de Obsidian instalado**
(`~/Library/Application Support/obsidian/obsidian-1.13.7.asar`): el operador `containsAny` está
registrado **para el tipo lista** —la cadena `labelListContainsAny` aparece junto a su definición—,
igual que `containsAll`, `inFolder`, `hasTag`, `hasProperty` y `isType`. Con esa evidencia se
escribieron las 13 vistas de `memoria/bases/`, una por área, embebidas en su MOC.

El mismo método se usó para el formato de canvas (`fromNode`, `toNode`, `fromSide`, `toSide`) antes
de escribir los tres.

**El prefijo `area-` de los `.base` no es estética:** `memoria/bases/pdc.base` y
`memoria/mapas/pdc.md` compartirían `basename`, y entonces `[[pdc]]` pasa a ser un enlace
**ambiguo** que el lint reporta. Se vio antes de escribirlos porque el lint ya tenía esa regla.

### Dos defectos del lint que destapó usarlo

Ninguno se maquilló: los dos eran **falsos positivos sobre sintaxis válida**, no comprobaciones
molestas.

1. **`.canvas` no estaba en el índice del vault.** Un enlace correcto a un canvas se reportaba como
   roto, porque el lint solo indexaba `.md` y `.base`.
2. **El separador de alias escapado (`\|`) ensuciaba el destino.** Dentro de una tabla de Markdown
   la barra **debe** escaparse o corta la celda, así que `[[destino\|Alias]]` es la forma correcta;
   el lint se llevaba la contrabarra dentro del destino y lo daba por roto.

Los dos van con prueba. La prueba vive en `tests/wiki/esquema.test.mjs` replicando el patrón del
lint, **no importándolo**, porque el lint no exporta nada: si ese patrón cambia, el test deja de
cubrirlo. Dicho a propósito en el propio archivo, para que nadie lo lea como más garantía de la que
da.

### Lo que se hizo, pieza por pieza

- **13 vistas Bases** en `memoria/bases/area-<área>.base`, con dos tablas cada una: lo que hay del
  área en la wiki y lo que hay en las fuentes. Es la primera cosa que **aprovecha** el frontmatter
  que la Tanda 2 puso en las 413 fuentes.
- **3 canvas**: `tablero-de-control` (las 13 áreas en cimientos / producto / oficio),
  `mapa-del-sistema` (front controller, `src/`, el legado, `admin/` y la capa de datos) y
  `cascada-lps` (PG → PI → PS y de dónde salen los indicadores).
- **Snippet CSS** `.obsidian/snippets/severidad-aia.css`, con la cascada de la auditoría
  (crítico → sin-problema) en callouts y distintivos. **Lleva escrito que es un espejo y no una
  fuente**: la escala real la fija DS-F1, y los colores canónicos viven en `tokens.css` en OKLCH.
- **Dashboard** en `memoria/index.md`: callouts de orientación, acceso a los tres canvas y la lista
  de lo que está abierto. Y una corrección: la tabla de capas decía que las fuentes no se editan
  desde aquí, y desde el 2026-08-19 llevan frontmatter — metadato añadido, no cuerpo tocado.
- **Canvas activado** en `core-plugins.json`; el snippet, en `appearance.json`.

### Lo que NO se hizo

- **Ningún plugin de comunidad.** Decisión del usuario. Con ello quedan fuera de esta tanda el
  Kanban de la cola, Iconize, Homepage y el tema Minimal, que el plan pedía.
- **Grupos de color del grafo**: `.obsidian/graph.json` no se tocó. Los grupos se guardan con el
  estado de la vista del grafo, que es en parte personal, y no encontré forma de comprobar que la
  consulta de color hace lo que dice sin abrir la aplicación. Mismo criterio que con Bases: sin
  verificación, no se escribe. **Queda pendiente y anotado**, no hecho a medias.

### Deuda de un par, atendida

La sesión de `ds-f1a` reportó, vía coordinadora, que los moldes de `memoria/templates/` dejan
`tags: []` sin decir que la lista es cerrada, y que por eso alguien tropezó con el lint. Los cinco
moldes documentan ahora las dos listas cerradas y apuntan a dónde vive la viva.
