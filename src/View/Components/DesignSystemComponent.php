<?php

namespace App\View\Components;

use InvalidArgumentException;

final class DesignSystemComponent
{
    public static function icon(array $config): string
    {
        $name = self::id($config['name'] ?? '', 'icon name');
        $attributes = 'aria-hidden="true"';
        if (($config['decorative'] ?? false) !== true) {
            $label = self::text($config['label'] ?? '', 'icon label');
            $attributes = 'role="img" aria-label="' . self::escape($label) . '"';
        }
        return '<span class="aia-icon aia-icon--' . self::escape($name)
            . '" data-aia-component="icon" ' . $attributes . '></span>';
    }

    public static function search(array $config): string
    {
        $id = self::id($config['id'] ?? '', 'search id');
        $label = self::text($config['label'] ?? '', 'search label');
        $action = self::text($config['actionLabel'] ?? '', 'search action label');
        $value = is_string($config['value'] ?? '') ? $config['value'] : '';
        return '<form class="aia-search" data-aia-component="search" role="search"><label for="'
            . self::escape($id) . '">' . self::escape($label) . '</label><div><input class="aia-input" id="'
            . self::escape($id) . '" type="search" value="' . self::escape($value)
            . '"><button class="aia-btn" type="submit">' . self::escape($action) . '</button></div></form>';
    }

    public static function pagination(array $config): string
    {
        $label = self::text($config['label'] ?? '', 'pagination label');
        $current = self::positiveInteger($config['current'] ?? null, 'current page');
        $total = self::positiveInteger($config['total'] ?? null, 'total pages');
        if ($current > $total || ($config['hrefPattern'] ?? '') !== '#page-%d') {
            throw new InvalidArgumentException('invalid pagination range or href pattern');
        }
        $items = [];
        for ($page = 1; $page <= $total; $page++) {
            $currentAttribute = $page === $current ? ' aria-current="page"' : '';
            $items[] = '<a href="#page-' . $page . '" data-page="' . $page . '"'
                . $currentAttribute . ' aria-label="Página ' . $page . '">' . $page . '</a>';
        }
        return '<nav class="aia-pagination" data-aia-component="pagination" aria-label="'
            . self::escape($label) . '">' . implode('', $items) . '</nav>';
    }

    public static function progress(array $config): string
    {
        $label = self::text($config['label'] ?? '', 'progress label');
        $value = self::percentage($config['value'] ?? null);
        return '<div class="aia-progress" data-aia-component="progress"><label>'
            . self::escape($label) . '<progress value="' . $value . '" max="100">'
            . $value . ' %</progress></label><span>' . $value . ' %</span></div>';
    }

    public static function liveRegion(array $config): string
    {
        $message = self::text($config['message'] ?? '', 'live region message');
        $priority = $config['priority'] ?? 'polite';
        if (!in_array($priority, ['polite', 'assertive'], true)) {
            throw new InvalidArgumentException('invalid live region priority');
        }
        $role = $priority === 'assertive' ? 'alert' : 'status';
        return '<div class="aia-live-region" data-aia-component="live-region" role="' . $role
            . '" aria-live="' . $priority . '" aria-atomic="true">' . self::escape($message) . '</div>';
    }

    public static function menu(array $config): string
    {
        $id = self::id($config['id'] ?? '', 'menu id');
        $label = self::text($config['label'] ?? '', 'menu label');
        $items = self::menuItems($config['items'] ?? null);
        return '<div class="aia-menu" data-aia-component="menu"><button class="aia-btn aia-btn--secondary"'
            . ' type="button" data-aia-menu-trigger aria-controls="' . self::escape($id)
            . '" aria-expanded="false">' . self::escape($label) . '</button><div id="'
            . self::escape($id) . '" data-aia-menu-panel role="menu" hidden>' . $items . '</div></div>';
    }

