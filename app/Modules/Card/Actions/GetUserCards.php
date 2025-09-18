<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetUserCardsResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

final class GetUserCards extends Action
{
    public function __invoke(GetUserCardsDto $dto): GetUserCardsResultDto
    {
        $user = Auth::user();

        if (! $user) {
            return new GetUserCardsResultDto(
                status: false,
                error: new ErrorDto('User not authenticated', 401)
            );
        }

        $cardIds = collect();

        /** @var User $user */
        $userGroupMembers = $user->groupMembers()->pluck('id');
        if ($userGroupMembers->isNotEmpty()) {
            $ownedCards = Card::whereIn('member_id', $userGroupMembers)->pluck('id');
            $cardIds = $cardIds->merge($ownedCards);
        }

        $uniqueCardIds = $cardIds->unique();

        if ($uniqueCardIds->isEmpty()) {
            return new GetUserCardsResultDto(status: true, data: collect());
        }

        $query = Card::whereIn('id', $uniqueCardIds);

        if ($dto->status) {
            $query->statusFilter($dto->status);
        }

        $cards = $query->get();

        return new GetUserCardsResultDto(status: true, data: $cards);
    }
}
