<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetCardWithHistoryDto;
use App\Modules\Card\Dto\ActionsResults\GetCardWithHistoryResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use DomainException;
use Throwable;

final readonly class GetCardWithHistory extends Action
{
    public function __construct(
        readonly private \App\Modules\Card\Actions\StatusHistory\GetCardStatusHistory $getCardStatusHistory,
    ) {}

    public function __invoke(GetCardWithHistoryDto $dto): GetCardWithHistoryResultDto
    {
        try {
            $card = Card::findOrFail($dto->id);

            if ($dto->user && !CardAccessControl::canUserViewCard($dto->user, $card)) {
                throw new DomainException('У вас нет прав для просмотра этой карты');
            }

            $historyDto = ($this->getCardStatusHistory)(\App\Modules\Card\Dto\Actions\StatusHistory\GetCardStatusHistoryDto::fromArray([
                'cardId' => $card->id
            ]));

            return new GetCardWithHistoryResultDto(
                status: true,
                data: [
                    'card' => $card,
                    'history' => $historyDto->data
                ]
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            return new GetCardWithHistoryResultDto(
                status: false,
                error: new ErrorDto(
                    error: 'Карта не найдена',
                    code: 404
                ),
                data: [
                    'card' => null,
                    'history' => null
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
                    'history' => null
                ]
            );
        }
    }
}