    public static function popover(array $config): string
    {
        $id = self::id($config['id'] ?? '', 'popover id');
        $label = self::text($config['label'] ?? '', 'popover label');
        $content = self::text($config['content'] ?? '', 'popover content');
        return '<div class="aia-popover" data-aia-component="popover"><button class="aia-btn aia-btn--secondary"'
            . ' type="button" data-aia-popover-trigger aria-controls="' . self::escape($id)
            . '" aria-expanded="false">' . self::escape($label) . '</button><div id="'
            . self::escape($id) . '" data-aia-popover-panel role="region" hidden>'
            . self::escape($content) . '</div></div>';
    }

    public static function biFigure(array $config): string
    {
        $id = self::id($config['id'] ?? 'aia-bi', 'BI figure id');
        $title = self::text($config['title'] ?? '', 'BI title');
        $summary = self::text($config['summary'] ?? '', 'BI summary');
        $rows = $config['rows'] ?? null;
        if (!is_array($rows) || $rows === []) {
            throw new InvalidArgumentException('BI rows must be a non-empty array');
        }
        $bars = [];
        $table = [];
        foreach ($rows as $index => $row) {
            $normalized = self::biRow($row);
            $bars[] = self::biBarsMarkup($normalized, $index);
            $table[] = self::biTableRowMarkup($normalized);
        }
        return self::biFigureMarkup($id, $title, $summary, implode('', $bars), implode('', $table), count($rows));
    }

    public static function dialog(array $config): string
    {
        $id = self::id($config['id'] ?? 'aia-dialog', 'dialog id');
        $title = self::text($config['title'] ?? '', 'dialog title');
        $description = self::text($config['description'] ?? '', 'dialog description');
        $open = self::text($config['openLabel'] ?? '', 'dialog open label');
        $close = self::text($config['closeLabel'] ?? '', 'dialog close label');
        return '<div class="aia-dialog" data-aia-component="dialog">'
            . '<button class="aia-btn" type="button" data-aia-dialog-open aria-controls="'
            . self::escape($id) . '">' . self::escape($open) . '</button><dialog id="'
            . self::escape($id) . '" class="aia-modal-surface" data-aia-dialog aria-labelledby="'
            . self::escape($id . '-title') . '" aria-describedby="' . self::escape($id . '-description')
            . '"><h3 id="' . self::escape($id . '-title') . '">' . self::escape($title)
            . '</h3><p id="' . self::escape($id . '-description') . '">' . self::escape($description)
            . '</p><button class="aia-btn" type="button" data-aia-dialog-close>'
            . self::escape($close) . '</button></dialog></div>';
    }

    public static function dataDisplay(array $config): string
    {
        $label = self::text($config['label'] ?? '', 'data display label');
        $records = $config['records'] ?? null;
        if (!is_array($records) || $records === []) {
            throw new InvalidArgumentException('records must be a non-empty array');
        }
        $rows = [];
        $cards = [];
        foreach ($records as $record) {
            $normalized = self::dataRecord($record);
            $rows[] = self::dataRowMarkup($normalized);
            $cards[] = self::dataCardMarkup($normalized);
        }
        return self::dataDisplayMarkup($label, implode('', $rows), implode('', $cards));
    }

    public static function status(array $config): string
    {
        $label = self::text($config['label'] ?? '', 'status label');
        $tone = self::tone($config['tone'] ?? 'info');
        $semantic = self::semanticAttributes($config);
        return '<span class="aia-chip aia-chip--' . self::escape($tone)
            . '" data-aia-component="status"' . $semantic . ' data-state-text>' . self::escape($label) . '</span>';
    }

    public static function feedback(array $config): string
    {
        $message = self::text($config['message'] ?? '', 'feedback message');
        $tone = self::tone($config['tone'] ?? 'info');
        $role = $tone === 'critical' ? 'alert' : 'status';
        return '<div class="aia-feedback aia-feedback--' . self::escape($tone)
            . '" data-aia-component="feedback"' . self::semanticAttributes($config) . ' role="' . $role . '" data-state-text>'
            . self::escape($message) . '</div>';
    }

