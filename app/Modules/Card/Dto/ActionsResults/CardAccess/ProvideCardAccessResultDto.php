<?php

namespace App\Modules\Card\Dto\ActionsResults\CardAccess;

use App\Base\ActionResultDto;
use App\Base\ErrorDto;

class ProvideCardAccessResultDto extends ActionResultDto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}