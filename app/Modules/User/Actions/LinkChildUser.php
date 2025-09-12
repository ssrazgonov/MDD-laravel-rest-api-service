<?php

namespace App\Modules\User\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\User\Dto\Actions\LinkChildUserDto;
use App\Modules\User\Dto\ActionsResults\LinkChildUserResultDto;
use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\User;
use App\Modules\User\Services\KeycloakMemberService;
use DomainException;
use Throwable;

final class LinkChildUser extends Action
{
    public function __construct(
        private readonly KeycloakMemberService $keycloakMemberService
    ) {
    }

    public function __invoke(LinkChildUserDto $dto): LinkChildUserResultDto
    {
        try {
            $childUser = User::find($dto->childUserId);

            if (! $childUser) {
                throw new DomainException('Child user not found', 404);
            }

            $linkedMember = $this->keycloakMemberService->linkChildUser(
                $dto->identityType,
                $dto->identityValue,
                $childUser
            );

            if (! $linkedMember instanceof GroupMember) {
                throw new DomainException('Member not found or already linked', 404);
            }

            return new LinkChildUserResultDto(
                status: true,
                data: $linkedMember
            );
        } catch (Throwable $throwable) {
            return new LinkChildUserResultDto(
                status: false,
                error: new ErrorDto(
                    error: $throwable->getMessage(),
                    code: $throwable->getCode()
                )
            );
        }
    }
}
