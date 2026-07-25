(() => {
  function applyTheme() {
    document.documentElement.setAttribute("data-aia-theme", "dark");
    document.documentElement.classList.add("aia-theme-dark");
    document.dispatchEvent(new CustomEvent("aia-theme-change", { detail: { theme: "dark" } }));
  }

  window.AiaDesignSystem = window.AiaDesignSystem || {};
  window.AiaDesignSystem.getTheme = () => "dark";
  document.dispatchEvent(new CustomEvent("aia-theme-ready"));

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
