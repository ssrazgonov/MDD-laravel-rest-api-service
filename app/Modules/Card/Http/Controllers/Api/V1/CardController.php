<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\CreateCard;
use App\Modules\Card\Dto\Actions\CreateCardDto;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Resources\CardResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function store(CreateCardRequest $request, CreateCard $createCard)
    {
        $createCardResult = $createCard(CreateCardDto::fromArray($request->validated()));

        if (!$createCardResult->status) {
            return response()->json($createCardResult, 500);
        }

        return response()->json($createCardResult, 200);
    }
}