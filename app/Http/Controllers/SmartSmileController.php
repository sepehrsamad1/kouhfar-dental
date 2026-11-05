<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SmartSmileUploadRequest;
use App\Services\SmartSmileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SmartSmileController
{
    public function consent(SmartSmileService $service): JsonResponse
    {
        if (! $service->enabled()) {
            return response()->json(['status' => 'disabled', 'non_diagnostic' => true], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $granted = $service->grantConsent();

        return response()->json([
            'status' => 'consented',
            'consent_token' => $granted['token'],
            'consent_version' => $granted['session']->consent_version,
            'non_diagnostic' => true,
        ], Response::HTTP_CREATED);
    }

    public function upload(SmartSmileUploadRequest $request, SmartSmileService $service): JsonResponse
    {
        if (! $service->enabled()) {
            return response()->json(['status' => 'disabled', 'non_diagnostic' => true], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $session = $service->resolve($request->string('consent_token')->toString());

        if ($session === null || $session->revoked_at !== null) {
            return response()->json(['status' => 'consent_required', 'non_diagnostic' => true], Response::HTTP_FORBIDDEN);
        }

        $session = $service->upload($session, $request->file('media'));

        return response()->json($service->publicState($session), Response::HTTP_ACCEPTED);
    }

    public function status(Request $request, SmartSmileService $service): JsonResponse
    {
        if (! $service->enabled()) {
            return response()->json(['status' => 'disabled', 'non_diagnostic' => true], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $token = $request->string('consent_token')->toString();
        $session = $service->resolve($token);

        if ($session === null) {
            return response()->json(['status' => 'not_found', 'non_diagnostic' => true], Response::HTTP_NOT_FOUND);
        }

        return response()->json($service->publicState($session));
    }

    public function delete(Request $request, SmartSmileService $service): JsonResponse
    {
        if (! $service->enabled()) {
            return response()->json(['status' => 'disabled', 'non_diagnostic' => true], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // Expiry prevents further reads/uploads, but the original bearer token
        // remains usable for an explicit deletion request.
        $session = $service->resolve($request->string('consent_token')->toString(), true);

        if ($session === null) {
            return response()->json(['status' => 'not_found', 'non_diagnostic' => true], Response::HTTP_NOT_FOUND);
        }

        $service->delete($session);

        return response()->json([
            'status' => 'deleted',
            'non_diagnostic' => true,
        ]);
    }
}
