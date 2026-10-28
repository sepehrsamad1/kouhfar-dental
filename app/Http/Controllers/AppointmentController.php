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
        // D8 is now owner-confirmed as required. Task 15 has not yet implemented OTP/provider
        // verification, so never create an unverified public appointment while the gate is on.
        if (config('booking.phone_verification_required')) {
            return response()->json([
                'code' => 'PHONE_VERIFICATION_NOT_READY',
                'message' => 'Phone verification is required but is not configured.',
            ], 503);
        }

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

        // Appointment UUID is a staff/internal identifier; the public response uses only the
        // request status and requested time. Idempotency still replays the same internal record.
        unset($result['body']['appointment_id']);

        return response()->json($result['body'], $result['status']);
    }
}
