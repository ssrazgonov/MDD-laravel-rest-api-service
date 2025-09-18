<?php

namespace App\Modules\Card\Dto\ActionsResults;

use App\Base\ActionResultDto;
use App\Modules\Card\Models\Card;

class GetMemberCardResultDto extends ActionResultDto
{
    /**
     * @param Card|null $data
     */
    public function __construct(
        bool $status,
        ?Card $data = null,
        mixed $error = null,
    ) {
        parent::__construct($status, $error, $data);
    }
}