    public static function filterForm(array $config): string
    {
        $id = self::id($config['id'] ?? 'aia-filters', 'filter form id');
        $label = self::text($config['label'] ?? '', 'filter form label');
        $fields = $config['fields'] ?? null;
        if (!is_array($fields) || $fields === []) {
            throw new InvalidArgumentException('fields must be a non-empty array');
        }
        $markup = [];
        $seen = [];
        foreach ($fields as $field) {
            $markup[] = self::filterFieldMarkup($id, $field, $seen);
        }
        $actions = self::actionGroup([
            'label' => 'Acciones de filtros', 'actions' => $config['actions'] ?? null,
        ]);
        return '<form class="aia-filter-form" data-aia-component="filter-form" aria-label="'
            . self::escape($label) . '">' . implode('', $markup) . $actions . '</form>';
    }

    public static function actionGroup(array $config): string
    {
        $label = self::text($config['label'] ?? '', 'action group label');
        $actions = $config['actions'] ?? null;
        if (!is_array($actions) || $actions === []) {
            throw new InvalidArgumentException('actions must be a non-empty array');
        }
        $buttons = [];
        foreach ($actions as $action) {
            if (!is_array($action)) throw new InvalidArgumentException('each action must be an array');
            $actionLabel = self::text($action['label'] ?? '', 'action label');
            $variant = $action['variant'] ?? 'primary';
            if (!in_array($variant, ['primary', 'secondary'], true)) {
                throw new InvalidArgumentException('invalid action variant');
            }
            $state = $action['state'] ?? 'normal';
            if (!in_array($state, ['normal', 'loading', 'disabled'], true)) {
                throw new InvalidArgumentException('invalid action state');
            }
            $class = $variant === 'secondary' ? ' aia-btn--secondary' : '';
            $busy = $state === 'loading' ? ' aria-busy="true"' : '';
            $disabled = $state === 'normal' ? '' : ' disabled';
            $buttons[] = '<button class="aia-btn' . $class . '" type="button"'
                . $busy . $disabled . '>' . self::escape($actionLabel) . '</button>';
        }
        return '<div class="aia-action-group" data-aia-component="action-group" role="group"'
            . ' aria-label="' . self::escape($label) . '">' . implode('', $buttons) . '</div>';
    }

    public static function pageHeader(array $config): string
    {
        $id = self::id($config['id'] ?? 'aia-page', 'page header id');
        $title = self::text($config['title'] ?? '', 'page title');
        $context = self::text($config['context'] ?? '', 'page context');
        $level = $config['headingLevel'] ?? 1;
        if (!is_int($level) || $level < 1 || $level > 6) {
            throw new InvalidArgumentException('headingLevel must be between 1 and 6');
        }
        $items = $config['breadcrumb'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new InvalidArgumentException('breadcrumb must be a non-empty array');
        }
        return self::pageHeaderMarkup($id, $title, $context, $level, $items);
    }

    public static function navigation(array $config): string
    {
        $id = self::id($config['id'] ?? 'aia-navigation', 'navigation id');
        $brand = self::text($config['brand'] ?? '', 'brand');
        $context = self::text($config['context'] ?? '', 'context');
        $active = (string) ($config['active'] ?? '');
        $destinations = $config['destinations'] ?? null;
        if (!is_array($destinations) || $destinations === []) {
            throw new InvalidArgumentException('destinations must be a non-empty array');
        }
        $seen = [];
        $links = [];
        foreach ($destinations as $destination) {
            if (!is_array($destination)) {
                throw new InvalidArgumentException('each destination must be an array');
            }
            $destinationId = self::id($destination['id'] ?? '', 'destination id');
            if (isset($seen[$destinationId])) {
                throw new InvalidArgumentException("duplicate destination id: {$destinationId}");
            }
            $seen[$destinationId] = true;
            $label = self::text($destination['label'] ?? '', 'destination label');
            $href = self::href($destination['href'] ?? '');
            $current = $destinationId === $active ? ' aria-current="page"' : '';
            $links[] = self::link($destinationId, $label, $href, $current);
        }
        if ($active !== '' && !isset($seen[$active])) {
            throw new InvalidArgumentException("unknown active destination: {$active}");
        }

        return self::navigationMarkup($id, $brand, $context, implode('', $links));
    }

