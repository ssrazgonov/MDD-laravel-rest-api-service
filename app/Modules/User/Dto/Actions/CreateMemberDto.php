<?php

namespace App\Modules\User\Dto\Actions;

use App\Base\Dto;

class CreateMemberDto extends Dto
{
    public function __construct(
        public readonly int $groupId,
        public readonly ?string $userId,
        public readonly string $type = 'dependent',
        public readonly ?string $name = null,
    ) {
    }
}
