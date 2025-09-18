<?php

namespace App\Modules\User\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\User\Dto\Actions\CreateMemberDto;
use App\Modules\User\Dto\ActionsResults\CreateMemberResultDto;
use App\Modules\User\Models\GroupMember;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateMember extends Action
{
    public function __invoke(CreateMemberDto $dto): CreateMemberResultDto
    {
        DB::beginTransaction();

        try {
            $member = GroupMember::create([
                'group_id' => $dto->groupId,
                'type' => $dto->type,
                'name' => $dto->name,
                'is_active' => true,
            ]);

            $member->attachUser($dto->userId);

            DB::commit();

            return new CreateMemberResultDto(
                status: true,
                data: $member
            );
        } catch (Throwable $throwable) {
            DB::rollBack();

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
