/**
 * public/js/modules/bi_filter_drawer.js
 * ======================================
 * Cajón derecho de filtros del Control Tower BI. Componente propio (no
 * reutiliza public/js/modules/lps_drawer.js): abre/cierra el cajón que
 * contiene el form de filtros (#filters-form), atrapa foco, cierra con
 * Escape/overlay y recalcula el contador "Filtros (N)" del trigger a
 * partir de los campos con valor. No toca applyFilters()/resetFilters()/
 * toggleProjectDropdown() de bi-spa.js ni los ids de los campos.
 */
(function () {
  var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
  var FILTER_FIELD_IDS = ['filter-semana', 'filter-desde', 'filter-hasta', 'filter-sub', 'filter-resp', 'filter-etapa'];

  function els() {
    return {
      trigger: document.querySelector('[data-bi-filter-trigger]'),
      drawer: document.querySelector('[data-bi-filter-drawer]'),
      overlay: document.querySelector('[data-bi-filter-drawer-overlay]'),
      closeBtn: document.querySelector('[data-bi-filter-close]'),
      count: document.getElementById('bi-filter-count'),
      form: document.getElementById('filters-form'),
    };
  }

  function isOpen(drawer) {
    return !!drawer && drawer.classList.contains('is-open');
  }

  function openDrawer() {
    var e = els();
    if (!e.drawer) return;
    e.drawer.hidden = false;
    if (e.overlay) e.overlay.hidden = false;
    // Forzar reflow para que la transición de transform se dispare tras
    // quitar [hidden] (display:none -> flex) en el mismo tick.
    void e.drawer.offsetWidth;
    e.drawer.classList.add('is-open');
    e.drawer.setAttribute('aria-hidden', 'false');
    if (e.overlay) e.overlay.classList.add('is-open');
    if (e.trigger) e.trigger.setAttribute('aria-expanded', 'true');
    if (e.closeBtn) setTimeout(function () { e.closeBtn.focus(); }, 260);
    updateCount();
  }

  function closeDrawer() {
    var e = els();
    if (!e.drawer || !isOpen(e.drawer)) return;
    e.drawer.classList.remove('is-open');
    e.drawer.setAttribute('aria-hidden', 'true');
    if (e.overlay) e.overlay.classList.remove('is-open');
    var onEnd = function (event) {
      if (event && event.target !== e.drawer) return;
      e.drawer.hidden = true;
      if (e.overlay) e.overlay.hidden = true;
      e.drawer.removeEventListener('transitionend', onEnd);
    };
    e.drawer.addEventListener('transitionend', onEnd);
    // Salvaguarda si prefers-reduced-motion recorta la transición a 1ms
    // o si transitionend no dispara (p.ej. display forzado externamente).
    setTimeout(onEnd, 320);
    if (e.trigger) {
      e.trigger.setAttribute('aria-expanded', 'false');
      e.trigger.focus();
    }
  }

  function toggleDrawer() {
    var e = els();
    if (isOpen(e.drawer)) {
      closeDrawer();
    } else {
      openDrawer();
    }
  }

  function updateCount() {
    var e = els();
    if (!e.count || !e.form) return;
    var count = 0;
    var checkedProjects = e.form.querySelectorAll('#project-checkbox-list input[type="checkbox"]:checked');
    if (checkedProjects.length > 0) count += 1;
    FILTER_FIELD_IDS.forEach(function (id) {
      var field = document.getElementById(id);
      if (field && String(field.value || '').trim() !== '') count += 1;
    });
    e.count.textContent = String(count);
  }

  function trapFocus(event) {
    var e = els();
    if (!isOpen(e.drawer)) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeDrawer();
      return;
    }
    if (event.key !== 'Tab') return;
    var items = Array.prototype.slice.call(e.drawer.querySelectorAll(FOCUSABLE_SELECTOR))
      .filter(function (item) { return item.getClientRects().length > 0; });
    if (!items.length) return;
    var first = items[0];
    var last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function bind() {
    var e = els();
    if (!e.drawer || !e.trigger) return;

    e.trigger.addEventListener('click', toggleDrawer);
    if (e.closeBtn) e.closeBtn.addEventListener('click', closeDrawer);
    if (e.overlay) e.overlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', trapFocus);

    if (e.form) {
      e.form.addEventListener('input', updateCount);
      e.form.addEventListener('change', updateCount);
      e.form.addEventListener('click', function (event) {
        if (event.target.closest('button')) {
          // Deja correr el handler inline (applyFilters/resetFilters/
          // toggleProjectDropdown) antes de recalcular.
          setTimeout(updateCount, 0);
        }
      });
    }

    updateCount();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
