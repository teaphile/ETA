<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::in([User::ROLE_STUDENT, User::ROLE_MODERATOR, User::ROLE_ADMIN])],
            'account_status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ];
    }
}
