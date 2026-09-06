<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\ProjectScope;
use App\Services\Lps\LpsActivityTargetAdapter;
use App\Services\Lps\LpsAlertRecord;
use App\Services\Lps\LpsAlertRepository;
use App\Services\Lps\LpsTarget;
use App\Services\Lps\LpsTargetException;
use App\Services\Lps\LpsTargetRequest;
use App\Services\Lps\LpsTargetResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * T02-AC-011..020, T02-AC-078..079, T02-AC-178: el resolver es la única autoridad de target.
 * Spies (no fakes mudos): cada consulta registra sus argumentos exactos para que una prueba
 * pueda afirmar que jamás faltó `project_id` ni se disparó una mutación.
 */
#[Group('puro')]
final class LpsTargetResolverTest extends TestCase
{
    public const PROJECT_ID = 73;

    private function scope(): ProjectScope
    {
        return new ProjectScope(self::PROJECT_ID, 'test.R', 'R');
    }

    /** @param array<string, array<int, int>> $activities module => [activityId => week] */
    private function adapter(string $module, array $activities): LpsActivityTargetAdapter
    {
        return new class ($module, $activities) implements LpsActivityTargetAdapter {
            /** @var array<int, true> */
            public array $calls = [];

            public function __construct(
                private readonly string $module,
                private readonly array $activities,
            ) {
            }

            public function moduleKey(): string
            {
                return $this->module;
            }

            public function resolveWeek(int $projectId, int $activityId): ?int
            {
                $this->calls[] = [$projectId, $activityId];

                if ($projectId !== LpsTargetResolverTest::PROJECT_ID) {
                    // Spy: si algún día el resolver deja de mandar el project_id correcto, esto
                    // debe reventar en vez de devolver silenciosamente null.
                    throw new \RuntimeException('El adapter recibió un project_id ajeno al scope.');
                }

                return $this->activities[$activityId] ?? null;
            }
        };
    }

    /** @param array<int, LpsAlertRecord> $alerts alertId => record */
    private function alertRepository(array $alerts): LpsAlertRepository
    {
        return new class ($alerts) implements LpsAlertRepository {
            public array $calls = [];

            public function __construct(private readonly array $alerts)
            {
            }

            public function findById(int $projectId, int $alertId): ?LpsAlertRecord
            {
                $this->calls[] = [$projectId, $alertId];

                if ($projectId !== LpsTargetResolverTest::PROJECT_ID) {
                    throw new \RuntimeException('El repositorio de alertas recibió un project_id ajeno.');
                }

                $alert = $this->alerts[$alertId] ?? null;

                // Escaneo cross-proyecto: nunca debe devolver una alerta de otro proyecto.
                return ($alert !== null && $alert->projectId === $projectId) ? $alert : null;
            }
        };
    }

    private function resolver(
        array $pgActivities = [],
        array $piActivities = [],
        array $psActivities = [],
        array $alerts = [],
    ): LpsTargetResolver {
        return new LpsTargetResolver(
            $this->scope(),
            $this->alertRepository($alerts),
            [
                $this->adapter('PG', $pgActivities),
                $this->adapter('PI', $piActivities),
                $this->adapter('PS', $psActivities),
            ],
        );
    }

    // --- activity PG/PI/PS ---

    public function testResuelveTargetDeActividadPG(): void
    {
        $target = $this->resolver(pgActivities: [4102 => 14])
            ->resolve(new LpsTargetRequest(activityId: 4102, module: 'PG'));

        self::assertSame(LpsTarget::KIND_ACTIVITY, $target->kind);
        self::assertSame('PG', $target->module);
        self::assertSame(14, $target->week);
        self::assertSame(self::PROJECT_ID, $target->projectId);
        self::assertFalse($target->isLegacy);
    }

    public function testResuelveTargetDeActividadPI(): void
    {
        $target = $this->resolver(piActivities: [55 => 9])
            ->resolve(new LpsTargetRequest(activityId: 55, module: 'PI'));

        self::assertSame('PI', $target->module);
        self::assertSame(9, $target->week);
    }

