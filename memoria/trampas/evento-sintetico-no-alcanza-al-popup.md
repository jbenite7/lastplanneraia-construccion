---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [qa]
fuente: sesión del 2026-08-18, diagnóstico de «Escape no cierra los modales»
resumen: "SweetAlert2 registra su listener de teclado en el elemento del popup, no en document: un `dispatchEvent` sobre `document` no prueba nada, siempre sale negativo"
---
**Un evento sintético solo prueba algo si se lanza donde el código escucha.** Medido el
2026-08-18: `document.dispatchEvent(new KeyboardEvent('keydown', {key:'Escape', bubbles:true}))`
no cierra un diálogo de SweetAlert2 **por diseño**, y de ahí no se sigue nada sobre la aplicación.

SweetAlert2 11.4.24 elige el destino del listener según su propia opción `keydownListenerCapture`,
que por defecto es `false`:

```js
globalState.keydownTarget = innerParams.keydownListenerCapture ? window : getPopup()
```

Es decir, **escucha en el `div.swal2-popup`**. Un evento despachado en `document` viaja hacia
abajo por captura y hacia arriba por burbuja desde su propio `target` —que es `document`—, y el
popup no está en ese camino: es un descendiente. El handler no se ejecuta nunca. Con una pulsación
real sí funciona, porque el foco está dentro del popup (`focusConfirm: true` deja el foco en
`.swal2-confirm`) y el evento burbujea desde ahí hasta el popup.

**Cómo se disfraza de bug de la aplicación.** El 2026-08-18 esta medida, junto con
[[panel-browser-no-anima]], sostuvo un informe de que «Escape no cierra ningún modal de la
aplicación» que era falso, y llegó a escribirse como comentario en `views/auth/login.view.php`
afirmando un defecto de la librería que no existe.

**Why:** un negativo obtenido con el instrumento apuntando al sitio equivocado es indistinguible
de un negativo real, y encima se siente más riguroso que la prueba manual — «lo comprobé con y sin
pulsación real» suena a dos evidencias cuando puede ser cero.

**How to apply:** antes de concluir nada de un `dispatchEvent`, comprobar que el handler se
ejecutó, no solo que el efecto no ocurrió. Dos sondas baratas:

- envolver `EventTarget.prototype.addEventListener` un momento y registrar sobre qué nodo se
  engancha el `keydown` —así salió este caso—;
- mirar `event.defaultPrevented` después de despachar: si el handler corrió y llamó a
  `preventDefault()`, el problema está **después** del teclado, no en él.

En este caso la segunda sonda dio `defaultPrevented: true` con el evento lanzado sobre el propio
popup: el teclado estaba bien y el fallo estaba en el desmontaje, que espera `animationend`.

Relacionado: [[panel-browser-no-anima]] (el otro falso negativo del mismo diagnóstico),
[[el-dom-dice-que-existe-no-que-se-ve]] y [[valor-declarado-no-es-valor-computado]], que son la
misma clase de error: medir una cosa y concluir sobre otra.
