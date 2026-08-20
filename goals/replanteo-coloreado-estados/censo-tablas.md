---
capa: fuente
tipo: censo
estado: vigente
fecha: 2026-08-20
areas: [design-system, programacion-intermedia, programa-general, programacion-semanal, plan-compras, admin]
fuente: goals/replanteo-coloreado-estados/censo-tablas.md
resumen: "Censo de recorte silencioso, fragmentación de palabra y tamaños de fuente en TODAS las tablas de la app, pedido por Felipe el 2026-08-20"
---

# Censo de tablas — recorte silencioso, fragmentación y tipografía

**Encargo de Felipe (2026-08-20):** «no revises solo la columna de estado, revisa todas las tablas
de todos los módulos». Barrido hecho por tres lecturas paralelas sobre el árbol del frente.

**La regla contra la que se mide:** nada se recorta sin que el usuario lo sepa. El texto **cabe**,
**envuelve entre palabras**, o se acorta con un **nombre corto oficial** declarado en contrato.
Nunca elipsis arbitraria, nunca partir una palabra a mitad, nunca esconder contenido sin aviso.
Envolver **no** es recortar: es la salida correcta cuando la celda admite más de una línea.

## Superficie censada

| Frente | Qué es | Tablas |
|---|---|---|
| Handsontable | PG, PG-Actualizar, PI, PS | 4 grillas |
| HTML producto | Control Tower (BI), Control de Cambios, CIC/CNC/CNP, modal de cambios, excepciones | 7 vistas |
| Admin | dashboard, proyectos, miembros, usuarios, catálogo de familias, limpieza PDC | 6 vistas |
| PDC v2 | SPA React + AG Grid | 5 pantallas de grilla |

## Hallazgos por gravedad

### 1. Contenido que desaparece sin ningún aviso (lo más grave)

- **`overflow-x: hidden` como envoltorio por defecto en Programación Semanal**, no `auto`:
  `styles.css:2138` (`.ps-table-wrap`), `styles.css:3344` (`#cuadroTabla`, **con `!important`**, así
  que ninguna regla puede reabrir el scroll), y el `.ps-page` general. En el viewport canónico la
  regla móvil de `access.css` no aplica, así que **lo que no cabe simplemente desaparece**: sin
  scroll, sin elipsis, sin sombra. Afecta a CIC, CNC, CNP y la vista de Semanal.
- **`.cm-table-shell { overflow: hidden }`** (`programacion-semanal.css:3288`) — mismo patrón en el
  modal de monitor de cambios, sin scroll alternativo.
- **PDC esconde columnas enteras por espacio sin decirlo**: `columnasQueCaben`
  (`pdc-app/src/lib/agGrid.ts:260-278`), usada en cinco pantallas. El mecanismo es sano (mide el
  contenedor real y respeta un orden de prescindibles), pero no hay indicador de «N columnas
  ocultas». El propio PDC ya sabe hacerlo bien en otro lado: sus tooltips sí avisan «y N más…».

### 2. Palabras partidas a mitad

- **`word-break: break-all`** en `programacion-semanal.css:1416` (primera columna de la tabla de
  excepciones): parte donde caiga, sin guion. Es el peor caso y no tiene comentario que lo
  justifique, mientras el resto del archivo explica por qué evita justamente eso.
- **`substring(0, 157) + '...'`** en `programacion_intermedia/hot.js:2374` (previsualización de
  restricción compartida): recorte por conteo de caracteres, puede cortar dentro de una palabra, sin
  tooltip ni indicio.
- **`hyphens: auto`** en `programacion-semanal.css:1469` para CIC/CNC/CNP: corta con guion — más
  suave, pero sigue alterando el texto sin acuerdo.
- **`word-break: break-word`** en `.cm-detail-cell` (`:3348`).

### 3. Elipsis en celdas y chips

~14 sitios en los módulos Handsontable (Semanal concentra 8), más `.pdc-tt-act`
(`pdc-app/src/styles.css:538`) en el tooltip de actividades del PDC, este último **sin `title` ni
fallback alguno**. La `@container (max-width: 120px)` de Semanal que esconde el nombre del estado sí
tiene respaldo declarado (aria-label + drawer), pero en pantalla el texto igual desaparece.

### 4. Tipografía fuera de rampa

Unos 20 valores literales conviven con la rampa densa consagrada (0.72 / 0.70 / 0.62rem):
`0.65, 0.66, 0.74, 0.75, 0.82, 0.84, 0.86, 0.92, 0.95rem` repartidos entre Intermedia, Semanal y BI.
Caso llamativo: `.cm-table` pinta la **celda más grande que su cabecera** (0.82 vs 0.72rem),
invirtiendo la jerarquía. Aparte, dos frentes corren con **escala propia declarada**: Admin
(0.75/0.875/1rem vía tokens `--ds-type-size-*`) y PDC (11-22px, decisión de densidad tipo hoja de
cálculo, medida y documentada el 2026-07-29).

## Lo que ya está bien, y conviene no romper

- **Los cuatro módulos Handsontable documentan sus anchos con medición real** (`columnMinWidths` y
  compañía, con comentarios que citan px y motivo). Es el patrón correcto y hay que imitarlo, no
  sustituirlo.
- **PDC v2 ya resolvió el problema por diseño**: `wrapText + autoHeight` en texto largo, anchos
  mínimos medidos contra el dato más ancho real, y un `overflow-wrap: normal` que anula
  explícitamente el `break-word` de fábrica de AG Grid que partía «SUBCONTRATACIO/N».
- **Cero cabeceras abreviadas a mano** en las 17 vistas revisadas: la sospecha inicial no se
  confirmó.
- Admin **no** es un sistema aislado: ya consume tokens de color, espaciado y z-index del design
  system. Su trabajo es conectar la escala tipográfica, no construir nada nuevo.

## Cómo se cierra cada clase (regla, no parche)

| Clase | Cierre |
|---|---|
| Desaparecer sin aviso | El envoltorio de tabla scrollea (`auto`) y **señala** que hay más; nunca `hidden` |
| Palabra partida | `word-break: normal` + `overflow-wrap: break-word` + `hyphens: none`; los anchos garantizan que la palabra más larga quepa |
| Elipsis | Nombre corto oficial en contrato (`displayShort`) cuando la celda es de una línea; envolver cuando admite dos |
| Columnas escondidas | Permitido, pero **anunciado** en pantalla, con el mismo criterio del «y N más…» que el PDC ya usa |
| Tipografía | Una rampa por familia de superficie, declarada en DESIGN.md; las escalas propias (Admin, PDC) se declaran como tales o se alinean |
