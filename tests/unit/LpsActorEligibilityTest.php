<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Lps\LpsActorCompatibilityChecker;
use App\Services\Lps\LpsActorEligibility;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * ESC-R6 / T02-AC-099: `eligible|profile_required|forbidden`, sin mapping por texto ni
 * autocreación de perfil.
 */
#[Group('puro')]
final class LpsActorEligibilityTest extends TestCase
{
    /** @param array<int, array<int, true>> $compatible projectId => [userId => true] */
    private function eligibility(array $compatible): LpsActorEligibility
    {
        $checker = new class ($compatible) implements LpsActorCompatibilityChecker {
            public array $calls = [];

            public function __construct(private readonly array $compatible)
            {
            }

            public function isCompatible(int $projectId, int $userId): bool
            {
                $this->calls[] = [$projectId, $userId];

                return isset($this->compatible[$projectId][$userId]);
            }
        };

        return new LpsActorEligibility($checker);
    }

    public function testActorConFilaProfesionalCompatibleEsElegible(): void
    {
        $result = $this->eligibility([73 => [10 => true]])->evaluate(73, 10, true);

        self::assertSame(LpsActorEligibility::ELIGIBLE, $result);
    }

    public function testActorSinFilaProfesionalCompatibleRequierePerfil(): void
    {
        $result = $this->eligibility([])->evaluate(73, 10, true);

        self::assertSame(LpsActorEligibility::PROFILE_REQUIRED, $result);
    }

    public function testActorDeOtroProyectoNoCuentaComoCompatible(): void
    {
        $result = $this->eligibility([99 => [10 => true]])->evaluate(73, 10, true);

        self::assertSame(LpsActorEligibility::PROFILE_REQUIRED, $result);
    }

    public function testSinCapacidadDeEscrituraEsForbiddenSinConsultarElChecker(): void
    {
        $result = $this->eligibility([73 => [10 => true]])->evaluate(73, 10, false);

        self::assertSame(LpsActorEligibility::FORBIDDEN, $result);
    }

    public function testUsuarioIdNoPositivoRequierePerfilSinConsultarElChecker(): void
    {
        $result = $this->eligibility([])->evaluate(73, 0, true);

        self::assertSame(LpsActorEligibility::PROFILE_REQUIRED, $result);
    }

    public function testNoBuscaPorNombreCorreoOCargo(): void
    {
        // El checker sólo recibe (projectId, userId): no hay superficie para pasar texto.
        $reflection = new \ReflectionMethod(LpsActorCompatibilityChecker::class, 'isCompatible');
        $params = $reflection->getParameters();

        self::assertCount(2, $params);
        self::assertSame('int', (string) $params[0]->getType());
        self::assertSame('int', (string) $params[1]->getType());
    }
}
