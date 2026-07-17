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
