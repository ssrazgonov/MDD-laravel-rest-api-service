<?php

namespace App\Modules\Card\Actions\StatusHistory;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\StatusHistory\GetCardStatusHistoryDto;
use App\Modules\Card\Dto\CardStatusHistoryListDto;
use App\Modules\Card\Models\CardStatusHistory;

final class GetCardStatusHistory extends Action
{
    public function __invoke(GetCardStatusHistoryDto $getCardStatusHistoryDto): CardStatusHistoryListDto
    {
        $history = CardStatusHistory::where('card_id', $getCardStatusHistoryDto->cardId)
                ->orderBy('created_at', 'desc')
                ->get();

        return new CardStatusHistoryListDto(
            data: $history
        );
    }
}