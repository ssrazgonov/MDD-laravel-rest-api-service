<?php

namespace App\Modules\Card\Actions;

use App\Base\Action;
use App\Modules\Card\Actions\CardAccess\ProvideCardAccess;
use App\Modules\Card\Actions\StatusHistory\CreateCardStatusHistory;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CardAccess\ProvideCardAccessDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\StatusHistory\CreateCardStatusHistoryDto;
use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Enums\GroupMemberType;
use App\Modules\User\Models\User;
use App\Modules\User\Services\KeycloakMemberService;
use DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateCard extends Action
{
    public function __construct(
        private readonly ProvideCardAccess $provideCardAccess,
        private readonly ActivateCard $activateCard,
        public readonly CreateCardStatusHistory $createCardStatusHistory,
        private readonly KeycloakMemberService $memberService
    ) {
    }

    public function __invoke(CreateCardDto $createCardActionDto): Card
    {
        return DB::transaction(function () use ($createCardActionDto) {
            /**
             * @var User $user
             */
            $user = Auth::user();

            if (! $user) {
                throw new AuthenticationException();
            }

            $memberId = $createCardActionDto->memberId ?? $user->group_member_id;

            $hashedPan = hash('sha256', $createCardActionDto->pan);

            if (Card::existsForMemberWithHash($memberId, $hashedPan)) {
                throw new DomainException(__('card::errors.already_exists'), 400);
            }

            $card = Card::create([
                'group_member_id' => $memberId,
                'hash' => $hashedPan,
                'last_digits' => substr($createCardActionDto->pan, -4),
                'status' => CardStatus::INACTIVE,
                'comment' => $createCardActionDto->comment,
            ]);

            $accessLevel = $card->groupMember->type === GroupMemberType::DEPENDENT
                ? CardAccessLevel::VIEW
                : CardAccessLevel::MANAGE;

            ($this->provideCardAccess)(new ProvideCardAccessDto(
                groupMemberId: $card->group_member_id,
                cardId: $card->id,
                level: $accessLevel
            ));

            if ($card->groupMember->type === GroupMemberType::DEPENDENT) {
                $primaryMembers = $card->groupMember->group->groupMembers()->primary()->active()->get();
                foreach ($primaryMembers as $primaryMember) {
                    $this->handleResult(
                        ($this->provideCardAccess)(new ProvideCardAccessDto(
                            groupMemberId: $primaryMember->id,
                            cardId: $card->id,
                            level: CardAccessLevel::MANAGE
                        ))
                    );
                }
            }

            if ($card->shouldAutoActivate()) {
                ($this->activateCard)(new ActivateCardDto(
                    id: $card->id,
                    comment: __('card::messages.created_with_activate'),
                    user: $user
                ));
            } else {
                ($this->createCardStatusHistory)(new CreateCardStatusHistoryDto(card: $card, comment: __('card::messages.created'), changedBy: $user->id));
            }

            $card->refresh();

            return $card;
        });
    }
}
