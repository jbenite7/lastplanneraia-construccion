---
capa: fuente
tipo: decision
estado: vigente
fecha: 2026-08-19
areas: [diseno, lps]
fuente: cola de pendientes del trabajo de estados, severidad y color del 2026-08-19
resumen: "Las decisiones de producto, proceso y diseño que la cola de estados y severidad dejó abiertas, cada una con su medición ya hecha"
project: lps-aia
type: decision
status: abierto
updated: 2026-08-19
---

# Decisiones pendientes — estados, severidad y color

**Qué es esto.** La cola del 2026-08-19 traía siete pendientes; tres o cuatro **no son trabajo, son
decisiones de Felipe**. Este documento las deja listas para decidir: cada una con la medición hecha,
las opciones reales, la consecuencia de cada una y una recomendación con su porqué. Lo técnico se
decidió y ejecutó sin preguntar; aquí solo queda lo que es de producto, proceso o diseño.

**Cómo leerlo.** Cada decisión es independiente salvo donde se diga. El orden es el de la cola, no
el de importancia. Las mediciones son de esta jornada, contra la base de **desarrollo**.

**El modelo que gobierna todo esto** (publicado en `c766a338`): tres canales, un eje cada uno — el
**color de fondo** dice QUÉ estado es (identidad), el **filete del borde** dice CUÁN GRAVE (nivel),
y el **orden** desempata. La regla vive en `axisRules` dentro de
`docs/design-system/state-semantics.json`.

---

## D-1 · ¿El contrato debe declarar los realces por condición del dato?

**Estado: abierta.** Es una sola pregunta con dos casos detrás, y las mediciones dicen que **no son
gemelos**.

### El caso de Programa General: `r0`

`getRestrictionAlertKey` (`public/js/modules/programa_general/hot.js:744`) deriva cuatro cubos
—`r0`, `r1`, `r2-3`, `r4-6`— de las filas que tienen **restricciones duras sin cumplir** y no están
ejecutadas, repartiéndolas por cuántas semanas faltan para su inicio. `r0` significa «debió iniciar
y sigue detenido», y llegó a tener **el único ancla propia de toda la escala**
(`--ds-cell-state-bloqueado-bg`). Hoy está aplanado en ámbar.

**Medido sobre 65.633 filas de `programa_consolidado`:**

| Cubo | Filas |
|---|---:|
| `r0` — debió iniciar y sigue detenido | **4.384** |
| `r1` — inicia en 1 semana | 1.347 |
| `r2-3` | 2.928 |
| `r4-6` | 4.584 |
| con restricciones sin cumplir, en total | 39.320 |
| de esas, **fuera** de los cuatro cubos (>6 semanas o sin dato) | 26.077 |

**El dato que de verdad decide** — qué estado declarado llevan las filas de cada cubo:

| Cubo | Estados que cruza |
|---|---|
| **`r0`** | Atrasada 3.199 · Debe Iniciar 1.047 · En Curso 111 · Fuera de Ventana 18 · Actividad Futura 9 |
| `r1` | Actividad Futura 1.159 · Debe Iniciar 150 · En Curso 32 · Fuera de Ventana 6 |
| `r2-3` | Actividad Futura 2.879 · En Curso 35 · Fuera de Ventana 7 · Debe Iniciar 5 · Atrasada 2 |
| `r4-6` | Actividad Futura 4.543 · En Curso 32 · Fuera de Ventana 9 |

**`r0` no es un estado: es un cruce.** Atraviesa cinco estados distintos. Si se le devolviera un
matiz propio —el `teal` que está libre—, ese color estaría diciendo «esto es un estado» sobre filas
que ya tienen cinco identidades diferentes, y el canal del matiz dejaría de significar lo que el
modelo dice que significa. **La conclusión no es que `r0` no importe: es que no cabe en el canal del
color de fondo.**

### El caso de Semanal: la ruta crítica

`getAlertClassForRow` (`public/js/modules/programacion_semanal/hot.js:1174`) devuelve
`ps-alert-critical-route` cuando el estado es `prog-ejecucion-con-restricciones` **y** `Critica >= 1`.
Se conservó a propósito para no regresionar.

**Medido con el mismo JOIN que usa la API real** (`SemanalApiController::list()`, que une las
restricciones desde `programa_consolidado` por `unique_id`):

| | Filas |
|---|---:|
| activas e incompletas en fase Programación | 249 |
| en estado `prog-ejecucion-con-restricciones` | 66 (26,5 %) |
| **de esas, realzadas por `Critica >= 1`** | **10** (15,2 % del estado, 4,0 % del total) |
| en ruta crítica en toda la fase, para comparar | 65 |

Aquí el realce **sí es un subconjunto de un solo estado**, no un cruce. Y hay un dato más, medido al
cerrar `semanal-fondo-por-matiz`: **en Semanal no existe análogo de «detenido por otro»** — ese
concepto no aparece en `WEEKLY_ALERT_MODEL` ni en `stateMachine.js`, en ninguna de sus dos fases.

### Las opciones

| | Qué implica | Consecuencia |
|---|---|---|
| **(a) El contrato declara los realces, con canal propio** | `state-semantics.json` gana un concepto nuevo: «realce por condición», que no consume matiz sino un cuarto recurso visual (trama, icono, marca en el borde) | Honesto con el modelo. Cuesta diseñar ese cuarto recurso y probarlo en las dos pantallas |
| **(b) El contrato declara solo los realces que son subconjunto de UN estado** | La ruta crítica de Semanal entra al contrato; `r0` no, porque cruza cinco estados | Barato y coherente. Deja `r0` sin representación: las 4.384 filas siguen aplanadas en ámbar |
| **(c) El contrato no declara realces** | Se retira la ruta crítica de Semanal y `r0` se queda como está | El más simple. Pierde información que hoy se ve, sobre 10 filas |

### Recomendación: **(b)**, y `r0` por otra vía

La ruta crítica de Semanal es un realce legítimo —un subconjunto de un estado, 10 filas de 249— y
declararlo cuesta poco. `r0` no cabe ahí, pero **4.384 filas detenidas no son ruido**: lo que pide
no es color, es una **columna o un filtro** que las liste, porque la pregunta que responde («¿qué
está detenido y debió arrancar?») es de trabajo, no de lectura de tabla.

**Qué NO haría: devolverle a `r0` su ancla `teal`.** Es la opción que parece más justa —recuperar
algo que se perdió— y es la que rompe el modelo: pintaría del mismo color filas que son Atrasada, En
Curso y Actividad Futura a la vez, y el color de fondo dejaría de ser fiable para todos los demás
estados, no solo para éste.
