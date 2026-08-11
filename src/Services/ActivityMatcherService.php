<?php

namespace App\Services;

/**
 * Activity name normalization and fuzzy matching service.
 *
 * Normalization: strip HTML, remove accents, lowercase, trim, collapse whitespace.
 * Fuzzy matching: Levenshtein + Jaro-Winkler + Jaccard with chapter boost.
 */
class ActivityMatcherService
{
    private static ?array $thresholdsCache = null;
    private array $thresholds;

    public function __construct()
    {
        $this->thresholds = self::getThresholds();
    }

    /**
     * Read matching thresholds from DB config table, with fallback to defaults.
     * Cached in a static variable per request to avoid N+1 queries.
     *
     * @return array{high: float, medium: float, chapter: float}
     */
    public static function getThresholds(): array
    {
        if (self::$thresholdsCache !== null) {
            return self::$thresholdsCache;
        }

        $defaults = ['high' => 0.90, 'medium' => 0.70, 'chapter' => 0.70];

        try {
            $db = \Database::getInstance();
            $stmt = $db->query("SELECT config_key, config_value FROM general_matching_config");
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            if (!empty($rows)) {
                self::$thresholdsCache = [
                    'high'    => (float)($rows['high_threshold'] ?? $defaults['high']),
                    'medium'  => (float)($rows['medium_threshold'] ?? $defaults['medium']),
                    'chapter' => (float)($rows['chapter_threshold'] ?? $defaults['chapter']),
                ];
                return self::$thresholdsCache;
            }
        } catch (\Exception $e) {
            // Table doesn't exist or DB error — use defaults
        }

        self::$thresholdsCache = $defaults;
        return $defaults;
    }

    // ─── Normalization ──────────────────────────────────────────────────

    /**
     * Normalize a raw activity name for comparison.
     *
     * Steps: strip HTML tags → remove accents → lowercase → trim → collapse whitespace.
     *
     * @param string $raw The raw activity name (may contain HTML, accents, extra spaces).
     * @return string The normalized name.
     */
    public function normalizeActivityName(string $raw): string
    {
        $name = $raw;

        // Strip HTML tags
        $name = strip_tags($name);

        // Remove chapter text [Capítulo: ...] — it's extracted separately by extractChapter()
        $name = preg_replace('/\[Cap[ií]tulo\s*:\s*.+?\]/iu', '', $name);

        // Remove accents via iconv transliteration
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        // Lowercase
        $name = strtolower($name);

        // Trim whitespace
        $name = trim($name);

        // Collapse multiple spaces/newlines/tabs into a single space
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove trailing commas left over after chapter removal
        $name = rtrim($name, ',');

        return $name;
    }

    /**
     * Extract chapter text from a raw activity name.
     *
     * Looks for patterns like:
     *   - [Capítulo: PREOPERATIVOS]
     *   - <small>[Capítulo: PREOPERATIVOS]</small>
     *   - <small> [Capítulo: PREOPERATIVOS] </small>
     *
     * @param string $raw The raw activity name (may contain chapter markers).
     * @return string|null The extracted chapter name, normalized (lowercase, trimmed), or null.
     */
    public function extractChapter(string $raw): ?string
    {
        // Match [Capítulo: ...] with optional surrounding <small> tags
        // The inner content is captured, allowing any whitespace around the colon and brackets
        if (preg_match('/\[Cap[ií]tulo\s*:\s*(.+?)\]/iu', $raw, $matches)) {
            $chapter = $matches[1];

            // Strip any remaining HTML from the chapter value
            $chapter = strip_tags($chapter);

            // Normalize: remove accents, lowercase, trim
            $chapter = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $chapter);
            $chapter = strtolower(trim($chapter));

            return $chapter !== '' ? $chapter : null;
        }

