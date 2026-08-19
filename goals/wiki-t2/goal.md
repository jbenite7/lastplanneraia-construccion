---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/wiki-t2/goal.md
resumen: Aplicar el backfill de frontmatter a la capa de fuentes, por lotes y con revisión entre uno y otro, usando la herramienta que dejó lista la Fase 1. Al…
---

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

## Cierre

**Fase 2 · Tanda 2 cerrada el 2026-08-19.** 410 de las 413 fuentes del vault llevan frontmatter
del esquema v2, y `node scripts/wiki-lint.mjs --estricto` pasa sobre todo el vault:

```
Sin hallazgos. 151 páginas de wiki y 410 de 413 fuentes declaradas (modo estricto).
```

**Las tres que faltan no son un descuido: están congeladas por contrato.** Ver «El sexto defecto»
más abajo.

**Ningún cuerpo tocado, y no es una promesa sino una cuenta:** `git diff --shortstat` sobre los
cuatro lotes da `413 files changed, 4171 insertions(+)` y **cero borrados**. Un backfill que solo
añade no puede haber modificado una línea de contenido.

`DESIGN.md` conserva intacto su frontmatter de otra herramienta (el que leen el linter Stitch y el
panel live): las claves del esquema se le antepusieron, no lo sustituyeron.

### Cinco defectos que destapó aplicar, no diseñar

El repaso muestral entre lotes es lo que los encontró. Ninguno se maquilló: cada uno se arregló en
la regla y se volvió a medir, como manda la Posture.

1. **Una cabecera de metadatos no es prosa.** `ROADMAP.md` resumía «Fecha: 2026-03-02» y las specs
   cogían la cabecera empezando donde acababa la etiqueta.
2. **La etiqueta de tesis va a media línea, no al principio.** `**Fecha:** … · **Decisión del
   usuario:** …` — anclarla al inicio dejaba fuera justo los documentos que el arreglo 1 mandaba
   allí.
3. **La etiqueta se cortaba al final de la línea:** devolvía «replantear toda la wiki sin perder
   la», una frase partida a la mitad.
4. **`campo()` leía la línea siguiente como valor de un campo vacío**, porque `\s*` incluye el
   salto de línea. Trece archivos con `fecha:` vacía se reportaron como
   «fecha no ISO: areas: [design-system]». **El defecto venía heredado del lint v1**, donde llevaba
   sin verse desde que se escribió: nunca hubo un campo vacío hasta este backfill.
5. **Una línea administrativa sin negritas descartaba el párrafo entero.** `Spec: [enlace]` abre
   casi todos los planes, y detrás venía la frase buena — «Medido sobre f1f5bd87. 41 !important en
   7 reglas», tirada junto con la etiqueta. 12 archivos afectados.

Y una carencia de la herramienta que el propio lote convirtió en bloqueo: `--rellenar`, para
completar las claves escritas pero vacías. El modo normal las respeta a propósito —para no pisar
lo que escribió una persona— y eso dejaba atrapado un lote aplicado antes de arreglar una regla.

### El sexto defecto, y el único que rompió un gate ajeno

`docs/design-system/manifests/goal-provenance.json` **congela por sha256** los tres documentos que
son el registro canónico del goal del design system (`goal.md`, `facts.md`, `plan.md` de
`design-system-nucleo-gobernanza`). El lote 4 les puso frontmatter y el gate `design-system:static`
se puso rojo con `goal provenance: hash mismatch` — exactamente la trampa que `AGENTS.md` ya tenía
escrita: «el contrato fija por hash unos archivos que el frente había editado».

**Se deshizo el cambio en esos tres y se dejaron fuera del backfill.** No se tocaron los hashes:
actualizarlos es tocar un contrato, y eso bloquea. Y aunque no bloqueara, sería la respuesta
equivocada — añadir metadato a un archivo cuyo propósito entero es estar congelado byte a byte
incumple la intención del contrato aunque el hash cuadre después.

La exclusión **se lee del propio manifiesto**, no de una lista de rutas escrita en el script: lo que
hay que respetar no son esos tres archivos, sino la regla de que un contrato puede congelar
cualquiera. Si mañana congela otro, el backfill ya lo respeta sin que nadie se acuerde de venir a
añadirlo. El lint hace lo mismo en modo estricto, y desde el mismo manifiesto.

**Ratificado el 2026-08-19:** esos tres quedan permanentemente sin frontmatter. La alternativa
—regenerar los hashes tras el backfill— se descartó.

### De dónde salió cada resumen

```
113  parrafo      el caso normal
107  titulo       último recurso
102  etiqueta     los planes, que abren con cabecera administrativa
 75  seccion      los goal.md y facts.md
 16  ninguno      escritos a mano
```

Los 17 escritos a mano: `PRODUCT.md`, `AGENTS.md`, `DESIGN.md`, `README.md` y
`docs/design-system/components.md`, `docs/20260325_general_feature_flags.md`, tres `facts.md`, un
HANDOFF, y **ocho `goal.md` que son andamiajes con el objetivo sin escribir**. A esos ocho no se
les inventó un objetivo: su resumen dice exactamente eso, que el andamiaje se creó y nadie lo
rellenó. Ahora se ven en el catálogo, que es donde deben verse.

### Lo que queda para las tandas siguientes

- **Tanda 3** — retag fino de las 151 páginas de la wiki.
- **`--estricto` no está enchufado a `npm run test:wiki`.** Se dejó a propósito: encenderlo es una
  decisión de contrato (a partir de ahí, **toda fuente nueva nace con frontmatter o el gate se
  pone rojo**), y esa decisión no es de este frente. La verificación de hoy corre el modo normal;
  el estricto se corre a mano y está en verde.
