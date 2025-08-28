<?php

namespace App\Modules\Card\Dto;

use App\Base\Dto;
use Illuminate\Support\Collection;
use App\Modules\Card\Models\Card;

class CardCollectionDto extends Dto
{
    /**
     * @param Collection<int, Card>|null $cards
     */
    public function __construct(public ?Collection $cards = null)
    {}
}