---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-20
areas: [design-system, lps]
fuente: docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design.md
resumen: "Las restricciones de Programación Intermedia se funden en una columna de indicadores y se liberan desde un globo anclado a la fila; la tabla deja de desbordar sin esconder nada. Semanal hereda la pieza en la ola siguiente"
---

# Habilitación en una columna — spec v1

> **v1 · para revisión de Felipe.** Escrita tras el grillado del 2026-08-20 y ampliada con el
> segundo sprint del 2026-08-21 (diez decisiones más). Todas las decisiones de producto que
> aparecen aquí las tomó él en esas conversaciones.
>
> **Dos correcciones a la v0, ambas por dato del código:** (a) las restricciones **no tienen tres
> estados** sino escalas propias por restricción y un valor `N/A`; (b) **Semanal sí tiene las mismas
> cinco restricciones duras** (`programacion_semanal/hot.js:570`), así que no puede quedar «fuera de
> alcance» sin más — queda en la ola siguiente.

## El problema, medido

`/programacion-intermedia` desborda: **17 columnas suman 1490px dentro de 1100 disponibles**, o sea
**390px de más**. Desde la ola 2 del frente `replanteo-coloreado-estados` la tabla al menos avisa
—scrollea y lo señala con sombra de borde— pero el residente sigue sin ver de un vistazo lo que
necesita.

De dónde sale el exceso, medido columna por columna en pantalla a 1180×820:

| Columna | Ancho |
|---|---:|
| Observaciones | 130 |
| Sub-Contratista | 126 |
| Actividad | 120 |
| Estado Operativo | 118 |
| Diseños y Especificaciones | 107 |
| Responsable AIA | 100 |
| Procedimiento Constructivo | 93 |
| Actividad Predecesora | 82 |
| Equipos y Herramienta | 81 |
| % Liberación | 78 |
| Modelación BIM | 76 |
| Id | 74 |
| Materiales | 69 |
| Ejecutado | 66 |
| Mano de Obra | 64 |
| Semanas Inicio | 62 |
| Lote | 44 |

**El hallazgo que decide el diseño:** las columnas de restricción son anchas por su CABECERA, no por
su dato. «Diseños y Especificaciones» ocupa 107px para mostrar `100,0%`. Siete columnas gastan 572px
en enseñar siete porcentajes.

## Qué es una restricción, y por qué esto es delicado

Las siete columnas de restricción son `type: 'dropdown'` con su lista de valores
(`programacion_intermedia/hot.js:354-366`): **liberar una restricción es cambiar ese valor**, y es el
trabajo central del módulo. Cualquier rediseño que las vuelva de solo lectura destruye la función.

Un primer borrador de esta propuesta cayó exactamente en ese error —dibujó indicadores bonitos y
olvidó la escritura—, y lo cazó Felipe con una sola pregunta: «¿y cómo liberamos la restricción?».
Queda escrito aquí porque es la trampa que cualquiera repetiría.

Hay además un segundo eje que YA existe y no se toca: el modal **Restricción Compartida**
(`#modal_shared_constraint`), que exige 2+ filas marcadas en la columna Lote y aplica **una
restricción a muchas actividades**. Los dos ejes son complementarios:

| | Una actividad | Muchas actividades |
|---|---|---|
| **Una restricción** | celda / globo | modal de Restricción Compartida *(ya existe)* |
| **Todas sus restricciones** | **globo por fila** *(esta spec)* | — |

## La decisión

**Decisión de Felipe (2026-08-20):** «Panel por fila + Modal de restricciones compartidas (que ya
existe)», y para la forma del panel, **opción A: globo anclado a la fila**, tras ver las tres
maquetas. El ratón se conserva tal cual y el teclado se suma — no lo reemplaza.

### 1 · La tabla: una columna «Habilitación»

Las **siete columnas de restricción** y la de **% Liberación** se funden en una sola columna:

- Un cuadrito por restricción en **orden fijo** (las duras primero, las blandas después), con la
  **inicial dentro** para no depender solo del color ni de la posición.
- El **% Liberación** calculado, al lado de los cuadritos.
- La celda es focuseable y tiene nombre accesible: anuncia la actividad, cuántas restricciones
  faltan y el porcentaje.
- **Tope de ocho cuadritos y «+N»**: el número de restricciones lo fija el proyecto (Construcción
  usa siete, otras áreas dos), así que la columna no puede asumir una cantidad.
- Las **filas de capítulo** llevan la celda vacía: no son actividades y no se liberan.
- La cabecera conserva el **embudo de filtro por restricción** que hoy existe en cada columna
  (`filters: true`). Fundir las columnas sin reponerlo mataría un filtro que el equipo usa.

**Cómo se lee un cuadrito.** Dos señales independientes, porque responden a dos preguntas distintas:

| Señal | Qué dice | Por qué |
|---|---|---|
| **Relleno** (vacío → medio → lleno) | Cuánto lleva esa restricción | Las escalas varían: Materiales va 0/33/66/100, Predecesora 0/50/100. Un relleno cubre cualquier escala, incluso si mañana cambian los pasos. |
| **Color** | Si **cumple su propio umbral** | Cada restricción tiene su `threshold`: Predecesora al 50 % ya cuenta como liberada, Materiales necesita 100. Colorear por el porcentaje crudo pintaría de amarillo algo ya resuelto, y la gente perseguiría restricciones muertas. |

