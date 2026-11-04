<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Otp\PhoneVerificationIntent;
use App\Services\Otp\PhoneVerificationService;
use App\Services\Otp\PhoneVerificationException;
use App\Support\IranianPhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class PublicPhoneVerificationController
{
    public function send(Request $request, PhoneVerificationService $verification): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:32'],
            'full_name' => ['required', 'string', 'min:3', 'max:200'],
            'service_id' => ['required', 'uuid', Rule::exists('services', 'id')->where(
                fn ($query) => $query->where('is_active', true)->where('is_public_bookable', true),
            )],
            'start_at' => ['required', 'date'],
        ]);

        try {
            $mobile = IranianPhoneNumber::normalize($data['mobile']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['mobile' => ['شماره موبایل معتبر نیست.']]);
        }

        $payload = [...$data, 'mobile' => $mobile];
        $intentHash = PhoneVerificationIntent::hash($payload);

        try {
            $challenge = $verification->start(
                $mobile,
                PhoneVerificationService::PUBLIC_BOOKING,
                $intentHash,
            );
        } catch (PhoneVerificationException) {
            return response()->json(['code' => 'RATE_LIMITED'], 429)
                ->header('Cache-Control', 'no-store, private');
        } catch (RuntimeException) {
            return response()->json(['code' => 'OTP_SEND_FAILED'], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json([
            'challenge_id' => $challenge->getKey(),
            'expires_in' => 300,
            'resend_after' => 60,
        ], 202)->header('Cache-Control', 'no-store, private');
    }

    public function verify(Request $request, PhoneVerificationService $verification): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'regex:/^\\d{4,10}$/'],
        ]);

        try {
            $grant = $verification->verify(
                $data['challenge_id'],
                $data['code'],
                PhoneVerificationService::PUBLIC_BOOKING,
            );
        } catch (RuntimeException) {
            return response()->json(['code' => 'OTP_INVALID_OR_EXPIRED'], 422)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json(['verification_grant' => $grant], 200)
            ->header('Cache-Control', 'no-store, private');
    }
}
