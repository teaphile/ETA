<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:6', 'max:300'],
            'answer' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
