# PDC · Auditoría del módulo de ensamble

Registro de auditoría del pase `improve-app` sobre `/plan-compras#/ensamble/*`. Tracker:
`docs/IMPROVE-PDC-PLAN.md`. Backlog ICE compartido: `docs/EXPERIMENTS.md`.

Todo lo de aquí está **medido en navegador** a 1180×820, dark, proyecto **Da Porto** (id 73), el
2026-08-06. Nada de esto se dedujo de leer el código: el código sirvió para explicar la medición,
no para sustituirla.

> **Aviso sobre el instrumento.** Dos de los siete hallazgos —`P-1` y `P-5`— no eran defectos, sino
> límites de la herramienta con la que se miraron, y ambos se cerraron el 2026-08-06:
>
> - **Capturas de pantalla:** medir en navegador no basta si el navegador no está pintando. `P-1`
>   salió de capturas tomadas con la pestaña en `hidden` y `requestAnimationFrame` detenido.
>   Comprobar `document.visibilityState === "visible"` y que `rAF` avanza. Detalle en §P-1.
> - **Lectura del árbol de accesibilidad:** solo imprime texto en los nodos hoja, así que cualquier
>   celda o cabecera con envoltorio parece «vacía». Para juzgar accesibilidad hay que pedir el
>   **nombre accesible calculado** (`getByRole(rol, { name })`), no leer el volcado del árbol. De ahí
>   salió `P-5`.

## UX Audit Findings

