<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\IranianPhoneNumber;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class AppointmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile');

        if (is_string($mobile)) {
            try {
                $mobile = IranianPhoneNumber::normalize($mobile);
            } catch (InvalidArgumentException) {
                // The validator returns the client-safe validation error.
            }
        }

        $this->merge([
            'mobile' => $mobile,
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:200'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'service_id' => [
                'required',
                'uuid',
                Rule::exists('services', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_public_bookable', true)),
            ],
            'start_at' => ['required', 'string', 'max:64'],
            'idempotency_key' => [
                'required',
                'string',
                'min:16',
                'max:200',
                'regex:/^[A-Za-z0-9._~-]+$/',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            try {
                new DateTimeImmutable((string) $this->input('start_at'));
            } catch (\Throwable) {
                $validator->errors()->add('start_at', 'The start_at value is invalid.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'An Idempotency-Key header is required.',
            'idempotency_key.regex' => 'The Idempotency-Key header contains invalid characters.',
        ];
    }
}
