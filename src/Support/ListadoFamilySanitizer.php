<?php

namespace App\Support;

class ListadoFamilySanitizer
{
    public function contextHint(string $text): string
    {
        $clean = $this->plainText($text);
        $patterns = [
            '/\b(Nivel\s*[A-Z0-9]+)\b/iu',
            '/\b(Piso\s*[A-Z0-9]+)\b/iu',
            '/\b(S[oó]tano\s*[A-Z0-9]+)\b/iu',
            '/\b(Torre\s*[A-Z0-9]+)\b/iu',
            '/\b(Zona\s*[^,\]]+)\b/iu',
            '/\b(Frente\s*[^,\]]+)\b/iu',
            '/\b(Sector\s*[^,\]]+)\b/iu',
            '/\b(Bloque\s*[^,\]]+)\b/iu',
            '/\b(Etapa\s*[^,\]]+)\b/iu',
            '/\b(Eje(?:s)?\s*[A-Z0-9,\sY-]+)\b/iu',
            '/\b(Apto\.?\s*[A-Z0-9-]+)\b/iu',
            '/\b(Apartamento\s*[A-Z0-9-]+)\b/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean, $matches) === 1) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    public function normalizeFamilyLabel(string $candidate, string $family): string
    {
        return $this->isFamilyWithOnlyContextSuffix($candidate, $family)
            ? $this->cleanLabel($family)
            : $this->cleanLabel($candidate);
    }

    public function isFamilyWithOnlyContextSuffix(string $candidate, string $family): bool
    {
        $candidate = $this->cleanLabel($candidate);
        $family = $this->cleanLabel($family);
        if ($candidate === '' || $family === '') {
            return false;
        }

        $candidateKey = $this->key($candidate);
        $familyKey = $this->key($family);
        if ($candidateKey === $familyKey) {
            return false;
        }
        if (!str_starts_with($candidateKey, $familyKey)) {
            return false;
        }

        $suffix = trim(mb_substr($candidate, mb_strlen($family, 'UTF-8'), null, 'UTF-8'));
        $suffix = trim($suffix, " \t\n\r\0\x0B-_,.");
        if ($suffix === '') {
            return false;
        }

        return $this->isContextOnlyText($suffix);
    }

    public function stripContextOnlyParts(string $label): string
    {
        $clean = $this->cleanLabel($label);
        $clean = preg_replace('/\bNivel\s*[A-Z0-9]+\b/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\bPiso\s*[A-Z0-9]+\b/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\bZona\s*[^,\]]+/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\bTorre\s*[A-Z0-9]+/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean, " \t\n\r\0\x0B-_,.");
    }

    private function isContextOnlyText(string $text): bool
    {
        $clean = $this->stripContextOnlyParts($text);
        $clean = preg_replace('/\b(DE|DEL|EN|Y|A|LA|EL|LOS|LAS)\b/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean, " \t\n\r\0\x0B-_,.") === '';
    }

    private function cleanLabel(string $text): string
    {
        $clean = $this->plainText($text);
        $clean = preg_replace('/\[Cap[ií]tulo:\s*[^\]]+\]/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean, " \t\n\r\0\x0B,");
    }

    private function plainText(string $text): string
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $plain) ?? $plain);
    }

    private function key(string $text): string
    {
        $key = mb_strtolower($this->cleanLabel($text), 'UTF-8');
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;

        return trim($key);
    }
}
