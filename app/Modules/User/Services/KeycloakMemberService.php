<?php

namespace App\Modules\User\Services;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Models\Group;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\MemberIdentity;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class KeycloakMemberService
{
    // Обработка родителя с детьми (из residents realm)
    public function processParentJwt(User $user, array $jwtChildren, string $userName): void
    {
        DB::transaction(function () use ($user, $jwtChildren, $userName) {
            // Если пользователь имеет individual группу, обновляем её на family
            $existingIndividualGroup = Group::whereHas('groupMembers', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('member_type', 'primary');
            })->where('group_type', 'individual')->first();

            if ($existingIndividualGroup && $jwtChildren !== []) {
                $existingIndividualGroup->update(['group_type' => 'family']);
                $group = $existingIndividualGroup;
            } else {
                // Ищем существующую группу по детям
                $group = $this->findOrCreateGroup($jwtChildren);

                // Если группа была individual, но теперь есть дети - меняем на family
                if ($group->group_type === 'individual' && $jwtChildren !== []) {
                    $group->update(['group_type' => 'family']);
                }
            }

            // Проверяем, есть ли пользователь уже в группе
            $existingGroupMember = GroupMember::where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $existingGroupMember) {
                // Добавляем пользователя в группу как primary member
                $primaryGroupMember = GroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'member_type' => 'primary',
                    'name' => $userName,
                    'is_active' => true,
                ]);

