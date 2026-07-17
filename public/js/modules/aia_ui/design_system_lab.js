(function () {
  'use strict';

  function boot() {
    var root = document.querySelector('.ds-lab');
    if (!root || root.dataset.labReady === 'true') return;
    root.dataset.labReady = 'true';

    var themeButton = root.querySelector('[data-lab-theme]');
    var familyLinks = Array.from(root.querySelectorAll('[data-lab-family-link]'));
    var densityInputs = Array.from(root.querySelectorAll('[data-lab-density]'));
    var shellToggle = root.querySelector('[data-shell-drawer-toggle]');
    var shellPanel = root.querySelector('[data-shell-drawer-panel]');
    var shellViewport = window.matchMedia('(min-width: 1200px)');
    var selectPreview = root.querySelector('[data-select2-preview-toggle]');
    var selectPreviewDropdown = root.querySelector('[data-select2-preview-dropdown]');
    var operationalFixtures = Array.from(root.querySelectorAll('[data-operational-fixture]'));

    function setOperationalState(fixture, state) {
      if (!fixture || !state) return;
      fixture.dataset.contractState = state;
      var activeButton;
      fixture.querySelectorAll('[data-contract-state-action]').forEach(function (button) {
        var isActive = button.dataset.contractStateAction === state;
        button.setAttribute('aria-pressed', String(isActive));
        if (isActive) activeButton = button;
      });
      var output = fixture.querySelector('[data-operational-state-output]');
      if (output) output.textContent = 'Estado actual: ' + (activeButton ? activeButton.textContent.trim() : state);
    }

    operationalFixtures.forEach(function (fixture) {
      fixture.querySelectorAll('[data-contract-state-action]').forEach(function (button) {
        button.addEventListener('click', function () {
          setOperationalState(fixture, button.dataset.contractStateAction);
        });
      });
      fixture.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
          event.preventDefault();
          setOperationalState(fixture, 'success');
        });
      });
    });

    root.querySelectorAll('[data-project-search]').forEach(function (form) {
      form.addEventListener('submit', function () {
        var fixture = form.closest('[data-operational-fixture]');
        var query = form.querySelector('input[type="search"]').value.trim().toLocaleLowerCase('es');
        var visibleCount = 0;
        fixture.querySelectorAll('[data-project-item]').forEach(function (item) {
          var matches = item.dataset.searchValue.includes(query);
          item.hidden = !matches;
          if (matches) visibleCount += 1;
        });
        var empty = fixture.querySelector('[data-project-empty]');
        if (empty) empty.hidden = visibleCount !== 0;
        setOperationalState(fixture, visibleCount === 0 ? 'empty' : 'success');
      });
    });

    root.querySelectorAll('[data-grid-editor]').forEach(function (editor) {
      editor.addEventListener('change', function () {
        setOperationalState(editor.closest('[data-operational-fixture]'), 'editing');
      });
    });

    root.querySelectorAll('[data-fixture-action="paginate-table"]').forEach(function (button) {
      button.addEventListener('click', function () {
        var fixture = button.closest('[data-operational-fixture]');
        fixture.querySelectorAll('.aia-pagination [data-page]').forEach(function (page) {
          if (page.dataset.page === button.dataset.page) page.setAttribute('aria-current', 'page');
          else page.removeAttribute('aria-current');
        });
        setOperationalState(fixture, 'loading');
      });
    });

    function clearTomSelection(fixture) {
      fixture.querySelectorAll('.ts-control .item').forEach(function (item) { item.hidden = true; });
      fixture.querySelectorAll('[role="option"]').forEach(function (option) {
        option.disabled = false;
        option.setAttribute('aria-selected', 'false');
      });
      setOperationalState(fixture, 'empty');
    }

    root.querySelectorAll('[data-fixture-action="clear-tom"]').forEach(function (button) {
      button.addEventListener('click', function () {
        clearTomSelection(button.closest('[data-operational-fixture]'));
      });
    });

    root.addEventListener('click', function (event) {
      var action = event.target.closest('[data-fixture-action]');
      if (!action || !root.contains(action)) return;
      var fixture = action.closest('[data-operational-fixture]');
      var actionName = action.dataset.fixtureAction;

      if (actionName === 'toggle-password') {
        var password = document.getElementById(action.getAttribute('aria-controls'));
        var isVisible = password && password.type === 'text';
        if (password) password.type = isVisible ? 'password' : 'text';
        action.setAttribute('aria-pressed', String(!isVisible));
        action.textContent = isVisible ? 'Mostrar' : 'Ocultar';
        return;
      }

      if (actionName === 'create-week') setOperationalState(fixture, 'creating');
      if (actionName === 'delete-week') setOperationalState(fixture, 'deleting');
      if (actionName === 'save-grid') setOperationalState(fixture, 'saving');
      if (actionName === 'undo-grid') setOperationalState(fixture, 'reverted');

      if (actionName === 'sort-table') {
        var body = fixture.querySelector('[data-sortable-body]');
        if (body) Array.from(body.rows).reverse().forEach(function (row) { body.appendChild(row); });
        var ascending = action.getAttribute('aria-sort') !== 'ascending';
        action.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
        setOperationalState(fixture, 'sorting');
      }

      if (actionName === 'paginate-table') {
        fixture.querySelectorAll('.aia-pagination [data-page]').forEach(function (page) {
          if (page.dataset.page === action.dataset.page) page.setAttribute('aria-current', 'page');
          else page.removeAttribute('aria-current');
        });
        setOperationalState(fixture, 'loading');
      }

      if (actionName === 'toggle-notifications') {
        var panel = document.getElementById(action.getAttribute('aria-controls'));
        var willOpen = panel ? panel.hidden : false;
        if (panel) panel.hidden = !willOpen;
        action.setAttribute('aria-expanded', String(willOpen));
        setOperationalState(fixture, willOpen ? 'unread' : 'default');
      }

      if (actionName === 'mark-read') {
        var notification = action.closest('li');
        if (notification) notification.remove();
        var count = fixture.querySelector('[data-notification-count]');
        if (count) count.textContent = String(fixture.querySelectorAll('[data-fixture-action="mark-read"]').length);
        setOperationalState(fixture, 'success');
      }

      if (actionName === 'open-drawer' || actionName === 'close-drawer') {
        var drawerToggle = fixture.querySelector('[data-fixture-action="open-drawer"]');
        var drawer = document.getElementById(drawerToggle.getAttribute('aria-controls'));
        var open = actionName === 'open-drawer';
        if (drawer) drawer.hidden = !open;
        drawerToggle.setAttribute('aria-expanded', String(open));
        setOperationalState(fixture, open ? 'open' : 'closing');
        if (!open) drawerToggle.focus();
      }

      if (actionName === 'analyze-review') setOperationalState(fixture, 'analyzing');
      if (actionName === 'apply-review') {
        var selected = fixture.querySelectorAll('.ds-proposal-list input:checked').length;
        var undoReview = fixture.querySelector('[data-fixture-action="undo-review"]');
        if (undoReview) undoReview.disabled = selected === 0;
        setOperationalState(fixture, selected > 0 ? 'partial' : 'reviewing');
      }
      if (actionName === 'undo-review') {
        action.disabled = true;
        setOperationalState(fixture, 'undone');
      }
      if (actionName === 'confirm-admin') setOperationalState(fixture, 'confirming');

      if (actionName === 'load-more-bi') {
        var extraRow = fixture.querySelector('[data-bi-extra-row]');
        if (extraRow) extraRow.hidden = false;
        action.textContent = 'No hay más resultados';
        action.disabled = true;
        setOperationalState(fixture, 'drilldown');
      }

      if (actionName === 'add-tom-option') {
        var tomControl = fixture.querySelector('.ts-control');
        var tomSearch = fixture.querySelector('[data-tom-search]');
        var tomValue = action.dataset.tomValue;
        var existingTomItem = tomControl && tomControl.querySelector('.item[data-value="' + tomValue + '"]');
        if (existingTomItem) {
          existingTomItem.hidden = false;
        } else if (tomControl && tomSearch) {
          var tomItem = document.createElement('span');
          var removeTomItem = document.createElement('button');
          tomItem.className = 'item';
          tomItem.dataset.value = tomValue;
          tomItem.append(document.createTextNode(action.textContent.trim() + ' '));
          removeTomItem.type = 'button';
          removeTomItem.dataset.fixtureAction = 'remove-tom-option';
          removeTomItem.dataset.tomValue = tomValue;
          removeTomItem.setAttribute('aria-label', 'Quitar ' + action.textContent.trim());
          removeTomItem.textContent = 'Quitar';
          tomItem.append(removeTomItem);
          tomControl.insertBefore(tomItem, tomSearch);
        }
        action.setAttribute('aria-selected', 'true');
        action.disabled = true;
        setOperationalState(fixture, 'success');
      }
      if (actionName === 'remove-tom-option') {
        var removedValue = action.dataset.tomValue || action.closest('.item').dataset.value;
        var removedItem = action.closest('.item');
        if (removedItem) removedItem.hidden = true;
        var restoredOption = fixture.querySelector('[data-tom-value="' + removedValue + '"]');
        if (restoredOption) {
          restoredOption.disabled = false;
          restoredOption.setAttribute('aria-selected', 'false');
        }
        setOperationalState(fixture, fixture.querySelector('.ts-control .item:not([hidden])') ? 'default' : 'empty');
      }
      if (actionName === 'clear-tom') {
        clearTomSelection(fixture);
      }

      if (actionName === 'toggle-calendar') {
        var calendar = document.getElementById(action.getAttribute('aria-controls'));
        var calendarOpen = calendar ? calendar.hidden : false;
        if (calendar) calendar.hidden = !calendarOpen;
        action.setAttribute('aria-expanded', String(calendarOpen));
        setOperationalState(fixture, calendarOpen ? 'open' : 'default');
      }
      if (actionName === 'clear-date') {
        var dateInput = fixture.querySelector('#fixture-date');
        if (dateInput) dateInput.value = '';
        setOperationalState(fixture, 'empty');
      }
    });

    function setSelectPreview(open) {
      if (!selectPreview || !selectPreviewDropdown) return;
      selectPreview.setAttribute('aria-expanded', String(open));
      selectPreviewDropdown.hidden = !open;
    }

    if (selectPreview && selectPreviewDropdown) {
      selectPreview.addEventListener('click', function () {
        setSelectPreview(selectPreview.getAttribute('aria-expanded') !== 'true');
      });
      selectPreview.addEventListener('keydown', function (event) {
        if (!['Enter', ' ', 'Escape'].includes(event.key)) return;
        event.preventDefault();
        setSelectPreview(event.key === 'Escape' ? false : selectPreview.getAttribute('aria-expanded') !== 'true');
      });
    }

    function setVendorStatus(selector, message) {
      var output = root.querySelector(selector);
      if (output) output.textContent = message;
    }

    var vendorAutosaveTimer;
    function scheduleVendorAutosave(message) {
      var indicator = root.querySelector('[data-handsontable-autosave]');
      if (indicator) {
        indicator.className = 'aia-chip aia-chip--warning';
        indicator.textContent = 'Guardando…';
      }
      setVendorStatus('[data-handsontable-status]', message || 'Guardando cambios automáticamente…');
      window.clearTimeout(vendorAutosaveTimer);
      vendorAutosaveTimer = window.setTimeout(function () {
        if (indicator) {
          indicator.className = 'aia-chip aia-chip--success';
          indicator.textContent = 'Autoguardado activo';
        }
        setVendorStatus('[data-handsontable-status]', 'Cambios guardados automáticamente. La auditoría se actualizó correctamente.');
      }, 300);
    }

    root.querySelectorAll('[data-vendor-grid-editor]').forEach(function (editor) {
      function autosaveVendorGrid() {
        scheduleVendorAutosave();
      }
      editor.addEventListener('input', autosaveVendorGrid);
      editor.addEventListener('change', autosaveVendorGrid);
    });

    root.querySelectorAll('[data-select2-search]').forEach(function (search) {
      search.addEventListener('input', function () {
        var query = search.value.trim().toLocaleLowerCase('es');
        var visible = 0;
        root.querySelectorAll('[data-select2-search-value]').forEach(function (option) {
          var matches = option.dataset.select2SearchValue.includes(query);
          option.hidden = !matches;
          if (matches) visible += 1;
        });
        var count = root.querySelector('[data-select2-result-count]');
        if (count) count.textContent = visible + (visible === 1 ? ' responsable disponible' : ' responsables disponibles');
        var empty = root.querySelector('[data-select2-empty]');
        if (empty) empty.hidden = visible !== 0;
      });
    });

    root.querySelectorAll('[data-select2-search-value]').forEach(function (option) {
      option.addEventListener('click', function () {
        root.querySelectorAll('[data-select2-search-value]').forEach(function (candidate) {
          candidate.setAttribute('aria-selected', String(candidate === option));
        });
        var value = root.querySelector('[data-select2-preview-value]');
        if (value) value.textContent = option.dataset.select2Value;
        setVendorStatus('[data-select2-status]', 'Seleccionado: ' + option.dataset.select2Value + '.');
        setSelectPreview(false);
        selectPreview.focus();
      });
    });

    root.querySelectorAll('[data-select2-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        var value = root.querySelector('[data-select2-preview-value]');
        if (value) value.textContent = 'Selecciona un responsable';
        root.querySelectorAll('[data-select2-search-value]').forEach(function (option) {
          option.setAttribute('aria-selected', 'false');
        });
        setVendorStatus('[data-select2-status]', 'Sin responsable seleccionado.');
        selectPreview.focus();
      });
    });

    function runVendorAction(action) {
      if (action.dataset.vendorAction === 'handsontable-save') {
        scheduleVendorAutosave('Guardando cambios automáticamente…');
      }
      if (action.dataset.vendorAction === 'handsontable-undo') {
        setVendorStatus('[data-handsontable-status]', 'Último cambio revertido. La grilla permanece en modo edición.');
      }
      if (action.dataset.vendorAction === 'handsontable-add') {
        var rows = root.querySelector('[data-handsontable-rows]');
        if (rows && !rows.querySelector('[data-handsontable-new-row]')) {
          var row = document.createElement('tr');
          row.dataset.handsontableNewRow = 'true';
          row.innerHTML = '<th scope="row">Nueva actividad</th><td>Sin asignar</td><td>0 %</td><td><span class="aia-chip aia-chip--warning">Por revisar</span></td><td><button class="aia-btn aia-btn--secondary" type="button">Detalle</button></td>';
          rows.append(row);
        }
        scheduleVendorAutosave('Nueva fila añadida. Autoguardado en curso.');
      }

      if (action.dataset.sweetalertAction === 'cancel') {
        setVendorStatus('[data-sweetalert-status]', 'Aplicación cancelada. No se realizaron cambios.');
      }
      if (action.dataset.sweetalertAction === 'confirm') {
        var popup = root.querySelector('[data-sweetalert-popup]');
        var title = root.querySelector('[data-sweetalert-title]');
        var description = root.querySelector('[data-sweetalert-description]');
        var chip = root.querySelector('[data-sweetalert-chip]');
        if (popup) popup.dataset.confirmed = 'true';
        if (title) title.textContent = 'Cambios aplicados';
        if (description) description.textContent = 'Los compromisos se actualizaron y el historial conserva una opción de deshacer.';
        if (chip) {
          chip.className = 'aia-chip aia-chip--success';
          chip.textContent = 'Aplicado';
        }
        action.disabled = true;
        setVendorStatus('[data-sweetalert-status]', 'Cambios aplicados correctamente. Puedes revisarlos en la auditoría.');
      }
    }

    root.querySelectorAll('[data-vendor-action], [data-sweetalert-action]').forEach(function (action) {
      action.addEventListener('click', function () {
        runVendorAction(action);
      });
    });

    function updateThemeLabel() {
      var theme = window.AiaDesignSystem.getTheme();
      themeButton.textContent = theme === 'dark' ? 'Usar tema linen' : 'Usar tema dark';
    }

    function showFamily(familyId, historyMode, moveFocus) {
      var familyLink = familyLinks.find(function (link) {
        return link.dataset.familyTarget === familyId;
      });
      if (!familyLink) return;
      root.querySelectorAll('[data-family]').forEach(function (section) {
        section.hidden = section.dataset.family !== familyId;
      });
      familyLinks.forEach(function (link) {
        if (link === familyLink) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
      });
      if (historyMode) {
        var url = new URL(window.location.href);
        url.searchParams.set('family', familyId);
        window.history[historyMode + 'State']({}, '', url);
      }
      if (moveFocus) {
        var heading = root.querySelector('[data-family="' + familyId + '"] h2');
        if (heading) heading.focus({ preventScroll: true });
      }
    }

    function normalizedFamilyId(familyId) {
      var familyExists = familyLinks.some(function (link) {
        return link.dataset.familyTarget === familyId;
      });
      return familyExists ? familyId : familyLinks[0].dataset.familyTarget;
    }

    var requestedFamily = new URL(window.location.href).searchParams.get('family');
    var initialFamilyId = normalizedFamilyId(requestedFamily);
    showFamily(initialFamilyId);
    if (requestedFamily !== initialFamilyId) {
      var initialUrl = new URL(window.location.href);
      initialUrl.searchParams.set('family', initialFamilyId);
      window.history.replaceState({}, '', initialUrl);
    }
    familyLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        showFamily(link.dataset.familyTarget, 'push', true);
      });
    });
    window.addEventListener('popstate', function () {
      var familyId = new URL(window.location.href).searchParams.get('family');
      showFamily(normalizedFamilyId(familyId), null, false);
    });

    themeButton.addEventListener('click', function () {
      window.AiaDesignSystem.toggleTheme();
    });
    document.addEventListener('aia-theme-change', updateThemeLabel);
    function updateDensity() {
      var density = densityInputs.find(function (input) { return input.checked; });
      root.dataset.density = density && density.value === 'touch' ? 'touch' : 'compact';
      root.querySelectorAll('[data-density-sample]').forEach(function (sample) {
        sample.dataset.selected = String(sample.dataset.densitySample === root.dataset.density);
      });
    }
    densityInputs.forEach(function (input) {
      input.checked = input.value === (window.matchMedia('(min-width: 73.75rem)').matches ? 'compact' : 'touch');
      input.addEventListener('change', updateDensity);
    });
    function setShellDrawer(open, restoreFocus) {
      var contextual = shellViewport.matches;
      shellPanel.dataset.shellPresentation = contextual ? 'contextual' : 'drawer';
      shellToggle.setAttribute('aria-expanded', String(contextual ? false : open));
      shellPanel.hidden = contextual ? false : !open;
      if (!open && restoreFocus) shellToggle.focus();
    }
    if (shellToggle && shellPanel) {
      shellToggle.addEventListener('click', function () {
        setShellDrawer(shellToggle.getAttribute('aria-expanded') !== 'true', false);
      });
      shellToggle.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || shellToggle.getAttribute('aria-expanded') !== 'true') return;
        event.preventDefault();
        setShellDrawer(false, true);
      });
      shellViewport.addEventListener('change', function () {
        setShellDrawer(false, false);
      });
      setShellDrawer(false, false);
    }
    updateThemeLabel();
    updateDensity();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
