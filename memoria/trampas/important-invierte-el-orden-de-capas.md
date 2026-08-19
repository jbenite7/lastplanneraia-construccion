---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [design-system, lps]
fuente: sesion
resumen: "Con `!important` el orden de las capas CSS se INVIERTE: `@layer components` le gana a `@layer module`, al revés que sin él. Editar la hoja del módulo y ver que la pantalla no cambia se lee como «mi cambio no aplicó» cuando lo que pasa es que un bloque gemelo en styles.css sigue ganando"
---

# Con `!important`, `@layer components` le gana a `@layer module`

**Medido el 2026-08-19** al convertir `/programa-general` y `/programacion-semanal` al modelo de
matiz, frentes `ds-f1a-estados-severidad` y `semanal-fondo-por-matiz`.

## La regla

El orden de capas de este repo (`aia-design-system.css:1`) es:

```
@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;
```

Sin `!important`, **`module` gana a `components`**: va después. Es lo que uno espera y por lo que
una hoja de módulo puede ajustar una primitiva.

**Con `!important`, el orden se invierte.** Es CSS Cascade nivel 5: para declaraciones importantes,
las capas se recorren al revés, así que **`components` gana a `module`**. Y como en este repo casi
todo lo que pinta celdas de Handsontable lleva `!important` para vencer al vendor, **la inversión es
la norma en las tablas, no el caso raro**.

## Cómo se manifiesta, que es lo que engaña

No falla nada. **La pantalla simplemente no cambia**, y eso se lee como «mi edición no llegó» o
«hay caché». Se pierde el tiempo recargando, subiendo la especificidad y dudando del navegador.

**Caso medido, `/programa-general`:** quien lo convirtió editó
`public/css/design-system/adapters/programa-general-handsontable.css` (`@layer module`), corrió la
sonda, y **los números salían correctos según su razonamiento** — pero la captura seguía enseñando
`en-curso` en verde y `terminada` sin tinte. La causa: `public/css/styles.css` tiene un **bloque
gemelo con los mismos selectores** y `!important`, en `@layer components`. Ese ganaba.

**Caso medido, `/programacion-semanal`:** al pasar el fondo a matiz **no bastaba con añadir** las
reglas nuevas en `programacion-semanal.css`; hubo que **retirar** el `background-color` de los
bloques `td.ps-row-state.ps-alert-*` de `styles.css`, o las nuevas quedaban inertes.

## La comprobación barata, antes de tocar nada

Antes de editar una hoja de módulo para cambiar el color de una celda, **busca el gemelo**:

```bash
grep -n 'td\.<tu-clase>' public/css/styles.css
```

Si aparece con `!important`, **es ese el que pinta**, no el tuyo. O lo editas ahí, o le quitas la
declaración para que la tuya pueda aplicar.

## Por qué ningún guard lo caza

Los guards estáticos leen el texto de una hoja concreta. **Ninguno cruza dos hojas para preguntar
quién gana la cascada**, que es información que solo existe al resolver el documento entero. Es
pariente de [[guard-de-texto-no-ve-el-parseo]]: allí el texto estaba bien y el parser lo tiraba;
aquí las dos reglas son válidas y gana la que no esperabas.

**Lo cazan las capturas, y solo ellas.** En los dos casos medidos, quien las miró lo vio en el acto;
quien miró el JSON de la sonda vio números que parecían correctos.
