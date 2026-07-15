<?php
$actionThemeCandidate = $activeCandidateId === 'theme-adaptive-primary';
$actionTitle = $actionThemeCandidate ? 'Primaria corporativa por tema' : 'Grupo integrado al canvas';
$actionDescription = $actionThemeCandidate
    ? 'El tema aplica su verde corporativo al instante; sombra y desplazamiento conservan la microinteracción.'
    : 'La jerarquía vive en el flujo de la página, sin una superficie adicional.';
?>
<div class="ds-candidate-grid ds-actions-comparison">
    <article class="ds-action-candidate" data-action-candidate="<?= htmlspecialchars($activeCandidateId, ENT_QUOTES, 'UTF-8') ?>" data-action-pattern="solid-outline">
        <header><p class="ds-lab__eyebrow"><?= htmlspecialchars($familyCandidateEyebrow, ENT_QUOTES, 'UTF-8') ?></p><h3><?= htmlspecialchars($actionTitle, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($actionDescription, ENT_QUOTES, 'UTF-8') ?></p></header>
        <?= \App\View\Components\DesignSystemComponent::actionGroup([
            'label' => 'Acciones del formulario',
            'actions' => [
                ['label' => 'Guardar cambios', 'variant' => 'primary'],
                ['label' => 'Cancelar', 'variant' => 'secondary'],
                ['label' => 'Guardando…', 'variant' => 'primary', 'state' => 'loading'],
                ['label' => 'No disponible', 'variant' => 'secondary', 'state' => 'disabled'],
            ],
        ]) ?>
        <div class="ds-primitive-row" aria-label="Iconos canónicos">
            <button class="aia-btn aia-btn--critical" type="button">Eliminar</button>
            <button class="aia-btn aia-btn--icon" type="button" aria-label="Filtrar">⌕</button>
            <button class="aia-btn aia-btn--floating" type="button" aria-label="Abrir ayuda">?</button>
            <span><?= \App\View\Components\DesignSystemComponent::icon([
                'name' => 'filter', 'decorative' => true,
            ]) ?> Icono decorativo junto a texto</span>
            <?= \App\View\Components\DesignSystemComponent::icon([
                'name' => 'warning', 'label' => 'Advertencia',
            ]) ?>
        </div>
    </article>
</div>
