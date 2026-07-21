<?php

namespace App\View\Components;

use InvalidArgumentException;

final class DesignSystemComponent
{
    private const ICON_GLYPHS = [
        'filter' => '<path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.8 9a2.5 2.5 0 1 1 4.7 1.2c0 1.8-2.5 2.1-2.5 3.8"/><path d="M12 17.5h.01"/>',
        'warning' => '<path d="M12 3 2.5 20.5h19L12 3Z"/><path d="M12 9v5"/><path d="M12 17.5h.01"/>',
        'overview' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'integration' => '<circle cx="7" cy="12" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="17" cy="17" r="3"/><path d="m9.5 10.5 5-2M9.5 13.5l5 2"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>',
        'program' => '<path d="M5 5h14v14H5z"/><path d="M8 9h8M8 13h5M8 17h3"/>',
        'tasks' => '<path d="M5 6h14M5 12h14M5 18h14"/><path d="m7 6 .01 0M7 12 .01 0M7 18 .01 0"/>',
        'list' => '<path d="M8 6h12M8 12h12M8 18h12"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'clipboard' => '<path d="M8 5h8a2 2 0 0 1 2 2v13H6V7a2 2 0 0 1 2-2Z"/><path d="M9 5a3 3 0 0 1 6 0M9 11h6M9 15h4"/>',
        'contract' => '<path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h3M9 12h6M9 16h6"/>',
        'chart' => '<path d="M5 20V10M12 20V4M19 20v-7"/><path d="M3 20h18"/>',
        'bell' => '<path d="M6 17h12l-1.5-2.5V10a4.5 4.5 0 0 0-9 0v4.5z"/><path d="M10 20h4"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'project' => '<path d="M4 7h6l2 2h8v10H4z"/><path d="M4 7V5h6l2 2"/>',
        'theme' => '<path d="M20 15.5A8.5 8.5 0 1 1 8.5 4 6.5 6.5 0 0 0 20 15.5Z"/>',
        'logout' => '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
        'collapse' => '<path d="m14 6-6 6 6 6"/>',
    ];

