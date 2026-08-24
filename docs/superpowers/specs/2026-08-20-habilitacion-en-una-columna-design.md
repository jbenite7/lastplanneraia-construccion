---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-20
areas: [design-system, lps]
fuente: docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design.md
resumen: "Las restricciones de Programación Intermedia se funden en una columna de 130px de cuadritos y se liberan desde un globo anclado a la fila; el % Liberación se muda al globo y la tabla cabe a 1100 con 82px de holgura. Semanal hereda la pieza en la ola siguiente"
---

# Habilitación en una columna — spec v2

> **v2 · aprobada por Felipe el 2026-08-21.** Escrita tras el grillado del 2026-08-20, ampliada con
> el segundo sprint del mismo día (diez decisiones) y cerrada con el tercero del 2026-08-21 (cinco
> decisiones, tomadas sobre mockups). Todas las decisiones de producto las tomó él.
>
> **Correcciones acumuladas, todas por dato del código o por aritmética, no por cambio de opinión:**
>
> | # | La v0/v1 decía | El dato |
> |---|---|---|
> | 1 | Las restricciones tienen tres estados | Cada una trae su escala (0/33/66/100, 0/50/100) y todas admiten `N/A` |
> | 2 | Semanal queda fuera de alcance | Tiene las mismas cinco duras (`programacion_semanal/hot.js:570`); entra en la ola siguiente |
> | 3 | Se liberan 650 px y la tabla queda en ~1040 | Las cuentas se estimaron sin leer `hot.js:443-467`; las diez columnas no tocadas ya sumaban 1094 de mínimo |
> | 4 | El `% Liberación` es una columna aparte | Es `Estado_Restricciones` (`hot.js:368`, `piPercentRenderer`, readOnly): mudarlo al globo libera esos 92 px |
> | 5 | Tope de ocho cuadritos y «+N» | Ocho de 14 px piden 126 de 114 útiles. El tope real es **siete**; con más, seis y el resto contado |
> | 6 | Inicial dentro del cuadrito | Una letra legible pide ~14 px de caja y la columna no da para eso más el ancho; la inicial sale de la tabla y vive en el globo |

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

Las **siete columnas de restricción** se funden en una sola de **130 px**, y el **% Liberación**
(la columna `Estado_Restricciones`) **sale de la tabla** y se muda al globo.

- Un cuadrito de **14 × 18 px** por restricción, en **orden fijo** (las duras primero, las blandas
  después). **Sin inicial dentro**: una letra legible obligaría a una caja mayor, y la columna no da
  para eso. El nombre completo vive en el globo y en el nombre accesible de la celda.
- La celda es focuseable y tiene nombre accesible: anuncia la actividad, cuántas restricciones
  faltan y el porcentaje — que sigue existiendo aunque ya no se dibuje.
- **Siete cuadritos visibles como tope, no ocho.** El número lo fija el proyecto (Construcción usa
  siete, otras áreas dos), así que la columna no puede asumir una cantidad; con ocho o más se
  muestran **seis y el resto contado** («+4»).
- Las **filas de capítulo** llevan la celda vacía: no son actividades y no se liberan.
- La cabecera conserva el **embudo de filtro por restricción** que hoy existe en cada columna
  (`filters: true`). Fundir las columnas sin reponerlo mataría un filtro que el equipo usa.

**Por qué el porcentaje se va.** Al lado de la celda ya va el chip de Estado Operativo, que se
calcula **desde ese mismo porcentaje**, y los cuadritos muestran cuántas faltan, que es el mismo
dato contado de otra forma. Tres formas de decir lo mismo en una fila. En el globo sí sirve, porque
ahí alguien está trabajando esa actividad.

**Cómo se lee un cuadrito.** Tres señales, y ninguna depende solo del color:

| Señal | Qué dice | Por qué |
|---|---|---|
| **Relleno** (vacío → medio → lleno) | Cuánto lleva esa restricción | Las escalas varían: Materiales va 0/33/66/100, Predecesora 0/50/100. Un relleno cubre cualquier escala, incluso si mañana cambian los pasos. |
| **Color** | Si **cumple su propio umbral** | Cada restricción tiene su `threshold`: Predecesora al 50 % ya cuenta, Materiales necesita 100. Colorear por el porcentaje crudo pintaría de amarillo algo ya resuelto, y la gente perseguiría restricciones muertas. |
| **Marca de visto** en la esquina | Que cumplió, **sin usar color** | Dos cuadritos medio llenos —Predecesora al 50 que ya cumple y Materiales al 50 que no— serían idénticos para quien no distingue verde de amarillo. El visto es la señal más reconocible para «esto ya está» y no gasta ancho. |

