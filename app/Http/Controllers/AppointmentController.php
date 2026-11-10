<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Booking\AppointmentBookingException;
use App\Domain\Booking\AppointmentCreator;
use App\Http\Requests\AppointmentStoreRequest;
use App\Services\BookingOtpVerificationException;
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
                $request->validated('otp_challenge_id'),
            );
        } catch (AppointmentBookingException $exception) {
            return response()->json([
                'code' => $exception->codeName,
                'message' => $exception->getMessage(),
            ], 409);
        } catch (BookingOtpVerificationException) {
            return response()->json([
                'code' => 'PHONE_VERIFICATION_REQUIRED',
                'message' => 'Phone verification is required.',
            ], 422);
        }

        // Appointment UUID is a staff/internal identifier; the public response uses only the
        // request status and requested time. Idempotency still replays the same internal record.
        unset($result['body']['appointment_id']);

        return response()->json($result['body'], $result['status']);
    }
}
