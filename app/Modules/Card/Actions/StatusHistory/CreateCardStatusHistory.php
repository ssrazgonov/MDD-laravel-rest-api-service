<?php

namespace App\Modules\Card\Actions\StatusHistory;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\ErrorDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\StatusHistory\CreateCardStatusHistoryResultDto;
use App\Modules\Card\Models\CardStatusHistory;
use Throwable;

final readonly class CreateCardStatusHistory extends Action
{
    protected function __invoke(CreateCardStatusHistoryDto $createCardStatusHistoryDto): CreateCardStatusHistoryResultDto
    {
        try {
            CardStatusHistory::create([
                'card_id' => $createCardStatusHistoryDto->card->id,
                'status' => $createCardStatusHistoryDto->card->status->value,
                'comment' => $createCardStatusHistoryDto->comment,
                'created_by' => auth()->user,
            ]);

            return new CreateCardStatusHistoryResultDto(status: true);
        } catch (Throwable $exception) {
            return new CreateCardStatusHistoryResultDto(
                status: false,
                error: new ErrorDto(
                    error: $exception->getMessage(),
                    code: $exception->getCode(),
                )
            );
        }
    }
}