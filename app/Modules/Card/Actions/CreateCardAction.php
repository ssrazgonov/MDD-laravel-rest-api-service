<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\CreateCardActionDto;
use App\Modules\Card\Dto\CardDto;

class CreateCardAction extends Action
{
    public function __invoke(CreateCardActionDto $createCardActionDto): ?CardDto
    {
        return new CardDto([]);
    }
}