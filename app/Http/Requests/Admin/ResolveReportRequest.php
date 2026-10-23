<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canModerateContent() ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:dismiss,hide,remove,warn'],
            'moderator_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
