<?php

namespace App\Modules\Card\Dto\Actions\StatusHistory;

use App\Base\Dto;

class GetCardStatusHistoryDto extends Dto
{
    public function __construct(public string $cardId)
    {
    }
}
