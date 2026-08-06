---
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [qa, design-system]
fuente: sesion
resumen: "El panel Browser integrado tiene el reloj de animaciones congelado: nada que dependa de animationend o transitionend se puede juzgar ahí"
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

**Why:** el panel se usa como juez de comportamiento («esto funciona / esto no»), y para cualquier
cosa que dependa de que una animación termine, su veredicto es siempre «no funciona». Ese falso
negativo es indistinguible de una regresión real.

**How to apply:** ante un componente que no desaparece, no avanza o no dispara su callback, medir
`document.timeline.currentTime` dos veces separadas por un `setTimeout`. Si no avanza, el panel no
puede juzgar: repetir con Playwright, que sí anima (`chromium.launch()`, mismo viewport 1180×820
dark, contra el mismo contenedor). Vale para `animationend`, `transitionend`, `requestAnimationFrame`
y cualquier callback encadenado a ellos. Para lo estático —capas, tokens, texto, red— el panel sigue
siendo válido y más barato.

Relacionado: [[captura-playwright-miente]] (la otra herramienta de inspección que miente, por el
motivo contrario: sí corre, pero retrata el momento equivocado), [[auth-capado-y-sin-red-externa]].
