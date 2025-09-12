<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\Card\Enums\CardStatus;

class GetUserCardsDto extends Dto
{
    public function __construct(
        public string $userId,
        public ?CardStatus $status = null
    ) {
    }
}
