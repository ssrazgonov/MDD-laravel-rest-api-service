<?php

namespace App\Modules\Card\Dto\ActionsResults\StatusHistory;

use App\Base\ActionResultDto;
use App\Base\ErrorDto;

class CreateCardStatusHistoryResultDto extends ActionResultDto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}