---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-14
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-14-shell-menu-flotante-responsive-design.md
resumen: Menú flotante del shell por debajo de 1180 px
---

# Menú flotante del shell por debajo de 1180 px

- Fecha: 2026-08-14
- Estado: aprobado en brainstorming, pendiente de plan
- Origen: hallazgos `V-1` y `V-2` de la fase 2 (móvil) de `improve-app`, medidos el 2026-08-13
  (`docs/DESIGN-AUDIT.md` §Lente de usabilidad sobre móvil y tablet)
- Relacionado: [`2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje.md`](../plans/2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje.md),
  cuya Task 4 usa el mismo umbral de 1180 px

## El problema, medido

En un viewport de 390 px la navegación lateral ocupa **240 px — el 60 % de la pantalla** — y deja
el contenido en una columna de **203 px**: las tarjetas de Programación Intermedia miden 171 px de
ancho por 944 px de alto y el texto se parte letra a letra. La barra de acciones se desborda.

La causa no son las tarjetas, que están bien construidas: **el sidebar no colapsa nunca por ancho
de pantalla.** `public/js/modules/aia_ui/sidebar_navigation.js` no consulta `matchMedia` ni
`innerWidth` en ninguna línea, y `public/css/design-system/adapters/shell-sidebar.css` solo tiene
media queries de `prefers-reduced-motion`. El estado vive en `localStorage` bajo la clave
`aia-sidebar-state` y **se comparte entre escritorio y móvil**: quien expandió el menú en su
portátil se lo encuentra expandido en el teléfono.

Ninguna prueba lo detectó porque todas miden a 1180 px o más.

## Decisiones

| # | Decisión | Alternativas descartadas |
|---|---|---|
| D1 | Por debajo del umbral el menú **se esconde y se abre por botón, flotando sobre el contenido**. | Dejarlo como columna de iconos de 64 px (más barato, pero sigue restando ancho); barra de pestañas inferior (once módulos no caben). |
| D2 | El umbral es **1180 px**, el mismo que separa tabla de tarjetas. Un solo corte para toda la app. | 768 px (deja la tablet apretada justo cuando empieza a mostrar tarjetas); 1200 px, el que usa el laboratorio (dejaría 20 px con tabla y menú flotante a la vez). |
| D3 | La preferencia guardada **manda solo por encima del umbral**. Por debajo el menú arranca siempre cerrado y abrirlo **no escribe** `localStorage`. | Una sola preferencia para todo (es el comportamiento actual, el que deja el móvil inservible); dos preferencias separadas por tramo (dos estados que mantener para algo que casi nadie quiere). |
| D4 | Se crea `shell-drawer.js` como **pieza canónica**, consumida solo por el shell. El laboratorio conserva su copia y su migración queda anotada como deuda con dueño. | Extraer ya la lógica de `design_system_lab.js` y unificar (correcto a largo plazo, pero toca un archivo cubierto por gates visuales y de accesibilidad que hoy están verdes); resolverlo solo con CSS (el cierre con Escape, el foco y los estados de accesibilidad exigen JS igualmente). |

## Arquitectura

### Unidades

| Unidad | Responsabilidad | Depende de |
|---|---|---|
| `public/js/modules/aia_ui/shell-drawer.js` | **Nueva y canónica.** Convierte un contenedor de navegación en flotante por debajo de un umbral: modo, apertura, cierre, foco y atributos de accesibilidad. **No** sabe qué es un sidebar ni qué es una preferencia. | nada |
| `public/js/modules/aia_ui/sidebar_navigation.js` | La consume y aporta lo único que es suyo: **cuándo persistir**. Por encima del umbral, su comportamiento actual queda intacto. | `shell-drawer` |
| `public/css/design-system/adapters/shell-sidebar.css` | El modo flotante: `aside` fuera de flujo, velo, y el `body` sin `padding-left`. | tokens `--ds-*` |
| `views/partials/shell_sidebar.php` | Emite el disparador «Menú», visible solo por debajo del umbral. | — |

La frontera que importa: `shell-drawer` recibe el contenedor y el umbral, y no consulta
`localStorage` jamás. Esa separación es la que permite que D3 se cumpla por construcción y no por
disciplina.

### El botón hace falta, y no es un detalle

Hoy el control de colapsar vive **dentro** del `aside` (`[data-sidebar-toggle]`). Si el `aside` se
oculta, el botón se va con él y el menú queda inalcanzable. El marcado del shell necesita un
disparador propio en la barra de contexto, visible solo por debajo de 1180 px.

