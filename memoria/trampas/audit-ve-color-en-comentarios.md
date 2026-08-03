---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-audit-ve-color-en-comentarios
resumen: "El audit del DS cuenta hex y rgba() escritos dentro de comentarios CSS: documentar un cambio citando el valor pone el gate en rojo"
---
`scripts/design-system-audit.mjs` cuenta `hardcoded-hex` y `hardcoded-color-function` sobre el
**texto crudo del archivo**, no sobre las declaraciones parseadas. Un `#8f1d1d` o un
`rgba(26, 86, 51, 0.12)` escrito dentro de un `/* comentario */` **cuenta como infracción**. El
filtro de comentarios que tiene el hex solo mira los 8 caracteres previos, así que no salva un
bloque de comentario largo, y el de funciones de color no filtra nada.

Ocurrió 3+1 veces seguidas al documentar la tokenización de `public/css/pdc.css` (2026-07-29).

**Why:** el reflejo natural al tokenizar es dejar escrito de qué valor se venía, y eso es
justo lo que rompe el gate — cuesta una vuelta entera de audit por cada comentario.

**How to apply:** en comentarios de CSS describe el color **con palabras** («un verde muy oscuro»,
«rosa pálido», «el ancla de crítico»), nunca con el literal. Si necesitas dejar constancia del
valor exacto, ponlo en el JSON de excepciones o en la bitácora del goal, que no se auditan.

Seguro por construcción, en cambio: `color-mix(...)` y `oklch(from var(--x) ...)` **no** los cuenta
`colorFunctionPattern`, porque exige un dígito justo tras el paréntesis. Son la vía tokenizada.

Relacionado: [[css-layer-cascade]], [[manifiesto-ds-exige-golden]].
