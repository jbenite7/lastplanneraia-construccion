<?php

// Contrato del partial shell_sidebar: ítem Semanas, RBAC de gestión y diálogos DS.
define('PROJECT_ROOT', dirname(__DIR__));
require PROJECT_ROOT . '/vendor/autoload.php';

session_start();
$_SESSION['usuario'] = 'contract';
$_SESSION['proyecto'] = 'Proyecto Contrato';
$_SESSION['semana'] = 3;
$_SESSION['db'] = 'contrato_db';
$_SESSION['nombreUsuario'] = 'Contract User';
$_SESSION['area'] = 'Construccion';
$_SESSION['project_id'] = 0;

function renderShellPartial(string $rol): string
{
    $shellActive = 'programacion-intermedia';
    $shellModuleLabel = 'Programación Intermedia';
    $shellWeeks = [
        ['Semana' => 3, 'Fecha_Inicio_Sem' => '2026-06-01', 'Fecha_Fin_Sem' => '2026-06-07'],
        ['Semana' => 2, 'Fecha_Inicio_Sem' => '2026-05-25', 'Fecha_Fin_Sem' => '2026-05-31'],
        ['Semana' => 1, 'Fecha_Inicio_Sem' => '2026-05-18', 'Fecha_Fin_Sem' => '2026-05-24'],
    ];
    $permiso = $rol;
    ob_start();
    require PROJECT_ROOT . '/views/partials/shell_sidebar.php';
    return (string) ob_get_clean();
}

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $fails++;
    }
};

$admin = renderShellPartial('A');
$check(str_contains($admin, 'data-destination-id="semanas-proyecto"'), 'A: ítem Semanas presente');
$check(str_contains($admin, 'data-sidebar-action'), 'A: ítem Semanas es action (botón)');
$check(str_contains($admin, 'shellWeekCreateDialog'), 'A: diálogo crear presente');
$check(str_contains($admin, 'shellWeekDeleteDialog'), 'A: diálogo eliminar presente');
$check(str_contains($admin, '"canCreate":true'), 'A: JSON canCreate true');
$check(str_contains($admin, '"canDelete":true'), 'A: JSON canDelete true');
$check(str_contains($admin, '"db":"contrato_db"'), 'A: JSON db');
$check(str_contains($admin, '"maxSemana":3'), 'A: JSON maxSemana');
$check(str_contains($admin, '"esAdmin":true'), 'A: JSON esAdmin');
$check(str_contains($admin, 'aia-modal-surface'), 'A: diálogos con clase canónica');
$check(str_contains($admin, 'aia-icon--chevron-down'), 'chip con chevron');
$check(substr_count($admin, 'aria-current="page"') === 1, 'A: un solo aria-current');

$viewer = renderShellPartial('V');
$check(str_contains($viewer, 'data-destination-id="semanas-proyecto"'), 'V: ítem Semanas visible (cambio permitido)');
$check(!str_contains($viewer, 'shellWeekCreateDialog'), 'V: sin diálogo crear');
$check(!str_contains($viewer, 'shellWeekDeleteDialog'), 'V: sin diálogo eliminar');
$check(str_contains($viewer, '"canCreate":false'), 'V: JSON canCreate false');

$check(str_contains($admin, 'shellWeekCreateOpen'), 'A: builder emite botón Nueva semana');
$check(str_contains($admin, 'data-shell-delete-week'), 'A: builder emite trash de última semana');
$check(!str_contains($viewer, 'shellWeekCreateOpen'), 'V: sin botón Nueva semana');
$check(!str_contains($viewer, 'data-shell-delete-week'), 'V: sin trash');

echo $fails === 0 ? "Shell sidebar partial: PASS\n" : "Shell sidebar partial: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
