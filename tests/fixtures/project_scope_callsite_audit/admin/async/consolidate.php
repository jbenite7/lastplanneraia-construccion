<?php

use App\Security\DataScope\SystemScopeRunner;

function authorizedAsyncRunner($db): void
{
    (new SystemScopeRunner($db->dataScope()))->run('fixture:async', static fn() => null);
}
