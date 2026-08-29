<?php

function resolvedUnclassifiedSqlFixture($db): void
{
    $db->queryWithProject('SELECT * FROM audit_table_not_in_catalog');
}
