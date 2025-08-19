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

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        return response()->json(
            $createCard->handleResult($createCard(CreateCardDto::fromArray($request->validated()))),
            Response::HTTP_CREATED
        );
    }

    public function activate(ActivateCardRequest $request, ActivateCard $activateCard)
    {
        return response()->json(
            $activateCard->handleResult($activateCard(ActivateCardDto::fromArray($request->validated()))),
            Response::HTTP_CREATED
        );
    }

    public function deactivate(DeactivateCardRequest $request, DeactivateCard $deactivateCard) {
        return response()->json()->api(
            $deactivateCard->handleResult($deactivateCard(DeactivateCardDto::fromArray($request->validated()))),
            Response::HTTP_CREATED
        ); 
    }

    public function history() {}
}
