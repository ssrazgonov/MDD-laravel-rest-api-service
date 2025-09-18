<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['family', 'organization', 'individual']),
            'is_active' => true,
        ];
    }

    public function family(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'family',
            'name' => 'Семья ' . $this->faker->lastName(),
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'individual',
            'name' => 'Персональная группа',
        ]);
    }
}
