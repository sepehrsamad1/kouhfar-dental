<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssistantRequest;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;

final class AssistantController
{
    public function __invoke(AssistantRequest $request, AssistantService $assistant): JsonResponse
    {
        $data = $request->validated();

        return response()->json(
            $assistant->respond((string) $data['message'], (string) ($data['locale'] ?? 'fa'))
        );
    }
}