        return null;
    }

    /**
     * Build a normalized comparison tuple from a raw activity name and optional chapter.
     *
     * @param string     $name    The raw activity name.
     * @param string|null $chapter Optional raw chapter string (if already extracted).
     * @return array{name: string, chapter: string|null}
     */
    public function normalizeForComparison(string $name, ?string $chapter): array
    {
        return [
            'name' => $this->normalizeActivityName($name),
            'chapter' => $chapter !== null
                ? $this->normalizeActivityName($chapter)
                : null,
        ];
    }

    // ─── Fuzzy matching algorithms ──────────────────────────────────────

    /**
     * Compute Levenshtein similarity ratio (0.0 to 1.0).
     *
     * Uses PHP's built-in levenshtein() then normalizes:
     *   ratio = 1.0 - (distance / max(len1, len2))
     *
     * Edge cases: identical strings → 1.0, both empty → 1.0.
     *
     * @param string $s1 First string (already normalized).
     * @param string $s2 Second string (already normalized).
     * @return float Similarity ratio in [0.0, 1.0].
     */
    public function levenshteinRatio(string $s1, string $s2): float
    {
        if ($s1 === $s2) {
            return 1.0;
        }

        $maxLen = max(strlen($s1), strlen($s2));

        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($s1, $s2);

        return 1.0 - ($distance / $maxLen);
    }

    /**
     * Compute Jaro-Winkler similarity (0.0 to 1.0).
     *
     * Jaro similarity = (m/|s1| + m/|s2| + (m-t)/m) / 3
     * where m = matching characters, t = transpositions/2.
     *
     * Winkler boost: if first 4 chars match, multiply by (1 + p * (1 - Jaro))
     * with p = 0.1 (standard prefix scale).
     *
     * @param string $s1 First string (already normalized).
     * @param string $s2 Second string (already normalized).
     * @return float Jaro-Winkler similarity in [0.0, 1.0].
     */
    public function jaroWinklerSimilarity(string $s1, string $s2): float
    {
        if ($s1 === $s2) {
            return 1.0;
        }

        $len1 = strlen($s1);
        $len2 = strlen($s2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        // Match window = max(floor(max(len1,len2)/2) - 1, 0)
        $matchWindow = max((int) floor(max($len1, $len2) / 2) - 1, 0);

        $s1Matches = array_fill(0, $len1, false);
        $s2Matches = array_fill(0, $len2, false);

        $matches = 0;
        $transpositions = 0;

        for ($i = 0; $i < $len1; $i++) {
            $low = max(0, $i - $matchWindow);
            $high = min($i + $matchWindow + 1, $len2);

            for ($j = $low; $j < $high; $j++) {
                if ($s2Matches[$j] || $s1[$i] !== $s2[$j]) {
                    continue;
                }

                $s1Matches[$i] = true;
                $s2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        // Count transpositions
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (! $s1Matches[$i]) {
                continue;
            }

            while (! $s2Matches[$k]) {
                $k++;
            }

            if ($s1[$i] !== $s2[$k]) {
                $transpositions++;
            }

            $k++;
        }

        $transpositions = (int) floor($transpositions / 2);

        $jaro = ($matches / $len1 + $matches / $len2 + ($matches - $transpositions) / $matches) / 3.0;

        // Winkler prefix check (up to 4 matching prefix chars)
        $prefixLen = 0;
        $limit = min(4, min($len1, $len2));
        for ($i = 0; $i < $limit; $i++) {
            if ($s1[$i] === $s2[$i]) {
                $prefixLen++;
            } else {
                break;
            }
        }

        $p = 0.1; // standard Winkler prefix scale

        return $jaro + $prefixLen * $p * (1.0 - $jaro);
    }

    /**
     * Compute token-based Jaccard similarity.
     *
     * Jaccard = |intersection(tokens1, tokens2)| / |union(tokens1, tokens2)|
     *
     * Tokens are space-split. Empty tokens after split are ignored.
     *
     * @param string $s1 First string (already normalized).
     * @param string $s2 Second string (already normalized).
     * @return float Jaccard index in [0.0, 1.0].
     */
    public function tokenJaccardSimilarity(string $s1, string $s2): float
    {
        if ($s1 === $s2) {
            return 1.0;
        }

        $tokens1 = $this->tokenize($s1);
        $tokens2 = $this->tokenize($s2);

        if (empty($tokens1) && empty($tokens2)) {
            return 1.0;
        }

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $set1 = array_unique($tokens1);
        $set2 = array_unique($tokens2);

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        $interCount = count($intersection);
        $unionCount = count($union);

        if ($unionCount === 0) {
            return 1.0;
        }

        return $interCount / $unionCount;
    }

    /**
     * Calculate a confidence score between two activities using weighted combination.
     *
     * Formula:
     *   base = 0.4 * levenshtein + 0.3 * jaroWinkler + 0.3 * jaccard
     *   chapter_boost = +0.10 if both chapters are non-null and equal
     *   final = min(1.0, base + chapter_boost)
     *
     * Names are normalized via normalizeActivityName() before comparison.
     * Chapters are compared as-is (caller should provide already-normalized or raw).
     * Null chapters receive no boost.
     *
     * @param string      $name1    Raw or normalized name of activity 1.
     * @param string|null $chapter1 Raw or normalized chapter of activity 1.
     * @param string      $name2    Raw or normalized name of activity 2.
     * @param string|null $chapter2 Raw or normalized chapter of activity 2.
     * @return float Confidence score in [0.0, 1.0].
     */
    public function calculateConfidence(
        string $name1,
        ?string $chapter1,
        string $name2,
        ?string $chapter2,
    ): float {
        $n1 = $this->normalizeActivityName($name1);
        $n2 = $this->normalizeActivityName($name2);

        // Identical normalized names → perfect match (1.0)
        if ($n1 === $n2) {
            return 1.0;
        }

        $lev = $this->levenshteinRatio($n1, $n2);
        $jw = $this->jaroWinklerSimilarity($n1, $n2);
        $jac = $this->tokenJaccardSimilarity($n1, $n2);

        $base = 0.4 * $lev + 0.3 * $jw + 0.3 * $jac;

        $chapterBoost = 0.0;
        if ($chapter1 !== null && $chapter2 !== null) {
            $ch1 = $this->normalizeActivityName($chapter1);
            $ch2 = $this->normalizeActivityName($chapter2);
            if ($ch1 === $ch2) {
                $chapterBoost = 0.10;
            }
        }

        return min(1.0, $base + $chapterBoost);
    }

    /**
     * Find the best matching activity from a source list for a given target.
     *
     * Uses a 4-stage cascade:
     *   Stage 0 — Chapter hard-filter: only sources matching the target chapter.
     *   Stage 1 — Confidence scoring: weighted fuzzy match, keep top 5.
     *   Stage 2 — Location scoring: build-direction-aware proximity, keep top 2.
     *   Stage 3 — Date tiebreaker: earliest Fecha_Inicio wins.
     *
     * @param string      $targetName    Raw or normalized target activity name.
     * @param string|null $targetChapter Raw or normalized target chapter.
     * @param array       $sourceActivities Array of ['name' => string, 'chapter' => ?string, ...]
     *                                      May include arbitrary extra keys (e.g., 'id', 'raw_name').
     * @return array{best: array|null, confidence: float, candidates: array}
     *         best = highest-confidence source activity (or null if no sources).
     *         confidence = score of best match.
     *         candidates = top 5 from stage 1, each with ['activity' => ..., 'confidence' => float].
     */
    public function findBestMatch(
        string $targetName,
        ?string $targetChapter,
        array $sourceActivities,
    ): array {
        if (empty($sourceActivities)) {
            return ['best' => null, 'confidence' => 0.0, 'candidates' => []];
        }

        // ── Stage 0: Chapter filter (HARD) ──────────────────────────────
        $chapterFiltered = [];
        foreach ($sourceActivities as $source) {
            $sourceChapter = $source['chapter'] ?? null;
            if ($this->chapterMatch($targetChapter, $sourceChapter, $this->thresholds['chapter'])) {
                $chapterFiltered[] = $source;
            }
        }

        // If no source matched by chapter, skip filter (use all sources)
        $pool = !empty($chapterFiltered) ? $chapterFiltered : $sourceActivities;

        // ── Stage 1: Confidence scoring — keep top 5 ───────────────────
        $scored = [];
        foreach ($pool as $source) {
            $confidence = $this->calculateConfidence(
                $targetName,
                $targetChapter,
                $source['name'],
                $source['chapter'] ?? null,
            );
            $scored[] = [
                'activity' => $source,
                'confidence' => $confidence,
            ];
        }

        // Sort by confidence DESC, name ASC (stable tiebreaker)
        usort($scored, function ($a, $b) {
            if ($b['confidence'] !== $a['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            return strcmp($a['activity']['name'] ?? '', $b['activity']['name'] ?? '');
        });

        $top5 = array_slice($scored, 0, 5);

        // ── Stage 2: Location scoring — keep top 2 ─────────────────────
        foreach ($top5 as &$candidate) {
            $candidate['locationScore'] = $this->scoreLocation(
                ['name' => $targetName, 'chapter' => $targetChapter],
                $candidate['activity'],
            );
        }
        unset($candidate);

        usort($top5, function ($a, $b) {
            if ($b['locationScore'] !== $a['locationScore']) {
                return $b['locationScore'] <=> $a['locationScore'];
            }

            if ($b['confidence'] !== $a['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            return strcmp($a['activity']['name'] ?? '', $b['activity']['name'] ?? '');
        });

        $top2 = array_slice($top5, 0, 2);

        // ── Stage 3: Final selection — confidence DESC, then date ASC (tiebreaker) ──
        usort($top2, function ($a, $b) {
            // Primary: confidence DESC
            if ($b['confidence'] !== $a['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            // Secondary: earlier date wins (tiebreaker)
            $dateA = $a['activity']['fecha_inicio'] ?? null;
            $dateB = $b['activity']['fecha_inicio'] ?? null;

            // NULL dates lose against non-NULL dates
            if ($dateA === null && $dateB !== null) {
                return 1;
            }
            if ($dateA !== null && $dateB === null) {
                return -1;
            }
            if ($dateA !== null && $dateB !== null) {
                $cmp = strcmp($dateA, $dateB);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            // Tertiary: name ASC (stable tiebreaker)
            return strcmp($a['activity']['name'] ?? '', $b['activity']['name'] ?? '');
        });

        $winner = $top2[0];

        return [
            'best'       => $winner['activity'],
            'confidence' => $winner['confidence'],
            'candidates' => array_slice($scored, 0, 5), // top 5 from stage 1
        ];
    }

    /**
     * Match all target activities against source activities, classifying into tiers.
     *
     * For each target, finds the best match from sources and classifies by confidence:
     *   - identical: confidence === 1.0 AND normalized names match exactly
     *   - high:      confidence >= high_threshold (dynamic, default 0.90)
     *   - medium:    confidence >= medium_threshold (dynamic, default 0.70) && < high
     *   - none:      confidence < medium_threshold
     *
     * Each entry in the result arrays includes the target data, matched source, and confidence.
     *
     * @param array $targetActivities Array of ['name' => string, 'chapter' => ?string, ...]
     * @param array $sourceActivities Array of ['name' => string, 'chapter' => ?string, ...]
     * @return array{identical: array, high: array, medium: array, none: array}
     */
    public function matchAll(array $targetActivities, array $sourceActivities): array
    {
        $result = [
            'identical' => [],
            'high' => [],
            'medium' => [],
            'none' => [],
        ];

        foreach ($targetActivities as $target) {
            $tgtName = $target['name'] ?? '';
            $tgtChapter = $target['chapter'] ?? null;

            $match = $this->findBestMatch($tgtName, $tgtChapter, $sourceActivities);

            $confidence = $match['confidence'];
            $bestSource = $match['best'];
            $candidates = $match['candidates'];

            // Find the MAX confidence across all candidates (not just the cascade winner).
            // The cascade selects by location+date, but tier should reflect the best possible match.
            $maxConfidence = $confidence;
            $maxConfidenceSource = $bestSource;
            foreach ($candidates as $c) {
                if (($c['confidence'] ?? 0) > $maxConfidence) {
                    $maxConfidence = $c['confidence'];
                    $maxConfidenceSource = $c['activity'] ?? null;
                }
            }

            $entry = [
                'target' => $target,
                'matched' => $bestSource,
                'confidence' => $maxConfidence,
                'candidates' => $candidates,
            ];

            // Check for identical: exact normalized name match (use source with max confidence)
            if ($maxConfidenceSource !== null && $maxConfidence === 1.0) {
                $tgtNormalized = $this->normalizeActivityName($tgtName);
                $srcNormalized = $this->normalizeActivityName($maxConfidenceSource['name'] ?? '');

                if ($tgtNormalized === $srcNormalized) {
                    $result['identical'][] = $entry;
                    continue;
                }
            }

            if ($maxConfidence >= $this->thresholds['high']) {
                $result['high'][] = $entry;
            } elseif ($maxConfidence >= $this->thresholds['medium']) {
                $result['medium'][] = $entry;
            } else {
                $result['none'][] = $entry;
            }
        }

        return $result;
    }

    // ─── Private helpers ────────────────────────────────────────────────

    /**
     * Extract a numeric location value from an activity name.
     *
     * Parses common Spanish construction location patterns (Piso, Nivel, Etapa, etc.)
     * and returns a float for sorting. Returns null if no pattern matches.
     *
     * Patterns are checked in priority order — first match wins.
     * Word boundary anchors (\b) are used for generic codes to avoid false positives.
     *
     * @param string $activityName The raw activity name.
     * @return float|null Location value, or null if none found.
     */
    private function extractLocationValue(string $activityName): ?float
    {
        // Floor
        if (preg_match('/Piso\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Level
        if (preg_match('/Nivel\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Stage
        if (preg_match('/Etapa\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Basement (accented and unaccented)
        if (preg_match('/S[oó]tano\s*(\d+)/iu', $activityName, $m)) {
            return -(float) $m[1];
        }

        // Tower letter → number (A=1, B=2, ... Z=26)
        if (preg_match('/Torre\s*([A-Z])/iu', $activityName, $m)) {
            return (float) (ord(strtoupper($m[1])) - ord('A') + 1);
        }

        // Zone
        if (preg_match('/Zona\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Sector
        if (preg_match('/Sector\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Segment (Tramo)
        if (preg_match('/Tramo\s*(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Area with letter-digit pattern (e.g., "Area A-1") → numeric part only
        if (preg_match('/Area\s*[A-Z]-(\d+)/iu', $activityName, $m)) {
            return (float) $m[1];
        }

        // Mezzanine (exact word, case-insensitive)
        if (preg_match('/\bmezanine\b/iu', $activityName)) {
            return 0.5;
        }

        // Level code: P followed by 2+ digits at word boundary (e.g., "P01", "P12")
        if (preg_match('/\bP(\d{2,})\b/', $activityName, $m)) {
            return (float) $m[1];
        }

        // Basement code: S followed by 1-2 digits at word boundary (e.g., "S1", "S02")
        if (preg_match('/\bS(\d{1,2})\b/', $activityName, $m)) {
            return -(float) $m[1];
        }

        return null;
    }

    /**
     * Classify whether construction direction is top-down or bottom-up.
     *
     * Checks the activity name first (lowercase), then the chapter text if provided.
     * Falls back to 'bottom-up' as the default for most construction work.
     *
     * @param string $activityName The raw activity name.
     * @param string $chapterText  Optional chapter text to check as secondary source.
     * @return string 'top-down' or 'bottom-up'.
     */
    private function classifyBuildDirection(string $activityName, string $chapterText = ''): string
    {
        $nameLower = strtolower($activityName);

        // Top-down indicators in name
        if (
            str_contains($nameLower, 'impermeabiliza')
            || str_contains($nameLower, 'aseo')
            || str_contains($nameLower, 'impermeabilizacion')
            || str_contains($nameLower, 'obra civil')
        ) {
            return 'top-down';
        }

        // Bottom-up indicators in name
        $bottomUpKeywords = [
            'acabados',
            'instalaciones',
            'albañileria',
            'albanileria',
            'estructura',
            'mamposteria',
            'mampostería',
            'columna',
            'viga',
            'placa',
            'muro',
        ];

        foreach ($bottomUpKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'bottom-up';
            }
        }

        // Check chapter text as secondary source
        if ($chapterText !== '') {
            $chapterLower = strtolower($chapterText);

            if (
                str_contains($chapterLower, 'impermeabiliza')
                || str_contains($chapterLower, 'aseo')
                || str_contains($chapterLower, 'impermeabilizacion')
                || str_contains($chapterLower, 'obra civil')
            ) {
                return 'top-down';
            }

            foreach ($bottomUpKeywords as $keyword) {
                if (str_contains($chapterLower, $keyword)) {
                    return 'bottom-up';
                }
            }
        }

        return 'bottom-up';
    }

    /**
     * Score location match between target and source activities.
     *
     * Considers build direction (top-down prefers higher locations, bottom-up prefers lower).
     * Returns a value in [0, 1] representing location compatibility.
     *
     * @param array $target Target activity ['name' => string, 'chapter' => ?string, ...].
     * @param array $source Source activity ['name' => string, 'chapter' => ?string, ...].
     * @return float Location score in [0, 1].
     */
    private function scoreLocation(array $target, array $source): float
    {
        $targetLoc = $this->extractLocationValue($target['name'] ?? '');
        $sourceLoc = $this->extractLocationValue($source['name'] ?? '');

        $direction = $this->classifyBuildDirection(
            $target['name'] ?? '',
            $target['chapter'] ?? '',
        );

        // Both have location data
        if ($targetLoc !== null && $sourceLoc !== null) {
            $rawDiff = abs($targetLoc - $sourceLoc);
            $maxVal  = max(1, max($targetLoc, $sourceLoc));
            $proximityScore = 1.0 - ($rawDiff / $maxVal);

            // Apply build direction preference to break ties
            // top-down: prefer sources at or above target level
            // bottom-up: prefer sources at or below target level
            if ($direction === 'top-down') {
                $directionBonus = ($sourceLoc >= $targetLoc) ? 0.15 : -0.15;
            } else {
                $directionBonus = ($sourceLoc <= $targetLoc) ? 0.15 : -0.15;
            }

            return max(0.0, min(1.0, $proximityScore + $directionBonus));
        }

        // Target has location, source does not → penalize.
        // El `$sourceLoc === null` que acompanaba a esta condicion sobraba: el bloque
        // anterior ya retorna cuando los dos son no-nulos, asi que llegar aqui con
        // `$targetLoc !== null` implica que `$sourceLoc` es null. Comportamiento
        // identico; se quita porque PHPStan la contaba como error y su entrada de
        // baseline habia caducado (esperaba 1 aparicion y habia 2).
        if ($targetLoc !== null) {
            return 0.3;
        }

        // Target has no location, source does → weak preference for data.
        // Simetrico: si se llega aqui, `$targetLoc` ya es null por descarte.
        if ($sourceLoc !== null) {
            return 0.7;
        }

        // Neither has location → neutral
        return 0.5;
    }

    /**
     * Check if two chapter strings match above a similarity threshold.
     *
     * Both inputs are normalized via normalizeActivityName(), then compared
     * with levenshteinRatio(). Returns false if either is null or empty.
     *
     * @param string|null $targetChapter Target chapter text.
     * @param string|null $sourceChapter Source chapter text.
     * @param float       $threshold     Minimum similarity ratio (default 0.70).
     * @return bool True if chapters match at or above threshold.
     */
    private function chapterMatch(?string $targetChapter, ?string $sourceChapter, float $threshold = 0.70): bool
    {
        if ($targetChapter === null || $targetChapter === '') {
            return false;
        }

        if ($sourceChapter === null || $sourceChapter === '') {
            return false;
        }

        $normalized1 = $this->normalizeActivityName($targetChapter);
        $normalized2 = $this->normalizeActivityName($sourceChapter);

        return $this->levenshteinRatio($normalized1, $normalized2) >= $threshold;
    }

    /**
     * Tokenize a string into space-separated words, filtering empty tokens.
     *
     * @param string $s The input string.
     * @return string[] Array of tokens.
     */
    private function tokenize(string $s): array
    {
        $tokens = preg_split('/\s+/', trim($s));

        return array_values(array_filter($tokens));
    }
}