    private static function link(string $id, string $label, string $href, string $current): string
    {
        return '<a href="' . self::escape($href) . '" data-shell-destination'
            . ' data-destination-id="' . self::escape($id) . '"' . $current . '>'
            . self::escape($label) . '</a>';
    }

    private static function navigationMarkup(string $id, string $brand, string $context, string $links): string
    {
        $panelId = $id . '-links';
        return '<div class="aia-navigation" data-aia-component="navigation" data-shell-pattern="adaptive">'
            . '<div class="aia-navigation__global"><span class="aia-navigation__brand aia-brand-lockup">'
            . '<img src="/public/img/aia-last-planner-mark.svg" alt="" aria-hidden="true">'
            . '<strong>' . self::escape($brand) . '</strong></span>'
            . '<span class="aia-navigation__context">' . self::escape($context) . '</span><button type="button" class="aia-btn aia-btn--secondary"'
            . ' data-shell-drawer-toggle aria-controls="' . self::escape($panelId) . '" aria-expanded="false">Menú</button></div>'
            . '<nav id="' . self::escape($panelId) . '" class="aia-navigation__links" data-shell-drawer-panel'
            . ' data-shell-presentation="drawer" aria-label="Navegación del proyecto" hidden>' . $links . '</nav></div>';
    }

    private static function pageHeaderMarkup(
        string $id,
        string $title,
        string $context,
        int $level,
        array $items
    ): string {
        $titleId = $id . '-title';
        return '<header class="aia-page-header" data-aia-component="page-header"'
            . ' aria-labelledby="' . self::escape($titleId) . '">'
            . self::breadcrumbMarkup($items)
            . "<h{$level} id=\"" . self::escape($titleId) . '">' . self::escape($title) . "</h{$level}>"
            . '<p class="aia-page-header__context">' . self::escape($context) . '</p></header>';
    }

    private static function breadcrumbMarkup(array $items): string
    {
        $crumbs = [];
        $lastIndex = count($items) - 1;
        foreach ($items as $index => $item) {
            if (!is_array($item)) throw new InvalidArgumentException('each breadcrumb must be an array');
            $label = self::text($item['label'] ?? '', 'breadcrumb label');
            $href = array_key_exists('href', $item) ? self::href($item['href']) : null;
            if ($index < $lastIndex && $href === null) {
                throw new InvalidArgumentException('non-current breadcrumb requires href');
            }
            $content = $index === $lastIndex
                ? '<span aria-current="page">' . self::escape($label) . '</span>'
                : '<a href="' . self::escape($href) . '">' . self::escape($label) . '</a>';
            $crumbs[] = '<li>' . $content . '</li>';
        }
        return '<nav class="aia-page-header__breadcrumb" aria-label="Miga de pan"><ol>'
            . implode('', $crumbs) . '</ol></nav>';
    }

    private static function biRow(mixed $row): array
    {
        if (!is_array($row)) throw new InvalidArgumentException('each BI row must be an array');
        return [
            'label' => self::text($row['label'] ?? '', 'BI row label'),
            'plan' => self::percentage($row['plan'] ?? null),
            'executed' => self::percentage($row['executed'] ?? null),
        ];
    }

    private static function biBarsMarkup(array $row, int $index): string
    {
        $x = $index * 30;
        return '<g><rect class="aia-bi__bar aia-bi__bar--plan" x="' . ($x + 2)
            . '" y="' . (100 - $row['plan']) . '" width="9" height="' . $row['plan']
            . '" data-bi-point="' . $row['plan'] . '"></rect><rect class="aia-bi__bar aia-bi__bar--executed" x="'
            . ($x + 13) . '" y="' . (100 - $row['executed']) . '" width="9" height="'
            . $row['executed'] . '" data-bi-point="' . $row['executed'] . '"></rect></g>';
    }

    private static function biTableRowMarkup(array $row): string
    {
        return '<tr data-bi-row="' . $row['plan'] . ',' . $row['executed'] . '"><th>'
            . self::escape($row['label']) . '</th><td>' . $row['plan'] . ' %</td><td>'
            . $row['executed'] . ' %</td></tr>';
    }

