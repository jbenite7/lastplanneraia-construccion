---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-08-14
areas: [lps, design-system]
fuente: "docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design.md (adenda del 2026-08-14); verificado contra public/js/modules/programacion_semanal/hot.js:3470 y public/js/modules/programacion_intermedia/hot.js:4437"
resumen: "Bajo 1180px, Semanal e Intermedia pintan tarjetas con la misma forma: cinco elementos en la cara visible y el resto en un desplegable nombrado por su contenido"
---
# La tarjeta móvil de programación (E2-bis)

Por debajo de **1180 px** los dos módulos de programación dejan la grilla y pintan tarjetas
(`UMBRAL_CARDS`, `public/js/modules/aia_ui/view-switch.js:6`). **La tablet recibe tarjetas**, no
grilla: el umbral es uno solo y no por módulo.

## La forma común, y por qué

Las dos tarjetas comparten forma, y la razón no es de maquetación: **responden «qué hago con esto»
en vez de «qué es esto»**. La de Semanal describía una fase que comparten las 31 tarjetas; la de
Intermedia decía qué falta en *esa*. Ganó la segunda.

De ahí salen los cinco elementos de la cara visible, iguales en ambos módulos:

| Elemento | Detalle |
|---|---|
| Identificador y actividad | El capítulo va aparte, en una línea atenuada —separado, no truncado— con `AIACardTitle.separarCapitulo()` (`public/js/modules/aia_ui/card-title.js`). |
| Chip con cifra accionable | En Intermedia, el contador de restricciones liberadas («3 de 7»); en Semanal, el botón de estado operativo. |
| Barra de avance | Continua en Semanal, **segmentada** en Intermedia —una casilla por restricción—. No se trasladó la segmentada a Semanal: allí el avance es continuo y segmentarlo inventaría una precisión que el dato no tiene. |
| Línea de foco | El asunto pendiente más urgente, en texto. |
| Responsable AIA | En obra se busca por persona antes que por actividad, y desplegar solo para saber a quién reclamar era el gesto más repetido. |

Lo demás vive en un `<details>` **nombrado por su contenido**, no por «ver más»: «Ver fechas y
presupuesto» en Semanal (`hot.js:3501`) y «Liberar restricciones» en Intermedia (`hot.js:4376`).

## La línea de foco ya estaba calculada

El dato del foco **no hubo que inventarlo**: ya se computaba y hasta entonces solo se entregaba al
lector de pantalla, mientras la tarjeta visible pintaba nada más el número. **Quien veía la pantalla
obtenía menos que quien la escuchaba.** Hoy se pinta en las dos: `.ps-mobile-foco`
(`programacion_semanal/hot.js:3488`, solo cuando el estado no es `ready`) y `.pi-mobile-card__foco`
con «Faltan …» (`programacion_intermedia/hot.js:4489`).

## En qué se diferencian los dos módulos

- **Semanal edita en la cara visible**, de un toque, porque capturar el compromiso en obra no puede
  costar un despliegue. El campo editable **cambia con la fase**: en calificación es el avance real;
  en programación, el compromiso (`hot.js:3495-3500`).
- **Intermedia edita dentro del desplegable**, y ahí está el cambio de fondo: sus tarjetas eran de
  solo lectura y **no mostraban ninguna de las siete restricciones**, que son para lo que existe el
  módulo. Hoy las libera desde el móvil (`construirDetalleRestricciones`, `hot.js:4370`).
- **El candado I4 se ve en móvil por primera vez**: sin Responsable AIA asignado, las restricciones
  quedan bloqueadas, y el listener móvil lo comprueba por su cuenta (`hot.js:4558`) en vez de
  confiar en que la UI ya lo impedía. Las reglas no se replicaron: se consumen de
  `public/js/modules/aia_ui/enablement-rules.js`.

## Lo que costó, medido contra lo estimado

La estimación por composición dio ≈325 px en Semanal y ≈275 en Intermedia. Lo medido: **360 px**
(+10,8 %) y **269 px** (−2,2 %). Se parte de 562 px por tarjeta en Semanal —1,5 por pantalla, con 31
tarjetas y unos 17.000 px de scroll para recorrerlas—, así que la ganancia es real aunque Semanal se
desviara.

**La lección para la próxima estimación por composición:** el desvío no vino del contenido sino del
**espaciado entre bloques**, que resultó ser el mayor consumidor de altura de la tarjeta. No es
residuo que se ajusta al final; hay que contarlo como un bloque más.

Ver [[una-decision-escrita-no-llega-sola-al-codigo]] —por qué esta decisión hubo que tomarla dos
veces—, [[programacion-semanal]] y [[programacion-intermedia]] para los módulos, y [[lps-dominio]]
para el dominio. La spec completa es
[[docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design|la de F2A]].
