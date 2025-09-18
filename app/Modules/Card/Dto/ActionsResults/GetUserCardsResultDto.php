<?php

namespace App\Modules\Card\Dto\ActionsResults;

use App\Base\ActionResultDto;
use App\Modules\Card\Models\Card;
use Illuminate\Support\Collection;

class GetUserCardsResultDto extends ActionResultDto
{
    /**
     * @param Collection<int, Card>|null $data
     */
    public function __construct(
        bool $status,
        ?Collection $data = null,
        mixed $error = null,
    ) {
        parent::__construct($status, $error, $data);
    }
}
