<?php

namespace Database\Factories\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'group_member_id' => GroupMember::factory(),
            'hash' => hash('sha256', $this->faker->numerify('##################')),
            'last_digits' => $this->faker->numerify('####'),
            'status' => CardStatus::ACTIVE,
            'comment' => $this->faker->sentence(),
        ];
    }
}
