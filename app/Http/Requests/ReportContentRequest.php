<?php

namespace App\Http\Requests;

use App\Models\CommunityPost;
use App\Models\CommunityReply;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportContentRequest extends FormRequest
{
    /** Whitelist — the client can never name an arbitrary class. */
    public const TYPES = [
        'post' => CommunityPost::class,
        'reply' => CommunityReply::class,
    ];

    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isSuspended();
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'target_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function targetClass(): string
    {
        return self::TYPES[$this->string('target_type')->toString()];
    }

    /**
     * The reason is stored as plain text and rendered escaped, so no rich-text
     * sanitisation library is needed and no HTML can be injected.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => is_string($this->reason) ? trim($this->reason) : $this->reason]);
    }
}
