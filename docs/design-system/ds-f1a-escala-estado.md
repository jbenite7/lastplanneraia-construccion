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

## Los trece estados

Las etiquetas son los literales **exactos** de la columna `Estado`, con tildes, verificados contra
la base. Un contrato cuya etiqueta no case con el literal guardado no puede cruzarse con los datos.

| Estado | Nivel | % de actividades | Quién lo produce |
|---|---|---:|---|
| `Atrasada` | Urgente | 8,0% | `pg_calculate_status` |
| `Debe Iniciar esta Semana y Restricciones Pendientes` | Urgente | 1,5% | nadie hoy |
| `En Liberación de Restricciones` | Atención · **revocable** | 10,7% | nadie hoy |
| `Debe Iniciar` | Atención | 0,9% | `pg_calculate_status` |
| `Debe Iniciar esta Semana` | Atención · **revocable** | 0,2% | nadie hoy |
| `Actividad Futura` | Controlado | 33,6% | `pg_calculate_status` |
| `Terminada` | Controlado | 19,0% | `pg_calculate_status` |
| `En Curso` | Controlado | 0,7% | `pg_calculate_status` |
| `Terminada Antes` | Controlado | 0,6% | nadie hoy |
| `A Tiempo` | Controlado | 0,4% | nadie hoy |
| `Adelantada` | Controlado | 0,0% | nadie hoy |
| `Fuera de Ventana` | *sin gravedad* | 24,2% | nadie hoy |
| `Sin Datos` | *sin gravedad* | 0,1% | `pg_calculate_status` |

### «Quién lo produce» no es un dato de archivo

Seis de los trece estados **no los produce nadie hoy**: `pg_calculate_status` tiene siete salidas
posibles y esas seis no están entre ellas. Eso significa que cada vez que se guarda una actividad,
su estado se reescribe con uno de los siete que sí produce — y los otros seis **se borran solos**.

Es exactamente lo que le estaba pasando a `Fuera de Ventana` antes de este contrato.

### Las dos asignaciones revocables

Estas dos las propuso la spec y **el usuario no las ha decidido**. La marca `revocable` del JSON las
protege, y quitarla es decisión suya:

- **`En Liberación de Restricciones` → Atención.** Parece el sustituto vivo de
  `Con Alerta Restricciones`, que `state-semantics.json` declara como atención y que **no existe en
  ninguna de las 65 549 filas**.
- **`Debe Iniciar esta Semana` → Atención.** Su hermano *con restricciones pendientes* va a
  urgente; lo que los separa es si algo lo bloquea. Si en obra «le toca esta semana» ya es urgente,
  se mueve.

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

## Pendiente de decisión del usuario

**Si `Fuera de Ventana` es etiqueta de pantalla o valor persistido.** No es un detalle:

- **Etiqueta de pantalla** — la columna `Estado` sigue guardando lo que guarda y la traducción vive
  en la capa de presentación. Barato y reversible; a cambio, suma un vocabulario en vez de restarlo.
- **Valor persistido** — `pg_calculate_status` pasa a producirlo y las 12 338 filas de
  `No Requerida` se migran. Es una migración sobre 16 proyectos, con respaldo verificable, dry-run
  y gate de tablas globales.
