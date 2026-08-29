<?php

declare(strict_types=1);

/** @return list<array{call: string, line: int}> */
function phpTestExecutableDdlCalls(string $source): array
{
    $tokens = token_get_all($source);
    $ddlVariables = [];
    $executionCalls = [
        'exec',
        'execsql',
        'mysqli_query',
        'mysql_query',
        'prepare',
        'query',
        'queryforprojects',
        'querywithproject',
    ];
    $ddlPattern = '/[\'\"]\s*(?:CREATE|DROP|ALTER|TRUNCATE)\s+(?:TEMPORARY\s+)?TABLE\b/i';

    $nextSignificant = static function (array $allTokens, int $start): ?int {
        for ($index = $start, $count = count($allTokens); $index < $count; $index++) {
            $token = $allTokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $index;
        }

        return null;
    };
    $collectExpression = static function (array $allTokens, int $start, string $terminator): string {
        $expression = '';
        $depth = 0;
        for ($index = $start, $count = count($allTokens); $index < $count; $index++) {
            $token = $allTokens[$index];
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ($text === '(' || $text === '[' || $text === '{') {
                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                if ($depth === 0 && $text === $terminator) {
                    break;
                }
                $depth--;
            }
            if ($depth === 0 && $text === $terminator) {
                break;
            }
            $expression .= $text;
        }

        return $expression;
    };

    $findings = [];
    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_VARIABLE) {
            $equals = $nextSignificant($tokens, $index + 1);
            if ($equals !== null && $tokens[$equals] === '=') {
                $expression = $collectExpression($tokens, $equals + 1, ';');
                $ddlVariables[$token[1]] = preg_match($ddlPattern, $expression) === 1;
            }
        }

        if (!is_array($token) || $token[0] !== T_STRING || !in_array(strtolower($token[1]), $executionCalls, true)) {
            continue;
        }
        $open = $nextSignificant($tokens, $index + 1);
        if ($open === null || $tokens[$open] !== '(') {
            continue;
        }
        $arguments = $collectExpression($tokens, $open + 1, ')');
        $usesDdlVariable = false;
        foreach ($ddlVariables as $variable => $containsDdl) {
            if ($containsDdl && preg_match('/' . preg_quote($variable, '/') . '\b/', $arguments) === 1) {
                $usesDdlVariable = true;
                break;
            }
        }
        if (preg_match($ddlPattern, $arguments) === 1 || $usesDdlVariable) {
            $findings[] = ['call' => $token[1], 'line' => $token[2]];
        }
    }

    return $findings;
}

function phpTestDeclaredLevel(string $source): ?string
{
    if (preg_match('/^\s*\/\/\s*@requiere:\s*([a-z-]+)\s*$/m', $source, $match) === 1) {
        return $match[1];
    }
    if (preg_match('/#\[Group\(\s*[\'\"]([a-z-]+)[\'\"]\s*\)\]/', $source, $match) === 1) {
        return $match[1];
    }

    return null;
}

/**
 * @param array<string, string> $testLevels path => declared level
 * @return list<array{file: string, level: string, calls: list<array{call: string, line: int}>}>
 */
function phpTestDdlLevelViolations(array $testLevels): array
{
    $violations = [];
    foreach ($testLevels as $path => $level) {
        $source = @file_get_contents($path);
        if ($source === false) {
            $violations[] = [
                'file' => $path,
                'level' => $level,
                'calls' => [['call' => 'unreadable', 'line' => 0]],
            ];
            continue;
        }
        $calls = phpTestExecutableDdlCalls($source);
        if ($calls !== [] && $level !== 'admin-db') {
            $violations[] = ['file' => $path, 'level' => $level, 'calls' => $calls];
        }
    }

    return $violations;
}
