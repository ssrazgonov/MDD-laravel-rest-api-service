<?php

namespace App\Modules\Card\Dto\Actions\StatusHistory;

use App\Base\Dto;

class GetCardStatusHistoryDto extends Dto
{
    public string $user_id;
    public string $card_id;
    public ?string $comment;
}