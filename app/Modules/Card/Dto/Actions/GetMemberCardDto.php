<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class GetMemberCardDto extends Dto
{
    public function __construct(
        public int $memberId,
        public int $cardId,
        public ?object $user = null,
    ) {
        parent::__construct([]);
    }
}
