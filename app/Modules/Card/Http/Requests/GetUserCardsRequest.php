<?php

namespace App\Modules\Card\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserCardsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes','nullable', 'string', 'in:active,inactive'],
        ];
    }
}