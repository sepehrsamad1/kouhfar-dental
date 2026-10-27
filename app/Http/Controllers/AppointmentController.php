<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Booking\AppointmentBookingException;
use App\Domain\Booking\AppointmentCreator;
use App\Http\Requests\AppointmentStoreRequest;
use Illuminate\Http\JsonResponse;

final class AppointmentController
{
    public function store(AppointmentStoreRequest $request, AppointmentCreator $creator): JsonResponse
    {
        try {
            $scope = 'public:'.hash('sha256', $request->ip().'|'.$request->userAgent());
            $result = $creator->create(
                $request->validated(),
                (string) $request->validated('idempotency_key'),
                $scope,
            );
        } catch (AppointmentBookingException $exception) {
            return response()->json([
                'code' => $exception->codeName,
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json($result['body'], $result['status']);
    }
}