    private static function biFigureMarkup(
        string $id, string $title, string $summary, string $bars, string $rows, int $count
    ): string {
        $width = $count * 30;
        return '<figure class="aia-card aia-bi" data-aia-component="bi-figure" data-bi-figure aria-labelledby="'
            . self::escape($id . '-title') . '" aria-describedby="' . self::escape($id . '-summary')
            . '"><figcaption><h3 id="' . self::escape($id . '-title') . '">' . self::escape($title)
            . '</h3><p id="' . self::escape($id . '-summary') . '">' . self::escape($summary)
            . '</p></figcaption>' . self::biLegendMarkup() . '<svg class="aia-bi__plot" viewBox="0 0 '
            . $width . ' 100" preserveAspectRatio="none" data-bi-plot aria-hidden="true">' . $bars
            . '</svg>' . self::biTableMarkup($rows) . '</figure>';
    }

    private static function biLegendMarkup(): string
    {
        return '<ul class="aia-bi__legend" aria-label="Series"><li><span class="aia-bi__legend-mark'
            . ' aia-bi__legend-mark--plan" aria-hidden="true"></span>Plan</li><li><span class="aia-bi__legend-mark'
            . ' aia-bi__legend-mark--executed" aria-hidden="true"></span>Ejecutado</li></ul>';
    }

    private static function biTableMarkup(string $rows): string
    {
        return '<details class="aia-bi__data" open><summary>Datos del gráfico</summary><table><caption'
            . ' class="aia-visually-hidden">Plan y avance ejecutado por periodo</caption><thead><tr><th>Periodo</th>'
            . '<th>Plan</th><th>Ejecutado</th></tr></thead><tbody>' . $rows . '</tbody></table></details>';
    }

