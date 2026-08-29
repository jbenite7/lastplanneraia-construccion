<?php

use App\Security\DataScope\SystemScopeRunner;

function unauthorizedRunnerFixture($db): void
{
    (new SystemScopeRunner($db->dataScope()))->run('fixture:unauthorized', static fn() => null);
}
