<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\ActivateCard;
use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Actions\DeactivateCard;
use App\Modules\Card\Actions\GetUserCards;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
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
use App\Modules\Card\Models\Card;

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: Card[]}
     */
    public function index(GetUserCardsRequest $request, GetUserCards $getUserCards)
    {
        $dto = new GetUserCardsDto(userId: Auth::id(), status: CardStatus::tryFrom($request->status));

        return response()->json()->api(
            $getUserCards->handleResult($getUserCards($dto)),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: Card}
     */
    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        $createCardData = $request->validated();
        $createCardData['user_id'] = Auth::id();

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
            $activateCard->handleResult($activateCard(new ActivateCardDto(id: $id))),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function deactivate(int $id, DeactivateCard $deactivateCard)
    {
        return response()->json()->api(
            $deactivateCard->handleResult($deactivateCard(new DeactivateCardDto(id: $id))),
            Response::HTTP_CREATED
        );
    }
}
