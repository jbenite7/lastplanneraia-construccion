# Respuestas del usuario — la dirección de DS-F1

**Recogidas el 2026-08-18**, con `brainstorming`, de a una y en simple, después de cerrar el frente
`bug-coloreado-severidad`. **No son un diseño: son las tres decisiones de negocio** que
`insumo-ds-f1.md` dejó abiertas y sin las cuales DS-F1 arrancaría a ciegas.

Quien abra DS-F1 empieza por aquí, no por cero.

---

## 1 · ¿Dónde se lee la gravedad? → **Fuera del color**

**Decidido: un filete en el borde izquierdo de la fila, cuyo grosor y brillo suben con la gravedad,
más el orden de las filas.** El color no codifica gravedad.

**Por qué se descartó teñir la fila entera**, que era lo que el usuario esperaba al abrir el frente:
tres de los ocho estados de Intermedia son `urgent`, así que en una obra real eso no son tres filas
sino media tabla. Un muro rojo no comunica gravedad, la anula — y `PRODUCT.md` lo prohíbe por su
nombre en las anti-referencias: «no debe verse decorativa, **saturada de alertas**». Además obligaba
a rehacer la paleta oscura entera y empeoraba en móvil, que Intermedia abrió el 2026-08-14.

**Por qué el filete sí:** el grosor **es** una escala ordinal de verdad (6 / 4 / 2 px se distinguen
sin discusión), y el orden —lo más grave arriba— es la cascada más fuerte que existe y no gasta
ningún color. Es lo único que cumple a la vez «hacer visible el riesgo antes de que escale» y «no
saturar de alertas», que son las dos frases del propio manual.

## 2 · ¿Cuántos escalones? → **Cuatro, los del contrato**

**Decidido: los cuatro niveles de `docs/design-system/state-semantics.json`** — `urgent`,
`attention`, `healthy`, `neutral`.

Consecuencia buena: **no hay que derogar el contrato del design system.** Consecuencia que hay que
trabajar: la Guía Operativa de la propia vista agrupa hoy los ocho estados en **tres** cajones (P1
Resolver hoy, P2 Gestión semanal, P3 Seguimiento), así que la leyenda y el contrato tendrán que
decir lo mismo — hoy no lo dicen.

## 3 · ¿Hay que cambiar el tema oscuro? → **La pregunta se cayó sola, y la nueva se contestó**

La pregunta original existía **solo** porque una cascada en el fondo exige recorrido de brillo. Al
mover la gravedad al filete, **el tema oscuro no se toca**: las ocho anclas se quedan como están,
los tests que fijan sus hex siguen en verde y las siete entradas de `state-tint-exceptions.json`
—medidas contra esos hex— no se invalidan. Se dejó constancia en vez de preguntarla igual.

La que sí quedaba abierta, y se preguntó en su lugar: **si el fondo ya no dice gravedad, ¿qué dice?**

**Decidido: un color por estado, los ocho** — el fondo de la fila pasa a codificar **identidad**,
igual que ya hace el chip de la casilla Estado. Fila y chip dejan de contradecirse dentro de la
misma fila. Y se cierran de paso los dos síntomas que abrieron el frente: los tres pares de estados
que hoy pintan idéntico y las ocho entradas de leyenda que solo dan cinco colores. **No hace falta
ningún color nuevo:** los ocho existen, están medidos y ya se usan en el chip.

---

## La dirección, en una frase

**El color dice qué estado es. El filete y el orden dicen cuán grave es.** Un eje por canal, y cada
canal con una propiedad que de verdad tiene escalones.

## Lo que sigue abierto y NO se decidió aquí

**Qué nivel le toca a cada estado.** Hay **tres** documentos vivos y los tres discrepan:

| Estado de PI | Guía Operativa (la vista) | `state-semantics.json` | `matriz-severidad-…md` |
|---|---|---|---|
| Inicio por Habilitar | **P1** resolver hoy | attention | critical si RC, attention si no |
| En Ejecución Pendiente | **P1** resolver hoy | attention | critical si RC, attention si no |
| Alistamiento Urgente | **P2** gestión semanal | **urgent** | attention |
| Alistamiento Pendiente | P3 seguimiento | attention | normal preventivo |

Con la respuesta 2 (cuatro niveles del contrato), **el contrato gana por defecto**, pero eso deja
«Alistamiento Urgente» como `urgent` cuando la leyenda que lee el usuario lo pone en P2, y dos
estados P1 como `attention`. **Es una llamada de obra, estado por estado, y hay que hacerla antes de
escribir una línea de CSS.** Es la misma decisión que ya está anotada en
`decisiones/bug-coloreado-severidad-ejecutor.md` como «qué autoridad de severidad se deroga»,
ahora con la tercera fuente sobre la mesa.

## Restricciones que siguen en pie

Todas las de `insumo-ds-f1.md`, y dos que la dirección elegida **desactiva**:

- ~~Rehacer la paleta oscura~~ — ya no hace falta: el fondo usa los ocho colores que ya existen.
- ~~Derogar `state-semantics.json`~~ — ya no hace falta: se adopta su eje de cuatro niveles.

Siguen vivas: los goldens de PI y PG se moverán (regenerarlos exige **aprobación visual explícita**),
el filete es una **primitiva nueva** del design system y necesita su ficha y su aprobación, y
`states-feedback.css:162` sigue siendo letra muerta por `legacy-bridge.css:104-142`.

## Archivos de este goal
- [[diagnostico]] · [[insumo-ds-f1]] · [[propuesta-arreglo-3-estados]] · [[goal]]
