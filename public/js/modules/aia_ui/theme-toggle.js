// D13: botón «Volver a oscuro» visible en la nav durante el estreno (primer mes);
// su recogida al menú del usuario es una tarea del plan de cierre de la serie.
(() => {
  const THEME_KEY = "aia-theme";

  function currentTheme() {
    return document.documentElement.getAttribute("data-aia-theme") === "dark" ? "dark" : "light";
  }

  window.aiaThemeToggle = function aiaThemeToggle() {
    const next = currentTheme() === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-aia-theme", next);
    document.documentElement.classList.toggle("aia-theme-dark", next === "dark");
    try {
      localStorage.setItem(THEME_KEY, next);
    } catch (_) {
      /* sin persistencia */
    }
    refreshLabel();
  };

  function refreshLabel() {
    const btn = document.querySelector("[data-aia-theme-toggle]");
    if (!btn) return;
    const dark = currentTheme() === "dark";
    btn.textContent = dark ? "Volver a claro" : "Volver a oscuro";
    btn.setAttribute("aria-pressed", String(dark));
  }

  document.addEventListener("DOMContentLoaded", () => {
    const btn = document.querySelector("[data-aia-theme-toggle]");
    if (btn) btn.addEventListener("click", window.aiaThemeToggle);
    refreshLabel();
  });
})();