**`N/A` va tachado y en gris apagado.** Ni verde ni rojo: no cuenta para el cálculo y no debe leerse
como liberada. Pintarlo de verde es lo más cómodo de programar y es justo lo que hace que alguien dé
por resuelta una restricción que nadie miró.

**Cuentas del ancho — corregidas el 2026-08-21, y el resultado cambia la conclusión.** Las
cifras de la v0 (572 + 78 = 650 liberados) se estimaron sin leer los arrays reales de
`hot.js:443-467`. Los verdaderos:

| Bloque | Mínimo | Piso duro |
|---|---|---|
| 7 columnas fijas iniciales | 672 | 580 |
| 7 restricciones (74 / 64 c.u.) | 518 | 448 |
| 3 columnas fijas finales | 422 | 326 |
| **Total** | **1612** | **1354** |

Disponible a 1100: **1040 px** (descontados ~60 de scrollbar y barra lateral).

**El problema es más profundo de lo que decía la v0:** las **diez columnas que esta spec no toca**
suman ya **1094 px de mínimo** — más que los 1040 disponibles, sin contar una sola restricción.
Apretándolas a su piso duro bajan a 906; sumando una Habilitación de 200 px queda **1106**, que
**sigue sin caber por 66 px**.

Conclusión: **fundir las restricciones es necesario pero no suficiente.** Para cumplir la condición
de hecho #1 hay que hacer además una de estas dos, y es decisión de Felipe cuál:

- **Habilitación de 134 px o menos** (cuadritos de ~10 px y el porcentaje debajo, no al lado), sin
  tocar ninguna otra columna; o
- **bajar el piso de una de las tres columnas anchas** — Actividad (piso 120), Estado Operativo
  (118) u Observaciones (130) —, lo que implica decidir cuál de esos textos puede estrecharse.

Lo que **no** es opción es dejarlo en 1106 y llamarlo cumplido.

### 2 · El globo: liberar sin perder el contexto

Se abre con clic en la celda, o con Enter/Espacio si se llegó por teclado. Contiene:

- Un **marcador de avance arriba** con el `% Liberación` y el estado operativo de la actividad, que
  **se mueve al marcar**. Sin él, la recompensa del gesto queda tapada por el propio globo: hoy la
  fila recalcula al instante (`recalculateRestrictionStateForVisualRow`) y el usuario lo ve.
- **Duras** (bloquean la habilitación) y **blandas** (seguimiento), agrupadas y rotuladas como tales
  — hoy esa distinción solo vive en una clase CSS (`pi-soft-restriction-cell`).
- Cada una con **el mismo selector y las mismas opciones de hoy**: mismo gesto, mismo dato, misma
  validación. No se inventa un editor nuevo.
- El **% Liberación** al pie, recalculado al vuelo.

**Comportamiento:**
- **Guarda al elegir**, igual que la celda hoy. Sin botón de confirmar: añadirlo cambiaría un
  contrato de interacción que el módulo ya tiene y que el residente conoce.
- **Escape** o clic afuera lo cierran, y **el foco vuelve a la celda** que lo abrió.
- Se **voltea** arriba/abajo y a izquierda/derecha cuando no cabe: la maquinaria ya existe y está
  probada — es la del tooltip de estado migrado a la Popover API en este mismo frente, que vive en
  el top-layer y por eso no lo recorta el scroller de la tabla.
- **Sin permiso de edición** (Visualizador, Subcontratista): se abre igual y se ve todo, con los
  selectores desactivados **y una línea que dice por qué**. Ver qué frena una actividad no es
  privilegio de quien edita: bloquear el globo entero le quitaría al subcontratista la única pantalla
  donde ve qué lo está deteniendo, y devolvería el asunto a la llamada telefónica.
- **Flechas arriba/abajo para saltar a la actividad anterior o siguiente sin cerrarlo**, también por
  teclado. En la reunión semanal se recorren las actividades de corrido; sin salto son dos clics por
  actividad. Lo que el globo **no** hace es seguir a la fila que el ratón toque: el contenido
  cambiaría bajo el cursor y se marcaría la restricción equivocada.
- **Si el guardado falla**, el aviso sale **dentro del globo, pegado a la restricción que falló**,
  con el texto que ya existe y un botón de reintentar. Hoy el fallo revierte la celda y avisa en la
  barra de la tabla (`hot.js:3204-3219`); con el globo encima ese aviso queda tapado y el usuario
  vería su marca deshacerse sola. En obra, con señal intermitente, eso pasaría seguido.
- **Deshacer con Ctrl+Z**, igual que hoy: equivocarse marcando no debe requerir un gesto nuevo.

### 3 · Lo que NO cambia, a propósito

