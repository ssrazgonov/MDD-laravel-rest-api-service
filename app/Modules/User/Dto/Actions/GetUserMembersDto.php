<?php

namespace App\Modules\User\Dto\Actions;

use App\Base\Dto;

class GetUserMembersDto extends Dto
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $memberType = null,
        public readonly ?bool $active = true
    ) {
    }
}
