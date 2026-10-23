<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'examination_id' => ['required', 'integer', 'exists:examinations,id'],
            'name' => ['required', 'string', 'max:150'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'exam_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['upcoming', 'active', 'completed', 'archived'])],
        ];
    }
}
