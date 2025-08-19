<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class DeactivateCardDto extends Dto
{
    public function __construct(
        public int $cardId,
        public ?string $comment = null
    ){}
}