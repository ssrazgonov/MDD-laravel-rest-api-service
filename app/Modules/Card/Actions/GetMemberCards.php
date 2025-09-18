<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Dto\Actions\GetMemberCardsDto;
use App\Modules\Card\Dto\ActionsResults\GetMemberCardsResultDto;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\GroupMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

final class GetMemberCards extends Action
{
    public function __invoke(GetMemberCardsDto $dto): GetMemberCardsResultDto
    {
        try {
            $member = GroupMember::findOrFail($dto->memberId);

            if ($dto->user && ! $member->canUserAccessCards($dto->user)) {
                throw new AuthorizationException('Access denied to member cards', 403);
            }

            $query = Card::forMember($dto->memberId);

            if ($dto->status) {
                $query->statusFilter($dto->status);
            }

            $cards = $query->get();

            return new GetMemberCardsResultDto(status: true, data: $cards);
        } catch (ModelNotFoundException) {
            return new GetMemberCardsResultDto(
                status: false,
                error: new ErrorDto('Member not found', 404)
            );
        } catch (Throwable $exception) {
            return new GetMemberCardsResultDto(
                status: false,
                error: new ErrorDto($exception->getMessage(), $exception->getCode() ?: 400)
            );
        }
    }
}
