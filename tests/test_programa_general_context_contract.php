<?php

declare(strict_types=1);
// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Security/DataScope/ProjectScope.php';
require_once __DIR__ . '/../src/Security/ProgramaGeneralActionPolicy.php';
require_once __DIR__ . '/../src/Services/RestrictionConfigResolver.php';
require_once __DIR__ . '/../src/Services/ProgramaGeneralContextService.php';

use App\Security\DataScope\ProjectScope;
use App\Services\ProgramaGeneralContextService;

function assertStrictEqual($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $msg = $message !== '' ? $message . ' ' : '';
        throw new RuntimeException($msg . 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

echo "=== Test Programa General Context Contract ===\n";

// Fake DB adapter
class FakeDatabaseForContext
{
    private array $semanas;
    private ?array $proyectoPc;
    private array $profesionales;
    private array $subcontratistas;

    public function __construct(
        array $semanas = [],
        ?array $proyectoPc = null,
        array $profesionales = [],
        array $subcontratistas = []
    ) {
        $this->semanas = $semanas;
        $this->proyectoPc = $proyectoPc;
        $this->profesionales = $profesionales;
        $this->subcontratistas = $subcontratistas;
    }

    public function queryWithProject(string $sql, array $params = [], ?int $projectId = null)
    {
        return $this->query($sql, $params);
    }

    public function query(string $sql, array $params = [])
    {
        if (str_contains($sql, 'semanas_activas') && str_contains($sql, 'ORDER BY Semana DESC')) {
            $row = !empty($this->semanas) ? end($this->semanas) : null;
            return new FakeStatement($row ? [$row] : []);
        }

        if (str_contains($sql, 'semanas_activas') && str_contains($sql, 'AND Semana = ?')) {
            $weekNum = $params[1] ?? 0;
            $found = null;
            foreach ($this->semanas as $s) {
                if ($s['Semana'] === $weekNum) {
                    $found = $s;
                    break;
                }
            }
            return new FakeStatement($found ? [$found] : []);
        }

        if (str_contains($sql, 'general_proyectos_procesos')) {
            return new FakeStatement($this->proyectoPc ? [$this->proyectoPc] : []);
        }

        if (str_contains($sql, 'profesionales')) {
            return new FakeStatement($this->profesionales);
        }

        if (str_contains($sql, 'subcontratistas')) {
            return new FakeStatement($this->subcontratistas);
        }

        return new FakeStatement([]);
    }
}

class FakeStatement
{
    private array $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function fetch($mode = null) { return array_shift($this->rows) ?: false; }
    public function fetchAll($mode = null) { $r = $this->rows; $this->rows = []; return $r; }
}

// 1. Caso Construcción estándar (semana 18 de 20, no confirmada)
$fakeDb = new FakeDatabaseForContext(
    semanas: [
        ['Semana' => 18, 'confirmed' => 0],
        ['Semana' => 20, 'confirmed' => 0],
    ],
    profesionales: [
        ['id' => 1, 'nombre' => 'Carlos Restrepo', 'cargo' => 'Director de Obra'],
        ['id' => 2, 'nombre' => 'Laura Marin', 'cargo' => 'Residente de Calidad'],
    ],
    subcontratistas: [
        ['id' => 10, 'nombre' => 'Cimentaciones SAS', 'especialidad' => 'Cimentación'],
    ]
);
$service = new ProgramaGeneralContextService(
    db: $fakeDb,
    permissionResolver: fn($perm) => true,
    biResolver: fn($mod) => '/bi/' . $mod,
    csrfResolver: fn($form) => 'csrf_' . $form
);

$scope = new ProjectScope(73, 'test.D', 'D');
$session = [
    'Proyecto_Proceso' => 'Torre 1',
    'Area' => 'Construccion',
    'Semana' => 18,
    'db' => 'torre_1',
];

$ctx = $service->build($scope, $session);

// Top level keys check
assertStrictEqual(['project', 'week', 'actions', 'csrf', 'restrictionConfig', 'links', 'catalogos'], array_keys($ctx), 'Top level keys');

// Project keys
assertStrictEqual(73, $ctx['project']['id'], 'project id');
assertStrictEqual('Torre 1', $ctx['project']['name'], 'project name');
assertStrictEqual('Construccion', $ctx['project']['area'], 'project area');
assertStrictEqual('torre_1', $ctx['project']['dbPrefix'], 'project dbPrefix');

// Week keys
assertStrictEqual(18, $ctx['week']['number'], 'week number');
assertStrictEqual(20, $ctx['week']['max'], 'week max');
assertStrictEqual(false, $ctx['week']['confirmed'], 'week confirmed');

// Actions
assertStrictEqual(true, $ctx['actions']['editPlanFields'], 'editPlanFields for D on past unconfirmed week');
assertStrictEqual(true, $ctx['actions']['editProgress'], 'editProgress');
assertStrictEqual(true, $ctx['actions']['runBatch'], 'runBatch');

// CSRF
assertStrictEqual('csrf_programa_general', $ctx['csrf']['programaGeneral'], 'csrf programaGeneral');
assertStrictEqual('csrf_lps_drawer', $ctx['csrf']['drawer'], 'csrf drawer');
assertStrictEqual('csrf_shell_api', $ctx['csrf']['shell'], 'csrf shell_api');

// Links
assertStrictEqual('/bi/programa-general', $ctx['links']['bi'], 'bi link');

// Restriction config
assertStrictEqual('Construccion', $ctx['restrictionConfig']['area'], 'restriction area');
assertStrictEqual(7, count($ctx['restrictionConfig']['restrictions']), '7 restrictions in Construccion');

// Catalogos
assertStrictEqual(2, count($ctx['catalogos']['profesionales']), '2 profesionales');
assertStrictEqual('Carlos Restrepo', $ctx['catalogos']['profesionales'][0]['nombre'], 'profesional 0 nombre');
assertStrictEqual(1, count($ctx['catalogos']['subcontratistas']), '1 subcontratista');
assertStrictEqual('Cimentaciones SAS', $ctx['catalogos']['subcontratistas'][0]['nombre'], 'subcontratista 0 nombre');

// 2. Caso Sin semanas (number=0, max=0)
$emptyDb = new FakeDatabaseForContext([]);
$emptyService = new ProgramaGeneralContextService(
    db: $emptyDb,
    permissionResolver: fn($perm) => true,
    biResolver: fn($mod) => null,
    csrfResolver: fn($form) => 'token'
);
$emptyCtx = $emptyService->build($scope, ['Proyecto_Proceso' => 'Vacio', 'Area' => 'Construccion']);
assertStrictEqual(0, $emptyCtx['week']['number'], 'empty week number 0');
assertStrictEqual(0, $emptyCtx['week']['max'], 'empty week max 0');
assertStrictEqual(false, $emptyCtx['actions']['editPlanFields'], 'actions false when week 0');
assertStrictEqual(false, $emptyCtx['actions']['editProgress'], 'actions false when week 0');
assertStrictEqual(false, $emptyCtx['actions']['runBatch'], 'actions false when week 0');
assertStrictEqual(null, $emptyCtx['links']['bi'], 'bi link null');
assertStrictEqual([], $emptyCtx['catalogos']['profesionales'], 'empty profesionales');
assertStrictEqual([], $emptyCtx['catalogos']['subcontratistas'], 'empty subcontratistas');

// 3. Caso Pre-Construcción con labels personalizadas
$pcDb = new FakeDatabaseForContext(
    [['Semana' => 5, 'confirmed' => 0]],
    [
        'pc_restr_2_nombre' => 'Diseño',
        'pc_restr_3_nombre' => '',
        'pc_restr_4_nombre' => 'Permisos',
    ]
);
$pcService = new ProgramaGeneralContextService(
    db: $pcDb,
    permissionResolver: fn($perm) => true,
    biResolver: fn($mod) => '/bi/' . $mod,
    csrfResolver: fn($form) => 'token'
);
$pcCtx = $pcService->build($scope, ['Proyecto_Proceso' => 'Edificio Pre', 'Area' => 'Pre-Construccion', 'Semana' => 5]);
assertStrictEqual('Pre-Construccion', $pcCtx['project']['area'], 'PC area');
assertStrictEqual(['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_4'], array_column($pcCtx['restrictionConfig']['restrictions'], 'key'), 'PC keys filtered');
assertStrictEqual([], $pcCtx['catalogos']['profesionales'], 'pc profesionales');
assertStrictEqual([], $pcCtx['catalogos']['subcontratistas'], 'pc subcontratistas');

echo "PASS: Programa General Context contract verified completely.\n";
