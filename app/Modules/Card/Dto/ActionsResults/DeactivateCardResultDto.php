<?php

namespace App\Modules\Card\Dto\ActionsResults;

use App\Base\ActionResultDto;
use App\Base\ErrorDto;

class DeactivateCardResultDto extends ActionResultDto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}
}