<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\GroupMember;
use App\Modules\User\Models\GroupMemberIdentity;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMemberIdentityFactory extends Factory
{
    protected $model = GroupMemberIdentity::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['keycloak', 'snils']);

        return [
            'group_member_id' => GroupMember::factory(),
            'type' => $this->faker->randomElement(['keycloak', 'snils']),
            'value' => $type == '' ? User::factory()->create()->id : hash('sha256', $this->faker->numerify('###-###-### ##')),
            'is_primary' => true,
        ];
    }

    public function keycloack()
    {
        return $this->state(fn () => [
            'type' => 'keycloak',
            'value' => User::factory()->create()->id,
        ]);
    }

    public function snils()
    {
        return $this->state(fn () => [
            'type' => 'snils',
            'value' => hash('sha256', $this->faker->numerify('###-###-### ##')),
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => false,
        ]);
    }
}
