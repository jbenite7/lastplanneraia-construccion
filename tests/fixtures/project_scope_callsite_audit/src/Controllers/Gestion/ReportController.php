<?php

use App\Security\DataScope\SystemScopeRunner;

function authorizedReportRunner($db): void
{
    (new SystemScopeRunner($db->dataScope()))->run('fixture:report', static fn() => null);
}
