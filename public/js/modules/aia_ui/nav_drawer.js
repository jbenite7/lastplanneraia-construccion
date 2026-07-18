((global) => {
  var focusableSelector = [
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled]):not([tabindex="-1"])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(",");

  function elements(root) {
    return {
      toggle: root.getElementById("drawerToggle"),
      drawer: root.querySelector(".navbar-collapse-drawer"),
      close: root.getElementById("drawerClose"),
      overlay: root.getElementById("drawerOverlay"),
    };
  }

  function isVisibleFocusable(element) {
    if (element.closest('[hidden], [aria-hidden="true"]')) return false;
    return element.getClientRects().length > 0;
  }

  function init(root) {
    root = root || document;
    var ui = elements(root);
    if (!ui.toggle || !ui.drawer || !ui.close || !ui.overlay) return false;
    if (ui.drawer.dataset.aiaDrawerReady === "true") return true;
    ui.drawer.dataset.aiaDrawerReady = "true";
    var desktopQuery = global.matchMedia("(min-width: 1200px)");

    function syncResponsiveSemantics(isDesktop) {
      if (isDesktop) {
        ui.drawer.removeAttribute("role");
        ui.drawer.removeAttribute("aria-modal");
        ui.drawer.removeAttribute("aria-hidden");
        return;
      }
      ui.drawer.setAttribute("role", "dialog");
      ui.drawer.setAttribute("aria-modal", "true");
      ui.drawer.setAttribute("aria-hidden", ui.drawer.classList.contains("show") ? "false" : "true");
    }

    function openDrawer() {
      ui.drawer.classList.add("show");
      ui.overlay.classList.add("show");
      root.body.classList.add("aia-nav-open");
      ui.toggle.setAttribute("aria-expanded", "true");
      ui.drawer.setAttribute("aria-hidden", "false");
      global.setTimeout(() => {
        ui.close.focus();
      }, 450);
    }

    function closeDrawer(restoreFocus) {
      ui.drawer.classList.remove("show");
      ui.overlay.classList.remove("show");
      root.body.classList.remove("aia-nav-open");
      ui.toggle.setAttribute("aria-expanded", "false");
      if (!desktopQuery.matches) ui.drawer.setAttribute("aria-hidden", "true");
      if (restoreFocus !== false) ui.toggle.focus();
    }

    function onKeydown(event) {
      if (!ui.drawer.classList.contains("show")) return;
      if (event.key === "Escape") {
        event.preventDefault();
        closeDrawer(true);
        return;
      }
      if (event.key === "Tab") {
        const items = Array.from(ui.drawer.querySelectorAll(focusableSelector)).filter(isVisibleFocusable);
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && root.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && root.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    }

    ui.toggle.addEventListener("click", openDrawer);
    ui.close.addEventListener("click", () => {
      closeDrawer(true);
    });
    ui.overlay.addEventListener("click", () => {
      closeDrawer(true);
    });
    root.addEventListener("keydown", onKeydown);
    desktopQuery.addEventListener("change", (event) => {
      if (event.matches) closeDrawer(false);
      syncResponsiveSemantics(event.matches);
    });
    syncResponsiveSemantics(desktopQuery.matches);
    return true;
  }

  global.AiaNavDrawer = { init: init };

  function boot() {
    init(document);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }
})(window);
