<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Database;
use Throwable;

class FamilyCatalogController extends AdminController
{
    public function index(): void
    {
        $this->requireAdminRole();
        $db = Database::getInstance();

        $this->render('matching/family_catalog', [
            'title' => 'Catálogo de Familias',
            'pageTitle' => 'Catálogo de Familias',
            'breadcrumb' => 'Catálogo de Familias',
            'csrf_token' => Security::generateCsrfToken(),
            'flash_success' => $this->pullFlash('flash_success'),
            'flash_error' => $this->pullFlash('flash_error'),
            'families' => $this->families($db),
            'aliases' => $this->aliases($db),
            'contractualElements' => $this->contractualElements($db),
            'rules' => $this->rules($db),
            'impact' => $this->impact($db),
            'audit' => $this->audit($db),
            'pendingDecisions' => $this->pendingDecisions($db),
        ]);
    }

    public function saveFamily(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $id = (int) ($_POST['id'] ?? 0);
        $codigo = $this->normalizeCode((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $categoria = trim((string) ($_POST['categoria'] ?? 'GENERAL'));
        $orden = (int) ($_POST['orden'] ?? 999);
        $revision = isset($_POST['siempre_revision']) ? 1 : 0;
        $activa = isset($_POST['activa']) ? 1 : 0;

        if ($codigo === '' || $nombre === '') {
            $this->redirectWith('flash_error', 'Código y nombre de familia son obligatorios.');
        }

        $db = Database::getInstance();
        try {
            if ($activa === 1) {
                $conflict = $this->activeFamilyConflictMessage($db, $nombre, $id);
                if ($conflict !== null) {
                    $this->redirectWith('flash_error', $conflict);
                }
            }

            if ($id > 0) {
                $db->query(
                    'UPDATE general_pdc_familias
                     SET codigo = ?, nombre = ?, categoria = ?, orden = ?, siempre_revision = ?, activa = ?
                     WHERE id = ?',
                    [$codigo, $nombre, $categoria, $orden, $revision, $activa, $id],
                );
                $action = 'ACTUALIZAR_FAMILIA';
            } else {
                $db->query(
                    'INSERT INTO general_pdc_familias
                     (codigo, nombre, categoria, orden, siempre_revision, activa)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$codigo, $nombre, $categoria, $orden, $revision, $activa],
                );
                $action = 'CREAR_FAMILIA';
            }
            $db->logActivity('CatalogoFamilias', $action, "Familia operativa: {$nombre}");
            $this->redirectWith('flash_success', 'Familia guardada.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] saveFamily: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo guardar la familia.');
        }
    }

    public function saveAlias(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $id = (int) ($_POST['id'] ?? 0);
        $alias = trim((string) ($_POST['alias_nombre'] ?? ''));
        $familyId = (int) ($_POST['familia_id'] ?? 0);
        $fuente = trim((string) ($_POST['fuente'] ?? 'admin'));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $activa = isset($_POST['activa']) ? 1 : 0;

        if ($alias === '' || $familyId <= 0) {
            $this->redirectWith('flash_error', 'Alias y familia canónica son obligatorios.');
        }

        $db = Database::getInstance();
        try {
            if ($id > 0) {
                $db->query(
                    'UPDATE general_pdc_family_aliases
                     SET alias_nombre = ?, alias_normalizado = ?, familia_id = ?, fuente = ?, notas = ?, activa = ?
                     WHERE id = ?',
                    [$alias, $this->normalizeLabel($alias), $familyId, $fuente, $notas, $activa, $id],
                );
                $action = 'ACTUALIZAR_ALIAS';
            } else {
                $db->query(
                    'INSERT INTO general_pdc_family_aliases
                     (alias_nombre, alias_normalizado, familia_id, fuente, notas, activa)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$alias, $this->normalizeLabel($alias), $familyId, $fuente, $notas, $activa],
                );
                $action = 'CREAR_ALIAS';
            }
            $db->logActivity('CatalogoFamilias', $action, "Alias: {$alias}");
            $this->redirectWith('flash_success', 'Alias guardado.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] saveAlias: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo guardar el alias.');
        }
    }

