<?php

declare(strict_types=1);

namespace AppHttpRequests;

use IlluminateFoundationHttpFormRequest;

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