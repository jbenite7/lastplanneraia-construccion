(function () {
  'use strict';

  function syncPasswordToggles(root) {
    root.querySelectorAll('[data-password-toggle]').forEach(function (button) {
      var input = document.getElementById(button.dataset.passwordToggle);
      if (!input) return;
      var visible = input.type === 'text';
      button.setAttribute('aria-pressed', visible ? 'true' : 'false');
      button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
      var icon = button.querySelector('i');
      if (icon) icon.className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-password-toggle]');
    if (!button) return;
    var input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    syncPasswordToggles(document);
    input.focus();
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-auth-form]');
    if (!form || form.getAttribute('aria-busy') === 'true') return;
    form.setAttribute('aria-busy', 'true');
    var submit = form.querySelector('[type="submit"]');
    if (!submit) return;
    submit.disabled = true;
    submit.dataset.defaultText = submit.innerHTML;
    submit.textContent = submit.dataset.loadingText || 'Procesando…';
  });

  new MutationObserver(function () { syncPasswordToggles(document); })
    .observe(document.body, { childList: true, subtree: true });
  syncPasswordToggles(document);
})();
