<?php

namespace App\Http\Requests\Admin;

use App\Integrations\Support\DataStatus;
use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('exam_travel.service_categories')))],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'exam_centre_id' => ['nullable', 'integer', 'exists:exam_centres,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:500'],
            'opening_hours' => ['nullable', 'string', 'max:200'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'reviews_count' => ['nullable', 'integer', 'min:0'],
            'price_from' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'source_name' => ['required', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'data_status' => ['required', 'in:'.implode(',', DataStatus::values())],
            'error_or_limitation' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_name.required' => 'Record where this listing came from (for example "Verified by phone call on 12 Oct 2026" or the provider name). Provenance is mandatory.',
        ];
    }
}
