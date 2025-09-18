<?php

namespace App\Modules\Card\Dto\Actions\CardAccess;

use App\Base\Dto;
use App\Modules\Card\Enums\CardAccessLevel;

class ProvideCardAccessDto extends Dto
{
    public function __construct(
        public string $groupMemberId,
        public string $cardId,
        public CardAccessLevel $level,
    ) {
    }
}
