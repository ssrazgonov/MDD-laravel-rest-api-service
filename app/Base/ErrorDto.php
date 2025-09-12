<?php

namespace App\Base;

use App\Base\Dto;

class ErrorDto extends Dto
{
    public function __construct(
        public ?string $error,
        public ?int $code,
    ) {
    }
}
