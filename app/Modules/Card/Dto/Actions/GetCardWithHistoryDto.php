<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\User\Models\User;

class GetCardWithHistoryDto extends Dto
{
    public function __construct(
        public int $id,
        public ?User $user = null
    ) {
    }
}
