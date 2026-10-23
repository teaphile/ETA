<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? preg_replace('/\s+/u', ' ', trim($this->name)) : $this->name,
            'current_city' => is_string($this->current_city) ? trim($this->current_city) : $this->current_city,
            'phone' => is_string($this->phone) ? preg_replace('/[^\d+]/', '', $this->phone) : $this->phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            // Email changes are intentionally not allowed here: they need re-verification.
            'phone' => ['nullable', 'string', 'max:16', 'regex:/^\+?\d{7,15}$/'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['required', 'in:en,hi'],
        ];
    }
}
