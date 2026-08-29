<?php

declare(strict_types=1);

namespace App\Security\DataScope;

enum TableScopeKind: string
{
    case Project = 'project';
    case Identity = 'identity';
    case System = 'system';
    case Unclassified = 'unclassified';
}
