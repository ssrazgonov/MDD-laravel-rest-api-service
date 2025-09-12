<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\GetCardStatusHistory;
use App\Modules\Card\Dto\Actions\GetCardWithHistoryDto;
use App\Modules\Card\Dto\Actions\StatusHistory\GetCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\GetCardWithHistoryResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

final class GetCardWithHistory extends Action
{
    public function __construct(
        private readonly GetCardStatusHistory $getCardStatusHistory,
    ) {
    }

    public function __invoke(GetCardWithHistoryDto $dto): GetCardWithHistoryResultDto
    {
        try {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && ! CardAccessControl::canUserViewCard($dto->user, $card)) {
                throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
            }

            $historyDto = ($this->getCardStatusHistory)(GetCardStatusHistoryDto::fromArray([
                'cardId' => $card->id,
            ]));

            return new GetCardWithHistoryResultDto(
                status: true,
                data: [
                    'card' => $card,
                    'history' => $historyDto->data,
                ]
            );
        } catch (ModelNotFoundException) {
            return new GetCardWithHistoryResultDto(
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
        } catch (Throwable $exception) {
            return new GetCardWithHistoryResultDto(
                status: false,
                error: new ErrorDto(
                    error: $exception->getMessage(),
                    code: $exception->getCode() ?: 400
                ),
                data: [
                    'card' => null,
                    'history' => null,
                ]
            );
        }
    }
}
