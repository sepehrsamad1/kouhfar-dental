<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Availability\AvailabilityRepository;
use App\Service;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;

/** Legacy web availability route. Keep the response compatible but never expose capacity IDs. */
final class AvailabilityController
{
    public function __invoke(Service $service, AvailabilityRepository $availability): JsonResponse
    {
        $slots = $availability->forPublicService($service, new DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $publicSlots = array_map(static fn (array $slot): array => [
            'start_at' => $slot['start_at'],
            'end_at' => $slot['end_at'],
            'display_local' => $slot['display_local'],
            'timezone' => $slot['timezone'],
        ], $slots);

        return response()->json([
            'timezone' => AvailabilityRepository::TIMEZONE,
            'slots' => $publicSlots,
        ])->header('Cache-Control', 'no-store, private');
    }
}
