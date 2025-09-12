<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetUserCardsResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;

final class GetUserCards extends Action
{
    public function __invoke(GetUserCardsDto $dto): GetUserCardsResultDto
    {
        $user = auth()->user();

        if (! $user) {
            return new GetUserCardsResultDto(
                status: false,
                error: new ErrorDto('User not authenticated', 401)
            );
        }

        // Собираем все карты, к которым пользователь имеет доступ
        $cardIds = collect();

        // 1. Карты primary group member пользователя
        $primaryGroupMember = $user->primaryGroupMember();
        if ($primaryGroupMember) {
            $primaryCards = Card::forMember($primaryGroupMember->id)->pluck('id');
            $cardIds = $cardIds->merge($primaryCards);
        }

        // 2. Карты, к которым предоставлен доступ через CardAccessControl
        $accessCardIds = CardAccessControl::where('user_id', $user->id)
            ->pluck('card_id');
        $cardIds = $cardIds->merge($accessCardIds);

        // Получаем уникальные карты
        $uniqueCardIds = $cardIds->unique();

        if ($uniqueCardIds->isEmpty()) {
            return new GetUserCardsResultDto(status: true, data: collect());
        }

        $cards = Card::whereIn('id', $uniqueCardIds)
            ->statusFilter($dto->status)
            ->get();

        return new GetUserCardsResultDto(status: true, data: $cards);
    }
}
