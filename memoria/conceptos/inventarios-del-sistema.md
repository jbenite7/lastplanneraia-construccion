---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
tags: [trampa]
fuente: scripts/design-system-unlayered-delivery.mjs, scripts/design-system-contracts.mjs, src/Controllers/Internal/DesignSystemLabController.php
resumen: "Cuatro censos con carácter distinto: vendors homologa terceros con hash, unlayered-delivery es lista cerrada de lo que puede vivir sin capa, ui-groups y operational-fixtures alimentan el laboratorio"
---
# Los inventarios del sistema: cuatro censos, cuatro caracteres

Cuatro archivos de `docs/design-system/` son censos, pero no del mismo tipo — confundirlos lleva a
tratarlos con la seriedad equivocada:

| Archivo | Carácter | Qué censa |
|---|---|---|
| `vendors.json` | **homologación con candado** | Librerías de terceros: versión, entrega, `adapterMaturity`, y el `sha256` de cada asset local — `scripts/design-system-contracts.mjs:856-879` verifica que los archivos existan y no hayan sido alterados |
| `unlayered-delivery-inventory.json` | **lista de excepción cerrada** | Hojas de estilo que entran al documento **fuera** del sistema `@layer`. Como una hoja sin capa gana por cascada al design system entero, lo que no esté declarado aquí **debe eliminarse**, no registrarse — gates en `scripts/design-system-unlayered-delivery.mjs:30` (estático) y `tests/browser/design-system-unlayered-delivery.mjs:29` (runtime) |
| `ui-groups-inventory.json` | censo descriptivo con guardas | Grupos de selectores por familia visual que el laboratorio muestra; el gate exige `themes === ['dark']` en todos — `design-system-contracts.mjs:280` — lo que lo hizo pieza clave del retiro de `linen` |
| `operational-fixtures.json` | censo de escenario | Los objetos operativos reales (grillas, selects, calendario) que el laboratorio renderiza como fixtures ejecutables — lo lee `DesignSystemLabController.php:71-73` |

**La distinción que más cuesta caro:** `vendors.json` y `unlayered-delivery-inventory.json` son
normativos —salirse de ellos rompe gates—, mientras que `ui-groups` y `operational-fixtures`
describen lo que el laboratorio enseña. Añadir un vendor nuevo exige alta en `vendors.json` con
hash; colar una hoja sin capa exige justificarla en el inventario de unlayered o ponerle capa.

`admin/` queda explícitamente fuera del alcance del inventario de unlayered — coherente con que
[[panel-admin|el panel]] es otra aplicación.

## Dónde se rompe esto en la práctica

- [[navbar-css-consumidor-vivo]] — antes de borrar una hoja «huérfana», comprueba quién la sirve:
  el censo dice quién declara, no quién carga.
- [[css-layer-cascade]] — por qué «sin capa gana a todo», que es la razón de ser del inventario de
  unlayered.

Mapa del área: [[design-system]] · vecino: [[laboratorio-design-system|el laboratorio]].
