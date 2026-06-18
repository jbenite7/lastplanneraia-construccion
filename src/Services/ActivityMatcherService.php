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
     * Iterates ALL source activities, computes confidence for each, and returns
     * the best match along with the top 3 candidates.
     *
     * @param string      $targetName    Raw or normalized target activity name.
     * @param string|null $targetChapter Raw or normalized target chapter.
     * @param array       $sourceActivities Array of ['name' => string, 'chapter' => ?string, ...]
     *                                      May include arbitrary extra keys (e.g., 'id', 'raw_name').
     * @return array{best: array|null, confidence: float, candidates: array}
     *         best = highest-confidence source activity (or null if no sources).
     *         confidence = score of best match.
     *         candidates = top 3 by confidence, each with ['activity' => ..., 'confidence' => float].
     */
    public function findBestMatch(
        string $targetName,
        ?string $targetChapter,
        array $sourceActivities,
    ): array {
        if (empty($sourceActivities)) {
            return ['best' => null, 'confidence' => 0.0, 'candidates' => []];
        }

        $scored = [];

        foreach ($sourceActivities as $source) {
            $srcName = $source['name'] ?? '';
            $srcChapter = $source['chapter'] ?? null;

            $confidence = $this->calculateConfidence(
                $targetName,
                $targetChapter,
                $srcName,
                $srcChapter,
            );

            $scored[] = [
                'activity' => $source,
                'confidence' => $confidence,
            ];
        }

        // Sort descending by confidence, then by name for stability
        usort($scored, function ($a, $b) {
            if ($b['confidence'] !== $a['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            return strcmp($a['activity']['name'] ?? '', $b['activity']['name'] ?? '');
        });

        $best = $scored[0];
        $candidates = array_slice($scored, 0, 3);

        return [
            'best' => $best['activity'],
            'confidence' => $best['confidence'],
            'candidates' => $candidates,
        ];
    }

    /**
     * Match all target activities against source activities, classifying into tiers.
     *
     * For each target, finds the best match from sources and classifies by confidence:
     *   - identical: confidence === 1.0 AND normalized names match exactly
     *   - high:      confidence >= 0.8
     *   - medium:    confidence >= 0.5 && < 0.8
     *   - none:      confidence < 0.5
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

            $entry = [
                'target' => $target,
                'matched' => $bestSource,
                'confidence' => $confidence,
                'candidates' => $match['candidates'],
            ];

            // Check for identical: exact normalized name match
            if ($bestSource !== null && $confidence === 1.0) {
                $tgtNormalized = $this->normalizeActivityName($tgtName);
                $srcNormalized = $this->normalizeActivityName($bestSource['name'] ?? '');

                if ($tgtNormalized === $srcNormalized) {
                    $result['identical'][] = $entry;
                    continue;
                }
            }

            if ($confidence >= 0.8) {
                $result['high'][] = $entry;
            } elseif ($confidence >= 0.5) {
                $result['medium'][] = $entry;
            } else {
                $result['none'][] = $entry;
            }
        }

        return $result;
    }

    // ─── Private helpers ────────────────────────────────────────────────

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
