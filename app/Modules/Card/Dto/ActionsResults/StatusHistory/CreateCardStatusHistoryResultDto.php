<?php

namespace App\Modules\Card\Dto\ActionsResults\StatusHistory;

use App\Base\Dto;
use App\Base\ErrorDto;

class CreateCardStatusHistoryResultDto extends Dto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}