<?php

namespace App\Modules\Card\Http\Resources;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Card
 */
class CardResource extends JsonResource
{
    private $user;

    public function __construct(Card $resource, $user = null)
    {
        parent::__construct($resource);
        $this->user = $user;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_member_id' => $this->group_member_id,
            'hash' => $this->hash,
            'last_digits' => $this->last_digits,
            'status' => $this->status,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            /* @scramble-enum view,manage */
            'access_level' => $this->getAccessLevelForUser(),
        ];
    }

    private function getAccessLevelForUser(): string
    {
        if (! $this->user) {
            return CardAccessLevel::VIEW->value;
        }

        $accessControl = $this->cardAccessControlForUser($this->user->group_member_id)->first();

        return $accessControl ? $accessControl->access_level->value : CardAccessLevel::VIEW->value;
    }

    private function cardAccessControlForUser(int $memberId)
    {
        return $this->resource->cardAccessControlForUser($memberId);
    }
}
