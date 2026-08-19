# Plan — DS-F0 · Auditoría total

**Spec:** `docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design.md` · **Estado:** aprobado
el 2026-08-19. **Esfuerzo:** ~2 jornadas. Es la fase más larga del programa y la que alimenta al resto.

## Tanda 1 — El esqueleto del inventario, antes de llenarlo

Definir el formato del inventario y la escala de severidad **operativa** que se va a usar para
clasificar. Ojo: esto no es fijar la escala del producto —eso es DS-F1— sino declarar con qué regla
se está clasificando aquí, para que el inventario sea reinterpretable después.

Censar los módulos a recorrer desde `memoria/arquitectura/`, que ya los tiene generados del código.

- **Verifica:** el censo de módulos coincide con las rutas reales de `public/index.php`.

## Tanda 2 — Recorrido por módulo

Módulo a módulo: tokens consumidos, primitivas usadas, overrides de vendor, `!important`, hex
sueltos, estilos en línea, y escenarios (vacío, cargando, error, hover, focus, selección).

Lote por lote, no todo de una: cada lote cierra su ficha antes del siguiente. Un inventario a medias
de todos los módulos es peor que uno completo de la mitad.

- **Verifica:** cada ficha con archivo y línea; muestreo del 10% recomprobado a mano.

## Tanda 3 — Los dos vendors

Handsontable y DataTables aparte, porque concentran la deuda y su patrón es distinto: no es un
módulo que se desvía del contrato, es un sistema entero que nunca entró.

- **Verifica:** censo de selectores del vendor que ningún token alcanza.

## Tanda 4 — Clasificación y entrega

Ordenar todo por severidad «Crítico → Sin problema». Marcar los huecos que esperan al CI.

- **Verifica:** el inventario abre y se lee; ningún hallazgo sin severidad ni sin ubicación.

## Riesgos y reversas

- **La auditoría se convierte en reparación** → es el riesgo principal y es de disciplina. Cada
  arreglo «de paso» rompe la premisa de DS-F1, que decide el contrato **con el inventario delante**.
- **El inventario crece hasta ser inmanejable** → por eso va por lotes y en formato consultable por
  máquina; si un lote no cabe, se parte.
