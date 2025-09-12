<?php

namespace App\Modules\Card\Dto;

use App\Base\Dto;
use App\Modules\Card\Models\Card;
use Illuminate\Support\Collection;

class CardWithHistoryDto extends Dto
{
    public function __construct(
        public ?Card $card = null,
        public ?Collection $history = null,
    ) {
    }
}
