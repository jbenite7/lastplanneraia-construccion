<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Lps\LpsAlertRecord;
use App\Services\Lps\LpsCrisisRepository;
use App\Services\Lps\LpsCrisisService;
use App\Services\Lps\LpsCrisisTrigger;
use App\Services\Lps\LpsTarget;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * T02-AC-105..129 (Tarea 6): registro/cierre de crisis. Fake in-memory del repositorio, espía de
 * llamadas (nunca DB real, D-T02 restricción global "sin DDL/DML"): {@see LpsCrisisService} es la
 * unidad bajo prueba, no la capa SQL legacy — esa vive en `LpsLegacyCrisisRepository` y no se
 * ejercita aquí.
 */
#[Group('puro')]
final class LpsCrisisServiceTest extends TestCase
{
    public const PROJECT_ID = 73;

    private function target(
        int $activityId = 4102,
        string $module = 'PG',
        int $week = 14,
        ?int $alertId = null,
        ?int $level = null,
        bool $active = false,
    ): LpsTarget {
        return $alertId !== null
            ? LpsTarget::forAlert(self::PROJECT_ID, $alertId, $activityId, $module, $week, $level ?? 1, $active)
            : LpsTarget::forActivity(self::PROJECT_ID, $activityId, $module, $week);
    }

    /** @param list<LpsAlertRecord> $activeSeed */
    private function repository(array $activeSeed = [], bool $closeReturns = true): LpsCrisisRepository
    {
        return new class ($activeSeed, $closeReturns) implements LpsCrisisRepository {
            /** @var list<string> */
            public array $calls = [];
            private int $nextId = 5000;

            public function __construct(private array $active, private bool $closeReturns)
            {
            }

            public function beginTransaction(): void
            {
                $this->calls[] = 'beginTransaction';
            }

            public function commit(): void
            {
                $this->calls[] = 'commit';
            }

            public function rollBack(): void
            {
                $this->calls[] = 'rollBack';
            }

            public function findActiveByTarget(int $projectId, int $activityId, int $week): ?LpsAlertRecord
            {
                $this->calls[] = 'findActiveByTarget';
                foreach ($this->active as $record) {
                    if ($record->projectId === $projectId && $record->activityId === $activityId && $record->week === $week) {
                        return $record;
                    }
                }

                return null;
            }

            public function insertAlert(int $projectId, int $activityId, string $module, int $week, string $trigger): int
            {
                $this->calls[] = 'insertAlert';

                return $this->nextId++;
            }

            public function setCrisisFlag(int $projectId, int $activityId, int $week, bool $active): void
            {
                $this->calls[] = 'setCrisisFlag:' . ($active ? 'on' : 'off');
            }

            public function closeAlert(int $projectId, int $alertId, int $userId, string $justification): bool
            {
                $this->calls[] = 'closeAlert';

                return $this->closeReturns;
            }
        };
    }

    // --- registro: idempotencia y orden transaccional (T02-AC-111) ---

    public function testRegistrarSinAlertaActivaInsertaYMarcaBanderaEnEseOrden(): void
    {
        $repo = $this->repository();
        $service = new LpsCrisisService($repo);

        $result = $service->register($this->target(), LpsCrisisTrigger::MANUAL);

        self::assertFalse($result->wasActive);
        self::assertSame(5000, $result->alertId);
        self::assertSame(
            ['beginTransaction', 'findActiveByTarget', 'insertAlert', 'setCrisisFlag:on', 'commit'],
            $repo->calls,
        );
    }

    public function testRegistrarConAlertaYaActivaEsIdempotenteYNoInserta(): void
    {
        $existing = new LpsAlertRecord(901, self::PROJECT_ID, 4102, 'PG', 14, 2, true);
        $repo = $this->repository([$existing]);
        $service = new LpsCrisisService($repo);

        $result = $service->register($this->target(), LpsCrisisTrigger::SOS_DIR);

        self::assertTrue($result->wasActive);
        self::assertSame(901, $result->alertId, 'el id devuelto debe ser el de la alerta ya existente');
        self::assertSame(
            ['beginTransaction', 'findActiveByTarget', 'setCrisisFlag:on', 'commit'],
            $repo->calls,
            'idempotencia: sin insertAlert cuando ya hay una alerta activa (T02-AC-111)',
        );
    }

    public function testRegistrarIdempotenteNoCambiaDeNivelPorqueLaInterfazNoExponeEsaOperacion(): void
    {
        // T02-AC-111/113: el repositorio de escritura no tiene ningún método para tocar
        // `nivel_actual` — ni siquiera insertAlert() lo recibe como parámetro (siempre nace en 1
        // en la implementación legacy). "No cambia de nivel" queda garantizado por la forma de la
        // interfaz, no por una convención que un llamador pueda romper.
        self::assertFalse(
            method_exists(LpsCrisisRepository::class, 'escalate')
            || method_exists(LpsCrisisRepository::class, 'incrementLevel')
            || method_exists(LpsCrisisRepository::class, 'setLevel'),
        );
    }

