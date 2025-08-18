<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class DeactivateCardDto extends Dto
{
    public string $user_id;
    public string $card_id;
    public ?string $comment;
    
    protected function rules(): array
    {
        return [];
    }
}