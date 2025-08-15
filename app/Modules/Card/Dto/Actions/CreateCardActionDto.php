<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class CreateCardActionDto extends Dto
{
    public int $user_id;
    public string $pan;
    public ?string $comment;
    
    protected function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'pan' => 'required|string|regex:/^\d{16,19}$/',
            'owner_name' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000'
        ];
    }
}