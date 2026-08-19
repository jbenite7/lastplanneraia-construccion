---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-revision-ux/facts.md
resumen: Treinta hechos verificables, agrupados por el plan que los cumple. Aceptados el 2026-07-28.
---

# Hechos aceptados — revisión de UX del módulo

Treinta hechos verificables, agrupados por el plan que los cumple. Aceptados el 2026-07-28.

> **Tres hechos se corrigieron después de explorar el código** (f11, f16, f26): sus premisas
> resultaron falsas. El texto anterior queda en `facts.meta.json` como `texto_anterior`.


## Plan 1 — Arreglos de tabla (visible, sin tocar la base)

- **f01** — En todas las tablas del módulo, las columnas de cifras, fechas y unidades muestran su contenido completo en un solo renglón: nunca aparecen recortadas con «…». `[auto]`
- **f02** — Las columnas de texto largo (nombre de insumo, de paquete, de archivo) parten el texto en varios renglones en lugar de recortarlo. `[auto]`
- **f03** — El ancho de cada columna se ajusta a lo que contiene, y no a un número fijo escrito en el código. `[auto]`
- **f04** — Un solo clic sobre una casilla editable entra en modo edición; ya no hace falta el doble clic. `[auto]`
- **f05** — En la tabla del Plan, hacer clic en una columna que NO se edita sigue abriendo el detalle de los siete pasos, como hoy. `[auto]`
- **f06** — La pantalla de Paquetes abre mostrando «Sin asignar» cuando queda al menos un insumo pendiente, y muestra «Todos» cuando ya no queda ninguno. `[auto]`
- **f07** — Los dos botones «Sembrar 1ª iteración» y «Auto-asignar lo seguro» quedan sustituidos por uno solo, con un nombre que se entiende sin explicación. `[auto]`
- **f08** — Ese botón único solo PROPONE destinos: no guarda ninguna asignación hasta que la persona confirma. `[auto]`
- **f09** — Junto al botón «Recalcular» hay un texto siempre visible que dice qué conserva (responsables, amarres y avance) y qué cambia (las fechas). `[auto]`

## Plan 2 — Lo que falta poder hacer (toca base y reglas)

- **f10** — Un paquete ya amarrado se puede desamarrar, y al hacerlo vuelve a la lista «Sin frente». `[auto]`
- **f11** — El responsable asignado a un paquete se conserva SIEMPRE: ni al desamarrar ni al cambiar de frente se pierde. (Corrige de paso un borrado silencioso que existe hoy: reamarrar a otro frente borra el responsable sin avisar.) `[auto]`
  - *Corregido: Corregido tras explorar el código: la premisa original era falsa.*
- **f12** — Al desamarrar, las fechas calculadas de ese paquete se borran: sin frente no queda ninguna fecha que pueda leerse como vigente. `[auto]`
- **f13** — Puede desamarrar exactamente quien puede amarrar; no hay ningún permiso nuevo. `[auto]`
- **f14** — Se puede cambiar el frente de un paquete ya amarrado directamente desde la tabla, sin tener que desamarrarlo primero. `[auto]`
- **f15** — Desde el historial de versiones se puede marcar a mano cuál presupuesto es el oficial, en vez de que lo sea siempre el último cargado. `[auto]`
- **f16** — Al cambiar la versión oficial, el sistema avisa qué quedará afectado —los vínculos del maestro hechos sobre la versión que se abandona— y deja decidir. No bloquea: las asignaciones a paquete y el plan de fechas no dependen de la versión y sobreviven solos. `[auto]`
  - *Corregido: Corregido tras explorar el código: la premisa original era falsa.*
- **f17** — Sí se puede pasar a una versión NUEVA aunque haya trabajo hecho: importar y adoptar un presupuesto más reciente sigue siendo posible. `[auto]`
- **f18** — Solo quien tiene permiso para importar presupuestos puede cambiar cuál es el oficial. `[auto]`
- **f19** — Fijar la versión oficial pide confirmación antes de aplicarse, porque cambia la base de todo lo demás. `[auto]`
- **f20** — El visor del presupuesto tiene un selector para elegir hasta qué nivel se ve: capítulo, subcapítulo, grupo, actividad o insumo. `[auto]`
- **f21** — El visor abre por defecto desplegado hasta el nivel de insumos, sin que haya que ir abriendo carpetas a mano. `[auto]`
- **f22** — El comparador de versiones tiene el mismo selector de nivel y abre igual de desplegado que el visor. `[auto]`
- **f23** — Hacer clic en una versión del historial lleva directo al visor con esa versión ya cargada, sin preguntar nada por el camino. `[auto]`
- **f24** — En el historial se pueden marcar hasta DOS versiones; al intentar marcar una tercera, la casilla no lo permite. `[auto]`
- **f25** — Con dos versiones marcadas, el botón «Comparar» lleva al comparador con esas dos ya enfrentadas. `[auto]`

## Plan 3 — Rediseño de navegación

- **f26** — El módulo vive dentro del shell del sistema de diseño y la barra lateral le da su entrada propia (hoy el enlace del sidebar apunta al módulo viejo). La navegación entre las seis pantallas se queda dentro del módulo, rediseñada como pestañas con los tokens del sistema — la barra lateral no admite anidamiento y las rutas con almohadilla no llegan al servidor, así que no puede marcar pantalla activa. Mismo patrón que ya usa Control Tower. `[manual]`
  - *Corregido: Corregido tras explorar el código: la premisa original era falsa.*
- **f27** — La pantalla del cargue de Excel se llama «Cargar presupuesto», y la palabra «Ensamble» queda libre para nombrar la etapa completa. `[auto]`
- **f28** — Maestro, Paquetes y Plan organizan sus tablas en pestañas dentro de la pantalla: se ve de entrada cuántas secciones hay y se salta a cualquiera sin desplazarse. `[auto]`
- **f29** — Ninguna tabla del módulo queda escondida debajo de otra: no hay que hacer scroll para descubrir que existía. `[manual]`

## Transversal

- **f30** — Nada de lo que ya funciona se rompe: la suite de PHP sigue en 0 fallos, la de la interfaz en verde, y el indicador de acierto del motor de paquetes sigue en 7 diferencias. `[auto]`
