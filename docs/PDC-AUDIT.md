# PDC · Auditoría del módulo de ensamble

Registro de auditoría del pase `improve-app` sobre `/plan-compras#/ensamble/*`. Tracker:
`docs/IMPROVE-PDC-PLAN.md`. Backlog ICE compartido: `docs/EXPERIMENTS.md`.

Todo lo de aquí está **medido en navegador** a 1180×820, dark, proyecto **Da Porto** (id 73), el
2026-08-06. Nada de esto se dedujo de leer el código: el código sirvió para explicar la medición,
no para sustituirla.

## UX Audit Findings

| Id | Lente | Hallazgo | Sev | Arreglo | Estado |
|---|---|---|---|---|---|
| `P-1` | ux-heuristics / refactoring-ui | **La grilla del Plan se pinta con el texto solapado e ilegible.** Al cargar, las 20 filas quedan a 28 px (la altura base del tema) con contenido de dos y tres renglones: el nombre del paquete y el frente se pintan encima de la fila siguiente. Afecta a las 5 filas vencidas, que son las primeras y las que motivan la pantalla. **No es un parpadeo**: se mantuvo estable durante 13 s de muestreo y solo se corrige cuando algo ajeno fuerza un reflow (redimensionar el contenedor, o inspeccionarlo con las herramientas del navegador — por eso «se arregla solo» al mirarlo). Reproducido también en `#/ensamble/paquetes`, así que es de la grilla compartida. | 4 | **Sin causa raíz confirmada.** Ver §P-1 abajo. | **bloqueante · abierto** |
| `P-2` | refactoring-ui / jobs-to-be-done | **En «Sin frente» el nombre del paquete recibía 39 px de ancho** y se partía a una palabra por renglón («Suministro / BASE / Y / SUB / BASE / GRANULAR»), mientras el chip decorativo «CORRESPONDENCIA · CONFIANZA ALTA» se llevaba 190 px — casi 5× más. Cada fila medía 96–135 px, así que de los **73 paquetes por amarrar solo cabían 3 en pantalla**. Es el defecto que golpea de lleno al job (§Fase 1: «cuesta armarlo la primera vez»). Causa: las columnas fijas sumaban 828 px dentro de un contenedor de 867 útiles, y la media query de rescate miraba el ancho del **viewport** (1180) cuando el que se queda corto es el **contenedor** (867: la barra lateral se lleva 325). | 4 | Rejilla con `max-content` para lo accesorio y el sobrante al nombre; corte por `@container`, no por viewport; chip acortado con el origen en `title`. | **aplicado y verificado** |
| `P-3` | made-to-stick | **La cabecera enseñaba «73 %» pegado a «20 de 93 paquetes con fecha»**, que es el 21 %. Son dos métricas distintas —una del valor, otra del conteo— presentadas como si fueran la misma cifra, y ninguna decía cuál era cuál. El comentario del código ya avisaba de que el número grande es la plata; la pantalla no. | 3 | El detalle ahora dice «del valor · 20 de 93 paquetes con fecha». Ningún cálculo tocado. | **aplicado y verificado** |
| `P-4` | design-everyday-things | **El botón de asignación en masa arranca proponiendo una acción destructiva.** Con el selector en su valor inicial («Sin asignar») el botón lee «Quitar responsable a 0 paquetes», junto a un contador que dice «20 sin responsable». El estado por defecto de la pantalla ofrece *quitar* lo que no hay, cuando lo que hace falta es *asignar*. Además nada indica que primero hay que marcar filas con la casilla: el botón está apagado y no dice por qué. | 3 | Propuesto: que el botón apagado diga su causa («Marca paquetes en la tabla para asignar»), y que el arranque no sea la rama destructiva. No aplicado: cambia comportamiento. | registrado |
| `P-5` | ux-heuristics (a11y) | **Las cabeceras de columna de la grilla no exponen texto al árbol de accesibilidad**: el lector devuelve ocho `columnheader` vacíos y las filas sin contenido accesible. Medido con la lectura del árbol de la página. | 3 | Sin arreglo propuesto todavía: es contrato de accesibilidad de AG Grid y merece medirse contra el suelo de a11y del sistema antes de tocar. | registrado |
| `P-6` | refactoring-ui | **La rejilla de «Sin frente» asigna columnas por orden de aparición**, así que una fila con la etiqueta «lote de obra» mete un hijo de más y desplaza todo lo que va detrás: en esas filas el selector, el botón y el chip caen en la columna equivocada. No se ve en Da Porto porque no hay lotes; se ve en cuanto los haya. | 2 | Asignar `grid-column` explícito por selector en vez de fiarlo al orden. No aplicado: sin datos con los que verificarlo, sería un cambio a ciegas. | registrado |
| `P-8` | systematic-debugging (reporte del usuario) | **«Aceptar N de confianza alta» no amarraba nada, nunca.** Cero POST a `/amarrar` y «0 paquetes amarrados por sugerencia del motor», medido interceptando la red. Causa raíz: el mapa `destinos` indexa por `claveDestino()` («73:0»), pero el botón masivo y la lista previa de confianza media leían `destinos[paqueteId]` («73») — clave inexistente, cada paquete caía en el `continue` de «sin elegir» y el lote se saltaba en silencio. El mismo desajuste hacía que la lista previa de confianza media enseñara «sin frente elegido» en las 9 filas. | 4 | Helper `destinoDePaquete()` en `lib/planFechas.ts` como única lectura legítima por paquete entero, usado en las dos lecturas rotas. TDD: test rojo primero (104/104 verde después). Verificado en navegador con el panel de confianza media —9/9 resuelven su frente, 0 «sin frente elegido»— y cancelado sin escribir datos. | **aplicado y verificado** |
| `P-7` | high-perf-browser | La pantalla dispara **ocho peticiones API en paralelo** al cargar (`plan`, `frentes`, `anclas`, `correspondencias`, `sugerencias`, `desfases`, `responsables`, `resumen`). Todas responden 200. Sin baseline de INP/LCP todavía: se anota como punto de partida de la fase 8, no como defecto. | 1 | — | registrado |

