(() => {
  function boot() {
    var root = document.querySelector(".ds-lab");
    if (!root || root.dataset.labReady === "true") return;
    root.dataset.labReady = "true";

    var themeButton = root.querySelector("[data-lab-theme]");
    var familyLinks = Array.from(root.querySelectorAll("[data-lab-family-link]"));
    var densityInputs = Array.from(root.querySelectorAll("[data-lab-density]"));
    var shellToggle = root.querySelector("[data-shell-drawer-toggle]");
    var shellPanel = root.querySelector("[data-shell-drawer-panel]");
    var shellViewport = window.matchMedia("(min-width: 1200px)");
    var selectPreview = root.querySelector("[data-select2-preview-toggle]");
    var selectPreviewDropdown = root.querySelector("[data-select2-preview-dropdown]");
    var operationalFixtures = Array.from(root.querySelectorAll("[data-operational-fixture]"));

    function setOperationalState(fixture, state) {
      if (!fixture || !state) return;
      fixture.dataset.contractState = state;
      var activeButton;
      fixture.querySelectorAll("[data-contract-state-action]").forEach((button) => {
        var isActive = button.dataset.contractStateAction === state;
        button.setAttribute("aria-pressed", String(isActive));
        if (isActive) activeButton = button;
      });
      var output = fixture.querySelector("[data-operational-state-output]");
      if (output) output.textContent = `Estado actual: ${activeButton ? activeButton.textContent.trim() : state}`;
    }

    operationalFixtures.forEach((fixture) => {
      fixture.querySelectorAll("[data-contract-state-action]").forEach((button) => {
        button.addEventListener("click", () => {
          setOperationalState(fixture, button.dataset.contractStateAction);
        });
      });
      fixture.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (event) => {
          event.preventDefault();
          setOperationalState(fixture, "success");
        });
      });
    });

    root.querySelectorAll("[data-project-search]").forEach((form) => {
      form.addEventListener("submit", () => {
        var fixture = form.closest("[data-operational-fixture]");
        var query = form.querySelector('input[type="search"]').value.trim().toLocaleLowerCase("es");
        var visibleCount = 0;
        fixture.querySelectorAll("[data-project-item]").forEach((item) => {
          var matches = item.dataset.searchValue.includes(query);
          item.hidden = !matches;
          if (matches) visibleCount += 1;
        });
        var empty = fixture.querySelector("[data-project-empty]");
        if (empty) empty.hidden = visibleCount !== 0;
        setOperationalState(fixture, visibleCount === 0 ? "empty" : "success");
      });
    });

    root.querySelectorAll("[data-grid-editor]").forEach((editor) => {
      editor.addEventListener("change", () => {
        setOperationalState(editor.closest("[data-operational-fixture]"), "editing");
      });
    });

    root.querySelectorAll('[data-fixture-action="paginate-table"]').forEach((button) => {
      button.addEventListener("click", () => {
        var fixture = button.closest("[data-operational-fixture]");
        fixture.querySelectorAll(".aia-pagination [data-page]").forEach((page) => {
          if (page.dataset.page === button.dataset.page) page.setAttribute("aria-current", "page");
          else page.removeAttribute("aria-current");
        });
        setOperationalState(fixture, "loading");
      });
    });

    function clearTomSelection(fixture) {
      fixture.querySelectorAll(".ts-control .item").forEach((item) => {
        item.hidden = true;
      });
      fixture.querySelectorAll('[role="option"]').forEach((option) => {
        option.disabled = false;
        option.setAttribute("aria-selected", "false");
      });
      setOperationalState(fixture, "empty");
    }

    root.querySelectorAll('[data-fixture-action="clear-tom"]').forEach((button) => {
      button.addEventListener("click", () => {
        clearTomSelection(button.closest("[data-operational-fixture]"));
      });
    });

    root.addEventListener("click", (event) => {
      var action = event.target.closest("[data-fixture-action]");
      if (!action || !root.contains(action)) return;
      var fixture = action.closest("[data-operational-fixture]");
      var actionName = action.dataset.fixtureAction;

      if (actionName === "toggle-password") {
        const password = document.getElementById(action.getAttribute("aria-controls"));
        const isVisible = password && password.type === "text";
        if (password) password.type = isVisible ? "password" : "text";
        action.setAttribute("aria-pressed", String(!isVisible));
        action.textContent = isVisible ? "Mostrar" : "Ocultar";
        return;
      }

      if (actionName === "create-week") setOperationalState(fixture, "creating");
      if (actionName === "delete-week") setOperationalState(fixture, "deleting");
      if (actionName === "save-grid") setOperationalState(fixture, "saving");
      if (actionName === "undo-grid") setOperationalState(fixture, "reverted");

      if (actionName === "sort-table") {
        const body = fixture.querySelector("[data-sortable-body]");
        if (body)
          Array.from(body.rows)
            .reverse()
            .forEach((row) => {
              body.appendChild(row);
            });
        const ascending = action.getAttribute("aria-sort") !== "ascending";
        action.setAttribute("aria-sort", ascending ? "ascending" : "descending");
        setOperationalState(fixture, "sorting");
      }

      if (actionName === "paginate-table") {
        fixture.querySelectorAll(".aia-pagination [data-page]").forEach((page) => {
          if (page.dataset.page === action.dataset.page) page.setAttribute("aria-current", "page");
          else page.removeAttribute("aria-current");
        });
        setOperationalState(fixture, "loading");
      }

      if (actionName === "toggle-notifications") {
        const panel = document.getElementById(action.getAttribute("aria-controls"));
        const willOpen = panel ? panel.hidden : false;
        if (panel) panel.hidden = !willOpen;
        action.setAttribute("aria-expanded", String(willOpen));
        setOperationalState(fixture, willOpen ? "unread" : "default");
      }

      if (actionName === "mark-read") {
        const notification = action.closest("li");
        if (notification) notification.remove();
        const count = fixture.querySelector("[data-notification-count]");
        if (count) count.textContent = String(fixture.querySelectorAll('[data-fixture-action="mark-read"]').length);
        setOperationalState(fixture, "success");
      }

      if (actionName === "open-drawer" || actionName === "close-drawer") {
        const drawerToggle = fixture.querySelector('[data-fixture-action="open-drawer"]');
        const drawer = document.getElementById(drawerToggle.getAttribute("aria-controls"));
        const open = actionName === "open-drawer";
        if (drawer) drawer.hidden = !open;
        drawerToggle.setAttribute("aria-expanded", String(open));
        setOperationalState(fixture, open ? "open" : "closing");
        if (!open) drawerToggle.focus();
      }

      if (actionName === "analyze-review") setOperationalState(fixture, "analyzing");
      if (actionName === "apply-review") {
        const selected = fixture.querySelectorAll(".ds-proposal-list input:checked").length;
        const undoReview = fixture.querySelector('[data-fixture-action="undo-review"]');
        if (undoReview) undoReview.disabled = selected === 0;
        setOperationalState(fixture, selected > 0 ? "partial" : "reviewing");
      }
      if (actionName === "undo-review") {
        action.disabled = true;
        setOperationalState(fixture, "undone");
      }
      if (actionName === "confirm-admin") setOperationalState(fixture, "confirming");

      if (actionName === "load-more-bi") {
        const extraRow = fixture.querySelector("[data-bi-extra-row]");
        if (extraRow) extraRow.hidden = false;
        action.textContent = "No hay más resultados";
        action.disabled = true;
        setOperationalState(fixture, "drilldown");
      }

      if (actionName === "add-tom-option") {
        const tomControl = fixture.querySelector(".ts-control");
        const tomSearch = fixture.querySelector("[data-tom-search]");
        const tomValue = action.dataset.tomValue;
        const existingTomItem = tomControl?.querySelector(`.item[data-value="${tomValue}"]`);
        if (existingTomItem) {
          existingTomItem.hidden = false;
        } else if (tomControl && tomSearch) {
          const tomItem = document.createElement("span");
          const removeTomItem = document.createElement("button");
          tomItem.className = "item";
          tomItem.dataset.value = tomValue;
          tomItem.append(document.createTextNode(`${action.textContent.trim()} `));
          removeTomItem.type = "button";
          removeTomItem.dataset.fixtureAction = "remove-tom-option";
          removeTomItem.dataset.tomValue = tomValue;
          removeTomItem.setAttribute("aria-label", `Quitar ${action.textContent.trim()}`);
          removeTomItem.textContent = "Quitar";
          tomItem.append(removeTomItem);
          tomControl.insertBefore(tomItem, tomSearch);
        }
        action.setAttribute("aria-selected", "true");
        action.disabled = true;
        setOperationalState(fixture, "success");
      }
      if (actionName === "remove-tom-option") {
        const removedValue = action.dataset.tomValue || action.closest(".item").dataset.value;
        const removedItem = action.closest(".item");
        if (removedItem) removedItem.hidden = true;
        const restoredOption = fixture.querySelector(`[data-tom-value="${removedValue}"]`);
        if (restoredOption) {
          restoredOption.disabled = false;
          restoredOption.setAttribute("aria-selected", "false");
        }
        setOperationalState(fixture, fixture.querySelector(".ts-control .item:not([hidden])") ? "default" : "empty");
      }
      if (actionName === "clear-tom") {
        clearTomSelection(fixture);
      }

      if (actionName === "toggle-calendar") {
        const calendar = document.getElementById(action.getAttribute("aria-controls"));
        const calendarOpen = calendar ? calendar.hidden : false;
        if (calendar) calendar.hidden = !calendarOpen;
        action.setAttribute("aria-expanded", String(calendarOpen));
        setOperationalState(fixture, calendarOpen ? "open" : "default");
      }
      if (actionName === "clear-date") {
        const dateInput = fixture.querySelector("#fixture-date");
        if (dateInput) dateInput.value = "";
        setOperationalState(fixture, "empty");
      }
    });

    function setSelectPreview(open) {
      if (!selectPreview || !selectPreviewDropdown) return;
      selectPreview.setAttribute("aria-expanded", String(open));
      selectPreviewDropdown.hidden = !open;
    }

    if (selectPreview && selectPreviewDropdown) {
      selectPreview.addEventListener("click", () => {
        setSelectPreview(selectPreview.getAttribute("aria-expanded") !== "true");
      });
      selectPreview.addEventListener("keydown", (event) => {
        if (!["Enter", " ", "Escape"].includes(event.key)) return;
        event.preventDefault();
        setSelectPreview(event.key === "Escape" ? false : selectPreview.getAttribute("aria-expanded") !== "true");
      });
    }

    function setVendorStatus(selector, message) {
      var output = root.querySelector(selector);
      if (output) output.textContent = message;
    }

    var vendorAutosaveTimer;
    function scheduleVendorAutosave(message) {
      var indicator = root.querySelector("[data-handsontable-autosave]");
      if (indicator) {
        indicator.className = "aia-chip aia-chip--warning";
        indicator.textContent = "Guardando…";
      }
      setVendorStatus("[data-handsontable-status]", message || "Guardando cambios automáticamente…");
      window.clearTimeout(vendorAutosaveTimer);
      vendorAutosaveTimer = window.setTimeout(() => {
        if (indicator) {
          indicator.className = "aia-chip aia-chip--success";
          indicator.textContent = "Autoguardado activo";
        }
        setVendorStatus(
          "[data-handsontable-status]",
          "Cambios guardados automáticamente. La auditoría se actualizó correctamente.",
        );
      }, 300);
    }

    root.querySelectorAll("[data-vendor-grid-editor]").forEach((editor) => {
      function autosaveVendorGrid() {
        scheduleVendorAutosave();
      }
      editor.addEventListener("input", autosaveVendorGrid);
      editor.addEventListener("change", autosaveVendorGrid);
    });

    root.querySelectorAll("[data-select2-search]").forEach((search) => {
      search.addEventListener("input", () => {
        var query = search.value.trim().toLocaleLowerCase("es");
        var visible = 0;
        root.querySelectorAll("[data-select2-search-value]").forEach((option) => {
          var matches = option.dataset.select2SearchValue.includes(query);
          option.hidden = !matches;
          if (matches) visible += 1;
        });
        var count = root.querySelector("[data-select2-result-count]");
        if (count)
          count.textContent = visible + (visible === 1 ? " responsable disponible" : " responsables disponibles");
        var empty = root.querySelector("[data-select2-empty]");
        if (empty) empty.hidden = visible !== 0;
      });
    });

    root.querySelectorAll("[data-select2-search-value]").forEach((option) => {
      option.addEventListener("click", () => {
        root.querySelectorAll("[data-select2-search-value]").forEach((candidate) => {
          candidate.setAttribute("aria-selected", String(candidate === option));
        });
        var value = root.querySelector("[data-select2-preview-value]");
        if (value) value.textContent = option.dataset.select2Value;
        setVendorStatus("[data-select2-status]", `Seleccionado: ${option.dataset.select2Value}.`);
        setSelectPreview(false);
        selectPreview.focus();
      });
    });

    root.querySelectorAll("[data-select2-clear]").forEach((button) => {
      button.addEventListener("click", () => {
        var value = root.querySelector("[data-select2-preview-value]");
        if (value) value.textContent = "Selecciona un responsable";
        root.querySelectorAll("[data-select2-search-value]").forEach((option) => {
          option.setAttribute("aria-selected", "false");
        });
        setVendorStatus("[data-select2-status]", "Sin responsable seleccionado.");
        selectPreview.focus();
      });
    });

    function runVendorAction(action) {
      if (action.dataset.vendorAction === "handsontable-save") {
        scheduleVendorAutosave("Guardando cambios automáticamente…");
      }
      if (action.dataset.vendorAction === "handsontable-undo") {
        setVendorStatus("[data-handsontable-status]", "Último cambio revertido. La grilla permanece en modo edición.");
      }
      if (action.dataset.vendorAction === "handsontable-add") {
        const rows = root.querySelector("[data-handsontable-rows]");
        if (rows && !rows.querySelector("[data-handsontable-new-row]")) {
          const row = document.createElement("tr");
          row.dataset.handsontableNewRow = "true";
          row.innerHTML =
            '<th scope="row">Nueva actividad</th><td>Sin asignar</td><td>0 %</td><td><span class="aia-chip aia-chip--warning">Por revisar</span></td><td><button class="aia-btn aia-btn--secondary" type="button">Detalle</button></td>';
          rows.append(row);
        }
        scheduleVendorAutosave("Nueva fila añadida. Autoguardado en curso.");
      }

      if (action.dataset.sweetalertAction === "cancel") {
        setVendorStatus("[data-sweetalert-status]", "Aplicación cancelada. No se realizaron cambios.");
      }
      if (action.dataset.sweetalertAction === "confirm") {
        const popup = root.querySelector("[data-sweetalert-popup]");
        const title = root.querySelector("[data-sweetalert-title]");
        const description = root.querySelector("[data-sweetalert-description]");
        const chip = root.querySelector("[data-sweetalert-chip]");
        if (popup) popup.dataset.confirmed = "true";
        if (title) title.textContent = "Cambios aplicados";
        if (description)
          description.textContent = "Los compromisos se actualizaron y el historial conserva una opción de deshacer.";
        if (chip) {
          chip.className = "aia-chip aia-chip--success";
          chip.textContent = "Aplicado";
        }
        action.disabled = true;
        setVendorStatus(
          "[data-sweetalert-status]",
          "Cambios aplicados correctamente. Puedes revisarlos en la auditoría.",
        );
      }
    }

    root.querySelectorAll("[data-vendor-action], [data-sweetalert-action]").forEach((action) => {
      action.addEventListener("click", () => {
        runVendorAction(action);
      });
    });

    function updateThemeLabel() {
      var theme = window.AiaDesignSystem.getTheme();
      themeButton.textContent = theme === "dark" ? "Usar tema linen" : "Usar tema dark";
    }

    function showFamily(familyId, historyMode, moveFocus) {
      var familyLink = familyLinks.find((link) => link.dataset.familyTarget === familyId);
      if (!familyLink) return;
      root.querySelectorAll("[data-family]").forEach((section) => {
        section.hidden = section.dataset.family !== familyId;
      });
      familyLinks.forEach((link) => {
        if (link === familyLink) link.setAttribute("aria-current", "page");
        else link.removeAttribute("aria-current");
      });
      if (historyMode) {
        const url = new URL(window.location.href);
        url.searchParams.set("family", familyId);
        window.history[`${historyMode}State`]({}, "", url);
      }
      if (moveFocus) {
        const heading = root.querySelector(`[data-family="${familyId}"] h2`);
        if (heading) heading.focus({ preventScroll: true });
      }
    }

    function normalizedFamilyId(familyId) {
      var familyExists = familyLinks.some((link) => link.dataset.familyTarget === familyId);
      return familyExists ? familyId : familyLinks[0].dataset.familyTarget;
    }

    var requestedFamily = new URL(window.location.href).searchParams.get("family");
    var initialFamilyId = normalizedFamilyId(requestedFamily);
    showFamily(initialFamilyId);
    if (requestedFamily !== initialFamilyId) {
      const initialUrl = new URL(window.location.href);
      initialUrl.searchParams.set("family", initialFamilyId);
      window.history.replaceState({}, "", initialUrl);
    }
    familyLinks.forEach((link) => {
      link.addEventListener("click", (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        showFamily(link.dataset.familyTarget, "push", true);
      });
    });
    window.addEventListener("popstate", () => {
      var familyId = new URL(window.location.href).searchParams.get("family");
      showFamily(normalizedFamilyId(familyId), null, false);
    });

    themeButton.addEventListener("click", () => {
      window.AiaDesignSystem.toggleTheme();
    });
    document.addEventListener("aia-theme-change", updateThemeLabel);
    function updateDensity() {
      var density = densityInputs.find((input) => input.checked);
      root.dataset.density = density && density.value === "touch" ? "touch" : "compact";
      root.querySelectorAll("[data-density-sample]").forEach((sample) => {
        sample.dataset.selected = String(sample.dataset.densitySample === root.dataset.density);
      });
    }
    densityInputs.forEach((input) => {
      input.checked = input.value === (window.matchMedia("(min-width: 73.75rem)").matches ? "compact" : "touch");
      input.addEventListener("change", updateDensity);
    });
    function setShellDrawer(open, restoreFocus) {
      var contextual = shellViewport.matches;
      shellPanel.dataset.shellPresentation = contextual ? "contextual" : "drawer";
      shellToggle.setAttribute("aria-expanded", String(contextual ? false : open));
      shellPanel.hidden = contextual ? false : !open;
      if (!open && restoreFocus) shellToggle.focus();
    }
    if (shellToggle && shellPanel) {
      shellToggle.addEventListener("click", () => {
        setShellDrawer(shellToggle.getAttribute("aria-expanded") !== "true", false);
      });
      shellToggle.addEventListener("keydown", (event) => {
        if (event.key !== "Escape" || shellToggle.getAttribute("aria-expanded") !== "true") return;
        event.preventDefault();
        setShellDrawer(false, true);
      });
      shellViewport.addEventListener("change", () => {
        setShellDrawer(false, false);
      });
      setShellDrawer(false, false);
    }
    updateThemeLabel();
    updateDensity();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }
})();