    public static function icon(array $config): string
    {
        $name = self::id($config['name'] ?? '', 'icon name');
        $attributes = 'aria-hidden="true"';
        if (($config['decorative'] ?? false) !== true) {
            $label = self::text($config['label'] ?? '', 'icon label');
            $attributes = 'role="img" aria-label="' . self::escape($label) . '"';
        }
        $glyph = self::ICON_GLYPHS[$name] ?? '<circle cx="12" cy="12" r="7"/>';
        return '<span class="aia-icon aia-icon--' . self::escape($name)
            . '" data-aia-component="icon" ' . $attributes . '><svg class="aia-icon__glyph"'
            . ' viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $glyph . '</svg></span>';
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
                . $currentAttribute . '>Página ' . $page . '</a>';
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
        if (($config['presentation'] ?? 'adaptive') === 'sidebar') {
            return self::sidebarNavigation($id, $brand, $config);
        }
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

    private static function sidebarNavigation(string $id, string $brand, array $config): string
    {
        $context = $config['context'] ?? null;
        if (!is_array($context)) {
            throw new InvalidArgumentException('sidebar context must be an array');
        }
        $project = self::text($context['project'] ?? '', 'sidebar project');
        $week = self::text($context['week'] ?? '', 'sidebar week');
        $active = (string) ($config['active'] ?? '');
        $groups = $config['groups'] ?? null;
        if (!is_array($groups) || $groups === []) {
            throw new InvalidArgumentException('sidebar groups must be a non-empty array');
        }
        $state = $config['initialState'] ?? 'expanded';
        if (!in_array($state, ['expanded', 'collapsed'], true)) {
            throw new InvalidArgumentException('invalid sidebar initial state');
        }
        $seen = [];
        $groupsMarkup = [];
        foreach ($groups as $group) {
            if (!is_array($group)) throw new InvalidArgumentException('each sidebar group must be an array');
            $groupId = self::id($group['id'] ?? '', 'sidebar group id');
            $groupLabel = self::text($group['label'] ?? '', 'sidebar group label');
            $items = $group['items'] ?? null;
            if (!is_array($items)) throw new InvalidArgumentException('sidebar group items must be an array');
            $itemsMarkup = [];
            foreach ($items as $item) {
                if (!is_array($item)) throw new InvalidArgumentException('each sidebar item must be an array');
                $itemId = self::id($item['id'] ?? '', 'sidebar item id');
                if (isset($seen[$itemId])) throw new InvalidArgumentException("duplicate sidebar item id: {$itemId}");
                $seen[$itemId] = true;
                $label = self::text($item['label'] ?? '', 'sidebar item label');
                $icon = self::id($item['icon'] ?? 'overview', 'sidebar item icon');
                $itemState = $item['state'] ?? 'default';
                if (!in_array($itemState, ['default', 'disabled'], true)) {
                    throw new InvalidArgumentException('invalid sidebar item state');
                }
                $href = array_key_exists('href', $item) ? self::href($item['href']) : '';
                $current = $itemId === $active ? ' aria-current="page"' : '';
                $badge = '';
                if (array_key_exists('badge', $item)) {
                    $badgeValue = self::text((string) $item['badge'], 'sidebar item badge');
                    $badge = '<span class="aia-sidebar__badge" aria-label="' . self::escape($badgeValue)
                        . '">' . self::escape($badgeValue) . '</span>';
                }
                $linkAttributes = $itemState === 'disabled'
                    ? ' role="link" aria-disabled="true" data-sidebar-disabled'
                    : ' href="' . self::escape($href) . '" data-shell-destination data-destination-id="' . self::escape($itemId) . '"';
                $itemsMarkup[] = '<li><' . ($itemState === 'disabled' ? 'span' : 'a') . ' class="aia-sidebar__link"'
                    . $linkAttributes . ' data-sidebar-item data-sidebar-icon="' . self::escape($icon) . '"'
                    . ' title="' . self::escape($label) . '"' . $current . '>'
                    . self::icon(['name' => $icon, 'decorative' => true])
                    . '<span class="aia-sidebar__label">' . self::escape($label) . '</span>' . $badge . '</' . ($itemState === 'disabled' ? 'span' : 'a') . '></li>';
            }
            $groupsMarkup[] = '<section class="aia-sidebar__group" data-sidebar-group="' . self::escape($groupId) . '"'
                . ' aria-labelledby="' . self::escape($id . '-' . $groupId . '-label') . '">'
                . '<h3 id="' . self::escape($id . '-' . $groupId . '-label') . '">' . self::escape($groupLabel) . '</h3>'
                . ($itemsMarkup === []
                    ? '<p class="aia-sidebar__empty" data-sidebar-empty>No hay módulos disponibles.</p>'
                    : '<ul>' . implode('', $itemsMarkup) . '</ul><p class="aia-sidebar__empty" data-sidebar-empty hidden>No hay módulos disponibles.</p>') . '</section>';
        }
        if ($active !== '' && !isset($seen[$active])) {
            throw new InvalidArgumentException("unknown active sidebar destination: {$active}");
        }
        $toggleId = $id . '-toggle';
        $panelId = $id . '-panel';
        $notifications = $config['utilities']['notifications'] ?? [];
        $notificationLabel = self::text($notifications['label'] ?? 'Avisos', 'sidebar notification label');
        $notificationState = $notifications['state'] ?? 'default';
        if (!in_array($notificationState, ['default', 'loading', 'empty', 'error'], true)) {
            throw new InvalidArgumentException('invalid sidebar notification state');
        }
        $notificationCount = array_key_exists('count', $notifications)
            ? self::text((string) $notifications['count'], 'sidebar notification count') : '';
        $account = $config['utilities']['account'] ?? [];
        $accountLabel = self::text($account['label'] ?? 'Cuenta', 'sidebar account label');
        $accountItems = $account['items'] ?? [
            ['label' => 'Cambiar proyecto'], ['label' => 'Cambiar tema'], ['label' => 'Cerrar sesión'],
        ];
        $accountMarkup = self::sidebarAccountMarkup($id . '-account', $accountLabel, $accountItems);
        $notificationText = $notificationState === 'loading' ? 'Cargando avisos…'
            : ($notificationState === 'empty' ? 'No hay avisos nuevos.'
                : ($notificationState === 'error' ? 'No se pudieron cargar los avisos.' : $notificationLabel));
        return '<aside class="aia-navigation aia-navigation--sidebar" aria-label="Navegación de ' . self::escape($brand) . '" data-aia-component="navigation"'
            . ' data-shell-pattern="sidebar" data-sidebar-state="' . self::escape($state) . '">'
            . '<header class="aia-sidebar__header"><a class="aia-sidebar__brand aia-brand-lockup" href="/proyectos"'
            . ' aria-label="' . self::escape($brand) . '"><img src="/public/img/aia-last-planner-mark.svg" alt="" aria-hidden="true">'
            . '<strong class="aia-sidebar__brand-name">' . self::escape($brand) . '</strong></a>'
            . '<div class="aia-sidebar__context"><span>' . self::escape($project) . '</span><small>' . self::escape($week) . '</small></div>'
            . '<button type="button" class="aia-btn aia-btn--secondary aia-sidebar__toggle" id="' . self::escape($toggleId)
            . '" data-sidebar-toggle aria-controls="' . self::escape($panelId) . '" aria-expanded="'
            . ($state === 'expanded' ? 'true' : 'false') . '"><span class="aia-sidebar__toggle-icon">'
            . self::icon(['name' => 'collapse', 'decorative' => true]) . '</span><span class="aia-sidebar__toggle-label">'
            . ($state === 'expanded' ? 'Colapsar menú' : 'Expandir menú') . '</span></button></header>'
            . '<nav id="' . self::escape($panelId) . '" class="aia-sidebar__nav" aria-label="Navegación del proyecto" aria-busy="false">'
            . implode('', $groupsMarkup) . '</nav>'
            . '<footer class="aia-sidebar__footer"><button type="button" class="aia-sidebar__utility" data-sidebar-notifications'
            . ' aria-label="' . self::escape($notificationText) . '" data-sidebar-notification-state="'
            . self::escape($notificationState) . '">' . self::icon(['name' => 'bell', 'decorative' => true])
            . '<span class="aia-sidebar__label">' . self::escape($notificationLabel) . '</span>'
            . ($notificationCount !== '' ? '<span class="aia-sidebar__badge">' . self::escape($notificationCount) . '</span>' : '')
            . '</button><span class="aia-sidebar__notification-state" data-sidebar-notification-message role="status" aria-live="polite">'
            . self::escape($notificationText) . '</span><button type="button" class="aia-sidebar__retry" data-sidebar-notification-retry hidden>Reintentar</button>' . $accountMarkup . '</footer></aside>';
    }

    private static function sidebarAccountMarkup(string $id, string $label, mixed $items): string
    {
        if (!is_array($items) || $items === []) throw new InvalidArgumentException('sidebar account items must be a non-empty array');
        $buttons = [];
        foreach ($items as $item) {
            if (!is_array($item)) throw new InvalidArgumentException('each sidebar account item must be an array');
            $itemLabel = self::text($item['label'] ?? '', 'sidebar account item label');
            $buttons[] = '<button type="button" role="menuitem">' . self::escape($itemLabel) . '</button>';
        }
        return '<div class="aia-menu aia-sidebar__account" data-aia-component="menu"><button type="button" class="aia-sidebar__utility"'
            . ' data-aia-menu-trigger aria-controls="' . self::escape($id) . '" aria-expanded="false">'
            . self::icon(['name' => 'user', 'decorative' => true]) . '<span class="aia-sidebar__label">'
            . self::escape($label) . '</span></button><div id="' . self::escape($id) . '" data-aia-menu-panel role="menu" hidden>'
            . implode('', $buttons) . '</div></div>';
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
        return '<header class="aia-page-header" data-aia-component="page-header" role="region"'
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
