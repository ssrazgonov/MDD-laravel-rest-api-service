<?php

namespace App\Modules\Card\Dto;

use App\Base\Dto;
use Illuminate\Support\Collection;

class CardStatusHistoryListDto extends Dto
{
    public function __construct(
        public ?Collection $data = null,
    ) {
        parent::__construct([]);
    }
}