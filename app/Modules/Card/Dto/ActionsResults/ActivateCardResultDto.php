<?php

namespace App\Modules\Card\Dto\ActionsResults;

use App\Base\Dto;
use App\Base\ErrorDto;

class ActivateCardResultDto extends Dto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}