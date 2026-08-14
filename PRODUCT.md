# Product

## Register

product

## Platform

web

## Users

Los equipos de planificación y ejecución de obra usan AIA durante la operación para anticipar riesgos y decidir qué acciones tomar antes de que una restricción o desviación llegue a afectar el trabajo.

## Product Purpose

Last Planner AIA hace visible el estado de la planificación y ejecución de obra para que los equipos identifiquen los puntos críticos con antelación. El éxito es que puedan actuar antes de que esos puntos se conviertan en incidentes operativos.

## Positioning

Convierte señales operativas dispersas en puntos críticos accionables antes de que ocurran.

## Brand Personality

Seguridad, confianza y foco. La experiencia debe sostener decisiones rápidas y responsables, sin desviar la atención del trabajo.

## Anti-references

No debe verse decorativa, saturada de alertas ni exigir lectura innecesaria antes de actuar.

## Design Principles

- Hacer visible el riesgo antes de que escale.
- Priorizar la siguiente decisión operativa por encima de la ornamentación.
- Mantener el contexto de proyecto y momento de trabajo siempre legible.
- Comunicar estados con calma y precisión para apoyar la confianza del equipo.

## Accessibility & Inclusion

WCAG AA como mínimo, foco visible, objetivos de interacción de al menos 44 px y respeto por `prefers-reduced-motion`.

**Excepción registrada (2026-07-29, ampliada 2026-08-03): superficies de datos densas.** En pantallas de hoja de cálculo operadas con ratón en desktop y sin equivalente móvil, el alto de fila/control es de 24 px y el cuerpo de texto de 13 px, con un piso duro de 11 px reservado a elementos secundarios (el dato principal no baja de 13 px). Nació el 2026-07-29 midiendo `/plan-compras`: la métrica anterior dejaba 17 filas de presupuesto en pantalla cuando el trabajo consiste en recorrer cientos. El 2026-08-03 se amplió de una superficie a **la familia completa de tablas desktop** (`/programa-general`, `/programacion-intermedia`, `/programacion-semanal`, `/pdc`, `/plan-compras`) vía el contrato `--ds-table-*` del design system — y de paso se corrigió el propio piso: el primer corte usaba 28 px por calco directo de `/plan-compras`, sin razón para quedarse por encima del suelo real. La premisa pasó a ser maximizar filas visibles contra el suelo real de **WCAG 2.2 SC 2.5.8 (AA): 24×24 px** para objetivos de interacción — no el genérico de 44 px, que protege el acierto del dedo sobre un cristal y no aplica aquí porque esta familia está fuera del alcance móvil del producto por contrato. El alto de fila/control queda **exactamente en ese mínimo, sin margen**: ningún control futuro dentro de una fila puede medir menos sin incumplir de verdad. Contraste AA (4.5:1), foco visible, orden de foco, teclado y `prefers-reduced-motion` no se relajan — y las cabeceras deben quedar legibles: compactar rompiendo palabras a mitad o truncando en ambigüedad es empeorar, por mucha fila que se gane. Detalle y escala completa en DESIGN.md §5 bis.

**Acotación del 2026-08-14: la excepción vale SOLO por encima de 1180 px.** La premisa que la
sostenía era literal — «no aplica aquí porque esta familia está fuera del alcance móvil del
producto por contrato»— y ese contrato **caducó** el 2026-08-14, cuando el piloto F2a-2b abrió
móvil y tablet en `/programacion-semanal` y `/programacion-intermedia`. Medido ese día a 390 px:
34 de 129 controles en Semanal y 11 de 44 en Intermedia por debajo del mínimo táctil, con
botones de acción de 28 px de alto y un conmutador de 13×13 px. **Por debajo de 1180 px rige el
mínimo táctil de 44×44 px sin excepción**; por encima se mantiene la densidad de 24 px, que
existe por una razón real (recorrer cientos de filas con ratón) y no se toca. La excepción
describe una superficie de escritorio, no una tolerancia del producto: cuando una superficie
gana alcance móvil, deja de estar cubierta. Decisión del usuario, 2026-08-14.

**Ampliación 2026-08-03 (task 22): botones de acción y chips contadores de toolbar.** La misma excepción cubre ahora los botones de acción y los chips contadores de las toolbars de `/programa-general`, `/programacion-intermedia`, `/programacion-semanal` y `/pdc` (fuera de la tabla propiamente dicha, pero parte de la misma superficie desktop-sin-móvil), vía el token `--ds-control-compact-min: 24px`. Misma razón que arriba: 44 px es AAA (SC 2.5.5, protege el acierto del dedo sobre cristal); el suelo real para esta familia es AA (SC 2.5.8) — 24×24 px, sin margen. Los chips contadores siguen siendo controles que filtran (no etiquetas): conservan afordancia, foco visible y estado activo distinguible. El foco visible se dibuja sobre la forma visual del control, nunca sobre un área invisible ampliada.
