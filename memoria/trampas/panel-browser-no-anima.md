---
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [qa, design-system]
fuente: sesion
resumen: "El panel Browser integrado tiene el reloj de animaciones congelado: ni los callbacks de animación llegan, ni las medidas de una propiedad en transición valen — devuelven su valor inicial"
---
**El navegador integrado (`mcp__Claude_Browser__*`) no anima.** Medido el 2026-08-06:
`document.timeline.currentTime` marcó `0` antes y después de esperar **1778 ms** de reloj real
(`performance.now()`). Ninguna animación CSS avanza, de ninguna página: una animación de sonda
inyectada a mano —ajena por completo a la aplicación— se quedó con la `opacity` inicial y cero
eventos `animationend`.

**Cómo se disfraza de bug de la aplicación.** El síntoma que costó el diagnóstico: en
`/login?timeout=1`, el diálogo SweetAlert2 «Sesión Finalizada» **no se cerraba** al pulsar OK.
SweetAlert2 desmonta el popup en el evento `animationend` de `swal2-hide`; sin reloj, ese evento
no llega nunca y el popup se queda pegado para siempre. Todo lo demás parecía correcto y lo
confirmaba: la clase `swal2-hide` sí se aplicaba, `animation-name: swal2-hide`,
`animation-duration: 0.15s`, `play-state: running`, los `@keyframes` resolvían bien dentro de
`@layer vendor` — pero `getAnimations()[0].currentTime` seguía en `0`.

**El descarte que no basta.** Se comprobó primero que el fallo se daba igual con y sin el bundle de
Bootstrap cargado. Eso descarta a Bootstrap, no al navegador, y aun así se reportó como fallo real
de la aplicación. La comprobación que sí cierra el caso es la sonda propia: **si una animación que
tú acabas de escribir tampoco corre, el sospechoso no es la app**.

**La segunda cara: no hacen falta callbacks para caer.** Medido de nuevo el 2026-08-07, esta vez
sin ningún evento de por medio. En `/programa-general`, al pulsar `[data-sidebar-toggle]` el rail
del shell pasaba a `data-sidebar-state="expanded"` y `--aia-sidebar-width` computaba `15rem`, pero
`getComputedStyle(aside).width` seguía en `64px` y el `padding-left` del body también. Se reportó
como defecto del shell y no lo era: `getAnimations()` devolvía dos `CSSTransition` de `width` y
`min-width` en `playState: "running"` con sus keyframes correctos (`64px → 240px`, 220 ms) y
`currentTime: 0`. Sin reloj, **una transición se queda clavada en su valor inicial**, y como una
animación en curso gana sobre el estilo inline, ni siquiera `element.style.width = '15rem'` la
mueve — lo que despista hacia buscar un `!important` inexistente. Con
`document.getAnimations().forEach(a => a.finish())` el rail medía sus `240px` y el body seguía;
con Playwright, el ciclo completo `64 → 240 → 64`.

**Why:** el panel se usa como juez de comportamiento («esto funciona / esto no»), y para cualquier
cosa que dependa de que una animación termine, su veredicto es siempre «no funciona». Ese falso
negativo es indistinguible de una regresión real.

**How to apply:** ante un componente que no desaparece, no avanza o no dispara su callback —o ante
una medida geométrica que no cuadra con la regla CSS que sí existe y sí matchea—, medir
`document.timeline.currentTime` dos veces separadas por un `setTimeout`. Si no avanza, el panel no
puede juzgar: repetir con Playwright, que sí anima (`chromium.launch()`, mismo viewport 1180×820
dark, contra el mismo contenedor). Vale para `animationend`, `transitionend`, `requestAnimationFrame`
y cualquier callback encadenado a ellos. Para lo estático —capas, tokens, texto, red— el panel sigue
siendo válido y más barato.

Relacionado: [[captura-playwright-miente]] (la otra herramienta de inspección que miente, por el
motivo contrario: sí corre, pero retrata el momento equivocado), [[auth-capado-y-sin-red-externa]].
