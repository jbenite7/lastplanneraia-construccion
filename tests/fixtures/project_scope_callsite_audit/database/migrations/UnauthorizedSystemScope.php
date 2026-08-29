<?php

use App\Security\DataScope\SystemScope;

function unauthorizedSystemScopeFactory(): void
{
    SystemScope::forMaintenance('fixture:migration');
}
