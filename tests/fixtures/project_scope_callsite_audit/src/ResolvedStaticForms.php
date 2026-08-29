<?php

final class ResolvedStaticForms
{
    private const PROJECT_SQL = 'SELECT * FROM programa';

    public function run($db): void
    {
        $db->queryWithProject(self::PROJECT_SQL);

        $sql = <<<'SQL'
SELECT * FROM programa
SQL;
        $db->queryWithProject($sql);
    }
}
