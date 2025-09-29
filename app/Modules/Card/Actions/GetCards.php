<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Dto\Actions\GetCardsDto;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class GetCards extends Action
{
    public function __invoke(GetCardsDto $dto): Collection
    {
        $memberId = $dto->memberId ?: $dto->user->group_member_id;
        $member = GroupMember::findOrFail($memberId);

        $userIsNotPrimaryMember = ! $member->group->groupMembers()->primary()->where('id', $dto->user->group_member_id)->exists();

        if ($dto->memberId && $userIsNotPrimaryMember) {
            throw new AuthorizationException('Access denied to member card', 403);
        }

        return $member->cards()->with('statusHistory')->when($dto->status, fn ($query) => $query->statusFilter($dto->status))->get();
    }
}
