<!DOCTYPE html>
<html lang="es" data-aia-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laboratorio interno · Design System AIA</title>
    <?= \App\View\Components\DesignSystemHeadComponent::render(true) ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/design-system/lab.css') ?>
</head>
<body class="aia-shell ds-lab" data-density="touch">
    <header class="ds-lab__header">
        <div><p class="ds-lab__eyebrow">Sprint 00 · 0.3.6</p><h1>Laboratorio del Design System AIA</h1></div>
        <div class="ds-lab__controls" aria-label="Controles del laboratorio">
            <button type="button" class="aia-btn aia-btn--secondary" data-lab-theme>Usar tema dark</button>
            <label class="aia-label" for="lab-family">Familia</label>
            <select class="aia-select" id="lab-family" data-lab-family>
                <?php foreach ($contract['families'] as $family): ?>
                    <option value="<?= htmlspecialchars($family['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($family['label'] ?? $family['id'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label class="aia-label" for="lab-density">Densidad</label>
            <select class="aia-select" id="lab-density" data-lab-density>
                <option value="touch">Touch</option>
                <option value="compact">Compacta</option>
            </select>
        </div>
    </header>
    <main class="aia-page ds-lab__families" id="contenido">
        <?php foreach ($contract['families'] as $family): ?>
            <?php
            $candidates = $family['candidates'] ?? [];
            $activeCandidateId = trim((string) ($family['activeCandidate'] ?? ''));
            $activeCandidate = null;
            foreach ($candidates as $candidate) {
                if (($candidate['id'] ?? '') === $activeCandidateId) {
                    $activeCandidate = $candidate;
                    break;
                }
            }
            if ($activeCandidate === null) {
                foreach ($candidates as $candidate) {
                    if (($candidate['status'] ?? '') === 'approved') {
                        $activeCandidate = $candidate;
                        break;
                    }
                }
            }
            $activeCandidateId = (string) ($activeCandidate['id'] ?? '');
            $approved = ($activeCandidate['status'] ?? 'candidate') === 'approved';
            $familyCandidateEyebrow = $approved ? 'Patrón aprobado' : 'Patrón en revisión';
            ?>
            <section class="aia-panel ds-lab__family" data-family="<?= htmlspecialchars($family['id'], ENT_QUOTES, 'UTF-8') ?>" data-active-candidate="<?= htmlspecialchars($activeCandidateId, ENT_QUOTES, 'UTF-8') ?>" data-family-status="<?= $approved ? 'approved' : 'candidate' ?>">
                <header class="ds-lab__family-head">
                    <h2><?= htmlspecialchars($family['label'] ?? $family['id'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="aia-chip<?= $approved ? ' aia-chip--success' : '' ?>" title="<?= $approved ? 'Familia aprobada' : 'Candidato activo pendiente de aprobación visual' ?>"><?= $approved ? 'Aprobado' : 'En revisión' ?></span>
                </header>
                <p class="ds-lab__review-note">Estado: <?= $approved ? 'familia aprobada y congelada.' : 'candidato activo pendiente de aprobación visual; la base aprobada se conserva como referencia.' ?></p>
                <?php if (!empty($family['description'])): ?>
                    <p class="aia-copy ds-lab__family-description"><?= htmlspecialchars($family['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php require __DIR__ . '/ui-group-index.php'; ?>
                <?php require __DIR__ . '/families/' . basename($family['id']) . '.php'; ?>
            </section>
        <?php endforeach; ?>
    </main>
    <script src="/public/js/modules/aia_ui/theme.js"></script>
    <script src="/public/js/modules/aia_ui/components.js"></script>
    <script src="/public/js/modules/aia_ui/design_system_lab.js"></script>
</body>
</html>
