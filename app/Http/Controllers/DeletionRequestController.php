<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DeletionRequest;
use App\StaffUser;
use App\Services\DeletionRequestWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeletionRequestController
{
    public function review(Request $request, DeletionRequest $deletionRequest, DeletionRequestWorkflow $workflow): JsonResponse
    {
        $actor = $request->user('staff');
        abort_unless($actor instanceof StaffUser, 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:accept_for_assessment,reject'],
            'reason_code' => ['nullable', 'string', 'max:60'],
        ]);

        $updated = $workflow->review(
            $deletionRequest,
            $actor,
            $validated['decision'],
            $validated['reason_code'] ?? null,
        );

        return response()->json([
            'id' => $updated->getKey(),
            'status' => $updated->status,
            'reviewedAt' => $updated->reviewed_at?->toIso8601String(),
        ]);
    }

    public function close(Request $request, DeletionRequest $deletionRequest, DeletionRequestWorkflow $workflow): JsonResponse
    {
        $actor = $request->user('staff');
        abort_unless($actor instanceof StaffUser, 403);

        $validated = $request->validate([
            'resolution_code' => ['required', 'in:retained_by_policy,legal_hold,correction_completed,no_action_required'],
        ]);

        $updated = $workflow->close(
            $deletionRequest,
            $actor,
            $validated['resolution_code'],
        );

        return response()->json([
            'id' => $updated->getKey(),
            'status' => $updated->status,
            'resolvedAt' => $updated->resolved_at?->toIso8601String(),
            'resolutionCode' => $updated->resolution_code,
        ]);
    }
}
