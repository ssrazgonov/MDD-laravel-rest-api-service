<?php

namespace App\Modules\Card\Dto\ActionsResults\CardAccess;

use App\Base\Dto;
use App\Base\ErrorDto;

class ProvideCardAccessResultDto extends Dto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}