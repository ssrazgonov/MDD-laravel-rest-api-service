<?php

namespace App\Modules\Card\Actions\CardAccess;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\CardAccess\ProvideCardAccessDto;
use App\Modules\Card\Dto\ActionsResults\CardAccess\ProvideCardAccessResultDto;
use App\Modules\Card\Models\CardAccessControl;

final readonly class ProvideCardAccess extends Action
{
    public function __invoke(ProvideCardAccessDto $provideCardAccessDto): ProvideCardAccessResultDto
    {
        CardAccessControl::updateOrCreate(
            [
                'card_id' => $provideCardAccessDto->cardId,
                'user_id' => $provideCardAccessDto->userId,
            ],
            [
                
            ]
        );

        return new ProvideCardAccessResultDto(status: true);
    }
}