| Id | Lente | Hallazgo | Sev | Arreglo | Estado |
|---|---|---|---|---|---|
| `P-1` | ux-heuristics / refactoring-ui | ~~**La grilla del Plan se pinta con el texto solapado e ilegible.** Al cargar, las 20 filas quedan a 28 px (la altura base del tema) con contenido de dos y tres renglones.~~ **No es un defecto de la aplicación: era un artefacto de la captura.** El síntoma solo aparece en un navegador cuyo bucle de render está suspendido (`document.visibilityState === "hidden"`, `requestAnimationFrame` sin correr), que es como se tomaron las capturas de la auditoría. En ventana visible la grilla se acomoda sola en ~0,7 s. Ver §P-1 abajo. | — | **Ninguno.** `pdc-app/src/lib/agGrid.ts` y `PlanFechas.tsx` quedan intactos. | **cerrado · no reproducible (2026-08-06)** |
| `P-2` | refactoring-ui / jobs-to-be-done | **En «Sin frente» el nombre del paquete recibía 39 px de ancho** y se partía a una palabra por renglón («Suministro / BASE / Y / SUB / BASE / GRANULAR»), mientras el chip decorativo «CORRESPONDENCIA · CONFIANZA ALTA» se llevaba 190 px — casi 5× más. Cada fila medía 96–135 px, así que de los **73 paquetes por amarrar solo cabían 3 en pantalla**. Es el defecto que golpea de lleno al job (§Fase 1: «cuesta armarlo la primera vez»). Causa: las columnas fijas sumaban 828 px dentro de un contenedor de 867 útiles, y la media query de rescate miraba el ancho del **viewport** (1180) cuando el que se queda corto es el **contenedor** (867: la barra lateral se lleva 325). | 4 | Rejilla con `max-content` para lo accesorio y el sobrante al nombre; corte por `@container`, no por viewport; chip acortado con el origen en `title`. | **aplicado y verificado** |
| `P-3` | made-to-stick | **La cabecera enseñaba «73 %» pegado a «20 de 93 paquetes con fecha»**, que es el 21 %. Son dos métricas distintas —una del valor, otra del conteo— presentadas como si fueran la misma cifra, y ninguna decía cuál era cuál. El comentario del código ya avisaba de que el número grande es la plata; la pantalla no. | 3 | El detalle ahora dice «del valor · 20 de 93 paquetes con fecha». Ningún cálculo tocado. | **aplicado y verificado** |
| `P-4` | design-everyday-things | **El botón de asignación en masa arranca proponiendo una acción destructiva.** Con el selector en su valor inicial («Sin asignar») el botón lee «Quitar responsable a 0 paquetes», junto a un contador que dice «20 sin responsable». El estado por defecto de la pantalla ofrece *quitar* lo que no hay, cuando lo que hace falta es *asignar*. Además nada indica que primero hay que marcar filas con la casilla: el botón está apagado y no dice por qué. | 3 | Se separa «todavía no he elegido» de «he elegido dejarlo sin responsable»: el selector arranca en «Elige a quién asignar…» (centinela `MASA_SIN_ELEGIR`, que no viaja al servidor) y quitar el responsable pasa a ser la opción explícita «Sin asignar — quitar responsable». El botón apagado dice su causa: «Marca paquetes en la tabla» → «Elige a quién asignar» → «Asignar a N paquetes». El mensaje de éxito ya no dice «asignado» cuando lo que hizo fue quitar. Regla en `accionMasaResponsable()` (`lib/planFechas.ts`), con TDD. | **aplicado y verificado** |
| `P-5` | ux-heuristics (a11y) | ~~**Las cabeceras de columna de la grilla no exponen texto al árbol de accesibilidad**: ocho `columnheader` vacíos.~~ **Falso, y por el mismo motivo que `P-1`:** la herramienta de lectura del árbol solo imprime texto en los nodos hoja, así que toda celda o cabecera con envoltorio sale «vacía» aunque tenga nombre. Contrastado con el cálculo real de Chromium sobre la propia grilla del Plan: `Paquete`, `Frente`, `Arranque`, `Necesidad`, `Responsable` y `Estado` resuelven 1 coincidencia cada uno por nombre accesible, y las celdas también (`Sum + Inst BOMBEO DE CONCRETO`). `Días` da 0 solo porque a 1180 px esa columna se oculta a propósito. | — | **Ninguno.** AG Grid ya expone el nombre. | **cerrado · no reproducible (2026-08-06)** |
| `P-6` | refactoring-ui | **La rejilla de «Sin frente» asigna columnas por orden de aparición**, así que una fila con la etiqueta «lote de obra» mete un hijo de más y desplaza todo lo que va detrás: en esas filas el selector, el botón y el chip caen en la columna equivocada. No se ve en Da Porto porque no hay lotes; se ve en cuanto los haya. | 2 | Asignar `grid-column` explícito por selector en vez de fiarlo al orden. No aplicado: sin datos con los que verificarlo, sería un cambio a ciegas. | registrado |
| `P-8` | systematic-debugging (reporte del usuario) | **«Aceptar N de confianza alta» no amarraba nada, nunca.** Cero POST a `/amarrar` y «0 paquetes amarrados por sugerencia del motor», medido interceptando la red. Causa raíz: el mapa `destinos` indexa por `claveDestino()` («73:0»), pero el botón masivo y la lista previa de confianza media leían `destinos[paqueteId]` («73») — clave inexistente, cada paquete caía en el `continue` de «sin elegir» y el lote se saltaba en silencio. El mismo desajuste hacía que la lista previa de confianza media enseñara «sin frente elegido» en las 9 filas. | 4 | Helper `destinoDePaquete()` en `lib/planFechas.ts` como única lectura legítima por paquete entero, usado en las dos lecturas rotas. TDD: test rojo primero (104/104 verde después). Verificado en navegador con el panel de confianza media —9/9 resuelven su frente, 0 «sin frente elegido»— y cancelado sin escribir datos. | **aplicado y verificado** |
| `P-7` | high-perf-browser | La pantalla dispara **ocho peticiones API en paralelo** al cargar (`plan`, `frentes`, `anclas`, `correspondencias`, `sugerencias`, `desfases`, `responsables`, `resumen`). Todas responden 200. Sin baseline de INP/LCP todavía: se anota como punto de partida de la fase 8, no como defecto. | 1 | — | registrado |

