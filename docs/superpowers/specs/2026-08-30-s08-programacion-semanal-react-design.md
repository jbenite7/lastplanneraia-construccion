---
capa: fuente
tipo: spec
estado: vigente
id: S08
fecha: 2026-08-30
superficie: programacion-semanal
rutas: ["/programacion-semanal"]
depende_de: [T01, T02, S05, S06, S07]
views: [VIEW-39, VIEW-40, VIEW-41]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramacionSemanalController, SemanalApiController, CncApiController, ProgramChangeDetector, CommitmentLockGuard, LpsWeekEditPolicy, SemanalReabrirPolicy, LpsService, ReportController, VIEW-39/40/41, hot.js, stateMachine.js, changeMonitor.js, CSS, manifiestos, pruebas, RBAC, specs S05-S07 y frontend actual en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S08 de Programación Semanal a React: programación y calificación, compromisos, avance real, CNC, TNP, actividad manual, conciliación/autoprogramación explícita, cierre/reapertura, log, filtros, CSV/XLSX, drawer, tabla y tarjetas accesibles en oscuro/claro, sin cambiar RLS, schema ni datos durante la fase documental."
---

# S08 — Programación Semanal en React

> **Estado:** diseño técnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que impidan escribir el plan. Esta spec no autoriza implementación, commits, DDL/DML, cambios
> RLS, cambios de permisos, deploy, publicación ni trabajo en `/admin/`. Su plan se escribe a
> continuación con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27
> planes.

## Relación con el programa

