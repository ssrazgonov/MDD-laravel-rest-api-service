<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\CardDto;
use App\Modules\Card\Models\Card;
use App\Enums\CardStatus;

final readonly class ActivateCard extends Action
{
    protected function __invoke(ActivateCardDto $dto): ?CardDto
    {
        $card = Card::where('id', $dto->card_id)
                    ->where('user_id', $dto->user_id)
                    ->first();

        if (!$card) {
            throw new \Exception("Карта не найдена или не принадлежит пользователю.");
        }

        if ($card->status === CardStatus::ACTIVE) {
            throw new \Exception("Карта уже активна.");
        }

        if (Card::hasActiveCard($dto->user_id)) {
            throw new \Exception("У вас уже есть активная карта. Сначала деактивируйте её.");
        }

        $card->update([
            'status' => CardStatus::ACTIVE,
        ]);


        $card->logStatusChange(CardStatus::ACTIVE, 'Карта активирована пользователем');

        return new CardDto([
            'id' => $card->id,
            'user_id' => $card->user_id,
            'last_4_digits' => $card->last_4_digits,
            'owner_name' => $card->owner_name,
            'status' => $card->status->value,
            'comment' => $card->comment,
            'created_at' => $card->created_at->toISOString(),
            'updated_at' => $card->updated_at->toISOString(),
        ]);
    }
}