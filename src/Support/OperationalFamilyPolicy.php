<?php

namespace App\Support;

use PDO;
use Throwable;

class OperationalFamilyPolicy
{
    public const RCI_FAMILY = 'Red de Extinción de Incendios';

    private mixed $db;
    private ?array $familyAliases = null;
    private ?array $contractualOnly = null;

    public function __construct(mixed $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }

        try {
            $this->db = class_exists('\Database') ? \Database::getInstance() : null;
        } catch (Throwable) {
            $this->db = null;
        }
    }

    public function normalizeOperationalFamily(string $name): string
    {
        $name = trim($name);
        foreach ($this->familyAliases() as $alias => $canonical) {
            if ($this->same($name, $alias)) {
                return $canonical;
            }
        }

        return $name;
    }

    public function isContractualOnlyFamily(string $name): bool
    {
        foreach ($this->contractualOnlyMap() as $contractual => $_) {
            if ($this->same($name, $contractual)) {
                return true;
            }
        }

        return false;
    }

    public function isOperationalFamilyAllowedForListado(string $name): bool
    {
        return trim($name) !== '' && !$this->isContractualOnlyFamily($name);
    }

    public function contractualPackageHints(string $name): array
    {
        foreach ($this->contractualOnlyMap() as $contractual => $hints) {
            if ($this->same($name, $contractual)) {
                return $hints;
            }
        }

        return [];
    }

    public function contractualPackageHintsForText(string $text): array
    {
        $hints = [];
        foreach ($this->contractualOnlyMap() as $contractual => $items) {
            if (!$this->textMentionsFamily($text, $contractual)) {
                continue;
            }
            foreach ($items as $item) {
                $hints[$this->packageKey($item)] = $item + ['sourceFamily' => $contractual];
            }
        }

        return array_values($hints);
    }

    public function familyClassification(string $name): string
    {
        if ($this->isContractualOnlyFamily($name)) {
            return 'elemento_contractual';
        }
        if ($this->normalizeOperationalFamily($name) !== trim($name)) {
            return 'alias_de_familia_operativa';
        }
        if (trim($name) === '') {
            return 'dudoso';
        }

        return 'familia_operativa';
    }

    public function contractualOnlyFamilies(): array
    {
        return array_keys($this->contractualOnlyMap());
    }

    public function familyAliases(): array
    {
        if ($this->familyAliases !== null) {
            return $this->familyAliases;
        }

        $this->familyAliases = $this->loadFamilyAliasesFromDb();

        return $this->familyAliases;
    }

    private function contractualOnlyMap(): array
    {
        if ($this->contractualOnly !== null) {
            return $this->contractualOnly;
        }

        $this->contractualOnly = $this->loadContractualElementsFromDb();

        return $this->contractualOnly;
    }

    private function loadFamilyAliasesFromDb(): array
    {
        if (!$this->tableExists('general_pdc_family_aliases')) {
            return [];
        }

        try {
            $rows = $this->query(
                "SELECT a.alias_nombre, f.nombre AS canonical_nombre
                 FROM general_pdc_family_aliases a
                 INNER JOIN general_pdc_familias f ON f.id = a.familia_id
                 WHERE a.activa = 1
                 ORDER BY a.id ASC"
            );
        } catch (Throwable) {
            return [];
        }

        $aliases = [];
        foreach ($rows as $row) {
            $alias = trim((string) ($row['alias_nombre'] ?? ''));
            $canonical = trim((string) ($row['canonical_nombre'] ?? ''));
            if ($alias !== '' && $canonical !== '') {
                $aliases[$alias] = $canonical;
            }
        }

        return $aliases;
    }

    private function loadContractualElementsFromDb(): array
    {
        if (!$this->tableExists('general_pdc_contractual_elements')) {
            return [];
        }

        try {
            $rows = $this->query(
                "SELECT nombre, tipo_paquete, paquete_nombre
                 FROM general_pdc_contractual_elements
                 WHERE activa = 1
                 ORDER BY id ASC"
            );
        } catch (Throwable) {
            return [];
        }

        $elements = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['nombre'] ?? ''));
            $tipo = trim((string) ($row['tipo_paquete'] ?? ''));
            $package = trim((string) ($row['paquete_nombre'] ?? ''));
            if ($name === '' || $tipo === '' || $package === '') {
                continue;
            }
            $elements[$name][] = [
                'tipoPaquete' => $tipo,
                'paqueteNombre' => $package,
            ];
        }

        return $elements;
    }

    private function tableExists(string $table): bool
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $rows = $this->query(
                'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table],
            );

            return (int) ($rows[0]['total'] ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function query(string $sql, array $params = []): array
    {
        if ($this->db instanceof PDO) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (is_object($this->db) && method_exists($this->db, 'query')) {
            return $this->db->query($sql, $params)->fetchAll();
        }

        return [];
    }

    private function textMentionsFamily(string $text, string $family): bool
    {
        $text = $this->normalize($text);
        $family = $this->normalize($family);
        if ($text === '' || $family === '') {
            return false;
        }
        if (str_contains($text, $family)) {
            return true;
        }
        if (str_starts_with($family, 'MANO DE OBRA ')) {
            return str_contains($text, 'MANO DE OBRA')
                && $this->containsEnoughFamilyTokens($text, str_replace('MANO DE OBRA ', '', $family));
        }

        return $this->containsEnoughFamilyTokens($text, $family);
    }

    private function containsEnoughFamilyTokens(string $text, string $family): bool
    {
        $tokens = array_values(array_filter(
            explode(' ', $family),
            static fn(string $token): bool => mb_strlen($token, 'UTF-8') >= 5
        ));
        if (empty($tokens)) {
            return false;
        }

        $matches = 0;
        foreach ($tokens as $token) {
            if (str_contains($text, $token)) {
                $matches++;
            }
        }

        return $matches >= min(2, count($tokens));
    }

    private function same(string $left, string $right): bool
    {
        return $this->normalize($left) === $this->normalize($right);
    }

    private function packageKey(array $item): string
    {
        return $this->normalize(($item['tipoPaquete'] ?? '') . '|' . ($item['paqueteNombre'] ?? ''));
    }

    private function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtoupper(trim($value));
    }
}