    public function saveContractualElement(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $tipo = trim((string) ($_POST['tipo_paquete'] ?? ''));
        $paquete = trim((string) ($_POST['paquete_nombre'] ?? ''));
        $familyId = (int) ($_POST['familia_id'] ?? 0);
        $fuente = trim((string) ($_POST['fuente'] ?? 'admin'));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $activa = isset($_POST['activa']) ? 1 : 0;

        if ($nombre === '' || $tipo === '' || $paquete === '') {
            $this->redirectWith('flash_error', 'Nombre, tipo y paquete contractual son obligatorios.');
        }

        $db = Database::getInstance();
        try {
            $params = [
                $nombre,
                $this->normalizeLabel($nombre),
                $tipo,
                $paquete,
                $familyId > 0 ? $familyId : null,
                $fuente,
                $notas,
                $activa,
            ];
            if ($id > 0) {
                $db->query(
                    'UPDATE general_pdc_contractual_elements
                     SET nombre = ?, nombre_normalizado = ?, tipo_paquete = ?, paquete_nombre = ?,
                         familia_id = ?, fuente = ?, notas = ?, activa = ?
                     WHERE id = ?',
                    [...$params, $id],
                );
                $action = 'ACTUALIZAR_ELEMENTO_CONTRACTUAL';
            } else {
                $db->query(
                    'INSERT INTO general_pdc_contractual_elements
                     (nombre, nombre_normalizado, tipo_paquete, paquete_nombre, familia_id, fuente, notas, activa)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    $params,
                );
                $action = 'CREAR_ELEMENTO_CONTRACTUAL';
            }
            $db->logActivity('CatalogoFamilias', $action, "Elemento contractual: {$nombre}");
            $this->redirectWith('flash_success', 'Elemento contractual guardado.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] saveContractualElement: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo guardar el elemento contractual.');
        }
    }

    public function saveRuleAssignment(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $ruleId = (int) ($_POST['rule_id'] ?? 0);
        $familyId = (int) ($_POST['familia_id'] ?? 0);
        $activa = isset($_POST['activa']) ? 1 : 0;
        $motivo = trim((string) ($_POST['motivo'] ?? 'Cambio manual desde catálogo admin.'));
        if ($ruleId <= 0 || $familyId <= 0) {
            $this->redirectWith('flash_error', 'Regla y familia destino son obligatorias.');
        }

        $db = Database::getInstance();
        try {
            $current = $db->query(
                'SELECT id, familia_id FROM general_pdc_activity_rules WHERE id = ? LIMIT 1',
                [$ruleId],
            )->fetch(\PDO::FETCH_ASSOC);
            if (!$current) {
                $this->redirectWith('flash_error', 'La regla seleccionada no existe.');
            }

            $db->query(
                'UPDATE general_pdc_activity_rules SET familia_id = ?, activa = ? WHERE id = ?',
                [$familyId, $activa, $ruleId],
            );
            $db->query(
                'INSERT INTO general_pdc_family_rule_audit
                 (rule_id, old_familia_id, new_familia_id, accion, motivo, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$ruleId, (int) $current['familia_id'], $familyId, 'admin_reasignar_regla', $motivo, $this->currentUser()],
            );
            $db->logActivity('CatalogoFamilias', 'REASIGNAR_REGLA', "Regla {$ruleId}: {$motivo}");
            $this->redirectWith('flash_success', 'Regla actualizada.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] saveRuleAssignment: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo actualizar la regla.');
        }
    }

