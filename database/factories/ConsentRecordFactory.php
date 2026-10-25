<?php

declare(strict_types=1);

namespace Database\Factories;

use App\ConsentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConsentRecord>
 */
class ConsentRecordFactory extends Factory
{
    protected $model = ConsentRecord::class;

    public function definition(): array
    {
        return [
            'patient_id' => (string) Str::uuid(),
            'scope' => 'synthetic-test-only',
            'granted_at' => null,
            'revoked_at' => null,
            'document_ref' => null,
        ];
    }
}
