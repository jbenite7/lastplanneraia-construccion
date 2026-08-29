<?php

final class InjectedRunnerFixture
{
    public function __construct(
        private \App\Security\DataScope\SystemScopeRunner $runner,
    ) {
    }

    public function execute(): void
    {
        $this->runner->run('fixture:injected', static fn() => null);
    }
}
