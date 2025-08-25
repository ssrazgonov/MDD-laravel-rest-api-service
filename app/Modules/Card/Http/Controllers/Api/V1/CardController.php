<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\ActivateCard;
use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Actions\DeactivateCard;
use App\Modules\Card\Dto\Actions\ActivateCardDto;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Dto\Actions\DeactivateCardDto;
use App\Modules\Card\Http\Requests\ActivateCardRequest;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Requests\DeactivateCardRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Modules\Card\Models\Card;

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: Card}
     */
    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        $createCardData = $request->validated();
        $createCardData['user_id'] = auth()->user()->id;

        return response()->json()->api(
            $createCard->handleResult($createCard(CreateCardDto::fromArray($createCardData))),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function activate(ActivateCardRequest $request, ActivateCard $activateCard)
    {
        return response()->json()->api(
            $activateCard->handleResult($activateCard(ActivateCardDto::fromArray($request->validated()))),
            Response::HTTP_CREATED
        );
    }

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: null}
     */
    public function deactivate(DeactivateCardRequest $request, DeactivateCard $deactivateCard) {
        return response()->json()->api(
            $deactivateCard->handleResult($deactivateCard(DeactivateCardDto::fromArray($request->validated()))),
            Response::HTTP_CREATED
        ); 
    }

    public function history() {}
}
