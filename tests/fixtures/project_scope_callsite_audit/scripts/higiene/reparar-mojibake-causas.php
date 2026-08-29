<?php

use App\Security\DataScope\SystemScopeRunner;

function authorizedMaintenanceRunner($db): void
{
    (new SystemScopeRunner($db->dataScope()))->run('fixture:maintenance', static fn() => null);
}
