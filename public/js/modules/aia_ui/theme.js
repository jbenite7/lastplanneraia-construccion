// Movimiento reducido: la única responsabilidad que le queda a este archivo.
// El tema lo decide theme-bootstrap.js (D12 claro de entrada, D14 preferencia por
// aparato, spec temas 2026-08-28). Hasta el 2026-09-06 este guion lo pisaba con
// "dark" sin condición en 19 pantallas —7 lo cargan a mano y 12 vía
// linksComunesHead2.js— y publicaba un global de tema que ningún código de
// producto consumía (solo un test lo esperaba). Conserva la ruta porque esas
// vistas y tres contratos la nombran; su contenido ya no toca el atributo ni la
// clase de tema: eso es asunto exclusivo del bootstrap.
(() => {
  if (!window.matchMedia) return;
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const applyMotion = () => {
    document.documentElement.classList.toggle("aia-no-motion", motion.matches);
  };
  applyMotion();
  if (motion.addEventListener) {
    motion.addEventListener("change", applyMotion);
  } else if (motion.addListener) {
    motion.addListener(applyMotion);
  }
})();
