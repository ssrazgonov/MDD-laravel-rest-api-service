<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetUserCardsResultDto;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

final class GetUserCards extends Action
{
    public function __invoke(GetUserCardsDto $dto): GetUserCardsResultDto
    {
        /** @var ?User $user */
        $user = Auth::user();

        if (! $user) {
            return new GetUserCardsResultDto(
                status: false,
                error: new ErrorDto('User not authenticated', 401)
            );
        }

        $query = $user->groupMember->cards();

        if ($dto->status) {
            $query->statusFilter($dto->status);
        }

        $cards = $query->get();

        return new GetUserCardsResultDto(status: true, data: $cards);
    }
}
