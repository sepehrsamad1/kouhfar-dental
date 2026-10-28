<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentItemUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'approval_scope' => ['required', 'string', 'in:general,clinical,pricing,testimonial'],
            'body' => ['required', 'array:title,text', 'required_array_keys:title,text'],
            'body.title' => ['required', 'string', 'max:240'],
            'body.text' => ['required', 'string', 'max:20000'],
        ];
    }
}
