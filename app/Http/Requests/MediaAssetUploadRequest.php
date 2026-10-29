<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MediaAssetUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') !== null;
    }

    public function rules(): array
    {
        return [
            'media' => ['required', 'file', 'max:10240'],
            'kind' => ['required', 'string', 'in:case_before,case_after,smile_input,other'],
            'owner_patient_id' => ['nullable', 'uuid', 'exists:patients,id'],
        ];
    }
}
