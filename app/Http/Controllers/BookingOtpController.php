<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BookingOtpRateLimitException;
use App\Services\BookingOtpService;
use App\Support\IranianPhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class BookingOtpController
{
    public function issue(Request $request, BookingOtpService $otp): JsonResponse
    {
        $validated = $request->validate(['mobile' => ['required', 'string', 'max:32']]);

        try {
            $phone = IranianPhoneNumber::normalize($validated['mobile']);
            $challengeId = $otp->issue($phone, (string) $request->ip());
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['mobile' => ['The mobile number is invalid.']]);
        } catch (BookingOtpRateLimitException) {
            return response()->json(['code' => 'RATE_LIMITED'], 429)
                ->header('Retry-After', '60')
                ->header('Cache-Control', 'no-store, private');
        } catch (RuntimeException) {
            return response()->json(['code' => 'OTP_UNAVAILABLE'], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json(['challenge_id' => $challengeId, 'expires_in' => 300], 202)
            ->header('Cache-Control', 'no-store, private');
    }

    public function verify(Request $request, BookingOtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'regex:/^[0-9]{4,12}$/D'],
        ]);

        if (! $otp->verify($validated['challenge_id'], $validated['code'])) {
            return response()->json(['code' => 'OTP_INVALID_OR_EXPIRED'], 422)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json(['verified' => true], 200)
            ->header('Cache-Control', 'no-store, private');
    }
}
