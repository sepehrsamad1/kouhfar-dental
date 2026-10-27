<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\IranianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class CreatePublicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile')) {
            try {
                $this->merge(['mobile' => IranianPhoneNumber::normalize((string) $this->input('mobile'))]);
            } catch (\InvalidArgumentException) {
                // Let the normal validation response remain a generic 422.
            }
        }
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'uuid'],
            'start_at' => ['required', 'string', 'max:80'],
            'full_name' => ['required', 'string', 'min:3', 'max:200'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ];
    }

    public function idempotencyKey(): string
    {
        $key = trim((string) $this->header('Idempotency-Key'));

        if ($key === '' || ! preg_match('/^[A-Za-z0-9._:-]{1,200}$/', $key)) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => 'A valid Idempotency-Key header is required.',
            ]);
        }

        return $key;
    }
}
