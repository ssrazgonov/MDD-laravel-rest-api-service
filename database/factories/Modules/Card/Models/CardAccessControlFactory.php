<?php

namespace Database\Factories\Modules\Card\Models;

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\Card\Models\Card;
use App\Modules\Card\Models\CardAccessControl;
use App\Modules\User\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardAccessControlFactory extends Factory
{
    protected $model = CardAccessControl::class;

    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'group_member_id' => GroupMember::factory(),
            'access_level' => $this->faker->randomElement([CardAccessLevel::VIEW, CardAccessLevel::MANAGE]),
        ];
    }

    public function view()
    {
        return $this->state(fn () => [
            'access_level' => CardAccessLevel::VIEW,
        ]);
    }

    public function manage()
    {
        return $this->state(fn () => [
            'access_level' => CardAccessLevel::MANAGE,
        ]);
    }
}
