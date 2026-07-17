<?php
$familyGroups = array_values(array_filter(
    $uiGroups,
    static fn(array $group): bool => $group['family'] === $family['id']
));
?>
<details class="ds-ui-index">
    <summary>Inventario de esta familia · <?= count($familyGroups) ?> grupos</summary>
    <ul>
        <?php foreach ($familyGroups as $group): ?>
            <li data-ui-group="<?= htmlspecialchars($group['id'], ENT_QUOTES, 'UTF-8') ?>">
                <strong><?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars(implode(' · ', $group['styleApi']), ENT_QUOTES, 'UTF-8') ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</details>
