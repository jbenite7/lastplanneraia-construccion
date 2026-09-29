---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-29
areas: [qa, design-system]
fuente: "package.json:18 (test:design-system:runtime), tests/browser/design-system-body-canvas-dark.mjs, tests/browser/design-system-unlayered-delivery.mjs, docs/design-system/unlayered-delivery-inventory.json; revisión final del frente ci-por-carriles, 2026-09-29"
resumen: "El gate «laboratorio» no revisa solo la página del laboratorio: mide el fondo oscuro de siete pantallas de producto y recorre 25 rutas, así que un PR de CSS, vistas o bundle no puede saltárselo"
---
# El gate «laboratorio» también revisa pantallas de producto

**El síntoma.** Al diseñar el CI por carriles, la spec 1.1 dejó el paso `Run laboratory gates`
(`npm run test:design-system:runtime`) encendido solo para tokens y vistas del design system. Un PR
que cambiara `public/css/styles.css`, una vista de módulo o el bundle React habría salido verde sin
correr el único gate bloqueante del job, y el rojo habría aparecido recién en `main`, tras el merge.

**Lo que parece.** Que «laboratorio» es la página `/internal/design-system`, una vista PHP que no
carga el bundle React, y que por eso solo la afectan los tokens y sus vistas. La página sí es eso.
El comando que lleva su nombre no.

**Lo que es.** `test:design-system:runtime` (`package.json:18`) encadena, además del laboratorio:
`design-system-body-canvas-dark.mjs`, que mide el fondo oscuro de siete pantallas de producto
(`/programa-general`, `/programacion-semanal`, `/programacion-intermedia`, `/indicadores`,
`/profesionales`, `/subcontratistas` y `/control-cambios`), y `design-system-unlayered-delivery.mjs`,
que recorre las 25 rutas de `docs/design-system/unlayered-delivery-inventory.json` (22 autenticadas y
3 públicas) y falla ante CSS sin capa o un 500. Es el único paso de ese job sin `continue-on-error`.

**Cómo se sale.** Cualquier filtro que omita gates por rutas debe encender este cuando el PR toque CSS,
vistas o el bundle (decisión D6 de Felipe, 2026-09-28). Un PR de solo `src/**` o de solo
`pdc-app`/`ct-app` sí lo omite, y esa brecha se acepta porque `main` corre la suite completa.

**Cuánto costó.** La spec 1.1 estuvo aprobada un día con la matriz equivocada. La atrapó la revisión
final de toda la rama, no las revisiones por tarea: cada una comprobaba contra la spec, y la spec
tenía el error. No llegó a producirse ningún merge con el filtro mal.

Relacionadas: [[qa-y-gates]] · [[design-system]] · [[el-archivo-que-tocas-puede-tener-un-contrato]] ·
[[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|spec del CI por carriles]]
