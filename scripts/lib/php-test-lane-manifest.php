<?php

declare(strict_types=1);

final class PhpTestLaneManifest
{
    /** @return array<string, int> */
    public static function levels(): array
    {
        return [
            'puro' => 0,
            'db' => 1,
            'http' => 2,
            'datos-proyecto' => 3,
            'admin-db' => 4,
        ];
    }

    public static function select(string $requested, string $declared): bool
    {
        $levels = self::levels();

        if (!array_key_exists($requested, $levels) || !array_key_exists($declared, $levels)) {
            return false;
        }

        if ($requested === 'admin-db' || $declared === 'admin-db') {
            return $requested === $declared;
        }

        return $levels[$requested] >= $levels[$declared];
    }

    /** @param array<string, mixed> $levels
     *  @return list<string>
     */
    public static function validateDeclaredLevels(array $levels): array
    {
        $knownLevels = self::levels();
        $invalid = [];

        foreach ($levels as $path => $level) {
            if (!is_string($level) || !array_key_exists($level, $knownLevels)) {
                $invalid[] = $path;
            }
        }

        sort($invalid, SORT_STRING);

        return $invalid;
    }
}
