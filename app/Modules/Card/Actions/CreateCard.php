<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Actions\CardAccess\ProvideCardAccess;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\CardAccess\ProvideCardAccessDto;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\ActionsResults\CreateCardResultDto;
use App\Modules\Card\Enums\CardAccessLevel;
use Illuminate\Support\Facades\DB;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use Throwable;

final readonly class CreateCard extends Action
{
    public function __construct(
        readonly private ProvideCardAccess $provideCardAccess,
        readonly private ActivateCard $activateCard,
        readonly public CreateCardStatusHistory $createCardStatusHistory
    ) {}

    public function __invoke(CreateCardDto $createCardActionDto): ?CreateCardResultDto
    {
        DB::beginTransaction();

        try {
            $hashedPan = hash('sha256', $createCardActionDto->pan);

            if (Card::existsForUserWithHash($createCardActionDto->userId, $hashedPan)) {
                throw new \DomainException("Эта карта уже добавлена в ваш профиль.", 409);
            }

            $card = Card::create([
                'user_id' => $createCardActionDto->userId,
                'hash' => $hashedPan,
                'last_digits' => substr($createCardActionDto->pan, -4),
                'status' => CardStatus::INACTIVE,
                'comment' => $createCardActionDto->comment ?? null,
            ]);

            if ($card->shouldAutoActivate()) {
                $this->handleResult(($this->activateCard)(new ActivateCardDto(
                    cardId: $card->id,
                    comment: 'Карта создана и активирована'
                )));
            } else {
                $this->handleResult(($this->createCardStatusHistory)(
                    new CreateCardStatusHistoryDto(card: $card, comment: 'Карта создана')
                ));
            }

            $this->handleResult(
                ($this->provideCardAccess)(new ProvideCardAccessDto(
                    userId: $createCardActionDto->userId,
                    cardId: $card->id,
                    level: CardAccessLevel::MANAGE
                ))
            );

            $card->refresh();

            DB::commit();

            return new CreateCardResultDto(card: $card, status: true);
        } catch (Throwable $exception) {
            DB::rollBack();
            return new CreateCardResultDto(
                error: new ErrorDto(error: $exception->getMessage(), code: $exception->getCode()),
                status: false
            );
        }
    }
}
