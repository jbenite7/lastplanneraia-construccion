<?php

declare(strict_types=1);

namespace App\Services;

use App\Security\CsrfTokenManager;
use App\Security\DataScope\ProjectScope;
use App\Security\ProgramaGeneralActionPolicy;
use App\Security\RbacService;
use App\View\Components\BiAccessComponent;

class ProgramaGeneralContextService
{
    private $db;
    /** @var callable|null */
    private $permissionResolver;
    /** @var callable|null */
    private $biResolver;
    /** @var callable|null */
    private $csrfResolver;

    public function __construct(
        $db = null,
        ?callable $permissionResolver = null,
        ?callable $biResolver = null,
        ?callable $csrfResolver = null
    ) {
        $this->db = $db ?? \Database::getInstance();
        $this->permissionResolver = $permissionResolver;
        $this->biResolver = $biResolver;
        $this->csrfResolver = $csrfResolver;
    }

    /**
     * Build the Programa General context array for S05.
     *
     * @param ProjectScope $scope
     * @param array $session
     * @return array
     */
    public function build(ProjectScope $scope, array $session): array
    {
        $projectId = $scope->projectId();
        $user = $scope->user();
        $role = $scope->role();

        $projectName = (string) ($session['Proyecto_Proceso'] ?? $session['proyecto'] ?? 'Proyecto');
        $dbPrefix = (string) ($session['db'] ?? $session['dbPrefix'] ?? '');
        $area = (string) ($session['Area'] ?? $session['area'] ?? 'Construccion');
        if ($area === '' || ($area !== 'Construccion' && $area !== 'Pre-Construccion')) {
            $area = 'Construccion';
        }

        // Query max week from semanas_activas
        $stmtMax = $this->db->query(
            "SELECT Semana, IFNULL(Semanal_Confirmada, 0) as confirmed FROM semanas_activas WHERE project_id = ? ORDER BY Semana DESC LIMIT 1",
            [$projectId]
        );
        $maxRow = $stmtMax ? $stmtMax->fetch(\PDO::FETCH_ASSOC) : null;
        $maxWeek = $maxRow ? (int) $maxRow['Semana'] : 0;

        $currentWeek = isset($session['Semana'])
            ? (int) $session['Semana']
            : (isset($session['semana']) ? (int) $session['semana'] : $maxWeek);
        if ($currentWeek <= 0 || $currentWeek > $maxWeek) {
            $currentWeek = $maxWeek;
        }

        $confirmed = false;
        if ($currentWeek > 0 && $maxWeek > 0) {
            if ($currentWeek === $maxWeek) {
                $confirmed = (bool) $maxRow['confirmed'];
            } else {
                $stmtCur = $this->db->query(
                    "SELECT IFNULL(Semanal_Confirmada, 0) as confirmed FROM semanas_activas WHERE project_id = ? AND Semana = ? LIMIT 1",
                    [$projectId, $currentWeek]
                );
                $curRow = $stmtCur ? $stmtCur->fetch(\PDO::FETCH_ASSOC) : null;
                $confirmed = $curRow ? (bool) $curRow['confirmed'] : false;
            }
        }

        // Permissions
        $canEdit = $this->checkPermission('lps.programa_general.editar', $role, $projectId);
        $normRole = $this->getRbac()->normalizeRole($role);
        $canEditPast = $this->checkPermission('lps.programa_general.editar_pasado', $role, $projectId)
            || in_array($normRole, ['A', 'D'], true);
        $canDownload = $this->checkPermission('lps.reportes.generar', $role, $projectId);
        $canReadDrawer = $this->checkPermission('lps.programacion_semanal.ver', $role, $projectId);
        $canWriteDrawer = $this->checkPermission('lps.programacion_semanal.editar', $role, $projectId);

        $actions = ProgramaGeneralActionPolicy::resolve(
            canEdit: $canEdit,
            canEditPast: $canEditPast,
            week: $currentWeek,
            maxWeek: $maxWeek,
            confirmed: $confirmed,
            canDownload: $canDownload,
            canReadDrawer: $canReadDrawer,
            canWriteDrawer: $canWriteDrawer
        );

        // PC Labels if Pre-Construccion
        $pcLabels = [];
        if ($area === 'Pre-Construccion') {
            $stmtPc = $this->db->query(
                "SELECT pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre FROM general_proyectos_procesos WHERE Id = ? LIMIT 1",
                [$projectId]
            );
            $pcRow = $stmtPc ? $stmtPc->fetch(\PDO::FETCH_ASSOC) : null;
            if ($pcRow) {
                $pcLabels = [
                    'restriccion_pc_2' => (string) ($pcRow['pc_restr_2_nombre'] ?? ''),
                    'restriccion_pc_3' => (string) ($pcRow['pc_restr_3_nombre'] ?? ''),
                    'restriccion_pc_4' => (string) ($pcRow['pc_restr_4_nombre'] ?? ''),
                ];
            }
        }

        $restrictionConfig = RestrictionConfigResolver::presentationConfig($area, $pcLabels);

        // CSRF Tokens
        $csrfPrograma = $this->generateCsrf('programa_general');
        $csrfDrawer = $this->generateCsrf('lps_drawer');
        $csrfShell = $this->generateCsrf('shell_api');

        // BI Link
        $biLink = $this->resolveBiLink('programa-general');

        // Catálogos activos de profesionales y subcontratistas
        $profStmt = method_exists($this->db, 'queryWithProject')
            ? $this->db->queryWithProject(
                "SELECT id, nombre, cargo FROM profesionales WHERE project_id = ? AND activo = 1 ORDER BY nombre ASC",
                [$projectId],
                $projectId
            )
            : $this->db->query(
                "SELECT id, nombre, cargo FROM profesionales WHERE project_id = ? AND activo = 1 ORDER BY nombre ASC",
                [$projectId]
            );
        $profesionales = ($profStmt && method_exists($profStmt, 'fetchAll'))
            ? (array) $profStmt->fetchAll(\PDO::FETCH_ASSOC)
            : [];

        $subStmt = method_exists($this->db, 'queryWithProject')
            ? $this->db->queryWithProject(
                "SELECT Id as id, subcontratista as nombre, alcance as especialidad FROM subcontratistas WHERE project_id = ? AND activo = 1 ORDER BY subcontratista ASC",
                [$projectId],
                $projectId
            )
            : $this->db->query(
                "SELECT Id as id, subcontratista as nombre, alcance as especialidad FROM subcontratistas WHERE project_id = ? AND activo = 1 ORDER BY subcontratista ASC",
                [$projectId]
            );
        $subcontratistas = ($subStmt && method_exists($subStmt, 'fetchAll'))
            ? (array) $subStmt->fetchAll(\PDO::FETCH_ASSOC)
            : [];

        return [
            'project' => [
                'id' => $projectId,
                'name' => $projectName,
                'area' => $area,
                'dbPrefix' => $dbPrefix,
            ],
            'week' => [
                'number' => $currentWeek,
                'max' => $maxWeek,
                'confirmed' => $confirmed,
            ],
            'actions' => $actions,
            'csrf' => [
                'programaGeneral' => $csrfPrograma,
                'drawer' => $csrfDrawer,
                'shell' => $csrfShell,
            ],
            'restrictionConfig' => $restrictionConfig,
            'links' => [
                'bi' => $biLink,
            ],
            'catalogos' => [
                'profesionales' => $profesionales,
                'subcontratistas' => $subcontratistas,
            ],
        ];
    }

    private ?RbacService $rbacService = null;

    private function getRbac(): RbacService
    {
        return $this->rbacService ??= new RbacService($this->db);
    }

    private function checkPermission(string $capability, string $role, int $projectId): bool
    {
        if ($this->permissionResolver !== null) {
            return (bool) ($this->permissionResolver)($capability, $role, $projectId);
        }

        return $this->getRbac()->can($capability, $role);
    }

    private function generateCsrf(string $formKey): string
    {
        if ($this->csrfResolver !== null) {
            return (string) ($this->csrfResolver)($formKey);
        }

        return CsrfTokenManager::generate($formKey === 'programa_general' ? 'programa_general_save' : $formKey);
    }

    private function resolveBiLink(string $module): ?string
    {
        if ($this->biResolver !== null) {
            return ($this->biResolver)($module);
        }

        if (!BiAccessComponent::canAccess()) {
            return null;
        }

        return BiAccessComponent::url($module);
    }
}
