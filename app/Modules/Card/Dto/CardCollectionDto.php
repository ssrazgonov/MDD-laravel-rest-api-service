<?php

namespace App\Modules\Card\Dto;

use App\Base\Dto;
use App\Modules\Card\Models\Card;
use Illuminate\Support\Collection;

class CardCollectionDto extends Dto
{
    /**
     * @param Collection<int, Card>|null $cards
     */
    public function __construct(public ?Collection $cards = null)
    {
    }
}
