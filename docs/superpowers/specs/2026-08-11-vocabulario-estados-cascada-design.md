# Unificar los vocabularios de estado de la cascada — spec

- Frente: `vocabulario-estados-cascada` · sesión ejecutora `36422d59`
- Fecha: 2026-08-11
- Plan: [2026-08-11-vocabulario-estados-cascada.md](../plans/2026-08-11-vocabulario-estados-cascada.md)
- Cola de decisiones: `decisiones/vocabulario-estados-cascada.md`

## El problema, en una frase

La cascada LPS (Programa General → Actualizar → Programación Intermedia → Programación
Semanal) nombra el mismo ciclo de una actividad con **tres vocabularios distintos que conviven**,
y encima el código se ha desviado del contrato que los declara: hoy hay estados que se llaman de
dos maneras **en la misma pantalla**.

## Cómo se midió

Fuente primaria: `docs/design-system/state-semantics.json`, que es el contrato donde cada módulo
declara sus estados (`key`, `label`, `level`, `hue`). Se cruzó con los literales reales del
código (`stateLabels` y `WEEKLY_ALERT_MODEL` en los `hot.js` de cada módulo,
`pg_calculate_status()` en `src/Legacy/estado_programa_general.php`) y con las leyendas de las
vistas.

Comandos de la medición (salida real en esta sesión, sobre `de02471a`):

- lectura del contrato con `python3` sobre `docs/design-system/state-semantics.json`
- `grep -rn` de cada etiqueta sobre `src public views admin tests e2e docs`
- diff programático entre `var stateLabels` de Intermedia y el contrato

## Censo — los términos del ciclo, en tabla

### Vocabulario A — «avance contra el cronograma» (Programa General)

Lo calcula `pg_calculate_status()` y **se persiste** en la columna `Estado` de
`{prog_consolidado}`. Nombra el ciclo por *tiempo y avance*.

| Término | Dónde aparece | Qué estado nombra |
|---|---|---|
| Actividad Futura | `estado_programa_general.php`, `LpsService`, `GeneralApiController`, `ProgramChangeDetector`, `programa_general/hot.js`, vista PG, contrato, 5 pruebas | Aún no le toca empezar |
| Debe Iniciar | ídem + `SemanalApiController`, `NotificationType`, `ReportController` | Le toca empezar esta semana y no ha empezado |
| En Curso | ídem | Empezó y va al día |
| Atrasada | ídem + BI, CNC, CNP, `ReportProcessor`, `admin/Models/Project` | Empezó y va por detrás de lo teórico |
| Terminada | ídem + `PasosContratacionService`, `lps_drawer` | Avance ≥ umbral de terminado |
| Sin Datos | ídem | Faltan fechas para clasificarla |
| Capítulo | `estado_programa_general.php`, `LpsService`, PDC, matching | No es actividad: es un título |
| Con Alerta Restricciones | `programa_general/hot.js`, vista PG, contrato | Marca superpuesta, viene del vocabulario B |

### Vocabulario B — «alistamiento y liberación» (Programación Intermedia)

`public/js/modules/programacion_intermedia/hot.js` (`stateLabels`, `PIStateMachine`). Nombra el
ciclo por *cuánto falta para poder comprometerla*.

| Término (contrato) | Variante que el código muestra hoy | Qué estado nombra |
|---|---|---|
| RC inicio vencido | RC inicio vencido | Ruta crítica con inicio pasado |
| Inicio vencido | **Inicio Vencido** | Inicio pasado |
| Inicio por Habilitar | Inicio por Habilitar | Inicio inminente, sin liberar |
| Alistamiento Urgente | **Alistamiento urgente** | Entra en 1 semana |
| Alistamiento en Riesgo | **Alistamiento en riesgo** | Entra en 2-3 semanas |
| Alistamiento Pendiente | **Alistamiento pendiente** | Entra en 4-6 semanas |
| En Ejecución Pendiente | **Ejecución Pendiente** | Con avance, sin liberar |
| Listo para Comprometer | **Listo para comprometer** | Liberada |
| — | Control | Fila sin clasificar (solo código) |
| — | Capítulo | Título (solo código) |

### Vocabulario C — «compromiso y cumplimiento» (Programación Semanal)

`WEEKLY_ALERT_MODEL` en `programacion_semanal/hot.js`, en dos fases. Nombra el ciclo por *la
promesa*.

| Término | Fase | Qué estado nombra |
|---|---|---|
| RC con restricciones | programación | Ruta crítica sin poder comprometerse |
| Ejecución con restricciones | programación | Con avance y condiciones pendientes |
| Condiciones Pendientes | programación | Requiere habilitación antes de comprometer |
| Por Comprometer | programación | Habilitada, sin compromiso |
| Lista para Confirmar | programación | Compromiso cargado |
| Incumplida (RC) | calificación | Promesa fallada en ruta crítica |
| Incumplida | calificación | Promesa fallada |
| Sin Calificar | calificación | Sin cerrar la semana |
| Cumplida Control | calificación | Promesa cumplida |
| Trabajo No Planificado | calificación | Se hizo sin haberse prometido |
| Programada Manualmente | ambas | Fila fuera de la máquina (solo código) |

