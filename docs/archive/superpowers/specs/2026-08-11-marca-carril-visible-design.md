# La aserción de la marca del carril comprueba que se vea, no que declare un filtro

- Fecha: 2026-08-11
- Frente: `severidad-runtime` (sesión de ejecución 06e4383d)
- Sha de arranque: `b509e90e`
- Origen: ficha de `docs/EXPERIMENTS.md:88`, y la medición que cerró su pregunta abierta

## Qué falla y por qué no es un defecto

`tests/browser/design-system-lab.mjs:448` exige
`await expect(logo).not.toHaveCSS('filter', 'none')` sobre la marca del carril, y el valor
computado es `none`. Es el **único** fallo de los 31 casos de la primera etapa de `runtime`.

No es una regresión. `4437fcfa` («adopta el logo “Last Planner · línea Construcción”») cambió a
propósito `filter: var(--ds-active-nav-mark-filter)` por `filter: none` en
`navigation.css:172-178`, con el porqué escrito al lado: *«El ícono Construcción es a color; no se
tiñe con el tema»*. El filtro existía para teñir una marca monocroma según el tema; el logo nuevo
es a color y teñirlo lo estropearía.

**La aserción defiende un mecanismo que el diseño retiró, no un resultado que el diseño quiera.**

## La pregunta que la ficha dejaba abierta, contestada con medida

La ficha exigía medir *si la marca se ve mal de verdad* antes de tocar nada. Medido a 1180×820
dark, sesión real, dibujando el SVG en canvas contra el fondo del carril `oklch(0.145 0.003 260)`:

| Qué | Contraste |
|---|---|
| Campo ámbar `rgb(181,82,17)`, 1013 px — el grueso de la marca | **1,67:1** |
| Segundo tono `rgb(232,119,34)` | 2,83:1 |
| Su parte más clara (la «L») | 8,37:1 |
| Piso AA para elementos no textuales | 3:1 |

**La respuesta tiene tres mitades, y aplanarla sería mentir:**

1. **Se lee.** La «L» clara destaca con holgura; la marca se reconoce en la captura a tamaño real.
2. **Su silueta exterior no llega al piso:** 1,67:1 contra 3:1.
3. **Ese piso no le aplica.** `DesignSystemComponent.php:431` genera el `<img>` con `alt=""` y
   `aria-hidden="true"`, con el `aria-label` en el enlace que lo envuelve: está **declarado
   decorativo**, y WCAG 1.4.11 gobierna elementos no textuales *que transmiten información*.

Sin el punto 3, el punto 2 sería un incumplimiento y la decisión habría sido la contraria. El
usuario decidió **viendo las capturas y con la tabla delante**: la prueba se ajusta al diseño.

## Qué debe comprobar la aserción nueva

El nombre del test —«theme-visible brand mark»— dice el propósito: **que la marca esté y se
reconozca**. El `filter` era un medio, y se confundió con el fin.

Lo que se querría cazar de verdad es que la marca **desaparezca**, se **cargue vacía** o quede
**tapada**. Nada de eso lo detectaba la aserción vieja: un SVG roto que no pinta nada seguiría
declarando su `filter` y pasaría en verde.

La nueva comprueba seis cosas donde antes había una:

1. **Existe:** exactamente un elemento de marca en el carril.
2. **Cargó:** `naturalWidth > 0` — un SVG vacío o un 404 no lo cumple.
3. **Ocupa sitio:** su caja mide más de cero en ambos ejes.
4. **Está visible:** ni `display:none`, ni `visibility:hidden`, ni opacidad nula.
5. **No está tapada:** lo que hay en el centro de su caja es ella misma, no otro elemento encima.
6. **Está dentro del carril:** su caja cae dentro de la del carril, no desbordada fuera de vista.

**Comprueba más que la anterior, no menos**, y comprueba en **pantalla** y no en el DOM — que es
la distinción que este repo ya tiene escrita como trampa
([[el-dom-dice-que-existe-no-que-se-ve]]): `querySelector` encuentra igual de bien lo que se ve y
lo que no.

**Lo que deja de comprobar, dicho claro:** que la marca declare un `filter`. Es intencionado, es
lo único que se retira, y es exactamente lo que el diseño decidió que no se exige.

## Lo que este frente no es

**No es poner `runtime` en verde.** Al pasar este fallo quedará medida **solo la primera etapa**:
el script encadena con `&&`, así que a11y, visual y rendimiento **no llegan a correr**. El recibo
lo repone la coordinadora, con el alcance declarado y leyendo el código de salida sin tubería —el
`exitCode 0` que yo vi era del `tail`, no de la suite—. Este frente **no toca evidencia**.

## Condición de hecho

1. La aserción comprueba que la marca se ve, con las seis comprobaciones de arriba.
2. Junto a ella queda escrito por qué cambió, con `4437fcfa` y la frase del diseño.
3. La ficha de `docs/EXPERIMENTS.md:88` queda cerrada con la respuesta y sus tres mitades.
4. **Mutación ejecutada:** con la marca quitada u oculta, la aserción **falla**. Si sigue verde,
   está mirando el DOM y no la pantalla, y no vale.
