<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: ci-en-verde

## Fase del plan
Plan: docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md
Fase: Fase F-0
Sha verificado: 65c44435 (job design-system-static en success; ver «Condición de hecho»)

## Objetivo
Devolver el workflow `design-system.yml` a verde, rojo desde el 2026-07-17, aplicando `D-GAC-1`:
la aserción del contrato de Programa General permite `!important` dentro de `@layer` y lo sigue
prohibiendo fuera. El CSS no se toca.

## Condición de hecho
`gh run list --workflow=design-system.yml --limit 1` muestra la primera `success` desde el
2026-07-17. Un `RC=0` local no la sustituye: el rojo era de CI.

**Estado: el job `design-system-static` está en `success`** sobre `65c44435` (corrida
`31561660136`, 2026-08-12) — la primera desde el 2026-07-17. La corrida **completa** sigue en
`failure` por el job `design-system-runtime`, que la Tarea 3 Paso 3 del plan declara terreno de
F-AB y dice que **no reabre esta fase**. Cuál de las dos lecturas cierra el frente **lo resolvió la
coordinadora el 2026-08-12: se cierra en su alcance, sin declarar «CI verde»**. Ver `## Cierre`.

## Medido en esta sesión (sobre f743c29b)

Tarea 1, hecha. La aserción nueva entregada con sus dos mutaciones ejecutadas:

- `!important` **fuera** de toda capa → `RC=1`, y cae la aserción esperada:
  `PG solo puede usar !important dentro de una @layer, nunca fuera de toda capa` (línea 72).
- `!important` **dentro** de `@layer components` → la línea 72 no dispara. Es lo que demuestra la
  excepción; sin esta segunda mutación solo estaría medida la prohibición.
- `public/css/programa-general.css` restaurado byte a byte: `git status --porcelain` solo lista
  el `.mjs`.

Segundo rojo, independiente y anterior, que impide cerrar:

```
AssertionError: PG debe renderizar siete filtros para cada area del proyecto
0 !== 14
    at tests/test_programa_general_sprint_contract.mjs:101
```

La regex de la línea 21 exige `class="aia-chip pg-filter-chip"` contiguos; el markup real es
`class="aia-chip pdc-legend-item pg-filter-chip …"`. Los 14 chips existen. Introducido en
`47dda844` (2026-08-04), ancestro de `main`, y tapado hasta ahora por el `!important`.

Corrección a la premisa del plan: la suite estática **no** está en rojo —
`npm run test:design-system:static` → `RC=0`, 8/8, también con `DS_ACTIVATION_STRICT=1`; su paso
`node-tests` ni siquiera barre este archivo. Lo rojo es el paso aparte `Enforce Programa General
pilot contract` (`.github/workflows/design-system.yml:46-47`), único fallo en el log de la corrida
`31554536625`. La conclusión del plan se sostiene (el job cae y `design-system-runtime` no corre);
la atribución a la suite estática no era exacta.

## Archivos declarados
tests/test_programa_general_sprint_contract.mjs

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Tarea 2b — D-GAC-2 (sobre 54e5f474)

`tests/…:21` compara tokens de clase en vez de una cadena literal: exige `aia-chip` y
`pg-filter-chip` sin exigir orden ni vecindad. Markup intacto. Las dos mutaciones exigidas por la
decisión, ejecutadas, ambas cayendo en la aserción esperada (`:111`):

- quitar un chip real entero → `13 !== 14`
- dejar el chip y quitarle solo la clase `pg-filter-chip` → `13 !== 14`

La segunda es la que vale: el elemento sigue presente con `aia-chip` y su `data-filter`, y aun así
cuenta 13. El número de la aserción no se tocó.

## Censo de lo que queda (sobre 54e5f474)

Tras dos rojos que tapaban al siguiente, en vez de escalar el tercero suelto se midió cuántos
quedan, con una copia desechable del contrato que acumula fallos en lugar de abortar en el primero
(creada y borrada; el árbol quedó limpio).

**Queda exactamente 1.** Contraste del instrumento contra `6d82f723`, cuyo resultado ya se conocía:
reportó los 3 fallos exactos que se sabían. El instrumento es sano.

El que queda (`buttons.css`, aserción `:150`, «los chips canónicos deben envolver entre palabras
sin fragmentarlas al ampliar»): exige `!important` en cuatro declaraciones de `.pdc-legend-item`.
En `buttons.css:976` **las cuatro existen**, pero solo `white-space` conserva el `!important`; las
otras tres lo perdieron en `0a228a39` (2026-08-11), *«diez de los dieciséis !important del chip de
leyenda no ganaban nada»*. El comportamiento nombrado sigue ahí; lo caducado es la exigencia del
`!important`. Es terreno de **F-D (`D-BTN-1`)**, cuya resta ya se ejecutó en parte sin actualizar
este contrato, y el CI en rojo lo tapaba.

## Tarea 2c — D-GAC-3 (sobre b0e71e19, después superseded)

La aserción pasó a exigir los valores y no el `!important`. Medido:

