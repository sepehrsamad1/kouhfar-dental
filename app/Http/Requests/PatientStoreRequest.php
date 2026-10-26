<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\IranianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user('staff')?->role, ['admin', 'reception'], true);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:200'],
            'mobile' => ['required', 'string', 'max:40'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile');

        if (is_string($mobile)) {
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

            if (! is_string($normalized) || $normalized === '') {
                $validator->errors()->add('mobile', 'شماره موبایل معتبر ایران وارد کنید.');
            }
        });
    }
}
