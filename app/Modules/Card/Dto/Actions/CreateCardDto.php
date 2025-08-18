<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class CreateCardDto extends Dto
{
    public function __construct(
        public string $userId,
        public string $pan,
        public ?string $comment,
    ){}
}