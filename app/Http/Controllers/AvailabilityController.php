<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Availability\AvailabilityRepository;
use App\Service;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AvailabilityController
{
    public function __invoke(Request $request, Service $service, AvailabilityRepository $availability): JsonResponse
    {
        $slots = $availability->forPublicService($service, new DateTimeImmutable('now', new \DateTimeZone('UTC')));

        return response()->json([
            'timezone' => AvailabilityRepository::TIMEZONE,
            'slots' => $slots,
        ])->header('Cache-Control', 'no-store, private');
    }
}
