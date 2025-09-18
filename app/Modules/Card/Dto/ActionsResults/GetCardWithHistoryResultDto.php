<?php

namespace App\Modules\Card\Dto\ActionsResults;

use App\Base\ActionResultDto;
use App\Modules\Card\Models\Card;

class GetCardWithHistoryResultDto extends ActionResultDto
{
    /**
     * @param array{card: Card, history: mixed[]}|null $data
     */
    public function __construct(
        bool $status,
        ?array $data = null,
        mixed $error = null,
    ) {
        parent::__construct($status, $error, $data);
    }
}
