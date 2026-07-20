<!DOCTYPE html>
<html lang="es" data-aia-theme="dark" class="aia-theme-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laboratorio interno · Design System AIA</title>
    <?= \App\View\Components\DesignSystemHeadComponent::renderLaboratory() ?>
</head>
<body class="aia-shell ds-lab" data-density="compact">
    <header class="ds-lab__header">
        <div class="ds-lab__identity">
            <p class="ds-lab__eyebrow">Sistema vivo · Revisión de patrones</p>
            <h1>Laboratorio AIA</h1>
            <p class="ds-lab__lede">Fundamentos, decisiones y componentes listos para validar.</p>
        </div>
        <div class="ds-lab__controls" aria-label="Controles del laboratorio">
            <fieldset class="ds-lab__density" aria-label="Densidad de la muestra">
                <legend class="aia-visually-hidden">Densidad de la muestra</legend>
                <label><input type="radio" name="lab-density" value="compact" data-lab-density checked> Compacta</label>
                <label><input type="radio" name="lab-density" value="touch" data-lab-density> Touch</label>
            </fieldset>
        </div>
    </header>
    <div class="ds-lab__workspace">
        <aside class="ds-lab__rail-wrap">
            <nav class="ds-lab__rail" aria-label="Familias del design system">
                <p class="ds-lab__rail-title">Familias <span><?= count($contract['families']) ?></span></p>
                <ul>
                    <?php foreach ($contract['families'] as $family): ?>
                        <?php
                        $familyId = (string) $family['id'];
                        $groupCount = count(array_filter(
                            $uiGroups,
                            static fn(array $group): bool => $group['family'] === $familyId
                        ));
                        $railCandidates = $family['candidates'] ?? [];
                        $railCandidateId = trim((string) ($family['activeCandidate'] ?? ''));
                        $railCandidate = null;
                        foreach ($railCandidates as $candidate) {
                            if (($candidate['id'] ?? '') === $railCandidateId) {
                                $railCandidate = $candidate;
                                break;
                            }
                        }
                        if ($railCandidate === null) {
                            foreach ($railCandidates as $candidate) {
                                if (($candidate['status'] ?? '') === 'approved') {
                                    $railCandidate = $candidate;
                                    break;
                                }
                            }
                        }
                        $familyApproved = ($railCandidate['status'] ?? 'candidate') === 'approved';
                        ?>
                        <li>
                            <a href="?family=<?= htmlspecialchars($familyId, ENT_QUOTES, 'UTF-8') ?>" data-lab-family-link data-family-target="<?= htmlspecialchars($familyId, ENT_QUOTES, 'UTF-8') ?>"<?= $familyId === $initialFamilyId ? ' aria-current="page"' : '' ?>>
                                <span class="ds-lab__rail-label"><?= htmlspecialchars($family['label'] ?? $familyId, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ds-lab__rail-meta"><?= $groupCount ?> <?= $groupCount === 1 ? 'grupo' : 'grupos' ?> · <?= $familyApproved ? 'Aprobado' : 'En revisión' ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <main class="aia-page ds-lab__families" id="contenido">
            <?php foreach ($contract['families'] as $family): ?>
            <?php
            $familyId = (string) $family['id'];
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
            ?>
            <section class="aia-panel ds-lab__family" data-family="<?= htmlspecialchars($familyId, ENT_QUOTES, 'UTF-8') ?>" data-active-candidate="<?= htmlspecialchars($activeCandidateId, ENT_QUOTES, 'UTF-8') ?>" data-family-status="<?= $approved ? 'approved' : 'candidate' ?>" aria-labelledby="family-<?= htmlspecialchars($familyId, ENT_QUOTES, 'UTF-8') ?>-title"<?= $familyId !== $initialFamilyId ? ' hidden' : '' ?>>
                <header class="ds-lab__family-head">
                    <h2 id="family-<?= htmlspecialchars($familyId, ENT_QUOTES, 'UTF-8') ?>-title" tabindex="-1"><?= htmlspecialchars($family['label'] ?? $familyId, ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="aia-chip<?= $approved ? ' aia-chip--success' : '' ?>" title="<?= $approved ? 'Familia aprobada' : 'Candidato activo pendiente de aprobación visual' ?>"><?= $approved ? 'Aprobado' : 'En revisión' ?></span>
                </header>
                <?php if (!empty($family['description'])): ?>
                    <p class="aia-copy ds-lab__family-description"><?= htmlspecialchars($family['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php require __DIR__ . '/ui-group-index.php'; ?>
                <?php require __DIR__ . '/families/' . basename($family['id']) . '.php'; ?>
                <?php require __DIR__ . '/operational-fixtures.php'; ?>
            </section>
            <?php endforeach; ?>
        </main>
    </div>
    <script src="/public/js/modules/aia_ui/components.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/design_system_lab.js') ?>
</body>
</html>
