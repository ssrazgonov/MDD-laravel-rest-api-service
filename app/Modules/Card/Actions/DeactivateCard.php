<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\DeactivateCardResultDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeactivateCard extends Action
{
    public function __construct(public readonly CreateCardStatusHistory $createCardStatusHistory)
    {
    }

    public function __invoke(DeactivateCardDto $dto): DeactivateCardResultDto
    {
        DB::beginTransaction();

        try {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && ! CardAccessControl::canUserManageCard($dto->user, $card)) {
                throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
            }

            if ($card->status === CardStatus::INACTIVE) {
                throw new DomainException(__('card::errors.already_deactivated'), 400);
            }

            $card->update([
                'status' => CardStatus::INACTIVE,
            ]);

            $this->handleResult(($this->createCardStatusHistory)(
                new CreateCardStatusHistoryDto(card: $card, comment: $dto->comment)
            ));

            DB::commit();

            return new DeactivateCardResultDto(status: true);
        } catch (ModelNotFoundException) {
            return new DeactivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: __('card::errors.card_not_found'),
                    code: 404
                ),
                data: [
                    'card' => null,
                    'history' => null,
                ]
            );
        } catch (Throwable $excepion) {
            DB::rollBack();

            return new DeactivateCardResultDto(
                status: false,
                error: new ErrorDto(
                    error: $excepion->getMessage(),
                    code: $excepion->getCode()
                ),
            );
        }
    }
}
