/**
 * public/js/modules/lps_drawer.js
 * ================================
 * Cajón Contextual LPS Premium para control de crisis, comentarios en hilos (Slack-style),
 * cálculo del Termómetro Habilitador de Restricciones (ITR) y escalamiento jerárquico SOS.
 */

window.LPSContextualDrawer = (function() {
  let activeHot = null;
  let activeModuleKey = null;
  let activeStateAdapter = null;
  let activeRowIndex = null;
  let activeConsecutivo = null;
  let activeParentId = null;
  let activeAlertaId = null;

  // Cargar/Inicializar modo simulación
  if (localStorage.getItem('lps_simulated_mode') === null) {
    localStorage.setItem('lps_simulated_mode', 'true');
  }

  function getSessionContext() {
    const dbEl = document.getElementById('baseDatos_PHP');
    const semEl = document.getElementById('semana_PHP');
    const permEl = document.getElementById('permiso_PHP');
    return {
      dbName: dbEl ? dbEl.value : '',
      semana: semEl ? parseInt(semEl.value, 10) : 0,
      permiso: permEl ? permEl.value : ''
    };
  }

  function bindEvents() {
    const overlay = document.getElementById('lps_drawer_overlay');
    const drawer = document.getElementById('lps_drawer');
    const closeBtn = document.getElementById('lps_drawer_close');
    const toggle = document.getElementById('lps_sim_mode_toggle');
    const sidebarTrigger = document.getElementById('lps_sidebar_trigger');

    if (sidebarTrigger) {
      sidebarTrigger.addEventListener('click', () => {
        if (drawer) {
          if (drawer.classList.contains('open')) {
            drawerClose();
          } else {
            drawerOpen();
          }
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        drawerClose();
      });
    }
    if (overlay) {
      overlay.addEventListener('click', () => {
        drawerClose();
      });
    }

    // Sync toggle modo simulación
    if (toggle) {
      toggle.checked = localStorage.getItem('lps_simulated_mode') === 'true';
      toggle.addEventListener('change', function() {
        localStorage.setItem('lps_simulated_mode', this.checked ? 'true' : 'false');
        showNotification(this.checked ? 'Modo Simulación Activado (Envíos Bloqueados)' : 'Modo Envíos Activos (Notificaciones Reales)');
        refreshDrawerData();
      });
    }

    // Botones de Escalamiento SOS
    const btnWa = document.getElementById('lps_btn_whatsapp');
    const btnEmail = document.getElementById('lps_btn_email');
    if (btnWa) btnWa.addEventListener('click', () => triggerEscalate('whatsapp'));
    if (btnEmail) btnEmail.addEventListener('click', () => triggerEscalate('email'));

    // Botón enviar comentario
    const btnSend = document.getElementById('lps_btn_send_comment');
    if (btnSend) btnSend.addEventListener('click', postComment);

    // Cancelar responder en hilo
    const btnCancelReply = document.getElementById('lps_btn_cancel_reply');
    if (btnCancelReply) {
      btnCancelReply.addEventListener('click', () => {
        activeParentId = null;
        document.getElementById('lps_thread_replying_indicator').style.display = 'none';
      });
    }

    // Validación interactiva de justificación de cierre
    const closureInput = document.getElementById('lps_closure_justification');
    const closureBtn = document.getElementById('lps_btn_close_crisis');
    if (closureInput && closureBtn) {
      closureInput.addEventListener('input', function() {
        const len = this.value.trim().length;
        const counter = document.getElementById('lps_closure_char_count');
        if (counter) {
          counter.textContent = `${len} / 100 caracteres`;
          if (len >= 100) {
            counter.style.color = '#198754';
          } else {
            counter.style.color = '#dc3545';
          }
        }
        closureBtn.disabled = len < 100;
      });

      closureBtn.addEventListener('click', closeCrisisAlert);
    }

    // Digest Semanal
    const btnDigest = document.getElementById('lps_btn_digest');
    const btnCopyDigest = document.getElementById('lps_btn_copy_digest');
    if (btnDigest) btnDigest.addEventListener('click', compileWeeklyDigest);
    if (btnCopyDigest) btnCopyDigest.addEventListener('click', copyDigestToClipboard);
  }

  function showNotification(message) {
    if (window.toastr) {
      window.toastr.info(message);
      return;
    }
    const toast = document.createElement('div');
    toast.style.cssText = `
      position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
      background: rgba(26, 60, 42, 0.96); color: #ffffff; padding: 10px 20px;
      border-radius: 8px; font-size: 0.85rem; font-weight: 600; z-index: 99999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15); pointer-events: none; transition: opacity 0.3s;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 2500);
  }

  function calculateITR(rowData) {
    const fields = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'];
    let liberadas = 0;
    let aplicables = 0;

    fields.forEach(f => {
      if (rowData[f] !== undefined && rowData[f] !== null) {
        const val = String(rowData[f]).trim().toUpperCase();
        if (val !== 'N/A' && val !== 'NA' && val !== '') {
          aplicables++;
          const floatVal = parseFloat(val);
          if (!isNaN(floatVal) && floatVal >= 0.999) {
            liberadas++;
          }
        }
      }
    });

    const porcentaje = aplicables > 0 ? (liberadas / aplicables) : 1.0;
    return {
      porcentaje: Math.round(porcentaje * 100),
      liberadas,
      aplicables
    };
  }

  function updateITRVisuals(itr) {
    const card = document.getElementById('lps_itr_card');
    const bar = document.getElementById('lps_itr_bar');
    const valText = document.getElementById('lps_itr_value');
    const details = document.getElementById('lps_itr_details');

    if (!card) return;
    card.style.display = 'block';

    if (bar) {
      bar.style.width = `${itr.porcentaje}%`;
      // Gradiente o colores según el porcentaje
      if (itr.porcentaje >= 80) {
        bar.style.background = '#198754'; // Verde
      } else if (itr.porcentaje >= 50) {
        bar.style.background = '#ffc107'; // Amarillo
      } else {
        bar.style.background = '#dc3545'; // Rojo
      }
    }
    if (valText) valText.textContent = `${itr.porcentaje}%`;
    if (details) {
      details.textContent = `${itr.liberadas} de ${itr.aplicables} restricciones habilitantes liberadas.`;
    }
  }

  function refreshDrawerData() {
    if (activeConsecutivo === null) return;
    loadCommentsAndCrisis();
  }

  function loadCommentsAndCrisis() {
    const container = document.getElementById('lps_comments_container');
    if (container) {
      container.innerHTML = '<div style="font-size:0.8rem; color:#666;">Cargando bitácora de hilos...</div>';
    }

    fetch(`/api/lps/comments?consecutivo=${activeConsecutivo}`)
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          renderCommentsTree(response.data);
          detectActiveCrisis(response.data);
        } else {
          if (container) container.innerHTML = `<div style="color:#dc3545; font-size:0.8rem;">Error: ${response.mensaje}</div>`;
        }
      })
      .catch(err => {
        console.error("Error al cargar comentarios:", err);
        if (container) container.innerHTML = '<div style="color:#dc3545; font-size:0.8rem;">Error de conexión.</div>';
      });
  }

  function detectActiveCrisis(commentsData) {
    // Buscar si hay algún escalamiento activo referenciado en los comentarios o consultar API
    // En su defecto, comprobamos si el rowData de la fila tiene alerta_crisis = 1
    const rowData = activeHot ? activeHot.getSourceDataAtRow(activeRowIndex) : null;
    const isCrisis = rowData && (parseInt(rowData.alerta_crisis, 10) === 1 || rowData.alerta_crisis === true);

    const closureCard = document.getElementById('lps_closure_card');
    const actCard = document.getElementById('lps_action_card');

    if (isCrisis) {
      if (closureCard) closureCard.style.display = 'block';
      if (actCard) actCard.style.display = 'block';

      // Obtener el ID de la alerta desde los comentarios o setear fallback temporal
      activeAlertaId = null;
      for (let c of commentsData) {
        if (c.escalamiento_id) {
          activeAlertaId = c.escalamiento_id;
          break;
        }
      }
      // Fallback: Buscar en la base de datos a través de una llamada corta si es necesario o asumir el ID en caliente
      if (!activeAlertaId && rowData) {
        activeAlertaId = rowData.escalamiento_id || rowData.alerta_id || null;
      }
    } else {
      if (closureCard) closureCard.style.display = 'none';
      if (actCard) {
        // Mostrar u ocultar SOS WhatsApp según prioridad Ruta Crítica P1
        const isCritical = rowData && (rowData.Ruta_Critica === 1 || rowData.prioridad === 'P1');
        actCard.style.display = isCritical ? 'block' : 'none';
      }
    }
  }

  function renderCommentsTree(comments) {
    const container = document.getElementById('lps_comments_container');
    const card = document.getElementById('lps_comments_card');
    if (!container || !card) return;

    card.style.display = 'block';
    container.innerHTML = '';

    if (!comments || comments.length === 0) {
      container.innerHTML = '<div style="font-size:0.8rem; color:#888; text-align:center; padding:10px;">Sin comentarios registrados. Escribe uno para iniciar la bitácora.</div>';
      return;
    }

    comments.forEach(c => {
      const isSystem = c.usuario_id === 0 || c.autor_nombre === 'Sistema' || !c.autor_nombre;
      const autor = isSystem ? 'Sistema AIA' : `${c.autor_nombre} (${c.autor_cargo || 'Cargo'})`;
      
      const commentDiv = document.createElement('div');
      commentDiv.className = 'lps-comment';
      commentDiv.style.cssText = `
        padding: 8px 10px; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 3px solid #1a3c2a;
        margin-bottom: 8px; font-size: 0.82rem;
      `;
      if (isSystem) {
        commentDiv.style.borderLeftColor = '#dc3545';
        commentDiv.style.background = 'rgba(220,53,69,0.03)';
      }

      // Reemplazar @D, @OT, etc. con badges
      let commentText = escapeHtml(c.comentario);
      commentText = commentText.replace(/@([A-Z]+)/g, '<span class="lps-mention-badge">@$1</span>');

      commentDiv.innerHTML = `
        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
          <strong style="color:#1a3c2a;">${escapeHtml(autor)}</strong>
          <span style="font-size:0.7rem; color:#888;">${c.created_at}</span>
        </div>
        <div style="color:#2d3748; line-height:1.4; white-space:pre-wrap;">${commentText}</div>
        <div style="margin-top:6px; display:flex; gap:12px; font-size:0.72rem;">
          <a href="#" class="lps-reply-trigger" data-id="${c.id}" style="color:#198754; font-weight:700; text-decoration:none;">Responder</a>
        </div>
        <div class="lps-replies-container" style="margin-left: 16px; margin-top: 8px; border-left: 1px dashed rgba(0,0,0,0.08); padding-left: 10px;"></div>
      `;

      // Renderizar respuestas del hilo
      const repliesContainer = commentDiv.querySelector('.lps-replies-container');
      if (c.respuestas && c.respuestas.length > 0) {
        c.respuestas.forEach(r => {
          const rDiv = document.createElement('div');
          rDiv.style.cssText = 'padding: 5px 8px; background: #ffffff; border-radius: 6px; margin-top: 4px; font-size: 0.8rem; box-shadow: 0 1px 2px rgba(0,0,0,0.02);';
          
          let replyText = escapeHtml(r.comentario);
          replyText = replyText.replace(/@([A-Z]+)/g, '<span class="lps-mention-badge">@$1</span>');

          const rSystem = r.usuario_id === 0 || !r.autor_nombre;
          const rAutor = rSystem ? 'Sistema AIA' : `${r.autor_nombre} (${r.autor_cargo || 'Cargo'})`;

          rDiv.innerHTML = `
            <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <strong style="color: #495057;">${escapeHtml(rAutor)}</strong>
              <span style="font-size:0.68rem; color:#aaa;">${r.created_at}</span>
            </div>
            <div style="color:#333; line-height:1.35; white-space:pre-wrap;">${replyText}</div>
          `;
          repliesContainer.appendChild(rDiv);
        });
      }

      container.appendChild(commentDiv);
    });

    // Agregar listeners a los enlaces de respuesta
    container.querySelectorAll('.lps-reply-trigger').forEach(el => {
      el.addEventListener('click', function(e) {
        e.preventDefault();
        activeParentId = parseInt(this.getAttribute('data-id'), 10);
        
        const indicator = document.getElementById('lps_thread_replying_indicator');
        if (indicator) {
          indicator.style.display = 'flex';
          const authorName = this.closest('.lps-comment').querySelector('strong').textContent;
          indicator.querySelector('span').textContent = `Respondiendo al hilo de ${authorName}`;
        }
        
        const input = document.getElementById('lps_comment_input');
        if (input) input.focus();
      });
    });
  }

  function postComment() {
    const input = document.getElementById('lps_comment_input');
    if (!input) return;
    const comentario = input.value.trim();
    if (!comentario) return;

    // Detectar menciones de roles
    const menciones = [];
    const matches = comentario.match(/@([A-Z]+)/g);
    if (matches) {
      matches.forEach(m => {
        const rol = m.substring(1);
        if (!menciones.includes(rol)) menciones.push(rol);
      });
    }

    const formData = new FormData();
    formData.append('consecutivo', activeConsecutivo);
    formData.append('comentario', comentario);
    if (activeParentId) formData.append('parent_id', activeParentId);
    if (activeAlertaId) formData.append('escalamiento_id', activeAlertaId);
    if (menciones.length > 0) formData.append('menciones', JSON.stringify({ roles: menciones }));

    fetch('/api/lps/comments/add', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          input.value = '';
          activeParentId = null;
          const indicator = document.getElementById('lps_thread_replying_indicator');
          if (indicator) indicator.style.display = 'none';
          
          showNotification('Comentario registrado.');
          refreshDrawerData();
        } else {
          showNotification(`Error: ${response.mensaje}`);
        }
      })
      .catch(err => {
        console.error("Error al enviar comentario:", err);
        showNotification('Error de conexión al enviar comentario.');
      });
  }

  function closeCrisisAlert() {
    const input = document.getElementById('lps_closure_justification');
    if (!input || !activeAlertaId) return;

    const justificacion = input.value.trim();
    if (justificacion.length < 100) {
      showNotification('La justificación debe tener al menos 100 caracteres.');
      return;
    }

    const formData = new FormData();
    formData.append('alerta_id', activeAlertaId);
    formData.append('justificacion', justificacion);

    fetch('/api/lps/crisis/close', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(response => {
        if (response.respuesta === 'OK') {
          showNotification('¡Crisis mitigada y cerrada formalmente!');
          input.value = '';
          
          // Limpiar banderas en caliente en Handsontable
          if (activeHot && activeRowIndex !== null) {
            activeHot.setDataAtRowProp(activeRowIndex, 'alerta_crisis', 0);
          }

          drawerClose();
        } else {
          showNotification(`Error: ${response.mensaje}`);
        }
      })
      .catch(err => {
        console.error("Error al cerrar crisis:", err);
        showNotification('Error de conexión al cerrar la crisis.');
      });
  }

  function drawerOpen() {
    const drawer = document.getElementById('lps_drawer');
    const overlay = document.getElementById('lps_drawer_overlay');
    if (!drawer) return;

    drawer.classList.add('open');

    // Desplazamiento adaptable en desktop
    if (window.innerWidth >= 992) {
      document.body.classList.add('lps-drawer-open');
      // Redibujado diferido tras la transición de apertura
      setTimeout(() => {
        if (activeHot) activeHot.render();
      }, 300);
    } else if (overlay) {
      overlay.classList.add('active');
    }
  }

  function drawerClose() {
    const drawer = document.getElementById('lps_drawer');
    const overlay = document.getElementById('lps_drawer_overlay');
    if (drawer) drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('active');

    // Quitar desplazamiento adaptable en desktop
    document.body.classList.remove('lps-drawer-open');
    // Redibujado diferido tras la transición de cierre
    setTimeout(() => {
      if (activeHot) {
        activeHot.render();
      }
    }, 300);
  }

  function triggerEscalate(type) {
    if (activeRowIndex === null || !activeHot) return;
    const rowData = activeHot.getSourceDataAtRow(activeRowIndex);
    const simulated = localStorage.getItem('lps_simulated_mode') === 'true';

    const consecutivo = rowData.Consecutivo || rowData.id || activeRowIndex + 1;
    const actividad = rowData.Actividad || rowData.nombre || 'Actividad sin nombre';
    const subcontratista = rowData.Subcontratista || rowData.responsable || 'Sin Asignar';
    const restriccion = rowData.Restriccion || rowData.causa_no_cumplimiento || 'Restricciones Abiertas';
    const telefono = rowData.Telefono || rowData.telefono_subcontratista || '';
    const correo = rowData.Correo || rowData.correo_responsable || '';

    // Jerarquía de escalamiento SOS
    const rolesNombres = { 1: 'Residente', 2: 'Director', 3: 'Coordinador de Integración', 4: 'Gerente de Construcción', 5: 'Gerente General' };
    const nivelActual = parseInt(rowData.nivel_actual || 1, 10);
    const siguienteNivel = Math.min(nivelActual + 1, 5);
    const rolSuperior = rolesNombres[siguienteNivel];

    const text = `🚨 [ALERTA SOS - CRISIS AIA] 🚨\n\nEstimado superior en calidad de ${rolSuperior}, se notifica bloqueo crítico en la obra.\n• Actividad: #${consecutivo} - ${actividad}\n• Subcontratista: ${subcontratista}\n• Restricción/Causa: ${restriccion}\n\nSe solicita intervención jerárquica urgente para liberar el frente y evitar retrasos acumulados en la línea base teórica. - Last Planner AIA`;

    if (simulated) {
      navigator.clipboard.writeText(text).then(() => {
        showNotification('¡SOS copiado en Modo Simulación al portapapeles!');
      });
    } else {
      // Registrar la detonación del escalamiento en la base de datos
      const formData = new FormData();
      formData.append('consecutivo', consecutivo);
      formData.append('modulo', activeModuleKey === 'programa-general' ? 'PG' : (activeModuleKey === 'programacion-intermedia' ? 'PI' : 'PS'));
      formData.append('trigger', `SOS-${rolSuperior.substring(0, 3).toUpperCase()}`);

      fetch('/api/lps/crisis/register', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(response => {
          if (response.respuesta === 'OK') {
            // Actualizar Handsontable
            activeHot.setDataAtRowProp(activeRowIndex, 'alerta_crisis', 1);
            showNotification('Alerta SOS registrada.');
            refreshDrawerData();
          }
        })
        .catch(err => console.error("Error al registrar crisis por SOS:", err));

      if (type === 'whatsapp') {
        if (!telefono) {
          showNotification('⚠️ Sin teléfono asignado. Usando copiado al portapapeles.');
          navigator.clipboard.writeText(text);
          return;
        }
        const waUrl = `https://api.whatsapp.com/send?phone=${telefono.replace(/\s+/g, '')}&text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank');
      } else {
        if (!correo) {
          showNotification('⚠️ Sin correo asignado. Usando copiado al portapapeles.');
          navigator.clipboard.writeText(text);
          return;
        }
        const mailUrl = `mailto:${correo}?subject=${encodeURIComponent('[SOS CRISIS LPS] Intervención Jerárquica Requeria')}&body=${encodeURIComponent(text)}`;
        window.open(mailUrl, '_blank');
      }
    }
  }

  function compileWeeklyDigest() {
    if (!activeHot) return;
    const sourceData = activeHot.getSourceData();
    const criticallyBlocked = {};

    sourceData.forEach((row, idx) => {
      const isCritical = row.Ruta_Critica === 1 || row.ruta_critica === 1 || row.prioridad === 'P1' || row.p1 === 1;
      const subcontratista = row.Subcontratista || row.responsable || 'Sin asignar';
      const consecutivo = row.Consecutivo || row.id || idx + 1;
      const actividad = row.Actividad || row.nombre || 'Tarea';
      const restriccion = row.Restriccion || row.causa_no_cumplimiento || 'Restricciones Abiertas';

      const hasBottleneck = row.atraso > 0 || row.Restriccion || row.causa_no_cumplimiento || row.compromiso_vencido || parseInt(row.alerta_crisis, 10) === 1;

      if (isCritical && hasBottleneck) {
        if (!criticallyBlocked[subcontratista]) {
          criticallyBlocked[subcontratista] = [];
        }
        criticallyBlocked[subcontratista].push(`Actividad #${consecutivo} (${actividad}) - Restricción: ${restriccion}`);
      }
    });

    const subcontratistasKeys = Object.keys(criticallyBlocked);
    const preview = document.getElementById('lps_digest_text_preview');
    const resultCard = document.getElementById('lps_digest_result_card');

    if (subcontratistasKeys.length === 0) {
      if (preview) preview.textContent = "Excelente. No se encontraron bloqueos críticos en actividades P1 (Ruta Crítica) para esta semana.";
      if (resultCard) resultCard.style.display = 'block';
      return;
    }

    let digestText = `📋 REPORTE CONSOLIDADO DE BLOQUEOS LPS - OBRA AIA\n`;
    digestText += `Semana de Control: ${new Date().toLocaleDateString()}\n`;
    digestText += `==============================================\n\n`;

    subcontratistasKeys.forEach(sub => {
      digestText += `▶️ RESPONSABLE: ${sub}\n`;
      criticallyBlocked[sub].forEach(task => {
        digestText += `  • ${task}\n`;
      });
      digestText += `\n`;
    });

    digestText += `----------------------------------------------\n`;
    digestText += `Solicitamos a los líderes de frente asegurar recursos y coordinar la liberación de frentes para evitar atrasos en la línea base teórica.`;

    if (preview) preview.textContent = digestText;
    if (resultCard) resultCard.style.display = 'block';
    showNotification('¡Digest consolidado semanal compilado!');
  }

  function copyDigestToClipboard() {
    const preview = document.getElementById('lps_digest_text_preview');
    if (!preview) return;
    navigator.clipboard.writeText(preview.textContent).then(() => {
      showNotification('¡Digest copiado al portapapeles!');
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  return {
    init: function(hot, moduleKey, stateAdapter) {
      activeHot = hot;
      activeModuleKey = moduleKey;
      activeStateAdapter = stateAdapter;

      bindEvents();

      // Interceptar clics y selección en Handsontable
      hot.addHook('afterSelectionEnd', function(r, c, r2, c2) {
        if (r < 0) return;
        activeRowIndex = r;
        const rowData = hot.getSourceDataAtRow(r);
        LPSContextualDrawer.updateContext(rowData, moduleKey);
      });
    },

    updateContext: function(rowData, moduleKey) {
      if (!rowData) return;

      const drawer = document.getElementById('lps_drawer');
      const overlay = document.getElementById('lps_drawer_overlay');
      if (!drawer) return;

      // YA NO abrimos automáticamente el drawer de forma obligatoria.
      // Solo actualizamos paddings de Handsontable si el drawer ya está abierto
      const isDrawerOpen = drawer.classList.contains('open');

      if (isDrawerOpen) {
        // Desplazamiento adaptable en desktop
        if (window.innerWidth >= 992) {
          document.body.classList.add('lps-drawer-open');
          // Redibujado diferido tras la transición de apertura
          setTimeout(() => {
            if (activeHot) activeHot.render();
          }, 300);
        } else if (overlay) {
          overlay.classList.add('active');
        }
      }

      // Extraer datos
      activeConsecutivo = rowData.Consecutivo || rowData.id || 'N/A';
      const actividad = rowData.Actividad || rowData.nombre || 'Tarea sin nombre';
      const subcontratista = rowData.Subcontratista || rowData.responsable || 'Sin Asignar';
      const restriccion = rowData.Restriccion || rowData.causa_no_cumplimiento || 'Ninguna';

      const isCritical = rowData.Ruta_Critica === 1 || rowData.ruta_critica === 1 || rowData.prioridad === 'P1' || rowData.p1 === 1;

      // Evaluar crisis activa para la Sidebar / FAB
      const hasRestriccion = restriccion && restriccion !== 'Ninguna' && restriccion.trim() !== '';
      const isCrisis = parseInt(rowData.alerta_crisis, 10) === 1 || rowData.alerta_crisis === true || (isCritical && hasRestriccion);
      
      const sidebarTrigger = document.getElementById('lps_sidebar_trigger');
      const sidebarBadge = document.getElementById('lps_sidebar_badge');

      if (sidebarTrigger) {
        if (isCrisis) {
          sidebarTrigger.classList.add('has-crisis');
          if (sidebarBadge) {
            sidebarBadge.style.display = 'flex';
          }
        } else {
          sidebarTrigger.classList.remove('has-crisis');
          if (sidebarBadge) {
            sidebarBadge.style.display = 'none';
          }
        }
      }

      // Renderizar datos de cabecera
      const titleEl = document.getElementById('lps_actividad_title');
      const consecEl = document.getElementById('lps_consecutivo');
      const priorityBadge = document.getElementById('lps_badge_priority');
      const diagCard = document.getElementById('lps_diagnostic_card');
      const descEl = document.getElementById('lps_diagnostico_desc');
      const rolBadge = document.getElementById('lps_badge_rol');

      if (titleEl) titleEl.innerHTML = actividad;
      if (consecEl) consecEl.textContent = `Actividad #${activeConsecutivo}`;
      
      // Siguiente rol en la jerarquía jerárquica AIA
      const rolesNombres = { 1: 'Residente', 2: 'Director', 3: 'Coordinador de Integración', 4: 'Gerente de Construcción', 5: 'Gerente General' };
      const nivelActual = parseInt(rowData.nivel_actual || 1, 10);
      const siguienteNivel = Math.min(nivelActual + 1, 5);
      if (rolBadge) {
        rolBadge.textContent = `Escalamiento: Superior Inmediato (${rolesNombres[siguienteNivel]})`;
        rolBadge.className = `lps-badge lps-badge-level-${siguienteNivel}`;
      }

      if (priorityBadge) {
        priorityBadge.className = 'lps-badge';
        if (isCritical) {
          priorityBadge.classList.add('lps-badge-p1');
          priorityBadge.textContent = 'Ruta Crítica P1';
          if (diagCard) {
            diagCard.className = 'lps-card-glass lps-state-p1';
          }
          if (descEl) {
            descEl.textContent = `🚨 FRENTE EN CRISIS. Actividad de Ruta Crítica P1 con restricción activa: [${restriccion}]. Se requiere de manera imperativa coordinar la mitigación. El retraso acumulado impactará el cronograma general.`;
          }
        } else {
          priorityBadge.classList.add('lps-badge-p3');
          priorityBadge.textContent = 'Seguimiento P3';
          if (diagCard) {
            diagCard.className = 'lps-card-glass lps-state-p3';
          }
          if (descEl) {
            descEl.textContent = `🟢 SEGUIMIENTO RUTINARIO. Actividad P3. No posee holgura cero. Mantener el ritmo operativo estándar. Sin alertas de crisis activadas.`;
          }
        }
      }

      // 2. Calcular y actualizar Termómetro ITR en vivo
      const itr = calculateITR(rowData);
      updateITRVisuals(itr);

      // 3. Cargar comentarios e hilos Slack-style
      loadCommentsAndCrisis();

      // Previsualizar texto de escalamiento individual si simulación está activa
      const simulated = localStorage.getItem('lps_simulated_mode') === 'true';
      const simCard = document.getElementById('lps_sim_clipboard_card');
      const simPreview = document.getElementById('lps_alert_text_preview');
      if (simCard && simPreview) {
        if (simulated && isCritical) {
          simCard.style.display = 'block';
          const rolSuperior = rolesNombres[siguienteNivel];
          simPreview.textContent = `🚨 [ALERTA SOS - CRISIS AIA] 🚨\nEstimado superior en calidad de ${rolSuperior}, se notifica bloqueo crítico en la obra.\n• Actividad: #${activeConsecutivo} - ${actividad}\n• Restricción/Causa: ${restriccion}`;
        } else {
          simCard.style.display = 'none';
        }
      }

      // Reiniciar reply state
      activeParentId = null;
      const indicator = document.getElementById('lps_thread_replying_indicator');
      if (indicator) indicator.style.display = 'none';
    }
  };
})();
