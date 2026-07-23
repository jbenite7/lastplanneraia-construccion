<!-- views/partials/drawer_unificado.php -->
<button type="button" class="lps-sidebar-trigger" id="lps_sidebar_trigger" aria-label="Abrir Cajón Contextual LPS" aria-controls="lps_drawer" aria-expanded="false">
  <div class="lps-sidebar-content">
    <span class="lps-sidebar-icon" aria-hidden="true">💡</span>
    <span class="lps-sidebar-text">CONCURRENCIA LPS</span>
  </div>
  <div class="lps-sidebar-badge lps-start-hidden" id="lps_sidebar_badge" aria-hidden="true">🔥</div>
</button>

<div class="lps-drawer-overlay" id="lps_drawer_overlay"></div>
<div class="lps-drawer" id="lps_drawer" role="dialog" aria-modal="true" aria-labelledby="lps_drawer_title" aria-hidden="true" inert>
  <!-- Cabecera Premium -->
  <div class="lps-drawer-header">
    <div class="lps-drawer-header__titles">
      <h3 id="lps_drawer_title">Cajón Contextual LPS</h3>
      <span class="lps-badge lps-badge--rol" id="lps_badge_rol"></span>
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
      <div class="lps-diagnostic__head">
        <span class="lps-badge" id="lps_badge_priority">Prioridad</span>
        <span class="lps-text-secondary lps-diagnostic__consecutivo" id="lps_consecutivo">Selecciona una fila</span>
      </div>
      <h4 class="lps-text-primary lps-diagnostic__title" id="lps_actividad_title">Ninguna actividad seleccionada</h4>
      <p class="lps-text-primary lps-diagnostic__desc" id="lps_diagnostico_desc">
        Haz clic en cualquier celda de la planilla para recibir el diagnóstico clínico de la tarea, restricciones abiertas y el plan estratégico recomendado.
      </p>
    </div>

    <!-- 2. Termómetro de Restricciones (Live ITR) -->
    <div class="lps-card-glass lps-start-hidden" id="lps_itr_card">
      <h4 class="lps-card-heading">Termómetro de Restricciones</h4>
      <div class="lps-itr__row">
        <div class="lps-itr__track">
          <div class="lps-itr__bar" id="lps_itr_bar"></div>
        </div>
        <span id="lps_itr_value" class="lps-text-primary lps-itr__value">0%</span>
      </div>
      <p class="lps-text-secondary lps-card-copy" id="lps_itr_details">
        0 de 0 restricciones liberadas.
      </p>
    </div>

    <!-- 3. Escalamiento Express (SOS Whatsapp & Email) -->
    <div class="lps-card-glass lps-start-hidden" id="lps_action_card">
      <h4 class="lps-card-heading">Escalamiento Jerárquico SOS</h4>

      <button class="lps-btn lps-btn-success lps-btn--stacked" id="lps_btn_whatsapp">
        <span class="lps-btn__emoji" aria-hidden="true">🔥</span> Enviar SOS por WhatsApp
      </button>

      <button class="lps-btn lps-btn-outline lps-btn--flush" id="lps_btn_email">
        <span class="lps-btn__emoji" aria-hidden="true">✉️</span> Notificar por Correo
      </button>

      <div id="lps_sim_clipboard_card" class="lps-divided-section lps-start-hidden">
        <span class="lps-caption">Previsualización de Alerta:</span>
        <div class="lps-digest-preview" id="lps_alert_text_preview"></div>
      </div>
    </div>

    <!-- 4. Hilos de Comentarios (Slack-Style) -->
    <div class="lps-card-glass lps-start-hidden" id="lps_comments_card">
      <h4 class="lps-card-heading">Bitácora de Control e Hilos</h4>

      <!-- Listado de comentarios -->
      <div id="lps_comments_container" class="lps-comments-list">
        <!-- Se inyecta dinámicamente -->
      </div>

      <!-- Formulario para agregar comentario o respuesta -->
      <div class="lps-comment-form">
        <div class="lps-comment-form__row">
          <label class="aia-visually-hidden" for="lps_comment_input">Comentario o actualización de la bitácora</label>
          <textarea id="lps_comment_input" class="lps-textarea lps-comment-form__input" placeholder="Escribe una actualización o @menciona un cargo (ej. @D, @OT)..." rows="2"></textarea>
          <button class="lps-btn lps-btn-success lps-comment-form__send" id="lps_btn_send_comment">
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
    <div class="lps-card-glass lps-start-hidden" id="lps_closure_card">
      <h4 class="lps-card-heading lps-card-heading--critical">Cierre y Mitigación de Crisis</h4>
      <p class="lps-text-secondary lps-card-copy">
        Para cerrar formalmente esta alerta de crisis, el usuario debe proveer una justificación técnica detallada (mínimo 100 caracteres).
      </p>

      <label class="aia-visually-hidden" for="lps_closure_justification">Justificación técnica del cierre de la crisis</label>
      <textarea id="lps_closure_justification" class="lps-textarea lps-closure-input" placeholder="Describa el acuerdo, la mitigación implementada en obra y la fecha de normalización (mínimo 100 caracteres)..." rows="3"></textarea>

      <div class="lps-closure-meta">
        <span id="lps_closure_char_count" class="lps-closure-count">0 / 100 caracteres</span>
      </div>

      <button class="lps-btn lps-btn-success lps-btn--flush" id="lps_btn_close_crisis" disabled>
        Firmar Cierre y Mitigación
      </button>
    </div>

    <!-- 6. LPS Weekly Digest (Consolidación Semanal) -->
    <div class="lps-card-glass" id="lps_digest_section">
      <h4 class="lps-card-heading">Weekly Digest (Consolidado)</h4>
      <p class="lps-text-secondary lps-card-copy">
        Recorre la planilla activa, agrupa todas las actividades P1 críticas y genera un reporte unificado de bloqueos para el contratista o director.
      </p>
      <button class="lps-btn lps-btn-outline lps-btn--target lps-btn--flush" id="lps_btn_digest">
        <span class="lps-btn__emoji" aria-hidden="true">📊</span> Compilar Digest de Obra
      </button>

      <div class="lps-digest-card lps-start-hidden" id="lps_digest_result_card">
        <span class="lps-caption">Digest de Bloqueos:</span>
        <div class="lps-digest-preview" id="lps_digest_text_preview"></div>
        <button class="lps-btn lps-btn-success lps-digest-card__copy" id="lps_btn_copy_digest">
          Copiar Digest Completo
        </button>
      </div>
    </div>
  </div>

  <!-- Configuración / Modo Simulación en el Footer -->
  <div class="lps-drawer-footer">
    <div class="lps-sim-toggle-container">
      <label class="lps-sim-label" for="lps_sim_mode_toggle">Modo Simulación (Inactivo)</label>
      <input type="checkbox" id="lps_sim_mode_toggle" class="lps-sim-checkbox" />
    </div>
    <p class="lps-text-secondary lps-footer-note">
      Las notificaciones reales están bloqueadas. Los CTAs copiarán el reporte al portapapeles.
    </p>
  </div>
</div>
