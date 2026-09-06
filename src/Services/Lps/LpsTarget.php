<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Target LPS inmutable y server-authoritative (D-T02-02, T02-AC-011..020). Sólo dos formas:
 * actividad (consecutivo + módulo PG/PI/PS) o alerta (alerta_id). El navegador nunca aporta
 * proyecto, semana o actor de autoridad: éstos siempre vienen resueltos por el resolver desde
 * ProjectScope y desde el adapter/alerta que corresponda.
 */
final readonly class LpsTarget
{
    public const KIND_ACTIVITY = 'activity';
    public const KIND_ALERT = 'alert';

    private function __construct(
        public string $kind,
        public int $projectId,
        public int $activityId,
        public string $module,
        public int $week,
        public ?int $alertId,
        public ?int $alertLevel,
        public bool $alertActive,
        public ?int $escalamientoId,
        public bool $isLegacy,
    ) {
    }

    public static function forActivity(
        int $projectId,
        int $activityId,
        string $module,
        int $week,
        ?int $escalamientoId = null,
        bool $isLegacy = false,
    ): self {
        return new self(
            self::KIND_ACTIVITY,
            $projectId,
            $activityId,
            $module,
            $week,
            null,
            null,
            false,
            $escalamientoId,
            $isLegacy,
        );
    }

    public static function forAlert(
        int $projectId,
        int $alertId,
        int $activityId,
        string $module,
        int $week,
        int $level,
        bool $active,
    ): self {
        return new self(
            self::KIND_ALERT,
            $projectId,
            $activityId,
            $module,
            $week,
            $alertId,
            $level,
            $active,
            $alertId,
            false,
        );
    }

    public function isAlert(): bool
    {
        return $this->kind === self::KIND_ALERT;
    }
}
