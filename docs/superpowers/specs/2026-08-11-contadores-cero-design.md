---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-11-contadores-cero-design.md
resumen: Ocultar las etiquetas contadoras que marcan cero — diseño
---

# Ocultar las etiquetas contadoras que marcan cero — diseño

- Fecha: 2026-08-11
- Frente: `contadores-cero` (sesión de ejecución 06e4383d)
- Pantalla: `/programacion-intermedia`
- Sha sobre el que se midió todo lo de aquí: `de02471a`

## La medición, y sobre qué caso se hizo

En vivo, proyecto **Da Porto**, 1180×820, tema dark, rol A por la puerta de servicio:

| Qué se contó | Cuánto |
|---|---|
| Etiquetas contadoras en `#piLegend` | **8** |
| De ellas, leyendo `(0)` a la vez | **7** |
| La que contaba algo | `liberated-control` → `(1)` |
| Controles visibles en toda la pantalla | **63** |

Además hay un noveno elemento en cero fuera de la leyenda: el chip `0 selec.`
(`#shared-selection-count`). Queda fuera del cambio; el porqué está en D-CERO-2.

El reparto depende del proyecto y de la semana: con otro proyecto cargado las mismas 8 etiquetas
pueden estar casi todas contando. El caso medido es el de arriba, y es representativo del estado
normal de un proyecto en marcha, donde la mayoría de categorías de alerta están vacías.

## La pregunta que el spec tiene que resolver: ¿ocultar, o mostrar apagado?

**No son equivalentes, y aquí ya hay evidencia en vez de opinión: mostrar apagado ya está
implementado y no quitó nada.**

`setLegendCount` (`public/js/modules/programacion_intermedia/hot.js:2886`) ya pone la clase
`is-zero`, y `public/css/programacion-intermedia.css:1726` ya la pinta atenuada — superficie
neutra, tinta secundaria. Es el cambio **C-24**, del ciclo anterior. Funciona: el chip en cero
deja de gritar. Pero **sigue ocupando exactamente el mismo sitio**, con su indicador, su etiqueta
y su `(0)`. Ese ciclo se cerró con 28 arreglos que fueron 28 adiciones y ninguna resta, y C-24 es
un ejemplo literal de eso: añadió un estado visual en vez de quitar un elemento.

La condición de cierre de este frente es que **haya menos elementos en pantalla**. Atenuar no la
cumple, porque atenuado ≠ ausente: el ojo sigue teniendo que recorrer y descartar ocho fichas.

**Decisión: ocultar.** El atenuado no se borra — se queda como el comportamiento de reserva para
el caso en que ocultar no aplica (ver más abajo), que es justo lo que hace que la vuelta atrás
sea una línea y no un revert.

## El coste, dicho sin rebajarlo

Una etiqueta en cero **comunica algo real**: que esa categoría existe y ahora mismo está vacía.
Al ocultarla:

1. **La categoría desaparece del vocabulario visible.** Quien no conozca el sistema no sabrá que
   «Alistamiento Urgente» existe hasta que algo caiga ahí. La leyenda es, de hecho, la única
   enumeración de los ocho estados operativos que el usuario ve en esta pantalla.
2. **Se pierde el «va bien» tácito.** Ver siete ceros es leer «no tengo nada vencido». Ver la
   ausencia también lo dice, pero más flojo: ausencia se confunde con «no cargó».
3. **Cada etiqueta es además un botón de filtro** (`role="button"`, `data-filter`). Ocultarla
   retira un control, no solo una decoración.

Se acepta ese coste porque la enumeración completa sigue disponible en el modal de leyenda de
colores (`#modal_leyenda_colores`), que es donde corresponde consultar el vocabulario, y porque
el precio de tenerla siempre desplegada lo paga cada usuario en cada carga.

## «Vacío» y «cero» no son lo mismo aquí

Esta es la distinción que hace correcta la implementación, y no es cosmética.

`updateLegendCounts(filtered)` recibe las filas **ya filtradas**. O sea: en cuanto hay un filtro
activo, las siete categorías que no son la filtrada marcan `(0)` **aunque tengan contenido**. Ese
cero significa «no en esta vista», no «vacío».

Si se ocultara por el valor a secas, activar un filtro haría desaparecer los otros siete botones
de filtro y dejaría al usuario encerrado, sin forma de cambiar de filtro salvo desactivar el que
puso. Es una trampa real, no hipotética.

**Regla:** se oculta solo el cero que significa vacío de verdad — es decir, cuando **no hay
ningún filtro activo** y el conteo cubre el conjunto entero. Con un filtro puesto, ningún chip se
oculta: se conserva el atenuado de C-24, que ahí sí es la lectura correcta («no en esta vista»).

Lo mismo aplica al modo `view_all`, donde los conteos vienen del servidor
(`updateLegendCountsFromServer`) y sí cubren el conjunto entero: ahí ocultar es legítimo.

## Reversibilidad: una condición en un sitio

Nada de borrar marcado. La vuelta atrás es una constante:

```js
var OCULTAR_CONTADORES_EN_CERO = true;   // ← ponerla en false devuelve el atenuado de C-24
```

Con `false`, todo vuelve al estado previo al frente: los ocho chips visibles, los que marcan cero
atenuados. No hay HTML que restaurar ni CSS que descomentar, porque el HTML de las ocho etiquetas
se queda intacto en la vista y la regla de C-24 se queda intacta en el CSS.

## El término duplicado «Listo para Comprometer» — sale de este frente

Llegó como segunda decisión y **no se resuelve aquí**; pasa al frente
`vocabulario-estados-cascada`. El porqué, medido, no opinado:

