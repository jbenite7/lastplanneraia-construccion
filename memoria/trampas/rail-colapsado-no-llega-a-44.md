---
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: public/css/design-system/components/navigation.css, medición en vivo en /internal/design-system a 1180x820
resumen: "El 43,5px de los items del rail colapsado no era redondeo: con padding-inline de 12px el carril de 4rem dejaba 38px utiles y el piso de 44 era inalcanzable por geometria"
---
# El rail colapsado no llegaba a 44px por geometría, no por medio píxel

Los 13 items del sidebar colapsado medían `43,5 × 44`: medio píxel corto **solo en el ancho**, lo
que invita a buscar un redondeo. No lo era. Medido en vivo a 1180x820:

- `--ds-sidebar-width-collapsed` = `4rem` = 64px, menos 2px de borde → 62px de `.aia-sidebar__nav`;
- el `nav` llevaba `padding-inline: var(--ds-space-3)` (12+12) → **38px de ancho útil**;
- el link no declaraba `min-inline-size`, así que su ancho lo fijaba su *min-content*:
  `1+12 + 17,5 (icono) + 12+1 = 43,5px`.

O sea: el 43,5 era la suma exacta del icono más el padding **del estado normal**. El
`padding-inline: var(--ds-space-2)` que el bloque colapsado intentaba aplicar perdía frente al
`padding: … !important` del estado base —que existe por el reset global `* { padding: 0 }` que
`styles.css` trae en la capa `module`—. Y aunque hubiera ganado, con 38px de caja **ningún**
objetivo podía alcanzar 44: el piso era inalcanzable por geometría.

Arreglo (2026-08-04): en el bloque `[data-sidebar-state="collapsed"]`, `padding-inline` del `nav` a
`--ds-space-2` (deja 46px libres) y `min-inline-size: var(--ds-target-min)` en el link.

**Cómo no caer.** Un ancho «medio píxel corto» en una caja con icono suele ser min-content, no
redondeo: suma padding + borde + icono antes de sospechar del navegador. Y en un contenedor de
ancho fijo, comprueba primero si el piso **cabe**; si no cabe, ninguna regla sobre el hijo lo
arregla.

Vecinas: [[lab-desktop-layout-suite]] (la suite que lo detectó, fuera del carril de gates) ·
[[css-layer-cascade]] (por qué el `!important` del estado base gana). Mapa del área:
[[design-system]].