    private static function dataRecord(mixed $record): array
    {
        if (!is_array($record)) throw new InvalidArgumentException('each record must be an array');
        $id = is_string($record['id'] ?? null) ? trim($record['id']) : '';
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $id)) {
            throw new InvalidArgumentException('invalid record id');
        }
        return [
            'id' => $id,
            'title' => self::text($record['title'] ?? '', 'record title'),
            'status' => self::text($record['status'] ?? '', 'record status'),
            'tone' => self::tone($record['tone'] ?? ''),
            'progress' => self::text($record['progress'] ?? '', 'record progress'),
        ];
    }

    private static function dataRowMarkup(array $record): string
    {
        return '<tr data-record-id="' . self::escape($record['id']) . '"><td>'
            . self::escape($record['title']) . '</td><td>'
            . self::status(['label' => $record['status'], 'tone' => $record['tone']])
            . '</td><td>' . self::escape($record['progress']) . '</td></tr>';
    }

    private static function dataCardMarkup(array $record): string
    {
        return '<article class="aia-card aia-data-card" data-record-id="' . self::escape($record['id'])
            . '"><header><span>' . self::escape($record['id']) . '</span>'
            . self::status(['label' => $record['status'], 'tone' => $record['tone']])
            . '</header><h3>' . self::escape($record['title'])
            . '</h3><p><strong>Avance:</strong> ' . self::escape($record['progress']) . '</p></article>';
    }

    private static function dataDisplayMarkup(string $label, string $rows, string $cards): string
    {
        $escaped = self::escape($label);
        return '<div class="aia-data-display" data-aia-component="data-display">'
            . '<div class="aia-table-shell aia-data-display__table" data-display-mode="table" role="region"'
            . ' aria-label="' . $escaped . ' en tabla"><table><thead><tr><th>Actividad</th><th>Estado</th>'
            . '<th>Avance</th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
            . '<div class="aia-data-display__cards" data-display-mode="cards" role="region"'
            . ' aria-label="' . $escaped . ' en tarjetas">' . $cards . '</div></div>';
    }

    private static function filterFieldMarkup(string $formId, mixed $field, array &$seen): string
    {
        if (!is_array($field)) throw new InvalidArgumentException('each field must be an array');
        $id = self::id($field['id'] ?? '', 'filter field id');
        if (isset($seen[$id])) throw new InvalidArgumentException("duplicate filter field id: {$id}");
        $seen[$id] = true;
        $label = self::text($field['label'] ?? '', 'filter field label');
        $type = $field['type'] ?? '';
        $controlId = $formId . '-' . $id;
        if ($type === 'search') {
            $value = is_string($field['value'] ?? '') ? $field['value'] : '';
            $control = '<input class="aia-input" data-filter-field="' . self::escape($id)
                . '" id="' . self::escape($controlId) . '" type="search" value="' . self::escape($value) . '">';
        } elseif ($type === 'select') {
            $control = self::filterSelectMarkup($controlId, $id, $field);
        } else {
            throw new InvalidArgumentException('invalid filter field type');
        }
        return '<label class="aia-label" for="' . self::escape($controlId) . '">'
            . self::escape($label) . $control . '</label>';
    }

    private static function filterSelectMarkup(string $controlId, string $id, array $field): string
    {
        $options = $field['options'] ?? null;
        if (!is_array($options) || $options === []) {
            throw new InvalidArgumentException('select options must be a non-empty array');
        }
        $selected = (string) ($field['value'] ?? '');
        $found = false;
        $markup = [];
        foreach ($options as $option) {
            if (!is_array($option)) throw new InvalidArgumentException('each option must be an array');
            $value = self::text($option['value'] ?? '', 'option value');
            $label = self::text($option['label'] ?? '', 'option label');
            $isSelected = $value === $selected;
            $found = $found || $isSelected;
            $markup[] = '<option value="' . self::escape($value) . '"'
                . ($isSelected ? ' selected' : '') . '>' . self::escape($label) . '</option>';
        }
        if ($selected !== '' && !$found) throw new InvalidArgumentException('unknown selected option');
        return '<select class="aia-select" data-filter-field="' . self::escape($id)
            . '" id="' . self::escape($controlId) . '">' . implode('', $markup) . '</select>';
    }

    private static function menuItems(mixed $items): string
    {
        if (!is_array($items) || $items === []) {
            throw new InvalidArgumentException('menu items must be a non-empty array');
        }
        $markup = [];
        foreach ($items as $item) {
            if (!is_array($item)) throw new InvalidArgumentException('each menu item must be an array');
            $label = self::text($item['label'] ?? '', 'menu item label');
            $markup[] = '<button type="button" role="menuitem">' . self::escape($label) . '</button>';
        }
        return implode('', $markup);
    }

    private static function positiveInteger(mixed $value, string $field): int
    {
        if (!is_int($value) || $value < 1) {
            throw new InvalidArgumentException("{$field} must be a positive integer");
        }
        return $value;
    }

    private static function id(mixed $value, string $field): string
    {
        $value = is_string($value) ? trim($value) : '';
        if (!preg_match('/^[a-z][a-z0-9-]*$/', $value)) {
            throw new InvalidArgumentException("invalid {$field}");
        }
        return $value;
    }

    private static function text(mixed $value, string $field): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            throw new InvalidArgumentException("{$field} is required");
        }
        return $value;
    }

    private static function tone(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if (!in_array($value, ['info', 'success', 'warning', 'critical'], true)) {
            throw new InvalidArgumentException('invalid semantic tone');
        }
        return $value;
    }

    private static function semanticAttributes(array $config): string
    {
        $severity = $config['severity'] ?? null;
        $urgency = $config['urgency'] ?? null;
        if (!is_string($severity) || !is_string($urgency)
            || !in_array($severity, ['none', 'low', 'medium', 'high'], true)
            || !in_array($urgency, ['none', 'soon', 'now'], true)) return '';
        return ' data-aia-severity="' . self::escape($severity)
            . '" data-aia-urgency="' . self::escape($urgency) . '"';
    }

    private static function percentage(mixed $value): int
    {
        if (!is_int($value) || $value < 0 || $value > 100) {
            throw new InvalidArgumentException('BI percentage must be an integer from 0 to 100');
        }
        return $value;
    }

    private static function href(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if (!preg_match('/^(?:\/(?!\/)[^\s]*|#[A-Za-z][A-Za-z0-9_:-]*)$/', $value)) {
            throw new InvalidArgumentException('destination href must be local');
        }
        return $value;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