- **La premisa heredada no se sostiene.** A 1180×820 el filtro está en `x=86` (dentro del
  viewport) y la celda `ops-state-chip` en **`x=1332`, fuera**: hay que desplazar la tabla para
  verla. No coexisten a la vista. (Yo mismo afirmé antes que estaban a 140px; era falso, mi
  primera comprobación no miraba los límites del viewport.)
- **No son dos copias de lo mismo.** Una es el **botón de filtro**; la otra, la **lectura de
  estado de esa fila** (`stateLabels`, `hot.js:505`), más una tercera en el modal
  (`hot.js:2823`). Borrar cualquiera quita función, no ruido. Lo duplicado es la cadena de texto,
  en tres sitios y dos capitalizaciones.
- **`GLOSARIO.md` no define el término.** Sin autoridad local, elegir capitalización canónica es
  **fijar vocabulario**, y hay un frente vivo haciendo justo eso.

Tampoco aporta a la condición de cierre: quitar la copia de la celda no reduce elementos, porque
es dato por fila y vive tras el desplazamiento horizontal.

## Lo que este frente no toca, a propósito

- El chip `0 selec.` — D-CERO-2 (lleva `aria-live`; ocultarlo rompe el anuncio).
- `programa-general` y `programacion-semanal`, que tienen el patrón gemelo — D-CERO-3 (alcance).
- Los goldens visuales fijados por `sha256` en el manifiesto del módulo — D-CERO-1, escalado.

## Accesibilidad

El chip oculto sale del árbol de accesibilidad junto con su `role="button"`, que es lo correcto:
un filtro que no filtraría nada no debe estar en el orden de tabulación. Los chips restantes
conservan su `aria-pressed` y su foco. No se toca `#shared-selection-count`, que es la única
región viva de la leyenda.

## Condición de hecho

1. Con la pantalla sin filtros, los chips en cero no ocupan sitio.
2. Con un filtro activo, siguen visibles (atenuados), y se puede cambiar de filtro.
3. Antes/después con **conteo de controles visibles**, no solo capturas.
4. `npm run test:design-system:static` sin regresión contra la base 7/8 de `de02471a`.

---

## Resultado medido (cierre del frente)

En contenedor propio montando este worktree —el `docker compose` del repo sirve el árbol principal
y daría un «después» falso; comprobado que el puerto principal no tenía este CSS y el propio sí—,
proyecto Da Porto, 1180×820, dark, sesión por la puerta de servicio:

| | Antes | Después |
|---|---|---|
| Chips de la leyenda visibles | 8 | **1** |
| Controles visibles en pantalla | 64 | **57** |
| Alto del bloque de leyenda | 88px | **44px** |

Nota sobre el 64 y el 63: la primera medición de este spec dio **63**, tomada en el árbol
principal antes de existir el worktree; el «antes» de esta tabla dio **64**, tomado en el
contenedor propio poniendo `OCULTAR_CONTADORES_EN_CERO = false`. La diferencia es de instrumento,
no del cambio. **La comparación válida es la de esta tabla**, porque sus dos extremos se midieron
con el mismo contenedor, el mismo proyecto y el mismo contador; comparar 63 con 57 mezclaría dos
montajes distintos, que es justo el error que este frente documenta en
[[valor-declarado-no-es-valor-computado]].

- **Guarda del filtro verificada:** con un filtro activo vuelven los 8 y se puede saltar de uno a
  otro. Sin ella, ocultar por el valor a secas habría borrado los siete botones de filtro justo
  cuando hacen falta para salir del filtro puesto — un fallo que solo aparece en un estado al que
  hay que llegar a propósito.
- **Reversión verificada, no descrita:** `OCULTAR_CONTADORES_EN_CERO = false` devuelve 64 controles
  y los 8 chips, con el atenuado de C-24 intacto.
- **Gate:** `static` 7/8, `audit` verde en **170/175**, re-verificado **después** de integrar
  `origin/main`. `node-tests` rojo preexistente (test de mtime, artefacto del worktree).

### Dos cosas que el plan no podía prever

1. **La regla acabó en la subcapa `components.components`.** `buttons.css:971` fuerza
   `display: inline-flex !important` desde ahí, y para `!important` la subcapa gana a su capa
   madre: la regla estaba en el CSSOM, casaba con el elemento, y el computado seguía siendo `flex`.
   No se tocó `buttons.css`, que es de PG, PI y PS. Documentado en [[css-layer-cascade]].
2. **Hubo que restar para poder sumar.** El `!important` necesario llevaba el módulo a 176 sobre un
   presupuesto de 175. En vez de subir el techo, bajó la cifra: seis `!important` que no competían
   con nadie salieron del módulo, confirmados **uno a uno** contra la línea base. No se tocaron las
   de color (solo se midió dark) ni el `min-height` de la barra (piso AA de 24px).

### Los goldens no se regeneraron, y esa es la conclusión correcta

Había aprobación explícita del usuario para regenerarlos. **No se usó.** El fixture del test visual
siembra los nueve estados, uno por fila, así que en esa captura ningún contador está en cero y este
cambio no la altera. El test falla por deriva **preexistente** —el selector de semana y un botón—,
confirmado ejecutando el mismo test contra el árbol principal **sin este código**: falla igual y en
las mismas dos zonas. Regenerar habría congelado una deriva ajena, y un número de semana que cambia
según cuándo se corra, bajo una firma nueva.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem; seccion «Resultado medido» con 8-1 chips y 64-57 controles

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
