/**
 * public/js/modules/lps_drawer.js
 * ================================
 * Motor reactivo y contextual para los LPS Contextual Drawers del sistema de planeación AIA.
 * Maneja la inyección del DOM, escucha los eventos de Handsontable, gestiona el Modo Simulación,
 * e implementa la consolidación semanal inteligente (LPS Weekly Digest) para mitigar el spam.
 */

window.LPSContextualDrawer = (function() {
  let activeHot = null;
  let activeModuleKey = null;
  let activeStateAdapter = null;
  let activeRowIndex = null;

  // Inicializar estado del modo simulación en localStorage si no existe
  if (localStorage.getItem('lps_simulated_mode') === null) {
    localStorage.setItem('lps_simulated_mode', 'true');
  }

  // Estructura HTML base inyectada dinámicamente
  const drawerHtml = `
    <div class="lps-drawer-overlay" id="lps_drawer_overlay"></div>
    <div class="lps-drawer" id="lps_drawer">
      <div class="lps-drawer-header">
        <h3 id="lps_drawer_title">Ayuda Operativa LPS</h3>
        <button class="lps-drawer-close" id="lps_drawer_close" aria-label="Cerrar">&times;</button>
      </div>
      <div class="lps-drawer-body">
        <!-- Indicador de Contexto / Selección de Fila -->
        <div class="lps-card-glass" id="lps_diagnostic_card">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
            <span class="lps-badge" id="lps_badge_priority">Prioridad</span>
            <span style="font-size: 0.8rem; color: #666; font-weight: 500;" id="lps_consecutivo">Selecciona una fila</span>
          </div>
          <h4 style="margin: 0 0 6px 0; font-size: 1.05rem; color: #1a3c2a; font-weight: 600;" id="lps_actividad_title">Ninguna actividad seleccionada</h4>
          <p style="margin: 0; font-size: 0.88rem; color: #495057; line-height: 1.4;" id="lps_diagnostico_desc">
            Haz clic en cualquier celda de la planilla para recibir el diagnóstico clínico de la tarea, restricciones abiertas y el plan estratégico recomendado.
          </p>
        </div>

        <!-- Acciones de Escalado y Comunicación -->
        <div class="lps-card-glass" id="lps_action_card" style="display: none;">
          <h4 style="margin: 0 0 10px 0; font-size: 0.95rem; color: #1a3c2a; font-weight: 600; text-transform: uppercase;">Escalamiento Express</h4>
          
          <button class="lps-btn lps-btn-success" id="lps_btn_whatsapp">
            <span style="font-size: 1.1rem; line-height: 1;">💬</span> Escalar por WhatsApp
          </button>
          
          <button class="lps-btn lps-btn-outline" id="lps_btn_email">
            <span style="font-size: 1.1rem; line-height: 1;">✉️</span> Notificar por Correo
          </button>

          <!-- Previsualización de Mensaje Copiado -->
          <div id="lps_sim_clipboard_card" style="display: none; border-top: 1px dashed rgba(26, 60, 42, 0.15); padding-top: 10px; margin-top: 10px;">
            <span style="font-size: 0.75rem; font-weight: 600; color: #8b4011; text-transform: uppercase;">Previsualización de Alerta:</span>
            <div class="lps-digest-preview" id="lps_alert_text_preview"></div>
          </div>
        </div>

        <!-- LPS Weekly Digest (Consolidación Semanal) -->
        <div class="lps-card-glass" id="lps_digest_section">
          <h4 style="margin: 0 0 6px 0; font-size: 0.95rem; color: #1a3c2a; font-weight: 600; text-transform: uppercase;">Weekly Digest (Consolidado)</h4>
          <p style="margin: 0 0 12px 0; font-size: 0.82rem; color: #666; line-height: 1.3;">
            Recorre la planilla activa, agrupa todas las actividades P1 críticas y genera un reporte unificado de bloqueos para el contratista o director.
          </p>
          <button class="lps-btn lps-btn-outline" style="min-height: 44px; margin-bottom: 0;" id="lps_btn_digest">
            <span style="font-size: 1.1rem; line-height: 1;">📊</span> Compilar Digest de Obra
          </button>

          <div class="lps-digest-card" id="lps_digest_result_card" style="display: none;">
            <span style="font-size: 0.75rem; font-weight: 600; color: #1a3c2a; text-transform: uppercase;">Digest de Bloqueos:</span>
            <div class="lps-digest-preview" id="lps_digest_text_preview"></div>
            <button class="lps-btn lps-btn-success" style="min-height: 38px; margin: 10px 0 0 0; font-size: 0.85rem;" id="lps_btn_copy_digest">
              Copiar Digest Completo
            </button>
          </div>
        </div>

        <!-- Guía de Colores y Leyendas Legacy (Modo Consulta) -->
        <div class="lps-card-glass" id="lps_legend_ref_card" style="display: none;">
          <h4 style="margin: 0 0 10px 0; font-size: 0.95rem; color: #1a3c2a; font-weight: 600; text-transform: uppercase;">Guía de Estados</h4>
          <div id="lps_legend_items_container" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>
      </div>

      <!-- Configuración del Modo Simulación -->
      <div class="lps-drawer-footer">
        <div class="lps-sim-toggle-container">
          <span class="lps-sim-label">Modo Simulación (Inactivo)</span>
          <input type="checkbox" id="lps_sim_mode_toggle" style="width: 20px; height: 20px; cursor: pointer;" />
        </div>
        <p style="margin: 6px 0 0 0; font-size: 0.72rem; color: #7f8c8d; text-align: center; line-height: 1.2;">
          Las notificaciones reales están bloqueadas. Los CTAs copiarán el reporte al portapapeles.
        </p>
      </div>
    </div>
  `;

  // Estructura de descripciones y guías por Módulo/Estado (Storytelling Calibrado)
  const guides = {
    'programa-general': {
      title: 'Monitoreo de Hitos del Proyecto',
      legend: [
        { label: 'Ruta Crítica Inicio Vencido', color: '#8b4011', desc: 'Hito crítico bloqueado. Afecta directamente la fecha de entrega.' },
        { label: 'Alistamiento Lookahead (2-3 sem)', color: '#1f4f82', desc: 'En ventana de control. Verificar compras y suministros.' },
        { label: 'Seguimiento Rutinario', color: '#1a3c2a', desc: 'Progreso conforme a la línea base teórica.' }
      ]
    },
    'programacion-intermedia': {
      title: 'Control de Lookahead & Restricciones',
      legend: [
        { label: 'Inicio Vencido con Restricción Abierta', color: '#8b4011', desc: 'Bloqueo logístico. Requiere escalado a compras o compras técnicas hoy.' },
        { label: 'Liberación Próxima (< 7 días)', color: '#1f4f82', desc: 'Habilitación activa. Asegurar contratos, cuadrillas y permisos.' },
        { label: 'Sin Restricciones / Listo', color: '#1a3c2a', desc: 'Actividad habilitada y lista para programar.' }
      ]
    },
    'programacion-semanal': {
      title: 'Compromisos Semanales de Campo',
      legend: [
        { label: 'Atraso Crítico / Frente Detenido', color: '#8b4011', desc: 'El subcontratista no puede avanzar. Acción: Intervención en campo hoy.' },
        { label: 'Compromiso Pendiente / En Progreso', color: '#1f4f82', desc: 'Actividad en ejecución. Monitorear recursos.' },
        { label: 'Completado con Éxito', color: '#1a3c2a', desc: 'Vía Libre. Compromiso semanal firmado y cumplido.' }
      ]
    }
  };

  function injectDOM() {
    if (document.getElementById('lps_drawer')) return;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = drawerHtml;
    while (tempDiv.firstChild) {
      document.body.appendChild(tempDiv.firstChild);
    }
    bindEvents();
  }

  function bindEvents() {
    const overlay = document.getElementById('lps_drawer_overlay');
    const drawer = document.getElementById('lps_drawer');
    const closeBtn = document.getElementById('lps_drawer_close');
    const toggle = document.getElementById('lps_sim_mode_toggle');

    // Cerrar drawer
    const closeDrawer = () => {
      drawer.classList.remove('open');
      overlay.classList.remove('active');
    };

    closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    // Sync toggle de simulación
    const simMode = localStorage.getItem('lps_simulated_mode') === 'true';
    toggle.checked = simMode;
    toggle.addEventListener('change', function() {
      localStorage.setItem('lps_simulated_mode', this.checked ? 'true' : 'false');
      showNotification(this.checked ? 'Modo Simulación Activado (Envíos Bloqueos)' : 'Modo Envíos Activos (Configurar Cuentas)');
      updateContextFromActiveSelection();
    });

    // Bindeo de botones de escalado
    document.getElementById('lps_btn_whatsapp').addEventListener('click', () => triggerEscalate('whatsapp'));
    document.getElementById('lps_btn_email').addEventListener('click', () => triggerEscalate('email'));

    // Bindeo de Digest Semanal
    document.getElementById('lps_btn_digest').addEventListener('click', compileWeeklyDigest);
    document.getElementById('lps_btn_copy_digest').addEventListener('click', copyDigestToClipboard);
  }

  function showNotification(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(26, 60, 42, 0.95);
      color: #ffffff;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
      z-index: 99999;
      pointer-events: none;
      transition: opacity 0.3s ease;
      font-family: 'Inter', sans-serif;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 2500);
  }

  function triggerEscalate(type) {
    if (activeRowIndex === null || !activeHot) return;
    const rowData = activeHot.getSourceDataAtRow(activeRowIndex);
    const simulated = localStorage.getItem('lps_simulated_mode') === 'true';

    // Construcción de la alerta
    const consecutivo = rowData.Consecutivo || rowData.id || activeRowIndex + 1;
    const actividad = rowData.Actividad || rowData.nombre || 'Actividad sin nombre';
    const subcontratista = rowData.Subcontratista || rowData.responsable || 'Sin Asignar';
    const restriccion = rowData.Restriccion || rowData.causa_no_cumplimiento || 'Restricciones Abiertas';
    const telefono = rowData.Telefono || rowData.telefono_subcontratista || '';
    const correo = rowData.Correo || rowData.correo_responsable || '';

    const text = ` Estimado ${subcontratista}, la actividad #${consecutivo} (${actividad}) presenta un bloqueo crítico debido a la restricción abierta de [${restriccion}]. Solicitamos su intervención urgente para liberar el frente. - Last Planner AIA`;

    if (simulated) {
      navigator.clipboard.writeText(text).then(() => {
        showNotification('¡Texto copiado al portapapeles en Modo Simulación!');
      });
    } else {
      if (type === 'whatsapp') {
        if (!telefono) {
          showNotification('⚠️ Sin teléfono asignado. Usando copiado de contingencia.');
          navigator.clipboard.writeText(text);
          return;
        }
        const waUrl = `https://api.whatsapp.com/send?phone=${telefono.replace(/\s+/g, '')}&text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank');
      } else {
        if (!correo) {
          showNotification('⚠️ Sin correo asignado. Usando copiado de contingencia.');
          navigator.clipboard.writeText(text);
          return;
        }
        const mailUrl = `mailto:${correo}?subject=${encodeURIComponent('[ALERTA LPS] Bloqueo de Actividad AIA')}&body=${encodeURIComponent(text)}`;
        window.open(mailUrl, '_blank');
      }
    }
  }

  function updateContextFromActiveSelection() {
    if (activeRowIndex === null || !activeHot) return;
    const rowData = activeHot.getSourceDataAtRow(activeRowIndex);
    LPSContextualDrawer.updateContext(rowData, activeModuleKey);
  }

  function compileWeeklyDigest() {
    if (!activeHot) return;
    const sourceData = activeHot.getSourceData();
    const criticallyBlocked = {};

    sourceData.forEach((row, idx) => {
      // Filtrar únicamente Ruta Crítica P1 (Ruta_Critica === 1 o similar dependiente del módulo)
      const isCritical = row.Ruta_Critica === 1 || row.ruta_critica === 1 || row.prioridad === 'P1' || row.p1 === 1;
      const subcontratista = row.Subcontratista || row.responsable || 'Sin asignar';
      const consecutivo = row.Consecutivo || row.id || idx + 1;
      const actividad = row.Actividad || row.nombre || 'Tarea';
      const restriccion = row.Restriccion || row.causa_no_cumplimiento || 'Restricciones Abiertas';

      // Detectamos si posee cuellos de botella (retraso, restricción, incompleto)
      const hasBottleneck = row.atraso > 0 || row.Restriccion || row.causa_no_cumplimiento || row.compromiso_vencido;

      if (isCritical && hasBottleneck) {
        if (!criticallyBlocked[subcontratista]) {
          criticallyBlocked[subcontratista] = [];
        }
        criticallyBlocked[subcontratista].push(`Actividad #${consecutivo} (${actividad}) - Restricción: ${restriccion}`);
      }
    });

    const subcontratistasKeys = Object.keys(criticallyBlocked);
    if (subcontratistasKeys.length === 0) {
      document.getElementById('lps_digest_text_preview').textContent = " Excelente. No se encontraron bloqueos críticos en actividades P1 (Ruta Crítica) para esta semana.";
      document.getElementById('lps_digest_result_card').style.display = 'block';
      return;
    }

    let digestText = `📋 REPORT CONSOLIDADO DE BLOQUEOS LPS - OBRA AIA\n`;
    digestText += `Semana de Control: ${new Date().toLocaleDateString()}\n`;
    digestText += `==============================================\n\n`;

    subcontratistasKeys.forEach(sub => {
      digestText += `▶️ RESPONSIBLE: ${sub}\n`;
      criticallyBlocked[sub].forEach(task => {
        digestText += `  • ${task}\n`;
      });
      digestText += `\n`;
    });

    digestText += `----------------------------------------------\n`;
    digestText += `Solicitamos a los líderes de frente asegurar recursos y coordinar la liberación de frentes para evitar atrasos en la línea base teórica.`;

    document.getElementById('lps_digest_text_preview').textContent = digestText;
    document.getElementById('lps_digest_result_card').style.display = 'block';
    showNotification('¡Digest consolidado semanal compilado con éxito!');
  }

  function copyDigestToClipboard() {
    const text = document.getElementById('lps_digest_text_preview').textContent;
    navigator.clipboard.writeText(text).then(() => {
      showNotification('¡Digest consolidado copiado al portapapeles!');
    });
  }

  return {
    init: function(hot, moduleKey, stateAdapter) {
      injectDOM();
      activeHot = hot;
      activeModuleKey = moduleKey;
      activeStateAdapter = stateAdapter;

      // Interceptar botón de Leyenda legacy
      const legendBtn = document.querySelector('.leyenda_colores') || document.getElementById('btn_leyenda');
      if (legendBtn) {
        // Clonar botón para quitar modales de Bootstrap
        const newBtn = legendBtn.cloneNode(true);
        newBtn.removeAttribute('data-toggle');
        newBtn.removeAttribute('data-target');
        newBtn.removeAttribute('data-bs-toggle');
        newBtn.removeAttribute('data-bs-target');
        legendBtn.parentNode.replaceChild(newBtn, legendBtn);

        newBtn.addEventListener('click', function(e) {
          e.preventDefault();
          LPSContextualDrawer.openLegend(moduleKey);
        });
      }

      // Escuchar selección en Handsontable
      hot.addHook('afterSelectionEnd', function(r, c, r2, c2) {
        if (r < 0) return;
        activeRowIndex = r;
        const rowData = hot.getSourceDataAtRow(r);
        LPSContextualDrawer.updateContext(rowData, moduleKey);
      });
    },

    updateContext: function(rowData, moduleKey) {
      if (!rowData) return;
      injectDOM();

      const drawer = document.getElementById('lps_drawer');
      const overlay = document.getElementById('lps_drawer_overlay');
      
      // Abrir drawer de forma reactiva
      drawer.classList.add('open');
      if (window.innerWidth < 992) {
        overlay.classList.add('active');
      }

      // Extraer datos de la fila
      const consecutivo = rowData.Consecutivo || rowData.id || 'N/A';
      const actividad = rowData.Actividad || rowData.nombre || 'Tarea sin nombre';
      const subcontratista = rowData.Subcontratista || rowData.responsable || 'Sin Asignar';
      const restriccion = rowData.Restriccion || rowData.causa_no_cumplimiento || 'Ninguna';

      // Evaluar estado con el adapter del módulo
      let stateInfo = { key: 'default', label: 'Seguimiento', rowClass: 'status-active' };
      if (activeStateAdapter) {
        try {
          stateInfo = activeStateAdapter(rowData);
        } catch(e) {
          console.error("Error al adaptar estado de la fila:", e);
        }
      }

      // Determinar si es Ruta Crítica (P1)
      const isCritical = rowData.Ruta_Critica === 1 || rowData.ruta_critica === 1 || rowData.prioridad === 'P1' || rowData.p1 === 1;
      
      // Renderizar metadatos
      document.getElementById('lps_consecutivo').textContent = `Actividad #${consecutivo}`;
      document.getElementById('lps_actividad_title').textContent = actividad;

      const badge = document.getElementById('lps_badge_priority');
      badge.className = 'lps-badge';
      
      const diagCard = document.getElementById('lps_diagnostic_card');
      diagCard.className = 'lps-card-glass';

      let diagnosticText = '';

      if (isCritical) {
        badge.classList.add('lps-badge-p1');
        badge.textContent = 'Ruta Crítica P1';
        diagCard.classList.add('lps-state-p1');

        if (restriccion && restriccion !== 'Ninguna') {
          diagnosticText = `🚨 FRENTE DETENIDO. La tarea está en ruta crítica y posee la restricción activa de [${restriccion}]. Requiere intervención inmediata del Residente de Obra con el Director hoy para evitar desvíos en la fecha contractual de entrega.`;
        } else {
          diagnosticText = `⚠️ HITO CRÍTICO DE OBRA. Actividad en ruta crítica con progreso activo. Asegurar recursos diarios, cuadrilla y frente liberado para mantener el ritmo operativo.`;
        }
        
        // Habilitar controles de comunicación
        document.getElementById('lps_action_card').style.display = 'block';
        
        // Generar previsualización del mensaje individual
        const simulated = localStorage.getItem('lps_simulated_mode') === 'true';
        if (simulated) {
          document.getElementById('lps_sim_clipboard_card').style.display = 'block';
          const alertText = `Estimado ${subcontratista}, la actividad #${consecutivo} (${actividad}) presenta un retraso crítico debido a la restricción [${restriccion}]. Rogamos coordinar liberación urgente. - Last Planner AIA`;
          document.getElementById('lps_alert_text_preview').textContent = alertText;
        } else {
          document.getElementById('lps_sim_clipboard_card').style.display = 'none';
        }

      } else {
        badge.classList.add('lps-badge-p3');
        badge.textContent = 'Seguimiento P3';
        diagCard.classList.add('lps-state-p3');
        diagnosticText = `🟢 SEGUIMIENTO RUTINARIO. Actividad de soporte. No impacta directamente la holgura del proyecto. Lógica informativa activa; sin CTAs de escalado telefónico para evitar spam al contratista.`;
        
        // Ocultar controles de comunicación individual para mitigar fatiga comunicativa
        document.getElementById('lps_action_card').style.display = 'none';
        document.getElementById('lps_sim_clipboard_card').style.display = 'none';
      }

      document.getElementById('lps_diagnostico_desc').textContent = diagnosticText;
      document.getElementById('lps_legend_ref_card').style.display = 'none';
    },

    openLegend: function(moduleKey) {
      injectDOM();
      
      const drawer = document.getElementById('lps_drawer');
      const overlay = document.getElementById('lps_drawer_overlay');
      
      drawer.classList.add('open');
      if (window.innerWidth < 992) {
        overlay.classList.add('active');
      }

      // Cargar guía operativa del módulo
      const moduleGuide = guides[moduleKey] || { title: 'Guía de Estados', legend: [] };
      document.getElementById('lps_consecutivo').textContent = 'Guía Operativa';
      document.getElementById('lps_actividad_title').textContent = moduleGuide.title;
      document.getElementById('lps_diagnostico_desc').textContent = 'A continuación se listan las prioridades operativas de control establecidas para este módulo. Selecciona cualquier fila en el grid para ver el diagnóstico particular de tu tarea.';
      
      const badge = document.getElementById('lps_badge_priority');
      badge.className = 'lps-badge lps-badge-p2';
      badge.textContent = 'LPS AIA';

      const diagCard = document.getElementById('lps_diagnostic_card');
      diagCard.className = 'lps-card-glass lps-state-p2';

      // Ocultar acciones atómicas
      document.getElementById('lps_action_card').style.display = 'none';

      // Renderizar leyenda
      const container = document.getElementById('lps_legend_items_container');
      container.innerHTML = '';
      moduleGuide.legend.forEach(item => {
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = `
          display: flex;
          align-items: flex-start;
          gap: 10px;
          padding: 8px 10px;
          background: #ffffff;
          border-radius: 8px;
          border-left: 4px solid ${item.color};
          font-size: 0.85rem;
          box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        `;
        itemDiv.innerHTML = `
          <div>
            <strong style="color: ${item.color}; display: block; margin-bottom: 2px;">${item.label}</strong>
            <span style="color: #555; line-height: 1.3;">${item.desc}</span>
          </div>
        `;
        container.appendChild(itemDiv);
      });

      document.getElementById('lps_legend_ref_card').style.display = 'block';
    }
  };
})();

// Auto-inyección al cargar el script
window.addEventListener('DOMContentLoaded', () => {
  // Inicialización diferida por precaución
});
