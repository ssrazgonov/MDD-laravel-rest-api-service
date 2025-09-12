<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Base\ErrorDto;
use App\Modules\Card\Actions\CardAccess\ProvideCardAccess;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CardAccess\ProvideCardAccessDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Dto\ActionsResults\CreateCardResultDto;
use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\User;
use App\Modules\User\Services\KeycloakMemberService;
use DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateCard extends Action
{
    public function __construct(
        private readonly ProvideCardAccess $provideCardAccess,
        private readonly ActivateCard $activateCard,
        public readonly CreateCardStatusHistory $createCardStatusHistory,
        private readonly KeycloakMemberService $memberService
    ) {
    }

    public function __invoke(CreateCardDto $createCardActionDto): CreateCardResultDto
    {
        DB::beginTransaction();

        try {
            /**
             * @var User $user
             */
            $user = Auth::user();

            if (! $user) {
                throw new AuthenticationException();
            }

            // Определяем memberId
            $memberId = $createCardActionDto->memberId;
            if (! $memberId) {
                // Используем primary group member пользователя
                $primaryGroupMember = $user->primaryGroupMember();
                if (! $primaryGroupMember) {
                    // Если нет primary group member, создаем индивидуальную группу
                    $this->memberService->processIndividualJwt($user);
                    $primaryGroupMember = $user->primaryGroupMember();
                    if (! $primaryGroupMember) {
                        throw new DomainException('Unable to create primary group member', 500);
                    }
                }

                $memberId = $primaryGroupMember->id;
            }

            $hashedPan = hash('sha256', $createCardActionDto->pan);

            if (Card::existsForMemberWithHash($memberId, $hashedPan)) {
                throw new DomainException(__('card::errors.already_exists'), 400);
            }

            $card = Card::create([
                'member_id' => $memberId,
                'hash' => $hashedPan,
                'last_digits' => substr($createCardActionDto->pan, -4),
                'status' => CardStatus::INACTIVE,
                'comment' => $createCardActionDto->comment ?? null,
            ]);

            if ($card->shouldAutoActivate()) {
                $this->handleResult(($this->activateCard)(new ActivateCardDto(
                    id: $card->id,
                    comment: __('card::messages.created_with_activate')
                )));
            } else {
                $this->handleResult(($this->createCardStatusHistory)(
                    new CreateCardStatusHistoryDto(card: $card, comment: __('card::messages.created'))
                ));
            }

            // Grant access to the user who owns the member
            $member = $card->member;
            if ($member) {
                $this->handleResult(
                    ($this->provideCardAccess)(new ProvideCardAccessDto(
                        userId: $member->user_id,
                        cardId: $card->id,
                        level: CardAccessLevel::MANAGE
                    ))
                );
            }

            $card->refresh();

            DB::commit();

            return new CreateCardResultDto(status: true, data: $card);
        } catch (Throwable $throwable) {
            DB::rollBack();

            return new CreateCardResultDto(
                status: false,
                error: new ErrorDto(error: $throwable->getMessage(), code: $throwable->getCode())
            );
        }
    }
}
