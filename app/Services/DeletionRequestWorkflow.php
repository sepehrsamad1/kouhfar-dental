<?php

declare(strict_types=1);

namespace App\Services;

use App\DeletionRequest;
use App\StaffUser;
use App\Support\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class DeletionRequestWorkflow
{
    private const REJECTION_CODES = [
        'identity_not_verified',
        'outside_scope',
        'duplicate_request',
    ];

    private const RESOLUTION_CODES = [
        'retained_by_policy',
        'legal_hold',
        'correction_completed',
        'no_action_required',
    ];

    public function review(DeletionRequest $request, StaffUser $actor, string $decision, ?string $reasonCode = null): DeletionRequest
    {
        $this->authorize($actor);

        if (! in_array($decision, ['accept_for_assessment', 'reject'], true)) {
            throw new InvalidArgumentException('Unsupported deletion request review decision.');
        }

        if ($decision === 'reject' && ! in_array($reasonCode, self::REJECTION_CODES, true)) {
            throw new InvalidArgumentException('A supported reason code is required when rejecting a request.');
        }

        return DB::transaction(function () use ($request, $actor, $decision, $reasonCode): DeletionRequest {
            $locked = DeletionRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($locked->status !== 'pending') {
                throw new LogicException('Only pending deletion requests can be reviewed.');
            }

            $before = ['status' => $locked->status];
            $locked->forceFill([
                'status' => $decision === 'reject' ? 'rejected' : 'under_review',
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now('UTC'),
                'resolution_code' => $decision === 'reject' ? $reasonCode : null,
            ])->save();

            AuditLogger::record($actor, 'deletion_request.reviewed', $locked, $before, [
                'status' => $locked->status,
                'reason_code' => $decision === 'reject' ? $reasonCode : 'accepted_for_assessment',
            ]);

            return $locked->refresh();
        });
    }

    public function close(DeletionRequest $request, StaffUser $actor, string $resolutionCode): DeletionRequest
    {
        $this->authorize($actor);

        if (! in_array($resolutionCode, self::RESOLUTION_CODES, true)) {
            throw new InvalidArgumentException('Unsupported deletion request resolution code.');
        }

        return DB::transaction(function () use ($request, $actor, $resolutionCode): DeletionRequest {
            $locked = DeletionRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($locked->status !== 'under_review') {
                throw new LogicException('Only requests under review can be closed.');
            }

            $before = ['status' => $locked->status];
            $locked->forceFill([
                'status' => 'closed',
                'resolved_at' => now('UTC'),
                'resolution_code' => $resolutionCode,
            ])->save();

            AuditLogger::record($actor, 'deletion_request.closed', $locked, $before, [
                'status' => $locked->status,
                'reason_code' => $resolutionCode,
            ]);

            return $locked->refresh();
        });
    }

    private function authorize(StaffUser $actor): void
    {
        if (! $actor->is_active || $actor->role !== 'admin') {
            throw new AuthorizationException('Only active administrators may manage deletion requests.');
        }
    }
}