Un borde más grueso **no** sirve para esto: a 14 px, uno o dos píxeles de borde no se distinguen a
la distancia en que esta pantalla se usa.

**`N/A` va tachado y en gris apagado.** Ni verde ni rojo: no cuenta para el cálculo y no debe leerse
como liberada. Pintarlo de verde es lo más cómodo de programar y es justo lo que hace que alguien dé
por resuelta una restricción que nadie miró.

**Cuentas del ancho — leídas de `hot.js:443-467`, no estimadas.** Disponible a 1100: **1040 px**,
descontados ~60 de scrollbar y barra lateral.

| Bloque | Antes (mín / piso) | Después (mín / piso) |
|---|---|---|
| 7 columnas fijas iniciales | 672 / 580 | 672 / 580 |
| 7 restricciones | 518 / 448 | — |
| `Estado_Restricciones` (% Liberación) | 92 / 78 | — se muda al globo |
| Estado Operativo + Observaciones | 330 / 248 | 330 / 248 |
| **Habilitación** | — | **130 / 130** |
| **Total** | **1612 / 1354** | **1132 / 958** |

**Cabe: 958 contra 1040, con 82 px de holgura** — con las columnas en su piso, que es lo que hace el
reparto responsivo cuando aprieta. No sobra margen para agregar una columna nueva, y eso es
deliberado: lo vigila la prueba del punto 1 de la condición de hecho.

**Dentro de la celda**, 130 px dejan 114 útiles (padding 8 + 8):

- siete cuadritos: `7 × 14 + 6 × 2` = **110** ✓
- ocho cuadritos: `8 × 14 + 7 × 2` = **126** ✗ — por eso el tope es siete
- seis más «+N»: `6 × 14 + 5 × 2 + 2 + 17` = **113** ✓

**Lo que se decidió NO tocar:** el piso de Actividad (120), Estado Operativo (118) y Observaciones
(130). Son las tres columnas de texto; estrecharlas devolvería el recorte silencioso que este frente
vino a erradicar. Si algún día hace falta más ancho, se revisa **qué columna sobra**, no cuál se
aprieta — y eso es un frente propio.

### 2 · El globo: liberar sin perder el contexto

Se abre con clic en la celda, o con Enter/Espacio si se llegó por teclado. Contiene:

- Un **marcador de avance arriba** con el `% Liberación` —que ya no vive en la tabla, así que este
  es su único sitio— y el estado operativo de la actividad, que **se mueve al marcar**. Sin él, la recompensa del gesto queda tapada por el propio globo: hoy la
  fila recalcula al instante (`recalculateRestrictionStateForVisualRow`) y el usuario lo ve.
- **Duras** (bloquean la habilitación) y **blandas** (seguimiento), agrupadas y rotuladas como tales
  — hoy esa distinción solo vive en una clase CSS (`pi-soft-restriction-cell`).
- Cada una con **el mismo selector y las mismas opciones de hoy**: mismo gesto, mismo dato, misma
  validación. No se inventa un editor nuevo.

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
8. Los **contadores de la leyenda** de Programa General e Intermedia usan el mismo token que el chip
   de estado de su fila, comprobado sobre valores computados en el navegador y no sobre el CSS
   declarado.
9. **La información no depende solo del color:** relleno y visto distinguen los cuatro casos con la
   pantalla en escala de grises.

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
- **Densidad de la celda:** siete cuadritos de 14 × 18 px en 114 útiles, sobre filas de 24 px de
  alto. Las cuentas cierran con 4 px de sobra, así que hay poco margen: hay que medirlo en pantalla
  con la fuente real antes de dar por buena la maqueta.
- **El visto sobre un cuadrito de 14 px es pequeño.** Es la señal que sostiene el criterio de no
  depender del color, así que si en pantalla no se distingue, no se encoge el problema: se sube el
  tamaño del cuadrito y se recalcula el ancho.
- **Los goldens de Intermedia cambian otra vez**, y con ellos una nueva aprobación visual.

### 4 · Los contadores de la leyenda

Los items de la leyenda usan la familia de tokens **vieja** (`--ds-cell-state-*`, `styles.css:536-542`)
mientras los chips de estado de la tabla ya usan la nueva (`--ds-state-solid-*`). Por eso los
contadores se ven todos del mismo gris verdoso y no se parecen a lo que describen.

**Cada contador toma el color sólido de su estado, idéntico al chip de su fila.** Semanal ya lo hizo
(`programacion-semanal.css:3683`); falta Programa General e Intermedia. Una leyenda que no se parece
a la tabla no está describiendo nada.

No se le inventa una paleta propia más suave: cuatro contadores en una barra no saturan la pantalla,
y desalinearlos del chip es el defecto que se está corrigiendo.

### 5 · Móvil: la misma pieza, otro envase

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
