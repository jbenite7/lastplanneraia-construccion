---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/ds-f1a-estado-ejecutor.md
resumen: Decisiones pendientes — frente ds-f1a-estado
---

<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente ds-f1a-estado

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## Decisiones del usuario — brainstorming DS-F1a, 2026-08-19

Tomadas en conversación directa con el usuario. Se registran aquí porque son las que fijan el
alcance del spec, y perderlas obligaría a volver a preguntarlas.

1. **DS-F1 se parte en cuatro contratos**, no uno: escala de estado/severidad, escala de stacking
   (z-index), primitivas `aia-*` y tokens. Se empieza por estado/severidad — frente
   `ds-f1a-estado`. Las otras tres vuelven a la cola con su propio turno.

2. **El vocabulario y la escala se diseñan juntos**, no por separado: decidir cómo se ve la
   gravedad exige saber cuántos niveles hay, y eso exige saber cómo se llaman los estados. Es
   además lo que el usuario ya había pedido el 2026-08-18 al reabrir D-VOC-1 («replantear el
   diseño antes de ejecutar»).

3. **Tres niveles de gravedad**, confirmado sobre la distribución real medida (65.549 filas de
   `programa_consolidado`): urgente 7,8%, atención 9,0%, sin acción 83%. Un cuarto nivel tendría
   que partir un grupo de 110 filas sobre 65.549. La categoría `contexto`/`neutral` del contrato
   **no cuenta como cuarto nivel**: es ausencia de gravedad, no un grado.

4. **`No Requerida` se recupera.** Definición del usuario: «actividad que comienza en 7 semanas o
   más respecto a la fecha de inicio de la semana actual» — o sea, lo que cae fuera del lookahead,
   que el código ya fija en `PG_LOOKAHEAD_DAYS = 42` (seis semanas). El usuario decide que es
   información útil y que se eliminó mal. Consecuencias asumidas: el contrato gana un estado y hay
   que tocar `pg_calculate_status`, que hoy no puede devolverlo.

### Lo que estas decisiones destapan y todavía no está decidido

- **El 51,1% de las filas de `programa_consolidado` tiene un estado que `state-semantics.json` no
  declara.** El contrato declara `Con Alerta Restricciones`, que no existe en ninguna de las 65.549
  filas; el que sí existe, `En Liberación de Restricciones` (8,3%), no está declarado.
- **`No Requerida` está marcada como eliminada** por `database/patches/20260527_remove_no_requerida_state.sql`
  (2026-05-27), que sin embargo dejó 12.338 filas vivas en 10 de 16 proyectos. Ese patch recorre
  los proyectos por `general_proyectos_procesos.Base_de_Datos`, o sea la arquitectura de prefijos
  anterior a las tablas globales: escrito para un modelo que ya no es el vigente.
- **`ProgramaGeneralController.php:257` cuenta como lookahead** `Actividad Futura`,
  `En Liberación de Restricciones` y `No Requerida` — código vivo leyendo dos estados que otra
  fuente da por muertos.
- **Hay cuatro autoridades con jurisdicción sobre los estados y no coinciden:**
  `docs/design-system/state-semantics.json`, `docs/ESTADOS-PG-PI-PS.md`,
  `docs/matriz-severidad-cajon-contextual-lps.md` y el propio código.
- **`Capítulo` (10,0%) no es un estado**, es un tipo de fila: `pg_calculate_status` lo devuelve en
  su primera línea, antes de mirar ninguna fecha.
- **El 11,8% de las filas tiene el estado vacío** y todavía no sabemos por qué.

5. **El estado de 7+ semanas se llama «Fuera de Ventana».** Sustituye a `No Requerida`, cuyo
   nombre decía lo contrario de lo que significa: se lee como «no hace falta hacerla» cuando
   significa «todavía no entra en la ventana de planificación». «Ventana» ya es vocabulario de
   Last Planner, así que el término no se inventa, se toma del dominio.

6. **Las 7.705 filas con `Estado` vacío quedan explicadas y no son un bug de datos.** Medido: el
   **100%** tiene `Titulo = 1`, o sea son capítulos. `pg_calculate_status` devuelve `'Capítulo'`
   en su primera línea, antes de mirar ninguna fecha; las filas que nunca pasaron por él se
   quedaron en blanco. Mismo tipo de fila, dos representaciones según si alguien las guardó.

   Consecuencia para el diseño: **14.583 filas del cronograma son capítulos, el 22,2% del total**,
   y no son actividades sino encabezados de agrupación. Sacándolos, el universo real son 50.966
   actividades y la gravedad se concentra un tercio más de lo que parecía: urgente 9,7% (era 7,6%),
   atención 11,8% (era 9,2%).
