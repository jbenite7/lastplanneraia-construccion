---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-26
areas: [proceso, design-system, bi, pdc]
fuente: docs/superpowers/specs/2026-08-26-v0-del-producto-design.md
resumen: "La v0 del producto: qué debe estar funcionando en las 41 pantallas para que la obra trabaje sin salirse, y en qué orden. Absorbe el mapa único del trabajo vivo"
---

# La v0 del producto — Design

- **Fecha:** 2026-08-26
- **Origen:** grilleo con Felipe del 2026-08-26, sobre el mapa único del trabajo vivo publicado
  ese mismo día.
- **Qué es:** **absorbe** [[docs/superpowers/specs/2026-08-25-mapa-unico-del-trabajo-vivo-design|el
  mapa único]] —que era un inventario— y le añade lo que un inventario no tiene: **criterio de corte
  y orden**. De aquí salen planes, no specs.

## Lo que decidió Felipe, y que este documento ejecuta

Se registra primero porque es lo que gobierna todo lo demás.

| # | Decisión | Consecuencia |
|---|---|---|
| 1 | **Un solo documento**, que reemplaza el mapa y define alcance de producto | No se abren specs atómicas nuevas. Lo siguiente son planes |
| 2 | **Prioridad: que el director decida > sin deuda > móvil > ciclo completo** | El orden de las olas de abajo |
| 3 | **El móvil es deuda, no un frente aparte** | Cada pantalla se toca **una vez** y queda terminada en escritorio y celular. Tocarla dos veces es lo que se evita |
| 4 | **El tema claro entra** | Sube el costo por pantalla, se paga una vez |
| 5 | **Las 41 pantallas del censo**, no las 17 con ficha | Obliga a reconciliar los dos inventarios antes de empezar |

## El problema, en una frase

El sistema está construido y desplegado, pero **quien dirige la obra todavía no puede decidir dentro
de él**: la Torre de Control deja mirar y no deja escribir. Y de las 41 pantallas, la mayoría solo
existe para escritorio, con las tablas heredadas y sin tema claro.

## Qué significa «v0 lista»

**Que el director de obra abra el sistema en el celular o en el computador, vea qué va a matar sus
compromisos, y decida ahí mismo — sin exportar a Excel, sin pedirle a nadie que le pase un dato, y
sin que la pantalla se vea distinta según por dónde entre.**

Todo lo que no sirva a esa frase queda fuera y se declara.

## Tarea cero — sin esto, «41» no significa nada

**Hay dos inventarios del mismo sistema y no se hablan.** El censo de diseño
(`docs/design-system/auditoria/censo-modulos.json`) lista **21 módulos y 41 superficies**; las fichas
(`docs/design-system/manifests/`) son **17** y usan otros nombres: `auth` frente a `autenticacion`,
`plan-compras-v2` frente a `plan-de-compras`, `project-selector` frente a `selector-de-proyectos`.
**Ningún cruce automático entre las dos listas funciona hoy**, así que ni el alcance ni el avance se
pueden medir.

Además, ocho módulos censados **no tienen ficha**: cronograma, integración, panel de administración,
los tres submódulos de indicadores, el legado y el núcleo.

**Corrección del 2026-08-26, medida al ir a planearla: esta tarea es mucho más pequeña de lo que
este documento dijo hace veinte minutos.** El párrafo de arriba describe el síntoma —«los nombres no
coinciden»— sin haber abierto los archivos. Abiertos:

- **El censo YA enlaza a las fichas**, campo `designSystem.moduleId`. No hay mapeo que inventar: 15 de
  21 módulos lo traen.
- **Solo 2 enlaces están rotos**: `selector-de-proyectos` apunta a `projects` (la ficha se llama
  `project-selector`) y `panel-admin` apunta a `admin`, que no existe.
- **De los 6 módulos sin enlace, solo 3 tienen pantalla real** — los submódulos CNP, CNC y CIC, los
  tres bajo `/programacion-semanal`. Los otros tres (integración, núcleo y runtime, legado) tienen
  **cero superficies**: no rinden pantalla y no necesitan ficha.
- **El gate de cobertura ya existe y pasa** (`tests/design-system/coverage-closure.test.mjs`, 3/3),
  con su brecha congelada en `coverage-debt.json`: 3 pantallas sin manifiesto (`/`, `/dashboard`,
  `/reportes/{tipo}`), 5 rutas declaradas que ya no responden y 1 manifiesto sin escenario.

**Qué se hace, entonces:** arreglar los 2 enlaces rotos, resolver las 3 pantallas de submódulo y las
3 de la deuda, y añadir **el gate que hoy no existe** — el que cruza censo contra fichas. El actual
cruza rutas contra fichas, que es otro eje y deja pasar precisamente esta divergencia. Medio día, no
un frente.

**Por qué se deja escrito el error y no se borra:** es la tercera vez en esta jornada que se describe
un pendiente desde el síntoma sin abrir el archivo, y las tres veces el trabajo real resultó menor.
Borrarlo dejaría la spec limpia y sin la lección.

## Las olas

El orden sale de la prioridad 2. Cada ola cierra con su condición propia.

### Ola 1 · Que el director decida — la Torre escribe

Hoy la Torre es un mirador: hay catálogo de métricas y servicio de linaje en el fondo, pero nadie los
conectó a la pantalla. `public/index.php:337,358` solo declara lectura.

Entra: las columnas donde guardar una asignación (responsable, fecha, estado de liberación), el
endpoint de escritura con sus permisos y su protección, y el linaje llegando a la pantalla — «¿de
dónde sale este número?» con un clic y alcanzable por teclado.