    public function approveCatalogItem(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $type = (string) ($_POST['type'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $map = [
            'family' => ['general_pdc_familias', 'APROBAR_FAMILIA'],
            'alias' => ['general_pdc_family_aliases', 'APROBAR_ALIAS'],
            'contractual' => ['general_pdc_contractual_elements', 'APROBAR_ELEMENTO_CONTRACTUAL'],
            'rule' => ['general_pdc_activity_rules', 'APROBAR_REGLA'],
        ];
        if ($id <= 0 || !isset($map[$type])) {
            $this->redirectWith('flash_error', 'Elemento de catálogo inválido.');
        }

        [$table, $action] = $map[$type];
        try {
            $db = Database::getInstance();
            if ($type === 'family') {
                $family = $db->query(
                    'SELECT id, nombre FROM general_pdc_familias WHERE id = ? LIMIT 1',
                    [$id],
                )->fetch(\PDO::FETCH_ASSOC);
                if (!$family) {
                    $this->redirectWith('flash_error', 'La familia seleccionada no existe.');
                }
                $conflict = $this->activeFamilyConflictMessage($db, (string) $family['nombre'], (int) $family['id']);
                if ($conflict !== null) {
                    $this->redirectWith('flash_error', $conflict);
                }
            }

            Database::getInstance()->query("UPDATE {$table} SET activa = 1 WHERE id = ?", [$id]);
            Database::getInstance()->logActivity('CatalogoFamilias', $action, "Elemento {$type} #{$id} activado.");
            $this->redirectWith('flash_success', 'Elemento aprobado y activado.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] approveCatalogItem: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo aprobar el elemento.');
        }
    }

    public function resolvePendingDecision(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $familyId = (int) ($_POST['familia_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $motivo = trim((string) ($_POST['motivo'] ?? 'Decisión humana desde catálogo admin.'));

        if ($familyId <= 0 || !in_array($decision, ['keep_listado', 'move_contracts'], true)) {
            $this->redirectWith('flash_error', 'Decisión pendiente inválida.');
        }

        $db = Database::getInstance();
        try {
            $family = $this->findFamily($db, $familyId);
            if ($family === null) {
                $this->redirectWith('flash_error', 'La familia seleccionada no existe.');
            }

            if ($decision === 'keep_listado') {
                $this->keepPendingFamilyInListado($db, $family, $motivo);
                $this->redirectWith('flash_success', 'La familia queda en Listado.');
            }

            $tipo = trim((string) ($_POST['tipo_paquete'] ?? ''));
            $paquete = trim((string) ($_POST['paquete_nombre'] ?? ''));
            if ($tipo === '' || $paquete === '') {
                $this->redirectWith('flash_error', 'Para pasar a Contratos debes indicar tipo y paquete.');
            }

            $this->movePendingFamilyToContracts($db, $family, $tipo, $paquete, $motivo);
            $this->redirectWith('flash_success', 'La familia pasó a Contratos.');
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] resolvePendingDecision: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo guardar la decisión.');
        }
    }

    public function exportCatalog(): void
    {
        $this->requireAdminRole();
        $type = (string) ($_GET['type'] ?? 'families');
        $db = Database::getInstance();
        $rows = $this->exportRows($db, $type);
        if ($rows === null) {
            http_response_code(400);
            echo 'Tipo de exportación inválido.';
            exit;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="catalogo-' . $type . '.csv"');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }

    public function importCatalog(): void
    {
        $this->requireAdminRole();
        $this->validatePost();

        $type = (string) ($_POST['type'] ?? '');
        $csv = trim((string) ($_POST['csv'] ?? ''));
        if ($csv === '') {
            $this->redirectWith('flash_error', 'Debes pegar el CSV a importar.');
        }

        try {
            $count = $this->importRows(Database::getInstance(), $type, $csv);
            Database::getInstance()->logActivity('CatalogoFamilias', 'IMPORTAR_CSV', "Tipo {$type}: {$count} filas.");
            $this->redirectWith('flash_success', "Importación completada: {$count} filas.");
        } catch (Throwable $e) {
            error_log('[FamilyCatalogController] importCatalog: ' . $e->getMessage());
            $this->redirectWith('flash_error', 'No se pudo importar el CSV.');
        }
    }

    private function validatePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }
    }

    private function families(Database $db): array
    {
        return $db->query(
            'SELECT id, codigo, nombre, categoria, orden, siempre_revision, COALESCE(activa, 1) AS activa
             FROM general_pdc_familias
             ORDER BY COALESCE(activa, 1) DESC, orden ASC, nombre ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function aliases(Database $db): array
    {
        return $db->query(
            'SELECT a.*, f.nombre AS familia_nombre
             FROM general_pdc_family_aliases a
             INNER JOIN general_pdc_familias f ON f.id = a.familia_id
             ORDER BY a.activa DESC, a.alias_nombre ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function contractualElements(Database $db): array
    {
        return $db->query(
            'SELECT e.*, f.nombre AS familia_nombre
             FROM general_pdc_contractual_elements e
             LEFT JOIN general_pdc_familias f ON f.id = e.familia_id
             ORDER BY e.activa DESC, e.nombre ASC, e.tipo_paquete ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function rules(Database $db): array
    {
        return $db->query(
            'SELECT r.id, r.patron_regex, r.modalidad_sugerida, r.confianza, r.prioridad,
                    r.descripcion, r.activa, f.nombre AS familia_nombre
             FROM general_pdc_activity_rules r
             INNER JOIN general_pdc_familias f ON f.id = r.familia_id
             ORDER BY r.activa DESC, r.prioridad DESC, r.confianza DESC, r.id ASC
             LIMIT 80'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function exportRows(Database $db, string $type): ?array
    {
        return match ($type) {
            'families' => $this->families($db),
            'aliases' => $this->aliases($db),
            'contractual' => $this->contractualElements($db),
            'rules' => $this->rules($db),
            default => null,
        };
    }

    private function importRows(Database $db, string $type, string $csv): int
    {
        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];
        $headers = null;
        $count = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            if ($headers === null) {
                $headers = array_map(static fn($h): string => trim((string) $h), $values);
                continue;
            }
            $values = array_slice(array_pad($values, count($headers), ''), 0, count($headers));
            $row = array_combine($headers, $values);
            if (!is_array($row)) {
                continue;
            }
            $this->upsertImportRow($db, $type, $row);
            $count++;
        }

        return $count;
    }

    private function upsertImportRow(Database $db, string $type, array $row): void
    {
        if ($type === 'families') {
            $codigo = $this->normalizeCode((string) ($row['codigo'] ?? ''));
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($codigo === '' || $nombre === '') {
                throw new \RuntimeException('CSV de familias requiere codigo y nombre.');
            }
            $activa = (int) ($row['activa'] ?? 0);
            if ($activa === 1) {
                $conflict = $this->activeFamilyConflictMessage($db, $nombre);
                if ($conflict !== null) {
                    throw new \RuntimeException($conflict);
                }
            }
            $db->query(
                'INSERT INTO general_pdc_familias
                 (codigo, nombre, categoria, orden, siempre_revision, activa)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), categoria = VALUES(categoria),
                     orden = VALUES(orden), siempre_revision = VALUES(siempre_revision), activa = VALUES(activa)',
                [$codigo, $nombre, $row['categoria'] ?? 'GENERAL', (int) ($row['orden'] ?? 999), (int) ($row['siempre_revision'] ?? 0), $activa],
            );
            return;
        }

        if ($type === 'aliases') {
            $alias = trim((string) ($row['alias_nombre'] ?? ''));
            $familyId = $this->resolveFamilyId($db, $row);
            if ($alias === '' || $familyId <= 0) {
                throw new \RuntimeException('CSV de aliases requiere alias_nombre y familia.');
            }
            $db->query(
                'INSERT INTO general_pdc_family_aliases
                 (alias_nombre, alias_normalizado, familia_id, fuente, notas, activa)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE familia_id = VALUES(familia_id), fuente = VALUES(fuente),
                     notas = VALUES(notas), activa = VALUES(activa)',
                [$alias, $this->normalizeLabel($alias), $familyId, $row['fuente'] ?? 'import', $row['notas'] ?? '', (int) ($row['activa'] ?? 0)],
            );
            return;
        }

        if ($type === 'contractual') {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $tipo = trim((string) ($row['tipo_paquete'] ?? ''));
            $paquete = trim((string) ($row['paquete_nombre'] ?? ''));
            if ($nombre === '' || $tipo === '' || $paquete === '') {
                throw new \RuntimeException('CSV contractual requiere nombre, tipo_paquete y paquete_nombre.');
            }
            $db->query(
                'INSERT INTO general_pdc_contractual_elements
                 (nombre, nombre_normalizado, tipo_paquete, paquete_nombre, familia_id, fuente, notas, activa)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE familia_id = VALUES(familia_id), fuente = VALUES(fuente),
                     notas = VALUES(notas), activa = VALUES(activa)',
                [$nombre, $this->normalizeLabel($nombre), $tipo, $paquete, $this->resolveFamilyId($db, $row) ?: null, $row['fuente'] ?? 'import', $row['notas'] ?? '', (int) ($row['activa'] ?? 0)],
            );
            return;
        }

        throw new \RuntimeException('Tipo de importación inválido.');
    }

    private function resolveFamilyId(Database $db, array $row): int
    {
        $familyId = (int) ($row['familia_id'] ?? 0);
        if ($familyId > 0) {
            return $familyId;
        }
        $familyName = trim((string) ($row['familia_nombre'] ?? ''));
        if ($familyName === '') {
            return 0;
        }

        return (int) $db->query(
            'SELECT id FROM general_pdc_familias WHERE nombre = ? OR codigo = ? LIMIT 1',
            [$familyName, $this->normalizeCode($familyName)],
        )->fetchColumn();
    }

    private function findFamily(Database $db, int $familyId): ?array
    {
        $family = $db->query(
            'SELECT id, codigo, nombre, categoria
             FROM general_pdc_familias
             WHERE id = ?
             LIMIT 1',
            [$familyId],
        )->fetch(\PDO::FETCH_ASSOC);

        return is_array($family) ? $family : null;
    }

    private function keepPendingFamilyInListado(Database $db, array $family, string $motivo): void
    {
        $familyId = (int) $family['id'];
        $db->query(
            'UPDATE general_pdc_familias
             SET siempre_revision = 0, activa = 1
             WHERE id = ?',
            [$familyId],
        );
        $db->query(
            'INSERT INTO general_pdc_family_rule_audit
             (rule_id, old_familia_id, new_familia_id, accion, motivo, metadata, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                null,
                $familyId,
                $familyId,
                'admin_decision_mantener_listado',
                $motivo,
                json_encode(['familia' => $family['nombre'] ?? '', 'decision' => 'keep_listado'], JSON_UNESCAPED_UNICODE),
                $this->currentUser(),
            ],
        );
        $db->logActivity('CatalogoFamilias', 'DECISION_LISTADO', "Mantener en Listado: {$family['nombre']}.");
    }

    private function movePendingFamilyToContracts(Database $db, array $family, string $tipo, string $paquete, string $motivo): void
    {
        $familyId = (int) $family['id'];
        $name = (string) $family['nombre'];
        $normalized = $this->normalizeLabel($name);

        $db->query(
            'UPDATE general_pdc_familias
             SET siempre_revision = 0, activa = 0
             WHERE id = ?',
            [$familyId],
        );
        $this->upsertContractualDecision($db, $familyId, $name, $normalized, $tipo, $paquete, $motivo);

        $rules = $db->query(
            'SELECT id
             FROM general_pdc_activity_rules
             WHERE familia_id = ? AND COALESCE(activa, 1) = 1',
            [$familyId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $db->query(
            'UPDATE general_pdc_activity_rules
             SET activa = 0
             WHERE familia_id = ?',
            [$familyId],
        );

        foreach ($rules as $rule) {
            $db->query(
                'INSERT INTO general_pdc_family_rule_audit
                 (rule_id, old_familia_id, new_familia_id, accion, motivo, metadata, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $rule['id'],
                    $familyId,
                    null,
                    'admin_decision_pasar_contratos',
                    $motivo,
                    json_encode(['familia' => $name, 'decision' => 'move_contracts', 'tipo_paquete' => $tipo, 'paquete_nombre' => $paquete], JSON_UNESCAPED_UNICODE),
                    $this->currentUser(),
                ],
            );
        }

        $db->logActivity('CatalogoFamilias', 'DECISION_CONTRATOS', "Pasar a Contratos: {$name} -> {$paquete}.");
    }

    private function upsertContractualDecision(Database $db, int $familyId, string $name, string $normalized, string $tipo, string $paquete, string $motivo): void
    {
        $existingId = (int) $db->query(
            'SELECT id
             FROM general_pdc_contractual_elements
             WHERE nombre_normalizado = ?
             LIMIT 1',
            [$normalized],
        )->fetchColumn();
        $notas = trim('Decisión humana: ' . $motivo);

        if ($existingId > 0) {
            $db->query(
                'UPDATE general_pdc_contractual_elements
                 SET tipo_paquete = ?, paquete_nombre = ?, familia_id = ?, fuente = ?, notas = ?, activa = 1
                 WHERE id = ?',
                [$tipo, $paquete, $familyId, 'admin_decision', $notas, $existingId],
            );
            return;
        }

        $db->query(
            'INSERT INTO general_pdc_contractual_elements
             (nombre, nombre_normalizado, tipo_paquete, paquete_nombre, familia_id, fuente, notas, activa)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [$name, $normalized, $tipo, $paquete, $familyId, 'admin_decision', $notas],
        );
    }

    private function activeFamilyConflictMessage(Database $db, string $nombre, int $familyId = 0): ?string
    {
        $normalized = $this->normalizeLabel($nombre);
        if ($normalized === '') {
            return null;
        }

        $alias = $db->query(
            'SELECT alias_nombre
             FROM general_pdc_family_aliases
             WHERE activa = 1 AND alias_normalizado = ?
             LIMIT 1',
            [$normalized],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($alias) {
            return 'Ese nombre ya está registrado como alias. No puede activarse como familia operativa.';
        }

        $contractual = $db->query(
            'SELECT nombre
             FROM general_pdc_contractual_elements
             WHERE activa = 1 AND nombre_normalizado = ?
             LIMIT 1',
            [$normalized],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($contractual) {
            return 'Ese nombre ya está registrado como elemento contractual. Debe gestionarse en Contratos, no como familia.';
        }

        if ($familyId > 0) {
            $duplicate = $db->query(
                'SELECT id
                 FROM general_pdc_familias
                 WHERE id <> ? AND COALESCE(activa, 1) = 1 AND codigo = ?
                 LIMIT 1',
                [$familyId, $this->normalizeCode($nombre)],
            )->fetchColumn();
            if ($duplicate) {
                return 'Ya existe una familia activa con ese código.';
            }
        }

        return null;
    }

    private function impact(Database $db): array
    {
        $rows = $db->query(
            'SELECT f.id, f.nombre,
                    COUNT(DISTINCT r.id) AS reglas,
                    COUNT(DISTINCT a.id) AS aliases,
                    COUNT(DISTINCT e.id) AS elementos_contractuales
             FROM general_pdc_familias f
             LEFT JOIN general_pdc_activity_rules r ON r.familia_id = f.id AND r.activa = 1
             LEFT JOIN general_pdc_family_aliases a ON a.familia_id = f.id AND a.activa = 1
             LEFT JOIN general_pdc_contractual_elements e ON e.familia_id = f.id AND e.activa = 1
             GROUP BY f.id, f.nombre
             ORDER BY reglas DESC, aliases DESC, f.nombre ASC
             LIMIT 80'
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    private function audit(Database $db): array
    {
        return $db->query(
            "SELECT usuario, accion, descripcion, fecha
             FROM general_auditoria_acciones
             WHERE modulo = 'CatalogoFamilias'
             ORDER BY fecha DESC
             LIMIT 20"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function pendingDecisions(Database $db): array
    {
        return $db->query(
            "SELECT f.id, f.nombre, f.categoria, f.siempre_revision,
                    COUNT(DISTINCT r.id) AS reglas_activas
             FROM general_pdc_familias f
             LEFT JOIN general_pdc_activity_rules r ON r.familia_id = f.id AND r.activa = 1
             WHERE COALESCE(f.activa, 1) = 1
               AND COALESCE(f.siempre_revision, 0) = 1
             GROUP BY f.id, f.nombre, f.categoria, f.siempre_revision
             ORDER BY f.categoria ASC, f.nombre ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function pullFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return is_string($value) ? $value : null;
    }

    private function redirectWith(string $key, string $message): void
    {
        $_SESSION[$key] = $message;
        header('Location: /admin/matching/family-catalog');
        exit;
    }

    private function currentUser(): string
    {
        if (isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])) {
            return (string) ($_SESSION['admin_user']['usuario'] ?? 'admin');
        }

        return (string) ($_SESSION['usuario'] ?? 'admin');
    }

    private function normalizeCode(string $value): string
    {
        $normalized = $this->normalizeLabel($value);
        return str_replace(' ', '_', $normalized);
    }

    private function normalizeLabel(string $value): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtoupper(trim($value));
    }
}
