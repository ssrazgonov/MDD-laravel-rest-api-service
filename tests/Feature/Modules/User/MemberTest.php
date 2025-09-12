<?php

namespace Tests\Feature\Modules\User;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Models\Group;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\MemberIdentity;
use App\Modules\User\Models\User;
use App\Modules\User\Services\KeycloakMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_group_members()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        GroupMember::factory()->primary()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        GroupMember::factory()->dependent()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->fresh()->groupMembers);
        $this->assertEquals('primary', $user->primaryGroupMember()->member_type);
        $this->assertCount(1, $user->groupMembers()->dependent()->get());
    }

    public function test_group_member_belongs_to_user()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $member->user->id);
    }

    public function test_group_member_can_have_identities()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $identity = $member->addIdentity('snils', '1234567890123456', true);

        $this->assertCount(1, $member->fresh()->identities);
        $this->assertEquals('snils', $identity->identity_type);
        $this->assertEquals('1234567890123456', $identity->identity_value);
        $this->assertTrue($identity->is_primary);
    }

    public function test_find_group_member_by_identity()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        $member->addIdentity('snils', '1234567890123456', true);

        $foundMember = MemberIdentity::findByIdentity('snils', '1234567890123456');

        $this->assertEquals($member->id, $foundMember->id);
    }

    public function test_two_realm_architecture()
    {
        // Создаем группу семьи
        $familyGroup = Group::create([
            'name' => 'Семья',
            'group_type' => 'family',
            'is_active' => true,
        ]);

        // Первый родитель входит (из residents realm с детьми)
        $parent1 = User::factory()->create();
        $parent1Member = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => $parent1->id,
            'member_type' => 'primary',
            'name' => 'Родитель 1',
            'is_active' => true,
        ]);
        $parent1Member->addIdentity('keycloak', $parent1->id, true);

        // Создаем ребенка (из kids realm)
        $childMember = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => null, // Пока ребенок не имеет своего аккаунта
            'member_type' => 'dependent',
            'name' => 'Ребенок',
            'is_active' => true,
        ]);
        $childMember->addIdentity('snils', '1234567890123456', true);

        // Создаем карту для ребенка
        $card = Card::factory()->create([
            'member_id' => $childMember->id,
        ]);

        // Второй родитель входит (из residents realm)
        $parent2 = User::factory()->create();
        $parent2Member = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => $parent2->id,
            'member_type' => 'primary',
            'name' => 'Родитель 2',
            'is_active' => true,
        ]);
        $parent2Member->addIdentity('keycloak', $parent2->id, true);

        // Предоставляем доступ второму родителю к карте ребенка
        CardAccessControl::create([
            'card_id' => $card->id,
            'user_id' => $parent2->id,
            'access_level' => CardAccessLevel::MANAGE,
        ]);

        // Проверяем, что оба родителя имеют доступ к карте
        $parent1Cards = Card::forMember($parent1Member->id)->get();
        $parent2AccessibleCards = CardAccessControl::where('user_id', $parent2->id)
            ->with('card')
            ->get()
            ->pluck('card');

        $this->assertCount(0, $parent1Cards); // У родителя 1 нет своих карт
        $this->assertCount(1, $parent2AccessibleCards); // Родитель 2 имеет доступ к 1 карте ребенка
        $this->assertEquals($card->id, $parent2AccessibleCards->first()->id);
    }

    public function test_child_upgrade_to_primary()
    {
        // Создаем группу семьи
        $familyGroup = Group::create([
            'name' => 'Семья',
            'group_type' => 'family',
            'is_active' => true,
        ]);

        // Создаем ребенка как dependent
        $childMember = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => null, // Ребенок не имеет аккаунта
            'member_type' => 'dependent',
            'name' => 'Ребенок',
            'is_active' => true,
        ]);
        $childMember->addIdentity('snils', '1234567890123456', true);

        // Создаем карту для ребенка
        $card = Card::factory()->create([
            'member_id' => $childMember->id,
        ]);

        // Ребенок получает свой аккаунт в residents realm
        $childUser = User::factory()->create();

        // Имитируем upgrade ребенка
        $newChildMember = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => $childUser->id,
            'member_type' => 'primary',
            'name' => 'Ребенок',
            'is_active' => true,
        ]);

        // Переносим identities от старого member
        $childMember->identities()->update(['group_member_id' => $newChildMember->id]);

        $newChildMember->addIdentity('keycloak', $childUser->id, true);

        // Переносим карту
        $card->update(['member_id' => $newChildMember->id]);

        // Деактивируем старый member
        $childMember->update(['is_active' => false]);

        // Проверяем, что карта теперь у нового member
        $this->assertEquals($newChildMember->id, $card->fresh()->member_id);
        $this->assertFalse($childMember->fresh()->is_active);
    }

    public function test_group_type_upgrade_from_individual_to_family()
    {
        // Создаем пользователя с персональной группой
        $user = User::factory()->create();
        $individualGroup = Group::create([
            'name' => 'Персональная группа',
            'group_type' => 'individual',
            'is_active' => true,
        ]);

        $userMember = GroupMember::create([
            'group_id' => $individualGroup->id,
            'user_id' => $user->id,
            'member_type' => 'primary',
            'name' => 'Пользователь',
            'is_active' => true,
        ]);
        $userMember->addIdentity('keycloak', $user->id, true);

        // Проверяем начальный тип группы
        $this->assertEquals('individual', $individualGroup->fresh()->group_type);

        // Имитируем вход пользователя с детьми (теперь он родитель)
        $service = app(KeycloakMemberService::class);
        $jwtChildren = [
            ['external_id' => '1234567890123456', 'name' => 'Ребенок'],
        ];

        $service->processParentJwt($user, $jwtChildren, 'Пользователь');

        // Проверяем, что тип группы изменился на family
        $this->assertEquals('family', $individualGroup->fresh()->group_type);

        // Проверяем, что ребенок был добавлен
        $childMembers = GroupMember::where('group_id', $individualGroup->id)
            ->where('member_type', 'dependent')
            ->get();
        $this->assertCount(1, $childMembers);
        $this->assertEquals('Ребенок', $childMembers->first()->name);
    }

    public function test_group_preservation_for_future_children()
    {
        // Создаем пользователя с family группой
        $user = User::factory()->create();
        $familyGroup = Group::create([
            'name' => 'Семья',
            'group_type' => 'family',
            'is_active' => true,
        ]);

        $userMember = GroupMember::create([
            'group_id' => $familyGroup->id,
            'user_id' => $user->id,
            'member_type' => 'primary',
            'name' => 'Родитель',
            'is_active' => true,
        ]);
        $userMember->addIdentity('keycloak', $user->id, true);

        // Имитируем вход без детей (например, дети временно не в JWT)
        $service = app(KeycloakMemberService::class);
        $service->processIndividualJwt($user, 'Родитель');

        // Проверяем, что группа осталась family (для будущего добавления детей)
        $this->assertEquals('family', $familyGroup->fresh()->group_type);

        // Проверяем, что не создалась новая группа
        $userGroups = $user->groups;
        $this->assertCount(1, $userGroups);
        $this->assertEquals($familyGroup->id, $userGroups->first()->id);
    }
}
