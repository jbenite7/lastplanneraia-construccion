(() => {
  // F0/Task 9: con un solo tema no hay cambio de tema que anunciar. Los dos
  // eventos custom que este script emitia se retiraron: sus unicos oyentes
  // (bi_chart_theme.js, bi-spa.js) cargaban despues de este script en
  // views/bi/_layout.php y nunca podian recibirlos; el evento de "listo" no
  // tenia ningun oyente en el repositorio.
  function applyTheme() {
    document.documentElement.setAttribute("data-aia-theme", "dark");
    document.documentElement.classList.add("aia-theme-dark");
  }

  window.AiaDesignSystem = window.AiaDesignSystem || {};
  window.AiaDesignSystem.getTheme = () => "dark";

  applyTheme();

  if (window.matchMedia) {
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
  }
})();
