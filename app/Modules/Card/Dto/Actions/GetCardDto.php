<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\User\Models\User;

class GetCardDto extends Dto
{
    public function __construct(
        public readonly ?int $memberId = null,
        public readonly int $cardId,
        public readonly User $user,
    ) {
    }
}
