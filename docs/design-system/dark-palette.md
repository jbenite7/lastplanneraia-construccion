---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/design-system/dark-palette.md
resumen: Estado: approved con anotaciones de tema y densidad, 2026-07-12.
---

# Paleta AIA sobre superficies oscuras

Estado: `approved` con anotaciones de tema y densidad, 2026-07-12.

## Criterio

- Se conserva el matiz de cada color oficial; solo se ajustan luminosidad y croma.
- Las variantes se derivaron mediante mezcla perceptual OKLCH con blanco y se fijaron en sRGB reproducible.
- En dark, los fondos retroceden y los acentos se vuelven más luminosos; no se invierten mecánicamente.
- El texto `#141c18` sobre cada variante supera WCAG 2.2 AA de 4.5:1.

| Dominio | Oficial | Sobre oscuro | Mezcla OKLCH | Contraste |
|---|---:|---:|---:|---:|
| Corporativo | `#1a5633` | `#6c9077` | 64% marca + 36% blanco | 4.87:1 |
| Construcción | `#b55211` | `#c57247` | 82% marca + 18% blanco | 4.86:1 |
| Inmobiliario | `#00a499` | `#2caa9f` | 94% marca + 6% blanco | 6.09:1 |
| Arquitectura | `#6752bf` | `#877cd1` | 76% marca + 24% blanco | 4.83:1 |

Fuentes: [W3C CSS Color 5](https://www.w3.org/TR/css-color-5/), [WCAG 2.2 contraste mínimo](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) y [Apple Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode).
