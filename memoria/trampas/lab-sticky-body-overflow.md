---
tipo: trampa
estado: vigente
fecha: 2026-07-27
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-lab-sticky-body-overflow
resumen: "Por qué .ds-lab lleva overflow visible en ambos ejes, y por qué no basta con borrar esa declaración"
---
`.ds-lab` va sobre `<body>` y **debe** quedar `overflow: visible` en AMBOS ejes.
No es cosmetico: basta con que un eje deje de ser `visible` para que body se
vuelva contenedor de scroll, y como body nunca scrollea de verdad
(`scrollHeight === clientHeight`), todo `position: sticky` descendiente
—`.ds-lab__header` y `.ds-lab__rail-wrap`— queda inerte.

Historia: `lab.css` tenia `overflow-y: auto` ahi. Mientras `html` fuera
`visible` en ambos ejes eso se propagaba al viewport (CSS Overflow 3 §3.3) y no
hacia dano. `5134ae2` (F1 Task 4) absorbio `html { overflow-y: auto }` desde
`styles.css` al `@layer reset` de `foundation.css` —que `lab-entrypoint.css` si
importa— y corto la propagacion: header medido en `headerTop = -640` tras
`scrollTo(0, 640)`. Corregido el 2026-07-27 en `lab.css` (`overflow: visible`).

**Why:** el sintoma aparecia en el header, pero la causa estaba dos niveles
arriba, en la interaccion de dos ficheros que ningun gate mira juntos.

**How to apply:**
- No lo "simplifiques" a `overflow-y: visible` ni lo borres. Borrarlo NO basta:
  body conserva `overflow-x: hidden` de `foundation.css` (`c58a6d6`) y eso
  promueve el otro eje a `auto`, reinstalando el bug. Medido en A/B.
- Funciona porque el bloque es `@layer module`, posterior a `@layer base` en el
  orden de `lab-entrypoint.css`. La contencion horizontal la sigue dando
  `html { overflow-x: hidden }` propagado al viewport, no se pierde.
- `c58a6d6` por si solo es inocente: con `html { overflow-y: auto }` presente,
  quitarle el `overflow-x` no cambia nada. Un A/B que solo toque `overflow-x`
  da cifras identicas y despista.
- Al depurar sticky aqui, mide `getComputedStyle(body).overflow*` y
  `body.scrollHeight vs clientHeight` antes que el elemento sticky.

Relacionado: [[css-layer-cascade]], [[lab-desktop-layout-suite]]
