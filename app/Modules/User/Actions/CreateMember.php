<?php

namespace App\Modules\User\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\User\Dto\Actions\CreateMemberDto;
use App\Modules\User\Dto\ActionsResults\CreateMemberResultDto;
use App\Modules\User\Models\GroupMember;
use Throwable;

final class CreateMember extends Action
{
    public function __invoke(CreateMemberDto $dto): CreateMemberResultDto
    {
        try {
            $member = GroupMember::create([
                'group_id' => $dto->groupId,
                'user_id' => $dto->userId,
                'member_type' => $dto->memberType,
                'name' => $dto->name,
                'is_active' => true,
            ]);

            return new CreateMemberResultDto(
                status: true,
                data: $member
            );
        } catch (Throwable $throwable) {
            return new CreateMemberResultDto(
                status: false,
                error: new ErrorDto(
                    error: $throwable->getMessage(),
                    code: $throwable->getCode()
                )
            );
        }
    }
}
