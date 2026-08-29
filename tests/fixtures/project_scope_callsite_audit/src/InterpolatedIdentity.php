<?php

function unresolvedIdentityFixture($db, string $table): void
{
    $db->queryWithProject("SELECT * FROM {$table}");
}
