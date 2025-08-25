<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\ActivateCardResultDto;
use App\Modules\Card\Dto\ActionsResults\DeactivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use Illuminate\Support\Facades\DB;
use App\Modules\Card\Models\Card;
use Throwable;
use DomainException;

final readonly class DeactivateCard extends Action
{
    public function __construct(readonly public CreateCardStatusHistory $createCardStatusHistory) {}

    public function __invoke(DeactivateCardDto $dto): DeactivateCardResultDto
    {
        DB::beginTransaction();

        try {
            $card = Card::findOrFail($dto->id);

            if ($card->status === CardStatus::INACTIVE) {
                throw new DomainException('Карта уже деактивирована');
            }

            $card->update([
                'status' => CardStatus::INACTIVE,
            ]);

            $this->handleResult(($this->createCardStatusHistory)(
                new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment)
            ));

            DB::commit();

            return new DeactivateCardResultDto(status: true);
        } catch (Throwable $excepion) {
            DB::rollBack();
            return new DeactivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: $excepion->getMessage(),
                    code: $excepion->getCode()
                ),
            );
        }
    }
}