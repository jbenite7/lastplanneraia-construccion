<div class="ds-candidate-grid ds-state-comparison">
    <article class="ds-state-candidate" data-state-candidate="tinted-status">
        <header><h3>Estados con fondo tenue</h3><p>El significado conserva texto explícito y el color actúa como refuerzo.</p></header>
        <div class="ds-state-set">
            <?= \App\View\Components\DesignSystemComponent::status(['label' => 'A tiempo', 'tone' => 'success', 'severity' => 'low', 'urgency' => 'none']) ?>
            <?= \App\View\Components\DesignSystemComponent::status(['label' => 'Por comprometer', 'tone' => 'warning', 'severity' => 'medium', 'urgency' => 'soon']) ?>
            <?= \App\View\Components\DesignSystemComponent::status(['label' => 'Pendiente de aprobación del responsable', 'tone' => 'warning', 'severity' => 'medium', 'urgency' => 'soon']) ?>
            <?= \App\View\Components\DesignSystemComponent::status(['label' => 'Bloqueado', 'tone' => 'critical', 'severity' => 'high', 'urgency' => 'now']) ?>
            <?= \App\View\Components\DesignSystemComponent::feedback(['message' => 'Actividad guardada', 'tone' => 'success', 'severity' => 'low', 'urgency' => 'none']) ?>
            <?= \App\View\Components\DesignSystemComponent::feedback(['message' => 'No se pudo guardar', 'tone' => 'critical', 'severity' => 'high', 'urgency' => 'now']) ?>
            <?= \App\View\Components\DesignSystemComponent::progress([
                'label' => 'Importando actividades', 'value' => 60,
            ]) ?>
            <?= \App\View\Components\DesignSystemComponent::liveRegion([
                'message' => 'Cambios guardados', 'priority' => 'polite',
            ]) ?>
            <div class="aia-feedback aia-feedback--info" data-ui-group="loading-spinner" role="status" aria-live="polite">
                <span class="aia-spinner" aria-hidden="true"></span>
                <span>Carga indeterminada</span>
            </div>
        </div>
    </article>
</div>
<section class="ds-state-semantics" data-state-semantics aria-labelledby="state-semantics-title">
    <h4 id="state-semantics-title">Mapa de gravedad y urgencia</h4>
    <p class="aia-helper">El nombre cambia por módulo; el nivel de acción y el color permanecen compartidos.</p>
    <?php foreach ($stateSemantics['levels'] as $level): ?>
        <article class="ds-state-semantics__level" data-aia-severity="<?= htmlspecialchars($level['severity'], ENT_QUOTES, 'UTF-8') ?>" data-aia-urgency="<?= htmlspecialchars($level['urgency'], ENT_QUOTES, 'UTF-8') ?>">
            <header><strong><?= htmlspecialchars($level['label'], ENT_QUOTES, 'UTF-8') ?></strong><span class="aia-chip aia-chip--<?= htmlspecialchars($level['token'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($level['token'], ENT_QUOTES, 'UTF-8') ?></span></header>
            <p><?= htmlspecialchars($level['action'], ENT_QUOTES, 'UTF-8') ?></p>
            <small><?= htmlspecialchars(implode(' · ', $level['examples']), ENT_QUOTES, 'UTF-8') ?></small>
        </article>
    <?php endforeach; ?>
</section>
<?php
$levelsById = [];
foreach ($stateSemantics['levels'] as $level) {
    $levelsById[$level['id']] = $level;
}
$intermediateStates = [];
foreach ($stateSemantics['moduleMappings'] as $mapping) {
    if ($mapping['module'] === 'programacion-intermedia') {
        $intermediateStates = $mapping['states'];
        break;
    }
}
?>
<section class="ds-state-module" data-state-module="programacion-intermedia" aria-labelledby="intermediate-states-title">
    <header>
        <p class="ds-lab__eyebrow">Referencia operativa</p>
        <h4 id="intermediate-states-title">Programación Intermedia · 8 estados</h4>
        <p class="aia-helper">Cada etiqueta conserva su nombre operativo; el color comunica la prioridad de acción compartida.</p>
    </header>
    <div class="ds-state-module__grid">
        <?php foreach ($intermediateStates as $state): ?>
            <?php $level = $levelsById[$state['level']]; ?>
            <article class="ds-state-module__state" data-aia-severity="<?= htmlspecialchars($level['severity'], ENT_QUOTES, 'UTF-8') ?>" data-aia-urgency="<?= htmlspecialchars($level['urgency'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="aia-chip aia-chip--<?= htmlspecialchars($level['token'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($state['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <small><?= htmlspecialchars($level['action'], ENT_QUOTES, 'UTF-8') ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<p class="aia-helper">Los textos envuelven entre palabras; una palabra nunca se fragmenta.</p>
