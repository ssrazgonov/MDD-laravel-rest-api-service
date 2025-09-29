<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\ActivateCard;
use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Actions\DeactivateCard;
use App\Modules\Card\Actions\GetCard;
use App\Modules\Card\Actions\GetCards;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\GetCardDto;
use App\Modules\Card\Dto\Actions\GetCardsDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Requests\GetUserCardsRequest;
use App\Modules\Card\Http\Resources\CardResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: CardResource[]}
     */
    public function index(GetUserCardsRequest $request, GetCards $getCards)
    {
        return CardResource::collection($getCards(new GetCardsDto(
            user: Auth::user(),
            status: CardStatus::tryFrom($request->status),
            memberId: null
        )))->additional([
            'status' => true,
            'error' => null,
        ]);
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: CardResource}
     */
    public function store(CreateCardRequest $request, CreateCard $createCard): CardResource
    {
        return new CardResource($createCard(CreateCardDto::fromArray(
            $request->validated()
        )));
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function activate(int $id, ActivateCard $activateCard)
    {
        return response()->json()->api(
            $activateCard(new ActivateCardDto(id: $id, user: Auth::user())),
            Response::HTTP_OK
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function deactivate(int $id, DeactivateCard $deactivateCard)
    {
        return response()->json()->api(
            $deactivateCard(new DeactivateCardDto(id: $id, user: Auth::user())),
            Response::HTTP_OK
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: CardResource}
     */
    public function show(int $id, GetCard $getCard): CardResource
    {
        return new CardResource($getCard(
            new GetCardDto(
                cardId: $id,
                user: Auth::user(),
                memberId: null
            )
        ));
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
    public function getMemberCards(Request $request, int $memberId, GetCards $getCards)
    {
        return CardResource::collection($getCards(new GetCardsDto(
            memberId: $memberId,
            user: Auth::user(),
            status: $request->status
        )))->additional([
            'status' => true,
            'error' => null,
        ]);
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
    public function getMemberCard(int $memberId, int $cardId, GetCard $getCard)
    {
        return new CardResource($getCard(
            new GetCardDto(
                cardId: $cardId,
                user: Auth::user(),
                memberId: $memberId
            )
        ));
    }
}