    public function testRegistrarPropagaLaExcepcionYHaceRollback(): void
    {
        $repo = new class implements LpsCrisisRepository {
            /** @var list<string> */
            public array $calls = [];

            public function beginTransaction(): void
            {
                $this->calls[] = 'beginTransaction';
            }

            public function commit(): void
            {
                $this->calls[] = 'commit';
            }

            public function rollBack(): void
            {
                $this->calls[] = 'rollBack';
            }

            public function findActiveByTarget(int $projectId, int $activityId, int $week): ?LpsAlertRecord
            {
                $this->calls[] = 'findActiveByTarget';
                throw new RuntimeException('fallo simulado de infraestructura');
            }

            public function insertAlert(int $projectId, int $activityId, string $module, int $week, string $trigger): int
            {
                return 0;
            }

            public function setCrisisFlag(int $projectId, int $activityId, int $week, bool $active): void
            {
            }

            public function closeAlert(int $projectId, int $alertId, int $userId, string $justification): bool
            {
                return false;
            }
        };
        $service = new LpsCrisisService($repo);

        $this->expectException(RuntimeException::class);

        try {
            $service->register($this->target(), LpsCrisisTrigger::MANUAL);
        } finally {
            self::assertSame(['beginTransaction', 'findActiveByTarget', 'rollBack'], $repo->calls);
        }
    }

    // --- trigger enum (T02-AC-109) ---

    public function testTriggerValidoAceptaLosCincoValoresDelEnum(): void
    {
        foreach (['MANUAL', 'SOS-RES', 'SOS-DIR', 'SOS-COO', 'SOS-GER'] as $valor) {
            self::assertTrue(LpsCrisisTrigger::isValid($valor), "$valor debería ser válido");
        }
    }

    public function testTriggerInvalidoSeRechaza(): void
    {
        self::assertFalse(LpsCrisisTrigger::isValid('SOS-RESIDENTE'));
        self::assertFalse(LpsCrisisTrigger::isValid('AUTO'));
        self::assertFalse(LpsCrisisTrigger::isValid(''));
        self::assertFalse(LpsCrisisTrigger::isValid('manual'), 'el enum es sensible a mayúsculas, igual que MODULES en el resolver');
    }

    // --- cierre: orden transaccional y comportamiento en carrera perdida (T02-AC-122..127) ---

    public function testCerrarConExitoCierraYLuegoLimpiaLaBanderaEnEseOrden(): void
    {
        $repo = $this->repository();
        $service = new LpsCrisisService($repo);
        $target = $this->target(alertId: 901, level: 3, active: true);

        $cerrado = $service->close($target, 55, str_repeat('x', 120));

        self::assertTrue($cerrado);
        self::assertSame(
            ['beginTransaction', 'closeAlert', 'setCrisisFlag:off', 'commit'],
            $repo->calls,
        );
    }

    public function testCerrarSinFilaAfectadaNoTocaLaBanderaYDevuelveFalso(): void
    {
        // Carrera perdida: closeAlert() no afecta ninguna fila (la alerta ya no estaba activa).
        $repo = $this->repository(closeReturns: false);
        $service = new LpsCrisisService($repo);
        $target = $this->target(alertId: 901, level: 3, active: true);

        $cerrado = $service->close($target, 55, str_repeat('x', 120));

        self::assertFalse($cerrado);
        self::assertSame(
            ['beginTransaction', 'closeAlert', 'commit'],
            $repo->calls,
            'setCrisisFlag no debe llamarse cuando closeAlert no afectó ninguna fila',
        );
    }

    public function testCerrarRecortaLaJustificacionAntesDeEntregarlaAlRepositorio(): void
    {
        $repo = new class implements LpsCrisisRepository {
            public ?string $justificacionRecibida = null;

            public function beginTransaction(): void
            {
            }

            public function commit(): void
            {
            }

            public function rollBack(): void
            {
            }

            public function findActiveByTarget(int $projectId, int $activityId, int $week): ?LpsAlertRecord
            {
                return null;
            }

            public function insertAlert(int $projectId, int $activityId, string $module, int $week, string $trigger): int
            {
                return 0;
            }

            public function setCrisisFlag(int $projectId, int $activityId, int $week, bool $active): void
            {
            }

            public function closeAlert(int $projectId, int $alertId, int $userId, string $justification): bool
            {
                $this->justificacionRecibida = $justification;

                return true;
            }
        };
        $service = new LpsCrisisService($repo);
        $target = $this->target(alertId: 901, level: 3, active: true);
        $conEspacios = '  ' . str_repeat('y', 105) . '  ';

        $service->close($target, 55, $conEspacios);

        self::assertSame(trim($conEspacios), $repo->justificacionRecibida);
    }
}
