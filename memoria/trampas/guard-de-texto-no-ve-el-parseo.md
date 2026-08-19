---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [design-system, qa]
fuente: sesion
resumen: "Un guard que lee el TEXTO de un CSS con expresión regular encuentra la declaración y da verde aunque el navegador nunca la aplique: un comentario mal cerrado hizo que el parser se tragara la línea siguiente, y los ocho carriles de la suite siguieron en verde con el filete de cuatro estados apagado en pantalla"
---

# Un guard que lee el texto del CSS no ve lo que el parser descarta

**Medido el 2026-08-19**, frente `ds-f1a-estados-severidad`, sobre `public/css/tokens.css`.

## Qué pasó

Al reescribir el bloque de tokens del filete de gravedad, el comentario anterior quedó **cerrado**
y el texto nuevo detrás como CSS crudo, con un `*/` suelto al final:

```css
       decir «medido y sin problema» para decir «no se midio». */
       SOLO DOS ESCALONES LLEVAN FILETE, y la ausencia es el tercer valor.
       ... prosa ...
       tengan token no es un olvido, es el contrato. */
    --ds-severity-rail-width-urgent: 6px;
    --ds-severity-rail-width-attention: 4px;
```

El parser descarta la basura y **se recupera en el siguiente `;`**, así que se traga la declaración
que viene justo después. Efecto en pantalla: `--ds-severity-rail-width-urgent` resolvía a **cadena
vacía**, `box-shadow: inset  0 0 0 #ffcdc8` era inválido, y **los cuatro estados `urgent` de
Programación Intermedia perdieron el filete**. `attention` sobrevivió por estar una línea más abajo.

## Por qué ningún guard lo vio

`tests/design-system/severity-rail.test.mjs` comprueba el token así:

```js
const v = tokens.match(new RegExp(`--ds-severity-rail-width-${n}:\\s*([^;]+);`))?.[1]?.trim();
assert.ok(v, `falta --ds-severity-rail-width-${n}`);
```

**Y lo encuentra.** El texto estaba perfectamente escrito; lo que fallaba era el **parseo**. La
suite estática dio **8/8 carriles en verde** con la pantalla rota.

## La regla

> **Un guard que lee el texto de una hoja valida la DECLARACIÓN, nunca lo que el navegador aplicó.**

Es pariente de [[guard-valida-declaracion-contra-si-misma]] pero no es lo mismo: aquel comprueba un
JSON contra ese mismo JSON; éste sí mira el archivo correcto y aun así no ve nada, porque la unidad
que mide —la cadena de texto— no es la unidad que el navegador ejecuta.

**Un guard de texto no puede detectar:** un comentario mal cerrado, una llave sin cerrar, una
declaración dentro de un bloque que el parser abortó, una `@layer` mal formada, o un valor válido
como cadena pero inválido como CSS.

## Cómo se caza

Lo cazó **una captura**, no un assert. Y lo diagnosticó en un paso una sonda que resuelve las
variables **en el navegador** en vez de leerlas del archivo —
`goals/ds-f1a-estados-severidad/evidence/sonda-vars.mjs`:

```js
const cs = getComputedStyle(td);
cs.getPropertyValue('--ds-severity-rail-width-urgent')  // → ""  ← aquí estaba
```

Eso separó «no se ve» de «el token llega vacío» sin conjeturar. Comparar el valor **declarado** con
el **computado** es lo que hay que hacer siempre; comparar declarado contra declarado es lo que da
verdes falsos.

## Dos comprobaciones baratas que sí lo habrían cazado

- **Comentarios balanceados**: `t.count('/*') === t.count('*/')` sobre la hoja. Una línea.
- **Resolver el token en el navegador**, no leerlo del archivo, en cualquier prueba de runtime que
  ya tenga página abierta.

Ninguna de las dos existía cuando esto pasó.
