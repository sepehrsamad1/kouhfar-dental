<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service;
use Illuminate\Http\JsonResponse;

/** Exposes only the public catalog required by the booking UI. */
final class PublicServicesController
{
    public function __invoke(): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->where('is_public_bookable', true)
            ->orderBy('name_fa')
            ->get(['id', 'name_fa', 'duration_minutes'])
            ->map(static fn (Service $service): array => [
                'id' => (string) $service->getKey(),
                'name_fa' => $service->name_fa,
                'duration_minutes' => $service->duration_minutes,
            ])
            ->values();

        return response()->json(['data' => $services])
            ->header('Cache-Control', 'no-store, private');
    }
}
