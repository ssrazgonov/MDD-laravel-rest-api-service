<?php

namespace App\Modules\User\Services;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Enums\GroupMemberType;
use App\Modules\User\Models\Group;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\GroupMemberIdentity;
use App\Modules\User\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class KeycloakMemberService
{
    private function shouldUpdateName(string $newName): bool
    {
        return ! empty($newName) && $newName !== 'Unknown' && $newName !== 'null';
    }

    public function processParentJwt(User $user, array $jwtChildren, string $userName): void
    {
        DB::transaction(function () use ($user, $jwtChildren, $userName) {
            $existingIndividualGroup = $user->groupMember?->group;

            if ($existingIndividualGroup && $jwtChildren !== []) {
                $existingIndividualGroup->update(['group_type' => 'family']);
                $group = $existingIndividualGroup;
            } else {
                $group = $this->findOrCreateGroup($jwtChildren);

                if ($group->group_type === 'individual' && $jwtChildren !== []) {
                    $group->update(['group_type' => 'family']);
                }
            }

            $existingGroupMember = $user->groupMember;

            if (! $existingGroupMember) {
                $groupMemberData = [
                    'group_id' => $group->id,
                    'type' => 'primary',
                    'is_active' => true,
                ];

                if ($this->shouldUpdateName($userName)) {
                    $groupMemberData['name'] = $userName;
                }

                $primaryGroupMember = GroupMember::create($groupMemberData);

                $primaryGroupMember->addIdentity('keycloak', $user->id, true);

                $primaryGroupMember->attachUser($user);
            }

            $this->syncChildren($group, $user, $jwtChildren);
        });
    }

    public function processIndividualJwt(User $user, string $userName, ?string $userSnils = null): void
    {
        DB::transaction(function () use ($user, $userName, $userSnils) {
            $existingGroupMember = $this->getGroupMemberByIdentity('keycloak', $user->id);

            if ($existingGroupMember instanceof GroupMember) {
                if ($existingGroupMember->type === GroupMemberType::DEPENDENT) {
                    $this->activateChildAccount($existingGroupMember, $user, $userName);
                }
            } else {
                if ($userSnils) {
                    $existingGroupMember = $this->getGroupMemberByIdentity('snils', $userSnils);

                    if ($existingGroupMember instanceof GroupMember && $existingGroupMember->type === GroupMemberType::DEPENDENT) {
                        $this->activateChildAccount($existingGroupMember, $user, $userName);

                        return;
                    }
                }

                $this->createIndividualGroup($user, $userName);
            }
        });
    }

    private function activateChildAccount(GroupMember $existingGroupMember, User $user, string $userName): void
    {
        $updateData = [
            'user_id' => $user->id,
            'is_active' => true,
        ];

        if ($this->shouldUpdateName($userName)) {
            $updateData['name'] = $userName;
        }

        $existingGroupMember->update($updateData);

        $existingGroupMember->addIdentity('keycloak', $user->id, false);
    }

    private function createIndividualGroup(User $user, string $userName): void
    {
        if ($user->groupMember()->exists()) {
            return;
        }

        $group = Group::create([
            'name' => 'Персональная группа',
            'group_type' => 'individual',
            'is_active' => true,
        ]);

        $groupMemberData = [
            'group_id' => $group->id,
            'type' => GroupMemberType::PRIMARY,
            'is_active' => true,
        ];

        if ($this->shouldUpdateName($userName)) {
            $groupMemberData['name'] = $userName;
        }

        $primaryGroupMember = GroupMember::create($groupMemberData);

        $existingKeycloak = GroupMemberIdentity::where('type', 'keycloak')
            ->where('value', $user->id)
            ->first();

        if (! $existingKeycloak) {
            $identity = $primaryGroupMember->addIdentity('keycloak', $user->id, true);
        }
    }

    private function findOrCreateGroup(array $jwtChildren): Group
    {
        foreach ($jwtChildren as $child) {
            if (isset($child['snils']) && ! empty($child['snils'])) {
                $hashedSnils = hash('sha256', $child['snils']);
                $existingIdentity = GroupMemberIdentity::where('type', 'snils')
                    ->where('value', $hashedSnils)
                    ->first();

                if ($existingIdentity && $existingIdentity->groupMember) {
                    return $existingIdentity->groupMember->group;
                }
            }
        }

        return Group::create([
            'name' => 'Семья',
            'type' => 'family',
            'is_active' => true,
        ]);
    }

    private function syncChildren(Group $group, User $user, array $jwtChildren): void
    {
        foreach ($jwtChildren as $child) {
            if (! isset($child['snils']) || empty($child['snils'])) {
                continue;
            }

            $hashedSnils = hash('sha256', $child['snils']);

            $existingIdentity = GroupMemberIdentity::where('type', 'snils')
                ->where('value', $hashedSnils)
                ->first();

            if ($existingIdentity && $existingIdentity->groupMember) {
                $childName = trim(($child['lastName'] ?? '') . ' ' . ($child['firstName'] ?? '') . ' ' . ($child['middleName'] ?? ''));
                if (! empty($childName)) {
                    $existingIdentity->groupMember->update([
                        'name' => $childName,
                    ]);
                }

                $this->ensureFamilyAccess($user, $existingIdentity->groupMember);
            } else {
                $childName = trim(($child['lastName'] ?? '') . ' ' . ($child['firstName'] ?? '') . ' ' . ($child['middleName'] ?? ''));
                $groupMemberData = [
                    'group_id' => $group->id,
                    'type' => 'dependent',
                    'is_active' => true,
                ];

                if (! empty($childName)) {
                    $groupMemberData['name'] = $childName;
                }

                $childGroupMember = GroupMember::create($groupMemberData);

                $childGroupMember->addIdentity('snils', $hashedSnils, true);
            }
        }
    }

    private function ensureFamilyAccess(User $parent, GroupMember $childGroupMember): void
    {
        foreach ($childGroupMember->cards as $card) {
            CardAccessControl::firstOrCreate([
                'card_id' => $card->id,
                'group_member_id' => $parent->groupMember->id,
            ], [
                'access_level' => CardAccessLevel::MANAGE,
            ]);
        }
    }

    public function linkChildUser(string $identityType, string $identityValue, User $childUser): ?GroupMember
    {
        $searchValue = $identityType === 'snils' ? hash('sha256', $identityValue) : $identityValue;
        $existingIdentity = GroupMemberIdentity::where('type', $identityType)
            ->where('value', $searchValue)
            ->first();

        if ($existingIdentity && $existingIdentity->groupMember) {
            $existingGroupMember = $existingIdentity->groupMember;

            DB::transaction(function () use ($existingGroupMember, $childUser) {
                $updateData = [
                    'type' => 'dependent',
                    'is_active' => true,
                ];

                $existingGroupMember->update($updateData);

                $existingGroupMember->addIdentity('keycloak', $childUser->id, false);

                $existingGroupMember->attachUser($childUser);
            });

            return $existingGroupMember->fresh();
        }

        return null;
    }

    public function getGroupMemberByIdentity(string $type, string $value): ?GroupMember
    {
        $searchValue = $type === 'snils' ? hash('sha256', $value) : $value;
        $identity = GroupMemberIdentity::where('type', $type)
            ->where('value', $searchValue)
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
        $groupMemberData = [
            'group_id' => $groupId,
            'type' => $memberType,
            'is_active' => true,
        ];

        if ($this->shouldUpdateName($name)) {
            $groupMemberData['name'] = $name;
        }

        $groupMember = GroupMember::create($groupMemberData);

        $storedValue = $identityType === 'snils' ? hash('sha256', $identityValue) : $identityValue;
        $groupMember->addIdentity($identityType, $storedValue, $isPrimary);
        $groupMember->attachUser($userId);

        return $groupMember;
    }

    public function ensureUserHasPrimaryGroupMember(User $user, string $userName): GroupMember
    {
        $primaryGroupMember = $user->primaryGroupMember()->first();

        if (! $primaryGroupMember) {
            $this->processIndividualJwt($user, $userName);
            $primaryGroupMember = $user->primaryGroupMember();

            if (! $primaryGroupMember) {
                throw new DomainException('Unable to create primary group member', 500);
            }
        }

        return $primaryGroupMember;
    }
}
