<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PgAvanceEdicionManualService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PgAvanceStatementDouble
{
    /** @param array<string, mixed> $row */
    public function __construct(private readonly array $row)
    {
    }

    /** @return array<string, mixed> */
    public function fetch(): array
    {
        return $this->row;
    }
}

final class PgAvanceInsertFailureDatabaseDouble
{
    public int $insertAttempts = 0;

    /** @param array<mixed> $params */
    public function queryWithProject(string $sql, array $params, int $projectId): PgAvanceStatementDouble
    {
        if (str_starts_with(ltrim($sql), 'SELECT ')) {
            return new PgAvanceStatementDouble([
                'Ejecutado' => 0.9,
                'programaAnteriorAsociar' => '*No Asociada*',
            ]);
        }

        $this->insertAttempts++;
        throw new RuntimeException('insert-failure-from-test-double');
    }
}

#[Group('puro')]
final class PgAvanceEdicionManualFailureTest extends TestCase
{
    public function testDevuelveFalseSiElInsertFallaSinNecesitarDdlRuntime(): void
    {
        $database = new PgAvanceInsertFailureDatabaseDouble();
        $service = new PgAvanceEdicionManualService($database);
        $previousErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            $inserted = $service->registrarSiCambio(
                990074,
                2,
                1,
                ['Ejecutado' => 0.7, 'programaAnteriorAsociar' => '*No Asociada*'],
                'test.A',
                false,
            );
        } finally {
            ini_set('error_log', is_string($previousErrorLog) ? $previousErrorLog : '');
        }

        self::assertFalse($inserted, 'un INSERT que falla no debe propagar la excepción');
        self::assertSame(1, $database->insertAttempts, 'el double debe haber ejercitado el fallo de INSERT');
    }
}
