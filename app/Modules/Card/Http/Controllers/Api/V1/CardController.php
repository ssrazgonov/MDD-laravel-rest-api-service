<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\ActivateCard;
use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Actions\DeactivateCard;
use App\Modules\Card\Actions\GetCardWithHistory;
use App\Modules\Card\Actions\GetMemberCard;
use App\Modules\Card\Actions\GetMemberCards;
use App\Modules\Card\Actions\GetUserCards;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\GetCardWithHistoryDto;
use App\Modules\Card\Dto\Actions\GetMemberCardDto;
use App\Modules\Card\Dto\Actions\GetMemberCardsDto;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Requests\GetUserCardsRequest;
use App\Modules\Card\Http\Resources\CardResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Get list of user's cards.
     *
     * @response array{
     *   status: bool,
     *   error: array{message: string, code: int},
     *   data: array{
     *     id: int,
     *     group_member_id: int,
     *     hash: string,
     *     last_digits: string,
     *     status: string,
     *     comment: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     access_level: "view"|"manage"
     *   }[]
     * }
     */
    public function index(GetUserCardsRequest $request, GetUserCards $getUserCards)
    {
        $dto = new GetUserCardsDto(userId: Auth::id(), status: CardStatus::tryFrom($request->status));

        return response()->json()->api(
            $getUserCards->handleResult($getUserCards($dto)),
            Response::HTTP_OK
        );
    }

    /**
     * Create a new card.
     *
     * @response array{
     *   status: bool,
     *   error: array{error: string, code: int},
     *   data: array{
     *     id: int,
     *     group_member_id: int,
     *     hash: string,
     *     last_digits: string,
     *     status: string,
     *     comment: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     access_level: "view"|"manage"
     *   }
     * }
     */
    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        $createCardData = CreateCardDto::fromArray([...$request->validated(), ...['user_id' => Auth::id()]]);

        return response()->json()->api(
            $createCard->handleResult($createCard($createCardData)),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function activate(int $id, ActivateCard $activateCard)
    {
        return response()->json()->api(
            $activateCard->handleResult($activateCard(new ActivateCardDto(id: $id, user: Auth::user()))),
            Response::HTTP_OK
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function deactivate(int $id, DeactivateCard $deactivateCard)
    {
        return response()->json()->api(
            $deactivateCard->handleResult($deactivateCard(new DeactivateCardDto(id: $id, user: Auth::user()))),
            Response::HTTP_OK
        );
    }

    /**
     * Get card details with history.
     *
     * @response array{
     *   status: bool,
     *   error: array{error: string, code: int},
     *   data: array{
     *     card: array{
     *       id: int,
     *       group_member_id: int,
     *       hash: string,
     *       last_digits: string,
     *       status: string,
     *       comment: string|null,
     *       created_at: string,
     *       updated_at: string,
     *       access_level: "view"|"manage"
     *     },
     *     history: mixed[]
     *   }
     * }
     */
    public function show(int $id, GetCardWithHistory $getCardWithHistory)
    {
        $result = $getCardWithHistory->handleResult($getCardWithHistory(new GetCardWithHistoryDto(id: $id, user: Auth::user())));

        if ($result->data && isset($result->data['card'])) {
            $user = Auth::user();
            $transformedCard = new CardResource($result->data['card'], $user);
            $transformedData = [
                'card' => $transformedCard,
                'history' => $result->data['history'],
            ];

            return response()->json([
                'status' => $result->status,
                'data' => $transformedData,
                'error' => $result->error,
            ], Response::HTTP_OK);
        }

        return response()->json()->api(
            $result,
            Response::HTTP_OK,
        );
    }

    /**
     * Get cards for a specific group member.
     *
     * @response array{
     *   status: bool,
     *   error: array{error: string, code: int},
     *   data: array{
     *     id: int,
     *     group_member_id: int,
     *     hash: string,
     *     last_digits: string,
     *     status: string,
     *     comment: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     access_level: "view"|"manage"
     *   }[]
     * }
     */
    public function getMemberCards(int $memberId, GetMemberCards $getMemberCards)
    {
        $dto = new GetMemberCardsDto(
            memberId: $memberId,
            user: Auth::user()
        );

        $result = $getMemberCards->handleResult($getMemberCards($dto));

        if ($result->data) {
            $user = Auth::user();
            $transformedData = $result->data->map(function ($card) use ($user) {
                return new CardResource($card, $user);
            });

            return response()->json([
                'status' => $result->status,
                'data' => $transformedData,
                'error' => $result->error,
            ], Response::HTTP_OK);
        }

        return response()->json()->api(
            $result,
            Response::HTTP_OK
        );
    }

    /**
     * Get specific card for a group member.
     *
     * @response array{
     *   status: bool,
     *   error: array{error: string, code: int},
     *   data: array{
     *     id: int,
     *     group_member_id: int,
     *     hash: string,
     *     last_digits: string,
     *     status: string,
     *     comment: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     access_level: "view"|"manage"
     *   }
     * }
     */
    public function getMemberCard(int $memberId, int $cardId, GetMemberCard $getMemberCard)
    {
        $dto = new GetMemberCardDto(
            memberId: $memberId,
            cardId: $cardId,
            user: Auth::user()
        );

        $result = $getMemberCard->handleResult($getMemberCard($dto));

        if ($result->data && isset($result->data['card'])) {
            $user = Auth::user();
            $transformedCard = new CardResource($result->data['card'], $user);
            $transformedData = [
                'card' => $transformedCard,
                'history' => $result->data['history'],
            ];

            return response()->json([
                'status' => $result->status,
                'data' => $transformedData,
                'error' => $result->error,
            ], Response::HTTP_OK);
        }

        return response()->json()->api(
            $result,
            Response::HTTP_OK
        );
    }
}
