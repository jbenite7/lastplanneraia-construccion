<?php

function runnerFactoryFixture($context): \App\Security\DataScope\SystemScopeRunner
{
    return new \App\Security\DataScope\SystemScopeRunner($context);
}
