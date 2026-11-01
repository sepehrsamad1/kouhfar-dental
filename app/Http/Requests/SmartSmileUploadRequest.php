<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SmartSmileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consent_token' => ['required', 'string', 'size:64'],
            'media' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
        ];
    }
}
