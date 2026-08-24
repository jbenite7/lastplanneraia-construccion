// El cuadrito de habilitacion, en un solo lugar. Antes de esto vivia
// duplicado -mismo marcado, misma logica- en `hot.js` (columna de la Task 4,
// contrato `{ prop, lectura }`) y en `readiness-popover.js` (globo/tarjeta
// movil de las Tasks 6-10, contrato `{ key, value, umbralRatio }`). El
// duplicado sobrevivio porque son dos sistemas de modulos distintos: `hot.js`
// es un script clasico y `readiness-popover.js` un modulo ES cargado con
// `type="module"`. Este archivo no elige bando: expone la misma funcion pura
// de DOM como export ES (para quien ya es modulo) y como `window.AIAReadinessBox`
// (para quien no lo es), igual que `readiness-cell.js` ya hace con su logica.
//
// Deliberadamente separado de `readiness-cell.js`: ese modulo es puro -sin
// DOM, sin Handsontable- porque asi lo pide la Task 2 del plan
// (`docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna.md`), y es
// lo que lo hace testeable con `node --test` sin navegador. Mezclar aqui la
// construccion de DOM habria diluido esa garantia para quien lea ese archivo
// esperando codigo puro.
export function construirCuadrito(prop, lectura) {
  const box = document.createElement('span');
  box.className = 'aia-readiness__box';
  box.setAttribute('data-restriccion', prop || '');

  if (lectura.esNoAplica) {
    box.classList.add('aia-readiness__box--na');
    return box;
  }
  if (lectura.cumple) {
    box.classList.add('aia-readiness__box--met');
    const check = document.createElement('span');
    check.className = 'aia-readiness__check';
    check.textContent = '✓';
    box.appendChild(check);
    return box;
  }
  const fill = document.createElement('span');
  fill.className = 'aia-readiness__fill';
  // El UNICO estilo inline permitido en esta obra: es un dato de la fila
  // (cuanto lleva la restriccion), no una decision de diseño.
  fill.style.height = Math.round(lectura.relleno * 100) + '%';
  box.appendChild(fill);
  return box;
}

if (typeof window !== 'undefined') {
  window.AIAReadinessBox = { construirCuadrito };
}
