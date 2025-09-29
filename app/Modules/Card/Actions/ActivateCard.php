<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\ActivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ActivateCard extends Action
{
    public function __construct(
        private readonly CreateCardStatusHistory $createCardStatusHistory,
        private readonly DeactivateCard $deactivateCard,
    ) {
    }

    public function __invoke(ActivateCardDto $dto): ActivateCardResultDto
    {
        return DB::transaction(function () use ($dto) {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && ! CardAccessControl::canUserManageCard($dto->user, $card)) {
                throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
            }

            if ($card->status === CardStatus::ACTIVE) {
                throw new DomainException(__('card::errors.already_activated'), 400);
            }

            if ($card->groupMember->hasActiveCard()) {
                ($this->deactivateCard)(new DeactivateCardDto(id: $card->groupMember->activeCards()->select('id')->first()->id));
            }

            $card->update([
                'status' => CardStatus::ACTIVE,
            ]);

            ($this->createCardStatusHistory)(new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment, changedBy: $dto->user->id));

            return new ActivateCardResultDto(status: true);
        });
    }
}
