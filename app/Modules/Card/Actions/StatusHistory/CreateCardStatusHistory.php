<?php

namespace App\Modules\Card\Actions\StatusHistory;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\StatusHistory\CreateCardStatusHistoryResultDto;
use App\Modules\Card\Models\CardStatusHistory;
use Throwable;

final class CreateCardStatusHistory extends Action
{
    public function __invoke(CreateCardStatusHistoryDto $createCardStatusHistoryDto): CreateCardStatusHistoryResultDto
    {
        try {
            CardStatusHistory::create([
                'card_id' => $createCardStatusHistoryDto->card->id,
                'status' => $createCardStatusHistoryDto->card->status->value,
                'comment' => $createCardStatusHistoryDto->comment,
                'changed_by' => auth()->id(),
            ]);

            return new CreateCardStatusHistoryResultDto(status: true);
        } catch (Throwable $throwable) {
            return new CreateCardStatusHistoryResultDto(
                status: false,
                error: new ErrorDto(
                    error: $throwable->getMessage(),
                    code: $throwable->getCode(),
                )
            );
        }
    }
}
