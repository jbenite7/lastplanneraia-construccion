(() => {
  function initDialog(root) {
    if (root.dataset.aiaReady === "true") return;
    var open = root.querySelector("[data-aia-dialog-open]");
    var dialog = root.querySelector("[data-aia-dialog]");
    var close = root.querySelector("[data-aia-dialog-close]");
    if (!open || !dialog || !close) return;
    root.dataset.aiaReady = "true";
    var desktop = window.matchMedia("(min-width: 1200px)");
    var updatePresentation = () => {
      dialog.dataset.overlayPresentation = desktop.matches ? "modal" : "drawer";
    };
    var closeDialog = () => {
      dialog.close();
      open.focus();
    };
    open.addEventListener("click", () => {
      dialog.showModal();
    });
    close.addEventListener("click", closeDialog);
    dialog.addEventListener("cancel", (event) => {
      event.preventDefault();
      closeDialog();
    });
    desktop.addEventListener("change", updatePresentation);
    updatePresentation();
  }

  function disclosureParts(root, kind) {
    return {
      trigger: root.querySelector(`[data-aia-${kind}-trigger]`),
      panel: root.querySelector(`[data-aia-${kind}-panel]`),
    };
  }

  function setDisclosure(parts, open, restoreFocus) {
    parts.trigger.setAttribute("aria-expanded", String(open));
    parts.panel.hidden = !open;
    if (!open && restoreFocus) parts.trigger.focus();
  }

  function menuItems(panel) {
    return Array.prototype.slice.call(panel.querySelectorAll('[role="menuitem"]'));
  }

  function initDisclosure(root, kind) {
    if (root.dataset.aiaReady === "true") return;
    var parts = disclosureParts(root, kind);
    if (!parts.trigger || !parts.panel) return;
    root.dataset.aiaReady = "true";
    var isMenu = kind === "menu";
    var isOpen = () => parts.trigger.getAttribute("aria-expanded") === "true";

    // Roving tab order: a role=menu owns keyboard nav, so items leave the tab
    // sequence and are reached with the arrow keys once the menu is open.
    if (isMenu) menuItems(parts.panel).forEach((item) => { item.tabIndex = -1; });

    var focusItem = (index) => {
      var items = menuItems(parts.panel);
      if (!items.length) return;
      items[((index % items.length) + items.length) % items.length].focus();
    };
    var openMenu = (focusIndex) => {
      setDisclosure(parts, true, false);
      if (isMenu && typeof focusIndex === "number") focusItem(focusIndex);
    };
    var closeMenu = (restoreFocus) => setDisclosure(parts, false, restoreFocus);

    parts.trigger.addEventListener("click", () => {
      if (isOpen()) closeMenu(false); else openMenu();
    });

    if (isMenu) {
      parts.trigger.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown") { event.preventDefault(); openMenu(0); }
        else if (event.key === "ArrowUp") { event.preventDefault(); openMenu(-1); }
      });
      parts.panel.addEventListener("keydown", (event) => {
        var items = menuItems(parts.panel);
        var current = items.indexOf(document.activeElement);
        if (event.key === "ArrowDown") { event.preventDefault(); focusItem(current + 1); }
        else if (event.key === "ArrowUp") { event.preventDefault(); focusItem(current - 1); }
        else if (event.key === "Home") { event.preventDefault(); focusItem(0); }
        else if (event.key === "End") { event.preventDefault(); focusItem(items.length - 1); }
        else if (event.key === "Tab") { closeMenu(false); }
      });
    }

    root.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !isOpen()) return;
      event.preventDefault();
      closeMenu(true);
    });

    // Dismiss on click outside the disclosure.
    document.addEventListener("click", (event) => {
      if (!isOpen() || root.contains(event.target)) return;
      closeMenu(false);
    });
  }

  function ensureScrollableRegions(root) {
    var scope = root || document;
    var region = scope.querySelector(".ht_master > .wtHolder");
    if (!region) return;
    var container = region.closest("#hot-container");
    if (container) {
      container.setAttribute("role", "region");
      ["aria-colcount", "aria-rowcount", "aria-multiselectable", "aria-readonly"].forEach((name) => {
        container.removeAttribute(name);
      });
      container.querySelectorAll('[class*="ht_clone_"]').forEach((clone) => {
        clone.setAttribute("aria-hidden", "true");
      });
      container.querySelectorAll(".ht_master *").forEach((element) => {
        element.removeAttribute("role");
        Array.from(element.attributes).forEach((attribute) => {
          if (attribute.name.indexOf("aria-") === 0) {
            element.removeAttribute(attribute.name);
          }
        });
      });
    }
    region.tabIndex = 0;
    region.setAttribute("role", "region");
    region.setAttribute("aria-label", "Tabla desplazable");
  }

  function init(root) {
    (root || document).querySelectorAll('[data-aia-component="dialog"]').forEach(initDialog);
    (root || document).querySelectorAll('[data-aia-component="menu"]').forEach((element) => {
      initDisclosure(element, "menu");
    });
    (root || document).querySelectorAll('[data-aia-component="popover"]').forEach((element) => {
      initDisclosure(element, "popover");
    });
    ensureScrollableRegions(root);
  }

  window.AiaComponents = { init: init, ensureScrollableRegions: ensureScrollableRegions };
  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      () => {
        init(document);
      },
      { once: true },
    );
  } else {
    init(document);
  }
})();