- archivo entero → `RC=0`, primera vez desde el 2026-07-17.
- mutación: quitar `word-break: normal;` del bloque `.pdc-legend-item` de `buttons.css` → `RC=1`,
  y cae la aserción que nombra esa propiedad. Prueba además que el bloque está bien acotado:
  existen otros dos `word-break: normal;` en el archivo (`:30`, `:89`) y no lo salvaron.
- `buttons.css` restaurado byte a byte; `git status --porcelain` no lo lista.

**Comprobación en navegador** (la que el contrato no tenía), `/programa-general`, 1180×820,
`html.aia-theme-dark`, sesión por la puerta de servicio con `test.A` en «Da Porto»:

- **7 chips `.pdc-legend-item` encontrados** — no cero, así que no hay ambigüedad entre «no aplica»
  y «no lo encontraste».
- `white-space`, `overflow-wrap` y `word-break` computan **`normal` en los 7**. Los valores ganan
  sin el `!important`: la decisión se sostiene y no hay hallazgo que escalar.

Dos instrumentos rotos cazados por el camino, ambos por contradecir un hecho ya sabido:

1. Un primer barrido de reglas devolvió **cero** reglas declarando `display` sobre el chip, con la
   regla existiendo en `buttons.css`. Causa: no descendía a los `@import`. `buttons.css` **sí**
   carga, vía `@import` dentro de `aia-design-system.css` (47 hojas contando imports).
2. `display` computa `flex` y no el `inline-flex` que declaran las cuatro reglas que lo fijan.
   **No es defecto:** el padre `#pgLegend` es `display: flex`, así que `inline-flex` se blockifica
   a `flex` por CSS estándar. Perseguido hasta disolverlo, no dejado como duda.

## Publicaciones

- 2026-08-12 · `485b24f6` (validation-log + excepción de `.gitignore`) publicado por la
  coordinadora (01a82dae) vía merge `83cda3f4`, push confirmado `main...origin/main` sin
  ahead/behind sobre `b3d65ddd`. Verificado vivo en el árbol publicado:
  `git show origin/main:goals/ci-en-verde/validation-log.md` devuelve el archivo. Con esto el
  frente queda **cerrado del todo**: era lo único que le faltaba publicar.

No las hizo este frente. `f743c29b` (D-GAC-1) y `54e5f474` (D-GAC-2) llegaron a `origin/main` por
otra sesión, bajo la excepción de protocolo anotada en `docs/decisiones-pendientes.md`; `D-GAC-3`
la reimplementó esa misma sesión y está en `65c44435`.

Al integrar, `tests/test_programa_general_sprint_contract.mjs` dio conflicto entre mi `D-GAC-3`
(`b0e71e19`) y la ya publicada. **Se resolvió a favor de la publicada**, que además declara
explícitamente que el bloque existe. Tras el merge `git diff origin/main` sale **vacío**: esta rama
no aporta contenido nuevo y no hay nada que publicar.

## Lo que queda fuera de este frente

`design-system-runtime` falla en «Run laboratory gates»: **18 pruebas visuales** de
`tests/browser/design-system-lab.visual.mjs:132` en dark, a 1180×820 y 1440×900, 2 pasadas. Es de
F-AB. No se tocan los baselines.

## Cierre

Anotado el 2026-08-12, tras el visto de la coordinadora (`.claude/vistos/ci-en-verde` → `b10a3298`)
y su cierre en el plan (`docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md`, «Cierre de
F-0»). **La duda que este frente dejó abierta —cuál de las dos lecturas cierra la fase— la resolvió
ella: se cierra en su alcance, y no se declara «CI verde».**

**Sha publicado:** `65c44435`; el cambio en sí, `b10a3298`. Ambos verificados ancestros de
`origin/main` con `git merge-base --is-ancestor`. **Este frente no empujó nada**: sus commits
`f743c29b` y `54e5f474` los publicó otra sesión bajo la excepción de protocolo registrada en
`D-GAC-3`, y su `b0e71e19` quedó superseded al resolver el conflicto a favor de lo ya publicado.
`git diff origin/main -- tests/test_programa_general_sprint_contract.mjs` sale vacío.

**Condición de hecho:**

- ✅ `design-system-static` → `success` en la corrida `31561660136`, primera desde el 2026-07-17,
  verificada contra las 12 corridas anteriores (todas `failure` o `cancelled`).
- ✅ `node tests/test_programa_general_sprint_contract.mjs` → `RC=0`, y la suite estática 8/8 con
  `DS_ACTIVATION_STRICT=1`, re-medido **después** de integrar.
- ❌ La corrida **completa** sigue en `failure` por `design-system-runtime`. El plan lo preveía por
  escrito antes de que ocurriera; no reabre esta fase. Es `D-GAC-4`.

**Lo que el frente destapó y no arregló, a propósito:** los 18 fallos visuales del runtime —que
ahora se ven porque el job se ejecuta por primera vez en un mes— y que `display` computa `flex` y
no el `inline-flex` que declara el contrato, una línea que vigila una declaración inerte. Ninguno
se tocó de paso.
