<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use App\Modules\Card\Actions\CreateCardAction;
use App\Modules\Card\Dto\Actions\CreateCardActionDto;
use App\Modules\Card\Http\Requests\CreateCardRequest;
use App\Modules\Card\Http\Resources\CardResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function store(CreateCardRequest $request, CreateCardAction $createCardAction)
    {
        $cardData = CreateCardActionDto::fromArray($request->validated());

        return new CardResource($createCardAction($cardData));
    }
}