<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetUserCardsResultDto;
use App\Modules\Card\Models\Card;

final class GetUserCards extends Action
{
    public function __invoke(GetUserCardsDto $dto): GetUserCardsResultDto
    {
        $cards = Card::forUser($dto->userId)
            ->statusFilter($dto->status)
            ->get();

        return new GetUserCardsResultDto(status: true, data: $cards);
    }
}