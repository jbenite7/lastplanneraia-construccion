<?php

namespace App\Controllers\Internal;

use App\Security\DesignSystemLabAccessPolicy;

final class DesignSystemLabController
{
    public function index(): void
    {
        $status = DesignSystemLabAccessPolicy::status($_SESSION);
        if ($status !== 200) {
            http_response_code($status);
            echo $status === 404 ? '404 Not Found' : '403 Forbidden';

            return;
        }

        $contract = json_decode((string) file_get_contents(
            PROJECT_ROOT . '/docs/design-system/homologation.json'
        ), true, 512, JSON_THROW_ON_ERROR);
        if (($_GET['fixture'] ?? '') === 'approved-family-v1') {
            $approvals = json_decode((string) file_get_contents(
                PROJECT_ROOT . '/docs/design-system/family-approvals.json'
            ), true, 512, JSON_THROW_ON_ERROR)['approvals'] ?? [];
            $approvedCandidateByFamily = [];
            foreach ($approvals as $approval) {
                $approvedCandidateByFamily[(string) ($approval['familyId'] ?? '')]
                    = (string) ($approval['candidateId'] ?? '');
            }

            foreach ($contract['families'] as &$family) {
                $familyId = (string) ($family['id'] ?? '');
                $approvedCandidateId = $approvedCandidateByFamily[$familyId] ?? '';
                $approvedCandidateExists = false;
                foreach (($family['candidates'] ?? []) as $candidate) {
                    if (($candidate['id'] ?? '') !== $approvedCandidateId
                        || ($candidate['status'] ?? '') !== 'approved') {
                        continue;
                    }

                    $family['activeCandidate'] = $approvedCandidateId;
                    $approvedCandidateExists = true;
                    break;
                }

                if (!$approvedCandidateExists) {
                    throw new \UnexpectedValueException(
                        "Missing approved-family-v1 candidate for {$familyId}"
                    );
                }
            }
            unset($family);
        }
        $uiGroups = json_decode((string) file_get_contents(
            PROJECT_ROOT . '/docs/design-system/ui-groups-inventory.json'
        ), true, 512, JSON_THROW_ON_ERROR)['groups'];
        $stateSemantics = json_decode((string) file_get_contents(
            PROJECT_ROOT . '/docs/design-system/state-semantics.json'
        ), true, 512, JSON_THROW_ON_ERROR);
        require PROJECT_ROOT . '/views/design-system/lab.view.php';
    }
}
