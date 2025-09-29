<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\Card\Enums\CardStatus;

class GetCardsDto extends Dto
{
    public function __construct(
        public ?int $memberId = null,
        public ?CardStatus $status = null,
        public object $user,
    ) {
    }
}
