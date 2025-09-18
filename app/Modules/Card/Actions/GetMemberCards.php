<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetMemberCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetMemberCardsResultDto;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

final class GetMemberCards extends Action
{
    public function __invoke(GetMemberCardsDto $dto): GetMemberCardsResultDto
    {
        try {
            $member = GroupMember::findOrFail($dto->memberId);

            $userIsPrimaryMember = $member->group->groupMembers()->primary()->where('id', $dto->user->group_member_id)->exists();

            if ($dto->user && ! $userIsPrimaryMember) {
                throw new AuthorizationException('Access denied to member cards', 403);
            }

            $query = $member->cards();

            if ($dto->status) {
                $query->statusFilter($dto->status);
            }

            $cards = $query->get();

            return new GetMemberCardsResultDto(status: true, data: $cards);
        } catch (ModelNotFoundException) {
            return new GetMemberCardsResultDto(
                status: false,
                error: new ErrorDto('Member not found', 404)
            );
        } catch (Throwable $exception) {
            return new GetMemberCardsResultDto(
                status: false,
                error: new ErrorDto($exception->getMessage(), $exception->getCode() ?: 400)
            );
        }
    }
}
