<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetMemberCardDto;
use App\Modules\Card\Dto\ActionsResults\GetMemberCardResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

final class GetMemberCard extends Action
{
    public function __invoke(GetMemberCardDto $dto): GetMemberCardResultDto
    {
        try {
            $member = GroupMember::findOrFail($dto->memberId);

            $card = Card::where('id', $dto->cardId)
                ->where('member_id', $dto->memberId)
                ->firstOrFail();

            if ($dto->user && ! $member->canUserAccessCards($dto->user)) {
                throw new AuthorizationException('Access denied to member card', 403);
            }

            return new GetMemberCardResultDto(status: true, data: $card);
        } catch (ModelNotFoundException) {
            return new GetMemberCardResultDto(
                status: false,
                error: new ErrorDto('Member or card not found', 404)
            );
        } catch (Throwable $exception) {
            return new GetMemberCardResultDto(
                status: false,
                error: new ErrorDto($exception->getMessage(), $exception->getCode() ?: 400)
            );
        }
    }
}
