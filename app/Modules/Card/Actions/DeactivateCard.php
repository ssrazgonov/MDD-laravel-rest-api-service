<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\DeactivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class DeactivateCard extends Action
{
    public function __construct(public readonly CreateCardStatusHistory $createCardStatusHistory)
    {
    }

    public function __invoke(DeactivateCardDto $dto): DeactivateCardResultDto
    {
        return DB::transaction(function () use ($dto) {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && ! CardAccessControl::canUserManageCard($dto->user, $card)) {
                throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
            }

            if ($card->status === CardStatus::INACTIVE) {
                throw new DomainException(__('card::errors.already_deactivated'), 400);
            }

            $card->update([
                'status' => CardStatus::INACTIVE,
            ]);

            ($this->createCardStatusHistory)(
                new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment, changedBy: $dto->user->id)
            );

            return new DeactivateCardResultDto(status: true);
        });
    }
}
