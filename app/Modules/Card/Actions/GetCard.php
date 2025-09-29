<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\GetCardDto;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;

final class GetCard extends Action
{
    public function __invoke(GetCardDto $dto): Card
    {
        if ($dto->user && ! CardAccessControl::canUserViewCard($dto->user, $dto->cardId)) {
            throw new AuthorizationException(__('card::errors.card_access_denied'), 403);
        }

        $memberId = $dto->memberId ?: $dto->user->group_member_id;
        $member = GroupMember::findOrFail($memberId);

        $userIsNotPrimaryMember = ! $member->group->groupMembers()->primary()->where('id', $dto->user->group_member_id)->exists();

        if ($dto->memberId && $userIsNotPrimaryMember) {
            throw new AuthorizationException('Access denied to member card', 403);
        }

        return Card::where('id', $dto->cardId)
            ->with('statusHistory')
            ->where('group_member_id', $memberId)
            ->firstOrFail();
    }
}
