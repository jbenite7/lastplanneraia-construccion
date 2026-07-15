(function () {
  'use strict';

  function initDialog(root) {
    if (root.dataset.aiaReady === 'true') return;
    var open = root.querySelector('[data-aia-dialog-open]');
    var dialog = root.querySelector('[data-aia-dialog]');
    var close = root.querySelector('[data-aia-dialog-close]');
    if (!open || !dialog || !close) return;
    root.dataset.aiaReady = 'true';
    var desktop = window.matchMedia('(min-width: 1200px)');
    var updatePresentation = function () {
      dialog.dataset.overlayPresentation = desktop.matches ? 'modal' : 'drawer';
    };
    var closeDialog = function () {
      dialog.close();
      open.focus();
    };
    open.addEventListener('click', function () { dialog.showModal(); });
    close.addEventListener('click', closeDialog);
    dialog.addEventListener('cancel', function (event) {
      event.preventDefault();
      closeDialog();
    });
    desktop.addEventListener('change', updatePresentation);
    updatePresentation();
  }

  function disclosureParts(root, kind) {
    return {
      trigger: root.querySelector('[data-aia-' + kind + '-trigger]'),
      panel: root.querySelector('[data-aia-' + kind + '-panel]'),
    };
  }

  function setDisclosure(parts, open, restoreFocus) {
    parts.trigger.setAttribute('aria-expanded', String(open));
    parts.panel.hidden = !open;
    if (!open && restoreFocus) parts.trigger.focus();
  }

  function initDisclosure(root, kind) {
    if (root.dataset.aiaReady === 'true') return;
    var parts = disclosureParts(root, kind);
    if (!parts.trigger || !parts.panel) return;
    root.dataset.aiaReady = 'true';
    parts.trigger.addEventListener('click', function () {
      setDisclosure(parts, parts.trigger.getAttribute('aria-expanded') !== 'true', false);
    });
    root.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      event.preventDefault();
      setDisclosure(parts, false, true);
    });
  }

  function ensureScrollableRegions(root) {
    var scope = root || document;
    var region = scope.querySelector('.ht_master > .wtHolder');
    if (!region) return;
    var container = region.closest('#hot-container');
    if (container) {
      container.setAttribute('role', 'region');
      ['aria-colcount', 'aria-rowcount', 'aria-multiselectable', 'aria-readonly']
        .forEach(function (name) { container.removeAttribute(name); });
      container.querySelectorAll('[class*="ht_clone_"]').forEach(function (clone) {
        clone.setAttribute('aria-hidden', 'true');
      });
      container.querySelectorAll('.ht_master *')
        .forEach(function (element) {
          element.removeAttribute('role');
          Array.from(element.attributes).forEach(function (attribute) {
            if (attribute.name.indexOf('aria-') === 0) {
              element.removeAttribute(attribute.name);
            }
          });
        });
    }
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    region.setAttribute('aria-label', 'Tabla desplazable');
  }

  function init(root) {
    (root || document).querySelectorAll('[data-aia-component="dialog"]').forEach(initDialog);
    (root || document).querySelectorAll('[data-aia-component="menu"]').forEach(function (element) {
      initDisclosure(element, 'menu');
    });
    (root || document).querySelectorAll('[data-aia-component="popover"]').forEach(function (element) {
      initDisclosure(element, 'popover');
    });
    ensureScrollableRegions(root);
  }

  window.AiaComponents = { init: init, ensureScrollableRegions: ensureScrollableRegions };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); }, { once: true });
  } else {
    init(document);
  }
})();