                // Добавляем Keycloak identity
                $primaryGroupMember->addIdentity('keycloak', $user->id, true);
            }

            // Синхронизируем детей
            $this->syncChildren($group, $user, $jwtChildren);
        });
    }

    // Обработка индивидуального пользователя (взрослый или ребенок)
    public function processIndividualJwt(User $user, string $userName): void
    {
        DB::transaction(function () use ($user, $userName) {
            // Ищем существующего group member по keycloak identity
            $existingGroupMember = $this->getGroupMemberByIdentity('keycloak', $user->id);

            if ($existingGroupMember instanceof GroupMember) {
                if ($existingGroupMember->member_type === 'dependent') {
                    // Это ребенок из kids realm, который теперь имеет аккаунт в residents
                    $this->upgradeChildToPrimary($existingGroupMember, $user, $userName);
                }

                // Если уже primary - ничего не делаем
            } else {
                // Это новый взрослый пользователь без детей
                $this->createIndividualGroup($user, $userName);
            }
        });
    }

    private function upgradeChildToPrimary(GroupMember $existingGroupMember, User $user, string $userName): void
    {
        // Создаем новый primary member для ребенка в той же группе
        $childPrimaryMember = GroupMember::create([
            'group_id' => $existingGroupMember->group_id,
            'user_id' => $user->id,
            'member_type' => 'primary',
            'name' => $userName,
            'is_active' => true,
        ]);

        // Добавляем identities к новому member
        $childPrimaryMember->addIdentity('keycloak', $user->id, true);

        // Копируем все identities от dependent member
        foreach ($existingGroupMember->identities as $identity) {
            $childPrimaryMember->addIdentity($identity->identity_type, $identity->identity_value, false);
        }

        // Переносим карты
        $existingGroupMember->cards()->update(['member_id' => $childPrimaryMember->id]);

        // Деактивируем старый dependent member
        $existingGroupMember->update(['is_active' => false]);
    }

    private function createIndividualGroup(User $user, string $userName): void
    {
        // Проверяем, есть ли уже у пользователя группа
        $existingGroupMember = GroupMember::where('user_id', $user->id)
            ->where('member_type', 'primary')
            ->first();

        if ($existingGroupMember) {
            // Пользователь уже имеет группу - обновляем тип если нужно
            $group = $existingGroupMember->group;

            // Если группа была family, но теперь пользователь без детей - оставляем family
            // (чтобы сохранить историю и возможность добавить детей позже)
            return;
        }

        // Создаем персональную группу для пользователя
        $group = Group::create([
            'name' => 'Персональная группа',
            'group_type' => 'individual',
            'is_active' => true,
        ]);

        // Создаем primary member
        $primaryGroupMember = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'member_type' => 'primary',
            'name' => $userName,
            'is_active' => true,
        ]);

        // Добавляем Keycloak identity если её нет у пользователя
        $existingKeycloak = MemberIdentity::where('identity_type', 'keycloak')
            ->where('identity_value', $user->id)
            ->first();

        if (! $existingKeycloak) {
            $primaryGroupMember->addIdentity('keycloak', $user->id, true);
        }
    }

    private function findOrCreateGroup(array $jwtChildren): Group
    {
        // Ищем существующую группу по external_id детей
        foreach ($jwtChildren as $child) {
            if (isset($child['external_id'])) {
                $existingIdentity = MemberIdentity::where('identity_type', 'snils')
                    ->where('identity_value', $child['external_id'])
                    ->first();

                if ($existingIdentity && $existingIdentity->groupMember) {
                    return $existingIdentity->groupMember->group;
                }
            }
        }

        // Если группа не найдена, создаем новую
        return Group::create([
            'name' => 'Семья',
            'group_type' => 'family',
            'is_active' => true,
        ]);
    }

    private function syncChildren(Group $group, User $user, array $jwtChildren): void
    {
        foreach ($jwtChildren as $child) {
            if (! isset($child['external_id'])) {
                continue;
            }

            // Ищем существующего ребенка
            $existingIdentity = MemberIdentity::where('identity_type', 'snils')
                ->where('identity_value', $child['external_id'])
                ->first();

            if ($existingIdentity && $existingIdentity->groupMember) {
                // Ребенок уже есть в группе - обновляем данные
                $existingIdentity->groupMember->update([
                    'name' => $child['name'] ?? $existingIdentity->groupMember->name,
                ]);

                // Предоставляем доступ этому родителю к картам ребенка
                $this->ensureFamilyAccess($user, $existingIdentity->groupMember);
            } else {
                // Создаем нового ребенка в группе
                $childGroupMember = GroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => null, // Ребенок еще не имеет аккаунта
                    'member_type' => 'dependent',
                    'name' => $child['name'] ?? 'Участник',
                    'is_active' => true,
                ]);

                $childGroupMember->addIdentity('snils', $child['external_id'], true);
            }
        }
    }

    private function ensureFamilyAccess(User $parent, GroupMember $childGroupMember): void
    {
        // Предоставляем доступ ко всем картам ребенка
        foreach ($childGroupMember->cards as $card) {
            CardAccessControl::firstOrCreate([
                'card_id' => $card->id,
                'user_id' => $parent->id,
            ], [
                'access_level' => CardAccessLevel::MANAGE,
            ]);
        }
    }

    public function linkChildUser(string $identityType, string $identityValue, User $childUser): ?GroupMember
    {
        $existingIdentity = MemberIdentity::where('identity_type', $identityType)
            ->where('identity_value', $identityValue)
            ->first();

        if ($existingIdentity && $existingIdentity->groupMember) {
            $existingGroupMember = $existingIdentity->groupMember;

            DB::transaction(function () use ($existingGroupMember, $childUser, $identityType, $identityValue) {
                // Обновляем существующего dependent member
                $existingGroupMember->update([
                    'user_id' => $childUser->id,
                    'member_type' => 'primary',
                    'is_active' => true,
                ]);

                // Переносим все identities к новому member
                $existingGroupMember->identities()->update(['group_member_id' => $existingGroupMember->id]);

                // Добавляем keycloak identity
                $existingGroupMember->addIdentity('keycloak', $childUser->id, true);
            });

            return $existingGroupMember->fresh();
        }

        return null;
    }

    public function getGroupMemberByIdentity(string $type, string $value): ?GroupMember
    {
        $identity = MemberIdentity::where('identity_type', $type)
            ->where('identity_value', $value)
            ->first();

        return $identity?->groupMember;
    }

    public function createGroupMemberWithIdentity(
        int $groupId,
        string $userId,
        string $memberType,
        string $name,
        string $identityType,
        string $identityValue,
        bool $isPrimary = true
    ): GroupMember {
        $groupMember = GroupMember::create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'member_type' => $memberType,
            'name' => $name,
            'is_active' => true,
        ]);

        $groupMember->addIdentity($identityType, $identityValue, $isPrimary);

        return $groupMember;
    }
}
