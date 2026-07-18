(() => {
  var storedTheme = "dark";
  try {
    storedTheme = window.localStorage.getItem("aia-theme") || "dark";
  } catch (_error) {
    // Dark remains the deterministic default when storage is unavailable.
  }

  var theme =
    storedTheme === "light" ? "linen" : storedTheme === "linen" || storedTheme === "dark" ? storedTheme : "dark";
  var root = document.documentElement;
  root.setAttribute("data-aia-theme", theme);
  root.classList.toggle("aia-theme-dark", theme === "dark");
  root.classList.toggle("aia-theme-linen", theme === "linen");
})();
