<?php

namespace App\Support;

use Database;
use PDO;

class FamilyCatalogStatusResolver
{
    public const CREATES_ACTIVITIES = 'creates_activities';
    public const MANAGED_IN_CONTRACTS = 'managed_in_contracts';
    public const ALIAS_OF = 'alias_of';
    public const NEEDS_DECISION = 'needs_decision';
    public const DO_NOT_USE = 'do_not_use';

    private array $labels = [
        self::CREATES_ACTIVITIES => 'Crea actividades',
        self::MANAGED_IN_CONTRACTS => 'Se gestiona en Contratos',
        self::ALIAS_OF => 'Es otro nombre de...',
        self::NEEDS_DECISION => 'Necesita decisión',
        self::DO_NOT_USE => 'No usar',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    public function statusForFamily(array $family): array
    {
        $familyId = (int) ($family['id'] ?? 0);
        $name = (string) ($family['nombre'] ?? '');
        $active = (int) ($family['activa'] ?? 1) === 1;
        $review = (int) ($family['siempre_revision'] ?? 0) === 1;
        $ruleCount = $familyId > 0 ? $this->activeRuleCount($familyId) : 0;
        $optionInfo = $familyId > 0 ? $this->contractOptionInfo($familyId) : ['options' => 0, 'items' => 0, 'hint' => ''];
        $alias = $this->activeAliasForName($name);
        $contractual = $this->activeContractualElementForFamily($familyId, $name);

        if ($alias !== null) {
            return $this->status(self::ALIAS_OF, [
                'reason' => 'Ese nombre ya apunta a una familia canónica.',
                'next_action' => 'Usa la familia canónica o ajusta el alias desde Admin.',
                'canonical_family' => (string) ($alias['familia_nombre'] ?? ''),
                'admin_action' => 'Revisar alias',
                'has_rules' => $ruleCount > 0,
                'has_contract_options' => ((int) $optionInfo['items']) > 0,
            ]);
        }

        if ($contractual !== null) {
            return $this->status(self::MANAGED_IN_CONTRACTS, [
                'reason' => 'El catálogo lo clasifica como elemento contractual, no como familia operativa de Listado.',
                'next_action' => 'Gestiona el paquete desde Contratos.',
                'package_hint' => $this->packageHintFromContractual($contractual),
                'canonical_family' => (string) ($contractual['familia_nombre'] ?? ''),
                'admin_action' => 'Ajustar elemento contractual',
                'has_rules' => $ruleCount > 0,
                'has_contract_options' => ((int) $optionInfo['items']) > 0,
            ]);
        }

        if ($active && $review) {
            return $this->status(self::NEEDS_DECISION, [
                'reason' => 'Está marcada para revisión humana antes de aplicarse automáticamente.',
                'next_action' => 'Admin decide si queda en Listado o pasa a Contratos.',
                'admin_action' => 'Resolver decisión pendiente',
                'has_rules' => $ruleCount > 0,
                'has_contract_options' => ((int) $optionInfo['items']) > 0,
            ]);
        }

        if ($active && $ruleCount > 0) {
            $hasOptions = ((int) $optionInfo['items']) > 0;
            return $this->status(self::CREATES_ACTIVITIES, [
                'reason' => $hasOptions
                    ? 'Tiene reglas activas y opciones contractuales configuradas.'
                    : 'Tiene reglas activas para Listado, pero aún no tiene paquetes de Contratos configurados.',
                'next_action' => $hasOptions
                    ? 'Puede usarse en el flujo normal Listado -> Contratos -> PDC.'
                    : 'Admin debe crear una opción contractual antes de automatizar paquetes.',
                'package_hint' => (string) $optionInfo['hint'],
                'admin_action' => $hasOptions ? 'Revisar reglas si cambia el criterio' : 'Crear opción contractual',
                'has_rules' => true,
                'has_contract_options' => $hasOptions,
            ]);
        }

        if ($active) {
            return $this->status(self::NEEDS_DECISION, [
                'reason' => 'Está activa, pero no tiene reglas de detección activas.',
                'next_action' => 'Admin debe crear o reasignar una regla antes de usarla automáticamente.',
                'admin_action' => 'Crear o reasignar reglas',
                'has_rules' => false,
                'has_contract_options' => ((int) $optionInfo['items']) > 0,
            ]);
        }

        return $this->status(self::DO_NOT_USE, [
            'reason' => 'Está fuera del flujo automático y no tiene una explicación contractual o de alias vigente.',
            'next_action' => 'Admin debe reactivarla, convertirla en alias o moverla a Contratos.',
            'admin_action' => 'Revisar clasificación',
            'has_rules' => $ruleCount > 0,
            'has_contract_options' => ((int) $optionInfo['items']) > 0,
        ]);
    }

    public function statusForAlias(array $alias): array
    {
        $active = (int) ($alias['activa'] ?? 1) === 1;
        if (!$active) {
            return $this->status(self::DO_NOT_USE, [
                'reason' => 'El alias está fuera del matching automático.',
                'next_action' => 'Admin puede activarlo si vuelve a ser válido.',
                'canonical_family' => (string) ($alias['familia_nombre'] ?? ''),
                'admin_action' => 'Revisar alias',
            ]);
        }

        return $this->status(self::ALIAS_OF, [
            'reason' => 'Cuando aparece este nombre, el sistema usa la familia canónica.',
            'next_action' => 'No crear otra familia con este nombre.',
            'canonical_family' => (string) ($alias['familia_nombre'] ?? ''),
            'admin_action' => 'Mantener alias o reasignarlo',
        ]);
    }

    public function statusForContractualElement(array $element): array
    {
        $active = (int) ($element['activa'] ?? 1) === 1;
        if (!$active) {
            return $this->status(self::DO_NOT_USE, [
                'reason' => 'El elemento contractual no está disponible para nuevas sugerencias.',
                'next_action' => 'Admin puede activarlo o reemplazarlo por un paquete vigente.',
                'package_hint' => $this->packageHintFromContractual($element),
                'admin_action' => 'Revisar elemento contractual',
            ]);
        }

        return $this->status(self::MANAGED_IN_CONTRACTS, [
            'reason' => 'Se debe gestionar como paquete o recurso contractual.',
            'next_action' => 'Asignar o confirmar paquete desde Contratos.',
            'package_hint' => $this->packageHintFromContractual($element),
            'canonical_family' => (string) ($element['familia_nombre'] ?? ''),
            'admin_action' => 'Mantener o ajustar paquete contractual',
        ]);
    }

    public function findContractualElementForText(string $text): ?array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return null;
        }

