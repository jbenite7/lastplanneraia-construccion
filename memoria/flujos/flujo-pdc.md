---
tipo: flujo
estado: vigente
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: docs/pdc-v2.md
resumen: "El flujo del Plan de Compras v2: del presupuesto al seguimiento, y qué módulo cubre cada paso"
---
# Flujo del Plan de Compras

El recorrido del PDC v2 según [[pdc-v2|docs/pdc-v2.md]], que es la fuente que manda. El mapa del
área es [[pdc]].

Todo arranca con el **presupuesto**, que se carga y se versiona; activar una versión es la decisión
que fija contra qué se compra. De ahí salen los insumos del **maestro de insumos**, que se depuran,
vinculan y clasifican. Luego los insumos se agrupan en **paquetes de contratación**: parte se asigna
sola y parte la decide una persona. Cada paquete recibe entonces su **plan con fechas**, los pasos
del proceso de contratación con sus duraciones, amarrados a las actividades de obra. Con el plan ya
en operación llega el **seguimiento**: vencimientos, desfases y flujo de caja.

Todo eso vive en **[[plan-de-compras]]** (SPA React, sub-router por hash).
**[[listado-de-actividades]]** y **[[contratos]]** eran la superficie y el motor semiautomático del
PDC v1: se eliminaron del repositorio el 2026-08-04 y no tienen sucesor directo en v2 (ver
[[semi-auto-solo-lo-usa-pdc]]). **[[subcontratistas]]** aporta a quién se contrata y
**[[torre-de-control-bi]]** lo mira desde arriba.

El flujo de programación corre al lado: ver [[flujo-lps]].