Esta spec continúa las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migración React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/superpowers/specs/2026-08-29-programa-general-react-design|S05 — Programa General React]];
- [[docs/superpowers/specs/2026-08-30-s06-actualizar-cronograma-react-design|S06 — Actualizar Cronograma React]];
- [[docs/superpowers/specs/2026-08-30-s07-programacion-intermedia-react-design|S07 — Programación Intermedia React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

T01 posee sesión, proyecto, semana, sidebar, tema, navegación y la recuperación de contexto. S05
posee la identidad canónica de actividad y el primer adaptador del drawer. S06 posee el ciclo que
crea o actualiza las semanas del cronograma. S07 posee la configuración compartida de restricciones
de Construcción/Preconstrucción. T02 será dueño del drawer React compartido. S08 posee el plan
semanal de actividades, sus dos fases, sus mutaciones y la integración contextual de una fila.

S08 no absorbe las superficies satélite. S09 posee CNP, S10 CNC y S11 CIC. Mientras cada una no haya
cortado, la navegación desde S08 conserva sus rutas legacy autorizadas. El CNC exigido al guardar un
avance incumplido y la CNP exigida al desprogramar sí pertenecen a S08 porque son precondiciones de
sus propias mutaciones, no sustitutos de las vistas completas S09/S10.

VIEW-39 es `views/programacion-semanal/partials/_changeMonitorModal.php`, VIEW-40 es
`views/programacion-semanal/partials/modal_reabrir.php` y VIEW-41 es
`views/programacion-semanal/programacion_semanal.view.php`. Esas tres piezas son propiedad de S08.
`views/partials/drawer_unificado.php` es VIEW-28 y pertenece a T02; no se elimina al cortar S08.

## Resultado buscado

`/programacion-semanal` será una superficie React que conserva, como mínimo, toda capacidad útil y
comportamiento observable del módulo PHP/JS actual:

1. usa exclusivamente el proyecto y la semana activos del shell;
2. distingue la fase de programación de la fase de calificación según el estado servidor de la
   semana;
3. carga actividades semanales, proyecciones, restricciones, asignaciones y cantidades con tipos
   estables;
4. presenta los diez estados operativos de ambas fases y un estado neutral de fila no activa;
5. permite buscar, combinar filtros, contar resultados y restablecer la vista sin mutar sesión;
6. muestra una tabla semántica responsive en desktop/tablet y tarjetas plenamente editables en
   móvil;
7. edita descripción, ubicación, subcontratista, Responsable AIA y compromiso durante programación;
8. edita avance real durante calificación y obliga CNC cuando el resultado queda bajo el
   compromiso;
9. valida números localizados, precisión, límites de porcentaje/cantidad y suma entre divisiones de
   una misma actividad tanto en cliente como en servidor;
10. guarda individualmente con recuperación de error y devuelve la fila completa recalculada;
11. permite agregar y duplicar una actividad manual, y desprogramar una actividad activa con CNP;
12. registra Trabajo No Planificado durante calificación con su Causa de Programación;
13. previsualiza y aplica explícitamente la conciliación/autoprogramación, sin escribir al abrir la
   pantalla;
14. presenta el log de la última conciliación y las alertas por restricciones o cambios;
15. previsualiza bloqueos, confirma compromisos, genera CIC y permite reapertura autorizada con
   motivo;
16. recarga, exporta CSV en cualquier layout y genera el corte XLSX autorizado;
17. explica estados, colores, restricciones y acciones mediante una leyenda y el drawer contextual;
18. maneja carga, vacío real, filtros sin resultados, sólo lectura, conflicto, error y recuperación;
19. ofrece la misma capacidad en oscuro y claro y es operable con teclado, zoom y lector de
   pantalla.

Paridad no obliga a conservar Handsontable, jQuery, Bootstrap, Select2, Font Awesome, globals,
HTML inyectado, endpoints multiplexados, rutas que reciben `db`, creación de tablas en runtime,
mutaciones al cargar ni defectos contradictorios. React conserva intención, datos, autorizaciones,
efectos y salidas; puede corregir seguridad, integridad, accesibilidad y recuperación.

## Alcance

### Incluido

- Ruta piloto y ruta canónica React de Programación Semanal.
- VIEW-39, VIEW-40 y VIEW-41, incluidos todos sus diálogos y acciones.
- Contexto tipado de proyecto/semana/fase, catálogos, acciones efectivas, enlaces y CSRF.
- Lista estable de actividades, sin `SELECT *`, HTML ni tipos ambiguos.
- Configuración de restricciones de S07 y proyecciones de la semana calculadas en servidor.
- Once estados semánticos: cinco de programación, cinco de calificación y uno neutral.
- Búsqueda, filtros combinables, chips de alerta, conteos totales/visibles y leyenda.
- Edición individual de planificación o calificación según fase y acción efectiva.
- Validación server-side de compromiso, real, presupuesto, porcentaje, asignaciones y CNC.
- Actividad manual, división por subcontratista, duplicación y desprogramación/CNP.
- Candidatos y registro transaccional de TNP con categorías por área.
- Preview puro y apply explícito de conciliación/autoprogramación, saneo y cambios en cascada.
- Log de la última ejecución sin DDL runtime.
- Preview de cierre, confirmación transaccional, CIC y reapertura auditada.
- CSV local y corte XLSX detrás de permiso de reporte.
- Tabla desktop/tablet y tarjetas móviles con capacidad equivalente.
- Integración del drawer T02 con diagnóstico semanal, comentarios, respuestas, menciones, SOS y
  crisis.
- Oscuro/claro, foco, live regions, reduced motion, zoom 200 % y targets táctiles.
- Contratos PHP, Zod, pruebas puras y navegador con red completamente interceptada.
- Convivencia legacy durante piloto y retiro exclusivo después del corte canónico.

### Fuera de alcance

- `/admin/` y cualquier ruta, permiso, dependencia, vista o estilo administrativo.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, columnas, índices, triggers, grants,
  usuarios, credenciales, membresías, roles, overrides o datos.
- Ejecutar DDL/DML durante esta fase documental o durante la verificación indicada en el plan.
- Crear la tabla `auto_program_log`, agregarle `project_id`/`unique_id` o reparar su schema en
  runtime. La migración global vigente es una precondición; una ausencia falla de forma segura.
- Migrar CNP, CNC o CIC como superficies completas. S09–S11 conservan esa propiedad.
- Cambiar la definición de semana, crear semanas o recalcular el cronograma. Corresponde a S06.
- Editar restricciones de PI/PG desde S08. S08 sólo lee su resultado para readiness/estado.
- Crear subcontratistas o profesionales faltantes durante el cierre. S13/S14 poseen sus catálogos.
- Insertar contactos ficticios como `placeholder@example.com` para hacer pasar una FK.
- Cambiar las categorías de CNP, CNC o CP existentes; sólo se normaliza su entrega.
- Agregar locks optimistas, tablas de drafts, colas o auditoría nuevas.
- Reescribir las métricas BI, reportes CNP/CNC/CIC o control de cambios fuera del corte semanal.
- Retirar endpoints/drawer/`legacyCards.js` compartidos mientras S09–S11 u otro módulo los consuma.
- Regenerar o aprobar goldens visuales sin autorización explícita.

## Punto de partida medido

### React

- No existe página, módulo, esquema Zod, gateway ni dominio de Programación Semanal.
- La sidebar sólo navega a la vista legacy.
- El router autenticado sirve el shell en `/app/`, pero no reconoce una ruta S08 nativa.
- `frontend/src/lib/api/cliente.ts` es la única frontera permitida para HTTP; ningún componente S08
  llamará `fetch`.
- T01 ya aporta proyecto/semana/tema. S08 no recrea selectores ni matrices locales de roles.

### Legacy

| Pieza | Medición auditada |
|---|---|
| Vista principal | VIEW-41, 591 líneas |
| Modal log | VIEW-39, 54 líneas |
| Modal reapertura | VIEW-40, 123 líneas |
| Controlador página | `ProgramacionSemanalController`, 199 líneas |
| API principal | `SemanalApiController`, 1.559 líneas |
| Motor de cambios | `ProgramChangeDetector`, 500+ líneas y DDL runtime |
| Interacción principal | `hot.js`, 5.179 líneas |
| Máquina de estados | `stateMachine.js`, 270 líneas |
| Monitor | `changeMonitor.js`, 377 líneas |
| Presentación | `programacion-semanal.css`, 3.814 líneas |
| Monitor CSS | `change-monitor.css`, 178 líneas |
| Grid | Handsontable, 24 propiedades y columnas ocultas |
| Responsive | tabla a partir de 1180 px; tarjetas bajo 1180 px |
| Evidencia visual | dark en 1180×820/1440×900; no hay golden light aprobado |

La vista carga jQuery, Bootstrap, Handsontable, Select2, globals de sesión, un drawer compartido y
scripts con estado duplicado. La API multiplexa diez operaciones bajo `opcion`, acepta el prefijo de
tabla desde el navegador, mezcla lectura/escritura/reportes y expone mensajes de excepción. La
clasificación existe en PHP y JavaScript con diferencias; el reporte posee una tercera variante.

### Propiedad visual y satélites

S08 retira sólo VIEW-39/40/41 y sus JS/CSS exclusivos. Las vistas `CNP.view.php`, `CNC.view.php` y
`CIC.view.php`, `legacyCards.js`, sus controladores y estilos sobreviven hasta S09/S10/S11. Durante
esa convivencia, los enlaces de S08 apuntan a `/programacion-semanal/cnp`,
`/programacion-semanal/cnc` y `/programacion-semanal/cic`; nunca vuelven a
`/legacy/cambiar_pagina.php` desde React.

## Comportamiento observable auditado

### Fases

`Semanal_Confirmada=0` produce **programación**. Se editan asignaciones, descripción, ubicación y
compromiso; se ofrecen autoprogramación, actividad manual, duplicar, desprogramar y confirmar
compromisos. El avance real no se edita.

`Semanal_Confirmada=1` produce **calificación**. La planificación queda bloqueada; un editor con
capacidad de calificar registra avance real, CNC y TNP. La reapertura aparece únicamente cuando la
política específica la permite. La fase no se deduce en React: llega en contexto y se revalida en
cada mutación.

### Campos y acciones

| Dato/acción | Legacy observado | S08 React |
|---|---|---|
| Identidad | `row_id`/`Consecutivo`, `unique_id`, `Id` mezclados | `rowId`, `sourceActivityId`, `activityCode` distintos |
| Actividad | HTML posible | texto seguro y búsqueda normalizada |
| Descripción/ubicación | editables pero descripción no tiene columna visible | editor de detalle en tabla y tarjeta |
| Subcontratista | primer valor de una cadena; split por filas | selector de un catálogo y filas separadas |
| Responsable AIA | editable en programación | selector tipado, obligatorio para compromiso/real |
| Unidad/presupuesto | `%` fallback o cantidad física | unidad y límite explícitos |
| Ejecutado PG | ratio 0..1 | porcentaje de avance acumulado, sólo lectura |
| Fin de semana | proyección en servidor | valor tipado y explicación |
| Cantidad sugerida | proyección × 100 o presupuesto | server DTO; comparación informativa |
| Compromiso | >0, autosave | >0, una decimal, guardado explícito/individual |
| Ejecutado real | >=0 en calificación | >=0, una decimal, validación de techo |
| PAC / completado | servidor al guardar | servidor, nunca editable |
| Estado | JS/PHP divergentes | resolver único PHP, React sólo presenta |
| Duplicar | crea fila manual sin compromiso | confirmación y respuesta con fila nueva |
| Eliminar | hard delete manual o desprograma con CNP | diálogo semántico según origen |
| Drawer | sólo se inicializa con HOT | disponible en tabla y tarjeta |

### Validación cuantitativa

Los valores admiten punto o coma al introducirse, pero el JSON canónico usa números. La precisión
visible y persistida es de una decimal.

- Compromiso normal: finito y `> 0`.
- Ejecutado real normal: finito y `>= 0`; vacío significa sin calificar.
- TNP: ejecutado real finito y `> 0`.
- Para unidad `%`, `Ejecutado acumulado × 100 + suma de compromisos de todas las filas con el mismo
  sourceActivityId <= 100`.
- Para unidad física con presupuesto positivo, `Ejecutado acumulado × presupuesto + suma de
  compromisos del mismo origen <= presupuesto`.
- La misma regla aplica a la suma de avances reales normales.
- Filas de otro proyecto, semana u origen no participan en la suma.
- El servidor vuelve a leer todas las divisiones dentro de la transacción; el cálculo cliente sólo
  anticipa el mensaje.
- Un compromiso `0` no desprograma por accidente: abre el flujo CNP.
- Un real menor al compromiso exige categoría CNC y una causa estándar o una observación. “Otra”
  exige observación.
- Un real que cumple o supera el compromiso limpia categoría, causa y observación CNC y calcula
  `PAC=1`; un incumplimiento calcula `PAC=0` y `P_Completado=real/compromiso`.
- Asignaciones, presupuesto, acumulado, PAC, porcentaje y estado se vuelven a derivar en PHP; el
  cliente no puede enviarlos como autoridad.

### Proyección sugerida

S08 conserva la fórmula de `LpsService::calculateWeeklyProjections`: calcula el solapamiento de
fechas de la actividad con la semana, distribuye el saldo por días restantes, limita el ratio a
`0..1` y deriva `Ejecutado_Fin_Semana`. La cantidad sugerida es ese ratio por 100 cuando no hay
presupuesto positivo, o por la cantidad presupuestada cuando existe. Fechas inválidas no se
silencian como cero: la fila devuelve una advertencia diagnóstica y no autoriza compromiso hasta que
el dato fuente sea válido.

### Actividad manual, duplicación y CNP

La bandeja manual conserva el universo legacy: actividades de Programa Consolidado de la semana,
sin título, `Semanas_Inicio` entre 1 y 6, ejecución `<=0.001`, sin filtrar por estado ni readiness.
Cada candidato incluye restricciones duras pendientes o “Lista para autoprogramar”.

Crear exige candidato por identidad estable, actividad, subcontratista, Responsable AIA y
compromiso válido; descripción es opcional. Si el origen contiene varios subcontratistas, se crean
filas separadas: sólo la primera recibe el compromiso y el resultado enumera todas. Cada par
semana/origen/subcontratista es único. Las filas son `Activa=NA`, preservan unidad, presupuesto,
fechas y código de origen y sincronizan carryover dentro de la misma transacción.

Duplicar crea una fila manual con los detalles de la fila origen y sin compromiso ni avance. Una
fila manual sin resultados se elimina físicamente después de confirmar. Una fila activa se
desprograma de forma lógica y requiere categoría y causa CNP; Responsable/empresa/observaciones se
guardan en el mismo acto. La categoría depende del área:

- Construcción: Programación, Mano de Obra, Materiales, Equipos, Diseños, Administrativas y Causas
  Exógenas.
- Preconstrucción: Diseños, Modelación, Presupuesto, Contratación y Trámites.

El catálogo de causas se entrega como árbol tipado desde la fuente existente; S08 no usa
`/api/cnc/reasons` con un `area` ignorado ni mezcla semántica CNP/CNC en el frontend.

### Trabajo No Planificado

TNP sólo existe en calificación y requiere una acción efectiva específica. Los candidatos conservan
el universo legacy: actividades del Programa Consolidado sin título, entre semanas de inicio 1 y
12, ejecución base cero y no presentes o inactivas en Programación Semanal. La respuesta distingue
“previamente programada”.

El payload usa `sourceActivityId`, ejecutado real positivo, categoría CP obligatoria, detalle CP de
hasta 255 caracteres y observaciones de hasta 500. Las categorías por área permanecen exactamente:

| Construcción | Preconstrucción |
|---|---|
| Buen Rendimiento | Buen Rendimiento |
| Oportunidad Detectada | Oportunidad Detectada |
| Mano de Obra Disponible | Diseños Listos |
| Materiales Disponibles | Modelación BIM Disponible |
| Equipos Disponibles | Presupuesto Disponible |
| Diseños Listos | Contratación Disponible |
| Gestión Resuelta | Trámites Resueltos |
| Condiciones Favorables | Condiciones Favorables |
| Compensación de Frente | Compensación de Frente |

Si ya existen filas semanales para el origen candidato, el servicio conserva el alcance multi-fila
legacy y actualiza todas las filas del origen en esa semana; la respuesta devuelve `affectedRows` y
la lista completa, para que el efecto nunca quede oculto. Si no existen, crea una fila TNP. En ambos
casos normaliza la representación incoherente actual a `Compromiso=null`, `PAC=null`, `Es_TNP=true`,
de modo que el estado resulte `cal-tnp`. La operación completa es transaccional. Esto corrige la
contradicción actual donde el botón aparece en calificación pero
`CommitmentLockGuard::guard(..., 'tnp')` bloquea semanas confirmadas.

## Máquina de estados canónica

`docs/design-system/state-semantics.json` y los umbrales de `RestrictionConfigResolver` son la
semántica visual vigente. El documento legacy `docs/last-planner-programacion-semanal-estados.md`
está incompleto y debe actualizarse o marcarse como reemplazado durante el corte.

### Estado neutral

`ps-no-activa` se asigna antes de cualquier fase cuando `Activa` es vacío, `NA`, `0`, `N`, `NO` o
`FALSE`; también a una actividad terminada en programación. Es neutral y no decide autorización:
`rowActions` aún puede permitir editar una fila manual. El estado explica si es manual, CNP o
inactiva.

### Programación

| ID | Etiqueta | Regla en orden | Severidad |
|---|---|---|---|
| `prog-ejecucion-con-restricciones` | Ejecución con restricciones | ejecutado > .001 y restricción dura pendiente | urgente/naranja |
| `prog-bloqueo-critico-sin-compromiso` | RC con restricciones | restricción dura pendiente y ruta crítica | urgente/rojo |
| `prog-condiciones-pendientes` | Condiciones Pendientes | restricción dura pendiente, no crítica | atención/ámbar |
| `prog-sin-compromiso` | Por Comprometer | sin compromiso positivo o faltan asignaciones | atención/violeta |
| `prog-lista-para-confirmar` | Lista para Confirmar | activa, habilitada, asignada y comprometida | saludable/verde |

La ejecución ya iniciada no desaparece por readiness, pero se advierte. En Construcción son duras
D. y Especificaciones, Materiales, Mano de Obra y Equipos al 100 %, y Predecesora al 50 %. En
Preconstrucción sólo `restriccion_pc_1`/Predecesora al 50 % es dura. `N/A` cumple. Las restricciones
blandas nunca bloquean autoprogramación ni cierre.

### Calificación

| ID | Etiqueta | Regla en orden | Severidad |
|---|---|---|---|
| `cal-tnp` | Trabajo No Planificado | compromiso vacío y real >0 | neutral/azul |
| `cal-sin-calificar` | Sin Calificar | compromiso vacío o real vacío | atención/neutral |
| `cal-incumplida-critica` | Incumplida (RC) | real < compromiso y ruta crítica | urgente/rojo |
| `cal-incumplida` | Incumplida | real < compromiso y no crítica | atención/ámbar |
| `cal-cumplida-control` | Cumplida Control | real >= compromiso | saludable/verde |

El orden es parte del contrato. React recibe `{id,label,shortLabel,severity,tone,explanation,
nextActions}` y jamás vuelve a clasificar con valores crudos.

## Autoprogramación y monitor de cambios

### Comportamiento legacy observado

Existen tres caminos solapados:

1. `opcion=autoprogramar` inserta elegibles, refresca detalles, elimina no elegibles sin compromiso o
   real, sincroniza flags y devuelve alertas.
2. `opcion=sanear` se dispara automáticamente, intenta reconciliar cambios y devuelve `OK` incluso
   cuando falla.
3. `POST /api/semanal/auto-program` se ejecuta al cargar, borra duplicados/huérfanos, compromete,
   descompromete, crea CNP, registra log y notifica.

El tercer camino llama `ProgramChangeDetector::ensureLogTable()` tanto para aplicar como para leer:
ejecuta `CREATE TABLE IF NOT EXISTS` y puede ejecutar `ALTER TABLE`. Además la llamada automática no
envía CSRF. Ese comportamiento no se traslada a React.

### Decisión S08

S08 concentra los tres caminos en un servicio determinista con dos fases:

1. **Preview puro:** se ejecuta al cargar sólo después de lista/contexto; no escribe, no crea schema
   y devuelve un plan tipado, conteos, alertas y un `snapshotHash` de los hechos relevantes.
2. **Apply explícito:** el usuario autorizado revisa y confirma; el servidor revalida scope, semana,
   fase, permiso, snapshot y cada operación, aplica todo en una transacción, sincroniza carryover y
   flags, registra un batch y devuelve filas/log actualizados.

El plan puede contener `insert`, `refresh`, `reactivate`, `deprogram`, `insertCnp`, `removeOrphan` o
`deduplicate`. Conserva estas inmunidades:

- nunca toca filas manuales `Activa=NA`;
- no reactiva una CNP voluntaria;
- no desprograma una fila reactivada voluntariamente;
- no borra filas con compromiso positivo o avance real;
- una ejecución base > .001 mantiene la actividad aunque haya restricciones pendientes;
- una fila huérfana o duplicada se muestra de forma explícita antes de retirarse;
- una restricción blanda sólo informa;
- aplicar dos veces sobre el mismo snapshot es idempotente o devuelve conflicto, nunca duplica.

El monitor muestra el último batch existente con actividad, tipo, acción, detalle, CNP y fecha. Leer
un log ausente devuelve vacío si no hubo batch; un schema ausente devuelve
`SCHEMA_PREREQUISITE_MISSING` y no intenta repararlo. La acción “Actualizar” sólo vuelve a leer.

## Cierre, CIC y reapertura

### Preview de cierre

Antes de confirmar, el servidor devuelve resumen y blockers por `rowId`, código, actividad y
razones. Una fila activa bloquea cuando:

- no tiene compromiso positivo;
- falta subcontratista;
- falta Responsable AIA;
- viola el techo cuantitativo;
- su identidad/catálogo requerido para CIC es inconsistente.

La UI puede filtrar “Sólo bloqueos”, lleva foco a una fila y no presenta el botón final mientras
haya blockers. No usa una lista ambigua de `Id` que puede repetirse.

### Apply de cierre

El apply revalida el preview/snapshot, deriva fecha/actor en servidor, marca la semana confirmada,
genera o actualiza CIC para los subcontratistas y recalcula PAC/completado en una sola transacción.
No acepta `fechaCierreCompromisos` del navegador. No inventa subcontratistas, correos, NIT ni
credenciales; una referencia sin catálogo vuelve a ser blocker. Un fallo deja la semana abierta.

### Reapertura

Conserva `SemanalReabrirPolicy`:

- A y D pueden reabrir dentro o fuera del plazo;
- R sólo hasta finalizar la semana de la fecha de inicio y falla cerrado si la fecha no se resuelve;
- DCV y demás perfiles no pueden reabrir.

El motivo normalizado exige 20–500 caracteres. La operación exige edición, CSRF, semana confirmada y
política específica; desconfirma, limpia fecha de cierre y audita actor/rol/motivo en transacción.
React sólo usa `actions.reopenWeek`; no calcula rol ni reloj.

## Inventario HTTP auditado

| Método | Ruta actual | Contrato/uso actual | Disposición S08 |
|---|---|---|---|
| GET | `/programacion-semanal` | VIEW-41; sólo autenticación | piloto React, luego canónica con permiso de lectura |
| GET/POST | `/api/semanal/list` | `SELECT ps.*`, `db`/semana cliente, tipos mixtos | alias legacy; nueva lista tipada/scoped |
| POST | `/api/semanal/save` | diez opciones bajo `opcion` | aliases; reemplazar por mutaciones estrechas |
| POST | `/api/semanal/reabrir` | `db`, semana, motivo, CSRF | alias; wrapper scoped y JSON estricto |
| GET | `/api/semanal/tnp-actividades` | candidatos con `db`/semana cliente | alias; candidatos scoped |
| POST | `/api/semanal/auto-program` | muta al cargar sin CSRF | retirar; preview puro + apply explícito |
| GET | `/api/semanal/auto-program-log` | lee y ejecuta DDL runtime | retirar; lectura pura |
| POST | `/api/cnc/reasons` | catálogo global por categoría | no consumir desde S08; contexto tipado |
| POST | `/reportes/compromisos` | `db`/semana, genera archivo y `{url}` | alias; wrapper scoped/CSRF |
| GET/POST | `/api/lps/comments*` | drawer compartido | conservar; T02 tipa |
| POST | `/api/lps/crisis/register` | SOS/crisis | conservar; `modulo=PS` |
| POST | `/api/lps/crisis/close` | cierre crisis | conservar; T02 tipa |
| GET | `/api/session` | sesión/shell/proyecto/semana | T01; S08 consume |
| POST | `/context/week` | cambia semana activa | T01; S08 reinicia datos/drafts |
| GET | `/programacion-semanal/cnp` | S09 legacy/React futuro | navegación contextual |
| GET | `/programacion-semanal/cnc` | S10 legacy/React futuro | navegación contextual |
| GET | `/programacion-semanal/cic` | S11 legacy/React futuro | navegación contextual |
| GET | `/bi/programacion-semanal` | BI semanal | navegación si servidor autoriza |

## Contratos HTTP nuevos

S08 crea exactamente estos límites. Ninguno recibe `db`, prefijo, `project_id`, proyecto, área,
usuario, rol, permiso, fase, máximo de semana ni fecha de cierre desde React.

```text
GET  /api/programacion-semanal/context
GET  /api/programacion-semanal/activities
POST /api/programacion-semanal/activity
GET  /api/programacion-semanal/manual-candidates
POST /api/programacion-semanal/manual-activity
POST /api/programacion-semanal/activity/duplicate
POST /api/programacion-semanal/activity/deprogram
GET  /api/programacion-semanal/tnp-candidates
POST /api/programacion-semanal/tnp
GET  /api/programacion-semanal/reconciliation/preview
POST /api/programacion-semanal/reconciliation/apply
GET  /api/programacion-semanal/reconciliation/log
GET  /api/programacion-semanal/close/preview
POST /api/programacion-semanal/close/apply
POST /api/programacion-semanal/reopen
POST /api/programacion-semanal/report
```

Cada endpoint tiene prueba de contrato PHP y cada respuesta/payload tiene esquema Zod estricto. Los
GET declaran `Cache-Control: no-store`. Toda mutación exige JSON, CSRF de S08, permiso/acción
efectiva y revalidación de contexto. Ninguna mutación se reintenta automáticamente.

### Contexto

`GET /context` devuelve:

```text
project: { id, name, area }
week: { number, max, startDate, endDate, confirmed, closeDate, phase }
actions: {
  view, editPlanning, qualify, addManual, duplicate, deprogram,
  previewReconciliation, applyReconciliation, registerTnp,
  previewClose, closeWeek, reopenWeek, report, exportCsv, openDrawer
}
csrf
catalogs: {
  subcontractors[], professionals[],
  cnp: [{ category, causes[] }], cnc: [{ category, causes[] }], cp[]
}
links: { cnp, cnc, cic, bi|null }
restrictionConfig
stateLegend[]
```

Si no hay proyecto, redirige el shell a `/proyectos`. Si el proyecto no tiene semanas, conserva el
flujo hacia `/programa-general-actualizar`. Una semana de URL inválida se sanea por T01 antes de
crear el contexto; la API no muta sesión por parámetros de S08.

### Actividades

`GET /activities` devuelve `{phase, generatedAt, rows, counts}`. `rows=[]` es un vacío real. Cada
fila contiene sólo:

```text
rowId, sourceActivityId, activityCode, activityName, description, location,
startDate, endDate, subcontractor, responsible, company, unit, budgetQuantity,
baseExecutedRatio, projectedEndRatio, suggestedQuantity, commitment, actualExecuted,
completionRatio, pac, performance, critical, late, activeKind, manual, tnp,
cnp, cnc, cp, readiness, state, rowActions
```

`activeKind` es `active | manual | deprogrammed | inactive`; no expone el valor legacy ambiguo.
`readiness` contiene restricciones normalizadas y pendientes duras/blandas. Texto fuente se entrega
sin HTML. Fechas son `YYYY-MM-DD|null`, números son `number|null`, booleanos son booleanos.

### Guardado individual

`POST /activity` acepta:

```text
{ rowId, changes: { description?, location?, subcontractor?, responsible?, commitment?,
                    actualExecuted?, cnc? } }
```

La allowlist depende de fase y `rowActions`. El servicio lee la fila, mezcla sólo claves presentes,
valida catálogos/sumas/CNC, actualiza, sincroniza carryover cuando corresponde y devuelve la fila
completa más `affectedSourceRows`. Una respuesta parcial o `BIEN` sin DTO no es válida.

### Operaciones manuales

- `GET /manual-candidates`: lista lazy con motivo/readiness y datos fuente seguros.
- `POST /manual-activity`: `{sourceActivityId, activityName, description?, location?,
  subcontractors:[...], responsible, commitment}`; revalida candidato y devuelve todas las filas.
- `POST /activity/duplicate`: `{rowId}`; devuelve la copia manual.
- `POST /activity/deprogram`: `{rowId, responsible?, company?, cnp:{category,cause,
  observations?}}`; el servidor decide hard-delete manual o CNP lógica y devuelve el resultado.

### TNP

- `GET /tnp-candidates`: lista lazy sólo cuando la fase/acción lo permiten.
- `POST /tnp`: `{sourceActivityId, actualExecuted, cpCategory, cpDetail?, observations?}`; revalida
  fase, universo, categoría y longitudes, aplica transaccionalmente y devuelve todas las filas.

### Conciliación

- `GET /reconciliation/preview`: plan puro, `snapshotHash`, operaciones, alertas y conteos.
- `POST /reconciliation/apply`: `{snapshotHash}`; no acepta operaciones fabricadas por el cliente.
- `GET /reconciliation/log`: último batch puro con conteos y entradas tipadas.

### Cierre y reapertura

- `GET /close/preview`: resumen, blockers y `snapshotHash`; 409 si ya está confirmada.
- `POST /close/apply`: `{snapshotHash}`; fecha/actor se derivan en servidor.
- `POST /reopen`: `{reason}`; 20–500 caracteres y política específica.

### Reporte

`POST /report` acepta `{format:"xlsx"}` y responde `{downloadUrl, filename, expiresAt?}`. Proyecto y
semana se derivan del scope. El generador usa el resolver canónico de estados, incluyendo TNP y los
diez estados; no recibe filas ni estilos desde React. CSV se genera localmente desde `rows` ya
normalizadas y filtradas, con columnas documentadas y escape RFC 4180.

### Errores estables

```json
{
  "error": {
    "code": "WEEK_PHASE_CHANGED",
    "message": "La semana cambió a calificación. Recarga los datos antes de continuar.",
    "fieldErrors": {},
    "retryable": false
  }
}
```

Códigos mínimos: `AUTH_REQUIRED`, `PROJECT_REQUIRED`, `WEEK_REQUIRED`, `FORBIDDEN`,
`WEEK_READ_ONLY`, `WEEK_PHASE_CHANGED`, `ROW_NOT_FOUND`, `ROW_SCOPE_MISMATCH`,
`INVALID_QUANTITY`, `QUANTITY_LIMIT_EXCEEDED`, `ASSIGNMENT_REQUIRED`, `CNC_REQUIRED`,
`CNP_REQUIRED`, `TNP_INVALID`, `PREVIEW_STALE`, `CLOSE_BLOCKED`,
`SCHEMA_PREREQUISITE_MISSING`, `REPORT_FAILED`, `VALIDATION_ERROR` y `INTERNAL_ERROR`. Las
excepciones se registran en servidor y nunca salen al navegador.

## Permisos y capacidades

### Matriz fallback observada

| Perfil canónico | Ver | Editar/calificar | Reporte | Resultado S08 |
|---|---:|---:|---:|---|
| A, D | Sí | Sí | Sí | planificación y calificación; reapertura siempre |
| R | Sí | Sí | Sí | semana actual/reciente; calificación histórica; reapertura en plazo |
| DCV | Sí | Sí | Sí | semana actual/reciente y calificación histórica; sin reapertura |
| OT | Sí | No | Sí | lectura, CSV y XLSX |
| G, S, SG | Sí | No | No | lectura y CSV |
| V | Sí | No | Sí | lectura, CSV y XLSX |
| C | No | No | No | sin navegación y 403 |

La tabla describe fallbacks actuales, no reemplaza RBAC. Overrides pueden alterar el resultado.
PHP resuelve acciones efectivas; React no interpreta roles, cargos, aliases ni letras.

### Política de semana

- `RbacCatalog::canEditLpsWeek`: A/D en cualquier semana; R/DCV sólo si semana > `maxWeek-2`.
- `LpsWeekEditPolicy`: una acción de calificación permite a A/D/R/DCV registrar real en una semana
  confirmada aunque la edición de planificación histórica esté bloqueada.
- `SemanalReabrirPolicy`: es adicional, nunca se infiere de `editar=true`.
- Cerrar, conciliar, crear, duplicar y desprogramar sólo ocurren en programación.
- TNP y avance real sólo ocurren en calificación.
- CSV está disponible a todo lector porque no genera datos ni archivos en servidor; XLSX exige
  `lps.reportes.generar`.

### Contradicciones que S08 cierra

1. La página actual sólo exige autenticación; la canónica exige `lps.programacion_semanal.ver`.
2. Botones y celdas se controlan por una letra de rol del DOM; React usa acciones server-side.
3. La API acepta `db`/semana del cliente; S08 deriva scope y semana de sesión.
4. El cliente aplica los techos de cantidad, pero PHP no; S08 los hace autoritativos en servidor.
5. TNP se ofrece en calificación pero el guard lo rechaza en semana confirmada; la política S08 lo
   clasifica como acción de calificación.
6. Abrir la vista ejecuta `sanear` y `auto-program`; S08 sólo ejecuta preview puro.
7. Auto-program no usa CSRF y el log ejecuta DDL; apply usa CSRF y lectura nunca crea schema.
8. Cerrar acepta una fecha cliente y puede crear proveedores con correo ficticio; S08 deriva fecha y
   bloquea referencias de catálogo inválidas.
9. Cerrar actualiza semana y genera CIC sin una transacción envolvente; S08 es all-or-nothing.
10. El estado se calcula en tres lugares divergentes y el reporte omite TNP; S08 usa un resolver.
11. Las tarjetas móviles pierden edición, acciones, drawer y CSV; S08 mantiene capacidad equivalente.
12. El endpoint de causas ignora `area` y se reutiliza para CNP/CNC; el contexto separa catálogos.
13. `sanear` oculta excepciones y responde éxito; S08 devuelve error estable y conserva el estado.
14. La lista devuelve `ps.*` y errores con excepción; S08 usa DTO allowlisted y errores opacos.

## Filtros, conteos y CSV

Los filtros viven en la URL de la SPA o estado local serializable, nunca en sesión ni en la base:

- búsqueda por código, Id, actividad, descripción y ubicación;
- estado operativo multiselección sin Ctrl/Cmd;
- gravedad;
- crítica/no crítica;
- subcontratista y Responsable AIA;
- activa, manual, CNP o TNP;
- restricción dura pendiente / lista;
- con/sin compromiso en programación;
- sin calificar, cumplida o incumplida en calificación;
- con/sin CNC;
- sólo blockers del cierre o cambios de conciliación cuando existe ese contexto.

Los conteos muestran total del endpoint, visibles, seleccionados si aplica, y cantidad por cada estado
de la fase. Desactivar un estado desde la leyenda es explícito, multi-select y anuncia el resultado.
“Sin actividades en la semana” y “Sin coincidencias con los filtros” son estados distintos.

El CSV usa el mismo orden visible, pero exporta datos, no HTML/chips. Incluye fase, semana, Id,
actividad, subcontratista, responsable, unidad, presupuesto, ejecutado base, proyectado, sugerido,
compromiso, real, PAC, estado, CNP/CNC/CP y observaciones pertinentes. Funciona en tabla y tarjetas;
no depende de una instancia HOT.

## Arquitectura React

### Módulo

```text
frontend/src/modules/programacion-semanal/
  ProgramacionSemanalPage.tsx
  useProgramacionSemanal.ts
  dominio/
    normalizarProgramacionSemanal.ts
    filtrarProgramacionSemanal.ts
    exportarProgramacionSemanalCsv.ts
  componentes/
    ToolbarProgramacionSemanal.tsx
    FiltrosProgramacionSemanal.tsx
    LeyendaProgramacionSemanal.tsx
    TablaProgramacionSemanal.tsx
    TarjetasProgramacionSemanal.tsx
    EditorActividadSemanal.tsx
    DialogoActividadManual.tsx
    DialogoDesprogramar.tsx
    DialogoTnp.tsx
    DialogoCnc.tsx
    DialogoConciliacion.tsx
    HistorialConciliacion.tsx
    DialogoCierreSemana.tsx
    DialogoReapertura.tsx
```

Los esquemas viven en `frontend/src/lib/api/esquemas/programacion-semanal.ts`; el gateway vive en
`frontend/src/lib/api/programacion-semanal.ts` y usa exclusivamente `cliente.ts`. Todos los tipos son
`z.infer`; no hay interfaces manuales que puedan divergir del wire.

### Estado y concurrencia

El hook mantiene una fuente normalizada por `rowId`: contexto, filas, filtros, selección, fila
abierta, drafts, operaciones pendientes, preview y error. Cambio de proyecto/semana aborta requests,
cierra diálogos/drawer, descarta drafts con confirmación si estaban sucios y vuelve a cargar.

Una fila sólo tiene una mutación activa. Guardar no hace optimistic success: puede mantener el draft
visual con estado “Guardando”, pero sustituye la fila únicamente con el DTO servidor. En 409/422 el
draft permanece, foco vuelve al campo y se ofrece recarga. Las mutaciones no se reintentan.

### Layouts

- `>=1180`: tabla completa, toolbar de una o dos líneas y detalle contextual.
- `768..1179`: tabla semántica con columnas prioritarias, detalle expandible y scroll sólo dentro de
  su contenedor; la página no desborda horizontalmente.
- `<768`: tarjetas, sin tabla/HOT oculta. Cada tarjeta permite las mismas ediciones y acciones que
  su fila según fase y permisos.

En programación, tarjeta y tabla editan asignaciones, descripción/ubicación y compromiso; muestran
readiness, sugerido, proyección, CNP y duplicar/desprogramar. En calificación editan real y CNC;
muestran compromiso, PAC, TNP y explicación. Ambos abren T02 drawer.

## Drawer contextual

S08 consume T02; no crea un segundo drawer. Al abrir una fila envía identidad canónica de actividad,
`rowId`, semana y `modulo="PS"`. La pestaña de diagnóstico semanal muestra:

- fase, estado, explicación y siguientes acciones;
- restricciones duras/blandas y readiness;
- compromiso/sugerido/asignaciones o compromiso/real/PAC;
- CNP, CNC o CP/TNP cuando corresponda;
- enlace autorizado a S09/S10/S11;
- comentarios, respuestas, menciones, digest, SOS y crisis de T02.

La antigua tarjeta de “estado operativo” se integra como diagnóstico; no compite con otro drawer.
Todas las acciones están disponibles desde móvil y teclado. Cerrar fila, cambiar semana o cambiar
proyecto limpia el contexto.

## Estados de experiencia

- **Carga inicial:** skeleton de toolbar, resumen y filas/tarjetas; no spinner global bloqueante.
- **Recarga:** conserva contenido y anuncia actualización.
- **Vacío real:** explica que la semana no tiene actividades y muestra sólo acciones autorizadas.
- **Filtros vacíos:** conserva filtros y ofrece restablecer.
- **Sólo lectura:** todos los datos, filtros, CSV, leyenda/drawer y reporte autorizado siguen
  disponibles; no se renderizan controles editables deshabilitados sin explicación.
- **Programación:** guía de compromisos, preview de cierre y pendientes.
- **Calificación:** guía de avance, CNC y TNP.
- **Guardando:** estado por fila/tarjeta y acción bloqueada sólo en esa fila.
- **Conflicto:** conserva draft, explica fase/contexto cambiado y ofrece recarga.
- **Error de catálogo:** formulario permanece abierto y reintenta sólo por acción humana.
- **Error de conciliación/cierre:** ningún éxito parcial; se conserva preview y se anuncia rollback.
- **Offline/red:** banner no destructivo, reintento manual y CSV de datos ya cargados.

## Accesibilidad, tema y design system

- Usar sólo tokens de `public/css/tokens.css` y contratos semánticos del design system.
- Oscuro es default/fallback; claro tiene contraste y capacidad equivalentes.
- No usar hex, colores literales, estilos inline, `!important`, Bootstrap, Handsontable, Select2,
  Font Awesome ni CSS-in-JS en el módulo React.
- Tabla con `caption`, encabezados asociados, fila seleccionable por control real y detalle con
  `aria-expanded`/`aria-controls`.
- Tarjetas con títulos, grupos de campos y botones nominados; target mínimo 44×44.
- Filtros, leyenda y tabs usan controles nativos; no dependen sólo de color ni modificadores.
- Diálogos atrapan/restauran foco, cierran con Escape cuando no hay mutación y describen impacto.
- Errores se asocian a campos mediante `aria-describedby`; resumen de error recibe foco.
- `aria-live` anuncia carga, conteos, guardado, cierre y cambio de fase sin duplicar mensajes.
- La edición completa funciona con teclado; no hay gesto hover-only ni acción por doble clic.
- Soporta zoom 200 %, `prefers-reduced-motion`, texto seguro y página sin overflow horizontal en
  390×844, 768×1024, 1180×820 y 1440×900.
- Los candidatos visuales oscuro/claro requieren aprobación explícita antes de convertirse en
  goldens.

## Seguridad y RLS

S08 no cambia RLS ni la arquitectura global. Cada lectura/escritura:

1. exige sesión;
2. resuelve `ProjectScope` desde el servidor;
3. deriva proyecto/prefijo/área/semana de scope/sesión;
4. exige capacidad de lectura, edición, calificación, reapertura o reporte según la operación;
5. verifica que `rowId` y `sourceActivityId` pertenecen al proyecto y semana activos;
6. revalida fase y política de semana dentro de la transacción;
7. usa prepared statements y `project_id` explícito mediante la capa `Database`;
8. valida CSRF para toda mutación;
9. allowlistea campos y catálogos;
10. registra actor y resultado sin secretos ni payload completo sensible.

React nunca envía ni persiste `db`, prefijo, `project_id`, rol, usuario, grants, credenciales o flags
de autorización. Las acciones de contexto son conveniencia visual, no autorización. Un request
fabricado vuelve a pasar todas las políticas.

No se tocan `docs/security/rls-runtime-boundary.md`, grants, usuarios ni base durante S08 documental.
Los contratos PHP usan stores falsos; Playwright intercepta antes de navegar.

## Estrategia strangler y retiro

Durante construcción, React vive en `/app/programacion-semanal` y
`/programacion-semanal` continúa en PHP. Los 16 endpoints nuevos conviven con aliases legacy. S08
no redirige CNP/CNC/CIC a React antes de S09–S11.

El corte final agrega `/programacion-semanal` a `SpaRouter::RUTAS_MIGRADAS`, actualiza la sidebar y
mantiene GET/HEAD solamente. Después de verificar paridad, RBAC, responsive, a11y, claro/oscuro y
rollback, se retiran VIEW-39/40/41, `hot.js`, `stateMachine.js`, `changeMonitor.js` y CSS exclusivo.
Un search de cero callers decide qué métodos de `SemanalApiController`,
`ProgramacionSemanalController` y `ProgramChangeDetector` pueden retirarse; no se elimina una clase
compartida a ciegas.

No se retiran `legacyCards.js`, CNP/CNC/CIC views/controllers/styles, drawer compartido,
`RestrictionConfigResolver`, `LpsService`, políticas, tablas, reportes ni endpoints usados por otra
superficie.

## Estrategia de pruebas

### PHP sin DML

- Resolver puro: once estados, ambas áreas, N/A, umbrales, prioridad y explicación.
- Política pura: matriz fallback, overrides, semana histórica, fase y reapertura.
- Proyección y cantidades: fechas, `%`, unidades físicas, splits, precisión y techos.
- Contratos de los 16 endpoints con stores falsos, scope/semana/capacidades/CSRF.
- Mutación de fila: allowlist, merge servidor, CNC, PAC, carryover y rollback falso.
- Manual/duplicar/desprogramar: universo, unicidad, CNP y resultados múltiples.
- TNP: fase confirmada autorizada, categorías/longitudes y lista de filas afectadas.
- Conciliación: preview puro, inmunidades, stale hash, idempotencia, apply/log sin DDL.
- Cierre: blockers, fecha servidor, catálogo inválido, CIC all-or-nothing y reapertura.
- Reporte: scope, permiso, resolver canónico, URL segura y error opaco.
- Ruta GET/HEAD/POST y vista protegida.

Todos usan interfaces de store/generador/notificador/reloj falsos. Ninguna prueba abre conexión de
escritura ni ejecuta rollback-DML.

### TypeScript/Vitest

- Los 16 esquemas aceptan fixtures válidos y rechazan claves extra/tipos legacy/HTML.
- Gateway usa paths, métodos, JSON, CSRF, cancelación y `cliente.ts`; cero `fetch` fuera de cliente.
- Normalización conserva identidad y números `0` versus `null`.
- Filtros/conteos/CSV cubren acentos, estados, fase y escape.
- Hook cubre carrera de requests, cambio de contexto, drafts, conflicto y mutaciones sin retry.
- Tabla/tarjetas poseen igualdad de acciones y edición.
- Diálogos manual/CNP/CNC/TNP/conciliación/cierre/reapertura cubren validación y foco.
- Drawer recibe `modulo=PS`, identidad/semana correctas y se limpia al cambiar contexto.
- Tema sólo usa tokens y no altera capacidades.

### Playwright interceptado

Antes de navegar, interceptar sesión, contexto, 16 endpoints S08, reportes y writes T02. Escenarios:

1. programación permitida en 1180×820;
2. lector sin mutaciones ni botones falsos;
3. búsqueda/filtros/conteos/leyenda/CSV;
4. edición válida e inválida con techo de porcentaje/cantidad;
5. actividad manual, duplicar y desprogramar/CNP;
6. preview/apply/log de conciliación y stale hash;
7. cierre bloqueado, cierre exitoso y cambio a calificación;
8. calificación, CNC obligatorio y cumplimiento que limpia CNC;
9. TNP funcional en semana confirmada;
10. reapertura permitida/denegada;
11. tablet 768×1024 y móvil 390×844 con edición/drawer/CSV equivalentes;
12. cambio de semana/proyecto cancela requests y limpia drafts/contexto;
13. vacíos, 401/403/409/422/500, red y recuperación;
14. axe y navegación completa por teclado;
15. candidatos oscuro/claro en 390, 768, 1180 y 1440 sin actualizar goldens.

No ejecutar suites legacy que entren por dev-door, cambien sesión, guarden semanal, concilien,
cierren/reabran, creen TNP/CNP/CNC/CIC, escriban drawer o generen archivos reales. En particular,
`programacion-semanal-roles-phases.mjs`, `programacion-semanal-sprint.mjs`, ciclos CNP y
`preconstruccion-full-cycle.mjs` no pertenecen a la verificación documental ni al gate sin fixture
aislado.

## Criterios de aceptación

- S08-AC-01: `/app/programacion-semanal` y, tras el corte, `/programacion-semanal` rinden el mismo
  componente React protegido.
- S08-AC-02: no existe `fetch` S08 fuera de `frontend/src/lib/api/cliente.ts`.
- S08-AC-03: los 16 endpoints poseen Zod estricto y contrato PHP.
- S08-AC-04: React no envía proyecto/prefijo/área/rol/fase/semana autoritativa.
- S08-AC-05: una lista vacía es `[]` y se distingue de error o filtro vacío.
- S08-AC-06: fase y acciones provienen del servidor y cada mutación las revalida.
- S08-AC-07: los once estados se resuelven en una sola pieza PHP y coinciden en lista, save y
  reporte.
- S08-AC-08: filtros, conteos y leyenda operan sin Ctrl/Cmd ni mutación de sesión.
- S08-AC-09: tabla >=768 y tarjetas <768 tienen edición/acciones equivalentes.
- S08-AC-10: compromiso, real, splits, porcentaje y presupuesto se validan en servidor.
- S08-AC-11: real incumplido no guarda sin CNC válido; cumplir limpia CNC y recalcula PAC.
- S08-AC-12: actividad manual/duplicada/desprogramada conserva identidad, CNP, carryover y
  transacción.
- S08-AC-13: TNP funciona durante calificación, exige categoría y devuelve todas las filas afectadas.
- S08-AC-14: cargar la página no ejecuta DML ni DDL.
- S08-AC-15: conciliación siempre es preview puro + apply explícito/CSRF/idempotente.
- S08-AC-16: leer el log nunca crea o altera tablas.
- S08-AC-17: preview de cierre identifica blockers por fila y close/CIC es all-or-nothing.
- S08-AC-18: cierre deriva fecha/actor y nunca crea proveedores/contactos ficticios.
- S08-AC-19: reapertura aplica `SemanalReabrirPolicy`, motivo 20–500 y auditoría.
- S08-AC-20: CSV funciona en tabla/tarjetas y XLSX exige permiso de reporte.
- S08-AC-21: drawer T02 funciona en todos los layouts y se limpia al cambiar contexto.
- S08-AC-22: 401/403/409/422/500 conservan drafts pertinentes y ofrecen recuperación manual.
- S08-AC-23: oscuro/claro, 390/768/1180/1440, zoom 200 %, teclado y axe cumplen sin overflow.
- S08-AC-24: no se toca `/admin/`, RLS, schema, grants, usuarios, credenciales ni datos en el frente
  documental/verificación.
- S08-AC-25: VIEW-39/40/41 y activos exclusivos sólo se retiran tras piloto, cero callers y rollback
  probado; S09–S11 siguen accesibles.

## Entregas verticales

### Entrega 1 — Núcleo de lectura responsive

Permiso de vista, resolver canónico, contexto/lista Zod, gateway, página, tabla/tarjetas, fase,
estados, filtros, conteos, leyenda, recarga y CSV. No hay writes ni retiro legacy.

### Entrega 2 — Edición de programación

Validación cuantitativa server-side, guardado individual, asignaciones, compromiso, actividad
manual, duplicación y desprogramación/CNP con equivalencia móvil.

### Entrega 3 — Conciliación explícita

Preview/apply/log, inmunidades, alertas, idempotencia, carryover y eliminación de DDL/auto-DML de la
ruta React.

### Entrega 4 — Cierre y calificación

Preview/apply de cierre y CIC, transición de fase, avance real/CNC, TNP y reapertura autorizada.

### Entrega 5 — Drawer, reporte y calidad

T02, XLSX, errores, accesibilidad, oscuro/claro, responsive, navegador interceptado y gate visual.

### Entrega 6 — Corte canónico

SpaRouter/sidebar, manifiestos, zero-caller audit, retiro exclusivo de VIEW-39/40/41 y aliases
seguros, sin tocar satélites S09–S11.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Tres motores de autoprogramación divergen | un plan puro y un apply transaccional |
| Vista produce DML/DDL oculto | red interceptada, prueba de preview puro y search de DDL |
| Estado cambia entre carga y save | revalidación servidor y `WEEK_PHASE_CHANGED` |
| Splits exceden presupuesto | suma server-side por origen/semana/proyecto en transacción |
| TNP permanece roto por lock | acción de calificación y contrato confirmado explícito |
| Cierre parcial deja CIC inconsistente | preview + transacción all-or-nothing |
| Self-heal inventa proveedores | blocker de catálogo y enlace a S13, nunca placeholder |
| Reporte diverge del estado UI | resolver canónico compartido |
| Móvil pierde funciones | matriz de acciones idéntica y pruebas 390×844 |
| Catálogos CNP/CNC se mezclan | DTO separado por dominio y área servidor |
| Retiro rompe S09–S11 | ownership/zero-caller y no borrar `legacyCards.js` |
| Light queda nominal | escenarios funcionales y candidatos visuales en ambos temas |

## Decisiones descartadas

- Mantener Handsontable dentro de React.
- Reutilizar VIEW-41 mediante iframe o `dangerouslySetInnerHTML`.
- Ejecutar conciliación al montar la página.
- Mantener `ProgramChangeDetector::ensureLogTable()` en request runtime.
- Enviar `db`, proyecto, área, rol, fase o fecha de cierre desde React.
- Confiar en techos de cantidad sólo del cliente.
- Usar `opcion` para el nuevo contrato.
- Crear subcontratistas ficticios durante el cierre.
- Mantener dos drawers o una grilla oculta en móvil.
- Migrar CNP/CNC/CIC dentro de S08.
- Reescribir goldens sin aprobación.

## Decisiones pendientes

No hay decisiones de negocio, producto, estrategia o PM pendientes. Durante implementación sólo se
admiten hallazgos técnicos contra código real; si alteran alcance, dato, autorización, semántica de
TNP/CNP/CNC, cierre o rollout, se detiene la tarea y se eleva como decisión de producto antes de
continuar.

## Siguiente gate

Invocar `superpowers:writing-plans` para escribir
`docs/superpowers/plans/2026-08-30-s08-programacion-semanal-react.md`, autorrevisarlo, comprobar
trazabilidad de los 16 endpoints y 25 criterios, actualizar el atlas maestro y continuar con S09 sin
implementar.