        $rows = $this->db->query(
            'SELECT e.*, f.nombre AS familia_nombre
             FROM general_pdc_contractual_elements e
             LEFT JOIN general_pdc_familias f ON f.id = e.familia_id
             WHERE COALESCE(e.activa, 1) = 1
             ORDER BY CHAR_LENGTH(e.nombre_normalizado) DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $candidate = (string) ($row['nombre_normalizado'] ?? self::normalize((string) ($row['nombre'] ?? '')));
            if ($candidate !== '' && ($candidate === $normalized || str_contains($normalized, $candidate))) {
                $row['catalog_status'] = $this->statusForContractualElement($row);
                return $row;
            }
        }

        return null;
    }

    public static function normalize(string $value): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtoupper(trim($value));
    }

    private function status(string $key, array $extra): array
    {
        return array_merge([
            'status_key' => $key,
            'label' => $this->labels[$key] ?? $key,
            'reason' => '',
            'next_action' => '',
            'package_hint' => '',
            'canonical_family' => '',
            'admin_action' => '',
            'has_rules' => false,
            'has_contract_options' => false,
        ], $extra);
    }

    private function activeRuleCount(int $familyId): int
    {
        return (int) $this->db->query(
            'SELECT COUNT(*)
             FROM general_pdc_activity_rules
             WHERE familia_id = ? AND COALESCE(activa, 1) = 1',
            [$familyId],
        )->fetchColumn();
    }

    private function contractOptionInfo(int $familyId): array
    {
        $row = $this->db->query(
            'SELECT COUNT(DISTINCT o.id) AS options_count,
                    COUNT(i.id) AS items_count,
                    GROUP_CONCAT(DISTINCT i.paquete_nombre ORDER BY i.orden ASC SEPARATOR ", ") AS package_hint
             FROM general_pdc_family_contract_options o
             LEFT JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
             WHERE o.familia_id = ? AND COALESCE(o.activa, 1) = 1',
            [$familyId],
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'options' => (int) ($row['options_count'] ?? 0),
            'items' => (int) ($row['items_count'] ?? 0),
            'hint' => mb_substr((string) ($row['package_hint'] ?? ''), 0, 180),
        ];
    }

    private function activeAliasForName(string $name): ?array
    {
        $normalized = self::normalize($name);
        if ($normalized === '') {
            return null;
        }

        $row = $this->db->query(
            'SELECT a.*, f.nombre AS familia_nombre
             FROM general_pdc_family_aliases a
             INNER JOIN general_pdc_familias f ON f.id = a.familia_id
             WHERE COALESCE(a.activa, 1) = 1 AND a.alias_normalizado = ?
             LIMIT 1',
            [$normalized],
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function activeContractualElementForFamily(int $familyId, string $name): ?array
    {
        $normalized = self::normalize($name);
        $row = $this->db->query(
            'SELECT e.*, f.nombre AS familia_nombre
             FROM general_pdc_contractual_elements e
             LEFT JOIN general_pdc_familias f ON f.id = e.familia_id
             WHERE COALESCE(e.activa, 1) = 1
               AND (e.familia_id = ? OR e.nombre_normalizado = ?)
             ORDER BY e.familia_id DESC
             LIMIT 1',
            [$familyId, $normalized],
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function packageHintFromContractual(array $element): string
    {
        return trim(implode(' / ', array_filter([
            (string) ($element['tipo_paquete'] ?? ''),
            (string) ($element['paquete_nombre'] ?? ''),
        ])));
    }
}
