<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'content_key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'kind' => ['required', Rule::enum(ContentKind::class)],
            'locale' => ['required', 'string', 'in:fa,en'],
            'approval_scope' => ['required', 'string', 'in:general,clinical,pricing,testimonial'],
            'body' => ['required', 'array:title,text', 'required_array_keys:title,text'],
            'body.title' => ['required', 'string', 'max:240'],
            'body.text' => ['required', 'string', 'max:20000'],
        ];
    }
}
