<?php

namespace Database\Factories\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'member_id' => null, // Will be set when creating
            'hash' => hash('sha256', '4111111111111111'),
            'last_digits' => '1111',
            'status' => CardStatus::ACTIVE,
            'comment' => $this->faker->sentence(),
        ];
    }
}
