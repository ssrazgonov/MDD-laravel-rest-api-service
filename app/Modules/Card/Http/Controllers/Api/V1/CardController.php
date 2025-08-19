<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Http\Requests\CreateCardRequest;
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
        $createCardResult = $createCard->handleResult($createCard(CreateCardDto::fromArray($request->validated())));

        return response()->json($createCardResult, Response::HTTP_CREATED);
    }
}