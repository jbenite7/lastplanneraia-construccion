---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/focus-visible-verde/goal.md
resumen: Andamiaje del frente focus-visible-verde: el goal.md se creo y su objetivo nunca se escribio.
---

# Frente: focus-visible-verde

## Objetivo
Resolver `D-F1-3`: qué hacer con el verde de `handsontable-module.css:777`, que citaba un token
inexistente y pintaba su reserva.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
public/css/handsontable-module.css,docs/decisiones-pendientes.md,memoria/trampas

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

Frente **ejecutado el 2026-08-11**; sección escrita el 2026-08-19 con evidencia de hoy.

```
$ sed -n '775,779p' public/css/design-system/../handsontable-module.css
.lps-sidebar-trigger:focus-visible {
  transform: scale(1.08);
  /* Verde Primario real. Antes decia `var(--aia-green, oklch(...))` y
     `--aia-green` no existe en ningun CSS: pintaba la reserva … */
```

La reserva está retirada y el porqué quedó escrito en el propio archivo.

**La primera respuesta era la equivocada, y por eso importa el cómo.** La primera vuelta eligió
`--aia-green-medium` sobre una premisa falsa: que ese verde *era* el indicador de foco. No lo es —
medido con `Tab` real a 1180×820 en dark, el foco lo señala el **anillo teal del sistema**, y el
verde es solo el relleno del botón. Ver [[medir-foco-de-teclado]].
