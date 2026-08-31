<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Lps\LpsActionPolicy;
use App\Services\Lps\LpsActorEligibility;
use App\Services\Lps\LpsTarget;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * D-T02-03/09, ESC-R5/R6/R7: acciones efectivas por target + capacidad + elegibilidad. Nunca
 * expone una matriz por rol, sólo los booleanos ya decididos server-side.
 */
#[Group('puro')]
final class LpsActionPolicyTest extends TestCase
{
    private function policy(): LpsActionPolicy
    {
        return new LpsActionPolicy();
    }

    private function activityTarget(): LpsTarget
    {
        return LpsTarget::forActivity(73, 4102, 'PG', 14);
    }

    private function alertTarget(int $level = 1, bool $active = true): LpsTarget
    {
        return LpsTarget::forAlert(73, 901, 4102, 'PS', 14, $level, $active);
    }

    // --- read/edit capability overrides ---

    public function testSinCapacidadDeLecturaReadEsFalso(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), false, false, LpsActorEligibility::FORBIDDEN);

        self::assertFalse($actions['read']);
    }

    public function testConCapacidadDeLecturaReadEsVerdaderoAunSinEditar(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), true, false, LpsActorEligibility::FORBIDDEN);

        self::assertTrue($actions['read']);
        self::assertFalse($actions['comment']);
        self::assertFalse($actions['notifyNext']);
        self::assertFalse($actions['close']);
    }

    public function testSinCapacidadDeEditarActorWriteBlockEsForbidden(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), true, false, LpsActorEligibility::ELIGIBLE);

        self::assertSame('forbidden', $actions['actorWriteBlock']);
    }

    // --- actor eligible/profile-required/forbidden ---

    public function testActorElegibleConCapacidadPermiteComentar(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertTrue($actions['comment']);
        self::assertSame('none', $actions['actorWriteBlock']);
    }

    public function testActorProfileRequiredBloqueaComentarYCerrarPeroNoLectura(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(), true, true, LpsActorEligibility::PROFILE_REQUIRED);

        self::assertTrue($actions['read']);
        self::assertFalse($actions['comment']);
        self::assertFalse($actions['close']);
        self::assertSame('profile_required', $actions['actorWriteBlock']);
    }

    public function testActorProfileRequiredNoBloqueaAvisarPorqueNoPersisteElActor(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), true, true, LpsActorEligibility::PROFILE_REQUIRED);

        self::assertTrue($actions['notifyNext']);
    }

    public function testActorForbiddenBloqueaComentarYCerrar(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(), true, true, LpsActorEligibility::FORBIDDEN);

        self::assertFalse($actions['comment']);
        self::assertFalse($actions['close']);
        self::assertSame('forbidden', $actions['actorWriteBlock']);
    }

    // --- terminal hierarchy ---

    public function testNivelTerminalBloqueaAvisarSiguienteNivel(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(level: 5, active: true), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertFalse($actions['notifyNext']);
        // Cerrar sigue disponible en nivel terminal: la jerarquía sólo bloquea "siguiente nivel".
        self::assertTrue($actions['close']);
    }

    public function testNivelNoTerminalPermiteAvisarSiguienteNivel(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(level: 4, active: true), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertTrue($actions['notifyNext']);
    }

    public function testTargetDeActividadSinAlertaSiemprePuedeAvisarPrimerNivel(): void
    {
        $actions = $this->policy()->evaluate($this->activityTarget(), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertTrue($actions['notifyNext']);
        self::assertFalse($actions['close']);
    }

    // --- stale/closed alert by operation ---

    public function testAlertaCerradaBloqueaAvisarYCerrarPeroNoLectura(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(level: 2, active: false), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertTrue($actions['read']);
        self::assertFalse($actions['notifyNext']);
        self::assertFalse($actions['close']);
    }

    public function testAlertaCerradaAunPermiteComentarSiElActorEsElegible(): void
    {
        $actions = $this->policy()->evaluate($this->alertTarget(level: 2, active: false), true, true, LpsActorEligibility::ELIGIBLE);

        self::assertTrue($actions['comment']);
    }
}
