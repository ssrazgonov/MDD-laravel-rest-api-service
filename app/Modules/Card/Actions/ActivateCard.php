<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\ActivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use Illuminate\Support\Facades\DB;
use App\Modules\Card\Models\Card;
use Throwable;

final readonly class ActivateCard extends Action
{
    public function __construct(readonly public CreateCardStatusHistory $createCardStatusHistory) {}

    public function __invoke(ActivateCardDto $dto): ActivateCardResultDto
    {
        DB::beginTransaction();

        try {
            $card = Card::findOrFail($dto->cardId);

            $card->update([
                'status' => CardStatus::ACTIVE,
            ]);

            $this->handleResult(($this->createCardStatusHistory)(
                new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment)
            ));

            DB::commit();

            return new ActivateCardResultDto(status: true);
        } catch (Throwable $excepion) {
            DB::rollBack();
            return new ActivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: $excepion->getMessage(),
                    code: $excepion->getCode()
                ),
            );
        }
    }
}
