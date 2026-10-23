<?php

namespace App\Http\Requests\Admin;

class UpdateCentreRequest extends StoreCentreRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'status' => ['required', 'in:active,archived'],
        ];
    }
}
