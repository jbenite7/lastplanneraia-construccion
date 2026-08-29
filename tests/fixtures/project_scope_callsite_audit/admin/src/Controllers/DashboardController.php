<?php

use App\Security\DataScope\SystemScopeRunner;

function authorizedAdminRunner($db): void
{
    (new SystemScopeRunner($db->dataScope()))->run('fixture:admin', static fn() => null);
}
