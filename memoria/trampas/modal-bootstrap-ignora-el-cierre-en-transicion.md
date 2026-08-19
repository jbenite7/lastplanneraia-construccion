---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [qa, design-system]
fuente: tests/browser/programacion-semanal-roles-phases.mjs, public/vendor/bootstrap/bootstrap.min.js (4.3.1)
resumen: Bootstrap 4 ignora el cierre de un modal mientras la apertura sigue en transición, así que un e2e que cierra justo después de `toBeVisible()` es intermitente
---
`Modal.prototype.hide` de Bootstrap 4.3.1 empieza con `if (this._isTransitioning || !this._isShown)
return`: **el cierre se descarta en silencio** si el fundido de apertura no ha terminado. Y
`await expect(modal).toBeVisible()` de Playwright pasa en cuanto el modal se pinta, bastante antes
de ese final. Entre las dos cosas queda una ventana en la que el clic en «Cancelar» o «Cerrar» se
ejecuta, no da error, y el modal se queda abierto.

El síntoma es un **intermitente**, que es peor que un rojo: el 2026-08-13 el caso «calificación
expone controles y modales sin escribir datos» salió verde y rojo en corridas consecutivas sin
tocar nada, y la primera lectura fue atribuirlo a una regresión del producto. No lo era: el
producto cierra bien cuando se le deja terminar de abrir.

**Cómo cerrar sin intermitencia** — esperar a que Bootstrap declare acabada la transición, en vez
de dormir un número mágico de milisegundos:

```js
await page.waitForFunction(
  (id) => {
    const data = window.jQuery(`#${id}`).data('bs.modal');
    return Boolean(data) && data._isTransitioning !== true;
  },
  modalId,
);
```

El helper `dismissModal` de `tests/browser/programacion-semanal-roles-phases.mjs` es eso mismo con
la aserción de cerrado detrás. Tres corridas seguidas del par de casos afectados pasaron 3/3 con
él puesto; sin él, uno de cada dos o tres fallaba.

Ver también [[captura-playwright-miente]], que es la misma familia: evidencia tomada antes de que
la interfaz termine de asentarse.
