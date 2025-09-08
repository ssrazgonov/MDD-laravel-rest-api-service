<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\ActivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use Illuminate\Support\Facades\DB;
use App\Modules\Card\Models\Card;
use DomainException;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Card\Models\CardAccessControl;
use Illuminate\Database\Eloquent\ModelNotFoundException;


final class ActivateCard extends Action
{
    public function __construct(
        readonly private CreateCardStatusHistory $createCardStatusHistory,
        readonly private DeactivateCard $deactivateCard,
    ) {}

    public function __invoke(ActivateCardDto $dto): ActivateCardResultDto
    {
        DB::beginTransaction();

        try {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && !CardAccessControl::canUserManageCard($dto->user, $card)) {
                throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
            }

            if ($card->status === CardStatus::ACTIVE) {
                throw new DomainException(__('card::errors.already_activated'), 400);
            }

            if ($card->user->hasActiveCard()) {
                $this->handleResult(($this->deactivateCard)(
                    new DeactivateCardDto(id: $card->user->activeCard()->id)
                ));
            }

            $card->update([
                'status' => CardStatus::ACTIVE,
            ]);

            $this->handleResult(($this->createCardStatusHistory)(
                new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment)
            ));

            DB::commit();

            return new ActivateCardResultDto(status: true);
        } catch (ModelNotFoundException $exception) {
            return new ActivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: __('card::errors.card_not_found'),
                    code: 404
                ),
            );
        } catch (Throwable $excepion) {
            DB::rollBack();
            return new ActivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: $excepion->getMessage(),
                    code: $excepion->getCode()
                ),
            );
        }
    }
}
