---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-08-19
areas: [design-system]
tags: [leer-antes-de-tocar]
fuente: docs/design-system/ds-f1a-escala-estado.md
resumen: La escala de estado — contrato DS-F1a
---

# La escala de estado — contrato DS-F1a

> **La fuente ejecutable es `ds-f1a-escala-estado.json`, no este archivo.** Aquí se explica el
> contrato; allí se declara. **Si divergen, manda el JSON** — la misma jerarquía que `DESIGN.md`
> tiene con `tokens.css`. `tests/design-system/ds-f1a-escala-estado.test.mjs` comprueba que no
> diverjan: cada etiqueta del JSON tiene que aparecer en este documento.
>
> **Convive con `state-semantics.json` y no lo sustituye.** Sanear aquel contrato excede este
> frente y es una decisión pendiente del usuario.

**Base medida:** `1af2e9ac`, sobre las 50 966 actividades reales de `programa_consolidado` — las
65 549 filas menos 14 583 capítulos, que no son actividades.

## La regla: dos canales, un trabajo cada uno

| Canal | Qué dice | Cuándo aparece |
|---|---|---|
| **Fondo de la celda** | Qué estado es y en qué horizonte cae | Siempre |
| **Barra al borde de la fila** | Cuánto pesa | Solo en el 21,3% que pide algo |

### Por qué el fondo no puede llevar la gravedad

No es una preferencia estética. **`Fuera de Ventana` (24,2%), `Actividad Futura` (33,6%) y
`Terminada` (19,0%) tienen urgencia cero las tres.** Si el fondo codificara gravedad, las tres se
pintarían igual y se perdería la distinción de horizonte — la misma que este contrato viene a
recuperar. El fondo ya tiene un trabajo que ningún otro canal puede hacer.

### Por qué la ausencia de barra significa algo

El 78,7% de las actividades no lleva barra, y eso no es un hueco: **es la señal de que no piden
nada**. Es lo que permite que el 21,3% restante se lea de un barrido vertical, sin comparar tonos.
Si «controlado» tuviera marca propia, casi toda la tabla llevaría una y la señal dejaría de ser
escasa.

## Los tres niveles

Los tres niveles son conceptuales: **la barra dibuja dos y la ausencia de barra dibuja el tercero.**

| Nivel | Acción que pide | Cómo se ve |
|---|---|---|
| **Urgente** | Atender ahora | Barra, tratamiento fuerte |
| **Atención** | Revisar antes del siguiente hito | Barra, tratamiento medio |
| **Controlado** | Continuar según el ciclo normal | **Sin barra** |

**`Fuera de Ventana` y `Sin Datos` tampoco llevan barra, y no por la misma razón que
`Controlado`:** no es que su gravedad sea baja, es que **no tienen gravedad**. Se distinguen en el
fondo, que es su canal.

## Los siete estados

Medidos sobre **50 976 actividades reales** después de la migración del 2026-08-19. Las etiquetas
son los literales exactos de la columna `Estado`.

| Estado | Nivel | % de actividades |
|---|---|---:|
| `Atrasada` | Urgente | 8,0% |
| `Debe Iniciar` | Atención | 2,6% |
| `Actividad Futura` | Controlado | 17,8% |
| `Terminada` | Controlado | 19,7% |
| `En Curso` | Controlado | 1,1% |
| `Fuera de Ventana` | *sin gravedad* | 50,6% |
| `Sin Datos` | *sin gravedad* | 0,1% |

**Los siete estados legacy quedaron sin una sola fila:** `No Requerida`,
`En Liberación de Restricciones`, `Terminada Antes`, `A Tiempo`, `Adelantada`,
`Debe Iniciar esta Semana` y `Debe Iniciar esta Semana y Restricciones Pendientes`. La columna
`Estado` ya solo contiene lo que los calculadores canónicos producen, y todos sus valores tienen
nivel declarado — el 51,1% que el contrato no reconocía es cero.

### Lo que la migración enseñó y el contrato no decía

- **La mitad del cronograma está fuera de ventana.** `Fuera de Ventana` es el estado más frecuente
  con diferencia. Es coherente con un cronograma largo, y significa que la tabla, leída de un
  vistazo, habla sobre todo de trabajo que todavía no entra en la conversación.
- **Dos predicciones del informe fallaron.** Se dijo que `Actividad Futura` bajaría a ~6,8% y quedó
  en 17,8%: no se contó con que los 5 391 `En Liberación de Restricciones` entrarían ahí. La de
  `Fuera de Ventana` (~51% contra 50,6% real) sí acertó.
- **Una actividad terminada nunca es «fuera de ventana»**, aunque empiece a siete semanas: el
  calculador comprueba el avance completo antes que la regla de horizonte
  (`src/Legacy/estado_programa_general.php:148`). Por eso las 24 filas que se temía perder
  conservaron su `Terminada`.

## `Fuera de Ventana`

Sustituye a `No Requerida`, cuyo nombre decía lo contrario de lo que significa: se lee como «no
hace falta hacerla» cuando significa «todavía no entra en la ventana de planificación».

**Definición:** actividad que comienza en **7 semanas o más** respecto a la fecha de inicio de la
semana actual — es decir, fuera de `PG_LOOKAHEAD_DAYS = 42`.

## Lo que este contrato no decide

- **No sanea `state-semantics.json`.** El 51,1% de las filas tiene un estado que aquel contrato no
  declara, y él declara `Con Alerta Restricciones`, que no existe en ninguna fila. Es un hallazgo
  documentado, no un arreglo de este frente.
- **No decide qué pasa con `Capítulo`.** Los 14 583 capítulos ocupan la columna `Estado` sin ser un
  estado, y son la causa de las 7 705 filas «vacías» — el 100% de ellas tiene `Titulo = 1`.
- **No implementa CSS.** Construir los dos canales es DS-F2.

## `Fuera de Ventana` es un valor persistido

**Decisión del usuario, 2026-08-19.** No es una etiqueta de presentación: la columna `Estado` lo
guarda, y desde ese día **los dos calculadores lo producen** con la regla de offset ≥ 7 semanas —
`pg_calculate_status()` y `LpsService::calculateGeneralStatus()`.

La decisión se tomó **conociendo su alcance real**, que es mayor de lo que parecía: la regla no
reclasifica las 12 338 filas que hoy llevan la etiqueta, sino **26 084**, porque 13 664 actividades
que hoy se llaman `Actividad Futura` también empiezan a siete o más semanas. Tras la migración,
`Fuera de Ventana` pasa de 24,2% a **~51%** de la tabla y `Actividad Futura` baja a ~6,8%.

> **Los porcentajes de la tabla de arriba son los del reparto ANTERIOR a la migración**, medidos
> sobre `1af2e9ac`. Se conservan porque describen el estado real de los datos hasta que el frente
> de migración corra. Ese frente los actualizará.

## Los estados sin nivel de gravedad

`Fuera de Ventana` y `Sin Datos` declaran `nivel: null`, y el contrato lo dice con texto propio
porque tras este frente son **~51% de la tabla**: no es un caso de borde.

- **No dibujan barra.** Un estado sin nivel no pide acción.
- **Es la misma ausencia visual que `controlado`, y no significa lo mismo.** `controlado` es «va
  bien»; `null` es «esto no se mide por urgencia». Un cronograma con la mitad de sus actividades
  fuera de ventana no está medio sano: está medio fuera de la conversación.
- **La distinción se lee en el eje matiz**, que ya existe en el fondo — **no en un canal nuevo**.
  El fondo es el canal de identidad y horizonte, y distinguir esto es exactamente su tarea.
