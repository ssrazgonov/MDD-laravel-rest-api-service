<?php

namespace App\Modules\Card\Http\Resources;

use App\Base\ApiResource;
use App\Modules\Card\Models\Card;
use Illuminate\Http\Request;

/**
 * this for scramble.
 *
 * @mixin Card
 */
class CardResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [...parent::toArray($request), ...[

            'status_history' => $this->whenLoaded('statusHistory'),

            /* @var CardAccessLevel  */
            'access_level' => $this->cardAccessControlForCurrentUser()->value,
        ]];
    }
}
