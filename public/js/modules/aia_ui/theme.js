(() => {
  var storageKey = "aia-theme";
  var allowedThemes = { linen: true, dark: true };

  function normalizeTheme(theme) {
    if (theme === "light") return "linen";
    return allowedThemes[theme] ? theme : "dark";
  }

  function readTheme() {
    try {
      const stored = window.localStorage.getItem(storageKey);
      if (stored) return normalizeTheme(stored);
    } catch (_error) {
      // Local storage can be disabled in private sessions.
    }
    return "dark";
  }

  function applyTheme(theme) {
    var nextTheme = normalizeTheme(theme);
    document.documentElement.setAttribute("data-aia-theme", nextTheme);
    document.documentElement.classList.toggle("aia-theme-dark", nextTheme === "dark");
    document.documentElement.classList.toggle("aia-theme-linen", nextTheme !== "dark");
    document.dispatchEvent(new CustomEvent("aia-theme-change", { detail: { theme: nextTheme } }));
    return nextTheme;
  }

  function updateThemeSwitches(root, theme) {
    var isDark = theme === "dark";
    root.querySelectorAll(".aia-theme-switch").forEach((themeSwitch) => {
      var icon = themeSwitch.querySelector("i");
      var label = themeSwitch.querySelector(".aia-theme-switch-text");
      themeSwitch.setAttribute("aria-pressed", isDark ? "true" : "false");
      if (icon) {
        icon.classList.toggle("fa-moon", !isDark);
        icon.classList.toggle("fa-sun", isDark);
      }
      if (label) label.textContent = isDark ? "Modo linen" : "Modo oscuro";
    });
  }

  window.AiaDesignSystem = window.AiaDesignSystem || {};
  window.AiaDesignSystem.setTheme = (theme) => {
    var nextTheme = applyTheme(theme);
    try {
      window.localStorage.setItem(storageKey, nextTheme);
    } catch (_error) {
      // Theme still applies for the current page.
    }
    return nextTheme;
  };
  window.AiaDesignSystem.getTheme = () => document.documentElement.getAttribute("data-aia-theme") || "dark";
  window.AiaDesignSystem.toggleTheme = () =>
    window.AiaDesignSystem.setTheme(window.AiaDesignSystem.getTheme() === "dark" ? "linen" : "dark");
  window.AiaDesignSystem.bindThemeSwitches = (root) => {
    root = root || document;
    root.querySelectorAll(".aia-theme-switch").forEach((themeSwitch) => {
      if (themeSwitch.dataset.aiaThemeReady === "true") return;
      themeSwitch.dataset.aiaThemeReady = "true";
      themeSwitch.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        window.AiaDesignSystem.toggleTheme();
      });
    });
    updateThemeSwitches(root, window.AiaDesignSystem.getTheme());
  };
  document.dispatchEvent(new CustomEvent("aia-theme-ready"));

  document.addEventListener("aia-theme-change", (event) => {
    updateThemeSwitches(document, event.detail.theme);
  });

  function boot() {
    window.AiaDesignSystem.bindThemeSwitches(document);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }

  applyTheme(readTheme());
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
