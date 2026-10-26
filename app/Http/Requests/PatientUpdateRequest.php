<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\IranianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class PatientUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') !== null;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:200'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'version' => ['required', 'integer', 'min:1'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile');

        if (is_string($mobile) && trim($mobile) !== '') {
            try {
                $this->merge(['mobile_normalized' => IranianPhoneNumber::normalize($mobile)]);
            } catch (\InvalidArgumentException) {
                $this->merge(['mobile_normalized' => '']);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $normalized = $this->input('mobile_normalized');

            if ($this->filled('mobile') && (! is_string($normalized) || $normalized === '')) {
                $validator->errors()->add('mobile', 'شماره موبایل معتبر ایران وارد کنید.');
            }
        });
    }
}
