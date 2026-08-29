<?php

function t(string $prefix, string $fallback): string
{
    return 'SELECT * FROM general_usuarios';
}

function helperReturnedSqlFixture($db): void
{
    $db->queryWithProject(t('fixture', 'programa'));
}
