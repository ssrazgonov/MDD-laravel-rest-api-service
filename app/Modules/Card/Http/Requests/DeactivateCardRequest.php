<?php

namespace App\Modules\Card\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateCardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'card_id' => [
                'required',
                'string',
            ],
        ];
    }
}