## §P-1 · Cerrado: no era un bug, era el espejo (2026-08-06)

Sesión de `systematic-debugging`. **Conclusión: `P-1` no existe como defecto de la aplicación.** El
síntoma es real, pero solo se produce en un navegador que no está pintando, y así se tomaron las
capturas de esta auditoría. El usuario confirmó que en su navegador no se reproduce.

### La cadena de evidencia

1. **La medición de AG Grid nunca falló.** Envolviendo `ResizeObserver` en la propia página —su
   `contentRect` llega ya medido, así que registrarlo **no** fuerza reflujo— se ve que tras el
   ajuste de ancho las celdas `nombre`/`frente`/`estado` miden 50 px, y 75 px las de tres
   renglones: exactamente los 52/77 de la pantalla correcta.
2. **Lo que no ocurre es la escritura en el DOM.** Muestreando desde dentro de la página solo
   `style.height` (lectura que no fuerza layout), las filas se quedan en `28px` desde t≈1,1 s hasta
   t≈17,3 s. Reproduce el «13 s de muestreo» del informe original.
3. **La causa: el bucle de render está parado.** En ese estado,
   `document.visibilityState === "hidden"` y un contador de `requestAnimationFrame` marca **0
   ticks**. AG Grid aplica los cambios de altura de fila por su cola de *animation frame*
   (`RowAutoHeightService` → `calculateRowHeights` → repintado diferido): sin frames, la altura se
   calcula, se guarda en el `rowNode` y nunca llega al elemento.
4. **Cualquier cosa que despierte el render lo arregla**, incluido un simple movimiento de ratón,
   sin JS ni cambio de ancho de por medio.
5. **En ventana real y visible** (Chromium con ventana, no headless): `visibilityState: "visible"`,
   `rAF` corriendo, y el muestreo registra `28px → 52px` a los **688 ms**, solo.

### Por qué fallaron los tres intentos previos

Los tres —`resetRowHeights()` tras `sizeColumnsToFit()`, diferirlo un `requestAnimationFrame`,
atarlo a `document.fonts.ready`— atacaban la **medición del ancho**, que estaba bien. Y ninguno
podía funcionar por construcción: **ningún código de la aplicación puede arreglar un
`requestAnimationFrame` que no corre.** Por la misma razón «un cambio real de ancho lo arregla» no
significaba que el camino de recuperación remidiera bien: significaba que reanudaba una escritura
pendiente. Y «medir con `getBoundingClientRect` altera el resultado» no era que la medición
estorbara, sino que despertaba la pintura.

Se revirtió toda la instrumentación: `pdc-app/src/pages/PlanFechas.tsx` y
`public/pdc-app/assets/pdc.js` quedan **byte a byte** como estaban.

### Nota metodológica (esto es lo que hay que llevarse)

Una captura de la SPA hecha con navegador automatizado **no sirve** para juzgar alturas de fila,
posiciones ni nada que dependa de `requestAnimationFrame`: el panel puede entregar la imagen con la
pestaña en `hidden` y el render suspendido. Antes de anotar un hallazgo visual medido así,
comprobar `document.visibilityState === "visible"` y que `rAF` avanza. Vale para cualquier grilla
del módulo, no solo para esta.

## Lo aplicado en esta sesión

Dos cambios, ambos verificados en navegador tras recompilar el bundle (`npm run build` en
`pdc-app/`, TypeScript incluido):

- **`pdc-app/src/styles.css`** — rejilla de «Sin frente». Nombre del paquete: **39 → 287 px**
  (7,4×). Chip de confianza: 190 → 116 px. Alto de fila: **96–135 → 48–57 px** (media 50), o sea
  poco más de la mitad, con lo que se ve el doble de paquetes por pantalla. Sin desbordamiento
  horizontal a 1180.
- **`pdc-app/src/pages/PlanFechas.tsx`** — el chip de confianza pierde el prefijo redundante y gana
  `title` con el origen; el detalle de cobertura nombra su unidad («del valor · …»).

