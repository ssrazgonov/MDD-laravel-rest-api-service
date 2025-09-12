<?php

namespace App\Modules\User\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\User\Dto\Actions\GetUserMembersDto;
use App\Modules\User\Dto\ActionsResults\GetUserMembersResultDto;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\User;
use Throwable;

final class GetUserMembers extends Action
{
    public function __invoke(GetUserMembersDto $dto): GetUserMembersResultDto
    {
        try {
            if ($dto->memberType === 'dependent') {
                // Для детей ищем через группы пользователя
                $user = User::find($dto->userId);
                if (! $user) {
                    return new GetUserMembersResultDto(
                        status: false,
                        error: new ErrorDto('User not found', 404)
                    );
                }

                $primaryMember = $user->primaryGroupMember();
                if (! $primaryMember) {
                    return new GetUserMembersResultDto(status: true, data: collect());
                }

                $query = GroupMember::where('group_id', $primaryMember->group_id)
                    ->where('member_type', 'dependent');
            } else {
                // Для других типов - по user_id
                $query = GroupMember::where('user_id', $dto->userId);

                if ($dto->memberType) {
                    $query->where('member_type', $dto->memberType);
                }
            }

            if ($dto->active !== null) {
                $query->where('is_active', $dto->active);
            }

            $members = $query->get();

            return new GetUserMembersResultDto(
                status: true,
                data: $members
            );
        } catch (Throwable $throwable) {
            return new GetUserMembersResultDto(
                status: false,
                error: new ErrorDto(
                    error: $throwable->getMessage(),
                    code: $throwable->getCode()
                )
            );
        }
    }
}
