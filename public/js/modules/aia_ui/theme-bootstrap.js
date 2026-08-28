// D12 (spec temas 2026-08-28, decisión de Felipe): claro de entrada — el claro
// es la cara del producto. La elección manual persiste por aparato (D14) y gana.
const DEFAULT_THEME = "light";

(function applyThemeEarly() {
  let stored = null;
  try {
    stored = localStorage.getItem("aia-theme");
  } catch (_) {
    /* privado/bloqueado */
  }
  const theme = stored === "dark" || stored === "light" ? stored : DEFAULT_THEME;
  var root = document.documentElement;
  root.setAttribute("data-aia-theme", theme);
  root.classList.toggle("aia-theme-dark", theme === "dark");
})();
