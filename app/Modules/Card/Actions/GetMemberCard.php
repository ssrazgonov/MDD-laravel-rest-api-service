<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\StatusHistory\GetCardStatusHistory;
use App\Modules\Card\Dto\Actions\GetMemberCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\GetCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\GetCardWithHistoryResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

final class GetMemberCard extends Action
{
    public function __construct(
        private readonly GetCardStatusHistory $getCardStatusHistory,
    ) {
    }

    public function __invoke(GetMemberCardDto $dto): GetCardWithHistoryResultDto
    {
        try {
            $member = GroupMember::findOrFail($dto->memberId);

            $userIsPrimaryMember = $member->group->groupMembers()->primary()->where('id', $dto->user->group_member_id)->exists();

            if ($dto->user && ! $userIsPrimaryMember) {
                throw new AuthorizationException('Access denied to member card', 403);
            }

            $card = Card::where('id', $dto->cardId)
                ->where('group_member_id', $dto->memberId)
                ->firstOrFail();

            $historyDto = ($this->getCardStatusHistory)(GetCardStatusHistoryDto::fromArray([
                'cardId' => $card->id,
            ]));

            return new GetCardWithHistoryResultDto(status: true, data: [
                'card' => $card,
                'history' => $historyDto->data,
            ]);
        } catch (ModelNotFoundException) {
            return new GetCardWithHistoryResultDto(
                status: false,
                error: new ErrorDto('Member or card not found', 404)
            );
        } catch (Throwable $exception) {
            return new GetCardWithHistoryResultDto(
                status: false,
                error: new ErrorDto($exception->getMessage(), $exception->getCode() ?: 400)
            );
        }
    }
}
