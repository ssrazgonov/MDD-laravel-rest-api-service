<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\Card\Enums\CardStatus;

class GetMemberCardsDto extends Dto
{
    public function __construct(
        public int $memberId,
        public ?CardStatus $status = null,
        public ?object $user = null,
    ) {
        parent::__construct([]);
    }
}
