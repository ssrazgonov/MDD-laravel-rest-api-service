<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\Group;
use App\Modules\User\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMemberFactory extends Factory
{
    protected $model = GroupMember::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'type' => $this->faker->randomElement(['primary', 'dependent']),
            'name' => $this->faker->name(),
            'is_active' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'primary',
        ]);
    }

    public function dependent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'dependent',
        ]);
    }
}
