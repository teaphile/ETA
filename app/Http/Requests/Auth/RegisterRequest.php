<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? preg_replace('/\s+/u', ' ', trim($this->name)) : $this->name,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'phone' => is_string($this->phone) ? preg_replace('/[^\d+]/', '', $this->phone) : $this->phone,
            'current_city' => is_string($this->current_city) ? trim($this->current_city) : $this->current_city,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:16', 'regex:/^\+?\d{7,15}$/'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'in:en,hi'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the terms and the privacy notice to create an account.',
            'phone.regex' => 'Enter a valid phone number, digits only (an optional leading + is allowed).',
            'email.unique' => 'An account with this email already exists. Try signing in or resetting your password.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_city' => 'current city',
            'preferred_language' => 'preferred language',
        ];
    }
}
