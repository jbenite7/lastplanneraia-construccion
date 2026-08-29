<?php

use App\Security\DataScope\SystemScope;

function unauthorizedRootSystemScopeFixture(): void
{
    SystemScope::forMaintenance('fixture:root');
}
