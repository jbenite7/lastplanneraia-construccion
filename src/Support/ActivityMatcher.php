<?php

namespace App\Support;

use PDO;

class ActivityMatcher
{
    private const AUTO_CONFIDENCE_THRESHOLD = 70;

    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    public function loadRules(): array
    {
        $stmt = $this->db->query(
            "SELECT r.id, r.familia_id, r.patron_regex, r.modalidad_sugerida, r.confianza, r.prioridad,
                    f.codigo AS familia_codigo, f.nombre AS familia_nombre, f.categoria, f.siempre_revision
             FROM general_pdc_activity_rules r
             INNER JOIN general_pdc_familias f ON f.id = r.familia_id
             WHERE r.activa = 1
             ORDER BY r.prioridad DESC, r.confianza DESC, r.id ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function matchActivity(array $activity, array $rules): ?array
    {
        $normalized = $this->normalizeActivityText((string) ($activity['Actividad'] ?? ''));
        if ($normalized === '') {
            return null;
        }

        $leafName = $this->extractLeafName($normalized);
        if ($this->isJmcCode($leafName)) {
            return null;
        }

        // Stage 0: Filter rules by chapter context
        $filteredRules = $this->filterRulesByChapter($activity, $rules);

        // Try matching with filtered rules first
        $match = $this->tryMatchCascade($normalized, $leafName, $activity, $filteredRules);
        if ($match !== null) {
            $match['chapterFiltered'] = ($filteredRules !== $rules);
            return $match;
        }

        // Fallback: if filtering happened but no match found, try ALL rules
        if ($filteredRules !== $rules) {
            $fallbackMatch = $this->tryMatchCascade($normalized, $leafName, $activity, $rules);
            if ($fallbackMatch !== null) {
                $fallbackMatch['chapterFiltered'] = false;
                $fallbackMatch['reviewRequired'] = true;
                $fallbackMatch['reviewReason'] = 'Match encontrado via fallback sin filtro de capítulo — verificar asignación de familia.';
                return $fallbackMatch;
            }
        }

        return null;
    }

    /**
     * Run the 3-tier matching cascade (leafName → breadcrumb → __capitulo) against a set of rules.
     *
     * @param string $normalized  Normalized activity text
     * @param string $leafName    Extracted leaf name
     * @param array  $activity    Original activity array (for __capitulo access)
     * @param array  $rules       Rules to match against (may be filtered or all)
     * @return array|null Match result or null
     */
    private function tryMatchCascade(string $normalized, string $leafName, array $activity, array $rules): ?array
    {
        if ($leafName !== '') {
            $match = $this->matchAgainstText($leafName, $rules);
            if ($match !== null) {
                $match['matchedBy'] = 'nombre';
                $match['breadcrumbLevel'] = null;
                return $match;
            }
        }

        foreach ($this->extractChapterHierarchy($normalized) as $index => $chapterLevel) {
            $match = $this->matchAgainstText($chapterLevel, $rules);
            if ($match !== null) {
                $match['matchedBy'] = 'breadcrumb';
                $match['breadcrumbLevel'] = $index + 1;
                return $match;
            }
        }

        $parentChapter = (string) ($activity['__capitulo'] ?? '');
        if ($parentChapter !== '') {
            $match = $this->matchAgainstText($parentChapter, $rules);
            if ($match !== null) {
                $match['matchedBy'] = 'capitulo';
                $match['breadcrumbLevel'] = null;
                return $match;
            }
        }

        return null;
    }

    /**
     * Filter rules by chapter context extracted from the activity.
     *
     * Extracts all chapter sources (breadcrumb hierarchy + positional __capitulo),
     * looks up matching categories in general_pdc_chapter_category_map,
     * and filters rules to those in matching categories.
     *
     * Soft filter: if no chapter sources found, no categories matched, or
     * filtered set is empty, returns the original rules unchanged.
     *
     * @param array $activity Activity array with 'Actividad' and optional '__capitulo'
     * @param array $rules    All loaded rules (each has 'categoria' key)
     * @return array Filtered rules (subset) or original rules if no filter applies
     */
    private function filterRulesByChapter(array $activity, array $rules): array
    {
        // 1. Extract all chapter sources
        $normalized = $this->normalizeActivityText((string) ($activity['Actividad'] ?? ''));
        $chapterSources = $this->extractChapterHierarchy($normalized);

        $parentChapter = (string) ($activity['__capitulo'] ?? '');
        if ($parentChapter !== '') {
            $chapterSources[] = $this->normalizeActivityText($parentChapter);
        }

        // No chapter info at all → no filter
        if (empty($chapterSources)) {
            return $rules;
        }

        // 2. Load chapter-to-category mappings from DB
        $mappings = $this->loadChapterCategoryMap();
        if (empty($mappings)) {
            return $rules;
        }

        // 3. Match chapter sources against keywords (substring match)
        $matchedCategories = [];
        foreach ($mappings as $mapping) {
            $keyword = $this->normalizeActivityText($mapping['chapter_keyword']);
            foreach ($chapterSources as $source) {
                if ($keyword !== '' && mb_stripos($source, $keyword) !== false) {
                    $matchedCategories[$mapping['categoria']] = true;
                    break;
                }
            }
        }

        // No categories matched → no filter
        if (empty($matchedCategories)) {
            return $rules;
        }

        // 4. Filter rules to matching categories
        $filtered = array_filter($rules, static function ($rule) use ($matchedCategories) {
            return isset($matchedCategories[$rule['categoria'] ?? '']);
        });

        // Filtered set empty → return all (soft fallback)
        if (empty($filtered)) {
            return $rules;
        }

        return array_values($filtered);
    }

    /**
     * Load chapter-to-category mappings from the database.
     *
     * @return array List of ['chapter_keyword' => string, 'categoria' => string]
     */
    private function loadChapterCategoryMap(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT chapter_keyword, categoria
                 FROM general_pdc_chapter_category_map
                 WHERE activa = 1
                 ORDER BY prioridad DESC, chapter_keyword ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Table might not exist yet — degrade gracefully to no filter
            return [];
        }
    }

    private function matchAgainstText(string $text, array $rules): ?array
    {
        $matches = [];
        foreach ($rules as $rule) {
            $pattern = (string) $rule['patron_regex'];
            if (@preg_match($pattern, $text) === 1) {
                $matches[] = $rule;
            }
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, static function ($a, $b) {
            return ((int) $b['prioridad'] <=> (int) $a['prioridad'])
                ?: ((int) $b['confianza'] <=> (int) $a['confianza']);
        });

        $best = $matches[0];
        $sameRank = array_filter($matches, static function ($item) use ($best) {
            return (int) $item['prioridad'] === (int) $best['prioridad']
                && (int) $item['confianza'] === (int) $best['confianza']
                && (int) $item['familia_id'] !== (int) $best['familia_id'];
        });

        $reviewRequired = false;
        $reviewReason = '';
        if ((int) $best['confianza'] < self::AUTO_CONFIDENCE_THRESHOLD) {
            $reviewRequired = true;
            $reviewReason = 'Confianza inferior al umbral automático.';
        } elseif (!empty($sameRank)) {
            $reviewRequired = true;
            $reviewReason = 'Actividad ambigua: coincide con más de una familia.';
        } elseif ((int) ($best['siempre_revision'] ?? 0) === 1) {
            $reviewRequired = true;
            $reviewReason = 'Familia configurada para revisión manual obligatoria.';
        }

        $best['reviewRequired'] = $reviewRequired;
        $best['reviewReason'] = $reviewReason;

        return $best;
    }

    public function normalizeActivityText(string $raw): string
    {
        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = mb_strtoupper($text, 'UTF-8');
        $text = $this->removeAccents($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function removeAccents(string $text): string
    {
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator !== null) {
                $result = $transliterator->transliterate($text);
                if ($result !== false) {
                    return $result;
                }
            }
        }

        return strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        ]);
    }

    public function extractLeafName(string $normalized): string
    {
        $pos = mb_strpos($normalized, '[CAPITULO:');
        $leaf = $pos === false ? $normalized : mb_substr($normalized, 0, $pos);

        return trim(rtrim(trim($leaf), ','));
    }

    public function extractChapterHierarchy(string $normalized): array
    {
        if (!preg_match('/\[CAPITULO:\s*([^\]]+)\]/u', $normalized, $matches)) {
            return [];
        }

        $levels = array_map('trim', explode(',', $matches[1]));

        return array_values(array_filter($levels, static fn($level) => $level !== ''));
    }

    private function isJmcCode(string $name): bool
    {
        return preg_match('/^[A-Z]{1,3}-\d{2,}/', trim($name)) === 1;
    }
}
