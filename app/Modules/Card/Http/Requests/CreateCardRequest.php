<?php

namespace App\Modules\Card\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pan' => [
                'required',
                'string',
                'regex:/^2[0-9]{15,18}$/',
            ],
            'comment' => 'nullable|string|max:256',

            'memberId' => 'nullable|integer|exists:group_members,id',
        ];
    }
}
