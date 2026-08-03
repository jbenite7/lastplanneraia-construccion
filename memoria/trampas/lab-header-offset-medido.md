---
tipo: trampa
estado: vigente
fecha: 2026-07-27
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-lab-header-offset-medido
resumen: --ds-lab-header-offset lo publica ResizeObserver con la altura real del header; el calc() de lab.css es solo fallback
---
`--ds-lab-header-offset` (que `.ds-lab__rail-wrap` usa en `top` y en
`max-block-size: calc(100vh - ...)`) lo publica `design_system_lab.js` midiendo
`.ds-lab__header` con un `ResizeObserver`. El `calc()` de tokens que sigue en
`lab.css` es solo fallback sin JS.

Antes era una formula de tokens —`--ds-target-min + --ds-space-12 + --ds-space-1
+ --ds-border-width` = 97px— que no mapeaba a ninguna parte real de la caja. El
header mide 104px (`12 + 12` de padding + `79` del bloque de identidad + `1` de
borde), asi que el rail se solapaba 7px con el header. Corregido el 2026-07-27.

**Why:** la altura la dicta el bloque de identidad (eyebrow 18 + h1 36 + lede
21), o sea la escala tipografica; ninguna constante sobrevive a un cambio de
tokens de tipografia.

**How to apply:**
- No vuelvas a "simplificar" el offset a un numero ni a una formula de tokens:
  se desviara otra vez y el sintoma (rail solapado) solo se ve con el sticky
  vivo, que es justamente lo que estuvo muerto y tapo el defecto.
- El ResizeObserver tambien cubre el cambio de densidad compacta/touch, que
  altera la altura del header despues del boot.
- Mismo patron que la trampa de [[hot-container-height-ownership]]:
  altura real medida, no numero magico en CSS.

Relacionado: [[lab-sticky-body-overflow]], [[lab-desktop-layout-suite]]