- El **modal de Restricción Compartida** y la **columna Lote** que lo alimenta. **El globo actúa
  siempre sobre una sola actividad**, nunca sobre la selección múltiple: son dos gestos distintos
  —el lote es «llegaron los materiales», el globo es «qué le falta a esta»— y mezclarlos permitiría
  cambiar diez filas con un clic que parece tocar una. Ese es el error más caro posible aquí.
- La **exportación a CSV** conserva una columna por restricción. Fundirlas en pantalla es una
  decisión de lectura; el archivo que alguien abre en Excel y cruza con otra cosa no se toca.
- El **cálculo** del `% Liberación` y del estado operativo: esta spec mueve **dónde se editan** las
  restricciones, no qué significan.
- Las columnas **Observaciones, Sub-Contratista, Actividad, Estado Operativo, Responsable AIA, Id,
  Ejecutado, Semanas Inicio**: se quedan como están.
- La **configuración dinámica de restricciones** (`getRestrictionConfig`): el número de
  restricciones lo decide el proyecto, así que la columna se arma desde esa configuración y no desde
  una lista de siete escrita a mano.

## Condición de hecho

1. `/programacion-intermedia` **no desborda** a 1180×820, medido en pantalla sobre el contenedor
   real (`scrollWidth <= clientWidth`).
2. **Ninguna celda esconde texto**: sonda de `scrollHeight`/`scrollWidth` contra `client*` sobre
   todas las celdas y cabeceras renderizadas, en cero.
3. El globo **se abre, se opera y se cierra completo con teclado**, y el foco vuelve a su celda.
4. Un rol **sin permiso de edición** ve las siete restricciones y no puede cambiar ninguna
   (verificado con `/dev/entrar` en un rol permitido y uno denegado, como exige AGENTS.md).
5. Liberar desde el globo produce **exactamente el mismo guardado** que liberar desde la celda de
   hoy: mismo endpoint, misma carga, mismo recálculo.
6. La suite del gate en verde y los goldens regenerados **con aprobación visual explícita de
   Felipe**.
7. El **filtro por restricción sigue existiendo** desde la cabecera de Habilitación.

## Cómo se mide, un mes después

Dos cifras, ninguna de ellas una opinión:

| Qué | Cómo | Por qué esa |
|---|---|---|
| **Cabe a 1100 sin barra horizontal** | Prueba automática que falla sola si alguien vuelve a ensanchar | Hoy pide 1490. Sin un guardián, la columna diecisiete vuelve en tres meses. |
| **Restricciones liberadas por semana, que no baje** | Conteo sobre el dato que ya se guarda | Dice si el gesto nuevo estorbó en obra. Si baja, el globo está cobrando más de lo que ahorra. |

Deliberadamente **no** se mide por «se ve mejor» ni por encuesta: es exactamente el criterio que
llevó a esta pantalla a tener diecisiete columnas, cada una añadida porque a alguien le pareció
buena idea.

## Riesgos declarados

- **Es el corazón funcional del módulo.** Si el globo falla, no se puede liberar. Mitigación: el
  selector y el guardado son los de hoy; lo que cambia es dónde se dibujan.
- **Un clic más por restricción suelta.** Hoy se edita en la celda directamente; con el globo hay
  que abrirlo. Se compensa cuando se liberan varias de la misma actividad, que según Felipe es el
  gesto real. Queda declarado como el costo aceptado de la decisión.
- **Densidad de la celda:** siete cuadritos más un porcentaje en ~200px, sobre filas de 24px de alto.
  Hay que medirlo en pantalla antes de dar por buena la maqueta.
- **Los goldens de Intermedia cambian otra vez**, y con ellos una nueva aprobación visual.

### 4 · Móvil: la misma pieza, otro envase

En móvil la tabla **ya no es una tabla**: se convierte en tarjetas (`pi-mobile-card`) que listan
cada restricción con su nombre y su valor. O sea, el panel por fila **ya existe ahí y funciona**.

El contenido del globo y el de la tarjeta son **el mismo componente**: mismo orden, mismos grupos,
mismos colores, mismo selector. Cambia solo el envase — en escritorio flota anclado a la fila, en
móvil vive dentro de la tarjeta. Diseñarlos aparte dejaría dos formas distintas de liberar lo mismo,
y la de móvil quedaría desactualizada al primer cambio.

## Orden de ejecución

1. **Intermedia primero.** Se construye, se prueba en obra una semana.
2. **Semanal después**, con la pieza ya rodada. Semanal maneja las mismas cinco duras
   (`programacion_semanal/hot.js:570`); dejarla indefinidamente distinta reintroduce el problema que
   este frente vino a corregir. Hacer las dos a la vez duplicaría el riesgo de la primera versión.

## Fuera de alcance

Programa General, el rediseño del modal de Restricción Compartida, el cálculo del estado operativo,
y el resto del censo de tablas (`goals/replanteo-coloreado-estados/censo-tablas.md`).

**Supuesto declarado, no resuelto:** dos personas editando la misma actividad a la vez. Hoy existe
un 409 que avisa si la semana activa cambió en otra sesión (`hot.js:3214`), pero nada vigila la
misma celda. El globo no empeora eso ni lo arregla; queda anotado por si aparece en obra.
