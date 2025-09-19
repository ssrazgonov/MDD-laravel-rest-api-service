<?php

namespace Tests\Helpers;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Models\Group;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\GroupMemberIdentity;
use App\Modules\User\Models\User;

trait TestHelper
{
    public function createParentAndChildrens(): array
    {
        $group = Group::factory()->family()->create();
        $childrens = GroupMember::factory(2)->dependent()->for($group)->has(GroupMemberIdentity::factory()->secondary()->snils(), 'identities')->create();
        $parent = GroupMember::factory()->primary()->for($group)->create();
        $parentIdentity = GroupMemberIdentity::factory()->for($parent)->create();
        $user = User::factory()->for($parent)->create();

        return [
            'group' => $group,
            'parentUser' => $user,
            'parentMember' => $parent,
            'parentIdentity' => $parentIdentity,
            'childrens' => $childrens,
        ];
    }

    public function createCardForGroupMember(GroupMember $groupMember, CardAccessLevel $accessLevel, bool $status = true): Card
    {
        $cardAccessControll = $accessLevel == CardAccessLevel::VIEW ?
            CardAccessControl::factory()->view()->for($groupMember) :
            CardAccessControl::factory()->for($groupMember)->manage();

        $card = Card::factory()->for($groupMember)->has($cardAccessControll)->create([
            'status' => $status ? 'active' : 'inactive',
        ]);

        return $card;
    }
}
