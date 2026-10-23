<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isSuspended();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->title) ? preg_replace('/\s+/u', ' ', trim($this->title)) : $this->title,
            'body' => is_string($this->body) ? trim($this->body) : $this->body,
            'city' => is_string($this->city) ? trim($this->city) : $this->city,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:6', 'max:180'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(config('exam_travel.community_categories')))],
            'examination_id' => ['nullable', 'integer', 'exists:examinations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Give your post a clear title of at least 6 characters so other students can find it.',
            'body.min' => 'Add a little more detail (at least 10 characters) so others can actually help.',
        ];
    }
}
