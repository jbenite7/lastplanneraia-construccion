---
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, qa]
fuente: sesion
resumen: "Un gate que lee archivos da verde con un token que apunta a una variable inexistente; solo el navegador ve que la cadena de var() se rompió"
---
`scripts/design-system-table-contract.mjs` comprobaba que los `--ds-table-*` y los
`--ds-cell-state-*` **existieran y estuvieran bien formados**, leyendo `tokens.css` como texto.
Pasó en verde durante tres días con `--ds-table-empty-fg: var(--ds-active-text-tertiary)`, un
token que apunta a una variable **que nunca existió**: la escala activa llega hasta `secondary`.
El texto del estado vacío caía a color heredado en todas las tablas de la aplicación.

Un gate estático no puede ver esto por construcción: la declaración es sintácticamente
impecable y el fallo solo ocurre al resolver la cascada. Lo mismo vale para `color-mix()` y
`oklch()`, cuyo valor final no existe en ningún archivo del repositorio.

**Why:** el criterio de cierre del goal [[goals/cierre-dark-mode-y-tablas/goal|cierre-dark-mode-y-tablas]] exigía medir «con filas cargadas» y AA
por par, y se dio por cumplido con un gate que nunca abrió un navegador.

**How to apply:** todo contrato sobre *valores resueltos* necesita superficie de runtime. El
compañero es `tests/browser/design-system-table-contract.runtime.mjs`, enrutado en
`test:design-system:runtime`. Dos filos medidos al escribirlo:

- **El color computado se serializa en el espacio en que se escribió.** Una escala en `oklch`
  devuelve `oklch(...)`, y leer esos tres números como si fueran canales RGB da un contraste
  inventado — daba 1,01:1 sobre pares que se leen perfectamente. Hay que pasar por un lienzo
  (`canvas` 1×1 + `getImageData`) para obtener sRGB de verdad.
- **`getPropertyValue` sobre una variable devuelve el texto sin evaluar.** Para resolverla hay
  que pintarla en un elemento real y leer el computado.

Y para elegir superficie: los fixtures del laboratorio se repiten en **cada** familia y solo una
está visible, así que un `.first()` sin acotar cae en una tabla oculta. La familia con grilla es
`vendor-adapters`. Ver [[semanal-auto-dispara-mutaciones]] para por qué no se mide en
`/programacion-semanal`.

Relacionado: [[visual-baselines-estado-real]], [[gate-visual-tolerancia-enganosa]],
[[manifiesto-ds-exige-golden]].
