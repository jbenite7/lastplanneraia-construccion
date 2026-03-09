/**
 * Context Manager
 * Handles global application state (Week, Project, Module)
 * Dispatches events when context changes.
 */
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ semana: week }),
      });
      const data = await response.json();

      if (data.success) {
        this.state.week = week;
        // Dispatch updated event
        document.dispatchEvent(new CustomEvent('context:updated', { detail: this.state }));

        if (redirectUrl) {
          window.location.href = redirectUrl;
        } else {
          window.location.reload();
        }
      } else {
        console.error('Context Error:', data.message);
        alert('No se pudo cambiar la semana: ' + data.message);
      }
    } catch (err) {
      console.error('Network Error:', err);
    }
  }

  async clearWeek(redirectUrl = null) {
    try {
      const response = await fetch('/context/clear-week', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      const data = await response.json();

      if (data.success) {
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
