<?php

namespace App\Modules\User\Dto\ActionsResults;

use App\Base\ActionResultDto;
use App\Base\ErrorDto;

class CreateMemberResultDto extends ActionResultDto
{
    public function __construct(
        bool $status = true,
        ?ErrorDto $error = null,
        mixed $data = null
    ) {
        parent::__construct($status, $error, $data);
    }
}