## Pendiente de decisión del usuario

Ninguna: las tres se resolvieron el 2026-08-06.

1. ~~**`P-1`**~~ — cerrado: no era un defecto de la aplicación. Ver §P-1.
2. ~~**`P-4`**~~ — el usuario eligió separar «elegir» de «quitar»; aplicado y verificado.
3. ~~**`P-5`**~~ — cerrado: las cabeceras nunca estuvieron mudas, era el instrumento de medición.

## Reporte de compras en la Control Tower · rediseño narrativo (2026-08-06)

Alcance distinto al del resto de este archivo: no es la SPA de `/plan-compras`, sino la pestaña
**Plan de Compras** de `/bi/control-tower`, que hasta hoy eran cuatro tablas seguidas sin ninguna
frase que dijera qué mirar. Decidido con el usuario: **riesgo primero**, gráficos arriba y tablas
al detalle plegable. Medido en Da Porto, 1180×820, dark.

### Hallazgos que motivaron el cambio

| Id | Hallazgo | Severidad | Estado |
|---|---|---|---|
| `T-1` | El reporte abría con una tabla «Indicador / Valor / Acción». Nada decía qué pasa: había que leer 27 celdas para descubrir que 174 compras están vencidas | 3 | **aplicado** — titular generado del corte + tres tarjetas de cifra |
| `T-2` | El horizonte de vencimientos existía en el servicio (`vencimientosAgregados()['totales']`, siete cubetas) pero **no viajaba al front**: `pdcBreakdown()` solo mandaba paso y responsable, y las filas por obra colapsan las seis cubetas en «vencidos» y «en riesgo» | 3 | **aplicado** — `totales` expuesto y dibujado |
| `T-3` | «Dónde se atasca» y «quién lo tiene encima» eran tablas de tres columnas ordenadas alfabéticamente por el navegador; el cuello de botella no saltaba a la vista | 2 | **aplicado** — barras horizontales apiladas, ordenadas por carga |
| `T-4` | El resumen ejecutivo mostraba «PDC en riesgo» sin ninguna vía para ir a mirarlo | 2 | **aplicado** — tarjeta «Compras vencidas» que abre la pestaña |
| `T-5` | La cubeta «más adelante» (443 pasos en Da Porto) aplasta la escala de lo urgente si se dibuja junto a las demás | 2 | **aplicado** — queda fuera del gráfico y se declara en la nota al pie, no se oculta |

### Registrado, no aplicado

| Id | Hallazgo | Por qué no se tocó |
|---|---|---|
| `T-6` | **El resumen ejecutivo se contradice con su propio tablero:** dice «el nivel de riesgo de la obra es bajo» en la misma pantalla donde 174 compras están vencidas. `composeExecutiveBrief()` no mira compras | Cambiar el diagnóstico ejecutivo es dominio, no presentación. Pide decisión del usuario |
| `T-7` | Con una sola obra en el filtro, «cuánto del plan está armado» es un gráfico de dos barras: la forma no aporta sobre el número | Solo molesta en proyecto único; con cartera se lee bien. No vale la excepción todavía |

### Trampa medida, para quien siga

- **`status-critical` no es un color de dato.** Resuelve a `#ffcdc8`, un rosa pálido pensado para
  texto de estado: la barra de «ya vencido» salió rosa claro sobre fondo oscuro. El rojo de series
  es `critical` (`oklch(65% 0.18 26.3)`). Queda un uso viejo de `status-critical` como color de
  serie en `bi-spa.js:3704`, sin auditar en esta pasada.
- **Las utilidades de rejilla del BI no son Tailwind completo.** `public/css/design-system/adapters/bi-utilities.css`
  define a mano solo `md:grid-cols-2`, `lg:grid-cols-2/3/4` y `xl:grid-cols-2/3`. Escribir
  `md:grid-cols-3` o `xl:grid-cols-5` no falla: la rejilla se queda en una columna, en silencio.
