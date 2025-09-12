<?php

namespace App\Modules\User\Dto\Actions;

use App\Base\Dto;

class LinkChildUserDto extends Dto
{
    public function __construct(
        public readonly string $identityType,
        public readonly string $identityValue,
        public readonly string $childUserId
    ) {
    }
}
