(function () {
  'use strict';

  function boot() {
    var root = document.querySelector('.ds-lab');
    if (!root || root.dataset.labReady === 'true') return;
    root.dataset.labReady = 'true';

    var themeButton = root.querySelector('[data-lab-theme]');
    var familySelect = root.querySelector('[data-lab-family]');
    var density = root.querySelector('[data-lab-density]');
    var shellToggle = root.querySelector('[data-shell-drawer-toggle]');
    var shellPanel = root.querySelector('[data-shell-drawer-panel]');
    var shellViewport = window.matchMedia('(min-width: 1200px)');
    var selectPreview = root.querySelector('[data-select2-preview-toggle]');
    var selectPreviewDropdown = root.querySelector('[data-select2-preview-dropdown]');

    function setSelectPreview(open) {
      selectPreview.setAttribute('aria-expanded', String(open));
      selectPreviewDropdown.hidden = !open;
    }

    selectPreview.addEventListener('click', function () {
      setSelectPreview(selectPreview.getAttribute('aria-expanded') !== 'true');
    });
    selectPreview.addEventListener('keydown', function (event) {
      if (!['Enter', ' ', 'Escape'].includes(event.key)) return;
      event.preventDefault();
      setSelectPreview(event.key === 'Escape' ? false : selectPreview.getAttribute('aria-expanded') !== 'true');
    });

    function updateThemeLabel() {
      var theme = window.AiaDesignSystem.getTheme();
      themeButton.textContent = theme === 'dark' ? 'Usar tema linen' : 'Usar tema dark';
    }

    function showFamily(familyId) {
      root.querySelectorAll('[data-family]').forEach(function (section) {
        section.hidden = section.dataset.family !== familyId;
      });
      familySelect.value = familyId;
      var url = new URL(window.location.href);
      url.searchParams.set('family', familyId);
      window.history.replaceState({}, '', url);
    }

    var requestedFamily = new URL(window.location.href).searchParams.get('family');
    var familyExists = Array.from(familySelect.options).some(function (option) {
      return option.value === requestedFamily;
    });
    showFamily(familyExists ? requestedFamily : familySelect.options[0].value);
    familySelect.addEventListener('change', function () {
      showFamily(familySelect.value);
    });

    themeButton.addEventListener('click', function () {
      window.AiaDesignSystem.toggleTheme();
    });
    document.addEventListener('aia-theme-change', updateThemeLabel);
    function updateDensity() {
      root.dataset.density = density.value === 'compact' ? 'compact' : 'touch';
      root.querySelectorAll('[data-density-sample]').forEach(function (sample) {
        sample.setAttribute('aria-current', String(sample.dataset.densitySample === root.dataset.density));
      });
    }
    density.value = window.matchMedia('(min-width: 1200px)').matches ? 'compact' : 'touch';
    density.addEventListener('change', updateDensity);
    function setShellDrawer(open, restoreFocus) {
      var contextual = shellViewport.matches;
      shellPanel.dataset.shellPresentation = contextual ? 'contextual' : 'drawer';
      shellToggle.setAttribute('aria-expanded', String(contextual ? false : open));
      shellPanel.hidden = contextual ? false : !open;
      if (!open && restoreFocus) shellToggle.focus();
    }
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
    updateThemeLabel();
    updateDensity();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
