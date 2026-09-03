/**
 * Context Manager
 * Handles global application state (Week, Project, Module)
 * Dispatches events when context changes.
 *
 * `setWeek()`/`clearWeek()` llaman a `ContextController` (src/Controllers/Core/ContextController.php),
 * endurecido en T02: exige `X-CSRF-Token` (form-key `shell_api`, mismo patrón que `lps_drawer.js`)
 * y devuelve `{ok, ...}` / `{ok:false, error:{code,message}}`, no el `{success,message}` original.
 * El token lo emite `views/partials/shell_sidebar.php` en `<meta name="lps-shell-csrf-token">`
 * (2026-09-03) — el único parcial que incluyen las 16 vistas que traen este script.
 */
function lpsShellCsrfToken() {
  const meta = document.querySelector('meta[name="lps-shell-csrf-token"]');
  return (meta && meta.getAttribute('content')) || '';
}

class ContextManager {
  constructor() {
    this.state = {
      project: '',
      week: 0,
      module: '',
    };
    this.init();
  }

  init() {
    // Read initial state from hidden inputs if available (Legacy Support)
    const weekInput = document.getElementById('semana');
    const projectInput = document.getElementById('proyecto');

    if (weekInput) this.state.week = parseInt(weekInput.value) || 0;
    if (projectInput) this.state.project = projectInput.value || '';

    // Listen for internal events
    document.addEventListener('context:change_week', (e) =>
      this.setWeek(e.detail.week, e.detail.redirect)
    );
  }

  async setWeek(week, redirectUrl = null) {
    if (!week) return;

    try {
      const response = await fetch('/context/week', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': lpsShellCsrfToken(),
        },
        body: JSON.stringify({ semana: week }),
      });
      const data = await response.json();

      if (data.ok) {
        this.state.week = week;
        // Dispatch updated event
        document.dispatchEvent(new CustomEvent('context:updated', { detail: this.state }));

        if (redirectUrl) {
          window.location.href = redirectUrl;
        } else {
          window.location.reload();
        }
      } else {
        const mensaje = (data.error && data.error.message) || 'Error desconocido';
        console.error('Context Error:', mensaje);
        if (window.AIA && window.AIA.Notice && typeof window.AIA.Notice.error === 'function') {
          AIA.Notice.error('No se pudo cambiar la semana: ' + mensaje);
        } else if (typeof window.alert === 'function') {
          window.alert('No se pudo cambiar la semana: ' + mensaje);
        }
      }
    } catch (err) {
      console.error('Network Error:', err);
    }
  }

  async clearWeek(redirectUrl = null) {
    try {
      const response = await fetch('/context/clear-week', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': lpsShellCsrfToken(),
        },
      });
      const data = await response.json();

      if (data.ok) {
        this.state.week = 0;
        // Dispatch updated event
        document.dispatchEvent(new CustomEvent('context:updated', { detail: this.state }));

        if (redirectUrl) {
          window.location.href = redirectUrl;
        } else {
          window.location.reload();
        }
      }
    } catch (err) {
      console.error('Network Error:', err);
    }
  }
}

// Initialize Global Context
window.Context = new ContextManager();

// Global Helper for Legacy Onclicks
window.cambiarSemanaSesion = (week, url) => {
  window.Context.setWeek(week, url);
};