### Vocabulario A′ — Programa General Actualizar

Reusa A pero **cambia una pieza**: donde PG dice `Debe Iniciar`/`Con Alerta Restricciones`, aquí
aparece `Bloqueado` (contrato `programa-general-actualizar`, vista
`programaGeneralActualizar.view.php`).

### El recuento

| Corte | Términos distintos |
|---|---|
| Declarados por el contrato en los 4 módulos de la cascada | **26** |
| Solo en código, no en el contrato (Control, Capítulo, Programada Manualmente) | +3 |
| Variantes duplicadas del mismo estado por desviación código↔contrato | +6 |
| **Total de cadenas distintas que un usuario de obra puede ver** | **35** |

La revisión en frío contó 21; el censo con el contrato delante da **35**. La diferencia son las
seis variantes duplicadas, los tres estados solo-código y los estados de Actualizar, que no se
habían separado de los de PG.

## Los tres solapamientos reales

Un mismo momento del ciclo se nombra tres veces:

| Momento del ciclo | Vocabulario A | Vocabulario B | Vocabulario C |
|---|---|---|---|
| Aún no le toca | Actividad Futura | Alistamiento Pendiente | — |
| Le toca y no puede arrancar | Debe Iniciar | Inicio por Habilitar | Condiciones Pendientes |
| Le toca, puede arrancar | — | Listo para Comprometer | Por Comprometer |
| Empezó con condiciones sin cerrar | Atrasada / En Curso | En Ejecución Pendiente | Ejecución con restricciones |
| Se pasó de fecha | Atrasada | Inicio vencido | Incumplida |
| En ruta crítica y mal | Atrasada | RC inicio vencido | RC con restricciones / Incumplida (RC) |
| Cerrada bien | Terminada | — | Cumplida Control |

## Qué manda

1. `GLOSARIO.md` es la referencia de dominio. Reconoce **Actividad Liberada**, **Restricciones**,
   **Liberación de Restricciones**, **Promesa Confiable**, **PAC/PPC**, **CNC**, **CNP**. No
   reconoce ninguna de las 35 etiquetas de pantalla: el vocabulario de la interfaz **nunca se
   derivó del glosario**. Ese es el hallazgo de fondo.
2. `docs/design-system/state-semantics.json` es contrato ejecutable: `ops-state-contract.test.mjs`
   y `states-feedback.test.mjs` lo verifican. Los une por `key`, no por etiqueta —el propio
   comentario del test admite la desviación de etiquetas—, así que **alinear las etiquetas al
   contrato no altera lo que esas pruebas miden**.

## Qué se puede hacer sin criterio del usuario

**Solo una cosa: cerrar la desviación código↔contrato en Intermedia.** Seis estados se muestran
hoy con una cadena distinta de la que el contrato declara, y en dos casos (`Inicio Vencido`,
`Ejecución Pendiente`) la leyenda de la vista y el chip de la fila **nombran distinto el mismo
`key` dentro de la misma pantalla**. El contrato ya decidió el nombre; el código no lo está
proyectando. Corregirlo no elige vocabulario nuevo: aplica el que ya está aprobado.

> **Corrección del 2026-08-11, después de publicar.** Una redacción anterior de este spec decía
> «se contradicen en la misma pantalla», y el mensaje del commit `ca9c6bb7` —ya publicado, y no se
> reescribe historia— dice lo mismo. La frase se lee como «el usuario ve los dos nombres a la vez»,
> y eso es **falso** al viewport canónico: medido a 1180×820, el botón de filtro está en `x=86` y la
> celda `ops-state-chip` del mismo estado en `x=1332`, fuera de la pantalla. Sí valía para el modal
> de guía operativa, que se abre encima de la leyenda. El defecto y el recuento no dependen de esto
> —los términos se cuentan, no se miran—, pero la premisa había viajado por tres sesiones sin que
> nadie la comprobara. El mecanismo, en [[el-dom-dice-que-existe-no-que-se-ve]].

Efecto medible: **35 → 29 términos**, sin tocar datos guardados, sin tocar lo que mide ninguna
prueba, sin cambiar ninguna palabra que la obra no vea ya escrita en su propia leyenda.

## Qué NO se decide aquí

Todo lo demás. Unificar A, B y C en un vocabulario único implica renombrar estados que la obra usa
a diario y, en el caso de A, **reescribir una columna persistida** (`Estado` en
`{prog_consolidado}`, que `pg_calculate_status()` escribe en cada guardado). Va a
`decisiones/vocabulario-estados-cascada.md` con su medición, sin decidirse.

## Condición de hecho del frente

1. Este spec, con el censo en tabla. ✔
2. Plan aprobado en su gate antes de editar.
3. Recuento antes/después: **35 → 29** en esta pasada; el resto depende de las decisiones
   encoladas.
4. Antes/después a 1180×820 dark de Intermedia, con sesión por la puerta de servicio.
5. Verde con salida real: `npm run test:design-system:static`, PHPStan, y las pruebas PHP del área.
