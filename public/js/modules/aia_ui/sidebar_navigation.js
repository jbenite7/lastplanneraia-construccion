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
    const notificationCopy =
      {
        default: "Avisos",
        loading: "Cargando avisos…",
        empty: "No hay avisos nuevos.",
        error: "No se pudieron cargar los avisos.",
      }[state] || "Avisos";
    if (notificationMessage) notificationMessage.textContent = notificationCopy;
    if (notificationButton) notificationButton.setAttribute("aria-label", notificationCopy);
    candidate.querySelectorAll("[data-sidebar-state-action]").forEach((button) => {
      button.setAttribute("aria-pressed", String(button.dataset.sidebarStateAction === state));
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
    if (restoreFocus) toggle.focus();
  }

  function init(shell) {
    if (shell.dataset.sidebarReady === "true") return;
    const toggle = shell.querySelector("[data-sidebar-toggle]");
    if (!toggle) return;
    shell.dataset.sidebarReady = "true";
    const candidate = shell.closest(".ds-shell-candidate");

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
