<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'message' => is_string($this->message) ? trim($this->message) : $this->message,
        ]);
    }

    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'min:2',
                'max:'.(int) config('exam_travel.assistant.max_question_length', 1000),
            ],
            'itinerary_id' => ['nullable', 'integer', 'exists:itineraries,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Type a question so the assistant has something to look up.',
            'message.max' => 'Please keep your question under :max characters.',
        ];
    }
}