## §P-1 · Lo que ya está descartado

Tres hipótesis probadas y **descartadas**, cada una con su medición. Vale la pena dejarlas escritas
para que la sesión que lo retome no las repita:

1. **«Es un parpadeo de arranque.»** No. Muestreadas las alturas cada 250 ms durante 13 s: las 20
   filas se mantienen en 28 px, con separación de 28 px entre `top` consecutivos.
2. **«`resetRowHeights()` tras `sizeColumnsToFit()` lo arregla.»** No basta, ni en la misma vuelta
   ni diferido un frame con `requestAnimationFrame`.
3. **«Es la fuente web, que llega después de medir.»** Atar el remedido a `document.fonts.ready`
   tampoco lo arregla en la carga inicial.

Lo que **sí** está confirmado: un cambio real de ancho del contenedor (que dispara el
`ResizeObserver` → `onGridSizeChanged` → `sizeColumnsToFit`) deja las alturas correctas
(52/52/52/52/52/77) y la pantalla se pinta perfecta. Es decir, **el camino de recuperación
funciona; lo que falla es la primera medición.**

Los tres intentos de arreglo se **revirtieron**: `pdc-app/src/lib/agGrid.ts` está intacto. Parchear
a ciegas una librería que comparten siete tablas no es aceptable, y a la tercera hipótesis fallida
lo honesto es admitir que esto necesita una sesión propia de `systematic-debugging`, no otro
intento.

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

1. **`P-1`**: ¿se abre ya la sesión de `systematic-debugging`, o se difiere? Mientras siga abierto,
   la pantalla principal del módulo se lee mal en cada carga.
2. **`P-4`**: ¿el botón de masa puede dejar de arrancar en la rama destructiva? Es cambio de
   comportamiento.
3. **`P-5`**: ¿las cabeceras mudas de AG Grid entran en el suelo de accesibilidad del sistema, o se
   aceptan como excepción documentada igual que las de `.pdc-header`?
