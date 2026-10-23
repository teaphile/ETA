<?php

namespace App\Http\Requests\Admin;

use App\Integrations\Support\DataStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreCentreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'centre_code' => is_string($this->centre_code) ? mb_strtoupper(preg_replace('/\s+/', '', trim($this->centre_code))) : $this->centre_code,
            'name' => is_string($this->name) ? preg_replace('/\s+/u', ' ', trim($this->name)) : $this->name,
            'city' => is_string($this->city) ? trim($this->city) : $this->city,
            'state' => is_string($this->state) ? trim($this->state) : $this->state,
        ]);
    }

    public function rules(): array
    {
        return [
            'examination_id' => ['nullable', 'integer', 'exists:examinations,id'],
            'exam_session_id' => ['nullable', 'integer', 'exists:exam_sessions,id'],
            'centre_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:250'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pin_code' => ['nullable', 'string', 'max:10', 'regex:/^\d{4,10}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'verification_status' => ['required', 'in:'.implode(',', DataStatus::values())],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'provider_reference' => ['nullable', 'string', 'max:190'],
            'error_or_limitation' => ['nullable', 'string', 'max:500'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required_with' => 'Provide both latitude and longitude, or leave both empty — half a coordinate is not usable.',
            'longitude.required_with' => 'Provide both latitude and longitude, or leave both empty — half a coordinate is not usable.',
            'pin_code.regex' => 'PIN code should be 4–10 digits.',
        ];
    }
}
