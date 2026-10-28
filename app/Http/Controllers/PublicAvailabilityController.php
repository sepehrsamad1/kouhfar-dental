<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Availability\AvailabilityRepository;
use App\Service;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public, versioned, capacity-redacted view of availability. */
final class PublicAvailabilityController
{
    public function __invoke(Request $request, Service $service, AvailabilityRepository $availability): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $date = $validated['date'];
        $slots = $availability->forPublicService($service, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $publicSlots = array_values(array_map(
            static fn (array $slot): array => [
                'start_at' => $slot['start_at'],
                'end_at' => $slot['end_at'],
            ],
            array_filter($slots, static fn (array $slot): bool => str_starts_with($slot['display_local'], $date)),
        ));

        return response()->json([
            'data' => [
                'date' => $date,
                'timezone' => AvailabilityRepository::TIMEZONE,
                'slots' => $publicSlots,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }
}
