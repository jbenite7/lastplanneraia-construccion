((global) => {
  const shellSelector = '[data-shell-pattern="sidebar"]';

  function setPreviewState(candidate, state) {
    if (!candidate || !state) return;
    if (state === "default") candidate.removeAttribute("data-sidebar-preview-state");
    else candidate.setAttribute("data-sidebar-preview-state", state);
    candidate.querySelectorAll("[data-sidebar-group]").forEach((group) => {
      const list = group.querySelector("ul");
      const empty = group.querySelector("[data-sidebar-empty]");
      if (list) list.hidden = state === "empty";
      if (empty && list) empty.hidden = state !== "empty";
    });
    const notificationMessage = candidate.querySelector("[data-sidebar-notification-message]");
    const notificationButton = candidate.querySelector("[data-sidebar-notifications]");
    const retryButton = candidate.querySelector("[data-sidebar-notification-retry]");
    const notificationCopy =
      {
        default: "Avisos",
        loading: "Cargando avisos…",
        empty: "No hay avisos nuevos.",
        error: "No pudimos cargar los avisos. Intenta de nuevo.",
      }[state] || "Avisos";
    if (notificationMessage) notificationMessage.textContent = notificationCopy;
    if (notificationButton) notificationButton.setAttribute("aria-label", notificationCopy);
    if (retryButton) retryButton.hidden = state !== "error";
    const sidebarNav = candidate.querySelector(".aia-sidebar__nav");
    if (sidebarNav) sidebarNav.setAttribute("aria-busy", String(state === "loading"));
    candidate.querySelectorAll("[data-sidebar-state-action]").forEach((button) => {
      button.setAttribute("aria-pressed", String(button.dataset.sidebarStateAction === state));
    });

    if (retryButton && !retryButton.dataset.sidebarRetryReady) {
      retryButton.dataset.sidebarRetryReady = "true";
      retryButton.addEventListener("click", () => setPreviewState(candidate, "default"));
    }
  }

  // Persistencia opt-in: el consumidor envuelve el shell en [data-sidebar-persist]
  // (el project-selector no lo hace y conserva su estado por servidor).
  const storageKey = "aia-sidebar-state";

  function shouldPersist(shell) {
    return shell.closest("[data-sidebar-persist]") !== null;
  }

  function readPersistedState(shell) {
    if (!shouldPersist(shell)) return null;
    try {
      const stored = global.localStorage.getItem(storageKey);
      return stored === "collapsed" || stored === "expanded" ? stored : null;
    } catch (_error) {
      return null;
    }
  }

  // En colapsado los flyouts CSS muestran el label al instante; el title nativo
  // duplicaría el tooltip, así que se aparca en data-sidebar-title.
  function syncNativeTitles(shell, collapsed) {
    shell.querySelectorAll(".aia-sidebar__link").forEach((item) => {
      if (collapsed) {
        const title = item.getAttribute("title");
        if (title) {
          item.setAttribute("data-sidebar-title", title);
          item.removeAttribute("title");
        }
      } else {
        const stored = item.getAttribute("data-sidebar-title");
        if (stored) {
          item.setAttribute("title", stored);
          item.removeAttribute("data-sidebar-title");
        }
      }
    });
  }

  function setCollapsed(shell, collapsed, restoreFocus) {
    const toggle = shell.querySelector("[data-sidebar-toggle]");
    const label = shell.querySelector(".aia-sidebar__toggle-label");
    if (!toggle) return;
    shell.dataset.sidebarState = collapsed ? "collapsed" : "expanded";
    toggle.setAttribute("aria-expanded", String(!collapsed));
    toggle.setAttribute("aria-label", collapsed ? "Expandir menú" : "Colapsar menú");
    if (label) label.textContent = collapsed ? "Expandir menú" : "Colapsar menú";
    syncNativeTitles(shell, collapsed);
    if (shouldPersist(shell)) {
      try {
        global.localStorage.setItem(storageKey, collapsed ? "collapsed" : "expanded");
      } catch (_error) {
        // El estado sigue aplicando en la página actual.
      }
    }
    if (restoreFocus) toggle.focus();
  }

  function init(shell) {
    if (shell.dataset.sidebarReady === "true") return;
    const toggle = shell.querySelector("[data-sidebar-toggle]");
    if (!toggle) return;
    shell.dataset.sidebarReady = "true";
    const candidate = shell.closest(".ds-shell-candidate");

    const persisted = readPersistedState(shell);
    if (persisted !== null && persisted !== shell.dataset.sidebarState) {
      setCollapsed(shell, persisted === "collapsed", false);
    } else {
      syncNativeTitles(shell, shell.dataset.sidebarState === "collapsed");
    }

    toggle.addEventListener("click", () => {
      setCollapsed(shell, shell.dataset.sidebarState !== "collapsed", true);
    });

    shell.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;
      if (shell.dataset.sidebarState !== "collapsed") return;
      event.preventDefault();
      setCollapsed(shell, false, true);
    });

    if (candidate) {
      candidate.querySelectorAll("[data-sidebar-state-action]").forEach((button) => {
        button.setAttribute("aria-pressed", String(button.dataset.sidebarStateAction === "default"));
        button.addEventListener("click", () => {
          setPreviewState(candidate, button.dataset.sidebarStateAction);
        });
      });
    }
  }

  function boot(root) {
    (root || document).querySelectorAll(shellSelector).forEach(init);
  }

  global.AiaSidebarNavigation = { init: boot };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => boot(document), { once: true });
  } else {
    boot(document);
  }
})(window);
