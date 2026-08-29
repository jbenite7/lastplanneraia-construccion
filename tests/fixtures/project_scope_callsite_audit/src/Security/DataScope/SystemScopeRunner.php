<?php

use App\Security\DataScope\SystemScope;

function authorizedSystemScopeFactory(): void
{
    SystemScope::forMaintenance('fixture:runner');
}
