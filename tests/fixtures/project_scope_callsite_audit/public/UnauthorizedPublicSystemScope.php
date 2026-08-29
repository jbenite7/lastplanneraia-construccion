<?php

use App\Security\DataScope\SystemScope;

function unauthorizedPublicSystemScopeFixture(): void
{
    SystemScope::forMaintenance('fixture:public');
}
