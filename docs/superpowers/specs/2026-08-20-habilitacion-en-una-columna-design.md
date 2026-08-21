---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-20
areas: [design-system, lps]
fuente: docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design.md
resumen: "Las siete restricciones de Programación Intermedia se funden en una columna de indicadores y se liberan desde un globo anclado a la fila; la tabla deja de desbordar sin esconder nada"
---

# Habilitación en una columna — spec v0

> **v0 · para revisión de Felipe.** Escrita tras el grillado del 2026-08-20. Todas las decisiones
> de producto que aparecen aquí las tomó él en esa conversación y están marcadas como tales.

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

- Siete cuadritos de color en **orden fijo** (las cinco duras primero, las dos blandas después), cada
  uno con el color del nivel de esa restricción.
- El **% Liberación** calculado, al lado de los cuadritos.
- La celda es focuseable y tiene nombre accesible: anuncia la actividad, cuántas restricciones
  faltan y el porcentaje.

**Cuentas del ancho:** salen 572px (siete restricciones) + 78px (% Liberación) = **650px**; entra
una columna de ~200px. Neto: **450px liberados**, y la tabla pasa de 1490 a **~1040px en 1100** —
entra completa, con unos 60px de margen, sin scroll horizontal y **sin esconder nada**.

### 2 · El globo: liberar sin perder el contexto

Se abre con clic en la celda, o con Enter/Espacio si se llegó por teclado. Contiene:

- **Cinco duras** (bloquean la habilitación) y **dos blandas** (seguimiento), agrupadas y rotuladas
  como tales — hoy esa distinción solo vive en una clase CSS (`pi-soft-restriction-cell`).
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
  selectores desactivados. Ver por qué una actividad no arranca no es privilegio de quien edita.

### 3 · Lo que NO cambia, a propósito

- El **modal de Restricción Compartida** y la **columna Lote** que lo alimenta.
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

## Riesgos declarados

- **Es el corazón funcional del módulo.** Si el globo falla, no se puede liberar. Mitigación: el
  selector y el guardado son los de hoy; lo que cambia es dónde se dibujan.
- **Un clic más por restricción suelta.** Hoy se edita en la celda directamente; con el globo hay
  que abrirlo. Se compensa cuando se liberan varias de la misma actividad, que según Felipe es el
  gesto real. Queda declarado como el costo aceptado de la decisión.
- **Densidad de la celda:** siete cuadritos más un porcentaje en ~200px, sobre filas de 24px de alto.
  Hay que medirlo en pantalla antes de dar por buena la maqueta.
- **Los goldens de Intermedia cambian otra vez**, y con ellos una nueva aprobación visual.

## Fuera de alcance

Programa General y Programación Semanal (no tienen columnas de restricción), el rediseño del modal
de Restricción Compartida, el cálculo del estado operativo, y el resto del censo de tablas
(`goals/replanteo-coloreado-estados/censo-tablas.md`).
