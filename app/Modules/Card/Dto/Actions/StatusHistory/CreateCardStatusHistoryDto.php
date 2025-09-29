<?php

namespace App\Modules\Card\Dto\Actions\StatusHistory;

use App\Base\Dto;
use App\Modules\Card\Models\Card;

class CreateCardStatusHistoryDto extends Dto
{
    public function __construct(
        public Card $card,
        public ?string $comment = null,
        public string $changedBy
    ) {
    }
}
