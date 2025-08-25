<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class ActivateCardDto extends Dto
{
    public function __construct(
        public int $id,
        public ?string $comment = null
    ){}
}