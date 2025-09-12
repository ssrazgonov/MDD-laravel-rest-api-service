<?php

namespace App\Modules\Card\Dto\Actions;

use App\Base\Dto;

class CreateCardDto extends Dto
{
    public function __construct(
        public string $pan,
        public ?string $comment,
        public ?int $memberId = null, // Опционально, если null - используем primary member
    ) {
        parent::__construct([]);
    }
}
