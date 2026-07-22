<!-- views/partials/drawer_unificado.php -->
<button type="button" class="lps-sidebar-trigger" id="lps_sidebar_trigger" aria-label="Abrir Cajón Contextual LPS" aria-controls="lps_drawer" aria-expanded="false">
  <div class="lps-sidebar-content">
    <span class="lps-sidebar-icon" aria-hidden="true">💡</span>
    <span class="lps-sidebar-text">CONCURRENCIA LPS</span>
  </div>
  <div class="lps-sidebar-badge" id="lps_sidebar_badge" aria-hidden="true" style="display: none;">🔥</div>
</button>

<div class="lps-drawer-overlay" id="lps_drawer_overlay"></div>
<div class="lps-drawer" id="lps_drawer" role="dialog" aria-modal="true" aria-labelledby="lps_drawer_title" aria-hidden="true" inert>
  <!-- Cabecera Premium -->
  <div class="lps-drawer-header">
    <div style="display: flex; flex-direction: column; gap: 2px;">
      <h3 id="lps_drawer_title">Cajón Contextual LPS</h3>
      <span class="lps-badge" id="lps_badge_rol" style="font-size: 0.7rem; align-self: flex-start; margin-top: 4px;"></span>
    </div>
    <button class="lps-drawer-close" id="lps_drawer_close" aria-label="Cerrar">&times;</button>
  </div>

  <div class="lps-drawer-body">
    <?php if (\App\View\Components\BiAccessComponent::canAccess()): ?>
    <div class="lps-card-glass lps-bi-access-card" id="lps_bi_control_tower_card">
      <h4 class="lps-bi-access-card__title">Control Tower BI</h4>
      <p class="lps-bi-access-card__description">
        Revisa el panel ejecutivo y las vistas BI del proyecto.
      </p>
      <a class="lps-btn lps-btn-outline lps-bi-access-card__link" href="<?php echo htmlspecialchars(\App\View\Components\BiAccessComponent::url('control-tower'), ENT_QUOTES, 'UTF-8'); ?>" data-bi-access-link="control-tower">
        <i class="fas fa-chart-line" aria-hidden="true"></i>
        <span>Abrir Control Tower</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- 1. Tarjeta de Diagnóstico Principal -->
    <div class="lps-card-glass" id="lps_diagnostic_card">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <span class="lps-badge" id="lps_badge_priority">Prioridad</span>
        <span class="lps-text-secondary" style="font-size: 0.8rem; font-weight: 600;" id="lps_consecutivo">Selecciona una fila</span>
      </div>
      <h4 class="lps-text-primary" style="margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 700; line-height: 1.3;" id="lps_actividad_title">Ninguna actividad seleccionada</h4>
      <p class="lps-text-primary" style="margin: 0; font-size: 0.88rem; line-height: 1.45;" id="lps_diagnostico_desc">
        Haz clic en cualquier celda de la planilla para recibir el diagnóstico clínico de la tarea, restricciones abiertas y el plan estratégico recomendado.
      </p>
    </div>

    <!-- 2. Termómetro de Restricciones (Live ITR) -->
    <div class="lps-card-glass" id="lps_itr_card" style="display: none;">
      <h4 class="lps-card-heading">Termómetro de Restricciones</h4>
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="flex: 1; background: #e9ecef; height: 12px; border-radius: 6px; overflow: hidden; position: relative;">
          <div id="lps_itr_bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #dc3545, #ffc107, #198754); transition: width 0.4s ease;"></div>
        </div>
        <span id="lps_itr_value" class="lps-text-primary" style="font-size: 0.95rem; font-weight: 700; min-width: 45px; text-align: right;">0%</span>
      </div>
      <p class="lps-text-secondary" style="margin: 6px 0 0 0; font-size: 0.78rem; line-height: 1.3;" id="lps_itr_details">
        0 de 0 restricciones liberadas.
      </p>
    </div>

    <!-- 3. Escalamiento Express (SOS Whatsapp & Email) -->
    <div class="lps-card-glass" id="lps_action_card" style="display: none;">
      <h4 class="lps-card-heading" style="margin-bottom: 10px;">Escalamiento Jerárquico SOS</h4>

      <button class="lps-btn lps-btn-success" id="lps_btn_whatsapp" style="margin-bottom: 8px;">
        <span style="font-size: 1.1rem; line-height: 1;" aria-hidden="true">🔥</span> Enviar SOS por WhatsApp
      </button>

      <button class="lps-btn lps-btn-outline" id="lps_btn_email" style="margin-bottom: 0;">
        <span style="font-size: 1.1rem; line-height: 1;" aria-hidden="true">✉️</span> Notificar por Correo
      </button>

      <div id="lps_sim_clipboard_card" style="display: none; border-top: 1px dashed var(--ds-active-border); padding-top: 10px; margin-top: 10px;">
        <span class="lps-caption">Previsualización de Alerta:</span>
        <div class="lps-digest-preview" id="lps_alert_text_preview" style="font-size: 0.75rem;"></div>
      </div>
    </div>

    <!-- 4. Hilos de Comentarios (Slack-Style) -->
    <div class="lps-card-glass" id="lps_comments_card" style="display: none;">
      <h4 class="lps-card-heading" style="margin-bottom: 12px;">Bitácora de Control e Hilos</h4>

      <!-- Listado de comentarios -->
      <div id="lps_comments_container" style="display: flex; flex-direction: column; gap: 14px; max-height: 320px; overflow-y: auto; margin-bottom: 12px; padding-right: 4px;">
        <!-- Se inyecta dinámicamente -->
      </div>

      <!-- Formulario para agregar comentario o respuesta -->
      <div style="border-top: 1px solid var(--ds-active-border); padding-top: 12px;">
        <div style="display: flex; align-items: flex-start; gap: 8px;">
          <label class="aia-visually-hidden" for="lps_comment_input">Comentario o actualización de la bitácora</label>
          <textarea id="lps_comment_input" class="lps-textarea" placeholder="Escribe una actualización o @menciona un cargo (ej. @D, @OT)..." rows="2" style="flex: 1; resize: none;"></textarea>
          <button class="lps-btn lps-btn-success" id="lps_btn_send_comment" style="width: auto; min-height: var(--ds-target-min); margin-bottom: 0; padding: 0 14px;">
            Enviar
          </button>
        </div>
        <div id="lps_thread_replying_indicator" class="lps-reply-indicator">
          <span>Respondiendo en hilo...</span>
          <button id="lps_btn_cancel_reply" class="lps-reply-indicator__close" aria-label="Cancelar respuesta en hilo">&times;</button>
        </div>
      </div>
    </div>

    <!-- 5. Mitigación y Cierre de Alerta de Crisis -->
    <div class="lps-card-glass" id="lps_closure_card" style="display: none;">
      <h4 class="lps-card-heading lps-card-heading--critical" style="margin-bottom: 10px;">Cierre y Mitigación de Crisis</h4>
      <p class="lps-text-secondary" style="margin: 0 0 8px 0; font-size: 0.8rem; line-height: 1.35;">
        Para cerrar formalmente esta alerta de crisis, el usuario debe proveer una justificación técnica detallada (mínimo 100 caracteres).
      </p>

      <label class="aia-visually-hidden" for="lps_closure_justification">Justificación técnica del cierre de la crisis</label>
      <textarea id="lps_closure_justification" class="lps-textarea" placeholder="Describa el acuerdo, la mitigación implementada en obra y la fecha de normalización (mínimo 100 caracteres)..." rows="3" style="width: 100%; resize: none; margin-bottom: 8px; box-sizing: border-box;"></textarea>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <span id="lps_closure_char_count" class="lps-closure-count" style="font-size: 0.75rem; font-weight: 600;">0 / 100 caracteres</span>
      </div>

      <button class="lps-btn lps-btn-success" id="lps_btn_close_crisis" style="margin-bottom: 0;" disabled>
        Firmar Cierre y Mitigación
      </button>
    </div>

    <!-- 6. LPS Weekly Digest (Consolidación Semanal) -->
    <div class="lps-card-glass" id="lps_digest_section">
      <h4 class="lps-card-heading" style="margin-bottom: 6px;">Weekly Digest (Consolidado)</h4>
      <p class="lps-text-secondary" style="margin: 0 0 12px 0; font-size: 0.82rem; line-height: 1.35;">
        Recorre la planilla activa, agrupa todas las actividades P1 críticas y genera un reporte unificado de bloqueos para el contratista o director.
      </p>
      <button class="lps-btn lps-btn-outline" style="min-height: var(--ds-target-min); margin-bottom: 0;" id="lps_btn_digest">
        <span style="font-size: 1.1rem; line-height: 1;" aria-hidden="true">📊</span> Compilar Digest de Obra
      </button>

      <div class="lps-digest-card" id="lps_digest_result_card" style="display: none;">
        <span class="lps-caption">Digest de Bloqueos:</span>
        <div class="lps-digest-preview" id="lps_digest_text_preview"></div>
        <button class="lps-btn lps-btn-success" style="min-height: var(--ds-target-min); margin: 10px 0 0 0; font-size: 0.85rem;" id="lps_btn_copy_digest">
          Copiar Digest Completo
        </button>
      </div>
    </div>
  </div>

  <!-- Configuración / Modo Simulación en el Footer -->
  <div class="lps-drawer-footer">
    <div class="lps-sim-toggle-container">
      <label class="lps-sim-label" for="lps_sim_mode_toggle">Modo Simulación (Inactivo)</label>
      <input type="checkbox" id="lps_sim_mode_toggle" style="width: 20px; height: 20px; cursor: pointer; border-radius: 4px;" />
    </div>
    <p class="lps-text-secondary" style="margin: 6px 0 0 0; font-size: 0.72rem; text-align: center; line-height: 1.2;">
      Las notificaciones reales están bloqueadas. Los CTAs copiarán el reporte al portapapeles.
    </p>
  </div>
</div>