    public function testResuelveTargetDeActividadPS(): void
    {
        $target = $this->resolver(psActivities: [900 => 20])
            ->resolve(new LpsTargetRequest(activityId: 900, module: 'PS'));

        self::assertSame('PS', $target->module);
        self::assertSame(20, $target->week);
    }

    // --- alert with persisted week/module/activity ---

    public function testTargetDeAlertaDerivaActividadSemanaYModuloDeLaAlertaPersistida(): void
    {
        $alert = new LpsAlertRecord(901, self::PROJECT_ID, 4102, 'PS', 14, 1, true);

        $target = $this->resolver(alerts: [901 => $alert])
            ->resolve(new LpsTargetRequest(alertId: 901));

        self::assertSame(LpsTarget::KIND_ALERT, $target->kind);
        self::assertSame(4102, $target->activityId);
        self::assertSame('PS', $target->module);
        self::assertSame(14, $target->week);
        self::assertSame(901, $target->alertId);
        self::assertSame(1, $target->alertLevel);
        self::assertTrue($target->alertActive);
    }

    // --- XOR violations ---

    public function testAlertaYConsecutivoJuntosViolanElXor(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver()->resolve(new LpsTargetRequest(activityId: 10, module: 'PG', alertId: 5));
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
            throw $exception;
        }
    }

    public function testNiAlertaNiConsecutivoTambienViolaElXor(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver()->resolve(new LpsTargetRequest());
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
            throw $exception;
        }
    }

    // --- malformed IDs ---

    public function testConsecutivoCeroOrNegativoEsValidationFailed(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver(pgActivities: [1 => 1])->resolve(new LpsTargetRequest(activityId: 0, module: 'PG'));
        } catch (LpsTargetException $exception) {
            self::assertSame(422, $exception->apiError()->httpStatus);
            throw $exception;
        }
    }

    public function testAlertaIdNegativoEsValidationFailed(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver()->resolve(new LpsTargetRequest(alertId: -5));
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
            throw $exception;
        }
    }

    public function testModuloFueraDelEnumEsValidationFailed(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver()->resolve(new LpsTargetRequest(activityId: 10, module: 'ZZ'));
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
            throw $exception;
        }
    }

    // --- project/alert/activity mismatch ---

    public function testActividadQueNoPerteneceAlProyectoEsTargetNotFound(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver(pgActivities: [1 => 1])->resolve(new LpsTargetRequest(activityId: 999, module: 'PG'));
        } catch (LpsTargetException $exception) {
            self::assertSame('LPS_TARGET_NOT_FOUND', $exception->apiError()->code);
            self::assertSame(404, $exception->apiError()->httpStatus);
            throw $exception;
        }
    }

    public function testAlertaAjenaAlProyectoEsTargetNotFoundIndistinguibleDeInexistente(): void
    {
        $ajena = new LpsAlertRecord(901, 999, 4102, 'PS', 14, 1, true);

        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver(alerts: [901 => $ajena])->resolve(new LpsTargetRequest(alertId: 901));
        } catch (LpsTargetException $exception) {
            self::assertSame('LPS_TARGET_NOT_FOUND', $exception->apiError()->code);
            throw $exception;
        }
    }

    // --- Pre-Construction week zero ---

    public function testSemanaCeroEsValidaParaPreConstruccionYNoSeConfundeConInexistente(): void
    {
        $target = $this->resolver(pgActivities: [77 => 0])
            ->resolve(new LpsTargetRequest(activityId: 77, module: 'PG'));

        self::assertSame(0, $target->week);
    }

    // --- legacy consecutive without module ---

    public function testConsecutivoLegacySinModuloSeResuelveProbandoLosAdaptadoresEnOrden(): void
    {
        $target = $this->resolver(psActivities: [500 => 6])
            ->resolve(new LpsTargetRequest(activityId: 500));

        self::assertSame('PS', $target->module);
        self::assertSame(6, $target->week);
        self::assertTrue($target->isLegacy);
    }

    public function testConsecutivoLegacyQueNoExisteEnNingunModuloEsTargetNotFound(): void
    {
        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver()->resolve(new LpsTargetRequest(activityId: 12345));
        } catch (LpsTargetException $exception) {
            self::assertSame('LPS_TARGET_NOT_FOUND', $exception->apiError()->code);
            throw $exception;
        }
    }

    // --- escalamiento_id legacy opcional (T02-AC-020) ---

    public function testEscalamientoIdLegacyDebePertenecerAlMismoProyectoActividadYSemana(): void
    {
        $alert = new LpsAlertRecord(30, self::PROJECT_ID, 500, 'PS', 6, 1, true);

        $target = $this->resolver(psActivities: [500 => 6], alerts: [30 => $alert])
            ->resolve(new LpsTargetRequest(activityId: 500, module: 'PS', escalamientoId: 30));

        self::assertSame(30, $target->escalamientoId);
    }

    public function testEscalamientoIdLegacyDeOtraActividadEsTargetNotFound(): void
    {
        $alert = new LpsAlertRecord(30, self::PROJECT_ID, 999, 'PS', 6, 1, true);

        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver(psActivities: [500 => 6], alerts: [30 => $alert])
                ->resolve(new LpsTargetRequest(activityId: 500, module: 'PS', escalamientoId: 30));
        } catch (LpsTargetException $exception) {
            self::assertSame('LPS_TARGET_NOT_FOUND', $exception->apiError()->code);
            throw $exception;
        }
    }

    public function testEscalamientoIdLegacyDeOtraSemanaEsTargetNotFound(): void
    {
        $alert = new LpsAlertRecord(30, self::PROJECT_ID, 500, 'PS', 5, 1, true);

        $this->expectException(LpsTargetException::class);

        try {
            $this->resolver(psActivities: [500 => 6], alerts: [30 => $alert])
                ->resolve(new LpsTargetRequest(activityId: 500, module: 'PS', escalamientoId: 30));
        } catch (LpsTargetException $exception) {
            self::assertSame('LPS_TARGET_NOT_FOUND', $exception->apiError()->code);
            throw $exception;
        }
    }

    // --- stale/closed alert by operation: el resolver expone el estado, no bloquea lectura ---

    public function testAlertaCerradaSeResuelvePeroExponeInactiva(): void
    {
        $alert = new LpsAlertRecord(901, self::PROJECT_ID, 4102, 'PS', 14, 2, false);

        $target = $this->resolver(alerts: [901 => $alert])->resolve(new LpsTargetRequest(alertId: 901));

        self::assertFalse($target->alertActive);
        self::assertSame(2, $target->alertLevel);
    }

    // --- jerarquía terminal: el resolver sólo transporta el nivel, no decide acciones ---

    public function testNivelTerminalSeTransportaSinDecidirAcciones(): void
    {
        $alert = new LpsAlertRecord(901, self::PROJECT_ID, 4102, 'PS', 14, 5, true);

        $target = $this->resolver(alerts: [901 => $alert])->resolve(new LpsTargetRequest(alertId: 901));

        self::assertSame(5, $target->alertLevel);
    }

    // --- spy: ningún camino omite el predicado de scope ---

    public function testCadaConsultaDeActividadRecibeElProjectIdDelScope(): void
    {
        $adapterPg = $this->adapter('PG', [4102 => 14]);
        $resolver = new LpsTargetResolver(
            $this->scope(),
            $this->alertRepository([]),
            [$adapterPg, $this->adapter('PI', []), $this->adapter('PS', [])],
        );

        $resolver->resolve(new LpsTargetRequest(activityId: 4102, module: 'PG'));

        self::assertSame([[self::PROJECT_ID, 4102]], $adapterPg->calls);
    }
}
