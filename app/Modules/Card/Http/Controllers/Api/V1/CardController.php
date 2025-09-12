<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\ActivateCard;
use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Actions\DeactivateCard;
use App\Modules\Card\Actions\GetCardWithHistory;
use App\Modules\Card\Actions\GetUserCards;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Dto\Actions\GetCardWithHistoryDto;
use App\Modules\Card\Dto\Actions\GetUserCardsDto;
use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Requests\GetUserCardsRequest;
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
     * @response array{status: bool, error: array{error: string, code: int} , data: Card[]}
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
     * @response array{status: bool, error: array{error: string, code: int} , data: Card}
     */
    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        $createCardData = $request->validated();
        $userId = Auth::id();
        $createCardData['user_id'] = $userId;

        return response()->json()->api(
            $createCard->handleResult($createCard(CreateCardDto::fromArray($createCardData))),
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
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function deactivate(int $id, DeactivateCard $deactivateCard)
    {
        return response()->json()->api(
            $deactivateCard->handleResult($deactivateCard(new DeactivateCardDto(id: $id, user: Auth::user()))),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: array{card: Card, history: CardStatusHistory[]}}
     */
    public function show(int $id, GetCardWithHistory $getCardWithHistory)
    {
        return response()->json()->api(
            $getCardWithHistory->handleResult($getCardWithHistory(new GetCardWithHistoryDto(id: $id, user: Auth::user()))),
            Response::HTTP_OK,
        );
    }
}
