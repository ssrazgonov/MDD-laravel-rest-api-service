<?php

namespace App\Modules\Card\Actions\StatusHistory;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Models\CardStatusHistory;

final class CreateCardStatusHistory extends Action
{
    public function __invoke(CreateCardStatusHistoryDto $createCardStatusHistoryDto): CardStatusHistory
    {
        return CardStatusHistory::create([
            'card_id' => $createCardStatusHistoryDto->card->id,
            'status' => $createCardStatusHistoryDto->card->status->value,
            'comment' => $createCardStatusHistoryDto->comment,
            'changed_by' => $createCardStatusHistoryDto->changedBy,
        ]);
    }
}
