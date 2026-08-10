---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [lps]
fuente: sesion
resumen: "Last Planner en este producto: programación general, intermedia y semanal, sus estados y el cajón contextual"
---
# Mapa · Dominio LPS

## Qué manda

- [[GLOSARIO]] — vocabulario oficial. **Consúltalo antes de nombrar cualquier cosa del dominio**;
  aquí un nombre mal puesto se propaga a la base de datos y a la interfaz.
- [[docs/ESTADOS-PG-PI-PS]] — los estados de las tres programaciones, que es donde está la
  complejidad real.
- [[docs/last-planner-programacion-intermedia-estados]] y
  [[docs/last-planner-programacion-semanal-estados]] — el detalle por superficie.
- [[docs/VISTAS-MODULOS]] — qué vista corresponde a qué módulo.
- [[docs/matriz-severidad-cajon-contextual-lps]] — severidades del cajón contextual.

## Las tres programaciones

Programación General (PG), Intermedia (PI) y Semanal (PS). Comparten primitivas visuales y buena
parte de la cascada CSS, así que un cambio en una salpica a las otras — la clase
`pdc-legend-item`, por ejemplo, está compartida entre las tres y llena de `!important`
(ver [[pdc-legend-item-clase-compartida]]).

## Cajón contextual

Toda su geometría vive en `public/css/handsontable-module.css`, que `core.css` **no** importa. Si
migras un head a `renderForModule` sin declarar el vendor `handsontable`, el cajón se cae y ningún
gate lo detecta — [[drawer-en-handsontable-module]].

Para QA visual del cajón conviene `/dashboard/escalamientos`, no `/programacion-semanal`: esta
última dispara `save` y `auto-program` con solo cargar la página ([[semanal-auto-dispara-mutaciones]]).
Sembrar la bitácora con `test.A` falla por clave foránea; se intercepta con una fixture.

## Tablas

Los módulos LPS usan Handsontable. La altura de `#hot-container` la resuelve JavaScript, no CSS:
`calc(100vh - Npx)` sobre ese contenedor es siempre incorrecto — [[hot-container-height-ownership]].

## Reabrir semana

El cliente y el servidor no aplican la misma regla sobre quién puede reabrir una semana, y ninguna
de las dos es la que el producto quiere — ver [[reabrir-semana-asimetria-cliente-servidor]].

## Vecinos

[[design-system]] para tokens y cascada · [[rbac-y-rutas]] para quién puede editar qué semana.
