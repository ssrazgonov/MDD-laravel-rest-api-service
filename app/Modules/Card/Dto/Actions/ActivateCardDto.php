<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;
use App\Modules\User\Models\User;

class ActivateCardDto extends Dto
{
    public function __construct(
        public int $id,
        public ?string $comment = null,
        public ?User $user = null
    ) {
    }
}
