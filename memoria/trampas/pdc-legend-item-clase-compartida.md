---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, lps]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "pdc-legend-item es una clase compartida entre PG, PI y PS; adoptar el design system exige desacoplarla, no pelear la cascada"
---
**`pdc-legend-item` es una clase compartida trampa** (revisado el 2026-08-03): la regla
`html body … {width: 205px !important}` que citaba la línea 6476 de `styles.css` **ya no
existe** — `205px` no aparece en el archivo (comprobado de nuevo el 2026-08-06; el conteo de
líneas se mueve con cada commit, hoy 4211). Tras la tokenización,
`.pdc-legend-item` se define en `styles.css:532-536` (corregido el 2026-08-10: las reglas de
estado — `.missing`, `.critical`, `.delayed`… — empiezan en `:536` y llegan a `:541-542`; el rango
anterior caía dentro de un comentario sobre tokens `--pdc-*` movidos a `tokens.css`) con tokens de
estado del design system y sin `!important` de ancho. Lo que sigue vigente es el fondo del asunto: la clase la comparten
PG, PI y PS, y `buttons.css` le pone `!important` en capa `components`, invencibles desde
CSS de módulo. **La cifra se corrigió el 2026-08-12**: aquí ponía «la llena de `!important`», y
tras la resta de `0a228a39` el bloque de `buttons.css:976` tiene **6**, no un enjambre —
`white-space`, `flex-shrink`, `font-size`, `line-height`, `transition` y `border`. Los de envoltura
(`overflow-wrap`, `word-break`) ya no están y **no hacían falta**: medido en navegador, ver
[[contrato-que-exige-important-mide-la-forma]]. El consejo no cambia, porque esos 6 siguen siendo
invencibles desde el módulo. Para adoptar el design system en una leyenda, desacopla con una clase propia del
módulo (patrón `pg-filter-chip`) en vez de pelear la cascada.

**Why:** una clase compartida entre tres módulos con `!important` de vendor invencibles desde el
módulo produce parches locales frágiles. **How to apply:** al tocar la leyenda de PG/PI/PS, crear
una clase propia del módulo en vez de sobreescribir `pdc-legend-item`.

Relacionado: [[css-layer-cascade]], [[reset-legacy-pisa-adaptadores]].
