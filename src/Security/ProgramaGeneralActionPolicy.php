<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Pure action matrix for Programa General (S05).
 * Resolves permissions and week state into 6 explicit action booleans.
 */
class ProgramaGeneralActionPolicy
{
    /**
     * @return array{
     *     editPlanFields: bool,
     *     editProgress: bool,
     *     runBatch: bool,
     *     downloadCut: bool,
     *     readDrawer: bool,
     *     writeDrawer: bool
     * }
     */
    public static function resolve(
        bool $canEdit,
        bool $canEditPast,
        int $week,
        int $maxWeek,
        bool $confirmed,
        bool $canDownload,
        bool $canReadDrawer,
        bool $canWriteDrawer
    ): array {
        $validWeek = $week > 0 && $maxWeek > 0 && $week <= $maxWeek;
        $editableWeek = $validWeek && ($week === $maxWeek || ($week < $maxWeek && $canEditPast));
        $mayMutate = $canEdit && $editableWeek;

        return [
            'editPlanFields' => $mayMutate && !$confirmed,
            'editProgress' => $mayMutate,
            'runBatch' => $mayMutate,
            'downloadCut' => $validWeek && $canDownload,
            'readDrawer' => $validWeek && $canReadDrawer,
            'writeDrawer' => $validWeek && $canWriteDrawer,
        ];
    }
}
