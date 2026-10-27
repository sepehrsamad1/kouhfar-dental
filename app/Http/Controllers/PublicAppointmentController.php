<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Booking\AppointmentCreationService;
use App\Domain\Booking\Exceptions\IdempotencyKeyReused;
use App\Domain\Booking\Exceptions\SlotUnavailable;
use App\Http\Requests\CreatePublicAppointmentRequest;
use Illuminate\Http\JsonResponse;

final class PublicAppointmentController
{
    public function store(CreatePublicAppointmentRequest $request, AppointmentCreationService $creator): JsonResponse
    {
        try {
            $result = $creator->create($request, $request->validated(), $request->idempotencyKey());

            return response()->json($result['body'], $result['status']);
        } catch (IdempotencyKeyReused) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
            ], 409);
        } catch (SlotUnavailable) {
            return response()->json([
                'code' => 'SLOT_UNAVAILABLE',
            ], 409);
        }
    }
}
