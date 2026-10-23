<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanJourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin_city' => is_string($this->origin_city) ? preg_replace('/\s+/u', ' ', trim($this->origin_city)) : $this->origin_city,
            'origin_address' => is_string($this->origin_address) ? preg_replace('/\s+/u', ' ', trim($this->origin_address)) : $this->origin_address,
            'modes' => array_values(array_intersect((array) $this->input('modes', []), ['train', 'bus', 'flight', 'car'])),
        ]);
    }

    public function rules(): array
    {
        return [
            'origin_city' => ['required', 'string', 'min:2', 'max:100'],
            'origin_address' => ['nullable', 'string', 'max:200'],
            'origin_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:origin_lng'],
            'origin_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:origin_lat'],
            'centre_id' => ['required', 'integer', 'exists:exam_centres,id'],
            'departure_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'budget' => ['nullable', 'integer', 'min:0', 'max:500000'],
            'modes' => ['required', 'array', 'min:1'],
            'modes.*' => ['string', 'in:train,bus,flight,car'],
            'save' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'modes.required' => __('messages.journey.select_modes_error'),
            'modes.min' => __('messages.journey.select_modes_error'),
            'origin_city.required' => 'Tell us where you are starting from — a city name is enough.',
            'centre_id.exists' => 'That examination centre is no longer available. Please pick another one.',
            'departure_date.after_or_equal' => 'Choose today or a future date. Past dates cannot be planned.',
        ];
    }

    public function attributes(): array
    {
        return [
            'origin_city' => 'starting city',
            'centre_id' => 'destination exam centre',
            'departure_date' => 'departure date',
        ];
    }
}