**La migración de esquema NO viaja dentro de la pantalla.** Es lo más caro de revertir de todo lo que
hay aquí: va con su propio permiso explícito, respaldo probado y ensayo en seco antes de aplicar.

**Y la Torre es además el piloto que mide.** Se deja completa —escritura, escritorio, celular, tema
claro, usabilidad— y **lo que cueste es el número con el que se dimensionan las otras 40**. Hasta que
esa cifra exista, este documento no promete fecha total, a propósito.

### Ola 2 · Cada pantalla, una sola vez

Las 40 restantes, en grupos por familia, cada una terminada en la misma pasada: tabla migrada al
sistema de diseño, tema claro, comportamiento en celular a 390×844, y los hallazgos de usabilidad que
le toquen.

Lo medido hoy que entra aquí:

- **Cinco módulos siguen con la tabla heredada** (`public/js/modules/*/hot*.js`), más los adaptadores
  de DataTables y las tarjetas legadas.
- **El tema claro no existe**: cero ocurrencias de conmutador o tema en `public/css`.
- **El móvil es 1 de 17**: solo `programa-general.json` declara móvil; 15 declaran solo escritorio.
- **Seis hallazgos de usabilidad vivos**, cuatro de ellos de una línea: contraste del chip de BI
  (`public/css/bi-control-tower.css:202`), su botón de 20 px (`:218`), la etiqueta ausente en
  recuperar contraseña (`admin/views/pages/password-forgot.php:39`) y el nombre repetido en el
  selector de proyecto (`views/core/project_selector.view.php:122`). Los otros dos son producto: el
  shell de escalamientos y el formulario de control de cambios, **que hoy no existe**
  (`views/control-cambios/controlCambios.view.php:855`).

### Ola 3 · Que el ciclo cierre sin salirse

Lo que quede para que una obra planee, compre, haga seguimiento y vea indicadores sin recurrir a otra
herramienta. Va de último por decisión de Felipe, y con razón: el Plan de Compras ya está desplegado
y en uso, así que esta ola es más corta de lo que parece.

## Lo que NO entra, dicho para que nadie lo suponga

- **El despliegue a producción.** Exige autorización explícita de Felipe, cada vez. Hoy producción va
  **233 commits atrás** (`6fa3cff1`), 86 de ellos tocando código, con dos migraciones pendientes.
  Esa decisión es suya y no la toma este documento.
- **Las 27 actividades con el acumulado corto en producción.** Medidas, no se reparan solas y
  necesitan revisión manual. Tienen dueño propio en `TASKS.md`.
- **Reescribir el legado.** Se migra lo que una pantalla necesita para quedar terminada, nada más.

## Riesgos

- **El riesgo mayor es prometer 41 sin saber qué cuesta 1.** Por eso la Ola 1 es piloto además de
  entrega, y por eso aquí no hay fecha total. Comprometerse antes de medir es como nacen los planes
  que otro documento da por pendientes meses después — y este repo tiene la evidencia.
- **Este documento va a envejecer, y ya se sabe cómo.** El mapa que absorbe envejeció **en horas**:
  otra sesión cerró un frente y le dejó cuatro pendientes que no recogía. La mitigación no es
  escribir mejor, es que **actualizar este documento sea un paso del cierre de cada frente**, no una
  buena intención. Va en la condición de hecho.
- **La pantalla de Actualizar Programa General pisa el avance sin avisar** en toda edición, no solo
  al cambiar la asociación (`TASKS.md:56`). Merece decisión propia antes de que la Ola 2 la toque.

## Condición de hecho

1. Existe **una** lista canónica de 41 pantallas, con ficha cada una, y un gate que falla si los dos
   inventarios divergen.
2. El director de obra asigna responsable y fecha a una restricción **sin salir de la Torre**, y
   puede preguntar de dónde sale cualquier número con un clic.
3. La migración de la Ola 1 se aplicó con autorización explícita, respaldo probado y ensayo en seco,
   y su reversa está escrita.
4. Cada pantalla cerrada lo está **en escritorio y en celular a 390×844, en tema oscuro y claro**, con
   su tabla migrada. No hay pantallas «a medias» esperando una segunda pasada.
5. **Existe el número**: cuánto costó dejar la primera pantalla completa, medido y escrito, y las
   olas siguientes están dimensionadas con él.
6. Cerrar cualquier frente incluye actualizar este documento. Un frente que no lo actualizó no está
   cerrado.

## El enfoque, aprobado

**Felipe aprobó el enfoque C el 2026-08-26: una pantalla completa primero, y que ella mida.** La
Torre se deja terminada de punta a punta —que escriba, en computador y celular, en oscuro y claro,
con su usabilidad resuelta— y **lo que cueste se anota**: ese número dimensiona las otras cuarenta.

Los dos descartados, con su motivo, para que nadie los reabra sin saber por qué se cayeron:

- **Barrer por familias.** Ordenado y con avance visible desde el primer día, pero compromete con 41
  pantallas sin saber qué cuesta una. Es como nacen los planes que otro documento da por pendientes
  meses después.
- **Cimientos primero** (tema claro y celular como capacidad del sistema, antes de aplicarlos). Suena
  eficiente y es el peor de los tres: unos cimientos sin una pared real encima suelen no encajar
  cuando llega la primera, y son meses en que nadie en obra nota nada — que es cuando un proyecto
  pierde el apoyo que lo sostiene.