### Contrato de comportamiento

**Por encima de 1180 px** — idéntico a hoy: manda la preferencia, el toggle la escribe, el `body`
conserva su `padding-left`.

**Por debajo de 1180 px** — el `aside` deja de ocupar sitio (el `body` pierde el `padding-left`,
que es de donde salen los 240 px) y aparece el botón «Menú». Al pulsarlo el menú se despliega sobre
el contenido con un velo. Lo cierran el velo, la tecla `Escape` y elegir un destino; al cerrarse el
foco vuelve al botón. Mientras está abierto el foco no se escapa del panel. **Nada de esto escribe
en `localStorage`.**

**Al cruzar el umbral en caliente** — se recalcula el modo; si el menú estaba abierto se cierra y se
restaura la preferencia guardada.

## El obstáculo que parecía existir, y no existía

**Corrección del 2026-08-14, Task 1 de su propio plan.** Esta sección afirmaba que en
`/programa-general` el ancho del menú «no obedecía al estado del componente»: variable en 240 px,
estado `expanded`, ancho real 64 px. Al perseguirlo con la Task 1, resultó ser **un artefacto del
navegador integrado de la sesión de brainstorming**, no un bug del producto — ni siquiera un
`width: 240px !important` inline movía el ancho medido ahí, lo que ya no era CSS.

**Reproducido en Chromium real vía Playwright, el mismo día:** el ancho sigue al estado en los dos
sentidos. Con `aia-sidebar-state` ausente, el shell nace `collapsed` (64 px, `--aia-sidebar-width:
4rem`); con la preferencia en `expanded`, pasa a 240 px con `--aia-sidebar-width: 15rem`. Sin
código nuevo, sin retirar ninguna regla.

**La lección, que vale más que el hallazgo falso:** una anomalía de CSS que resiste incluso a un
`!important` inline no es CSS — es una señal de que el entorno de medición es sospechoso. Se
verifica en el motor real antes de escribirla en una spec como obstáculo bloqueante.

## Pruebas

**Sin navegador (`node --test`):** la decisión del borde, única lógica pura de la pieza —1179 px da
flotante, 1180 px no.

**Con navegador (Playwright, `390x844`):**
- El `aside` no ocupa ancho: el `body` sin `padding-left` y el contenido usando el viewport completo.
- El disparador «Menú» existe y es alcanzable.
- El ciclo abrir–cerrar con ratón y con teclado, incluido `Escape` y el retorno del foco al botón.
- **La que sostiene D3:** tras abrir el menú en móvil, `localStorage.getItem('aia-sidebar-state')`
  conserva su valor previo.

**Regresión de escritorio (`1180x820` y `1440x900`):** comportamiento idéntico al actual y ningún
golden movido. Si un golden de escritorio se mueve, es una regresión, no un efecto esperado.

## Condición de hecho

1. En `390x844`, el contenido dispone del ancho completo y el menú es alcanzable y cerrable con
   ratón y con teclado.
2. Abrir el menú por debajo del umbral **no modifica** `aia-sidebar-state`, comprobado leyendo la
   clave antes y después.
3. Por encima de 1180 px el comportamiento es indistinguible del actual, con los goldens de
   escritorio sin cambios.
4. `npm run test:design-system:static` en sus ocho puertas.

## Fuera de alcance

Migrar `design_system_lab.js` a la pieza nueva (deuda anotada con dueño). El umbral de las tarjetas
y el montaje condicional de Handsontable, que son las Tasks 4 y 5 del plan `f2a-2b-2`. Los hallazgos
`V-4` y `V-5` sobre `tablet-viewport-scale.js` (detección por `navigator.platform` y
`maximum-scale=1.2`). Los objetivos táctiles de `V-7`.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| El menú flotante tapa contenido o atrapa el foco mal, y en móvil no hay forma de salir. | El cierre tiene tres vías —velo, `Escape` y elegir destino— y la prueba de teclado es parte de la condición de hecho, no un extra. |
| Quitar el `padding-left` del `body` descoloca módulos que asumen ese offset. | El cambio es de una sola declaración en el adaptador y se verifica con los goldens de escritorio, que no deben moverse, más una pasada en 390 px por los módulos de la cascada. |
| Dos implementaciones de menú flotante conviviendo (shell y laboratorio) divergen. | La del shell nace como canónica y la migración del laboratorio queda anotada con dueño, no como intención. |
